<?php

namespace App\Http\Controllers\Application;

use App\Http\Controllers\Controller;
use App\Models\PartOrder;
use App\Models\PartOrderItem;
use App\Support\CustomerContext;
use App\Traits\RoleCheckTrait;
use App\Traits\WebIndex;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PartOrderController extends Controller
{
    use RoleCheckTrait, WebIndex;

    public function __construct(PartOrder $partOrder)
    {
        $this->partOrder = $partOrder;
    }

    public function view()
    {
        return $this->webRoute('app.part_orders.part_order_index', 'part_order');
    }

    /**
     * Index JSON (tabela + cards).
     * Item 2 a gente fecha filtro/busca/paginação direitinho.
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $tenantId = CustomerContext::get();

        $q = $this->partOrder->query()
            ->withCount('items')
            ->orderByDesc('order_date')
            ->orderByDesc('created_at');

        // TODO (Item 2): filtros por status + busca

        // ---- VISIBILIDADE POR PERMISSÃO (mesma lógica da OS) ----
        $isMaster = false;
        if ($user) {
            $login    = $user->customerLogin ?? null;
            $isMaster = (bool) optional($login)->is_master_customer;
        }

        if (! $isMaster) {
            if ($user && $user->can("{$tenantId}_visualizar_pedidos_pecas")) {
                // ok, vê tudo do tenant
            } else {
                $q->whereRaw('1=0');
            }
        }

        $data = $q->paginate(20);

        $data->getCollection()->transform(function ($o) {
            $o->status_label = $o->status_label;
            return $o;
        });

        return response()->json($data);
    }

    public function show(string $id)
    {
        $order = $this->partOrder
            ->with(['items', 'items.part'])
            ->findOrFail($id);

        return response()->json($order);
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
        $order = $this->partOrder->findOrFail($id);
        $order->delete();

        return response()->json(['success' => true]);
    }

    public function duplicate(string $id)
    {
        $original = $this->partOrder->with('items')->findOrFail($id);

        return DB::transaction(function () use ($original) {
            $copy = $original->replicate(['id','order_number','created_at','updated_at','sent_at']);
            $copy->id = (string) Str::uuid();
            $copy->status = 'draft';
            $copy->sent_at = null;
            $copy->order_date = now()->toDateString();
            $copy->order_number = CustomerContext::for($original->customer_sistapp_id, function () {
                return $this->generateNextNumber();
            });

            $copy->save();

            foreach ($original->items as $it) {
                $new = $it->replicate(['id','part_order_id','created_at','updated_at']);
                $new->id = (string) Str::uuid();
                $new->part_order_id = $copy->id;
                $new->customer_sistapp_id = $copy->customer_sistapp_id;
                $new->save();
            }

            return response()->json(['ok' => true, 'id' => $copy->id]);
        });
    }

    public function send(Request $request, string $id)
    {
        $order = $this->partOrder->with('items')->findOrFail($id);

        // por enquanto: marcar como enviado. (Item 4 a gente fecha regras)
        $order->status = 'sent';
        $order->sent_at = now();
        $order->save();

        return response()->json(['ok' => true]);
    }

    protected function saveOrder(Request $request, ?string $id = null)
    {
        $validated = $request->validate([
            'title'        => ['required', 'string', 'max:255'],
            'billing_cnpj' => ['required', 'string', 'max:20'],
            'billing_uf'   => ['required', 'string', 'size:2'],
            'order_date'   => ['nullable', 'date'],
            'status'       => ['nullable', 'string', 'max:20'],
            'icms_rate'    => ['nullable', 'numeric'],

            'items'                  => ['array'],
            'items.*.id'             => ['nullable', 'string'],
            'items.*.part_id'        => ['nullable', 'string'],
            'items.*.code'           => ['nullable', 'string', 'max:255'],
            'items.*.description'    => ['nullable', 'string', 'max:255'],
            'items.*.ncm'            => ['nullable', 'string', 'max:50'],
            'items.*.unit_price'     => ['nullable', 'numeric'],
            'items.*.ipi_rate'       => ['nullable', 'numeric'],
            'items.*.quantity'       => ['nullable', 'numeric'],
            'items.*.discount_rate'  => ['nullable', 'numeric'],
            'items.*.position'       => ['nullable', 'integer'],
        ]);

        $items = collect($validated['items'] ?? [])
            ->filter(fn($it) => $this->itemIsFilled($it))
            ->values();

        if ($items->count() === 0) {
            return response()->json(['message' => 'Adicione ao menos 1 item preenchido.'], 422);
        }

        unset($validated['items']);

        return DB::transaction(function () use ($id, $validated, $items) {

            $tenant = auth()->user()->employeeCustomerLogin->customer_sistapp_id
                ?? CustomerContext::get();

            if ($id) {
                $order = $this->partOrder->with('items')->findOrFail($id);
                if ($order->status !== 'draft') {
                    return response()->json(['message' => 'Somente rascunho pode ser editado.'], 403);
                }
            }
            else {
                $order = new PartOrder();
                $order->order_number = $this->generateNextNumber();
                $order->status = 'draft';
            }

            $order->customer_sistapp_id = $tenant;

            $order->fill($validated);

            if (! $order->icms_rate) {
                $order->icms_rate = $this->rateFromUF($order->billing_uf);
            }

            $order->save();

            // sync itens (igual tua OS: keepIds + delete)
            $keep = [];

            foreach ($items as $idx => $row) {
                $payload = $this->normalizeItemPayload($row, $order, $idx);

                if (!empty($row['id'])) {
                    $item = PartOrderItem::where('part_order_id', $order->id)
                        ->where('id', $row['id'])
                        ->first();

                    if ($item) $item->update($payload);
                    else $item = $order->items()->create($payload);
                } else {
                    $item = $order->items()->create($payload);
                }

                $keep[] = $item->id;
            }

            $order->items()->whereNotIn('id', $keep)->delete();

            // recalcula totais do pedido
            $totals = $this->calcTotals($order->items()->get()->toArray(), (float) $order->icms_rate);

            $order->items_count     = (int) $totals['count'];
            $order->subtotal        = $totals['subtotal'];
            $order->ipi_total       = $totals['ipi_total'];
            $order->discount_total  = $totals['discount_total'];
            $order->icms_total      = $totals['icms_total'];
            $order->grand_total     = $totals['grand_total'];

            $order->save();

            $order->load(['items','items.part']);

            return response()->json([
                'success' => true,
                'data' => $order,
            ]);
        });
    }

    public function generateNextNumber(): string
    {
        return DB::transaction(function () {
            $tenantId = CustomerContext::get();

            $last = $this->partOrder->newQuery()
                ->where('customer_sistapp_id', $tenantId)
                ->select('order_number')
                ->orderByDesc('order_number')
                ->lockForUpdate()
                ->value('order_number');

            if (!$last) return 'PP-0001';

            $n = (int) preg_replace('/\D+/', '', $last);
            $n = $n + 1;

            return 'PP-' . str_pad((string) $n, 4, '0', STR_PAD_LEFT);
        });
    }

    // ===== helpers =====

    protected function itemIsFilled(array $it): bool
    {
        $code = trim((string)($it['code'] ?? ''));
        $desc = trim((string)($it['description'] ?? ''));
        $price = (float)($it['unit_price'] ?? 0);
        return ($code !== '' || $desc !== '' || $price > 0);
    }

    protected function rateFromUF(?string $uf): float
    {
        $uf = strtoupper((string)$uf);
        if ($uf === 'SP') return 18.0;
        if (in_array($uf, ['MG','PR','RS','RJ','SC'], true)) return 12.0;
        return 7.0;
    }

    protected function normalizeItemPayload(array $row, PartOrder $order, int $idx): array
    {
        $unit = (float)($row['unit_price'] ?? 0);
        $qty  = (float)($row['quantity'] ?? 1);
        $ipi  = (float)($row['ipi_rate'] ?? 0);
        $disc = (float)($row['discount_rate'] ?? 0);

        $lineSubtotal = $unit * $qty;
        $lineIpiAmount = $lineSubtotal * ($ipi / 100);
        $withIpi = $lineSubtotal + $lineIpiAmount;
        $lineDiscountAmount = $withIpi * ($disc / 100);
        $lineTotal = $withIpi - $lineDiscountAmount;

        return [
            'customer_sistapp_id'   => $order->customer_sistapp_id,
            'part_id'               => $row['part_id'] ?? null,
            'code'                  => $row['code'] ?? null,
            'description'           => $row['description'] ?? null,
            'ncm'                   => $row['ncm'] ?? null,
            'unit_price'            => $unit,
            'ipi_rate'              => $ipi,
            'quantity'              => $qty,
            'discount_rate'         => $disc,
            'line_subtotal'         => $lineSubtotal,
            'line_ipi_amount'       => $lineIpiAmount,
            'line_discount_amount'  => $lineDiscountAmount,
            'line_total'            => $lineTotal,
            'position'              => (int)($row['position'] ?? $idx),
        ];
    }

    protected function calcTotals(array $items, float $icmsRate): array
    {
        $subtotal = 0; $ipiTotal = 0; $discountTotal = 0; $totalLines = 0; $count = 0;

        foreach ($items as $it) {
            $subtotal      += (float)($it['line_subtotal'] ?? 0);
            $ipiTotal      += (float)($it['line_ipi_amount'] ?? 0);
            $discountTotal += (float)($it['line_discount_amount'] ?? 0);
            $totalLines    += (float)($it['line_total'] ?? 0);
            $count++;
        }

        $icmsTotal = $subtotal * ($icmsRate / 100);
        $grand = $totalLines + $icmsTotal;

        return [
            'count' => $count,
            'subtotal' => round($subtotal, 2),
            'ipi_total' => round($ipiTotal, 2),
            'discount_total' => round($discountTotal, 2),
            'icms_total' => round($icmsTotal, 2),
            'grand_total' => round($grand, 2),
        ];
    }
}
