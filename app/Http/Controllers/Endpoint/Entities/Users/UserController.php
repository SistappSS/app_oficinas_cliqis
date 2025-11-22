<?php

declare(strict_types=1);

namespace App\Http\Controllers\Endpoint\Entities\Users;

use App\Enums\PermissionNameEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\Entities\Users\StoreUserRequest;
use App\Http\Requests\Entities\Users\UpdateUserRequest;
use App\Models\Entities\Users\CustomerUserLogin;
use App\Models\Entities\Users\User;
use App\Traits\HttpResponse;
use App\Traits\RoleCheckTrait;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class UserController extends Controller
{
    use HttpResponse, RoleCheckTrait;

    public function __construct(User $user, CustomerUserLogin $customerUserLogin)
    {
        $this->user = $user;
        $this->customerUserLogin = $customerUserLogin;
    }

    public function index()
    {
        $auth = Auth::user();
        $customerSistappId = $auth->customerLogin->customer_sistapp_id;

        if ($auth->hasRole('admin')) {
            $users = $this->user->with(['roles', 'permissions'])
                ->latest()
                ->get();
        } else {
            $users = $this->user->with(['roles', 'permissions', 'customerLogin'])
                ->whereHas('customerLogin', function ($query) use ($customerSistappId) {
                    $query->where('customer_sistapp_id', $customerSistappId);
                })
                ->latest()
                ->get();
        }

        $users->each(function ($user) {
            $user->humansDate = humansDate($user->created_at);

            $user->translatedPermissions = $user->getAllPermissions()->map(function ($permission) {
                return PermissionNameEnum::getTranslatedPermission($permission->name) ?? $permission->name;
            });
        });

        return $this->trait("get", $users);
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

        if ($user->roles[0]->name) {
            $user->permissionUser = $user->roles[0]->name;
        } else if ($user->permissions[0]->name) {
            $user->permissionUser = $user->permissions[0]->name;
        }

        $user = [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'image' => $user->image,
            'humansDate' => humansDate($user->created_at),
            'is_active' => (bool)$user->is_active,
            'permission' => $user->permissionUser
        ];

        if ($user === null) {
            return $this->trait("error");
        } else {
            return $this->trait("get", $user);
        }
    }

    public function update(UpdateUserRequest $request, int $id)
    {
        $request->validated();

        $imagemBase64 = null;

        $user = $this->user->with('permissions', 'roles')->find($id);

        $permissionUser = $user->roles->isNotEmpty()
            ? $user->roles[0]->name
            : ($user->permissions->isNotEmpty() ? $user->permissions[0]->name : null);

        $oldPermission = match ($permissionUser) {
            'admin' => 'admin',
            'web_design' => 'web_design',
            'business' => 'business',
            'authorized' => 'authorized',
            'free' => 'free',
            default => null,
        };

        $password = $request->password ? Hash::make($request->password) : $user->password;

        if ($request->hasFile('image')) {
            $novaImagem = generateImg('users', $user->image);
            $imagemBase64 = $novaImagem['base64'];

            $user->image = $novaImagem['path'];
        }

        $user->update([
            'name' => ucwords($request->name),
            'email' => $request->email,
            'password' => $password,
            'image' => $imagemBase64,
            'is_active' => (bool) $request->is_active,
            'updated_at' => Carbon::now(),
        ]);

        if ($request->role && $request->role !== $oldPermission) {
            $user->removeRole($oldPermission);
            $user->assignRole($request->role);
        } else {
            $request->merge(['role' => $oldPermission]);
        }

        return $this->trait("update", $user);
    }


    public function destroy($id)
    {
        $user = $this->user->with('customer')->find($id);

        if ($user === null) {
            return $this->trait("error");
        } else {

            $user->delete();

            return $this->trait("delete", $user);
        }
    }
}
