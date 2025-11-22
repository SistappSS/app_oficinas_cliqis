<?php

namespace App\Services\Modules;

use App\Models\Modules\UserModulePermission;

class EligibilityService
{
    public function isInRenewalWindow(?UserModulePermission $ump): bool
    {
        if (!$ump || !$ump->expires_at) return false;
        return now()->between($ump->expires_at->copy()->subDays(3), $ump->expires_at->copy()->addDays(3));
    }

    /**
     * Não pode “comprar o mesmo módulo” se já há permissão ativa.
     * Renovação deve ser tratada como “renewal” (não duplicar permissão).
     */
    public function canBuyModule(?UserModulePermission $ump): bool
    {
        if (!$ump) return true;
        return !$ump->expires_at || $ump->expires_at->isPast();
    }

    /**
     * Pode fazer upgrade para anual se ele já tem o módulo ativo em ciclo mensal.
     */
    public function canUpgradeToAnnual(?UserModulePermission $ump): bool
    {
        if (!$ump || !$ump->expires_at || $ump->expires_at->isPast()) return false;

        $cycle = optional($ump->latestControl)->cycle; // 'monthly' | 'annual'
        return $cycle === 'monthly';
    }

    /**
     * Não pode comprar apenas feature na janela de renovação (D-3 a D+3).
     */
    public function canBuyFeature(UserModulePermission $ump): bool
    {
        return !$this->isInRenewalWindow($ump);
    }
}
