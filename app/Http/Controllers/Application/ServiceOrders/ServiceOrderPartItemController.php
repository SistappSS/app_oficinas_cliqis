<?php

namespace App\Http\Controllers\Application\ServiceOrders;

use App\Http\Controllers\Controller;
use App\Models\ServiceOrderPartItem;
use App\Traits\CrudResponse;
use App\Traits\RoleCheckTrait;
use App\Traits\WebIndex;
use Illuminate\Http\Request;

class ServiceOrderPartItemController extends Controller
{
    use CrudResponse, RoleCheckTrait, WebIndex;

    protected ServiceOrderPartItem $serviceOrderPartItem;

    public function __construct(ServiceOrderPartItem $serviceOrderPartItem)
    {
        $this->serviceOrderPartItem = $serviceOrderPartItem;
    }

    public function view()
    {
        return $this->webRoute('app.service_orders.service_order_part_item.service_order_part_item_index', 'service-order-part-item');
    }

    public function index(Request $request)
    {
        $q = $this->serviceOrderPartItem->query()
            ->with(['serviceOrder', 'part'])
            ->orderByDesc('created_at');

        $data = $q->paginate(20);

        return response()->json($data);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'service_order_id' => ['required', 'uuid'],
            'part_id'          => ['nullable', 'uuid'],
            'description'      => ['required', 'string'],
            'quantity'         => ['required', 'numeric'],
            'unit_price'       => ['required', 'numeric'],
            'total'            => ['required', 'numeric'],
        ]);

        return $this->storeMethod($this->serviceOrderPartItem, $validated);
    }

    public function show(string $id)
    {
        return $this->showMethod(
            $this->serviceOrderPartItem->with(['serviceOrder', 'part'])->find($id)
        );
    }

    public function update(Request $request, string $id)
    {
        $validated = $request->validate([
            'service_order_id' => ['required', 'uuid'],
            'part_id'          => ['nullable', 'uuid'],
            'description'      => ['required', 'string'],
            'quantity'         => ['required', 'numeric'],
            'unit_price'       => ['required', 'numeric'],
            'total'            => ['required', 'numeric'],
        ]);

        return $this->updateMethod($this->serviceOrderPartItem->find($id), $validated);
    }

    public function destroy(string $id)
    {
        return $this->destroyMethod($this->serviceOrderPartItem->find($id));
    }
}
