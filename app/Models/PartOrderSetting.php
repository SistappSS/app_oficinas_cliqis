<?php

namespace App\Models;

use App\Models\Entities\Suppliers\Supplier;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class PartOrderSetting extends Model
{
    use HasUuids;

    protected $table = 'part_order_settings';

    protected $fillable = [
        'customer_sistapp_id',
        'default_supplier_id',
        'billing_cnpj',
        'billing_uf',
        'email_subject_tpl',
        'email_body_tpl',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function supplier()
    {
        return $this->belongsTo(Supplier::class, 'default_supplier_id');
    }
}
