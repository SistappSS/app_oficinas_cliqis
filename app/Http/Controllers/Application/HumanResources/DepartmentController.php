<?php

namespace App\Http\Controllers\Application\HumanResources;

use App\Http\Controllers\Controller;
use App\Models\Authenticate\Permissions\Role;
use App\Models\HumanResources\Departments\Department;
use App\Support\CustomerContext;
use App\Traits\CrudResponse;
use App\Traits\RoleCheckTrait;
use App\Traits\WebIndex;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class DepartmentController extends Controller
{
    use CrudResponse, RoleCheckTrait, WebIndex;

    protected Department $department;

    public function __construct(Department $department)
    {
        $this->department = $department;
    }

    public function view()
    {
        return $this->webRoute('app.human_resources.department.department_index', 'department');
    }

    public function index(Request $request)
    {
        $q = $this->department->query()->orderBy('name');

        if ($term = trim($request->input('q', ''))) {
            $q->where(function ($w) use ($term) {
                $w->where('name', 'like', "%{$term}%")
                    ->orWhere('description', 'like', "%{$term}%");
            });
        }

        $data = $q->paginate(20);

        return response()->json($data);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'        => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
        ]);

        $department = $this->department->create($validated);

        $tenantId = CustomerContext::get();

        if (!$tenantId) {
            throw new \RuntimeException('CustomerContext não definido ao criar departamento.');
        }

        $slug     = Str::slug($department->name, '_');
        $roleName = "{$tenantId}_{$slug}";

        Role::firstOrCreate([
            'name'       => $roleName,
            'guard_name' => 'web',
        ]);

        return response()->json([
            'status' => 'sucesso',
            'message' => '<i class="fa-solid fa-check"></i> Inserção de dados efetuada com sucesso!',
            'data' => $department
        ], 200);
    }

    public function show(string $id)
    {
        return $this->showMethod($this->department->find($id));
    }

    public function update(Request $request, string $id)
    {
        $validated = $request->validate([
            'name'        => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
        ]);

        return $this->updateMethod($this->department->find($id), $validated);
    }

    public function destroy(string $id)
    {
        return $this->destroyMethod($this->department->find($id));
    }
}
