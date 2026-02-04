<?php

namespace App\Services\ServiceOrders;

use App\Models\ServiceOrders\CompletedServiceOrders\CompletedServiceOrder;
use App\Models\ServiceOrders\ServiceOrder;
use Illuminate\Support\Facades\Storage;

class ClientSignatureService
{
    public function save(ServiceOrder $os, string $imageBase64, array $meta = []): CompletedServiceOrder
    {
        $base64 = $imageBase64;

        if (str_starts_with($base64, 'data:image')) {
            [$metaHeader, $base64] = explode(',', $base64, 2);
        }

        $binary = base64_decode($base64, true);
        if ($binary === false) {
            abort(422, 'invalid_base64');
        }

        // limite simples (anti abuso) ~2.5MB
        if (strlen($binary) > 2_500_000) {
            abort(422, 'signature_too_large');
        }

        $fileName = 'client-signature-' . now()->format('Ymd-His') . '.png';
        $path     = $os->id . '/' . $fileName;

        Storage::disk('signatures')->put($path, $binary);

        return CompletedServiceOrder::updateOrCreate(
            ['service_order_id' => $os->id],
            [
                'customer_sistapp_id'   => $os->customer_sistapp_id,
                'client_name'           => $meta['client_name']  ?? $os->client_name ?? null,
                'client_email'          => $meta['client_email'] ?? $os->client_email ?? null,
                'client_signature_path' => $path,
                'client_signed_at'      => now(),
                'completed_at'          => now(),
                'technician_id'         => $meta['technician_id'] ?? null,
            ]
        );
    }
}
