<?php

namespace App\Models\Finances\Payables;

use App\Traits\HasCustomerScope;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class PayableCustomField extends Model
{
    use HasUuids, HasCustomerScope;

    protected $table = 'payable_custom_fields';

    protected $casts = [
        'active' => 'boolean',
    ];
}
