<?php

namespace App\Models\HumanResources\Employees;

use App\Models\Catalogs\ServiceOrders\CompletedServiceOrders\CompletedServiceOrder;
use App\Models\Catalogs\ServiceOrders\ServiceOrder;
use App\Models\Catalogs\ServiceOrders\ServiceOrderLaborEntries\ServiceOrderLaborEntry;
use App\Models\Entities\Users\User;
use App\Models\HumanResources\Benefits\EmployeeBenefit;
use App\Models\HumanResources\Departments\Department;
use App\Traits\HasCustomerScope;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Employee extends Model
{
    use HasUuids;
    use HasCustomerScope;

    public $incrementing = false;
    protected $keyType = 'string';
    protected $guarded = [];

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function employeeBenefits()
    {
        return $this->hasMany(EmployeeBenefit::class);
    }

    public function serviceOrdersAsTechnician()
    {
        return $this->hasMany(ServiceOrder::class, 'technician_id');
    }

    public function serviceOrdersOpened()
    {
        return $this->hasMany(ServiceOrder::class, 'opened_by_employee_id');
    }

    public function laborEntries()
    {
        return $this->hasMany(ServiceOrderLaborEntry::class);
    }

    public function completedServiceOrders()
    {
        return $this->hasMany(CompletedServiceOrder::class, 'technician_id');
    }
}
