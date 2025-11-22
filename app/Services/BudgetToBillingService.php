<?php

namespace App\Services;

use App\Models\Sales\Budgets\{Budget, BudgetInstallment, BudgetMonthlyItem, BudgetYearlyItem};
use App\Models\Sales\Budgets\Subscriptions\Subscription;
use App\Models\Sales\Invoices\Invoice;
use App\Models\Sales\Invoices\InvoiceItem;
use App\Support\CustomerContext;
use App\Support\TenantSequence;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class BudgetToBillingService
{
    public function convert(Budget $budget): void
    {
        DB::transaction(function () use ($budget) {
            $this->createOneTimeInvoices($budget);

            $this->createSubscriptions($budget);
        });
    }
    protected function createOneTimeInvoices(Budget $budget): void
    {
        $installments = BudgetInstallment::query()
            ->where('budget_id', $budget->id)   // << troque o whereHas por este
            ->orderBy('installment_number')
            ->get();

        foreach ($installments as $inst) {
            $this->spawnInvoice(
                $budget,
                \Carbon\Carbon::parse($inst->payment_date),
                $inst->price,
                [[
                    'description' => $inst->installment_number == 0
                        ? "Sinal — Orçamento # {$budget->budget_code}"
                        : "Parcela {$inst->installment_number} — Orçamento #{$budget->budget_code}",
                    'unit_amount' => $inst->price,
                    'type'        => 'one_time',
                    'budget_installment_id' => $inst->id,
                ]]
            );
        }
    }

    protected function createSubscriptions(Budget $budget): void
    {
        $monthlyItems = BudgetMonthlyItem::where('budget_id', $budget->id)->get();

        foreach ($monthlyItems as $m) {

            $nextDue = $this->computeNextMonthlyDue($budget->payment_date, $m->payment_day);

            Subscription::create([
                'customer_sistapp_id'     => $budget->customer_sistapp_id,
                'user_id'                 => $budget->user_id,
                'budget_id'               => $budget->id,
                'customer_id'             => $budget->customer_id,

                'budget_monthly_item_id'  => $m->id,
                'budget_yearly_item_id'   => null,

                'name'                    => $m->service?->name ?? 'Mensalidade',
                'amount'                  => $m->price,
                'period'                  => 'monthly',
                'day_of_month'            => (int)$m->payment_day,
                'month_of_year'           => null,
                'next_due_date'           => $nextDue->toDateString(),
                'auto_reminder'           => true,
                'active'                  => true,
            ]);
        }

        $yearlyItems = BudgetYearlyItem::where('budget_id', $budget->id)->get();

        foreach ($yearlyItems as $y) {

            $nextDue = $this->computeNextYearlyDue($budget->payment_date, $y->payment_day, $y->payment_month);

            Subscription::create([
                'customer_sistapp_id'     => $budget->customer_sistapp_id,
                'user_id'                 => $budget->user_id,
                'budget_id'               => $budget->id,
                'customer_id'             => $budget->customer_id,

                'budget_monthly_item_id'  => null,
                'budget_yearly_item_id'   => $y->id,

                'name'                    => $y->service?->name ?? 'Anuidade',
                'amount'                  => $y->price,
                'period'                  => 'yearly',
                'day_of_month'            => (int)$y->payment_day,
                'month_of_year'           => (int)$y->payment_month,
                'next_due_date'           => $nextDue->toDateString(),
                'auto_reminder'           => true,
                'active'                  => true,
            ]);
        }
    }

    protected function spawnInvoice(Budget $budget, Carbon $due, float $amount, array $items): Invoice
    {
        $attempts = 0;
        while (true) {
            $attempts++;
            $number = $this->nextNumber();

            try {
                $inv = Invoice::create([
                    'customer_sistapp_id' => $budget->customer_sistapp_id,
                    'user_id'             => $budget->user_id,
                    'budget_id'           => $budget->id,
                    'customer_id'         => $budget->customer_id,
                    'number'              => $number,
                    'due_date'            => $due,
                    'amount'              => $amount,
                    'installments'        => 1,
                    'auto_reminder'       => true,
                    'status'              => 'pending',
                    // opcional: 'budget_installment_id' => $items['budget_installment_id'] ?? null,
                ]);
                break;
            } catch (\Illuminate\Database\QueryException $e) {
                if (($e->errorInfo[1] ?? null) !== 1062 || $attempts >= 3) throw $e;
                // gera outro número e tenta de novo
            }
        }

        foreach ($items as $it) {
            InvoiceItem::create([
                'invoice_id'  => $inv->id,
                'description' => $it['description'],
                'qty'         => $it['qty'] ?? 1,
                'unit_amount' => $it['unit_amount'],
                'type'        => $it['type'] ?? 'one_time',
                'service_id'  => $it['service_id'] ?? null,
            ]);
        }

        return $inv;
    }

    protected function nextNumber(): string
    {
        $tenant = CustomerContext::get();
        $n = TenantSequence::next('invoice', $tenant); // atômico por tenant

        return '#' . str_pad((string)$n, 6, '0', STR_PAD_LEFT);
    }

    private function computeNextMonthlyDue($basePaymentDate, $day): Carbon
    {
        // basePaymentDate vem de $budget->payment_date (ex: '2025-10-10')
        // Queremos exatamente esse mês/dia, sem empurrar pro próximo.
        $base = Carbon::parse($basePaymentDate);

        // força o dia configurado (payment_day), mas mantendo ano/mês do orçamento
        $due = Carbon::create(
            $base->year,
            $base->month,
            min(28, (int)$day) // você já limita pra 28 pra não quebrar em meses curtos
        );

        // IMPORTANTE: NÃO empurra se "já passou"
        // Nada de if ($due->isPast()) { ... }

        return $due;
    }

    private function computeNextYearlyDue($basePaymentDate, $day, $month): Carbon
    {
        $base = Carbon::parse($basePaymentDate);

        $due = Carbon::create(
            $base->year,
            (int)$month,           // mês fixo da anual
            min(28, (int)$day)
        );

        return $due;
    }

}
