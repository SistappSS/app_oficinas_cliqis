<?php

namespace App\Http\Controllers\Application\ServiceOrders;

use App\Http\Controllers\Controller;
use App\Models\ServiceOrderServiceItem;
use App\Traits\CrudResponse;
use App\Traits\RoleCheckTrait;
use App\Traits\WebIndex;
use Illuminate\Http\Request;

class ServiceOrderServiceItemController extends Controller
{
    use CrudResponse, RoleCheckTrait, WebIndex;

    protected ServiceOrderServiceItem $serviceOrderServiceItem;

    public function __construct(ServiceOrderServiceItem $serviceOrderServiceItem)
    {
        $this->serviceOrderServiceItem = $serviceOrderServiceItem;
    }

    public function view()
    {
        return $this->webRoute('app.service_orders.service_order_service_item.service_order_service_item_index', 'service-order-service-item');
    }

    public function index(Request $request)
    {
        $q = $this->serviceOrderServiceItem->query()
            ->with(['serviceOrder', 'serviceItem', 'serviceType'])
            ->orderByDesc('created_at');

        $data = $q->paginate(20);

        return response()->json($data);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'service_order_id' => ['required', 'uuid'],
            'service_item_id'  => ['nullable', 'uuid'],
            'service_type_id'  => ['nullable', 'uuid'],
            'description'      => ['required', 'string'],
            'quantity'         => ['required', 'numeric'],
            'unit_price'       => ['required', 'numeric'],
            'total'            => ['required', 'numeric'],
        ]);

        return $this->storeMethod($this->serviceOrderServiceItem, $validated);
    }

    public function show(string $id)
    {
        return $this->showMethod(
            $this->serviceOrderServiceItem->with(['serviceOrder', 'serviceItem', 'serviceType'])->find($id)
        );
    }

    public function update(Request $request, string $id)
    {
        $validated = $request->validate([
            'service_order_id' => ['required', 'uuid'],
            'service_item_id'  => ['nullable', 'uuid'],
            'service_type_id'  => ['nullable', 'uuid'],
            'description'      => ['required', 'string'],
            'quantity'         => ['required', 'numeric'],
            'unit_price'       => ['required', 'numeric'],
            'total'            => ['required', 'numeric'],
        ]);

        return $this->updateMethod($this->serviceOrderServiceItem->find($id), $validated);
    }

    public function destroy(string $id)
    {
        return $this->destroyMethod($this->serviceOrderServiceItem->find($id));
    }
}
