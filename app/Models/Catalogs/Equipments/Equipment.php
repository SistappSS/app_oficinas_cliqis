<?php

namespace App\Models\Catalogs\Equipments;

use App\Models\Catalogs\EquipmentParts\EquipmentPart;
use App\Models\Catalogs\Equipments\EquipmentExtraInfos\EquipmentExtraInfo;
use App\Models\Catalogs\Parts\Part;
use App\Models\Catalogs\ServiceOrders\ServiceOrderEquipments\ServiceOrderEquipment;
use App\Traits\HasCustomerScope;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Equipment extends Model
{
    use HasUuids;
    use HasCustomerScope;

    public $incrementing = false;
    protected $keyType = 'string';
    protected $guarded = [];
    protected $table = 'equipments';

    public function extraInfo()
    {
        return $this->hasOne(EquipmentExtraInfo::class);
    }

    public function equipmentParts()
    {
        return $this->hasMany(EquipmentPart::class);
    }

    public function parts()
    {
        return $this->belongsToMany(
            Part::class,
            'equipment_parts',
            'equipment_id',
            'part_id'
        )
            ->using(EquipmentPart::class)   // <- usa o pivot customizado
            ->withTimestamps();
    }

    public function serviceOrderEquipments()
    {
        return $this->hasMany(ServiceOrderEquipment::class);
    }
}
