<?php

namespace App\Http\Controllers\Application;

use App\Http\Controllers\Controller;
use App\Models\Entities\Customers\Customer;
use App\Models\Sales\Budgets\Budget;
use App\Models\Sales\Budgets\Subscriptions\Subscription;
use App\Models\Sales\Invoices\Invoice;
use App\Traits\RoleCheckTrait;


class DashboardController extends Controller
{
    use RoleCheckTrait;

    public $customer;

    public function __construct(Customer $customer)
    {
        $this->customer = $customer;
    }

    public function dashboard()
    {
        $query = Customer::query()
            ->where('is_active', true)
            ->latest();

        if (!$this->userHasRole('admin')) {
            $query->where(function ($q) {
                $q->whereNull('customerId')
                    ->orWhere('customerId', 'not like', 'cus\_%');
            });
        }

        $activeCustomers = $query->paginate(20);

        // BUDGETS
        $budgets = Budget::where('customer_sistapp_id', $this->customerSistappID())->get();
        $approvedBudgets = $budgets->where('status', 'approved')->count();
        $pendingBudgets = $budgets->where('status', 'pending')->count();

        // RECEIVABLES
        $start = now()->startOfDay();
        $end = now()->copy()->addDays(6)->endOfDay();

        // INVOICES não pagas/canceladas com due_date no intervalo
        $sumInv = Invoice::query()
            ->whereNotIn('status', ['paid', 'canceled'])
            ->whereBetween('due_date', [$start->toDateString(), $end->toDateString()])
            ->sum('amount');

        // SUBSCRIPTIONS ativas com próxima cobrança no intervalo
        $sumSub = Subscription::query()
            ->where('active', true)
            ->whereBetween('next_due_date', [$start->toDateString(), $end->toDateString()])
            ->sum('amount');

        $receivableWeek = (float)($sumInv + $sumSub);
        $invoices = (float)$sumInv;

        $subscriptions = Subscription::query()
            ->where('active', true)
            ->sum('amount');

        return view('app.dashboards.dashboard', compact(
            'activeCustomers',
            'approvedBudgets',
            'pendingBudgets',
            'receivableWeek',
            'invoices',
            'subscriptions',
        ));
    }
}
