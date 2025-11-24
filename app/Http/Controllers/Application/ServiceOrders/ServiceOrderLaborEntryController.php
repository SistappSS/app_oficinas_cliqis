<?php

namespace App\Http\Controllers\Application\ServiceOrders;

use App\Http\Controllers\Controller;
use App\Models\ServiceOrderLaborEntry;
use App\Traits\CrudResponse;
use App\Traits\RoleCheckTrait;
use App\Traits\WebIndex;
use Illuminate\Http\Request;

class ServiceOrderLaborEntryController extends Controller
{
    use CrudResponse, RoleCheckTrait, WebIndex;

    protected ServiceOrderLaborEntry $serviceOrderLaborEntry;

    public function __construct(ServiceOrderLaborEntry $serviceOrderLaborEntry)
    {
        $this->serviceOrderLaborEntry = $serviceOrderLaborEntry;
    }

    public function view()
    {
        return $this->webRoute('app.service_orders.service_order_labor_entry.service_order_labor_entry_index', 'service-order-labor-entry');
    }

    public function index(Request $request)
    {
        $q = $this->serviceOrderLaborEntry->query()
            ->with(['serviceOrder', 'employee'])
            ->orderByDesc('started_at');

        $data = $q->paginate(20);

        return response()->json($data);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'service_order_id' => ['required', 'uuid'],
            'employee_id'      => ['nullable', 'uuid'],
            'started_at'       => ['nullable', 'date'],
            'ended_at'         => ['nullable', 'date'],
            'hours'            => ['nullable', 'numeric'],
            'rate'             => ['nullable', 'numeric'],
            'total'            => ['nullable', 'numeric'],
        ]);

        return $this->storeMethod($this->serviceOrderLaborEntry, $validated);
    }

    public function show(string $id)
    {
        return $this->showMethod(
            $this->serviceOrderLaborEntry->with(['serviceOrder', 'employee'])->find($id)
        );
    }

    public function update(Request $request, string $id)
    {
        $validated = $request->validate([
            'service_order_id' => ['required', 'uuid'],
            'employee_id'      => ['nullable', 'uuid'],
            'started_at'       => ['nullable', 'date'],
            'ended_at'         => ['nullable', 'date'],
            'hours'            => ['nullable', 'numeric'],
            'rate'             => ['nullable', 'numeric'],
            'total'            => ['nullable', 'numeric'],
        ]);

        return $this->updateMethod($this->serviceOrderLaborEntry->find($id), $validated);
    }

    public function destroy(string $id)
    {
        return $this->destroyMethod($this->serviceOrderLaborEntry->find($id));
    }
}
