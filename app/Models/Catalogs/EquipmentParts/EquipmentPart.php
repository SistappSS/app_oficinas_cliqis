<?php

namespace App\Models\Catalogs\EquipmentParts;

use App\Models\Catalogs\Equipments\Equipment;
use App\Models\Catalogs\Parts\Part;
use App\Traits\HasCustomerScope;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\Pivot;

class EquipmentPart extends Pivot
{
    use HasUuids, HasCustomerScope;

    protected $table = 'equipment_parts';

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'customer_sistapp_id',
        'equipment_id',
        'part_id',
    ];

    public function equipment()
    {
        return $this->belongsTo(Equipment::class);
    }

    public function part()
    {
        return $this->belongsTo(Part::class);
    }
}
