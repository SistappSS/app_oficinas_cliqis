<?php

namespace App\Models\Catalogs\ServiceOrders\ServiceOrderServiceItems;

use App\Models\Catalogs\ServiceOrders\ServiceOrder;
use App\Models\Catalogs\Services\ServiceItems\ServiceItem;
use App\Models\Catalogs\Services\ServiceTypes\ServiceType;
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
