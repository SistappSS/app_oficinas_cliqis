<?php

namespace App\Services;

use App\Models\Entities\Customers\Customer;
use App\Models\Sales\Budgets\Budget;
use App\Models\Sales\Budgets\BudgetInstallment;
use App\Models\Sales\Budgets\BudgetItem;
use App\Models\Sales\Budgets\BudgetMonthlyItem;
use App\Models\Sales\Budgets\BudgetYearlyItem;
use App\Models\Sales\Services\Service;
use App\Support\CustomerContext;
use App\Support\TenantSequence;
use Illuminate\Support\Facades\DB;

class BudgetBuilderService
{
    private function moneyToFloat(string $s): float
    {
        $s = preg_replace('/[^\d\.,-]/', '', $s ?? '');
        // se só tem vírgula -> formato BR
        if (strpos($s, ',') !== false && strpos($s, '.') === false) {
            $s = str_replace(['.', ','], ['', '.'], $s);
        } else {
            // assume ponto como decimal
            $s = str_replace(',', '', $s);
        }
        return (float)$s;
    }

    public function create(array $payload): Budget
    {
        return DB::transaction(function () use ($payload) {
            $tenant = CustomerContext::get() ?: auth()->user()->customerLogin->customer_sistapp_id;
            $code   = TenantSequence::next('budget', $tenant); // << AQUI

            $subtotal  = (float)($payload['subtotal_price'] ?? 0);
            $signalPct = (float)($payload['signal'] ?? 0);
            $signalVal = (float)($payload['signal_price'] ?? 0);
            $discounts = $payload['discount_price'] ?? [];

            $sumInstallments = 0.0;
            if (!empty($payload['installments_price'])) {
                $sumInstallments = array_sum(array_map('floatval', $payload['installments_price']));
            } elseif (!empty($payload['installments_value'][0])) {
                if (preg_match('/(\d+)x.*?([\d\.,]+)/', $payload['installments_value'][0], $m)) {
                    $count = (int)($m[1] ?? 0);
                    $val   = $this->moneyToFloat((string)($m[2] ?? '0'));
                    $sumInstallments = $count * $val;
                }
            }
            if ($sumInstallments <= 0 && $subtotal > 0) {
                $sumInstallments = max(0, $subtotal - $signalVal);
            }
            $totalBudget = $signalVal + $sumInstallments;

            $customer = Customer::findOrFail($payload['customer_id']); // escopo já valida tenant

            $budget = Budget::create([
                'customer_sistapp_id' => $tenant,
                'user_id'             => auth()->id(),
                'budget_code'         => $code,
                'customer_id'         => $customer->id,
                'status'              => 'pending',
                'payment_date'        => $payload['payment_date'],
                'signal_date'         => $payload['payment_date'],
                'deadline'            => $payload['deadline'],
                'signal'              => $signalPct,
                'signal_price'        => $signalVal,
                'remaining_price'     => $sumInstallments,
                'total_budget_price'  => $totalBudget,
                'discount_percent'    => (float)($payload['discount_percent'] ?? 0),
                'discount_scope'      => $payload['discount_scope'] ?? 'all',
                'customer_email'      => $customer->company_email ?? null,
            ]);

            // ... (resto do seu código de itens e parcelas igual)
            $globalPct  = (float)($payload['discount_percent'] ?? 0);
            $scope      = $payload['discount_scope'] ?? 'all';
            $serviceIds     = $payload['service_id'] ?? [];
            $prices         = $payload['price'] ?? [];
            $paymentMethods = $payload['payment_method'] ?? [];
            $budgetItemsCreated = [];

            foreach ($serviceIds as $i => $serviceId) {
                $service = Service::find($serviceId);
                if (!$service) continue;

                $applyGlobal = ($scope === 'all') || ($scope === 'one' && $service->type === 'payment_unique');

                $rawPrice   = (float)($prices[$i] ?? $service->price);
                $itemPct    = (float)($discounts[$i] ?? 0);
                $effPct     = $itemPct + ($applyGlobal ? $globalPct : 0);
                $finalPrice = $rawPrice * (1 - $effPct / 100);

                if ($service->type === 'payment_unique') {
                    $pm = (string)($paymentMethods[$i] ?? '1');
                    $budgetItemsCreated[] = BudgetItem::create([
                        'customer_sistapp_id'  => $budget->customer_sistapp_id,
                        'user_id'              => $budget->user_id,
                        'budget_id'            => $budget->id,
                        'service_id'           => $serviceId,
                        'item_price'           => $rawPrice,
                        'discount_price'       => (int) $effPct,
                        'price_with_discount'  => $finalPrice,
                        'payment_method'       => $pm,
                    ]);
                } elseif ($service->type === 'monthly') {
                    $day = \Carbon\Carbon::parse($budget->payment_date)->day;
                    BudgetMonthlyItem::create([
                        'customer_sistapp_id'  => $budget->customer_sistapp_id,
                        'user_id'              => $budget->user_id,
                        'budget_id'            => $budget->id,
                        'service_id'           => $serviceId,
                        'price'                => $rawPrice,
                        'discount_price'       => (int) $effPct,
                        'price_with_discount'  => $finalPrice,
                        'payment_day'          => $day,
                    ]);
                } else {
                    $d = \Carbon\Carbon::parse($budget->payment_date);
                    BudgetYearlyItem::create([
                        'customer_sistapp_id'  => $budget->customer_sistapp_id,
                        'user_id'              => $budget->user_id,
                        'budget_id'            => $budget->id,
                        'service_id'           => $serviceId,
                        'price'                => $finalPrice,
                        'payment_day'          => $d->day,
                        'payment_month'        => $d->month,
                    ]);
                }
            }

            $this->buildInstallments($budget, $budgetItemsCreated, $payload);
            return $budget;
        });
    }

