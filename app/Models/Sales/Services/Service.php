<?php

namespace App\Models\Sales\Services;

use App\Models\Sales\Budgets\BudgetItem;
use App\Models\Sales\Budgets\BudgetMonthlyItem;
use App\Models\Sales\Budgets\BudgetYearlyItem;
use App\Models\Sales\Budgets\MonthlyTransaction;
use App\Models\Sales\Budgets\YearlyTransaction;
use App\Traits\HasCustomerScope;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Service extends Model
{
    use HasFactory;
    use HasCustomerScope;

    protected $casts = ['price' => 'decimal:2'];

    protected function price(): Attribute
    {
        return Attribute::make(
            set: function ($value) {
                if (is_null($value) || $value === '') return null;
                if (is_numeric($value)) {
                    return number_format((float)$value, 2, '.', '');
                }
                $value = str_replace('.', '', $value);
                $value = str_replace(',', '.', $value);
                return number_format((float)$value, 2, '.', '');
            }
        );
    }

    public function budgetItems(): HasMany
    {
        return $this->hasMany(BudgetItem::class);
    }

    public function monthlyItems(): HasMany
    {
        return $this->hasMany(BudgetMonthlyItem::class);
    }

    public function monthlyTransactions(): HasMany
    {
        return $this->hasMany(MonthlyTransaction::class);
    }

    public function yearlyItems(): HasMany
    {
        return $this->hasMany(BudgetYearlyItem::class);
    }

    public function yearlyTransactions(): HasMany
    {
        return $this->hasMany(YearlyTransaction::class);
    }
}
