<?php

namespace App\Models\Authenticate;

use App\Models\Modules\Module;
use App\Traits\HasCustomerScope;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class ModuleSegmentRequirement extends Model
{
    use HasUuids;

    public $incrementing = false;
    protected $keyType = 'string';

    protected $guarded = [];

    public function module()
    {
        return $this->belongsTo(Module::class);
    }
}
