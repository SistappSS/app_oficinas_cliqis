<?php

namespace App\Models\Finances\Payables;

use App\Models\Entities\Suppliers\Supplier;
use App\Models\Entities\Users\User;
use App\Traits\HasCustomerScope;
use Illuminate\Database\Eloquent\Model;

class AccountPayable extends Model
{
    use HasCustomerScope;

    protected $casts = [
        'default_amount' => 'decimal:2',
        'first_payment'  => 'date',
        'end_recurrence' => 'date',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function recurrences()
    {
        return $this->hasMany(AccountPayableRecurrence::class);
    }
}
