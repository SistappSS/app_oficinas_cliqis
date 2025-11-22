<?php

namespace App\Models\Finances\Receivables;

use App\Models\Sales\Budgets\Subscriptions\Subscription;
use App\Traits\HasCustomerScope;
use Illuminate\Database\Eloquent\Model;

class AccountReceivablePayment extends Model
{
    use HasCustomerScope;

    public function subscription()
    {
        return $this->belongsTo(Subscription::class);
    }
}
