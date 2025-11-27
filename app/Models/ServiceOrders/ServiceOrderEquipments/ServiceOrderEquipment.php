<?php

namespace App\Models\ServiceOrders\ServiceOrderEquipments;

use App\Models\Catalogs\Equipments\Equipment;
use App\Models\ServiceOrders\ServiceOrder;
use App\Traits\HasCustomerScope;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class ServiceOrderEquipment extends Model
{
    use HasUuids;
    use HasCustomerScope;

    public $incrementing = false;
    protected $keyType = 'string';
    protected $guarded = [];

    protected $table = 'service_order_equipments';

    public function serviceOrder()
    {
        return $this->belongsTo(ServiceOrder::class);
    }

    public function equipment()
    {
        return $this->belongsTo(Equipment::class);
    }
}
