<?php

namespace App\Models;

use App\Models\Entities\Suppliers\Supplier;
use App\Models\PartOrderItem;
use App\Traits\HasCustomerScope;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class PartOrder extends Model
{
    use HasUuids, HasCustomerScope;

    public $incrementing = false;
    protected $keyType = 'string';
    protected $guarded = [];

    protected $casts = [
        'order_date'      => 'date:Y-m-d',
        'sent_at'         => 'datetime:Y-m-d H:i:s',
        'icms_rate'       => 'decimal:2',
        'subtotal'        => 'decimal:2',
        'ipi_total'       => 'decimal:2',
        'icms_total'      => 'decimal:2',
        'discount_total'  => 'decimal:2',
        'grand_total'     => 'decimal:2',
        'meta'            => 'array',
        'supplier_id'     => 'string',
        'signal_due_date' => 'date',
        'installments_first_due_date' => 'date',
    ];

    protected $appends = ['status_label'];

    public function items()
    {
        return $this->hasMany(PartOrderItem::class)->orderBy('position');
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class, 'supplier_id');
    }

    protected function statusLabel(): Attribute
    {
        return Attribute::get(fn () => match ($this->status) {
            'draft'     => 'Rascunho',
            'sent'      => 'Enviado',
            'open'      => 'Em aberto',
            'pending'   => 'Pendente',
            'late'      => 'Em atraso',
            'completed' => 'Concluído',
            'cancelled' => 'Cancelado',
            default     => ucfirst($this->status ?? 'Rascunho'),
        });
    }
}
