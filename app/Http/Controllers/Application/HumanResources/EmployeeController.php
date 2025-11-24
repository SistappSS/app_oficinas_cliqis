<?php

namespace App\Http\Controllers\Application\HumanResources;

use App\Http\Controllers\Controller;
use App\Models\HumanResources\Employees\Employee;
use App\Traits\CrudResponse;
use App\Traits\RoleCheckTrait;
use App\Traits\WebIndex;
use Carbon\Carbon;
use Illuminate\Http\Request;

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

        if ($term = trim($request->input('q', ''))) {
            $q->where(function ($w) use ($term) {
                $w->where('full_name', 'like', "%{$term}%")
                    ->orWhere('email', 'like', "%{$term}%")
                    ->orWhere('phone', 'like', "%{$term}%")
                    ->orWhere('document_number', 'like', "%{$term}%");
            });
        }

        $data = $q->paginate(20);

        return response()->json($data);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'department_id' => ['nullable', 'uuid'],
            'full_name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'document_number' => ['nullable', 'string', 'max:20'],
            'position' => ['nullable', 'string', 'max:255'],
            'hourly_rate' => ['nullable', 'numeric'],
            'is_technician' => ['sometimes', 'boolean'],
            'is_active' => ['sometimes', 'boolean'],
            'user_id' => ['nullable', 'uuid'],
        ]);

        $validated['is_technician'] = (bool)($validated['is_technician'] ?? false);
        $validated['is_active'] = (bool)($validated['is_active'] ?? true);
        $validated['user_id'] = $this->userAuth();

        return $this->storeMethod($this->employee, $validated);
    }

    public function show(string $id)
    {
        return $this->showMethod($this->employee->with('department')->find($id));
    }

    public function update(Request $request, string $id)
    {
        $validated = $request->validate([
            'department_id' => ['nullable', 'uuid'],
            'full_name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'document_number' => ['nullable', 'string', 'max:20'],
            'position' => ['nullable', 'string', 'max:255'],
            'hourly_rate' => ['nullable', 'numeric'],
            'is_technician' => ['sometimes', 'boolean'],
            'is_active' => ['sometimes', 'boolean'],
            'user_id' => ['nullable', 'uuid'],
        ]);

        $validated['is_technician'] = (bool)($validated['is_technician'] ?? false);
        $validated['is_active'] = (bool)($validated['is_active'] ?? true);

        return $this->updateMethod($this->employee->find($id), $validated);
    }

    public function destroy(string $id)
    {
        return $this->destroyMethod($this->employee->find($id));
    }
}
