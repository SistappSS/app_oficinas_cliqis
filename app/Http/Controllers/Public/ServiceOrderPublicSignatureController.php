<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\ServiceOrderSignatureLink;
use App\Services\ServiceOrders\ClientSignatureService;
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

        // expiração valida em PHP (evita treta de timezone no SQL)
        if (!$req || !$req->expires_at || $req->expires_at->lte(now())) {
            abort(404);
        }

        return $req;
    }

    public function show(string $token)
    {
        logger()->info('signature_link_open', [
            'ip' => request()->ip(),
            'ua' => request()->userAgent(),
            'url' => request()->fullUrl(),
            'token_len' => strlen($token),
        ]);

        $req = $this->findValidRequest($token);

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

        $data = $request->validate([
            'image_base64' => ['required', 'string'],
            'client_name'  => ['nullable', 'string', 'max:191'],
            'client_email' => ['nullable', 'string', 'max:191'],
        ]);

        $os = $req->serviceOrder()->firstOrFail();

        // salva assinatura
        $sig->save($os, $data['image_base64'], [
            'client_name'   => $data['client_name'] ?? null,
            'client_email'  => $data['client_email'] ?? $req->email ?? null, // ✅ coluna existe
            'technician_id' => $os->technician_id ?? null,
        ]);

        // aprova OS
        if (($os->status ?? null) !== 'approved') {
            $os->status = 'approved';
            $os->save();
        }

        // consome token (one-time)
        $req->used_at = now(); // ✅ coluna existe
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