    private function buildInstallments(Budget $budget, array $budgetItemsCreated, array $payload): void
    {
        $paymentDate = \Carbon\Carbon::parse($budget->payment_date);
        $itemId = optional($budgetItemsCreated[0] ?? null)->id;

        // 0) Sinal
        if ($budget->signal_price > 0 && $itemId) {
            BudgetInstallment::create([
                'customer_sistapp_id' => $budget->customer_sistapp_id,
                'user_id'             => $budget->user_id,
                'budget_id'           => $budget->id,
                'budget_item_id'      => $itemId,
                'installment_number'  => 0,
                'payment_date'        => $paymentDate->toDateString(),
                'price'               => $budget->signal_price,
                'status'              => 'pending',
            ]);
        }

        $prices = $payload['installments_price'] ?? [];
        $dates  = $payload['installments_date'] ?? [];
        $agg    = $payload['installments_value'][0] ?? null;

        // 1) Preferir valores explícitos
        if (!empty($prices)) {
            foreach ($prices as $i => $raw) {
                $price = (float)str_replace([','], ['.'], preg_replace('/[^\d,\.]/', '', $raw));
                $date  = $dates[$i] ?? $paymentDate->copy()->addMonths($i + 1)->toDateString();

                BudgetInstallment::create([
                    'customer_sistapp_id' => $budget->customer_sistapp_id,
                    'user_id'             => $budget->user_id,
                    'budget_id'           => $budget->id,
                    'budget_item_id'      => $itemId,
                    'installment_number'  => $i + 1,
                    'payment_date'        => $date,
                    'price'               => $price,
                    'status'              => 'pending',
                ]);
            }
            return;
        }

        // 2) Compat: só "3x - R$ 200,00" (sem prices[])
        if ($agg && preg_match('/(\d+)x/i', $agg, $m)) {
            $count     = max(0, (int)$m[1]);
            $remaining = max(0, (float)($payload['subtotal_price'] ?? 0) - (float)$budget->signal_price);

            if ($count > 0 && $remaining > 0) {
                $firstDue = $paymentDate->copy()->addDays((int)$budget->deadline);
                $each     = round($remaining / $count, 2);

                for ($i = 1; $i <= $count; $i++) {
                    BudgetInstallment::create([
                        'customer_sistapp_id' => $budget->customer_sistapp_id,
                        'user_id'             => $budget->user_id,
                        'budget_id'           => $budget->id,
                        'budget_item_id'      => $itemId,
                        'installment_number'  => $i,
                        'payment_date'        => $firstDue->copy()->addMonths($i - 1)->toDateString(),
                        'price'               => ($i < $count) ? $each : round($remaining - $each * ($count - 1), 2),
                        'status'              => 'pending',
                    ]);
                }
            }
            return;
        }

        // 3) Sem nada: cria 1x “à vista” com o restante
        $remaining = max(0, (float)($payload['subtotal_price'] ?? 0) - (float)$budget->signal_price);
        if ($remaining > 0) {
            $firstDue = $paymentDate->copy()->addDays((int)$budget->deadline)->toDateString();

            BudgetInstallment::create([
                'customer_sistapp_id' => $budget->customer_sistapp_id,
                'user_id'             => $budget->user_id,
                'budget_id'           => $budget->id,
                'budget_item_id'      => $itemId,
                'installment_number'  => 1,
                'payment_date'        => $firstDue,
                'price'               => $remaining,
                'status'              => 'pending',
            ]);
        }
    }
}
