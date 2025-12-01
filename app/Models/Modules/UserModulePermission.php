<?php

namespace App\Models\Modules;

use App\Traits\HasCustomerScope;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class UserModulePermission extends Model
{
    use HasUuids;
   // use HasCustomerScope;

    public $incrementing = false;
    protected $keyType = 'string';

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
