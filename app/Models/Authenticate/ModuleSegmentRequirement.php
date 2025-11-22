<?php

namespace App\Models\Authenticate;

use App\Models\Modules\Module;
use Illuminate\Database\Eloquent\Model;

class ModuleSegmentRequirement extends Model
{
    protected $guarded = [];

    public function module()
    {
        return $this->belongsTo(Module::class);
    }
}
