<?php

namespace App\Http\Controllers\Application\HumanResources;

use App\Http\Controllers\Controller;
use App\Models\EmployeeBenefit;
use App\Traits\CrudResponse;
use App\Traits\RoleCheckTrait;
use App\Traits\WebIndex;
use Illuminate\Http\Request;

class EmployeeBenefitController extends Controller
{
    use CrudResponse, RoleCheckTrait, WebIndex;

    protected EmployeeBenefit $employeeBenefit;

    public function __construct(EmployeeBenefit $employeeBenefit)
    {
        $this->employeeBenefit = $employeeBenefit;
    }

    public function view()
    {
        return $this->webRoute('app.human_resources.employee_benefit.employee_benefit_index', 'employee-benefit');
    }

    public function index(Request $request)
    {
        $q = $this->employeeBenefit->query()
            ->with(['employee', 'benefit'])
            ->orderByDesc('created_at');

        if ($term = trim($request->input('q', ''))) {
            $q->whereHas('employee', function ($w) use ($term) {
                $w->where('full_name', 'like', "%{$term}%");
            })->orWhereHas('benefit', function ($w) use ($term) {
                $w->where('name', 'like', "%{$term}%");
            });
        }

        $data = $q->paginate(20);

        return response()->json($data);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'employee_id' => ['required', 'uuid'],
            'benefit_id'  => ['required', 'uuid'],
            'value'       => ['nullable', 'numeric'],
            'notes'       => ['nullable', 'string'],
        ]);

        return $this->storeMethod($this->employeeBenefit, $validated);
    }

    public function show(string $id)
    {
        return $this->showMethod($this->employeeBenefit->with(['employee', 'benefit'])->find($id));
    }

    public function update(Request $request, string $id)
    {
        $validated = $request->validate([
            'employee_id' => ['required', 'uuid'],
            'benefit_id'  => ['required', 'uuid'],
            'value'       => ['nullable', 'numeric'],
            'notes'       => ['nullable', 'string'],
        ]);

        return $this->updateMethod($this->employeeBenefit->find($id), $validated);
    }

    public function destroy(string $id)
    {
        return $this->destroyMethod($this->employeeBenefit->find($id));
    }
}
