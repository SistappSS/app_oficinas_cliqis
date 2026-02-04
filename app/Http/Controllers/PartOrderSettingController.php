<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdatePartOrderSettingRequest;
use App\Models\Entities\Suppliers\Supplier;
use App\Models\PartOrderSetting;
use App\Support\CustomerContext;

class PartOrderSettingController extends Controller
{
    private function tenantId(): string
    {
        return (string) CustomerContext::get();
    }

    private function defaultSubject(): string
    {
        return 'Pedido de peças {{partOrderNumber}}';
    }

    private function defaultBody(): string
    {
        return "Olá {{supplierName}},\n\nSegue o pedido {{partOrderNumber}} do dia {{orderDate}}.\nItens: {{itemsCount}}\nTotal: {{total}}\n\nObrigado.";
    }

    public function show()
    {
        $tenant = $this->tenantId();

        $settings = PartOrderSetting::query()
            ->with('supplier:id,name,email')
            ->where('customer_sistapp_id', $tenant)
            ->first();

        if (!$settings) {
            return response()->json([
                'ok' => true,
                'data' => [
                    'id' => null,
                    'customer_sistapp_id' => $tenant,
                    'default_supplier_id' => null,
                    'supplier' => null,
                    'billing_cnpj' => null,
                    'billing_uf' => null,
                    'email_subject_tpl' => $this->defaultSubject(),
                    'email_body_tpl' => $this->defaultBody(),
                ],
            ]);
        }

        // fallback caso já tenha registro antigo vazio no banco
        if (!trim((string) $settings->email_subject_tpl)) $settings->email_subject_tpl = $this->defaultSubject();
        if (!trim((string) $settings->email_body_tpl))    $settings->email_body_tpl    = $this->defaultBody();

        return response()->json([
            'ok' => true,
            'data' => $settings,
        ]);
    }

    public function upsert(UpdatePartOrderSettingRequest $request)
    {
        $tenant = $this->tenantId();
        $data = $request->validated();

        // valida fornecedor dentro do tenant
        if (!empty($data['default_supplier_id'])) {
            $exists = Supplier::query()
                ->where('id', $data['default_supplier_id'])
                ->where('customer_sistapp_id', $tenant)
                ->where('is_active', true)
                ->exists();

            if (!$exists) {
                return response()->json([
                    'ok' => false,
                    'error' => 'Fornecedor inválido para este cliente.',
                ], 422);
            }
        }

        // ✅ fallback: não salva template vazio
        $subject = trim((string)($data['email_subject_tpl'] ?? ''));
        $body    = trim((string)($data['email_body_tpl'] ?? ''));

        $data['email_subject_tpl'] = $subject !== '' ? $subject : $this->defaultSubject();
        $data['email_body_tpl']    = $body    !== '' ? str_replace("\r\n", "\n", $body) : $this->defaultBody();

        // normalizações leves
        if (!empty($data['billing_uf'])) $data['billing_uf'] = strtoupper($data['billing_uf']);

        $settings = PartOrderSetting::updateOrCreate(
            ['customer_sistapp_id' => $tenant],
            array_merge($data, ['customer_sistapp_id' => $tenant])
        );

        $settings->load('supplier:id,name,email');

        return response()->json([
            'ok' => true,
            'data' => $settings,
        ]);
    }
}
