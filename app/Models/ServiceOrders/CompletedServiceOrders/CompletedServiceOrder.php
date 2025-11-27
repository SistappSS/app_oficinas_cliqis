<?php

namespace App\Models\ServiceOrders\CompletedServiceOrders;

use App\Models\HumanResources\Employees\Employee;
use App\Models\ServiceOrders\ServiceOrder;
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

    protected $table = 'completed_service_orders';

    protected $casts = [
        'client_signed_at'     => 'datetime',
        'technician_signed_at' => 'datetime',
        'completed_at'         => 'datetime',
    ];

    public function serviceOrder()
    {
        return $this->belongsTo(ServiceOrder::class);
    }

    public function technician()
    {
        return $this->belongsTo(Employee::class, 'technician_id');
    }
}
