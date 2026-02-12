<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\ServiceOrderSignatureLink;
use App\Models\ServiceOrders\ServiceOrder;
use App\Services\ServiceOrders\ClientSignatureService;
use App\Support\TenantUser\CustomerContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ServiceOrderPublicSignatureController extends Controller
{
    private function findValidRequest(string $token): ServiceOrderSignatureLink
    {
        $hash = hash('sha256', $token);

        $req = ServiceOrderSignatureLink::query()
            ->where('token', $hash)
            ->whereNull('used_at')
            ->first();

        if (!$req || !$req->expires_at || $req->expires_at->lte(now())) {
            abort(404);
        }

        return $req;
    }

    private function resolveTenantId(ServiceOrderSignatureLink $req): string
    {
        // sem Eloquent pra não sofrer com scope/boot
        $tenantId = DB::table('service_orders')
            ->where('id', $req->service_order_id)
            ->value('customer_sistapp_id');

        if (!$tenantId) abort(404);

        return (string) $tenantId;
    }

    public function show(string $token)
    {
        $req = $this->findValidRequest($token);
        $tenantId = $this->resolveTenantId($req);

        return CustomerContext::for($tenantId, function () use ($req, $token) {

            $os = ServiceOrder::query()
                ->whereKey($req->service_order_id) // agora o scope funciona pq tenant tá setado
                ->with([
                    'secondaryCustomer',
                    'technician',
                    'serviceItems',
                    'partItems.part',
                    'equipments',
                ])
                ->firstOrFail();

            return view('public.service_orders.signature', [
                'token'  => $token,
                'req'    => $req,
                'os'     => $os,
                'signed' => false,
            ]);
        });
    }

    public function store(string $token, Request $request, ClientSignatureService $sig)
    {
        // validação mais forte (evita lixo e assinatura vazia)
        $data = $request->validate([
            'image_base64' => ['required', 'string', 'starts_with:data:image/png;base64,', 'max:3000000'],
            'client_name'  => ['nullable', 'string', 'max:191'],
            'client_email' => ['nullable', 'email', 'max:191'],
        ]);

        $req = $this->findValidRequest($token);
        $tenantId = $this->resolveTenantId($req);

        return CustomerContext::for($tenantId, function () use ($req, $token, $data, $sig) {

            return DB::transaction(function () use ($req, $token, $data, $sig) {

                // trava o link pra impedir 2 assinaturas simultâneas (corrida)
                $reqLocked = ServiceOrderSignatureLink::query()
                    ->whereKey($req->id)
                    ->lockForUpdate()
                    ->first();

                if (!$reqLocked || $reqLocked->used_at || !$reqLocked->expires_at || $reqLocked->expires_at->lte(now())) {
                    abort(404);
                }

                $os = ServiceOrder::query()
                    ->whereKey($reqLocked->service_order_id)
                    ->firstOrFail();

                $sig->save($os, $data['image_base64'], [
                    'client_name'   => $data['client_name'] ?? null,
                    'client_email'  => $data['client_email'] ?? $reqLocked->email ?? null,
                    'technician_id' => $os->technician_id ?? null,
                ]);

                if (($os->status ?? null) !== 'approved') {
                    $os->status = 'approved';
                    $os->save();
                }

                $reqLocked->used_at = now();
                $reqLocked->save();

                $os->loadMissing([
                    'secondaryCustomer',
                    'technician',
                    'serviceItems',
                    'partItems.part',
                    'equipments',
                ]);

                return view('public.service_orders.signature', [
                    'token'  => $token,
                    'req'    => $reqLocked,
                    'os'     => $os,
                    'signed' => true,
                ]);
            });
        });
    }
}
