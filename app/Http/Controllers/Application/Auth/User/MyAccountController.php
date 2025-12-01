<?php

namespace App\Http\Controllers\Application\Auth\User;

use App\Http\Controllers\Controller;
use App\Models\Entities\Users\User;
use App\Models\Modules\ModuleTransactionPayment;
use App\Models\Modules\UserModulePermission;
use App\Traits\RoleCheckTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class MyAccountController extends Controller
{
    use RoleCheckTrait;

    protected $user;

    public function __construct(User $user) {
        $this->user = $user;
    }

    public function myAccount()
    {
        $userId = auth()->id();

        $isAdmin = $this->userHasRole('admin');

        $user = $this->user->with('customerLogin.customer', 'additionalInfo')->find($userId);

        //dd($user);

        if ($isAdmin) {
            $statusUser = 'Assinatura vitalícia';
            $status     = 'Administrador';
        } else {
            if ($user->hasPaidSubscription()) {
                $statusUser = 'Possui módulo contratado';
                $status     = 'Em dia';
            } elseif ($user->isOnTrial()) {
                $ends = $user->trialEndsAt();
                $diffDays  = floor(now()->floatDiffInDays($ends, false));
                $diffHours = max(0, round((now()->floatDiffInDays($ends, false) - $diffDays) * 24));
                $statusUser = "Teste gratuito — termina em {$diffDays} dias e {$diffHours} horas";
                $status     = 'Em teste';
            } else {
                $statusUser = 'Seu período de teste acabou.';
                $status     = 'Pausado';
            }
        }

        $modules = UserModulePermission::with([
            'module',              // nome do módulo
            'latestControl',       // seu controle atual
            'userFeatures.feature' // << precisa disso para listar features + nomes
        ])
            ->where('user_id', $userId)
            ->get()
            ->sortBy(fn($ump) => $ump->module->name);

        $tenantId = optional($user->customerLogin)->customer_sistapp_id;

        $transactions = ModuleTransactionPayment::with('transaction') // precisa da relação no model
        ->where('user_id', $user->id)
            ->when($tenantId, fn($q) => $q->where('customer_sistapp_id', $tenantId))
            ->orderByDesc('paid_at')
            ->orderByDesc('created_at')
            ->get()
            ->map(function ($p) {
                return [
                    'data'        => $p->paid_at ?? $p->created_at,
                    'charge_id'   => $p->charge_id,
                    'price_paid'  => $p->price_paid,
                    'description' => optional($p->transaction)->description, // opcional
                    'status'      => 'RECEIVED',
                    'cycle'       => $p->cycle,
                    'expires_at'  => $p->expires_at,
                ];
            });

        $segment = optional($user->additionalInfo)->segment; // agencia|empresa|freelancer|null
        $planLabel = match ($segment) {
            'empresa'     => 'Plano Empresa',
            'agencia'     => 'Plano Agência',
            'freelancer'  => 'Plano Freelancer',

            default       => 'Plano'
        };

        $trialEndsAt = $user->trialEndsAt();

        return view('auth.account.my_account', compact(
            'user','statusUser','status','modules','transactions','trialEndsAt','planLabel'
        ));
    }

    public function changeInformation(Request $request, $userId)
    {
        $user = $this->user->find($userId);

        if (!$user) {
            return redirect()->back()->withErrors(['user_not_found' => 'Usuário não encontrado.']);
        }

        $user->update([
            'name' => $request->name,
            'email' => $request->email
        ]);

        $user->customerLogin->customer->update([
            'name' => $request->name,
            'cpfCnpj' => $request->cpfCnpj,
            'mobilePhone' => $request->mobilePhone,
            'address' => $request->address,
            'addressNumber' => $request->addressNumber,
            'postalCode' => $request->postalCode,
            'cityName' => $request->cityName,
            'state' => $request->state,
            'province' => $request->province,
            'complement' => $request->complement
        ]);

        return redirect()->back();
    }

    public function changePassword(Request $request, $userId)
    {
        $user = $this->user->find($userId);

        if (!$user) {
            return redirect()->back()->withErrors(['user_not_found' => 'Usuário não encontrado.']);
        }

        if (!Hash::check($request->old_password, $user->password)) {
            return redirect()->back()->withErrors(['old_password' => 'Senha atual incorreta.']);
        }

        $user->password = Hash::make($request->new_password);
        $user->save();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('password_updated', true);
    }

    public function changeImage(Request $request)
    {
        $request->validate([
            'image' => 'required|image|max:2048',
        ]);

        $user = auth()->user();

        $image = generateImg('image-user');

        $user->image = $image;

        $user->save();

        return response()->json([
            'success' => true,
            'image' => 'data:image/png;base64,' . $image
        ]);
    }
}
