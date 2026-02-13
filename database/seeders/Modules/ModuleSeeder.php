<?php

namespace Database\Seeders\Modules;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Arr;
use App\Models\Entities\Users\CustomerUserLogin;

class ModuleSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        $allSegments = ['segment_empresa', 'segment_freelancer', 'segment_agencia'];

        $modules = [
            [
                'name' => 'Usuários & Acessos',
                'description' => 'Gestão de usuários e permissões do sistema.',
                'icon' => 'user-shield',
                'required_for_segments' => $allSegments,
                'features' => [
                    [
                        'name' => 'Usuários',
                        'base_role' => 'entitie_user',
                        'price' => 19.90,
                        'is_required' => 1,
                    ],
                ],
            ],
            [
                'name' => 'Clientes',
                'description' => 'Cadastros e gestão de clientes.',
                'icon' => 'address-book',
                'required_for_segments' => $allSegments,
                'features' => [
                    [
                        'name' => 'Cadastro de Clientes',
                        'base_role' => 'entitie_customer',
                        'price' => 29.90,
                        'is_required' => 1,
                    ],
                ],
            ],
            [
                'name' => 'Vendas',
                'description' => 'Serviços, orçamentos e contratos.',
                'icon' => 'chart-line',
                'required_for_segments' => null,
                'features' => [
                    [
                        'name' => 'Serviços',
                        'base_role' => 'sales_service',
                        'price' => 29.90,
                        'is_required' => 1,
                    ],
                    [
                        'name' => 'Orçamentos',
                        'base_role' => 'sales_budget',
                        'price' => 39.90,
                        'is_required' => 1,
                    ],
                    [
                        'name' => 'Contratos',
                        'base_role' => 'sales_contract',
                        'price' => 39.90,
                        'is_required' => 0, // exemplo de opcional
                    ],
                ],
            ],
            [
                'name' => 'Notas & Cobranças',
                'description' => 'Emissão e controle de notas e cobranças.',
                'icon' => 'file-invoice-dollar',
                'required_for_segments' => null,
                'features' => [
                    [
                        'name' => 'Notas (Invoices)',
                        'base_role' => 'sales_invoice',
                        'price' => 49.90,
                        'is_required' => 1,
                    ],
                    [
                        'name' => 'Cobranças (Billing)',
                        'base_role' => 'sales_billing',
                        'price' => 39.90,
                        'is_required' => 0, // exemplo de opcional
                    ],
                ],
            ],
            [
                'name' => 'Financeiro',
                'description' => 'Contas a pagar e a receber.',
                'icon' => 'wallet',
                'required_for_segments' => null,
                'features' => [
                    [
                        'name' => 'Contas a Pagar',
                        'base_role' => 'finance_payable',
                        'price' => 49.90,
                        'is_required' => 1,
                    ],
                    [
                        'name' => 'Contas a Receber',
                        'base_role' => 'finance_receivable',
                        'price' => 49.90,
                        'is_required' => 1,
                    ],
                ],
            ]
        ];

        $tenants = CustomerUserLogin::query()->select(['user_id', 'customer_sistapp_id'])->get();

        foreach ($tenants as $t) {
            foreach ($modules as $mod) {
                $moduleId = $this->upsertModule(
                    customerSistappId: $t->customer_sistapp_id,
                    userId: (int) $t->user_id,
                    name: (string) $mod['name'],
                    description: (string) $mod['description'],
                    icon: (string) $mod['icon'],
                    requiredForSegments: $mod['required_for_segments'] ?? null,
                    now: $now
                );

                $sum = 0.0;

                foreach ($mod['features'] as $feat) {
                    $price = (float) $feat['price'];
                    $sum += $price;

                    $roles = $this->buildFeatureRoles($feat['base_role'] ?? null);

                    $this->upsertFeature(
                        moduleId: $moduleId,
                        name: (string) $feat['name'],
                        price: $price,
                        roles: $roles,
                        isRequired: (int) ($feat['is_required'] ?? 0),
                        now: $now
                    );
                }

                DB::table('modules')->where('id', $moduleId)->update([
                    'price' => $sum,
                    'updated_at' => $now,
                ]);
            }
        }
    }

    private function buildFeatureRoles(?string $baseRole): array
    {
        if (!$baseRole) return ['admin'];
        return ['admin', "{$baseRole}_role"];
    }

    private function upsertModule(
        string $customerSistappId,
        int $userId,
        string $name,
        string $description,
        string $icon, $requiredForSegments, $now
    ): int {
        $row = DB::table('modules')->where('customer_sistapp_id', $customerSistappId)->where('user_id', $userId)->where('name', $name)->first();

        $payload = [
            'customer_sistapp_id' => $customerSistappId,
            'user_id' => $userId,
            'name' => $name,
            'description' => $description,
            'icon' => $icon,
            'required_for_segments' => $requiredForSegments === null ? null : json_encode($requiredForSegments, JSON_UNESCAPED_UNICODE),
            'is_active' => 1,
            'updated_at' => $now,
        ];

        if (!$row) {
            $payload['price'] = 0.00; // recalcula depois
            $payload['created_at'] = $now;

            return (int) DB::table('modules')->insertGetId($payload);
        }

        DB::table('modules')->where('id', $row->id)->update($payload);

        return (int) $row->id;
    }

    private function upsertFeature(
        int $moduleId,
        string $name,
        float $price,
        array $roles,
        int $isRequired, $now
    ): void {
        $row = DB::table('features')->where('module_id', $moduleId)->where('name', $name)->first();

        $payload = [
            'module_id' => $moduleId,
            'name' => $name,
            'price' => $price,
            'roles' => json_encode(array_values($roles), JSON_UNESCAPED_UNICODE),
            'is_active' => 1,
            'is_required' => $isRequired,
            'updated_at' => $now,
        ];

        if (!$row) {
            $payload['created_at'] = $now;
            DB::table('features')->insert($payload);
            return;
        }

        DB::table('features')->where('id', $row->id)->update($payload);
    }
}
