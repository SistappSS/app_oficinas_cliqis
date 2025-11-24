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


        return view('app.dashboards.dashboard', compact(
            'activeCustomers',
        ));
    }
}
