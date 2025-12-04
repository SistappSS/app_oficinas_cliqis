<?php

namespace App\Models\Finances;

use App\Models\ServiceOrders\ServiceOrderInvoice;
use App\Traits\HasCustomerScope;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class AccountReceivablePayment extends Model
{
    use HasUuids;
    use HasCustomerScope;

    public $incrementing = false;
    protected $keyType = 'string';
    protected $guarded = [];

    protected $casts = [
        'paid_at' => 'date',
    ];

    public function invoice()
    {
        return $this->belongsTo(ServiceOrderInvoice::class);
    }
}
