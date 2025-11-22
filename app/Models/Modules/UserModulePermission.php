<?php

namespace App\Models\Modules;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserModulePermission extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'expires_at' => 'datetime',
    ];

    public function module()
    {
        return $this->belongsTo(Module::class, 'module_id');
    }

    public function features()
    {
        return $this->hasMany(UserFeature::class, 'user_module_permission_id');
    }

    public function userFeatures()
    {
        return $this->hasMany(\App\Models\Modules\UserFeature::class, 'user_module_permission_id');
    }

    public function moduleControls()
    {
        return $this->hasMany(UserModuleControl::class, 'user_module_permission_id');
    }

    public function latestControl()
    {
        return $this->hasOne(UserModuleControl::class, 'user_module_permission_id')
            ->latestOfMany('contracted_date'); // ou 'id' se preferir
    }

    public function inRenewalWindow(): bool
    {
        if (!$this->expires_at) return false;
        return now()->between($this->expires_at->copy()->subDays(3), $this->expires_at->copy()->addDays(3));
    }

    public function nextExpiry(): ?\Carbon\Carbon
    {
        return $this->expires_at;
    }
}
