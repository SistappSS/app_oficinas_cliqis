<?php

namespace App\Models\Catalogs\ServiceOrders;

use App\Models\Catalogs\ServiceOrders\CompletedServiceOrders\CompletedServiceOrder;
use App\Models\Catalogs\ServiceOrders\ServiceOrderEquipaments\ServiceOrderEquipament;
use App\Models\Catalogs\ServiceOrders\ServiceOrderLaborEntries\ServiceOrderLaborEntry;
use App\Models\Catalogs\ServiceOrders\ServiceOrderPartItems\ServiceOrderPartItem;
use App\Models\Catalogs\ServiceOrders\ServiceOrderServiceItems\ServiceOrderServiceItem;
use App\Models\HumanResources\Employees\Employee;
use App\Models\SecondaryCustomer;
use App\Traits\HasCustomerScope;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class ServiceOrder extends Model
{
    use HasUuids;
    use HasCustomerScope;

    public $incrementing = false;
    protected $keyType = 'string';
    protected $guarded = [];

    public function secondaryCustomer()
    {
        return $this->belongsTo(SecondaryCustomer::class, 'secondary_customer_id');
    }

    public function technician()
    {
        return $this->belongsTo(Employee::class, 'technician_id');
    }

    public function openedBy()
    {
        return $this->belongsTo(Employee::class, 'opened_by_employee_id');
    }

    public function equipments()
    {
        return $this->hasMany(ServiceOrderEquipament::class);
    }

    public function serviceItems()
    {
        return $this->hasMany(ServiceOrderServiceItem::class);
    }

    public function partItems()
    {
        return $this->hasMany(ServiceOrderPartItem::class);
    }

    public function laborEntries()
    {
        return $this->hasMany(ServiceOrderLaborEntry::class);
    }

    public function completed()
    {
        return $this->hasOne(CompletedServiceOrder::class);
    }
}
