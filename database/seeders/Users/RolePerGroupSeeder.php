<?php

namespace Database\Seeders\Users;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolePerGroupSeeder extends Seeder
{
    public function run(): void
    {
        // carrega todos os nomes de permissões existentes
        $all = Permission::query()->pluck('name')->all();

        // agrupar por base (antes do sufixo de ação)
        $groups = [];
        foreach ($all as $perm) {
            $base = $this->baseKey($perm);
            $groups[$base][] = $perm;
        }

        // criar uma role por grupo e atribuir todas as permissões do grupo
        foreach ($groups as $base => $perms) {
            $roleName = "{$base}_role"; // ex.: entitie_user_role
            $role = Role::firstOrCreate(['name' => $roleName]);
            $role->givePermissionTo($perms);
        }
    }

    /**
     * Extrai a "base" do nome da permissão, removendo o sufixo de ação.
     * Trata também o caso especial de *_report_view.
     */
    private function baseKey(string $permission): string
    {
        // caso especial: termina com _report_view
        $reportViewSuffix = '_report_view';
        if (str_ends_with($permission, $reportViewSuffix)) {
            return substr($permission, 0, -strlen($reportViewSuffix));
        }

        // sufixos padrão de ação (adicione mais se necessário)
        $suffixes = [
            '_create',
            '_view',
            '_edit',
            '_delete',
            '_open',
            '_close',
        ];

        foreach ($suffixes as $suf) {
            if (str_ends_with($permission, $suf)) {
                return substr($permission, 0, -strlen($suf));
            }
        }

        // se não casou com nada, retorna como está (grupo unitário)
        return $permission;
    }
}
