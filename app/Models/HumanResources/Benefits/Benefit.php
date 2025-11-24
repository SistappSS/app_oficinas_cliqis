<?php

namespace App\Models\HumanResources\Benefits;

use App\Traits\HasCustomerScope;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Benefit extends Model
{
    use HasUuids;
    use HasCustomerScope;

    public $incrementing = false;
    protected $keyType = 'string';
    protected $guarded = [];

    public function employeeBenefits()
    {
        return $this->hasMany(EmployeeBenefit::class);
    }
}
