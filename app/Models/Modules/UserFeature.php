<?php

namespace App\Models\Modules;

use App\Traits\HasCustomerScope;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class UserFeature extends Model
{
    use HasUuids;
    //use HasCustomerScope;

    public $incrementing = false;
    protected $keyType = 'string';

    protected $guarded = [];

    protected $casts = [
        'price'           => 'decimal:2',
        'selected'        => 'boolean',
        'activated_at'    => 'datetime',
        'prorated_amount' => 'decimal:2',
    ];

    public function userModulePermission()
    {
        return $this->belongsTo(UserModulePermission::class, 'user_module_permission_id');
    }

    public function feature()
    {
        return $this->belongsTo(Feature::class, 'feature_id');
    }
}
