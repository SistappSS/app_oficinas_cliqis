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

//        $alreadySeeded = Permission::where('guard_name', 'web')
//            ->where('name', 'like', $prefix . '%')
//            ->exists();
//
//        if ($alreadySeeded) {
//            return;
//        }
//
//        $actions = ['visualizar', 'cadastrar', 'editar', 'excluir'];
//
//        $map = [
//            'entidades' => [
//                'clientes',
//                'usuarios',
//                'fornecedores'
//            ],
//
//            'eecursos humanos' => [
//                'departamentos',
//                'beneficios',
//                'beneficios_funcionarios',
//            ],
//
//            'catálogo' => [
//                'tipo_servico',
//                'servico',
//                'equipamentos',
//                'pecas',
//                'pecas_equipamentos',
//            ],
//
//            'ordem de serviço' => [
//                'ordem_servico'
//            ],
//
//            'permissoes' => [
//                'perfis',
//                'permissoes',
//            ]
//        ];
//
//        foreach ($map as $module => $resources) {
//            foreach ($resources as $resource) {
//                foreach ($actions as $action) {
//                    $baseName = "{$action}_{$resource}";
//                    $name     = $prefix . $baseName;
//
//                    Permission::firstOrCreate(
//                        [
//                            'name'       => $name,
//                            'guard_name' => 'web',
//                        ],
//                        []
//                    );
//                }
//            }
//        }

        $extras = [
            'aprovar_ordem_servico',
            'visualizar_cobrancas',
            'visualizar_contas_a_receber',
            'visualizar_contas_a_pagar',
            'visualizar_dashboard',
            'visualizar_chat_ia',
        ];

        foreach ($extras as $perm) {
            Permission::firstOrCreate(
                [
                    'name'       => "{$prefix}{$perm}",
                    'guard_name' => 'web',
                ],
                []
            );
        }
    }
}
