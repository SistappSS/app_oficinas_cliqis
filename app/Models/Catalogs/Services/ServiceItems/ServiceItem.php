<?php

namespace App\Models\Catalogs\Services\ServiceItems;

use App\Models\Catalogs\Services\ServiceTypes\ServiceType;
use App\Models\ServiceOrders\ServiceOrderServiceItems\ServiceOrderServiceItem;
use App\Traits\HasCustomerScope;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class ServiceItem extends Model
{
    use HasUuids;
    use HasCustomerScope;

    public $incrementing = false;
    protected $keyType = 'string';
    protected $guarded = [];

    public function serviceType()
    {
        return $this->belongsTo(ServiceType::class);
    }

    public function serviceOrderServiceItems()
    {
        return $this->hasMany(ServiceOrderServiceItem::class);
    }
}
