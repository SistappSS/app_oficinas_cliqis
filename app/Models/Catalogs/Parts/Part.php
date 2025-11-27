<?php

namespace App\Models\Catalogs\Parts;

use App\Models\Catalogs\EquipmentParts\EquipmentPart;
use App\Models\Catalogs\Equipments\Equipment;
use App\Models\Entities\Suppliers\Supplier;
use App\Models\ServiceOrders\ServiceOrderPartItems\ServiceOrderPartItem;
use App\Traits\HasCustomerScope;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Part extends Model
{
    use HasUuids;
    use HasCustomerScope;

    public $incrementing = false;
    protected $keyType = 'string';
    protected $guarded = [];

    public function serviceOrderPartItems()
    {
        return $this->hasMany(ServiceOrderPartItem::class);
    }

    public function EquipmentParts()
    {
        return $this->hasMany(EquipmentPart::class);
    }

    public function equipments()
    {
        return $this->belongsToMany(
            Equipment::class,
            'equipment_parts',
            'part_id',
            'equipment_id'
        )
            ->using(EquipmentPart::class)   // <- usa o pivot customizado
            ->withTimestamps();
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class, 'supplier_id');
    }
}
