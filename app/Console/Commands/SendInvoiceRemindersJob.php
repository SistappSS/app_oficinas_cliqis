<?php

namespace App\Console\Commands;

use App\Mail\InvoiceReminderMail;
use App\Models\Sales\Invoices\Invoice;
use App\Models\Sales\Invoices\ReminderInvoiceConfig;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class SendInvoiceRemindersJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        $today = now()->startOfDay();

        Invoice::withoutGlobalScopes() // garante que vê todos os tenants
        ->with('customer')
            ->where('auto_reminder', true)
            ->whereIn('status', ['pending', 'overdue'])
            ->whereBetween('due_date', [
                $today->copy()->subDay()->toDateString(),    // -1
                $today->copy()->addDays(3)->toDateString(),  // +3
            ])
            ->chunkById(100, function ($invoices) use ($today) {
                foreach ($invoices as $invoice) {
                    if (!$invoice->customer) {
                        continue;
                    }

                    $email = $invoice->customer->company_email
                        ?? $invoice->customer->email
                        ?? null;

                    if (!$email || !$invoice->due_date) {
                        continue;
                    }

                    $due = $invoice->due_date->copy()->startOfDay();
                    $daysDiff = $today->diffInDays($due, false);

                    $trigger = match ($daysDiff) {
                        3  => 'before_3_days',
                        0  => 'on_due_date',
                        -1 => 'after_1_day',
                        default => null,
                    };

                    if (!$trigger) {
                        continue;
                    }

                    // já mandou hoje? não manda de novo
                    if ($invoice->last_sent_at && $invoice->last_sent_at->greaterThanOrEqualTo($today)) {
                        continue;
                    }

                    $config = ReminderInvoiceConfig::where('customer_sistapp_id', $invoice->customer_sistapp_id)
                        ->where('trigger', $trigger)
                        ->where('is_active', true)
                        ->first();

                    Mail::to($email)->queue(
                        new InvoiceReminderMail($invoice, $config, $trigger)
                    );

                    $invoice->forceFill([
                        'sent_count'   => $invoice->sent_count + 1,
                        'last_sent_at' => now(),
                    ])->save();
                }
            });
    }
}
