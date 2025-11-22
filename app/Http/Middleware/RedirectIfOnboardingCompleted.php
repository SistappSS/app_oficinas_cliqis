<?php

namespace App\Http\Middleware;

use App\Models\Authenticate\AdditionalCustomerInfo;
use App\Models\Modules\UserModulePermission;
use Closure;
use Illuminate\Http\Request;

class RedirectIfOnboardingCompleted
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        if (!$user) return $next($request);

        if ($user->hasRole('admin')) {
            return $next($request);
        }

        $okLogin  = $user->customerLogin && $user->customerLogin->customer_sistapp_id;
        $okSeg    = AdditionalCustomerInfo::where('user_id', $user->id)->whereNotNull('segment')->exists();
        $okAddons = UserModulePermission::where('user_id', $user->id)->exists();

        if ($okLogin && $okSeg && $okAddons) {
            return redirect()->route('dashboard');
        }

        return $next($request);
    }
}
