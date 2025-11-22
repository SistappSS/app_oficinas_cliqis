<?php

namespace App\Http\Middleware;

use App\Models\Authenticate\AdditionalCustomerInfo;
use App\Models\Modules\UserModulePermission;
use Closure;
use Illuminate\Http\Request;

class EnsureOnboarding
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();
        if (!$user) return $next($request);

        // Rotas liberadas p/ onboarding (ajuste nomes/paths se precisar)
        $whitelist = [
            'additional-customer-info.index',
            'additional-customer-info.store',
            'company-segment.index',
            'company-segment.store',
            'addons.index',
            'addons.store',
            'profile.show',
            'google.redirect',
            'google.callback',
        ];
        if (in_array(optional($request->route())->getName(), $whitelist)) {
            return $next($request);
        }

        // 1) Cliente criado no Asaas? (via customerLogin com customer_sistapp_id)
        $customerLogin = $user->customerLogin; // ajuste se a relação for diferente
        if (!$customerLogin || !$customerLogin->customer_sistapp_id) {
            return redirect()->route('additional-customer-info.index');
        }

        // 2) Segmento preenchido?
        $aci = AdditionalCustomerInfo::where('user_id', $user->id)->first();
        if (!$aci || !$aci->segment) {
            return redirect()->route('company-segment.index');
        }

        // 3) Módulos escolhidos?
        $hasModules = UserModulePermission::where('user_id', $user->id)->exists();
        if (!$hasModules) {
            return redirect()->route('addons.index');
        }

        return $next($request);
    }
}
