<?php

namespace App\Http\Controllers\Application\ServiceOrders;

use App\Http\Controllers\Controller;
use App\Models\ServiceOrderEquipment;
use App\Traits\CrudResponse;
use App\Traits\RoleCheckTrait;
use App\Traits\WebIndex;
use Illuminate\Http\Request;

class ServiceOrderEquipmentController extends Controller
{
    use CrudResponse, RoleCheckTrait, WebIndex;

    protected ServiceOrderEquipment $serviceOrderEquipment;

    public function __construct(ServiceOrderEquipment $serviceOrderEquipment)
    {
        $this->serviceOrderEquipment = $serviceOrderEquipment;
    }

    public function view()
    {
        return $this->webRoute('app.service_orders.service_order_equipment.service_order_equipment_index', 'service-order-equipment');
    }

    public function index(Request $request)
    {
        $q = $this->serviceOrderEquipment->query()
            ->with(['serviceOrder', 'equipment'])
            ->orderByDesc('created_at');

        $data = $q->paginate(20);

        return response()->json($data);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'service_order_id'      => ['required', 'uuid'],
            'equipment_id'          => ['nullable', 'uuid'],
            'equipment_description' => ['nullable', 'string'],
            'serial_number'         => ['nullable', 'string', 'max:255'],
            'location'              => ['nullable', 'string'],
            'notes'                 => ['nullable', 'string'],
        ]);

        return $this->storeMethod($this->serviceOrderEquipment, $validated);
    }

    public function show(string $id)
    {
        return $this->showMethod(
            $this->serviceOrderEquipment->with(['serviceOrder', 'equipment'])->find($id)
        );
    }

    public function update(Request $request, string $id)
    {
        $validated = $request->validate([
            'service_order_id'      => ['required', 'uuid'],
            'equipment_id'          => ['nullable', 'uuid'],
            'equipment_description' => ['nullable', 'string'],
            'serial_number'         => ['nullable', 'string', 'max:255'],
            'location'              => ['nullable', 'string'],
            'notes'                 => ['nullable', 'string'],
        ]);

        return $this->updateMethod($this->serviceOrderEquipment->find($id), $validated);
    }

    public function destroy(string $id)
    {
        return $this->destroyMethod($this->serviceOrderEquipment->find($id));
    }
}
