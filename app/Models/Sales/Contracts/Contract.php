<?php

namespace App\Models\Sales\Contracts;

use App\Models\Entities\Customers\Customer;
use App\Traits\HasCustomerScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Contract extends Model
{
    use HasFactory;
    use HasCustomerScope;

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }
}
