<?php

namespace App\Models\ServiceOrders;

use App\Models\Entities\Customers\SecondaryCustomer;
use App\Models\HumanResources\Employees\Employee;
use App\Models\ServiceOrders\CompletedServiceOrders\CompletedServiceOrder;
use App\Models\ServiceOrders\ServiceOrderEquipments\ServiceOrderEquipment;
use App\Models\ServiceOrders\ServiceOrderLaborEntries\ServiceOrderLaborEntry;
use App\Models\ServiceOrders\ServiceOrderPartItems\ServiceOrderPartItem;
use App\Models\ServiceOrders\ServiceOrderServiceItems\ServiceOrderServiceItem;
use App\Traits\HasCustomerScope;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class ServiceOrder extends Model
{
    use HasUuids;
    use HasCustomerScope;

    public $incrementing = false;
    protected $keyType = 'string';
    protected $guarded = [];

    protected $casts = [
        'order_date'          => 'date',
        'labor_hour_value'    => 'decimal:2',
        'labor_total_hours'   => 'decimal:2',
        'labor_total_amount'  => 'decimal:2',
        'services_subtotal'   => 'decimal:2',
        'parts_subtotal'      => 'decimal:2',
        'discount_amount'     => 'decimal:2',
        'addition_amount'     => 'decimal:2',
        'grand_total'         => 'decimal:2',
    ];

    // ---- RELACIONAMENTOS ----

    public function secondaryCustomer()
    {
        return $this->belongsTo(SecondaryCustomer::class);
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
        return $this->hasMany(ServiceOrderEquipment::class);
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

    public function completion()
    {
        return $this->hasOne(CompletedServiceOrder::class);
    }

    public function invoice()
    {
        return $this->hasOne(ServiceOrderInvoice::class);
    }

    // ---- HELPERS ----

    protected function statusLabel(): Attribute
    {
        return Attribute::get(function () {
            return match ($this->status) {
                'draft'     => 'Rascunho',
                'pending'   => 'Pendente',
                'approved'  => 'Aprovada',
                'rejected'  => 'Reprovada',
                'completed' => 'Concluída',
                default     => ucfirst($this->status ?? 'Rascunho'),
            };
        });
    }
}
