<?php

namespace App\Http\Controllers\Application\ServiceOrders;

use App\Http\Controllers\Controller;
use App\Models\CompletedServiceOrder;
use App\Traits\CrudResponse;
use App\Traits\RoleCheckTrait;
use App\Traits\WebIndex;
use Illuminate\Http\Request;

class CompletedServiceOrderController extends Controller
{
    use CrudResponse, RoleCheckTrait, WebIndex;

    protected CompletedServiceOrder $completedServiceOrder;

    public function __construct(CompletedServiceOrder $completedServiceOrder)
    {
        $this->completedServiceOrder = $completedServiceOrder;
    }

    public function view()
    {
        return $this->webRoute('app.service_orders.completed_service_order.completed_service_order_index', 'completed-service-order');
    }

    public function index(Request $request)
    {
        $q = $this->completedServiceOrder->query()
            ->with(['serviceOrder', 'technician'])
            ->orderByDesc('completed_at');

        if ($term = trim($request->input('q', ''))) {
            $q->where(function ($w) use ($term) {
                $w->where('client_name', 'like', "%{$term}%")
                    ->orWhere('client_email', 'like', "%{$term}%");
            });
        }

        $data = $q->paginate(20);

        return response()->json($data);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'service_order_id'          => ['required', 'uuid'],
            'client_name'               => ['nullable', 'string', 'max:255'],
            'client_email'              => ['nullable', 'string', 'max:255'],
            'client_signature_path'     => ['nullable', 'string'],
            'client_signed_at'          => ['nullable', 'date'],
            'technician_id'             => ['nullable', 'uuid'],
            'technician_signature_path' => ['nullable', 'string'],
            'technician_signed_at'      => ['nullable', 'date'],
            'completed_at'              => ['nullable', 'date'],
        ]);

        return $this->storeMethod($this->completedServiceOrder, $validated);
    }

    public function show(string $id)
    {
        return $this->showMethod(
            $this->completedServiceOrder->with(['serviceOrder', 'technician'])->find($id)
        );
    }

    public function update(Request $request, string $id)
    {
        $validated = $request->validate([
            'service_order_id'          => ['required', 'uuid'],
            'client_name'               => ['nullable', 'string', 'max:255'],
            'client_email'              => ['nullable', 'string', 'max:255'],
            'client_signature_path'     => ['nullable', 'string'],
            'client_signed_at'          => ['nullable', 'date'],
            'technician_id'             => ['nullable', 'uuid'],
            'technician_signature_path' => ['nullable', 'string'],
            'technician_signed_at'      => ['nullable', 'date'],
            'completed_at'              => ['nullable', 'date'],
        ]);

        return $this->updateMethod($this->completedServiceOrder->find($id), $validated);
    }

    public function destroy(string $id)
    {
        return $this->destroyMethod($this->completedServiceOrder->find($id));
    }
}
