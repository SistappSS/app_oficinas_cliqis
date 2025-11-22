<?php

namespace App\Models\Sales\Invoices;

use App\Traits\HasCustomerScope;
use Illuminate\Database\Eloquent\Model;

class InvoiceItem extends Model
{
    use HasCustomerScope;

}
