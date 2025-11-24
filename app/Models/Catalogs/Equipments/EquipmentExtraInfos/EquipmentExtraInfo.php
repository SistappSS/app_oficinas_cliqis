<?php

namespace App\Models\Catalogs\Equipments\EquipmentExtralInfos;

use App\Models\Catalogs\Equipments\Equipment;
use App\Traits\HasCustomerScope;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class EquipmentExtralInfo extends Model
{
    use HasUuids;
    use HasCustomerScope;

    public $incrementing = false;
    protected $keyType = 'string';
    protected $guarded = [];

    public function equipment()
    {
        return $this->belongsTo(Equipment::class);
    }
}
