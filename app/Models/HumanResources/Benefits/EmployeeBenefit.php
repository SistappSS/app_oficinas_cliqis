<?php

namespace App\Models\HumanResources\Benefits;

use App\Models\HumanResources\Employees\Employee;
use App\Traits\HasCustomerScope;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class EmployeeBenefit extends Model
{
    use HasUuids;
    use HasCustomerScope;

    public $incrementing = false;
    protected $keyType = 'string';
    protected $guarded = [];

    // se a tabela for employee_benefits ou employee_benefit, ajuste aqui
    // protected $table = 'employee_benefit';

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function benefit()
    {
        return $this->belongsTo(Benefit::class);
    }
}
