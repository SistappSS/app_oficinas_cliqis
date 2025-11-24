<?php

namespace App\Http\Controllers\Application\Auth\Permissions\Modules;

use App\Http\Controllers\Controller;
use App\Models\Entities\Customers\Customer;
use App\Models\Entities\Users\CustomerUserLogin;
use App\Models\Entities\Users\User;
use App\Models\Modules\Module;
use App\Models\Modules\ModuleTransaction;
use App\Models\Modules\ModuleTransactionPayment;
use App\Models\Modules\UserModulePermission;
use App\Services\Modules\BillingService;
use App\Services\Modules\EligibilityService;
use App\Services\Modules\PricingService;
use App\Services\PaymentGateway\Connectors\AsaasConnector;
use App\Services\PaymentGateway\Gateway;
use App\Traits\RoleCheckTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class BuyModuleController extends Controller
{
    use RoleCheckTrait;

    public function __construct(
        Module $module,
        User $user,
        ModuleTransaction $moduleTransaction,
        CustomerUserLogin $customerLogin,
        private PricingService $pricing,
        private EligibilityService $eligibility,
        private BillingService $billing
    ) {
        $this->module            = $module;
        $this->user              = $user;
        $this->moduleTransaction = $moduleTransaction;
        $this->customerLogin     = $customerLogin;
    }

    /**
     * Tela de billing (somente mensal).
     */
    public function billing($userId)
    {
        $authId = auth()->id();

        if ((int)$userId !== (int)$authId && !$this->userHasRole('admin')) {
            abort(403);
        }

        $user = User::with([
            'modulePermissions' => fn ($q) => $q->where('expires_at', '>', now()),
            'modulePermissions.userFeatures.feature:id,name,price',
            'additionalInfo',
            'customerLogin',
        ])->findOrFail($userId);

        $segment = optional($user->additionalInfo)->segment;

        // Catálogo ativo
        $modules = Module::with('features')
            ->where('is_active', true)
            ->get(['id', 'name', 'description', 'price', 'icon']);

        // Tudo que escolheu no trial (pré-seleção)
        $allUmps = UserModulePermission::with('userFeatures')
            ->where('user_id', $userId)
            ->get();

        $trialModuleIds = $allUmps->pluck('module_id')->map(fn ($id) => (string) $id);

        $trialFeaturesByModule = $allUmps->mapWithKeys(fn ($ump) => [
            $ump->module_id => $ump->userFeatures->pluck('feature_id')->map(fn ($v) => (int) $v)->all(),
        ]);

        $hasPaid = ModuleTransactionPayment::where('user_id', (int)$userId)
            ->where('customer_sistapp_id', optional($user->customerLogin)->customer_sistapp_id)
            ->exists();

        $isSubscribed  = (bool) optional($user->customerLogin)->subscription;
        $considerOwned = $hasPaid || $isSubscribed;

        if ($considerOwned) {
            $active = $considerOwned
                ? UserModulePermission::with(['userFeatures' => fn($q) => $q->where('expires_at', '>', now())])
                    ->where('user_id', $user->id)
                    ->where('expires_at', '>', now())
                    ->get()
                : collect();

            $ownedModuleIds = $active->pluck('module_id')->map(fn ($id) => (string) $id);

            $ownedFeaturesByModule = $active->mapWithKeys(fn ($ump) => [
                $ump->module_id => $ump->userFeatures->pluck('feature_id')->map(fn ($v) => (int) $v)->all(),
            ]);

            $expiryByModule = $active->mapWithKeys(fn ($ump) => [
                $ump->module_id => optional($ump->expires_at)?->toDateTimeString(),
            ]);

            $renewalByModule = $active->mapWithKeys(function ($ump) {
                $exp = optional($ump->expires_at);
                $in  = $exp ? now()->between($exp->copy()->subDays(3), $exp->copy()->addDays(3)) : false;
                return [$ump->module_id => $in];
            });
        } else {
            // Em trial nada é OWNED
            $ownedModuleIds        = collect();
            $ownedFeaturesByModule = collect();
            $expiryByModule        = collect();
            $renewalByModule       = collect();
        }

        // Obrigatórios por segmento
        $requiredIds = collect();
        if ($segment) {
            $requiredIds = \App\Models\Authenticate\ModuleSegmentRequirement::where('segment', $segment)
                ->where('is_required', true)
                ->pluck('module_id')
                ->map(fn ($id) => (string) $id);
        }

        // Pré-seleção = trial + obrigatórios
        $initialSelected = $trialModuleIds->merge($requiredIds)->unique()->values();

        return view('app.modules.buy_module.billing', compact(
            'modules',
            'requiredIds',
            'initialSelected',
            'ownedModuleIds',
            'ownedFeaturesByModule',
            'expiryByModule',
            'renewalByModule',
            'trialFeaturesByModule'
        ));
    }

    public function qrCodeGenerate(Request $request)
    {
        try {
            $user = $this->user
                ->with('customerLogin.customer')
                ->findOrFail(auth()->id());

            // ---- validações básicas
            $requestedModuleIds = collect($request->input('module_ids', []))
                ->map(fn ($v) => (int) $v)
                ->filter()
                ->values();

            if (!$user || $requestedModuleIds->isEmpty()) {
                return response()->json(['success' => false, 'error' => 'Dados inválidos.'], 422);
            }

            $selectedFeatures = collect($request->input('selected_features', [])); // [module_id => [feature_id...]]
            $scope = trim((string) $request->input('scope', 'standard')) ?: 'standard';

            // ---- catálogo solicitado
            $modules = Module::with(['features' => function ($q) {
                $q->select('id', 'module_id', 'name', 'price', 'is_required');
            }])->findMany($requestedModuleIds);

            if ($modules->isEmpty()) {
                return response()->json(['success' => false, 'error' => 'Módulos inválidos.'], 422);
            }

            // ---- OWNED real (pagos/subscription) — trial não conta
            $hasPaid       = ModuleTransactionPayment::where('user_id', $user->id)->exists();
            $isSubscribed  = (bool) optional($user->customerLogin)->subscription;
            $considerOwned = $hasPaid || $isSubscribed;

            $active = $considerOwned
                ? UserModulePermission::with('userFeatures')
                    ->where('user_id', $user->id)
                    ->where('expires_at', '>', now())
                    ->get()
                : collect();

            $ownedModules = $active->pluck('module_id')->map(fn($v)=>(int)$v)->all();

            $ownedFeaturesByModule = $active->mapWithKeys(function ($ump) {
                return [
                    (int)$ump->module_id => $ump->userFeatures
                        ->pluck('feature_id')
                        ->map(fn ($v) => (int) $v)
                        ->all()
                ];
            });

            $renewalByModule = $active->mapWithKeys(function ($ump) {
                $exp = optional($ump->expires_at);
                $in  = $exp ? now()->between($exp->copy()->subDays(3), $exp->copy()->addDays(3)) : false;
                return [(int)$ump->module_id => $in];
            });

            // ---- pricing
            $totalValue     = 0.0;
            $priceBreakdown = [];

            foreach ($modules as $module) {
                $mid         = (int) $module->id;
                $hasFeatures = ($module->features && $module->features->count() > 0);

                $pickedIds = collect($selectedFeatures->get($mid, []))
                    ->map(fn ($v) => (int) $v)
                    ->unique();

                $alreadyOwns = $considerOwned && in_array($mid, $ownedModules, true);

                if ($alreadyOwns) {
                    if ($renewalByModule->get($mid, false) === true && $pickedIds->isNotEmpty()) {
                        return response()->json([
                            'success' => false,
                            'error'   => 'Você está no período de renovação deste módulo. Renove o módulo completo para alterar features.',
                        ], 422);
                    }

                    if ($hasFeatures) {
                        $requiredIds = $module->features->where('is_required', true)->pluck('id')->map(fn ($v) => (int) $v);
                        $ownedFeat   = collect($ownedFeaturesByModule->get($mid, []))->map(fn ($v) => (int) $v);
                        $toChargeIds = $requiredIds->merge($pickedIds)->unique()->diff($ownedFeat);

                        foreach ($module->features->whereIn('id', $toChargeIds->all()) as $f) {
                            $totalValue += (float) $f->price;
                            $priceBreakdown[] = [
                                'module_id'    => $mid,
                                'feature_id'   => (int) $f->id,
                                'feature_name' => $f->name,
                                'price'        => (float) $f->price,
                                'type'         => 'feature',
                            ];
                        }
                    }

                    continue;
                }

                // compra nova
                if ($hasFeatures) {
                    $requiredIds = $module->features->where('is_required', true)->pluck('id')->map(fn ($v) => (int) $v);
                    $toChargeIds = $requiredIds->merge($pickedIds)->unique();

                    foreach ($module->features->whereIn('id', $toChargeIds->all()) as $f) {
                        $totalValue += (float) $f->price;
                        $priceBreakdown[] = [
                            'module_id'    => $mid,
                            'feature_id'   => (int) $f->id,
                            'feature_name' => $f->name,
                            'price'        => (float) $f->price,
                            'type'         => 'feature',
                        ];
                    }
                } else {
                    $totalValue += (float) $module->price;
                    $priceBreakdown[] = [
                        'module_id'   => $mid,
                        'module_name' => $module->name,
                        'price'       => (float) $module->price,
                        'type'        => 'module',
                    ];
                }
            }

            if (($totalValue ?? 0) <= 0) {
                return response()->json(['success' => false, 'error' => 'Nenhum item novo foi selecionado.'], 422);
            }

            $billableModuleIds = collect($priceBreakdown)
                ->pluck('module_id')->filter()->unique()->values()->all();

            $billableSelected = collect($priceBreakdown)
                ->where('type','feature')
                ->groupBy('module_id')
                ->map(fn($g)=> $g->pluck('feature_id')->map(fn($v)=>(int)$v)->values()->all())
                ->toArray();

            // ============================
            // 1) EXTERNAL REFERENCE (sempre o mesmo para esse payload)
            // ============================
            $external = $this->billing->buildExternalReference(
                $user->id,
                $billableModuleIds,
                $billableSelected,
                'monthly',
                $scope
            );

            // ============================
            // 2) TENTA REAPROVEITAR COBRANÇA LOCAL
            // ============================
            $tenantId = optional($user->customerLogin)->customer_sistapp_id;

            $existing = ModuleTransaction::withoutGlobalScopes()
                ->where('user_id', $user->id)
                ->when($tenantId, fn ($q) => $q->where('customer_sistapp_id', $tenantId))
                ->where('external_reference', $external)
                ->where('status', 'PENDING')
                ->latest('id')
                ->first();

            //dd($existing, $external);

            if ($existing) {
                $due = $existing->due_date
                    ? \Carbon\Carbon::parse($existing->due_date)->endOfDay()
                    : null;

                if (!$due || now()->lt($due)) {
                    return response()->json([
                        'success' => true,
                        'data'    => [
                            'payment_id' => $existing->charge_id,
                            'qrCode'     => [
                                'encodedImage'   => $existing->pix_qrcode,
                                'payload'        => $existing->pix_url,
                                'expirationDate' => $existing->due_date,
                            ],
                            'status' => $existing->status,
                        ],
                    ]);
                }
                // se passou daqui: PENDING mas vencida → vamos gerar outra
            }

            // ============================
            // 3) CRIA NOVA VIA BillingService, COM FALLBACK
            // ============================
            $gateway = new Gateway(new AsaasConnector);
            $login   = $user->customerLogin;

            if (!$login) {
                return response()->json(['success' => false, 'error' => 'Usuário sem vínculo de cliente.'], 422);
            }

            $tenant = Customer::withoutGlobalScopes()->find($login->customer_id);
            if (!$tenant) {
                return response()->json(['success' => false, 'error' => 'Usuário sem vínculo de cliente.'], 422);
            }

            if (blank($tenant->customerId)) {
                try {
                    $payload = [
                        'name'    => $tenant->fantasy_name ?? $tenant->legal_name ?? $tenant->name ?? 'Cliente',
                        'email'   => $tenant->email ?? $user->email,
                        'cpfCnpj' => $tenant->document ?? $tenant->cnpj ?? $tenant->cpf ?? null,
                        'phone'   => $tenant->phone ?? null,
                    ];
                    $created = $gateway->customer()->upsert($payload);
                    if (!empty($created['id']) && str_starts_with($created['id'], 'cus_')) {
                        $tenant->customerId = $created['id'];
                        $tenant->save();
                    }
                } catch (\Throwable $e) {
                    // deixa seguir pra validação
                }
            }

            if (blank($tenant->customerId)) {
                return response()->json([
                    'success' => false,
                    'error'   => 'Conta de cobrança não encontrada. Conclua o cadastro (Asaas) do cliente principal.',
                ], 422);
            }

            try {
                // tenta criar/reusar no gateway
                $tx = $this->billing->createOrReusePendingBillable(
                    $user,
                    $billableModuleIds,
                    $billableSelected,
                    $scope,
                    (float) $totalValue,
                    $priceBreakdown,
                    $gateway
                );
            } catch (\Throwable $e) {
                // FALLBACK HARD: se der pau no gateway, tenta reaproveitar do banco
                Log::warning('Falha ao criar cobrança no gateway, tentando reaproveitar local', [
                    'user_id'   => $user->id,
                    'external'  => $external,
                    'exception' => $e->getMessage(),
                ]);

                $tx = ModuleTransaction::where('user_id', $user->id)
                    ->where('external_reference', $external)
                    ->where('status', 'PENDING')
                    ->latest('id')
                    ->first();

                if (!$tx) {
                    Log::error('Erro ao gerar cobrança PIX (sem fallback)', [
                        'user_id' => $user->id,
                        'msg'     => $e->getMessage(),
                    ]);

                    return response()->json([
                        'success' => false,
                        'error'   => 'Erro ao gerar cobrança.',
                    ], 500);
                }
            }

            return response()->json([
                'success' => true,
                'data'    => [
                    'payment_id' => $tx->charge_id,
                    'qrCode'     => [
                        'encodedImage'   => $tx->pix_qrcode,
                        'payload'        => $tx->pix_url,
                        'expirationDate' => $tx->due_date,
                    ],
                    'status' => $tx->status,
                ],
            ]);
        } catch (\Throwable $e) {
            Log::error('Erro ao gerar cobrança PIX (outer catch)', [
                'user_id' => auth()->id(),
                'msg'     => $e->getMessage(),
            ]);

            return response()->json(['success' => false, 'error' => 'Erro ao gerar cobrança.'], 500);
        }
    }

    public function checkPaymentStatus($paymentId)
    {
        // garante que está logado
        if (!auth()->check()) {
            return response()->json([
                'success' => false,
                'status'  => null,
                'done'    => false,
                'message' => 'Não autenticado.',
            ], 401);
        }

        // busca a transação SEM escopos globais
        $tx = ModuleTransaction::withoutGlobalScopes()
            ->where('charge_id', $paymentId)
            ->first();

        if (!$tx) {
            // nada no banco pra esse charge_id
            return response()->json([
                'success' => false,
                'status'  => null,
                'done'    => false,
                'message' => 'Cobrança não encontrada.',
            ], 404);
        }

        // segurança extra: garante que a cobrança é do usuário logado
        if ((int) $tx->user_id !== (int) auth()->id()) {
            abort(403); // ou retorna JSON 403 se preferir
        }

        // se quiser ainda mais rígido, valida tenant também:
        $login = auth()->user()->customerLogin;
        if (!$login || $login->customer_sistapp_id !== $tx->customer_sistapp_id) abort(403);

        $gw = new Gateway(new AsaasConnector);

        $status = $gw->payment()->getPaymentStatus($paymentId)['status'] ?? null;
        $ok = in_array($status, ['RECEIVED','CONFIRMED','RECEIVED_IN_CASH','COMPLETED'], true);

        $done = false;
        if ($ok) {
            // passa $tx pra evitar outra busca e ainda assim
            // o capturePayment faz lock dentro da transaction
            $done = $this->billing->capturePayment($paymentId, $gw, $tx, $status);
        }

        return response()->json([
            'success' => true,
            'status'  => $status,
            'done'    => $done,
        ]);
    }


    public function cancel($paymentId)
    {
        $tx = ModuleTransaction::where('charge_id', $paymentId)->firstOrFail();

        if ($tx->user_id !== auth()->id() && !$this->userHasRole('admin')) {
            abort(403);
        }

        $gateway = new Gateway(new AsaasConnector);
        $this->billing->cancelPending($paymentId, $gateway);

        return back()->with('success', 'Cobrança cancelada.');
    }
}
