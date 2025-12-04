<?php

namespace App\Models\ServiceOrders;

use App\Models\Finances\AccountReceivablePayment;
use App\Traits\HasCustomerScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ServiceOrderInvoice extends Model
{
    use HasFactory;
    use HasCustomerScope;

    protected $table = 'service_order_invoices';

    public $incrementing = false;
    protected $keyType   = 'string';

    protected $guarded = [];

    protected $casts = [
        'due_date' => 'date',
    ];

    public function serviceOrder()
    {
        return $this->belongsTo(ServiceOrder::class);
    }

    public function payments()
    {
        return $this->hasMany(AccountReceivablePayment::class);
    }
}
