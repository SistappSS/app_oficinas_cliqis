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

        // 1) só ativos (opcional mas recomendado)
        if ($request->boolean('only_active', true)) {
            $q->where('is_active', true);
        }

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
            'email'           => [
                'nullable',
                'email',
                'max:255',
                Rule::unique('users', 'email'),
            ],
            'phone'           => ['nullable', 'string', 'max:30'],
            'document_number' => ['nullable', 'string', 'max:20'],
            'position'        => ['nullable', 'string', 'max:255'],
            'hourly_rate'     => ['nullable', 'numeric'],
            'is_technician'   => ['sometimes', 'boolean'],
            'is_active'       => ['sometimes', 'boolean'],
            'user_id'         => ['nullable', 'uuid'],
        ], [
            'full_name.required' => 'Insira um nome para esse funcionário.',
        ]);

        $validated['is_technician'] = (bool)($validated['is_technician'] ?? false);
        $validated['is_active']     = (bool)($validated['is_active'] ?? true);

        return DB::transaction(function () use ($validated, $ownerUserId) {
            $firstName = explode(' ', $validated['full_name'])[0];
            $randomPassword = ucfirst($firstName) . '@123';
            $randomEmail = "{$firstName}@cliqis.com.br";

            $employeeUser = User::create([
                'name'     => $validated['full_name'],
                'email'    => $validated['email']  ?? $randomEmail,
                'password' => Hash::make($randomPassword),
            ]);

            $tenantId = CustomerContext::get();

            if (!$tenantId) {
                throw new \RuntimeException('CustomerContext não definido ao criar funcionário.');
            }

            $employeeRoleName = "{$tenantId}_employee_customer_cliqis";

            $employeeCustomerRole = Role::firstOrCreate([
                'name'       => $employeeRoleName,
                'guard_name' => 'web',
            ]);

            $employeeUser->assignRole($employeeRoleName);

            if (!empty($validated['department_id'])) {
                $department = Department::find($validated['department_id']);

                if ($department) {
                    $slug               = Str::slug($department->name, '_');
                    $departmentRoleName = "{$tenantId}_{$slug}";

                    Role::firstOrCreate([
                        'name'       => $departmentRoleName,
                        'guard_name' => 'web',
                    ]);

                    $employeeUser->assignRole($departmentRoleName);
                }
            }

            $employeeData            = $validated;
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
            'full_name' => ['sometimes', 'string', 'max:255'],
            'email'           => ['nullable', 'email', 'max:255'],
            'phone'           => ['nullable', 'string', 'max:30'],
            'document_number' => ['nullable', 'string', 'max:20'],
            'position'        => ['nullable', 'string', 'max:255'],
            'hourly_rate'     => ['nullable', 'numeric'],
            'is_technician'   => ['sometimes', 'boolean'],
            'is_active'       => ['sometimes', 'boolean'],
            'user_id'         => ['nullable', 'uuid'],
        ]);

        if (!array_key_exists('full_name', $validated)) {
            unset($validated['full_name']);
        }

        $validated['is_technician'] = (bool)($validated['is_technician'] ?? false);
        $validated['is_active']     = (bool)($validated['is_active'] ?? true);

        return $this->updateMethod($this->employee->find($id), $validated);
    }

    public function destroy(string $id)
    {
        return $this->destroyMethod($this->employee->find($id));
    }
}
