<?php

namespace App\Http\Controllers\Application\Auth\Authenticate;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginUserRequest;
use App\Http\Requests\Auth\RegisterUserRequest;
use App\Models\Entities\Customers\Customer;
use App\Models\Entities\Users\CustomerUserLogin;
use App\Models\Entities\Users\User;
use App\Traits\CreateCustomerAsaas;
use App\Traits\RoleCheckTrait;
use Illuminate\Contracts\Auth\StatefulGuard;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Laravel\Fortify\Contracts\LogoutResponse;

class AuthController extends Controller
{
    use CreateCustomerAsaas, RoleCheckTrait;

    protected $guard;
    protected $user;
    protected $customerUserLogin;
    protected $customer;

    public function __construct(StatefulGuard $guard, User $user, CustomerUserLogin $customerUserLogin, Customer $customer)
    {
        $this->guard = $guard;
        $this->user = $user;
        $this->customerUserLogin = $customerUserLogin;
        $this->customer = $customer;
    }

    public function login(LoginUserRequest $request)
    {
        $request->validated();

        $credentials = $request->only('email', 'password');

        if (Auth::attempt($credentials)) {
            $user = Auth::user();

            if ($request->hasSession()) {
                $request->session()->regenerate();
            }

            // Checa assinatura
            if ($user->hasActiveSubscription()) {
                return redirect()->intended('/dashboard');
            }

            // Sem assinatura, prepara mensagem
            $message = $user->hasRole('customer_customer_cliqis')
                ? 'Seu acesso está desabilitado. Entre em contato com o vendedor responsável.'
                : 'Sua assinatura está vencida. Renove para continuar usando o sistema.';

            // Redireciona para módulos para renovar
            return redirect()->route('module.index')->with('error', $message);
        }

        return back()->withErrors([
            'email' => 'As credenciais fornecidas estão incorretas.',
        ]);
    }

    public function register(RegisterUserRequest $request)
    {
        $request->validated();

        $user = $this->user->create([
            'name' => ucwords($request->name),
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'is_active' => 1,
        ]);

        Auth::guard('web')->login($user);
        $request->session()->regenerate();

        $next = $user->nextOnboardingRoute() ?? 'dashboard';

        return redirect()->route($next);
    }

    public function loginView()
    {
        return view('auth.login');
    }

    public function registerView()
    {
        return view('auth.register');
    }

    public function forgotPassword()
    {
        return view('auth.forgot_password');
    }

    public function destroy(Request $request): LogoutResponse
    {
        $this->guard->logout();

        if ($request->hasSession()) {
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        return app(LogoutResponse::class);
    }
}
