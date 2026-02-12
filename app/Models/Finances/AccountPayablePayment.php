<?php

namespace App\Models\Finances;

use App\Models\AccountPayablePaymentAdjustment;
use App\Models\Entities\Users\User;
use App\Traits\HasCustomerScope;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class AccountPayablePayment extends Model
{
    use HasUuids;
    use HasCustomerScope;

    public $incrementing = false;
    protected $keyType = 'string';
    protected $guarded = [];


    protected $casts = [
        'meta' => 'array',
        'paid_at' => 'date',
        'amount'  => 'decimal:2',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function recurrence()
    {
        return $this->belongsTo(AccountPayableRecurrence::class, 'payable_recurrence_id');
    }

    public function accountPayable()
    {
        // acesso indireto à conta mãe
        return $this->hasOneThrough(
            AccountPayable::class,
            AccountPayableRecurrence::class,
            'id',                 // chave local em recurrence
            'id',                 // chave local em payable
            'payable_recurrence_id', // fk neste model -> recurrence
            'account_payable_id'     // fk em recurrence -> payable
        );
    }

    public function adjustments()
    {
        return $this->hasMany(AccountPayablePaymentAdjustment::class, 'payment_id');
    }
}
