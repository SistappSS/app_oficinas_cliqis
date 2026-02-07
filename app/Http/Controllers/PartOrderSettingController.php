<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdatePartOrderSettingRequest;
use App\Models\Entities\Suppliers\Supplier;
use App\Models\PartOrderSetting;
use App\Support\Audit\Audit;
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

        $beforeModel = PartOrderSetting::where('customer_sistapp_id', $tenant)->first();

        if (!empty($data['default_supplier_id'])) {
            $exists = Supplier::query()
                ->where('id', $data['default_supplier_id'])
                ->where('customer_sistapp_id', $tenant)
                ->where('is_active', true)
                ->exists();

            if (!$exists) {
                Audit::log(
                    'part_order_settings.upsert',
                    'PartOrderSetting',
                    $beforeModel?->id,
                    false,
                    [
                        'reason' => 'invalid_supplier',
                        'default_supplier_id' => $data['default_supplier_id'],
                    ]
                );

                return response()->json([
                    'ok' => false,
                    'error' => 'Fornecedor inválido para este cliente.',
                ], 422);
            }
        }

        $subject = trim((string)($data['email_subject_tpl'] ?? ''));
        $body    = trim((string)($data['email_body_tpl'] ?? ''));

        $data['email_subject_tpl'] = $subject !== '' ? $subject : $this->defaultSubject();
        $data['email_body_tpl']    = $body    !== '' ? str_replace("\r\n", "\n", $body) : $this->defaultBody();

        if (!empty($data['billing_uf'])) $data['billing_uf'] = strtoupper($data['billing_uf']);

        $settings = PartOrderSetting::updateOrCreate(
            ['customer_sistapp_id' => $tenant],
            array_merge($data, ['customer_sistapp_id' => $tenant])
        );

        $settings->load('supplier:id,name,email');

        $before = $beforeModel?->only([
            'default_supplier_id','billing_cnpj','billing_uf','email_subject_tpl','email_body_tpl'
        ]) ?? [];

        $after = $settings->only([
            'default_supplier_id','billing_cnpj','billing_uf','email_subject_tpl','email_body_tpl'
        ]);

        $changes = $this->diffAssoc($before, $after);

        Audit::log(
            'part_order_settings.upsert',
            'PartOrderSetting',
            $settings->id,
            true,
            [
                'changed' => $changes,
            ]
        );

        return response()->json([
            'ok' => true,
            'data' => $settings,
        ]);
    }

    private function diffAssoc(array $before, array $after): array
    {
        $changes = [];

        foreach ($after as $k => $v) {
            $b = $before[$k] ?? null;

            // normaliza strings
            if (is_string($b)) $b = trim($b);
            if (is_string($v)) $v = trim($v);

            // trata null vs "" como igual
            $b2 = ($b === '') ? null : $b;
            $v2 = ($v === '') ? null : $v;

            if ($b2 !== $v2) {
                $changes[$k] = ['from' => $b2, 'to' => $v2];
            }
        }

        return $changes;
    }
}
