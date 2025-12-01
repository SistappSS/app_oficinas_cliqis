<?php

namespace App\Models\Modules;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Feature extends Model
{
    use HasUuids;

    public $incrementing = false;
    protected $keyType = 'string';

    protected $guarded = [];

    protected $casts = [
        'roles'       => 'array',
        'is_required' => 'boolean',
        'is_active'   => 'boolean',
        'price'       => 'decimal:2',
    ];

    public function module()
    {
        return $this->belongsTo(Module::class, 'module_id');
    }

    public function userFeatures()
    {
        return $this->hasMany(UserFeature::class, 'feature_id');
    }
}
