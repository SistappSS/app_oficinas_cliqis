<?php

namespace App\Models;

use App\Models\Catalogs\Parts\Part;
use App\Models\PartsOrders\PartOrder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class PartOrderItem extends Model
{
    use HasUuids;

    public $incrementing = false;
    protected $keyType = 'string';
    protected $guarded = [];

    protected $casts = [
        'unit_price'           => 'decimal:2',
        'ipi_rate'             => 'decimal:2',
        'quantity'             => 'decimal:2',
        'discount_rate'        => 'decimal:2',
        'line_subtotal'        => 'decimal:2',
        'line_ipi_amount'      => 'decimal:2',
        'line_discount_amount' => 'decimal:2',
        'line_total'           => 'decimal:2',
    ];

    public function order()
    {
        return $this->belongsTo(PartOrder::class, 'part_order_id');
    }

    public function part()
    {
        return $this->belongsTo(Part::class);
    }
}
