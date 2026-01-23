<?php

namespace App\Http\Controllers\Application\HumanResources;

use App\Http\Controllers\Controller;
use App\Models\Authenticate\Permissions\Role;
use App\Models\Entities\Customers\CustomerEmployeeUser;
use App\Models\HumanResources\Employees\Employee;
use App\Models\HumanResources\Departments\Department;
use App\Models\Entities\Users\User;
use App\Support\CustomerContext;
use App\Traits\CrudResponse;
use App\Traits\RoleCheckTrait;
use App\Traits\WebIndex;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class EmployeeController extends Controller
{
    use CrudResponse, RoleCheckTrait, WebIndex;

    protected Employee $employee;

    public function __construct(Employee $employee)
    {
        $this->employee = $employee;
    }

    public function view()
    {
        return $this->webRoute('app.human_resources.employee.employee_index', 'employee');
    }

    public function index(Request $request)
    {
        $q = $this->employee->query()
            ->with('department')
            ->orderBy('full_name');

        // 2) filtro técnico (usado no typeahead)
        if ($request->boolean('is_technician')) {
            $q->where('is_technician', true);
        }

        // 3) busca
        if ($term = trim($request->input('q', ''))) {
            $q->where(function ($w) use ($term) {
                $w->where('full_name', 'like', "%{$term}%")
                    ->orWhere('email', 'like', "%{$term}%")
                    ->orWhere('phone', 'like', "%{$term}%")
                    ->orWhere('document_number', 'like', "%{$term}%");
            });
        }

        if ($request->boolean('typeahead')) {
            return response()->json([
                'data' => $q->limit(20)->get(['id','full_name','hourly_rate']),
            ]);
        }

        $data = $q->paginate(20);

        return response()->json($data);
    }

    public function store(Request $request)
    {
        $ownerUserId = $this->userAuth();

        $validated = $request->validate([
            'department_id'   => ['nullable', 'uuid'],
            'full_name'       => ['required', 'string', 'max:255'],
            'email'           => ['nullable','email','max:255', Rule::unique('users', 'email')],
            'phone'           => ['nullable', 'string', 'max:30'],
            'document_number' => ['nullable', 'string', 'max:20'],
            'position'        => ['nullable', 'string', 'max:255'],
            'hourly_rate'     => ['nullable', 'numeric'],
            'is_technician'   => ['sometimes', 'boolean'],
            'is_active'       => ['sometimes', 'boolean'],
            'user_id'         => ['nullable', 'uuid'],

            // campo de UI (não salva em employees)
            'has_access'      => ['sometimes', 'boolean'],

            // senha só faz sentido quando has_access = true
            'password'        => ['nullable', 'string', 'min:6', 'confirmed'],
        ], [
            'full_name.required'   => 'Insira um nome para esse funcionário.',
            'password.confirmed'   => 'As senhas não conferem.',
        ]);

        $validated['is_technician'] = (bool)($validated['is_technician'] ?? false);
        $validated['is_active']     = (bool)($validated['is_active'] ?? true);

        // pega o toggle ANTES da transaction
        $hasAccess = (bool)($validated['has_access'] ?? false);

        return DB::transaction(function () use ($validated, $ownerUserId, $hasAccess) {

            $firstNameRaw = explode(' ', trim($validated['full_name']))[0] ?? 'User';
            $firstName = Str::ucfirst(Str::lower(Str::ascii($firstNameRaw)));

            $randomEmail = "{$firstName}@cliqis.com.br";
            $finalEmail = strtolower($validated['email'] ?? $randomEmail);

            $defaultPassword = "{$firstName}_123@";

            $passwordToUse = ($hasAccess && !empty($validated['password']))
                ? $validated['password']
                : $defaultPassword;

            $employeeUser = User::create([
                'name'     => $validated['full_name'],
                'email'    => strtolower($validated['email'] ?? $randomEmail),
                'password' => Hash::make($passwordToUse),
            ]);

            $validated['email'] = $finalEmail;

            $tenantId = CustomerContext::get();
            if (!$tenantId) {
                throw new \RuntimeException('CustomerContext não definido ao criar funcionário.');
            }

            $employeeRoleName = "{$tenantId}_employee_customer_cliqis";
            Role::firstOrCreate(['name' => $employeeRoleName, 'guard_name' => 'web']);
            $employeeUser->assignRole($employeeRoleName);

            if (!empty($validated['department_id'])) {
                $department = Department::find($validated['department_id']);
                if ($department) {
                    $slug = Str::slug($department->name, '_');
                    $departmentRoleName = "{$tenantId}_{$slug}";
                    Role::firstOrCreate(['name' => $departmentRoleName, 'guard_name' => 'web']);
                    $employeeUser->assignRole($departmentRoleName);
                }
            }

            // 🔥 LIMPA CAMPOS QUE NÃO EXISTEM EM employees
            unset($validated['password'], $validated['password_confirmation'], $validated['has_access']);

            $employeeData = $validated;
            $employeeData['user_id'] = $ownerUserId;

            $employee = $this->employee->create($employeeData);

            CustomerEmployeeUser::create([
                'employee_id' => $employee->id,
                'user_id'     => $employeeUser->id,
                'customer_sistapp_id' => $tenantId,
            ]);

            return response()->json($employee->load('department'), 201);
        });
    }

    public function show(string $id)
    {
        return $this->showMethod($this->employee->with('department')->find($id));
    }

    public function update(Request $request, string $id)
    {
        $validated = $request->validate([
            'department_id'   => ['nullable', 'uuid'],
            'full_name'       => ['sometimes', 'string', 'max:255'],
            'email'           => ['nullable', 'email', 'max:255'],
            'phone'           => ['nullable', 'string', 'max:30'],
            'document_number' => ['nullable', 'string', 'max:20'],
            'position'        => ['nullable', 'string', 'max:255'],
            'hourly_rate'     => ['nullable', 'numeric'],
            'is_technician'   => ['sometimes', 'boolean'],
            'is_active'       => ['sometimes', 'boolean'],

            // só se quiser permitir troca de senha no edit
            'password'        => ['nullable', 'string', 'min:6', 'confirmed'],
        ], [
            'password.confirmed' => 'As senhas não conferem.',
        ]);

        if (!array_key_exists('full_name', $validated)) {
            unset($validated['full_name']);
        }

        $validated['is_technician'] = (bool)($validated['is_technician'] ?? false);
        $validated['is_active']     = (bool)($validated['is_active'] ?? true);

        return DB::transaction(function () use ($validated, $id) {

            $employee = $this->employee->with('department')->findOrFail($id);

            // 1) Atualiza employees (sem campos que não existem)
            $employeeUpdate = $validated;
            unset($employeeUpdate['password'], $employeeUpdate['password_confirmation']);
            $employee->update($employeeUpdate);

            // 2) Acha o user do funcionário via pivot customer_employee_users
            $employeeUserId = CustomerEmployeeUser::where('employee_id', $employee->id)->value('user_id');

            if ($employeeUserId) {
                $user = User::find($employeeUserId);

                if ($user) {
                    $userUpdate = [];

                    // email
                    if (array_key_exists('email', $validated) && !empty($validated['email'])) {
                        $userUpdate['email'] = strtolower($validated['email']);
                    }

                    // nome do user acompanha full_name (opcional mas faz sentido)
                    if (array_key_exists('full_name', $validated) && !empty($validated['full_name'])) {
                        $userUpdate['name'] = $validated['full_name'];
                    }

                    if (!empty($userUpdate)) {
                        $user->update($userUpdate);
                    }

                    // senha
                    if (!empty($validated['password'])) {
                        $user->update(['password' => Hash::make($validated['password'])]);
                    }
                }
            }

            return response()->json($employee->fresh()->load('department'));
        });
    }

    public function destroy(string $id)
    {
        return $this->destroyMethod($this->employee->find($id));
    }
}
