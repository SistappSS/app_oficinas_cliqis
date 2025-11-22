<?php

namespace App\Http\Controllers\APP\Authenticate;

use App\Http\Controllers\Controller;
use App\Models\Entities\Users\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

class GoogleAuthController extends Controller
{
    public function redirect()
    {
        return Socialite::driver('google')
            ->scopes(['openid','email','profile'])
            ->redirect();
    }

    public function callback()
    {
        $g = Socialite::driver('google')->stateless()->user();

        $email = $g->getEmail();
        if (!$email) return redirect()->route('login')
            ->withErrors(['email' => 'Não foi possível obter seu e-mail no Google.']);

        $user = User::where('google_id', $g->getId())->first()
            ?? User::where('email', $email)->first();

        if (!$user) {
            $user = User::create([
                'name'              => $g->getName() ?: explode('@', $email)[0],
                'email'             => $email,
                'password'          => Str::password(32), // senha aleatória
                'email_verified_at' => now(),
            ]);
            $user->google_id = $g->getId();
            $user->save();
        } else if (empty($user->google_id)) {
            $user->google_id = $g->getId();
            $user->save();
        }

        Auth::login($user, true);

        // respeita seu fluxo
        if (method_exists($user, 'nextOnboardingRoute')) {
            if ($route = $user->nextOnboardingRoute()) {
                return redirect()->route($route);
            }
        }
        return redirect()->route('dashboard');
    }
}
