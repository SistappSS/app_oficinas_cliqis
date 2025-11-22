<?php

namespace App\Mail;

use App\Models\Sales\Invoices\Invoice;
use App\Models\Sales\Invoices\ReminderInvoiceConfig;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class InvoiceReminderMail extends Mailable
{
    use Queueable, SerializesModels;

    public Invoice $invoice;
    public ?ReminderInvoiceConfig $config;
    public string $context;

    public function __construct(Invoice $invoice, ?ReminderInvoiceConfig $config, string $context = 'manual')
    {
        $this->invoice = $invoice;
        $this->config  = $config;
        $this->context = $context;
    }

    protected function normalizePlaceholders(string $text): string
    {
        $names = [
            'customer_name',
            'invoice_number',
            'amount',
            'due_date',
            'days_diff',
            'days_diff_label',
        ];

        foreach ($names as $name) {
            $pattern = '/\{\{\s*' . $name . '\s*\}\}/u';
            $text    = preg_replace($pattern, '{{' . $name . '}}', $text);
        }

        return $text;
    }

    protected function makeDaysDiffLabel(?int $daysDiff): string
    {
        if ($daysDiff === null) {
            return '';
        }

        if ($daysDiff > 1) {
            return "vence em {$daysDiff} dias";
        }
        if ($daysDiff === 1) {
            return "vence amanhã";
        }
        if ($daysDiff === 0) {
            return 'vence hoje';
        }
        if ($daysDiff === -1) {
            return 'venceu há 1 dia';
        }

        return 'venceu há ' . abs($daysDiff) . ' dias';
    }

    /**
     * Gera subject + body já com variáveis substituídas.
     */
    public function compose(): array
    {
        $invoice = $this->invoice;

        $due   = $invoice->due_date?->copy()->startOfDay();
        $today = now()->startOfDay();

        $daysDiff = $due
            ? $today->diffInDays($due, false)
            : null;

        $daysLabel    = $this->makeDaysDiffLabel($daysDiff);
        $amountBr     = 'R$ ' . number_format($invoice->amount, 2, ',', '.');
        $dueBr        = $due ? $due->format('d/m/Y') : '-';
        $customerName = $invoice->customer->name ?? 'Cliente';

        $replacements = [
            '{{customer_name}}'   => $customerName,
            '{{invoice_number}}'  => $invoice->number,
            '{{amount}}'          => $amountBr,
            '{{due_date}}'        => $dueBr,
            '{{days_diff}}'       => (string) ($daysDiff ?? 0),
            '{{days_diff_label}}' => $daysLabel,
        ];

        $subjectTemplate = $this->config?->subject
            ?: 'Lembrete de vencimento - {{invoice_number}}';

        $bodyTemplate = $this->config?->body
            ?: 'Olá {{customer_name}}, sua fatura {{invoice_number}} no valor de {{amount}} {{days_diff_label}} (vencimento em {{due_date}}).';

        // normaliza {{ customer_name }} -> {{customer_name}}
        $subjectTemplate = $this->normalizePlaceholders($subjectTemplate);
        $bodyTemplate    = $this->normalizePlaceholders($bodyTemplate);

        $subject = strtr($subjectTemplate, $replacements);
        $body    = strtr($bodyTemplate, $replacements);

        return [$subject, $body];
    }

    public function build()
    {
        [$subject, $body] = $this->compose();

        return $this->subject($subject)
            ->view('mail.invoices.reminder')
            ->with([
                'invoice' => $this->invoice,
                'body'    => $body,
            ]);
    }
}
