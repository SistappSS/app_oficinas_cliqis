<?php

namespace App\Models\Sales\Budgets\Subscriptions;

use App\Models\Entities\Customers\Customer;
use App\Models\Sales\Budgets\Budget;
use App\Models\Sales\Services\Service;
use App\Traits\HasCustomerScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Subscription extends Model
{
    use HasCustomerScope;

    protected $casts = [
        'next_due_date' => 'date',
        'auto_reminder' => 'boolean',
        'active' => 'boolean',
    ];

    public function budget(): BelongsTo {
        return $this->belongsTo(Budget::class);
    }

    public function customer(): BelongsTo {
        return $this->belongsTo(Customer::class);
    }

    public function service(): BelongsTo {
        return $this->belongsTo(Service::class, 'service_id');
    }
}
