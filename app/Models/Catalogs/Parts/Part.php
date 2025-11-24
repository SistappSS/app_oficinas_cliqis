<?php

namespace App\Models\Catalogs\Parts;

use App\Models\Catalogs\EquipmentParts\EquipmentPart;
use App\Models\Catalogs\Equipments\Equipment;
use App\Models\Catalogs\ServiceOrders\ServiceOrderPartItems\ServiceOrderPartItem;
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

    public function Equipments()
    {
        // ajuste o nome da tabela pivot se for diferente
        return $this->belongsToMany(
            Equipment::class,
            'equipment_part',
            'part_id',
            'equipment_id'
        );
    }
}
