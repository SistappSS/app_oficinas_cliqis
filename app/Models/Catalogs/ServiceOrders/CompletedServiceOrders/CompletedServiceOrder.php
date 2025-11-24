<?php

namespace App\Models\Catalogs\ServiceOrders\CompletedServiceOrders;

use App\Models\Catalogs\ServiceOrders\ServiceOrder;
use App\Models\HumanResources\Employees\Employee;
use App\Traits\HasCustomerScope;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class CompletedServiceOrder extends Model
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

    public function technician()
    {
        return $this->belongsTo(Employee::class, 'technician_id');
    }
}
