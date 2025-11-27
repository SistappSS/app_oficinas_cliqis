<?php

namespace App\Models\Catalogs\Services\ServiceTypes;

use App\Models\Catalogs\Services\ServiceItems\ServiceItem;
use App\Models\ServiceOrders\ServiceOrderServiceItems\ServiceOrderServiceItem;
use App\Traits\HasCustomerScope;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class ServiceType extends Model
{
    use HasUuids;
    use HasCustomerScope;

    public $incrementing = false;
    protected $keyType = 'string';
    protected $guarded = [];

    public function serviceItems()
    {
        return $this->hasMany(ServiceItem::class);
    }

    public function serviceOrderServiceItems()
    {
        return $this->hasMany(ServiceOrderServiceItem::class);
    }
}
