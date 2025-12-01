<?php

namespace App\Traits\BasePermissions;

use App\Models\Authenticate\Permissions\Permission;

trait TenantPermissionUser
{
    /**
     * Garante que as permissões base do tenant existam.
     * Idempotente: se já existir uma permission com o prefixo, não recria nada.
     */
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

        // cuidado com acento em "usuário" => usa "usuario" na chave interna
        $map = [
            'Entidades' => [
                'Clientes',
                'Usuários',
            ],

            'Recursos Humanos' => [
                'Departamentos',
                'Benefícios',
                'Benefícios_Funcionários',
            ],

            'Catálogo' => [
                'Tipo de serviço',
                'Serviço',
                'Peças',
                'Peças_Equipamentos',
            ],

            'Ordem de Serviço' => [
                'Ordem de Serviço'
            ],
        ];

        foreach ($map as $module => $resources) {
            foreach ($resources as $resource) {
                foreach ($actions as $action) {
                    $baseName = "{$action} {$resource}";
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
    }
}
