<?php

namespace App\Models\Sales\Budgets;

use App\Models\Sales\Services\Service;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class YearlyTransaction extends Model
{
    use HasFactory;

    public function budgetYearlyItem(): BelongsTo
    {
        return $this->belongsTo(BudgetYearlyItem::class);
    }

    public function service()
    {
        return $this->hasOneThrough(Service::class, BudgetYearlyItem::class, 'id', 'id', 'budget_yearly_item_id', 'service_id');
    }
}
