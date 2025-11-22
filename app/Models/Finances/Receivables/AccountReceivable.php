<?php

namespace App\Models\Finances\Receivables;

use App\Models\Entities\Customers\Customer;
use App\Models\Sales\Budgets\Budget;
use App\Traits\HasCustomerScope;
use Illuminate\Database\Eloquent\Model;

class AccountReceivable extends Model
{
    use HasCustomerScope;

    protected $casts = ['first_payment'=>'date','end_recurrence'=>'date'];

    public function customer(){ return $this->belongsTo(Customer::class); }

    public function budget(){ return $this->belongsTo(Budget::class); }
}
