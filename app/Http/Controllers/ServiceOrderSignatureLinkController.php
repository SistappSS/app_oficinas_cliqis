<?php

namespace App\Http\Controllers;

use App\Mail\ServiceOrders\ServiceOrderSignatureLinkMail;
use App\Models\ServiceOrders\ServiceOrder;
use App\Models\ServiceOrderSignatureLink;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class ServiceOrderSignatureLinkController extends Controller
{
    public function send(Request $request, ServiceOrder $serviceOrder)
    {
        // tenta pegar do body, senão do cliente da OS
        $email = $request->input('email')
            ?: optional($serviceOrder->secondaryCustomer)->email
                ?: $serviceOrder->requester_email;

        if (!$email) {
            return response()->json([
                'ok' => false,
                'message' => 'Sem e-mail do cliente para enviar o link.',
            ], 422);
        }

        $expiresAt = now()->addDays(2);
        $token = Str::random(64);

        ServiceOrderSignatureLink::create([
            'service_order_id'        => $serviceOrder->id,
            'token'                  => hash('sha256', $token),
            'email'                  => $email,
            'expires_at'             => $expiresAt,
            'sent_at'                => now(),
            'created_by_employee_id' => optional(auth()->user())->employee_id ?? null,
        ]);

        // se você ainda não tem a tela pública, deixa isso assim por enquanto
        $link = url("/service-orders/sign/{$token}");

        Mail::to($email)->send(new ServiceOrderSignatureLinkMail(
            order_number: (string)($serviceOrder->order_number ?? $serviceOrder->id),
            link: $link,
            customer_name: optional($serviceOrder->secondaryCustomer)->name,
            expires_at: $expiresAt->format('d/m/Y H:i'),
        ));

        return response()->json([
            'ok' => true,
            'message' => 'Link enviado com sucesso.',
        ]);
    }
}
