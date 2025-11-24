<?php

namespace App\Http\Middleware;

use App\Support\CustomerContext;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SetCustomerContext
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();
        $tenantId = null;

        if ($user) {
            $tenantId = DB::table('customer_user_logins')
                ->where('user_id', $user->id)
                ->orderByDesc('id')          // último login / vínculo
                ->value('customer_sistapp_id');
        }

        // seta no contexto (pode ser null, aí o HasCustomerScope bloqueia)
        CustomerContext::set($tenantId);

        return $next($request);
    }
}
