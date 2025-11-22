<?php

// app/Mail/BudgetPdfMail.php
namespace App\Mail;

use App\Models\Sales\Budgets\Budget;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class BudgetPdfMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Budget $budget, public string $pdfBinary) {}

    // app/Mail/BudgetPdfMail.php
    public function build()
    {
        $subject = 'Orçamento #' . ($this->budget->code ?? $this->budget->id);

        $mail = $this->subject($subject)
            ->view('mail.budget_simple')
            ->attachData(
                $this->pdfBinary,
                'orcamento-' . ($this->budget->code ?? $this->budget->id) . '.pdf',
                ['mime' => 'application/pdf']
            );

        if (auth()->check()) {
            $mail->from(
                auth()->user()->company_email ?? config('mail.from.address'),
                auth()->user()->company_name  ?? auth()->user()->name ?? config('mail.from.name')
            );
        }

        return $mail;
    }
}
