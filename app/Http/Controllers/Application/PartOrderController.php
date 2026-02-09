<?php

namespace App\Http\Controllers\Application;

use App\Http\Controllers\Controller;
use App\Mail\PartOrders\PartOrderToSupplierMail;
use App\Models\PartOrder;
use App\Models\PartOrderItem;
use App\Models\PartOrderSetting;
use App\Models\Stock\StockLocation;
use App\Services\Stock\ReceivePartOrderService;
use App\Support\Audit\Audit;
use App\Support\CustomerContext;
use App\Traits\RoleCheckTrait;
use App\Traits\WebIndex;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class PartOrderController extends Controller
{
    use RoleCheckTrait, WebIndex;

    protected PartOrder $partOrder;

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
            ->where('customer_sistapp_id', $tenantId)
            ->with(['supplier:id,name,email'])
            ->withCount('items')
            ->withSum('items as qty_total_sum', 'quantity')
            ->withSum('items as received_qty_sum', 'received_qty')

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

        $data = $q->paginate(1000);

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
        $original->loadMissing('items'); // garantia

        return DB::transaction(function () use ($original) {
            $copy = $original->replicate([
                'id','order_number','created_at','updated_at','sent_at',
                'supplier_email_used','email_subject_used','email_body_used',
                'account_payable_id',
            ]);

            $meta = $copy->meta;
            if (is_array($meta)) {
                unset($meta['payable_id'], $meta['payment']);
                $copy->meta = $meta;
            }

            $copy->account_payable_id = null;
            $copy->supplier_email_used = null;
            $copy->email_subject_used = null;
            $copy->email_body_used = null;



            $copy->id = (string) Str::uuid();
            $copy->status = 'draft';
            $copy->sent_at = null;
            $copy->order_date = now()->toDateString();
            $copy->order_number = CustomerContext::for($original->customer_sistapp_id, function () {
                return $this->generateNextNumber();
            });

            // se você tiver campos tipo received_qty_sum no header, zera aqui também
            // $copy->received_qty_sum = 0;

            $copy->save();

            foreach ($original->items as $it) {
                $new = $it->replicate([
                    'id','part_order_id','created_at','updated_at',
                    // se existirem esses campos, melhor não copiar:
                    // 'received_qty','received_at','last_received_at',
                ]);

                $new->id = (string) Str::uuid();
                $new->part_order_id = $copy->id;
                $new->customer_sistapp_id = $copy->customer_sistapp_id;

                // ✅ zera recebido no clone
                if (isset($new->received_qty)) $new->received_qty = 0;

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

        $this->createPayableFromPartOrder($order, (string) auth()->id());

        Audit::log(
            'part_order.send',
            'PartOrder',
            $order->id,
            true,
            [
                'order_number' => $order->order_number,
                'to' => $supplierEmail,
                'subject_used' => $subjectFinal,
                'status' => $order->status,
            ]
        );

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

        Audit::log(
            'part_order.resend',
            'PartOrder',
            $order->id,
            true,
            [
                'order_number' => $order->order_number,
                'to' => $to,
                'subject_used' => $subject,
            ]
        );

        return response()->json(['ok' => true]);
    }

    protected function applyMailVars(string $subject, string $body, PartOrder $order): array
    {
        // ✅ aceita @{{var}} vindo do front (preview usa isso)
        $subject = str_replace('@{{', '{{', $subject);
        $body    = str_replace('@{{', '{{', $body);

        $itemsCount = $order->relationLoaded('items')
            ? $order->items->count()
            : (int)($order->items_count ?? 0);

        $fmtMoney = fn($v) => 'R$ ' . number_format((float)($v ?? 0), 2, ',', '.');

        // ✅ não explode se order_date vier string
        $orderDate = '';
        if (!empty($order->order_date)) {
            try {
                $orderDate = \Illuminate\Support\Carbon::parse($order->order_date)->format('d/m/Y');
            } catch (\Throwable $e) {
                $orderDate = (string) $order->order_date;
            }
        }

        $vars = [
            '{partOrderNumber}' => (string) $order->order_number,
            '{supplierName}'    => (string) optional($order->supplier)->name,
            '{supplierEmail}'   => (string) optional($order->supplier)->email,
            '{orderDate}'       => $orderDate,
            '{itemsCount}'      => (string) $itemsCount,
            '{total}'           => $fmtMoney($order->grand_total),

            '{title}'           => (string) $order->title,
            '{billingCnpj}'     => (string) $order->billing_cnpj,
            '{billingUf}'       => (string) $order->billing_uf,

            // COMPAT: {{var}}
            '{{partOrderNumber}}' => (string) $order->order_number,
            '{{supplierName}}'    => (string) optional($order->supplier)->name,
            '{{supplierEmail}}'   => (string) optional($order->supplier)->email,
            '{{orderDate}}'       => $orderDate,
            '{{itemsCount}}'      => (string) $itemsCount,
            '{{total}}'           => $fmtMoney($order->grand_total),

            // snake compat
            '{{order_number}}'   => (string) $order->order_number,
            '{{supplier_name}}'  => (string) optional($order->supplier)->name,
            '{{supplier_email}}' => (string) optional($order->supplier)->email,
            '{{order_date}}'     => $orderDate,
            '{{billing_cnpj}}'   => (string) $order->billing_cnpj,
            '{{billing_uf}}'     => (string) $order->billing_uf,
        ];

        return [strtr($subject, $vars), strtr($body, $vars)];
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

            'status'       => ['nullable', 'string', \Illuminate\Validation\Rule::in(['draft'])],

            'payment_mode' => ['required', 'string', \Illuminate\Validation\Rule::in(['avista', 'sinal_parcelas'])],

            'signal_due_date' => ['required', 'date_format:Y-m-d'],

            'signal_amount' => [
                'nullable','numeric','min:0','max:100',
                \Illuminate\Validation\Rule::requiredIf(fn() => $request->input('payment_mode') === 'sinal_parcelas'),
            ],

            'installments_count' => [
                'nullable','integer','min:1','max:120',
                \Illuminate\Validation\Rule::requiredIf(fn() => $request->input('payment_mode') === 'sinal_parcelas'),
            ],

            'installments_first_due_date' => [
                'nullable','date_format:Y-m-d',
                'after_or_equal:signal_due_date',
            ],

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

        $validated['billing_uf'] = strtoupper($validated['billing_uf']);
        $validated['billing_cnpj'] = preg_replace('/\D+/', '', (string)$validated['billing_cnpj']);

        $validated['status'] = 'draft';

        $validated['payment_mode'] = $validated['payment_mode'] ?? 'avista';
        $validated['signal_amount'] = round((float)($validated['signal_amount'] ?? 0), 2); // % 0-100
        $validated['installments_count'] = (int)($validated['installments_count'] ?? 0);

        if ($validated['payment_mode'] === 'avista') {
            $validated['signal_amount'] = 0;
            $validated['installments_count'] = 0;
            $validated['installments_first_due_date'] = null;
        } else {
            $validated['signal_amount'] = max(0, min(100, $validated['signal_amount']));
            $validated['installments_count'] = max(1, $validated['installments_count']);

            if ($validated['signal_amount'] >= 100) {
                $validated['payment_mode'] = 'avista';
                $validated['signal_amount'] = 0;
                $validated['installments_count'] = 0;
                $validated['installments_first_due_date'] = null;
            }
        }

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

            if ($order->payment_mode === 'sinal_parcelas') {
                $order->signal_amount = max(0, min(100, round((float)$order->signal_amount, 2)));
                $order->installments_count = max(1, (int)$order->installments_count);

                if ($order->signal_amount >= 100) {
                    $order->payment_mode = 'avista';
                    $order->signal_amount = 0;
                    $order->installments_count = 0;
                    $order->installments_first_due_date = null;
                }
            } else {
                $order->signal_amount = 0;
                $order->installments_count = 0;
                $order->installments_first_due_date = null;
            }

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

    public function receive(Request $request, string $id, ReceivePartOrderService $svc)
    {
        $order = $this->partOrder
            ->with(['items','items.part'])
            ->findOrFail($id);

        $tenant = CustomerContext::get();
        if ($order->customer_sistapp_id !== $tenant) {
            return response()->json(['message' => 'Acesso negado.'], 403);
        }

        $mode = (string) $request->input('mode', 'total');

        $rules = [
            'mode' => ['required', 'in:total,partial'],
            'items' => ['nullable', 'array'],
            'items.*.part_order_item_id' => ['required_with:items', 'string'],
            'items.*.qty' => ['nullable', 'integer', 'min:0'],

            // ✅ adiciona isso
            'items.*.price_mode' => ['required_with:items', 'in:sale,markup'],

            'items.*.sale_price' => ['nullable', 'numeric', 'min:0'],
            'items.*.markup_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],

            'items.*.locations' => ['nullable', 'array'],
            'items.*.locations.*.location_id' => ['required_with:items.*.locations', 'string'],
            'items.*.locations.*.qty' => ['required_with:items.*.locations', 'integer', 'min:0'],
        ];

        if ($mode === 'partial') {
            $rules['items'] = ['required', 'array', 'min:1'];
        }

        $v = Validator::make($request->all(), $rules);

        $v->after(function ($validator) use ($mode, $request) {
            if ($mode !== 'partial') return;

            $items = $request->input('items', []);
            $hasAny = false;

            foreach ($items as $it) {
                $qty = (int)($it['qty'] ?? 0);
                $locs = $it['locations'] ?? [];

                // aceita qty direto
                if ($qty > 0) { $hasAny = true; break; }

                // aceita qty via locais
                if (is_array($locs)) {
                    foreach ($locs as $l) {
                        if ((int)($l['qty'] ?? 0) > 0) { $hasAny = true; break 2; }
                    }
                }
            }

            if (!$hasAny) {
                $validator->errors()->add('items', 'Informe ao menos 1 item com quantidade > 0 para entrada parcial.');
            }
        });

        if ($v->fails()) {
            return response()->json(['message' => 'Dados inválidos.', 'errors' => $v->errors()], 422);
        }

        try {
            $movementId = $svc->receive($order, $v->validated(), auth()->id());
            return response()->json(['ok' => true, 'movement_id' => $movementId]);
        } catch (\Throwable $e) {
            return response()->json(['message' => $e->getMessage() ?: 'Falha no recebimento.'], 422);
        }
    }

    public function receiveData(string $id)
    {
        $order = $this->partOrder
            ->with(['items'])
            ->findOrFail($id);

        $tenant = CustomerContext::get();
        if ($order->customer_sistapp_id !== $tenant) {
            return response()->json(['message' => 'Acesso negado.'], 403);
        }

        $locCount = StockLocation::where('customer_sistapp_id', $tenant)->count();
        $mustSplit = $locCount > 1;

        $locations = StockLocation::where('customer_sistapp_id', $tenant)
            ->orderByDesc('is_default')
            ->orderBy('name')
            ->get(['id','name','is_default']);

        $defaultLocId = optional($locations->firstWhere('is_default', true))->id;

        $items = $order->items->map(function ($it) use ($mustSplit) {
            $qtyStr = (string) $it->quantity;

            // se teu banco já virou int, isso sempre vai dar false.
            $hasDecimal = preg_match('/\.\d*[1-9]/', $qtyStr) === 1;
            $qtyInt = (int) $qtyStr;

            $remaining = $hasDecimal ? null : max(0, $qtyInt - (int)$it->received_qty);

            return [
                'id' => (string) $it->id,
                'code' => (string) ($it->code ?? ''),
                'description' => (string) ($it->description ?? ''),
                'ncm' => (string) ($it->ncm ?? ''),
                'quantity' => (int) $qtyInt,
                'received_qty' => (int) $it->received_qty,
                'remaining' => $remaining,
                'unit_price' => (float) $it->unit_price,
                'line_total' => (float) $it->line_total,
                'integer_only' => !$hasDecimal,
                'must_split_by_location' => $mustSplit,
            ];
        });

        return response()->json([
            'order' => [
                'id' => (string) $order->id,
                'status' => (string) $order->status,
            ],
            'must_split_by_location' => $mustSplit,
            'default_location_id' => $defaultLocId,
            'locations' => $locations,
            'items' => $items,
        ]);
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

    protected function createPayableFromPartOrder(PartOrder $order, ?string $userId): void
    {
        $tenantId = (string) $order->customer_sistapp_id;
        $total = round((float) ($order->grand_total ?? 0), 2);

        if ($total <= 0) return;

        if (!empty($order->account_payable_id)) {
            $exists = DB::table('account_payables')
                ->where('customer_sistapp_id', $tenantId)
                ->where('id', $order->account_payable_id)
                ->exists();

            if ($exists) return;

            $order->account_payable_id = null;
            $order->save();
        }

        $mode = (string) ($order->payment_mode ?? 'avista');

        $baseDue = Carbon::parse($order->signal_due_date ?: now()->toDateString())->startOfDay();

        $rows = [];

        if ($mode === 'avista') {
            $rows[] = ['due' => $baseDue->toDateString(), 'amount' => $total];
        } else {
            $signalPercent = round((float)($order->signal_amount ?? 0), 2);
            $signalPercent = max(0, min(100, $signalPercent));

            $sinal = round($total * ($signalPercent / 100), 2);
            $sinal = max(0, min($total, $sinal));

            $installments = max(1, (int)($order->installments_count ?? 1));
            $rest = round($total - $sinal, 2);

            if ($sinal > 0) {
                $rows[] = ['due' => $baseDue->toDateString(), 'amount' => $sinal];
            }

            if ($rest > 0) {
                $firstInstDue = $order->installments_first_due_date
                    ? Carbon::parse($order->installments_first_due_date)->startOfDay()
                    : (($sinal > 0) ? $baseDue->copy()->addMonth() : $baseDue->copy());

                $base = floor(($rest / $installments) * 100) / 100; // trunca 2 casas
                $lastAdj = round($rest - ($base * $installments), 2);

                for ($i = 0; $i < $installments; $i++) {
                    $amt = $base + (($i === $installments - 1) ? $lastAdj : 0);
                    $rows[] = [
                        'due' => $firstInstDue->copy()->addMonths($i)->toDateString(),
                        'amount' => round($amt, 2),
                    ];
                }
            }

            if (!$rows) {
                $rows[] = ['due' => $baseDue->toDateString(), 'amount' => $total];
            }
        }

        usort($rows, fn($a, $b) => strcmp($a['due'], $b['due']));

        $payableId = (string) Str::uuid();
        $times = count($rows);
        $firstPayment = $rows[0]['due'];
        $endRecurrence = $rows[$times - 1]['due'];

        $supplierName = $order->relationLoaded('supplier')
            ? (string) optional($order->supplier)->name
            : (string) optional($order->supplier()->first())->name;

        $desc = "Pedido de peças {$order->order_number} - " . ($order->title ?: ($supplierName ?: '—'));

        DB::transaction(function () use ($tenantId, $userId, $order, $payableId, $rows, $times, $firstPayment, $endRecurrence, $desc) {

            DB::table('account_payables')->insert([
                'id' => $payableId,
                'customer_sistapp_id' => $tenantId,
                'user_id' => $userId,
                'description' => $desc,
                'default_amount' => $rows[0]['amount'],
                'first_payment' => $firstPayment,
                'end_recurrence' => $endRecurrence,
                'recurrence' => 'variable',
                'times' => $times,
                'status' => 'open',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            foreach ($rows as $idx => $r) {
                DB::table('account_payable_recurrences')->insert([
                    'id' => (string) Str::uuid(),
                    'customer_sistapp_id' => $tenantId,
                    'user_id' => $userId,
                    'account_payable_id' => $payableId,
                    'recurrence_number' => $idx + 1,
                    'due_date' => $r['due'],
                    'amount' => $r['amount'],
                    'status' => 'pending',
                    'amount_paid' => 0,
                    'paid_at' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            $order->account_payable_id = $payableId;
            $order->save();
        });
    }
}
