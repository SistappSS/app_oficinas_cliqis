<?php

namespace App\Http\Middleware;

use App\Models\Entities\Customers\CustomerEmployeeUser;
use App\Support\CustomerContext;
use App\Traits\RoleCheckTrait;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CheckSubscription
{
    use RoleCheckTrait;

    public function handle(Request $request, Closure $next)
    {
        $user = Auth::user();

        if (! $user) {
            return redirect()->route('login');
        }

        if ($this->userHasRole('admin')) {
            return $next($request);
        }

        $routeName = optional($request->route())->getName();

        logger()->info('Rota atual', ['route' => $routeName]);
        logger()->info('SUB CHECK', [
            'user_id'       => $user->id,
            'subscription'  => optional($user->customerLogin)->subscription,
            'trial_ends_at' => optional($user->customerLogin)->trial_ends_at,
            'now'           => now()->toDateTimeString(),
        ]);

        $isEmployeeCustomer = CustomerEmployeeUser::withoutGlobalScope('customer')
            ->where('user_id', $user->id)
            ->exists();

        $onboardingWhitelist = [
            'additional-customer-info.index', 'additional-customer-info.store',
            'company-segment.index',          'company-segment.store',
            'addons.index',                   'addons.store',
            'verification.notice', 'verification.verify',
            'verification.send',   'two-factor.login',
            'io.options',
            'service-orders.signature.public.show', 'service-orders.signature.public.store'
        ];

        if (!$isEmployeeCustomer) {
            if ($nextStep = $user->nextOnboardingRoute()) {
                if (! in_array($routeName, $onboardingWhitelist, true)) {
                    return redirect()->route($nextStep);
                }

                return $next($request);
            }
        }

        $login = $user->customerLogin;
        $trialExpirado = $login && $login->trial_ends_at && now()->gte($login->trial_ends_at);
        $naoAssinante  = $login && ! $login->subscription;

        if ($trialExpirado && $naoAssinante) {
            $billingWhite = [
                'my-account.index',
                'profile.show',
                'logout',
                'billing.index',
                'buy-module.index',
                'module.checkout',
                'verificar-pix-pendente',
                'gerar-qrcode.module',
                'checar-pagamento.module',
                'io.options',
                'service-orders.signature.public.show',
                'service-orders.signature.public.store'
            ];

            if (! in_array($routeName, $billingWhite, true)) {
                return redirect()->route('billing.index', $user->id)
                    ->with('error', 'Seu período de teste expirou. Realize o pagamento para continuar.');
            }
        }

        return $next($request);
    }
}
