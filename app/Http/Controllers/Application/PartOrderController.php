<?php

namespace App\Http\Controllers\Application;

use App\Http\Controllers\Controller;
use App\Mail\PartOrders\PartOrderToSupplierMail;
use App\Models\PartOrder;
use App\Models\PartOrderItem;
use App\Models\PartOrderSetting;
use App\Support\CustomerContext;
use App\Traits\RoleCheckTrait;
use App\Traits\WebIndex;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
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

    public function index(Request $request)
    {
        $user = $request->user();
        $tenantId = CustomerContext::get();

        $q = $this->partOrder->query()
            ->where('customer_sistapp_id', $tenantId) // ✅ SEMPRE
            ->with(['supplier:id,name,email'])
            ->withCount('items')
            ->orderByDesc('order_date')
            ->orderByDesc('created_at');

        // ---- VISIBILIDADE POR PERMISSÃO (mesma lógica da OS) ----
        $isMaster = false;
        if ($user) {
            $login    = $user->customerLogin ?? null;
            $isMaster = (bool) optional($login)->is_master_customer;
        }

        if (! $isMaster) {
            if ($user && $user->can("{$tenantId}_visualizar_pedidos_pecas")) {
                // ok
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
        $order = $this->findOrderTenantOrFail($id);
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
        $order = $this->findOrderTenantOrFail($id);
        $order->delete();
        return response()->json(['success' => true]);
    }

    public function duplicate(string $id)
    {
        $original = $this->findOrderTenantOrFail($id);

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

    private function isValidEmail(?string $email): bool
    {
        $email = trim((string) $email);
        return $email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL);
    }

    public function send(Request $request, string $id)
    {
        $order = $this->partOrder
            ->with(['supplier', 'items', 'items.part'])
            ->findOrFail($id);

        if ($order->status !== 'draft') {
            return response()->json(['message' => 'Somente rascunho pode ser enviado.'], 403);
        }

        $supplierEmail = trim((string) optional($order->supplier)->email);

        if (! $this->isValidEmail($supplierEmail)) {
            return response()->json(['message' => 'E-mail do fornecedor ausente ou inválido.'], 422);
        }

        $tenantId = $order->customer_sistapp_id ?? CustomerContext::get();

        $settings = PartOrderSetting::query()
            ->where('customer_sistapp_id', $tenantId)
            ->first();

        $subjectTpl = (string)($settings?->email_subject_tpl ?: 'Pedido de peças {{partOrderNumber}}');
        $bodyTpl    = (string)($settings?->email_body_tpl ?: "Olá {{supplierName}},\n\nSegue o pedido {{partOrderNumber}} do dia {{orderDate}}.\nItens: {{itemsCount}}\nTotal: {{total}}\n\nObrigado.");

        [$subjectFinal, $bodyFinal] = $this->applyMailVars($subjectTpl, $bodyTpl, $order);

        $pdfContent = Pdf::loadView('layouts.templates.pdf.part_order', ['order' => $order])
            ->setPaper('a4')
            ->output();

        // envia
        Mail::to($supplierEmail)->send(
            new PartOrderToSupplierMail($order, $subjectFinal, $bodyFinal, $pdfContent)
        );

        // ✅ snapshot + status
        $order->supplier_email_used = $supplierEmail;
        $order->email_subject_used  = $subjectFinal;
        $order->email_body_used     = $bodyFinal;

        $order->status  = 'pending';
        $order->sent_at = now();
        $order->save();

        return response()->json(['ok' => true]);
    }

    public function resend(Request $request, string $id)
    {
        $order = $this->partOrder
            ->with(['supplier', 'items', 'items.part'])
            ->findOrFail($id);

        if ($order->status === 'draft') {
            return response()->json(['message' => 'Rascunho: use Enviar.'], 422);
        }

        $to = trim((string) ($order->supplier_email_used ?: optional($order->supplier)->email));

        if (! $this->isValidEmail($to)) {
            return response()->json(['message' => 'E-mail do fornecedor ausente ou inválido.'], 422);
        }

        // usa snapshot se existir; se não, cai no template atual
        $subject = (string) $order->email_subject_used;
        $body    = (string) $order->email_body_used;

        if ($subject === '' || $body === '') {
            $tenantId = $order->customer_sistapp_id ?? CustomerContext::get();
            $settings = PartOrderSetting::where('customer_sistapp_id', $tenantId)->first();

            $subjectTpl = (string)($settings?->email_subject_tpl ?: 'Pedido de peças {{partOrderNumber}}');
            $bodyTpl    = (string)($settings?->email_body_tpl ?: "Olá {{supplierName}},\n\nSegue o pedido {{partOrderNumber}}.\n\nObrigado.");

            [$subject, $body] = $this->applyMailVars($subjectTpl, $bodyTpl, $order);
        }

        $pdfContent = Pdf::loadView('layouts.templates.pdf.part_order', ['order' => $order])
            ->setPaper('a4')
            ->output();

        Mail::to($to)->send(new PartOrderToSupplierMail($order, $subject, $body, $pdfContent));

        // atualiza snapshot (último envio) + sent_at
        $order->supplier_email_used = $to;
        $order->email_subject_used  = $subject;
        $order->email_body_used     = $body;
        $order->sent_at = now();
        $order->save();

        return response()->json(['ok' => true]);
    }

    protected function applyMailVars(string $subject, string $body, PartOrder $order): array
    {
        $itemsCount = $order->relationLoaded('items') ? $order->items->count() : (int)($order->items_count ?? 0);

        $fmtMoney = function ($v) {
            return 'R$ ' . number_format((float)($v ?? 0), 2, ',', '.');
        };

        $vars = [
            '{partOrderNumber}' => (string) $order->order_number,
            '{supplierName}'    => (string) optional($order->supplier)->name,
            '{supplierEmail}'   => (string) optional($order->supplier)->email,
            '{orderDate}'       => $order->order_date ? $order->order_date->format('d/m/Y') : '',
            '{itemsCount}'      => (string) $itemsCount,
            '{total}'           => $fmtMoney($order->grand_total),

            '{title}'           => (string) $order->title,
            '{billingCnpj}'     => (string) $order->billing_cnpj,
            '{billingUf}'       => (string) $order->billing_uf,

            // COMPAT: {{var}}
            '{{partOrderNumber}}' => (string) $order->order_number,
            '{{supplierName}}'    => (string) optional($order->supplier)->name,
            '{{supplierEmail}}'   => (string) optional($order->supplier)->email,
            '{{orderDate}}'       => $order->order_date ? $order->order_date->format('d/m/Y') : '',
            '{{itemsCount}}'      => (string) $itemsCount,
            '{{total}}'           => $fmtMoney($order->grand_total),

            '{{order_number}}'  => (string) $order->order_number,
            '{{supplier_name}}' => (string) optional($order->supplier)->name,
            '{{supplier_email}}'=> (string) optional($order->supplier)->email,
            '{{order_date}}'    => $order->order_date ? $order->order_date->format('d/m/Y') : '',
            '{{billing_cnpj}}'  => (string) $order->billing_cnpj,
            '{{billing_uf}}'    => (string) $order->billing_uf,
        ];

        $subjectFinal = strtr($subject, $vars);
        $bodyFinal    = strtr($body, $vars);

        return [$subjectFinal, $bodyFinal];
    }

    protected function saveOrder(Request $request, ?string $id = null)
    {
        $UF = ["AC","AL","AM","AP","BA","CE","DF","ES","GO","MA","MG","MS","MT","PA","PB","PE","PI","PR","RJ","RN","RO","RR","RS","SC","SE","SP","TO"];

        $validated = $request->validate([
            'title'        => ['required', 'string', 'max:255'],

            'supplier_id'  => ['nullable', 'uuid'],

            'billing_cnpj' => ['required', 'string', function($attr,$value,$fail){
                $digits = preg_replace('/\D+/', '', (string)$value);
                if (strlen($digits) !== 14) $fail('CNPJ inválido.');
            }],
            'billing_uf'   => ['required', 'string', 'size:2', \Illuminate\Validation\Rule::in($UF)],
            'order_date'   => ['nullable', 'date_format:Y-m-d'],

            // não deixa subir status fora do fluxo
            'status'       => ['nullable', 'string', \Illuminate\Validation\Rule::in(['draft'])],

            'icms_rate'    => ['nullable', 'numeric', 'min:0', 'max:100'],

            'items'                  => ['required', 'array'],
            'items.*.id'             => ['nullable', 'uuid'],
            'items.*.part_id'        => ['nullable', 'uuid'],
            'items.*.code'           => ['nullable', 'string', 'max:255'],
            'items.*.description'    => ['nullable', 'string', 'max:255'],
            'items.*.ncm'            => ['nullable', 'string', 'max:50'],

            'items.*.unit_price'     => ['required', 'numeric', 'min:0'],
            'items.*.ipi_rate'       => ['nullable', 'numeric', 'min:0', 'max:100'],
            'items.*.quantity'       => ['required', 'numeric', 'min:1'],
            'items.*.discount_rate'  => ['nullable', 'numeric', 'min:0', 'max:100'],
            'items.*.position'       => ['nullable', 'integer', 'min:0'],
        ]);

        // ✅ normaliza UF/CNPJ no que vai pro banco
        $validated['billing_uf'] = strtoupper($validated['billing_uf']);
        $validated['billing_cnpj'] = preg_replace('/\D+/', '', (string)$validated['billing_cnpj']);

        // ✅ força status draft sempre aqui (evita bypass)
        $validated['status'] = 'draft';

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

            // ✅ update precisa ser scoped por tenant (impede “roubo”)
            if ($id) {
                $order = $this->partOrder->newQuery()
                    ->where('customer_sistapp_id', $tenant)
                    ->with('items')
                    ->findOrFail($id);

                if ($order->status !== 'draft') {
                    return response()->json(['message' => 'Somente rascunho pode ser editado.'], 403);
                }
            } else {
                $order = new PartOrder();
                $order->order_number = $this->generateNextNumber();
                $order->status = 'draft';
            }

            $order->customer_sistapp_id = $tenant;
            $order->fill($validated);

            // ✅ tenant check do fornecedor (usando model do relacionamento)
            if ($order->supplier_id) {
                $supplierModel = $order->supplier()->getRelated();
                $ok = $supplierModel->newQuery()
                    ->where('customer_sistapp_id', $tenant)
                    ->whereKey($order->supplier_id)
                    ->exists();

                abort_unless($ok, 422, 'Fornecedor inválido para este cliente.');
            }

            if (! $order->icms_rate) {
                $order->icms_rate = $this->rateFromUF($order->billing_uf);
            }

            $order->save();

            // ✅ tenant check de part_id (ajuste nome da coluna/tabela se diferente)
            $partIds = $items->pluck('part_id')->filter()->unique()->values();
            if ($partIds->count()) {
                $count = DB::table('parts')
                    ->where('customer_sistapp_id', $tenant)
                    ->whereIn('id', $partIds)
                    ->count();

                abort_unless($count === $partIds->count(), 422, 'Uma ou mais peças são inválidas para este cliente.');
            }

            $keep = [];

            foreach ($items as $idx => $row) {
                $payload = $this->normalizeItemPayload($row, $order, $idx);

                if (!empty($row['id'])) {
                    $item = PartOrderItem::where('customer_sistapp_id', $tenant)
                        ->where('part_order_id', $order->id)
                        ->where('id', $row['id'])
                        ->first();

                    if ($item) $item->update($payload);
                    else $item = $order->items()->create($payload);
                } else {
                    $item = $order->items()->create($payload);
                }

                $keep[] = $item->id;
            }

            $order->items()
                ->where('customer_sistapp_id', $tenant)
                ->whereNotIn('id', $keep)
                ->delete();

            $totals = $this->calcTotals($order->items()->get()->toArray(), (float) $order->icms_rate);

            $order->items_count     = (int) $totals['count'];
            $order->subtotal        = $totals['subtotal'];
            $order->ipi_total       = $totals['ipi_total'];
            $order->discount_total  = $totals['discount_total'];
            $order->icms_total      = $totals['icms_total'];
            $order->grand_total     = $totals['grand_total'];

            $order->save();
            $order->load(['items','items.part','supplier']);

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
    protected function findOrderTenantOrFail(string $id, ?string $tenantId = null): PartOrder
    {
        $tenantId = $tenantId ?: CustomerContext::get();

        return $this->partOrder->newQuery()
            ->where('customer_sistapp_id', $tenantId)
            ->with(['supplier', 'items', 'items.part'])
            ->findOrFail($id);
    }

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
