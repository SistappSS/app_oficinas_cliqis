<?php

namespace App\Http\Controllers\Application\ServiceOrders;

use App\Http\Controllers\Controller;
use App\Models\HumanResources\Employees\Employee;
use App\Models\ServiceOrders\ServiceOrder;
use App\Models\ServiceOrders\ServiceOrderEquipments\ServiceOrderEquipment;
use App\Models\ServiceOrders\ServiceOrderLaborEntries\ServiceOrderLaborEntry;
use App\Models\ServiceOrders\ServiceOrderPartItems\ServiceOrderPartItem;
use App\Models\ServiceOrders\ServiceOrderServiceItems\ServiceOrderServiceItem;
use App\Support\CustomerContext;
use App\Traits\RoleCheckTrait;
use App\Traits\WebIndex;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ServiceOrderController extends Controller
{
    use RoleCheckTrait, WebIndex;

    public function __construct(ServiceOrder $serviceOrder)
    {
        $this->serviceOrder = $serviceOrder;
    }

    public function view()
    {
        return $this->webRoute('app.service_orders.service_order_index', 'service_order');
    }

    public function create(?ServiceOrder $serviceOrder = null)
    {
        if ($serviceOrder) {
            $serviceOrder->load([
                'equipments',
                'serviceItems',
                'partItems',
                'laborEntries',
                'technician',
                'secondaryCustomer',
            ]);

            $displayOrderNumber = $serviceOrder->order_number;
            $defaultTechnician  = $serviceOrder->technician;
        } else {
            $displayOrderNumber = $this->generateNextNumber();
            $defaultTechnician = Employee::where('user_id', auth()->id())->first();
        }

        return view('app.service_orders.service_order_create', [
            'serviceOrder'        => $serviceOrder,
            'displayOrderNumber'  => $displayOrderNumber,
            'defaultTechnician'  => $defaultTechnician,
        ]);
    }

    public function index(Request $request)
    {
        $user = $request->user();

        $q = $this->serviceOrder->query()
            ->with('secondaryCustomer')
            ->orderByDesc('order_date')
            ->orderByDesc('created_at');

        // ----- BUSCA TEXTO -----
        if ($term = trim($request->input('q', ''))) {
            $q->where(function ($w) use ($term) {
                $w->where('order_number', 'like', "%{$term}%")
                    ->orWhere('requester_name', 'like', "%{$term}%")
                    ->orWhere('ticket_number', 'like', "%{$term}%")
                    ->orWhereHas('secondaryCustomer', function ($q2) use ($term) {
                        $q2->where('name', 'like', "%{$term}%");
                    });
            });
        }

        // ----- FILTRO STATUS (chips da tela) -----
        if ($status = $request->input('status')) {
            $allowed = ['draft', 'pending', 'approved', 'completed', 'rejected'];
            if (in_array($status, $allowed, true)) {
                $q->where('status', $status);
            }
        }

        // ----- VISIBILIDADE POR PERMISSÃO -----
        $isMaster = false;

        if ($user) {
            // ajusta essa parte para o lugar onde você guarda o "master"
            $login    = $user->customerLogin ?? null;
            $isMaster = (bool) optional($login)->is_master_customer;
        }

        $tenantId = CustomerContext::get();

        if (! $isMaster) {
            if ($user && $user->can("{$tenantId}_aprovar_ordem_servico")) {
                // financeiro: vê todas do tenant, mas só nessas situações
                $q->whereIn('status', ['approved', 'completed', 'rejected']);

            } elseif ($user && $user->can("{$tenantId}_visualizar_ordem_servico")) {
                // técnico: só as OS criadas por ele
                // troca 'user_id' se o campo for outro
                $q->where('technician_id', $user->employeeCustomerLogin->employee_id);

            } else {
                // sem permissão: não retorna nada
                $q->whereRaw('1=0');
            }
        }

        $data = $q->paginate(20);

        return response()->json($data);
    }

    public function show(string $id)
    {
        $os = $this->serviceOrder
            ->with([
                'secondaryCustomer',
                'technician',
                'openedBy',
                'equipments',
                'serviceItems',
                'partItems',
                'laborEntries',
                'completion',
            ])
            ->findOrFail($id);

        return response()->json($os);
    }

    public function store(Request $request)
    {
        return $this->saveOrder($request);
    }

    public function update(Request $request, string $id)
    {
        return $this->saveOrder($request, $id);
    }

    public function destroy(string $id)
    {
        $os = $this->serviceOrder->findOrFail($id);
        $os->delete();

        return response()->json([
            'success' => true,
            'message' => 'Ordem de serviço excluída com sucesso.',
        ]);
    }

    // =========================================================
    // MÉTODO CENTRAL DE SALVAR (CREATE + UPDATE)
    // =========================================================
    protected function saveOrder(Request $request, ?string $id = null)
    {
        $validated = $request->validate([
            // cabeçalho
            'order_date'              => ['nullable', 'date'],
            'status'                  => ['nullable', 'string', 'max:20'],
            'secondary_customer_id'   => ['nullable', 'string'],
            'technician_id'           => ['nullable', 'string'],
            'opened_by_employee_id'   => ['nullable', 'string'],
            'requester_name'          => ['nullable', 'string', 'max:255'],
            'requester_email'         => ['nullable', 'string', 'max:255'],
            'requester_phone'         => ['nullable', 'string', 'max:30'],
            'ticket_number'           => ['nullable', 'string', 'max:255'],
            'address_line1'           => ['nullable', 'string', 'max:255'],
            'city'                    => ['nullable', 'string', 'max:255'],
            'state'                   => ['nullable', 'string', 'max:2'],
            'zip_code'                => ['nullable', 'string', 'max:15'],
            'payment_condition'       => ['nullable', 'string', 'max:255'],
            'notes'                   => ['nullable', 'string'],

            'labor_hour_value'        => ['nullable', 'numeric'],
            'discount_amount'         => ['nullable', 'numeric'],
            'addition_amount'         => ['nullable', 'numeric'],

            'equipments'              => ['array'],
            'equipments.*.id'         => ['nullable', 'string'],
            'equipments.*.equipment_id'         => ['nullable', 'string'],
            'equipments.*.equipment_description'=> ['nullable', 'string'],
            'equipments.*.serial_number'        => ['nullable', 'string'],
            'equipments.*.location'             => ['nullable', 'string'],
            'equipments.*.notes'                => ['nullable', 'string'],

            'services'                => ['array'],
            'services.*.id'           => ['nullable', 'string'],
            'services.*.service_item_id' => ['nullable', 'string'],
            'services.*.service_type_id' => ['nullable', 'string'],
            'services.*.description'  => ['nullable', 'string'],
            'services.*.quantity'     => ['nullable', 'numeric'],
            'services.*.unit_price'   => ['nullable', 'numeric'],

            'parts'                   => ['array'],
            'parts.*.id'              => ['nullable', 'string'],
            'parts.*.part_id'         => ['nullable', 'string'],
            'parts.*.description'     => ['nullable', 'string'],
            'parts.*.quantity'        => ['nullable', 'numeric'],
            'parts.*.unit_price'      => ['nullable', 'numeric'],

            'labor_entries'               => ['array'],
            'labor_entries.*.id'          => ['nullable', 'string'],
            'labor_entries.*.employee_id' => ['nullable', 'string'],
            'labor_entries.*.started_at'  => ['nullable', 'date'],
            'labor_entries.*.ended_at'    => ['nullable', 'date'],
            'labor_entries.*.hours'       => ['nullable', 'numeric'],
            'labor_entries.*.rate'        => ['nullable', 'numeric'],
        ]);

        $status  = $validated['status'] ?? 'draft';
        $equip   = collect($validated['equipments']      ?? []);
        $services= collect($validated['services']        ?? []);
        $parts   = collect($validated['parts']           ?? []);
        $labors  = collect($validated['labor_entries']   ?? []);

        $laborHourValue  = (float)($validated['labor_hour_value'] ?? 0);
        $discountAmount  = (float)($validated['discount_amount'] ?? 0);
        $additionAmount  = (float)($validated['addition_amount'] ?? 0);

        unset(
            $validated['equipments'],
            $validated['services'],
            $validated['parts'],
            $validated['labor_entries']
        );

        return DB::transaction(function () use (
            $id,
            $validated,
            $status,
            $equip,
            $services,
            $parts,
            $labors,
            $laborHourValue,
            $discountAmount,
            $additionAmount
        ) {
            if ($id) {
                $os = \App\Models\ServiceOrders\ServiceOrder::findOrFail($id);
            } else {
                $os = new ServiceOrder();
                $os->order_number = $this->generateNextNumber();
            }

            $os->customer_sistapp_id = auth()->user()->employeeCustomerLogin->customer_sistapp_id ?? $this->customerSistappID();

            $os->fill($validated);
            $os->status            = $status;
            $os->labor_hour_value  = $laborHourValue;

            $os->save();

            // -------------------
            // EQUIPAMENTOS
            // -------------------
            $keepEquipIds = [];

            foreach ($equip as $row) {
                if (
                    empty($row['equipment_id']) &&
                    empty($row['equipment_description']) &&
                    empty($row['serial_number']) &&
                    empty($row['location']) &&
                    empty($row['notes'])
                ) {
                    continue;
                }

                $itemData = [
                    'equipment_id'          => $row['equipment_id']         ?? null,
                    'equipment_description' => $row['equipment_description']?? null,
                    'serial_number'         => $row['serial_number']       ?? null,
                    'location'              => $row['location']            ?? null,
                    'notes'                 => $row['notes']               ?? null,
                ];

                if (!empty($row['id'])) {
                    $item = ServiceOrderEquipment::where('service_order_id', $os->id)
                        ->where('id', $row['id'])
                        ->first();

                    if ($item) {
                        $item->update($itemData);
                    } else {
                        $item = $os->equipments()->create($itemData);
                    }
                } else {
                    $item = $os->equipments()->create($itemData);
                }

                $keepEquipIds[] = $item->id;
            }

            $os->equipments()->whereNotIn('id', $keepEquipIds)->delete();

            // -------------------
            // SERVIÇOS
            // -------------------
            $keepServiceIds   = [];
            $servicesSubtotal = 0;

            foreach ($services as $row) {
                if (empty($row['description']) && empty($row['service_item_id'])) {
                    continue;
                }

                $qty  = (float)($row['quantity']   ?? 1);
                $unit = (float)($row['unit_price'] ?? 0);
                $tot  = $qty * $unit;

                $itemData = [
                    'service_item_id' => $row['service_item_id'] ?? null,
                    'service_type_id' => $row['service_type_id'] ?? null,
                    'description'     => $row['description']    ?? '',
                    'quantity'        => $qty,
                    'unit_price'      => $unit,
                    'total'           => $tot,
                ];

                if (!empty($row['id'])) {
                    $item = ServiceOrderServiceItem::where('service_order_id', $os->id)
                        ->where('id', $row['id'])
                        ->first();

                    if ($item) {
                        $item->update($itemData);
                    } else {
                        $item = $os->serviceItems()->create($itemData);
                    }
                } else {
                    $item = $os->serviceItems()->create($itemData);
                }

                $keepServiceIds[]   = $item->id;
                $servicesSubtotal  += $tot;
            }

            $os->serviceItems()->whereNotIn('id', $keepServiceIds)->delete();

            // -------------------
            // PEÇAS
            // -------------------
            $keepPartIds   = [];
            $partsSubtotal = 0;

            foreach ($parts as $row) {
                if (empty($row['description']) && empty($row['part_id'])) {
                    continue;
                }

                $qty  = (float)($row['quantity']   ?? 1);
                $unit = (float)($row['unit_price'] ?? 0);
                $tot  = $qty * $unit;

                $itemData = [
                    'part_id'     => $row['part_id']    ?? null,
                    'description' => $row['description']?? '',
                    'quantity'    => $qty,
                    'unit_price'  => $unit,
                    'total'       => $tot,
                ];

                if (!empty($row['id'])) {
                    $item = ServiceOrderPartItem::where('service_order_id', $os->id)
                        ->where('id', $row['id'])
                        ->first();

                    if ($item) {
                        $item->update($itemData);
                    } else {
                        $item = $os->partItems()->create($itemData);
                    }
                } else {
                    $item = $os->partItems()->create($itemData);
                }

                $keepPartIds[]  = $item->id;
                $partsSubtotal += $tot;
            }

            $os->partItems()->whereNotIn('id', $keepPartIds)->delete();

            // -------------------
            // HORAS / MÃO DE OBRA
            // -------------------
            $keepLaborIds     = [];
            $laborTotalHours  = 0;
            $laborTotalAmount = 0;

            foreach ($labors as $row) {
                if (empty($row['started_at']) && empty($row['ended_at']) && empty($row['hours'])) {
                    continue;
                }

                $hours = (float)($row['hours'] ?? 0);
                $rate  = (float)($row['rate']  ?? $laborHourValue);
                $tot   = $hours * $rate;

                $entryData = [
                    'employee_id' => $row['employee_id'] ?? null,
                    'started_at'  => $row['started_at']  ?? null,
                    'ended_at'    => $row['ended_at']    ?? null,
                    'hours'       => $hours,
                    'rate'        => $rate,
                    'total'       => $tot,
                ];

                if (!empty($row['id'])) {
                    $entry = ServiceOrderLaborEntry::where('service_order_id', $os->id)
                        ->where('id', $row['id'])
                        ->first();

                    if ($entry) {
                        $entry->update($entryData);
                    } else {
                        $entry = $os->laborEntries()->create($entryData);
                    }
                } else {
                    $entry = $os->laborEntries()->create($entryData);
                }

                $keepLaborIds[]     = $entry->id;
                $laborTotalHours   += $hours;
                $laborTotalAmount  += $tot;
            }

            $os->laborEntries()->whereNotIn('id', $keepLaborIds)->delete();

            // -------------------
            // TOTAIS GERAIS
            // -------------------
            $os->services_subtotal  = $servicesSubtotal;
            $os->parts_subtotal     = $partsSubtotal;
            $os->labor_total_hours  = $laborTotalHours;
            $os->labor_total_amount = $laborTotalAmount;
            $os->discount_amount    = $discountAmount;
            $os->addition_amount    = $additionAmount;

            $os->grand_total = $servicesSubtotal + $partsSubtotal + $laborTotalAmount
                - $discountAmount + $additionAmount;

            $os->save();

            $os->load([
                'equipments',
                'serviceItems',
                'partItems',
                'laborEntries',
            ]);

            return response()->json([
                'success' => true,
                'message' => $id
                    ? 'Ordem de serviço atualizada com sucesso.'
                    : 'Ordem de serviço criada com sucesso.',
                'data'    => $os,
            ]);
        });
    }
    protected function generateNextNumber(): string
    {
        $last = ServiceOrder::orderByDesc('created_at')->value('order_number');

        if (!$last) {
            return '000001';
        }

        $int = (int) preg_replace('/\D/', '', $last);
        $next = $int + 1;

        return str_pad((string) $next, 6, '0', STR_PAD_LEFT);
    }
}
