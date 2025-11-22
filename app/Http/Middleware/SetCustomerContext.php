<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Support\CustomerContext;

class SetCustomerContext
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        $tenantId = null;
        if ($user) {
            // Pega customers.customer_sistapp_id via pivot customer_user_logins
            $tenantId = DB::table('customer_user_logins as cul')
                ->join('customers as c', 'c.id', '=', 'cul.customer_id')
                ->where('cul.user_id', $user->id)
                ->orderByDesc('cul.id') // ajuste sua regra de "login/cliente ativo"
                ->value('c.customer_sistapp_id');
        }

        // Sem tenant => fecha tudo pelos escopos (fail-safe)
        CustomerContext::set($tenantId);

        return $next($request);
    }
}
