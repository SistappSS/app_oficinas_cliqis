<?php

namespace App\Models\Sales\Budgets;

use App\Models\Entities\Customers\Customer;
use App\Models\Entities\Users\User;
use App\Models\Sales\Services\Service;
use App\Traits\HasCustomerScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Budget extends Model
{
    use HasFactory;
    use HasCustomerScope;

    public const STATUS_PENDING  = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';

    protected $casts = [
        'approved_at' => 'datetime',
        'budget_code' => 'int',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function installments()
    {
        return $this->hasManyThrough(
            BudgetInstallment::class,
            BudgetItem::class,
            'budget_id',
            'budget_item_id',
            'id',
            'id'
        );
    }

    public function items()
    {
        return $this->hasMany(BudgetItem::class);
    }

    public function monthlyItems()
    {
        return $this->hasMany(BudgetMonthlyItem::class, 'budget_id', 'id');
    }

    public function yearlyItems()
    {
        return $this->hasMany(BudgetYearlyItem::class, 'budget_id', 'id');
    }
}
