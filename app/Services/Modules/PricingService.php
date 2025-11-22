<?php

namespace App\Services\Modules;

use App\Models\Modules\Module;
use Carbon\Carbon;

class PricingService
{
    /**
     * Retorna o breakdown mensal do módulo:
     *  - se tiver features: obrigatórias + selecionadas do carrinho
     *  - se NÃO tiver features: preço do módulo
     */
    public function breakdownForModule(Module $module, array $selectedFeatureIds = []): array
    {
        $hasFeatures = $module->relationLoaded('features')
            ? $module->features->count() > 0
            : $module->features()->exists();

        if (!$hasFeatures) {
            return [[
                'module_id'   => (int) $module->id,
                'type'        => 'module',
                'name'        => $module->name,
                'price'       => (float) $module->price,
            ]];
        }

        $ids = collect($selectedFeatureIds)->map(fn($v)=>(int)$v)->unique()->values();
        $features = $module->relationLoaded('features') ? $module->features : $module->features()->get();

        $rows = [];

        foreach ($features as $f) {
            $isRequired = (bool) ($f->is_required ?? false);
            $isSelected = $ids->contains((int)$f->id);
            if ($isRequired || $isSelected) {
                $rows[] = [
                    'module_id'   => (int) $module->id,
                    'feature_id'  => (int) $f->id,
                    'type'        => 'feature',
                    'name'        => $f->name,
                    'price'       => (float) $f->price,
                ];
            }
        }

        return $rows;
    }

    public function monthlyTotalForModule(Module $module, array $selectedFeatureIds = []): float
    {
        return array_reduce(
            $this->breakdownForModule($module, $selectedFeatureIds),
            fn($sum, $row) => $sum + (float)$row['price'],
            0.0
        );
    }

    /**
     * Soma do carrinho para ciclo (monthly | annual).
     */
    public function cartTotal(iterable $modules, array $selectedFeaturesByModule, string $cycle): float
    {
        $sum = 0.0;
        foreach ($modules as $module) {
            $ids = $selectedFeaturesByModule[$module->id] ?? [];
            $sum += $this->monthlyTotalForModule($module, $ids);
        }
        if ($this->isAnnual($cycle)) {
            $sum = round($sum * 12 * 0.85, 2);
        }
        return $sum;
    }

    /**
     * Pró-rata de feature até o vencimento do módulo (base 30 dias).
     */
    public function featureProration(float $featureMonthlyPrice, Carbon $moduleExpiry): float
    {
        $now = now();
        if (!$moduleExpiry || $moduleExpiry->lessThanOrEqualTo($now)) {
            return round($featureMonthlyPrice, 2);
        }
        $daysLeft    = max(1, $moduleExpiry->diffInDays($now)); // evita 0
        $daysInCycle = 30;
        return round(($featureMonthlyPrice / $daysInCycle) * $daysLeft, 2);
    }

    public function annualUpgradeAmount(float $monthlySum): float
    {
        // 11 meses com -15%
        return round($monthlySum * 11 * 0.85, 2);
    }

    public function isAnnual(string $cycle): bool
    {
        return $cycle === 'annual' || $cycle === 'yearly'; // tolera legado
    }
}
