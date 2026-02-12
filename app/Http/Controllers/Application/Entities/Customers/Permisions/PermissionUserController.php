<?php

namespace App\Http\Controllers\Application\Entities\Customers\Permisions;

use App\Http\Controllers\Controller;
use App\Models\Authenticate\Permissions\Permission;
use App\Models\Authenticate\Permissions\Role;
use App\Support\TenantUser\CustomerContext;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PermissionUserController extends Controller
{
    public function view()
    {
        return view('app.entities.customer.permissions.permission_user_index');
    }

    public function rolesIndex(Request $request)
    {
        $tenantId = CustomerContext::get();
        if (! $tenantId) {
            return response()->json([]);
        }

        $prefix = $tenantId . '_';

        $roles = Role::query()
            ->where('guard_name', 'web')
            ->where('name', 'like', $prefix.'%')
            ->orderBy('name')
            ->get()
            // NÃO mostrar a role interna employee_customer_cliqis
            ->filter(function (Role $role) use ($prefix) {
                $base = \Illuminate\Support\Str::startsWith($role->name, $prefix)
                    ? \Illuminate\Support\Str::after($role->name, $prefix)
                    : $role->name;

                return $base !== 'employee_customer_cliqis';
            })
            ->values()
            ->map(function (Role $role) use ($prefix) {
                $display = \Illuminate\Support\Str::startsWith($role->name, $prefix)
                    ? \Illuminate\Support\Str::after($role->name, $prefix)
                    : $role->name;

                return [
                    'id'                => $role->id,
                    'name'              => $role->name,
                    'display_name'      => $display,
                    'permissions_count' => $role->permissions()->count(),
                ];
            });

        return response()->json($roles);
    }

    public function rolesStore(Request $request)
    {
        $tenantId = CustomerContext::get();
        if (! $tenantId) {
            return response()->json(['message' => 'Tenant não definido.'], 422);
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
        ]);

        $displayName = trim($data['name']);
        $prefix      = $tenantId . '_';
        $fullName    = $prefix . $displayName;

        if (Role::where('guard_name', 'web')->where('name', $fullName)->exists()) {
            return response()->json(['message' => 'Já existe um perfil com esse nome.'], 422);
        }

        $role = Role::create([
            'name'       => $fullName,
            'guard_name' => 'web',
        ]);

        return response()->json([
            'id'                => $role->id,
            'name'              => $role->name,
            'display_name'      => $displayName,
            'permissions_count' => 0,
        ], 201);
    }

    public function rolesUpdate(Request $request, Role $role)
    {
        $tenantId = CustomerContext::get();
        if (! $tenantId) {
            return response()->json(['message' => 'Tenant não definido.'], 422);
        }

        $prefix = $tenantId . '_';
        if (! Str::startsWith($role->name, $prefix)) {
            return response()->json(['message' => 'Role não pertence a este tenant.'], 403);
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
        ]);

        $displayName = trim($data['name']);
        $newFullName = $prefix . $displayName;

        if ($role->name !== $newFullName &&
            Role::where('guard_name', 'web')->where('name', $newFullName)->exists()
        ) {
            return response()->json(['message' => 'Já existe um perfil com esse nome.'], 422);
        }

        $role->name = $newFullName;
        $role->save();

        return response()->json([
            'id'                => $role->id,
            'name'              => $role->name,
            'display_name'      => $displayName,
            'permissions_count' => $role->permissions()->count(),
        ]);
    }

    public function rolesDestroy(Role $role)
    {
        $tenantId = CustomerContext::get();
        if (! $tenantId) {
            return response()->json(['message' => 'Tenant não definido.'], 422);
        }

        $prefix = $tenantId . '_';
        if (! Str::startsWith($role->name, $prefix)) {
            return response()->json(['message' => 'Role não pertence a este tenant.'], 403);
        }

        $role->delete();

        return response()->json([
            'success' => true,
            'message' => 'Perfil excluído com sucesso.',
        ]);
    }

    public function permissionsIndex()
    {
        $tenantId = CustomerContext::get();
        if (! $tenantId) {
            return response()->json([]);
        }

        $prefix = $tenantId . '_';

        $permissions = Permission::query()
            ->where('guard_name', 'web')
            ->where('name', 'like', $prefix . '%')
            ->orderBy('name')
            ->get()
            ->map(function (Permission $p) use ($prefix) {
                $base = Str::startsWith($p->name, $prefix)
                    ? Str::after($p->name, $prefix)
                    : $p->name;

                $group   = $this->detectGroupFromName($base);
                $display = $this->humanizePermissionName($base);

                return [
                    'id'           => $p->id,
                    'name'         => $p->name,      // com tenantId_
                    'base_name'    => $base,         // sem tenantId_
                    'group'        => $group,
                    'display_name' => $display,
                ];
            });

        return response()->json($permissions);
    }

    protected function detectGroupFromName(string $name): string
    {
        // ex: "cadastrar Benefícios_Funcionários"
        $clean = str_replace(['.', ':'], ' ', $name);

        // separa por espaço ou underscore e remove vazios
        $parts = preg_split('/[\s_]+/', $clean, -1, PREG_SPLIT_NO_EMPTY);

        // se não tiver pelo menos 2 palavras, manda pra "Geral"
        if (count($parts) < 2) {
            return 'Geral';
        }

        // junta tudo a partir da segunda palavra => recurso
        // ex: ["cadastrar","Benefícios","Funcionários"] => "Benefícios Funcionários"
        $resource = implode(' ', array_slice($parts, 1));

        return ucwords($resource); // deixa bonitinho
    }

    protected function humanizePermissionName(string $name): string
    {
        // finance_receivable_view -> Finance receivable view
        $label = str_replace(['.', '_'], ' ', $name);
        $label = preg_replace('/\s+/', ' ', trim($label));

        return ucfirst($label);
    }

    public function rolePermissions(Role $role)
    {
        $tenantId = CustomerContext::get();
        if (! $tenantId) {
            return response()->json(['message' => 'Tenant não definido.'], 422);
        }

        $prefix = $tenantId . '_';
        if (! Str::startsWith($role->name, $prefix)) {
            return response()->json(['message' => 'Role não pertence a este tenant.'], 403);
        }

        $ids = $role->permissions()->pluck('id');

        return response()->json($ids);
    }

    public function syncRolePermissions(Request $request, Role $role)
    {
        $tenantId = CustomerContext::get();
        if (! $tenantId) {
            return response()->json(['message' => 'Tenant não definido.'], 422);
        }

        $prefix = $tenantId . '_';
        if (! \Illuminate\Support\Str::startsWith($role->name, $prefix)) {
            return response()->json(['message' => 'Role não pertence a este tenant.'], 403);
        }

        $data = $request->validate([
            'permission_ids'   => ['array'],
            // SEUS IDs SÃO UUID, NÃO INTEGER
            'permission_ids.*' => ['string', 'exists:permissions,id'],
            // ou, se quiser forçar uuid:
            // 'permission_ids.*' => ['uuid', 'exists:permissions,id'],
        ]);

        $role->syncPermissions($data['permission_ids'] ?? []);

        return response()->json([
            'success' => true,
            'message' => 'Permissões atualizadas.',
        ]);
    }
}
