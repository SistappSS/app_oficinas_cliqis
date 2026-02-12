<?php

namespace App\Http\Controllers\Application\Entities\Users;

use App\Http\Controllers\Controller;
use App\Http\Requests\Entities\Users\StoreUserRequest;
use App\Models\Authenticate\Permissions\Permission;
use App\Models\Authenticate\Permissions\Role;
use App\Models\Entities\Customers\CustomerEmployeeUser;
use App\Models\Entities\Users\CustomerUserLogin;
use App\Models\Entities\Users\User;
use App\Support\TenantUser\CustomerContext;
use App\Traits\HttpResponse;
use App\Traits\RoleCheckTrait;
use App\Traits\WebIndex;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    use HttpResponse, RoleCheckTrait, WebIndex;

    protected $user;
    protected $customerUserLogin;

    public function __construct(User $user, CustomerUserLogin $customerUserLogin)
    {
        $this->user = $user;
        $this->customerUserLogin = $customerUserLogin;
    }

    public function view()
    {
        return $this->webRoute('app.entities.user.user_index', 'user');
    }

    public function index(Request $request)
    {
        $tenantId = CustomerContext::get();
        abort_if(! $tenantId, 403, 'Tenant não definido.');

        $ids = $this->tenantUserIds();

        $query = User::query()
            ->with(['roles', 'permissions'])
            ->when($ids->isEmpty(), fn($q) => $q->whereRaw('1=0'))
            ->when($ids->isNotEmpty(), fn($q) => $q->whereIn('id', $ids))
            ->orderBy('name');

        if ($term = trim($request->input('q', ''))) {
            $query->where(function ($w) use ($term) {
                $w->where('name', 'like', "%{$term}%")
                    ->orWhere('email', 'like', "%{$term}%");
            });
        }

        $data = $query->paginate(20);

        $ownerIds    = CustomerUserLogin::where('customer_sistapp_id', $tenantId)->pluck('user_id');
        $employeeIds = CustomerEmployeeUser::pluck('user_id');

        $data->getCollection()->transform(function (User $user) use ($ownerIds, $employeeIds) {
            if ($ownerIds->contains($user->id)) {
                $user->type = 'owner';
            } elseif ($employeeIds->contains($user->id)) {
                $user->type = 'employee';
            } else {
                $user->type = 'other';
            }
            return $user;
        });

        return response()->json($data);
    }

    public function store(StoreUserRequest $request)
    {
        $request->validated();

        $imagemBase64 = null;

        if ($request->hasFile('image')) {
            $imagemProduct = $request->file('image');
            $caminhoImagem = $imagemProduct->store('assets/img/products', 'public');

            $imageData = Storage::disk('public')->get($caminhoImagem);
            $image = imagecreatefromstring($imageData);

            if ($image !== false) {
                $w = 150;
                $h = 150;
                $resizedImage = imagescale($image, $w, $h);

                ob_start();
                imagejpeg($resizedImage);
                $imagemBase64 = base64_encode(ob_get_clean());
                imagedestroy($resizedImage);
            }
            imagedestroy($image);
        }

        $authenticatedUser = Auth::user();

        if ($authenticatedUser->hasRole('admin') && $request->role != 'admin') {
            $prefix = 'sist_';
            do {
                $randomNumber = mt_rand(100000, 999999);
                $customerSistappId = $prefix . $randomNumber;
                $trialEnds = Carbon::now()->addDays(14);
            } while ($this->customerUserLogin->where('customer_sistapp_id', $customerSistappId)->exists());
        } else {
            $customerSistappId = $authenticatedUser->customerLogin->customer_sistapp_id;
            $trialEnds = $authenticatedUser->customerLogin->trial_ends_at;
        }

        $user = $this->user->create([
            'name' => ucwords($request->name),
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'image' => $imagemBase64,  // Passando apenas a base64 como string
            'is_active' => 1,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);

        // cadastrar customer e colocar id no customerUserLogin

        $this->customerUserLogin->create([
            'user_id' => $user->id,
            'customer_sistapp_id' => $customerSistappId,
            'trial_ends_at' => $trialEnds,
            'subscription' => $authenticatedUser->hasRole('admin'),
            'is_master_customer' => $authenticatedUser->hasRole('admin'),
        ]);

        if ($authenticatedUser->hasRole('admin')) {
            $user->assignRole($request->role);
        } else {
            $user->syncRoles($authenticatedUser->roles);
            $user->syncPermissions($authenticatedUser->permissions);
        }

        return $this->trait("store", $user);
    }

    public function show($id)
    {
        $user = $this->user->with('permissions', 'roles')->find($id);

        if (! $user) {
            return $this->trait("error");
        }

        $permissionUser = null;

        if ($user->roles->isNotEmpty()) {
            $permissionUser = $user->roles->first()->name;
        } elseif ($user->permissions->isNotEmpty()) {
            $permissionUser = $user->permissions->first()->name;
        }

        $payload = [
            'id'         => $user->id,
            'name'       => $user->name,
            'email'      => $user->email,
            'image'      => $user->image,
            'humansDate' => humansDate($user->created_at),
            'is_active'  => (bool) $user->is_active,
            'permission' => $permissionUser,
        ];

        return $this->trait("get", $payload);
    }

    public function update(Request $request, $id)
    {
        $auth = auth()->user();

        $login = CustomerUserLogin::where('user_id', $auth->id)->first();
        if (!$login) {
            abort(403, 'Usuário sem vínculo de cliente.');
        }

        $tenantId = $login->customer_sistapp_id;
        $isMaster = (bool) $login->is_master_customer;

        if (!$isMaster && (string) $auth->id !== (string) $id) {
            abort(403, 'Você só pode editar o seu próprio usuário.');
        }

        if ($isMaster) {
            $allowedIds = $this->tenantUserIdsFor($tenantId);
            if (!$allowedIds->contains($id)) {
                abort(403, 'Usuário fora do seu tenant.');
            }
        }

        $user = User::findOrFail($id);

        $data = $request->validate([
            'name'        => ['required', 'string', 'max:255'],
            'email'       => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($user->id),
            ],
            'password'      => ['nullable', 'string', 'min:8', 'confirmed'],
            'roles'         => ['array'],
            'roles.*'       => ['string'],
            'permissions'   => ['array'],
            'permissions.*' => ['string'],
        ]);

        $update = [
            'name'  => $data['name'],
            'email' => $data['email'],
        ];

        if (!empty($data['password'])) {
            $update['password'] = Hash::make($data['password']);
        }

        $user->update($update);

        // se não for master, não mexe em roles/perms
        if (!$isMaster) {
            return response()->json($user->fresh(['roles', 'permissions']));
        }

        $tenantPrefix = "{$tenantId}_";

        $tenantRoleNames = Role::where('guard_name', 'web')
            ->where('name', 'like', $tenantPrefix . '%')
            ->pluck('name');

        $currentRoles       = $user->roles->pluck('name');
        $currentTenantRoles = $currentRoles->filter(fn($name) => str_starts_with($name, $tenantPrefix));
        $otherRoles         = $currentRoles->reject(fn($name) => str_starts_with($name, $tenantPrefix));

        $newTenantRoles = collect($data['roles'] ?? [])->intersect($tenantRoleNames);

        $finalRoles = $otherRoles->merge($newTenantRoles)->unique()->values();
        $user->syncRoles($finalRoles);

        $allPermNames = Permission::where('guard_name', 'web')->pluck('name');
        $newPerms     = collect($data['permissions'] ?? [])->intersect($allPermNames);

        $user->syncPermissions($newPerms);

        $user->load(['roles', 'permissions']);

        return response()->json($user);
    }

    public function destroy($id)
    {
        $tenantId = CustomerContext::get();
        if (!$tenantId) {
            abort(403, 'Tenant não definido.');
        }

        $allowedIds = $this->tenantUserIds();
        abort_unless($allowedIds->contains($id), 403, 'Usuário fora do tenant.');

        $user = User::findOrFail($id);

        $isOwner = CustomerUserLogin::where('customer_sistapp_id', $tenantId)
            ->where('user_id', $user->id)
            ->exists();

        if ($isOwner) {
            return response()->json([
                'ok'      => false,
                'message' => 'Não é possível excluir o usuário principal do cliente.',
            ], 409);
        }

        CustomerEmployeeUser::where('user_id', $user->id)->delete();
        $user->delete();

        return response()->json(['ok' => true]);
    }

    public function permissions()
    {
        $tenantId = CustomerContext::get();
        if (!$tenantId) {
            abort(403, 'Tenant não definido.');
        }

        $prefix = "{$tenantId}_";

        $roles = Role::where('guard_name', 'web')
            ->where('name', 'like', $prefix . '%')
            ->orderBy('name')
            ->get()
            ->map(function (Role $role) use ($prefix) {
                $short = preg_replace('/^' . preg_quote($prefix, '/') . '/', '', $role->name);
                return [
                    'name'  => $role->name,
                    'short' => str_replace('_', ' ', $short),
                ];
            })
            ->values();

        $permissions = Permission::where('guard_name', 'web')
            ->whereHas('roles', function ($q) use ($prefix) {
                $q->where('name', 'like', $prefix . '%');
            })
            ->orderBy('name')
            ->get();

        $permissionsGrouped = $permissions
            ->groupBy(function (Permission $p) {
                return explode('_', $p->name)[0];
            })
            ->map(function ($group) {
                return $group->map(function (Permission $p) {
                    return [
                        'name'  => $p->name,
                        'label' => str_replace('_', ' ', $p->name),
                    ];
                })->values();
            });

        return response()->json([
            'roles'               => $roles,
            'permissions_grouped' => $permissionsGrouped,
        ]);
    }

    protected function tenantUserIds(): Collection
    {
        $tenantId = CustomerContext::get();

        if (!$tenantId) {
            return collect();
        }

        $ownerIds = CustomerUserLogin::where('customer_sistapp_id', $tenantId)->pluck('user_id');

        $employeeIds = CustomerEmployeeUser::pluck('user_id');

        return $ownerIds->merge($employeeIds)->unique()->values();
    }

    protected function tenantUserIdsFor(string $tenantId): Collection
    {
        // donos (customer_user_logins)
        $ownerIds = CustomerUserLogin::where('customer_sistapp_id', $tenantId)
            ->pluck('user_id');

        // funcionários (pivot), com coluna customer_sistapp_id
        $employeeIds = CustomerEmployeeUser::where('customer_sistapp_id', $tenantId)
            ->pluck('user_id');

        return $ownerIds->merge($employeeIds)->unique()->values();
    }
}
