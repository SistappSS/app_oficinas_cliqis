<?php

namespace App\Models\Sales\Budgets;

use App\Models\Sales\Services\Service;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BudgetItem extends Model
{
    use HasFactory;
    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class, 'service_id');
    }

    public function budget(): BelongsTo
    {
        return $this->belongsTo(Budget::class);
    }

    // Relacionamento: Um BudgetItem tem várias Parcelas
    public function installments(): HasMany
    {
        return $this->hasMany(BudgetInstallment::class);
    }
}
