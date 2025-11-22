<?php

namespace App\Models\Sales\Budgets;

use App\Models\Sales\Services\Service;
use App\Traits\HasCustomerScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BudgetYearlyItem extends Model
{
    use HasFactory;
    use HasCustomerScope;
    public function budget(): BelongsTo
    {
        return $this->belongsTo(Budget::class);
    }

    public function yearlyTransactions(): HasMany
    {
        return $this->hasMany(YearlyTransaction::class);
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class, 'service_id');
    }
}
