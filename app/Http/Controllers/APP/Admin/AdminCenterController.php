<?php

namespace App\Http\Controllers\APP\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class AdminCenterController extends Controller
{
    public function roleIndex()
    {
        $roles = Role::with('permissions')->get();

        $permissions = Permission::all();

        $groupedPermissions = [];

        foreach ($permissions as $permission) {
            $prefix = Str::before($permission->name, '_');

            $group = $groupNames[$prefix] ?? ucfirst($prefix);

            $groupedPermissions[$group][] = $permission;
        }

        ksort($groupedPermissions);

        return view('app.admin_center.role.role_index', compact('roles', 'groupedPermissions'));
    }

    public function roleCreate()
    {
        $permissions = Permission::where('guard_name', 'web')->pluck('name');

        $groupedPermissions = $permissions->reduce(function ($carry, $permission) {
            // Padrão: xxx_yyy_zzz => pega "xxx_yyy"
            $segments = explode('_', $permission);
            $groupKey = count($segments) >= 2
                ? $segments[0] . '_' . $segments[1]
                : $segments[0];

            $carry[$groupKey][] = $permission;

            return $carry;
        }, []);

        ksort($groupedPermissions); // ordena por grupo

        return view('app.admin_center.role.role_create', compact('groupedPermissions'));
    }

    public function roleStore(Request $request)
    {
        $request->validate([
            'name' => 'required|string|unique:roles,name',
            'permissions' => 'nullable|array',
            'permissions.*' => 'string|exists:permissions,name',
        ]);

        $role = Role::create([
            'name' => $request->name,
            'guard_name' => 'web',
        ]);

        if ($request->has('permissions')) {
            $role->syncPermissions($request->permissions);
        }

        return redirect()->route('roles.index')->with('success', 'Role criada com sucesso!');
    }

    public function permissionIndex()
    {
        $permissions = Permission::where('guard_name', 'web')->get();
        return view('app.admin_center.permission.permission_index', compact('permissions'));
    }

    public function permissionStore(Request $request)
    {
        $request->validate([
            'name' => 'required|string',
            'actions' => 'nullable|array',
        ]);

        $names = [];

        if ($request->filled('actions')) {
            foreach ($request->actions as $action) {
                $names[] = strtolower($request->name.'_'.$action);
            }
        } else {
            $names[] = $request->name;
        }

        foreach ($names as $name) {
            Permission::firstOrCreate([
                'name' => $name,
                'guard_name' => 'web',
            ]);
        }

        if ($request->expectsJson()) {
            return response()->json(['success' => true]);
        }

        return redirect()->route('permissions.index')->with('success', 'Permissão(s) criada(s) com sucesso!');
    }

    public function getPermissions()
    {
        $permissions = Permission::where('guard_name', 'web')->get();

        $grouped = $permissions->groupBy(function ($permission) {
            $parts = explode('_', $permission->name);
            return count($parts) > 1 ? implode('_', array_slice($parts, 0, -1)) : 'Outros';
        });

        return response()->json($grouped);
    }
}
