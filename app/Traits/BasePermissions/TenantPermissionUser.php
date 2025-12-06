<?php

namespace App\Traits\BasePermissions;

use App\Models\Authenticate\Permissions\Permission;

trait TenantPermissionUser
{
    protected function ensureTenantBasePermissions(string $tenantId): void
    {
        if (! $tenantId) {
            return;
        }

        $prefix = $tenantId . '_';

        $alreadySeeded = Permission::where('guard_name', 'web')
            ->where('name', 'like', $prefix . '%')
            ->exists();

        if ($alreadySeeded) {
            return;
        }

        $actions = ['visualizar', 'cadastrar', 'editar', 'excluir'];

        $map = [
            'entidades' => [
                'clientes',
                'usuarios',
                'fornecedores'
            ],

            'eecursos humanos' => [
                'departamentos',
                'beneficios',
                'beneficios_funcionarios',
            ],

            'catálogo' => [
                'tipo_servico',
                'servico',
                'equipamentos',
                'pecas',
                'pecas_equipamentos',
            ],

            'ordem de serviço' => [
                'ordem_servico'
            ],

            'permissoes' => [
                'perfis',
                'permissoes',
            ]
        ];

        foreach ($map as $module => $resources) {
            foreach ($resources as $resource) {
                foreach ($actions as $action) {
                    $baseName = "{$action}_{$resource}";
                    $name     = $prefix . $baseName;

                    Permission::firstOrCreate(
                        [
                            'name'       => $name,
                            'guard_name' => 'web',
                        ],
                        []
                    );
                }
            }
        }

        Permission::firstOrCreate(
            [
                'name'       => "{$prefix}aprovar_ordem_servico",
                'guard_name' => 'web',
            ],
            [
                'name'       => "{$prefix}visualizar_cobrancas",
                'guard_name' => 'web',
            ],
            [
                'name'       => "{$prefix}visualizar_contas_a_receber",
                'guard_name' => 'web',
            ],
            [
                'name'       => "{$prefix}visualizar_contas_a_pagar",
                'guard_name' => 'web',
            ],
            [
                'name'       => "{$prefix}visualizar_dashboard",
                'guard_name' => 'web',
            ],
            [
                'name'       => "{$prefix}visualizar_chat_ia",
                'guard_name' => 'web',
            ]
        );
    }
}
