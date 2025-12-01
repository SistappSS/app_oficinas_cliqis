<?php

namespace App\Models\Modules;

use App\Traits\HasCustomerScope;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class ModulePermission extends Model
{
    use HasUuids;
    //use HasCustomerScope;

    public $incrementing = false;
    protected $keyType = 'string';

    protected $guarded = [];
}
