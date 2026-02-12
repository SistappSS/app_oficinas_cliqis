<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\ServiceOrders\ServiceOrder;
use App\Models\ServiceOrderSignatureLink;
use App\Services\ServiceOrders\ClientSignatureService;
use App\Support\TenantUser\CustomerContext;
use Illuminate\Http\Request;

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

    private function bootTenant(ServiceOrderSignatureLink $req): void
    {
        $tenantId = ServiceOrder::withoutGlobalScope('customer')
            ->whereKey($req->service_order_id)
            ->value('customer_sistapp_id');

        if (!$tenantId) abort(404);

        CustomerContext::set($tenantId);
    }

    public function show(string $token)
    {
        $req = $this->findValidRequest($token);

        $this->bootTenant($req);

        $os = $req->serviceOrder()
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
    }

    public function store(string $token, Request $request, ClientSignatureService $sig)
    {
        $req = $this->findValidRequest($token);

        $this->bootTenant($req);

        $data = $request->validate([
            'image_base64' => ['required', 'string'],
            'client_name'  => ['nullable', 'string', 'max:191'],
            'client_email' => ['nullable', 'string', 'max:191'],
        ]);

        $os = $req->serviceOrder()->firstOrFail();

        $sig->save($os, $data['image_base64'], [
            'client_name'   => $data['client_name'] ?? null,
            'client_email'  => $data['client_email'] ?? $req->email ?? null,
            'technician_id' => $os->technician_id ?? null,
        ]);

        if (($os->status ?? null) !== 'approved') {
            $os->status = 'approved';
            $os->save();
        }

        $req->used_at = now();
        $req->save();

        $os->loadMissing([
            'secondaryCustomer',
            'technician',
            'serviceItems',
            'partItems.part',
            'equipments',
        ]);

        return view('public.service_orders.signature', [
            'token'  => $token,
            'req'    => $req,
            'os'     => $os,
            'signed' => true,
        ]);
    }
}
