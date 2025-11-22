<?php

namespace App\Models\Sales\Budgets;

use App\Models\Sales\Services\Service;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MonthlyTransaction extends Model
{
    use HasFactory;

    public function budgetMonthlyItem(): BelongsTo
    {
        return $this->belongsTo(BudgetMonthlyItem::class);
    }

    public function service()
    {
        return $this->hasOneThrough(Service::class, BudgetMonthlyItem::class, 'id', 'id', 'budget_monthly_item_id', 'service_id');
    }
}
