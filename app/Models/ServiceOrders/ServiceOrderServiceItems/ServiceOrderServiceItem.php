<?php

namespace App\Models\ServiceOrders\ServiceOrderServiceItems;

use App\Models\Catalogs\Services\ServiceItems\ServiceItem;
use App\Models\Catalogs\Services\ServiceTypes\ServiceType;
use App\Models\ServiceOrders\ServiceOrder;
use App\Traits\HasCustomerScope;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class ServiceOrderServiceItem extends Model
{
    use HasUuids;
    use HasCustomerScope;

    public $incrementing = false;
    protected $keyType = 'string';
    protected $guarded = [];

    protected $table = 'service_order_service_items';

    protected $casts = [
        'quantity'   => 'decimal:2',
        'unit_price' => 'decimal:2',
        'total'      => 'decimal:2',
    ];

    public function serviceOrder()
    {
        return $this->belongsTo(ServiceOrder::class);
    }

    public function serviceItem()
    {
        return $this->belongsTo(ServiceItem::class);
    }

    public function serviceType()
    {
        return $this->belongsTo(ServiceType::class);
    }
}
