<?php

namespace App\Models\HumanResources\Departments;

use App\Models\HumanResources\Employees\Employee;
use App\Traits\HasCustomerScope;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Department extends Model
{
    use HasUuids;
    use HasCustomerScope;

    public $incrementing = false;
    protected $keyType = 'string';
    protected $guarded = [];

    public function employees()
    {
        return $this->hasMany(Employee::class);
    }
}
