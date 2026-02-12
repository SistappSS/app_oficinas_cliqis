<?php

namespace App\Models;

use App\Traits\HasCustomerScope;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class AccountPayablePaymentAdjustment extends Model
{
    use HasUuids, HasCustomerScope;

    protected $table = 'account_payable_payment_adjustments';

    public $incrementing = false;
    protected $keyType = 'string';
}
