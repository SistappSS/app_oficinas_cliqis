<?php

namespace App\Console\Commands;

use App\Models\Sales\Budgets\BudgetYearlyItem;
use App\Models\Sales\Budgets\YearlyTransaction;
use Illuminate\Console\Command;

class RegisterYearlyTransactions extends Command
{
    protected $signature = 'register:yearly-overdue';
    protected $description = 'Register overdue yearly items';

    public function handle()
    {
        $monthlyItems = BudgetYearlyItem::all();

        $year = now()->year;

        foreach ($monthlyItems as $item) {
            $exists = YearlyTransaction::where('budget_yearly_item_id', $item->id)
                ->where('reference_month', $item->payment_month)
                ->where('reference_year', $year)
                ->exists();

            if (!$exists) {
                YearlyTransaction::create([
                    'budget_yearly_item_id' => $item->id,
                    'user_id' => $item->user_id,
                    'customer_sistapp_id' => $item->customer_sistapp_id,
                    'reference_month' => $item->payment_month,
                    'reference_year' => $year,
                    'amount' => $item->service->price,
                    'status' => 'pending',
                    'payment_date' => \Carbon\Carbon::createFromFormat('Y m d', "{$year} {$item->payment_month} {$item->payment_day}"),
                ]);

                $this->info("Pendência registrada para o item: {$item->id}");
            } else {
                $this->info("Pendência já registrada para o item: {$item->id}");
            }
        }

        $this->info('Verificação de pendências concluída.');
    }
}
