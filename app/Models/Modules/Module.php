<?php

namespace App\Models\Modules;

use App\Models\Authenticate\ModuleSegmentRequirement;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Module extends Model
{
    use HasUuids;

    public $incrementing = false;
    protected $keyType = 'string';

    protected $guarded = [];

    protected $casts = [
        'required_for_segments' => 'array',
    ];

    public function moduleTrasanction()
    {
        return $this->hasMany(ModuleTransaction::class);
    }

    public function features()
    {
        return $this->hasMany(Feature::class, 'module_id');
    }

    public function segmentRequirements()
    {
        return $this->hasMany(ModuleSegmentRequirement::class);
    }

    public function requiredSegments(): \Illuminate\Support\Collection
    {
        return $this->segmentRequirements
            ->where('is_required', true)
            ->pluck('segment')
            ->values();
    }
}
