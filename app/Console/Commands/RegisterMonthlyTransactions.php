<?php

namespace App\Console\Commands;

use App\Models\Sales\Budgets\BudgetMonthlyItem;
use App\Models\Sales\Budgets\MonthlyTransaction;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class RegisterMonthlyTransactions extends Command
{
    protected $signature = 'register:monthly-overdue';
    protected $description = 'Register overdue monthly items';

    public function handle()
    {
        Log::info('CRON rodou em ' . now());

        $monthlyItems = BudgetMonthlyItem::all();

        $month = now()->month;
        $year = now()->year;

        foreach ($monthlyItems as $item) {
            $exists = MonthlyTransaction::where('budget_monthly_item_id', $item->id)
                ->where('reference_month', $month)
                ->where('reference_year', $year)
                ->exists();

            if (!$exists) {
                MonthlyTransaction::create([
                    'budget_monthly_item_id' => $item->id,
                    'user_id' => $item->user_id,
                    'customer_sistapp_id' => $item->customer_sistapp_id,
                    'reference_month' => $month,
                    'reference_year' => $year,
                    'amount' => $item->price,
                    'status' => 'pending',
                    'payment_date' => \Carbon\Carbon::createFromFormat('Y m d', "$year $month {$item->payment_day}"),
                ]);

                $this->info("Pendência registrada para o item: {$item->id}");
            } else {
                $this->info("Pendência já registrada para o item: {$item->id}");
            }
        }

        $this->info('Verificação de pendências concluída.');
    }
}
