<?php

namespace App\Models\Catalogs\Equipments;

use App\Models\Catalogs\EquipmentParts\EquipmentPart;
use App\Models\Catalogs\Equipments\EquipmentExtralInfos\EquipmentExtralInfo;
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

    public function extraInfo()
    {
        return $this->hasOne(EquipmentExtralInfo::class);
    }

    public function equipmentParts()
    {
        return $this->hasMany(EquipmentPart::class);
    }

    public function parts()
    {
        // ajuste o nome da tabela pivot se for diferente
        return $this->belongsToMany(
            Part::class,
            'equipment_part',
            'equipment_id',
            'part_id'
        );
    }

    public function serviceOrderEquipments()
    {
        return $this->hasMany(ServiceOrderEquipment::class);
    }
}
