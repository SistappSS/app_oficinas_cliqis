<?php

namespace App\Models\Finances\Payables;

use App\Models\Entities\Users\User;
use App\Traits\HasCustomerScope;
use Illuminate\Database\Eloquent\Model;

class AccountPayableRecurrence extends Model
{
    use HasCustomerScope;

    protected $guarded = [];

    protected $casts = [
        'due_date'    => 'date',
        'paid_at'     => 'date',
        'amount'      => 'decimal:2',
        'amount_paid' => 'decimal:2',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function accountPayable()
    {
        return $this->belongsTo(AccountPayable::class);
    }

    public function payments()
    {
        return $this->hasMany(AccountPayablePayment::class, 'payable_recurrence_id');
    }
}
