<?php

namespace App\Models\Catalogs\Equipments\EquipmentExtraInfos;

use App\Models\Catalogs\Equipments\Equipment;
use App\Traits\HasCustomerScope;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class EquipmentExtraInfo extends Model
{
    use HasUuids;
    use HasCustomerScope;

    public $incrementing = false;
    protected $keyType = 'string';
    protected $guarded = [];

    protected $casts = [
        'image_path' => 'array', // JSON -> array (mime, data, name, size)
        'catalog_pdf'  => 'array',
    ];

    public function equipment()
    {
        return $this->belongsTo(Equipment::class);
    }
}
