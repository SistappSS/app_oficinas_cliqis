<?php

namespace App\Models\Catalogs\EquipmentParts;

use App\Models\Catalogs\Equipments\Equipment;
use App\Models\Catalogs\Parts\Part;
use App\Traits\HasCustomerScope;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class EquipmentPart extends Model
{
    use HasUuids;
    use HasCustomerScope;

    public $incrementing = false;
    protected $keyType = 'string';
    protected $guarded = [];

    // ajuste se o nome da tabela for algo como 'equipment_part'
    // protected $table = 'equipment_part';

    public function equipment()
    {
        return $this->belongsTo(Equipment::class);
    }

    public function part()
    {
        return $this->belongsTo(Part::class);
    }
}
