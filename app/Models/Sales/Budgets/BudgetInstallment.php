<?php

namespace App\Models\Sales\Budgets;

use App\Traits\HasCustomerScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BudgetInstallment extends Model
{
    use HasFactory;
    use HasCustomerScope;

    public function budgetItem(): BelongsTo
    {
        return $this->belongsTo(BudgetItem::class);
    }

    // Relacionamento: A parcela pertence indiretamente a um Budget (via BudgetItem)
    public function budget(): BelongsTo
    {
        return $this->belongsTo(Budget::class, 'budget_id', 'id')
            ->join('budget_items', 'budget_items.id', '=', 'budget_installments.budget_item_id'); // Certifique-se de usar o nome correto da tabela e chaves.
    }
}
