<?php

namespace App\Console\Commands;

use App\Models\Sales\Budgets\Subscriptions\Subscription;
use App\Models\Sales\Invoices\Invoice;
use App\Models\Sales\Invoices\InvoiceItem;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class GenerateRecurringInvoices extends Command {
    protected $signature = 'invoices:generate-recurring';
    protected $description = 'Gera faturas para assinaturas ativas quando chegar a data de vencimento';

    public function handle(): int {
        $today = now()->startOfDay();
        Subscription::where('active',true)->whereDate('next_due_date','<=',$today)->chunkById(200, function($subs){
            foreach($subs as $s){
                DB::transaction(function() use ($s){
                    $inv = Invoice::create([
                        'customer_sistapp_id'=>$s->customer_sistapp_id,
                        'user_id'=>$s->user_id,
                        'budget_id'=>$s->budget_id,
                        'customer_id'=>$s->customer_id,
                        'number'=>$this->nextNumber(),
                        'due_date'=>$s->next_due_date,
                        'amount'=>$s->amount,
                        'installments'=>1,
                        'is_recurring'=>true,
                        'recurring_period'=>$s->period,
                        'auto_reminder'=>$s->auto_reminder,
                        'status'=>'pending'
                    ]);
                    InvoiceItem::create([
                        'invoice_id'=>$inv->id,
                        'description'=>$s->name,
                        'qty'=>1,
                        'unit_amount'=>$s->amount,
                        'type'=>'subscription'
                    ]);

                    // empurra próximo vencimento
                    $next = $s->period==='monthly'
                        ? $s->next_due_date->clone()->addMonth()->startOfDay()
                        : $s->next_due_date->clone()->addYear()->startOfDay();
                    $s->update(['next_due_date'=>$next]);
                });
            }
        });
        return self::SUCCESS;
    }

    private function nextNumber(): string {
        $last = Invoice::max('id') ?? 0;
        return '#'.str_pad((string)(230 + $last + 1), 6, '0', STR_PAD_LEFT);
    }
}
