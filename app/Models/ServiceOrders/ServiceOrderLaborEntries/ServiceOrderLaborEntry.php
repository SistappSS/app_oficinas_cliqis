<?php

namespace App\Models\ServiceOrders\ServiceOrderLaborEntries;

use App\Models\HumanResources\Employees\Employee;
use App\Models\ServiceOrders\ServiceOrder;
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

    protected $table = 'service_order_labor_entries';

    protected $casts = [
        'started_at' => 'datetime',
        'ended_at'   => 'datetime',
        'hours'      => 'decimal:2',
        'rate'       => 'decimal:2',
        'total'      => 'decimal:2',
    ];

    public function serviceOrder()
    {
        return $this->belongsTo(ServiceOrder::class);
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }
}
