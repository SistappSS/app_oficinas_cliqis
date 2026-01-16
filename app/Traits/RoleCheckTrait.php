<?php

namespace App\Traits;

use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

trait RoleCheckTrait
{
    private function userHasRole($role)
    {
        return Auth::check() && Auth::user()->hasRole($role);
    }

    /**
     * Retorna o customer_sistapp_id do usuário logado
     * seja ele cliente (customerLogin) ou funcionário (employeeCustomerLogin).
     */
    private function customerSistappID(): string
    {
        $user = Auth::user();

        $tenantId =
            optional($user->customerLogin)->customer_sistapp_id
            ?? optional($user->employeeCustomerLogin)->customer_sistapp_id;

        if (!$tenantId) {
            abort(403, 'Usuário sem vínculo de cliente/funcionário (customer_sistapp_id não encontrado).');
        }

        return $tenantId;
    }

    private function trialEnds()
    {
        // se funcionário, você provavelmente quer herdar do cliente (decide regra aqui)
        if ($this->userHasRole('admin')) {
            return Carbon::now()->addDays(14);
        }

        $user = Auth::user();

        // cliente direto
        if ($user->customerLogin) {
            return $user->customerLogin->trial_ends_at;
        }

        // funcionário -> pega do "tenant" (ajuste se tiver esse campo disponível em CustomerEmployeeUser)
        return optional($user->employeeCustomerLogin)->trial_ends_at;
    }

    private function subscription()
    {
        if ($this->userHasRole('admin')) {
            return true;
        }

        $user = Auth::user();

        if ($user->customerLogin) {
            return (bool) $user->customerLogin->subscription;
        }

        return (bool) optional($user->employeeCustomerLogin)->subscription;
    }

    private function userAuth()
    {
        return Auth::user()->id;
    }

    private function determineUserRole($request)
    {
        return $this->userHasRole('admin') ? $request->role : 'customer_customer_cliqis';
    }
}
