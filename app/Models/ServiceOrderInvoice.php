<?php

namespace App\Models\ServiceOrders;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ServiceOrderInvoice extends Model
{
    use HasFactory;

    protected $table = 'service_order_invoices';

    public $incrementing = false;
    protected $keyType   = 'string';

    protected $fillable = [
        'id',
        'service_order_id',
        'number',
        'amount',
        'payment_date',
        'payment_method',
        'installments',
        'status',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $model) {
            if (empty($model->id)) {
                $model->id = (string) Str::uuid();
            }
        });
    }

    public function serviceOrder()
    {
        return $this->belongsTo(ServiceOrder::class);
    }
}
