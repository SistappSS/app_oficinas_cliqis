<?php

namespace App\Services\Modules;

use App\Models\Entities\Customers\Customer;
use App\Models\Entities\Users\CustomerUserLogin;
use App\Models\Entities\Users\User;
use App\Models\Modules\Feature;
use App\Models\Modules\Module;
use App\Models\Modules\ModuleTransaction;
use App\Models\Modules\ModuleTransactionPayment;
use App\Models\Modules\UserFeature;
use App\Models\Modules\UserModuleControl;
use App\Models\Modules\UserModulePermission;
use App\Services\PaymentGateway\Gateway;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BillingService
{
    private const OK_STATUSES = ['RECEIVED', 'RECEIVED_IN_CASH', 'CONFIRMED', 'COMPLETED'];

    // BillingService
    public function buildExternalReference(
        int $userId, array $moduleIds, array $selectedFeatures, string $cycle, string $scope
    ): string {
        $mods = collect($moduleIds)->map(fn($v)=> (int)$v)->unique()->sort()->values()->all();

        $feats = collect($selectedFeatures)
            ->mapWithKeys(fn($arr,$k)=>[(int)$k => collect($arr)->map(fn($v)=>(int)$v)->unique()->sort()->values()->all()])
            ->sortKeys() // chaves (module_id) em ordem
            ->all();

        return hash('sha256', json_encode([
            'u'=>$userId, 'm'=>$mods, 'f'=>$feats, 'c'=>$cycle, 's'=>$scope,
        ], JSON_UNESCAPED_UNICODE));
    }

    protected function resolveGatewayCustomerId(User $user): string
    {
        $login = $user->customerLogin;

        if (!$login) {
            throw new \DomainException('Usuário sem vínculo de cliente.');
        }

        // Busca o customer EXATAMENTE do login
        $customer = Customer::withoutGlobalScopes()
            ->where('id', $login->customer_id)
            ->where('customer_sistapp_id', $login->customer_sistapp_id)
            ->first();

        if (!$customer || empty($customer->customerId) || !str_starts_with($customer->customerId, 'cus_')) {
            throw new \DomainException(
                'Conta de cobrança não encontrada. Conclua o cadastro (Asaas) do cliente principal.'
            );
        }

        return $customer->customerId;
    }

    public function createOrReusePendingBillable(
        User $user,
        array $billableModuleIds,
        array $billableSelectedByModule,
        string $scope,
        float $totalValue,
        array $priceBreakdown,
        Gateway $gateway
    ): ModuleTransaction {
        $cycle = 'monthly';
        $total = round((float) $totalValue, 2);

        // normaliza módulos/features que serão cobrados
        $mods  = collect($billableModuleIds)
            ->map(fn ($v) => (int) $v)
            ->unique()
            ->sort()
            ->values()
            ->all();

        $feats = collect($billableSelectedByModule)
            ->mapWithKeys(fn ($arr, $k) => [
                (int) $k => collect($arr)
                    ->map(fn ($v) => (int) $v)
                    ->unique()
                    ->sort()
                    ->values()
                    ->all(),
            ])
            ->sortKeys()
            ->all();

        $external = $this->buildExternalReference($user->id, $mods, $feats, $cycle, $scope);

        $tenantId = optional($user->customerLogin)->customer_sistapp_id;

        return DB::transaction(function () use (
            $user,
            $mods,
            $feats,
            $scope,
            $cycle,
            $total,
            $priceBreakdown,
            $gateway,
            $external,
            $tenantId
        ) {
            // 1) Reuso sob lock
            $existing = ModuleTransaction::withoutGlobalScopes()
                ->where('user_id', $user->id)
                ->when($tenantId, fn ($q) => $q->where('customer_sistapp_id', $tenantId))
                ->where('status', 'PENDING')
                ->where('external_reference', $external)
                ->lockForUpdate()
                ->first();

            if ($existing) {
                // due_date é DATE na base → considera o dia inteiro como válido
                $due = $existing->due_date
                    ? Carbon::parse($existing->due_date)->endOfDay()
                    : null;

                if ($due && now()->lt($due)) {
                    // ainda válido: reusa a cobrança existente
                    return $existing;
                }

                // expirou (ou sem due) → cancela no gateway e marca como CANCELLED
                try {
                    $gateway->payment()->cancel($existing->charge_id);
                } catch (\Throwable $e) {
                    // ignora erro de cancelamento no gateway
                }

                $existing->update([
                    'status'       => 'CANCELLED',
                    'cancelled_at' => now(),
                ]);
            }

            // 2) Cancela outros PENDING desse usuário com payload diferente
            $pend = ModuleTransaction::withoutGlobalScopes()
                ->where('user_id', $user->id)
                ->when($tenantId, fn ($q) => $q->where('customer_sistapp_id', $tenantId))
                ->where('status', 'PENDING')
                ->where('external_reference', '!=', $external)
                ->lockForUpdate()
                ->get();

            foreach ($pend as $tx) {
                try {
                    $gateway->payment()->cancel($tx->charge_id);
                } catch (\Throwable $e) {
                }

                $tx->update([
                    'status'       => 'CANCELLED',
                    'cancelled_at' => now(),
                ]);
            }

            // 3) valida customer e cria cobrança/QR
            $customerIdGateway = $this->resolveGatewayCustomerId($user);
            $login = $user->customerLogin;

            if (!$login || !$login->customer_id) {
                throw new \DomainException('Usuário sem vínculo de cliente local.');
            }

            $desc = 'Compra: ' . Module::whereIn('id', $mods)->pluck('name')->join(', ');

            $charge = $gateway->payment()->create([
                'billingType'   => 'PIX',
                'customer'      => $customerIdGateway,
                'value'         => $total,
                'dueDate'       => now()->format('Y-m-d'),
                'description'   => $desc,
                'daysAfterDueDateToCancellationRegistration' => 1,
                'externalReference' => $external,
            ]);

            $qr = $gateway->payment()->getPixQrCode($charge['id']);

            $due = !empty($qr['expirationDate'])
                ? Carbon::parse($qr['expirationDate'])
                    ->timezone(config('app.timezone'))
                    ->toDateTimeString()
                : now()->addMinutes(30)->toDateTimeString();

            // 4) cria a transação vinculada a esse charge (ainda dentro da transação)
            return ModuleTransaction::create([
                'user_id'             => $user->id,
                'customer_sistapp_id' => $login->customer_sistapp_id,
                'customer_id'         => (int) $login->customer_id,
                'module_ids'          => json_encode($mods),
                'selected_features'   => json_encode($feats),
                'price_breakdown'     => json_encode($priceBreakdown),
                'charge_id'           => $charge['id'],
                'external_reference'  => $external,
                'billing_type'        => 'pix',
                'cycle'               => $cycle,
                'due_date'            => $due,
                'description'         => $desc,
                'status'              => $charge['status'] ?? 'PENDING',
                'pix_qrcode'          => $qr['encodedImage'] ?? null,
                'pix_url'             => $qr['payload'] ?? null,
                'price_paid'          => 0.00,
            ]);
        });
    }

    public function capturePayment(string $chargeId, Gateway $gateway, ?ModuleTransaction $prefetchedTx = null, ?string $knownStatus = null): bool {
        return DB::transaction(function () use ($chargeId, $gateway, $prefetchedTx, $knownStatus) {

            $tx = $prefetchedTx
                ? ModuleTransaction::withoutGlobalScopes()->whereKey($prefetchedTx->id)->lockForUpdate()->first()
                : ModuleTransaction::withoutGlobalScopes()->where('charge_id',$chargeId)->lockForUpdate()->first();

            if (!$tx || $tx->processed_at) return (bool) $tx;

            $status = $knownStatus ?? ($gateway->payment()->getPaymentStatus($chargeId)['status'] ?? null);


            if (!in_array($status, self::OK_STATUSES, true)) return false;

            CustomerUserLogin::where('user_id',$tx->user_id)->update(['subscription'=>1]);

            $items = collect(json_decode($tx->price_breakdown, true) ?: []);

            $paidTotal = (float) $items->sum('price');
            $tx->update([
                'status'     => $status,
                'payment_at' => now(),
                'price_paid' => $paidTotal,
            ]);

            $monthsToAdd = 1;

            $modulesToTouch = $items->pluck('module_id')->map(fn($v)=>(int)$v)->filter()->unique()->values();
            $featuresPaidByModule = $items->where('type','feature')
                ->groupBy('module_id')
                ->map(fn($g)=> $g->pluck('feature_id')->map(fn($v)=>(int)$v)->unique()->values());

            foreach ($modulesToTouch as $mid) {
                $ump = UserModulePermission::firstOrCreate([
                    'user_id' => $tx->user_id,
                    'module_id' => (int)$mid,
                    'customer_sistapp_id' => $tx->customer_sistapp_id,
                ]);

                $paidFeatures = collect($featuresPaidByModule->get($mid, []));

                if ($paidFeatures->isEmpty()) {
                    // Cobrou o módulo "puro"
                    $base = ($ump->expires_at && \Carbon\Carbon::parse($ump->expires_at)->isFuture())
                        ? \Carbon\Carbon::parse($ump->expires_at) : now();
                    $ump->update(['expires_at' => $base->copy()->addMonths($monthsToAdd)]);
                } else {
                    // Cobrou features: estende só elas e depois sincroniza o vencimento do módulo
                    foreach ($paidFeatures as $fid) {
                        $uf = \App\Models\Modules\UserFeature::firstOrNew([
                            'user_module_permission_id' => $ump->id,
                            'feature_id' => (int)$fid,
                        ]);
                        $cur = $uf->expires_at ? \Carbon\Carbon::parse($uf->expires_at) : null;
                        $start = ($cur && $cur->isFuture()) ? $cur : now();

                        $uf->is_active = 1;
                        $uf->selected = 1;
                        $uf->price = (float) optional(\App\Models\Modules\Feature::find($fid))->price ?? 0;
                        $uf->activated_at = $uf->activated_at ?: now();
                        $uf->expires_at = $start->copy()->addMonths($monthsToAdd);
                        $uf->save();
                    }

                    $maxFeatExp = \App\Models\Modules\UserFeature::where('user_module_permission_id',$ump->id)->max('expires_at');
                    if ($maxFeatExp) $ump->update(['expires_at'=>$maxFeatExp]);
                }

                // controle financeiro por módulo desta compra
                $moduleTotal = (float) $items->where('module_id',$mid)->sum('price');
                \App\Models\Modules\UserModuleControl::create([
                    'user_module_permission_id'=>$ump->id,
                    'cycle'=>'monthly','total'=>$moduleTotal,
                    'contracted_date'=>now(),'month_reference'=>now()->month,'year_reference'=>now()->year,
                ]);
            }

            $tx->update(['processed_at'=>now()]);

            if (class_exists(\App\Services\Modules\RoleSyncService::class)) {
                app(\App\Services\Modules\RoleSyncService::class)->syncFromFeatures($tx->user);
            }

            $this->enforcePaidStateSafe($tx);
            return true;
        });
    }

    public function cancelUserPendings(User $user, Gateway $gateway): void
    {
        $tenant = optional($user->customerLogin)->customer_sistapp_id;

        DB::transaction(function () use ($user, $gateway, $tenant) {
            $q = ModuleTransaction::where('user_id', $user->id)->where('status', 'PENDING')->lockForUpdate();
            if ($tenant) $q->where('customer_sistapp_id', $tenant);

            $txs = $q->get();

            foreach ($txs as $tx) {
                try {
                    $gateway->payment()->cancel($tx->charge_id);
                } catch (\Throwable $e) {
                }
                $tx->update(['status' => 'CANCELLED', 'cancelled_at' => now()]);
            }
        });
    }

    public function cancelPending(string $chargeId, Gateway $gateway): void
    {
        DB::transaction(function () use ($chargeId, $gateway) {
            $tx = ModuleTransaction::where('charge_id', $chargeId)->lockForUpdate()->first();
            if (!$tx || $tx->status !== 'PENDING') return;

            try {
                $gateway->payment()->cancel($chargeId);
            } catch (\Throwable $e) {
            }

            $tx->update([
                'status' => 'CANCELLED',
                'cancelled_at' => now(),
            ]);
        });
    }

    protected function enforcePaidStateSafe(ModuleTransaction $tx): void
    {
        // desativa features vencidas
        UserFeature::whereHas('ump', fn($q) => $q->where('user_id', $tx->user_id))
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', now())
            ->update(['is_active' => 0]);

        // módulo expira pelo maior vencimento das features
        UserModulePermission::where('user_id', $tx->user_id)->get()->each(function ($ump) {
            $max = UserFeature::where('user_module_permission_id', $ump->id)->max('expires_at');
            if ($max && (!$ump->expires_at || Carbon::parse($max)->gt($ump->expires_at))) {
                $ump->expires_at = $max;
                $ump->save();
            }
        });
    }
}
