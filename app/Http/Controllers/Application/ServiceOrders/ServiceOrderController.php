<?php

namespace App\Http\Controllers\Application\ServiceOrders;

use App\Http\Controllers\Controller;
use App\Models\ServiceOrder;
use App\Traits\CrudResponse;
use App\Traits\RoleCheckTrait;
use App\Traits\WebIndex;
use Illuminate\Http\Request;

class ServiceOrderController extends Controller
{
    use CrudResponse, RoleCheckTrait, WebIndex;

    protected ServiceOrder $serviceOrder;

    public function __construct(ServiceOrder $serviceOrder)
    {
        $this->serviceOrder = $serviceOrder;
    }

    public function view()
    {
        return $this->webRoute('app.service_orders.service_order.service_order_index', 'service-order');
    }

    public function index(Request $request)
    {
        $q = $this->serviceOrder->query()
            ->with(['secondaryCustomer', 'technician'])
            ->orderByDesc('order_date');

        if ($term = trim($request->input('q', ''))) {
            $q->where(function ($w) use ($term) {
                $w->where('order_number', 'like', "%{$term}%")
                    ->orWhere('status', 'like', "%{$term}%")
                    ->orWhere('requester_name', 'like', "%{$term}%")
                    ->orWhere('ticket_number', 'like', "%{$term}%");
            });
        }

        $data = $q->paginate(20);

        return response()->json($data);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'order_number'           => ['required', 'string', 'max:255'],
            'order_date'             => ['nullable', 'date'],
            'status'                 => ['required', 'string', 'max:20'],
            'secondary_customer_id'  => ['nullable', 'uuid'],
            'technician_id'          => ['nullable', 'uuid'],
            'opened_by_employee_id'  => ['nullable', 'uuid'],

            'requester_name'         => ['nullable', 'string', 'max:255'],
            'requester_email'        => ['nullable', 'string', 'max:255'],
            'requester_phone'        => ['nullable', 'string', 'max:30'],
            'ticket_number'          => ['nullable', 'string', 'max:255'],

            'address_line1'          => ['nullable', 'string'],
            'address_line2'          => ['nullable', 'string'],
            'city'                   => ['nullable', 'string', 'max:255'],
            'state'                  => ['nullable', 'string', 'max:2'],
            'zip_code'               => ['nullable', 'string', 'max:15'],

            'labor_hour_value'       => ['nullable', 'numeric'],
            'labor_total_hours'      => ['nullable', 'numeric'],
            'labor_total_amount'     => ['nullable', 'numeric'],

            'payment_condition'      => ['nullable', 'string'],
            'notes'                  => ['nullable', 'string'],

            'services_subtotal'      => ['nullable', 'numeric'],
            'parts_subtotal'         => ['nullable', 'numeric'],
            'discount_amount'        => ['nullable', 'numeric'],
            'addition_amount'        => ['nullable', 'numeric'],
            'grand_total'            => ['nullable', 'numeric'],
        ]);

        return $this->storeMethod($this->serviceOrder, $validated);
    }

    public function show(string $id)
    {
        return $this->showMethod(
            $this->serviceOrder->with([
                'secondaryCustomer','technician',
                'serviceItems','partItems','laborEntries','equipments'
            ])->find($id)
        );
    }

    public function update(Request $request, string $id)
    {
        $validated = $request->validate([
            'order_number'           => ['required', 'string', 'max:255'],
            'order_date'             => ['nullable', 'date'],
            'status'                 => ['required', 'string', 'max:20'],
            'secondary_customer_id'  => ['nullable', 'uuid'],
            'technician_id'          => ['nullable', 'uuid'],
            'opened_by_employee_id'  => ['nullable', 'uuid'],

            'requester_name'         => ['nullable', 'string', 'max:255'],
            'requester_email'        => ['nullable', 'string', 'max:255'],
            'requester_phone'        => ['nullable', 'string', 'max:30'],
            'ticket_number'          => ['nullable', 'string', 'max:255'],

            'address_line1'          => ['nullable', 'string'],
            'address_line2'          => ['nullable', 'string'],
            'city'                   => ['nullable', 'string', 'max:255'],
            'state'                  => ['nullable', 'string', 'max:2'],
            'zip_code'               => ['nullable', 'string', 'max:15'],

            'labor_hour_value'       => ['nullable', 'numeric'],
            'labor_total_hours'      => ['nullable', 'numeric'],
            'labor_total_amount'     => ['nullable', 'numeric'],

            'payment_condition'      => ['nullable', 'string'],
            'notes'                  => ['nullable', 'string'],

            'services_subtotal'      => ['nullable', 'numeric'],
            'parts_subtotal'         => ['nullable', 'numeric'],
            'discount_amount'        => ['nullable', 'numeric'],
            'addition_amount'        => ['nullable', 'numeric'],
            'grand_total'            => ['nullable', 'numeric'],
        ]);

        return $this->updateMethod($this->serviceOrder->find($id), $validated);
    }

    public function destroy(string $id)
    {
        return $this->destroyMethod($this->serviceOrder->find($id));
    }
}
