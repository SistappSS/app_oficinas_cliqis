<?php

namespace App\Models\ServiceOrders\ServiceOrderPartItems;

use App\Models\Catalogs\Parts\Part;
use App\Models\ServiceOrders\ServiceOrder;
use App\Traits\HasCustomerScope;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class ServiceOrderPartItem extends Model
{
    use HasUuids;
    use HasCustomerScope;

    public $incrementing = false;
    protected $keyType = 'string';
    protected $guarded = [];

    protected $table = 'service_order_part_items';

    protected $casts = [
        'quantity'   => 'decimal:2',
        'unit_price' => 'decimal:2',
        'total'      => 'decimal:2',
    ];

    public function serviceOrder()
    {
        return $this->belongsTo(ServiceOrder::class);
    }

    public function part()
    {
        return $this->belongsTo(Part::class);
    }
}
