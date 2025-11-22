<?php

namespace App\Models\Sales\Invoices;

use App\Traits\HasCustomerScope;
use Illuminate\Database\Eloquent\Model;

class ReminderInvoiceConfig extends Model
{
    use HasCustomerScope;

    protected $table = 'reminder_invoice_configs';

    protected $guarded = [];

    protected $casts = [
        'is_active' => 'bool',
    ];
}
