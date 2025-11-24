<?php

namespace App\Models\Catalogs\ServiceOrders\ServiceOrderLaborEntries;

use App\Models\Catalogs\ServiceOrders\ServiceOrder;
use App\Models\HumanResources\Employees\Employee;
use App\Traits\HasCustomerScope;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class ServiceOrderLaborEntry extends Model
{
    use HasUuids;
    use HasCustomerScope;

    public $incrementing = false;
    protected $keyType = 'string';
    protected $guarded = [];

    public function serviceOrder()
    {
        return $this->belongsTo(ServiceOrder::class);
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }
}
