<?php

namespace App\Http\Controllers\Stock;

use App\Http\Controllers\Controller;
use App\Models\Stock\StockBalance;
use App\Models\Stock\StockLocation;
use App\Models\Stock\StockMovementReason;
use App\Models\Stock\StockPart;
use App\Support\TenantUser\CustomerContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class StockController extends Controller
{
    public function view()
    {
        return view('app.stock.stock.stock_index');
    }

    public function index(Request $request)
    {
        $tenantId = CustomerContext::get();

        $q = trim((string) $request->query('q', ''));
        $onlyActive = (int) $request->query('active', 1) === 1;

        // pega do querystring OU do default do tenant
        $locationId = (string) ($this->resolveLocationId($tenantId, $request) ?? '');

        // locations (pra filtro)
        $locations = StockLocation::where('customer_sistapp_id', $tenantId)
            ->orderByDesc('is_default')
            ->orderBy('name')
            ->get(['id','name','is_default']);

        // base (sempre prefixado)
        $query = StockPart::query()
            ->from('stock_parts')
            ->select('stock_parts.*')
            ->where('stock_parts.customer_sistapp_id', $tenantId);

        if ($onlyActive) {
            $query->where('stock_parts.is_active', 1);
        }

        if ($q !== '') {
            $query->where(function ($w) use ($q) {
                $w->where('stock_parts.code', 'like', "%{$q}%")
                    ->orWhere('stock_parts.name', 'like', "%{$q}%")
                    ->orWhere('stock_parts.description', 'like', "%{$q}%");
            });
        }

        // se tiver local resolvido: puxa saldo daquele local + regra de baixo
        if ($locationId !== '') {
            $query->leftJoin('stock_balances as sb', function ($j) use ($tenantId, $locationId) {
                $j->on('sb.stock_part_id', '=', 'stock_parts.id')
                    ->where('sb.customer_sistapp_id', '=', $tenantId)
                    ->where('sb.location_id', '=', $locationId);
            });

            $query->addSelect([
                DB::raw('COALESCE(sb.qty_on_hand, 0) as qty_location'),
                DB::raw('COALESCE(sb.avg_cost, 0) as avg_cost_location'),
                DB::raw('COALESCE(sb.min_qty, 0) as min_qty_location'),
                DB::raw('CASE WHEN COALESCE(sb.min_qty,0) > 0 AND COALESCE(sb.qty_on_hand,0) < COALESCE(sb.min_qty,0) THEN 1 ELSE 0 END as is_low'),
            ]);
        } else {
            // sem local resolvido -> não aplica regra por local
            $query->addSelect([
                DB::raw('0 as qty_location'),
                DB::raw('0 as avg_cost_location'),
                DB::raw('0 as min_qty_location'),
                DB::raw('0 as is_low'),
            ]);
        }

        $items = $query
            ->orderBy('stock_parts.code')
            ->paginate(25);

        return response()->json([
            'locations' => $locations,
            'items' => $items,
            'meta' => [
                'location_id' => $locationId,
            ],
        ]);
    }

    public function show(string $id)
    {
        $tenantId = CustomerContext::get();

        $part = StockPart::query()
            ->where('customer_sistapp_id', $tenantId)
            ->where('id', $id)
            ->firstOrFail([
                'id','code','name','description','ncm',
                'qty_on_hand_global','avg_cost_global','last_cost',
                'default_sale_price','default_markup_percent'
            ]);

        $locs = StockLocation::query()
            ->where('customer_sistapp_id', $tenantId)
            ->orderByDesc('is_default')
            ->orderBy('name')
            ->get(['id','name','is_default']);

        $bals = StockBalance::query()
            ->where('customer_sistapp_id', $tenantId)
            ->where('stock_part_id', $id)
            ->get(['location_id','qty_on_hand','avg_cost','min_qty'])
            ->keyBy('location_id');

        $locations = $locs->map(function ($l) use ($bals) {
            $b = $bals->get($l->id);

            $qty = (int) ($b->qty_on_hand ?? 0);
            $min = (int) ($b->min_qty ?? 0);

            return [
                'id' => (string) $l->id,
                'name' => (string) $l->name,
                'is_default' => (bool) $l->is_default,
                'qty_on_hand' => $qty,
                'avg_cost' => (float) ($b->avg_cost ?? 0),
                'min_qty' => $min,
                'is_low' => ($min > 0 && $qty < $min) ? 1 : 0,
            ];
        })->values();

        $reasons = StockMovementReason::query()
            ->where('customer_sistapp_id', $tenantId)
            ->where('is_active', 1)
            ->orderByDesc('is_system')
            ->orderBy('label')
            ->get(['code','label','is_system']);

        return response()->json([
            'part' => $part,
            'locations' => $locations,
            'reasons' => $reasons,
            'permissions' => [
                'override_cost_out' => auth()->user()?->can('stock.override_cost_out') ?? false,
            ],
        ]);
    }

    public function kpis(Request $request)
    {
        $tenantId   = CustomerContext::get();
        $locationId = (string) $request->query('location_id', '');
        $onlyActive = (int) $request->query('active', 1) === 1;
        $q          = trim((string) $request->query('q', ''));

        $cacheKey = "stock:kpis:{$tenantId}:loc:{$locationId}:active:" . ($onlyActive ? 1 : 0) . ":q:" . md5($q);

        $data = Cache::remember($cacheKey, 30, function () use ($tenantId, $locationId, $onlyActive, $q) {

            $applySearch = function ($qry) use ($q) {
                if ($q === '') return $qry;

                return $qry->where(function ($w) use ($q) {
                    $w->where('p.code', 'like', "%{$q}%")
                        ->orWhere('p.name', 'like', "%{$q}%")
                        ->orWhere('p.description', 'like', "%{$q}%");
                });
            };

            if ($locationId !== '') {
                $qry = DB::table('stock_parts as p')
                    ->where('p.customer_sistapp_id', $tenantId);

                if ($onlyActive) $qry->where('p.is_active', 1);

                $qry = $applySearch($qry);

                $qry->leftJoin('stock_balances as sb', function ($j) use ($tenantId, $locationId) {
                    $j->on('sb.stock_part_id', '=', 'p.id')
                        ->where('sb.customer_sistapp_id', '=', $tenantId)
                        ->where('sb.location_id', '=', $locationId);
                });

                $agg = $qry->selectRaw('
                COUNT(*) as total_skus,
                SUM(CASE WHEN COALESCE(sb.qty_on_hand,0) > 0 THEN 1 ELSE 0 END) as skus_in_stock,
                SUM(COALESCE(sb.qty_on_hand,0)) as total_qty,
                SUM(COALESCE(sb.qty_on_hand,0) * COALESCE(sb.avg_cost,0)) as total_cost_value,
                SUM(CASE WHEN p.default_sale_price > 0 THEN COALESCE(sb.qty_on_hand,0) * p.default_sale_price ELSE 0 END) as total_sale_value,
                SUM(CASE WHEN p.default_sale_price > 0 AND COALESCE(sb.qty_on_hand,0) > 0 THEN 1 ELSE 0 END) as sale_skus_in_stock
            ')->first();
            } else {
                $qry = DB::table('stock_parts as p')
                    ->where('p.customer_sistapp_id', $tenantId);

                if ($onlyActive) $qry->where('p.is_active', 1);

                $qry = $applySearch($qry);

                $agg = $qry->selectRaw('
                COUNT(*) as total_skus,
                SUM(CASE WHEN p.qty_on_hand_global > 0 THEN 1 ELSE 0 END) as skus_in_stock,
                SUM(p.qty_on_hand_global) as total_qty,
                SUM(p.qty_on_hand_global * p.avg_cost_global) as total_cost_value,
                SUM(CASE WHEN p.default_sale_price > 0 THEN p.qty_on_hand_global * p.default_sale_price ELSE 0 END) as total_sale_value,
                SUM(CASE WHEN p.default_sale_price > 0 AND p.qty_on_hand_global > 0 THEN 1 ELSE 0 END) as sale_skus_in_stock
            ')->first();
            }

            $in7   = $this->kpiMovementsAgg($tenantId, $locationId, $onlyActive, $q, 'in',  7);
            $out7  = $this->kpiMovementsAgg($tenantId, $locationId, $onlyActive, $q, 'out', 7);
            $in30  = $this->kpiMovementsAgg($tenantId, $locationId, $onlyActive, $q, 'in',  30);
            $out30 = $this->kpiMovementsAgg($tenantId, $locationId, $onlyActive, $q, 'out', 30);

            return [
                'total_skus'         => (int) ($agg->total_skus ?? 0),
                'skus_in_stock'      => (int) ($agg->skus_in_stock ?? 0),
                'sale_skus_in_stock' => (int) ($agg->sale_skus_in_stock ?? 0),
                'total_qty'          => (int) ($agg->total_qty ?? 0),
                'total_cost_value'   => (float) ($agg->total_cost_value ?? 0),
                'total_sale_value'   => (float) ($agg->total_sale_value ?? 0),
                'in_7'   => $in7,
                'out_7'  => $out7,
                'in_30'  => $in30,
                'out_30' => $out30,
            ];
        });

        return response()->json(['kpis' => $data]);
    }

    private function kpiMovementsAgg(string $tenantId, string $locationId, bool $onlyActive, string $q, string $type, int $days): array
    {
        $from = now()->subDays($days);

        $qry = DB::table('stock_movements as m')
            ->join('stock_movement_items as i', function ($j) use ($tenantId) {
                $j->on('i.movement_id', '=', 'm.id')
                    ->where('i.customer_sistapp_id', '=', $tenantId);
            })
            ->join('stock_parts as p', function ($j) use ($tenantId) {
                $j->on('p.id', '=', 'i.stock_part_id')
                    ->where('p.customer_sistapp_id', '=', $tenantId);
            })
            ->where('m.customer_sistapp_id', $tenantId)
            ->where('m.type', $type)
            ->where('m.created_at', '>=', $from);

        if ($locationId !== '') {
            $qry->where('i.location_id', $locationId);
        }

        if ($onlyActive) {
            $qry->where('p.is_active', 1);
        }

        if ($q !== '') {
            $qry->where(function ($w) use ($q) {
                $w->where('p.code', 'like', "%{$q}%")
                    ->orWhere('p.name', 'like', "%{$q}%")
                    ->orWhere('p.description', 'like', "%{$q}%");
            });
        }

        $agg = $qry->selectRaw('
        COALESCE(SUM(i.qty),0) as total_qty,
        COALESCE(SUM(i.total_cost),0) as total_cost
    ')->first();

        return [
            'total_qty'  => (int) ($agg->total_qty ?? 0),
            'total_cost' => (float) ($agg->total_cost ?? 0),
        ];
    }

    public function adjust(Request $request, string $id)
    {
        $tenantId = CustomerContext::get();

        // (opcional) permissão - se ainda não criou, comente por enquanto
        // abort_unless(auth()->user()?->can('stock.adjust'), 403);

        $data = $request->validate([
            'location_id' => [
                'required',
                'string',
                'size:36',
                Rule::exists('stock_locations', 'id')->where(fn ($q) => $q->where('customer_sistapp_id', $tenantId)),
            ],

            // ajustes de saldo/custos por local
            'qty_on_hand' => ['nullable', 'integer', 'min:0'],
            'avg_cost'    => ['nullable', 'numeric', 'min:0'],
            'min_qty'     => ['nullable', 'integer', 'min:0'],

            // defaults do item (globais)
            'last_cost'              => ['nullable', 'numeric', 'min:0'],
            'default_sale_price'     => ['nullable', 'numeric', 'min:0'],
            'default_markup_percent' => ['nullable', 'numeric', 'min:0'],

            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $locId = (string) $data['location_id'];
        $now   = now();

        return DB::transaction(function () use ($tenantId, $id, $locId, $data, $now) {

            // lock part
            $part = DB::table('stock_parts')
                ->where('customer_sistapp_id', $tenantId)
                ->where('id', $id)
                ->lockForUpdate()
                ->first();

            abort_if(!$part, 404);

            // lock/create balance (por local)
            $bal = DB::table('stock_balances')
                ->where('customer_sistapp_id', $tenantId)
                ->where('stock_part_id', $id)
                ->where('location_id', $locId)
                ->lockForUpdate()
                ->first();

            if (!$bal) {
                $balId = (string) Str::uuid();

                DB::table('stock_balances')->insert([
                    'id' => $balId,
                    'customer_sistapp_id' => $tenantId,
                    'stock_part_id' => $id,
                    'location_id' => $locId,
                    'qty_on_hand' => 0,
                    'avg_cost' => 0,
                    'min_qty' => 0,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);

                $bal = (object) [
                    'id' => $balId,
                    'qty_on_hand' => 0,
                    'avg_cost' => 0,
                    'min_qty' => 0,
                ];
            }

            $before = [
                'qty_on_hand' => (int) ($bal->qty_on_hand ?? 0),
                'avg_cost'    => (float) ($bal->avg_cost ?? 0),
                'min_qty'     => (int) ($bal->min_qty ?? 0),

                'last_cost'              => (float) ($part->last_cost ?? 0),
                'default_sale_price'     => (float) ($part->default_sale_price ?? 0),
                'default_markup_percent' => (float) ($part->default_markup_percent ?? 0),
            ];

            // aplica valores (se não veio, mantém)
            $newQty = array_key_exists('qty_on_hand', $data) && $data['qty_on_hand'] !== null
                ? (int) $data['qty_on_hand']
                : (int) $before['qty_on_hand'];

            $newAvg = array_key_exists('avg_cost', $data) && $data['avg_cost'] !== null
                ? (float) $data['avg_cost']
                : (float) $before['avg_cost'];

            $newMin = array_key_exists('min_qty', $data) && $data['min_qty'] !== null
                ? (int) $data['min_qty']
                : (int) $before['min_qty'];

            $newLastCost = array_key_exists('last_cost', $data) && $data['last_cost'] !== null
                ? (float) $data['last_cost']
                : (float) $before['last_cost'];

            $newSale = array_key_exists('default_sale_price', $data) && $data['default_sale_price'] !== null
                ? (float) $data['default_sale_price']
                : (float) $before['default_sale_price'];

            $newMk = array_key_exists('default_markup_percent', $data) && $data['default_markup_percent'] !== null
                ? (float) $data['default_markup_percent']
                : (float) $before['default_markup_percent'];

            // valida “teve mudança”
            $changed = (
                $newQty !== $before['qty_on_hand'] ||
                $newAvg !== $before['avg_cost'] ||
                $newMin !== $before['min_qty'] ||
                $newLastCost !== $before['last_cost'] ||
                $newSale !== $before['default_sale_price'] ||
                $newMk !== $before['default_markup_percent']
            );

            abort_if(!$changed, 422, 'Nada para ajustar.');

            // update balance
            DB::table('stock_balances')
                ->where('id', $bal->id)
                ->where('customer_sistapp_id', $tenantId)
                ->update([
                    'qty_on_hand' => $newQty,
                    'avg_cost'    => $newAvg,
                    'min_qty'     => $newMin,
                    'updated_at'  => $now,
                ]);

            // recalcula global (denormalizado em stock_parts)
            $g = DB::table('stock_balances')
                ->where('customer_sistapp_id', $tenantId)
                ->where('stock_part_id', $id)
                ->selectRaw('
                COALESCE(SUM(qty_on_hand),0) as total_qty,
                COALESCE(SUM(qty_on_hand * avg_cost),0) as total_cost_value
            ')
                ->first();

            $totalQty = (int) ($g->total_qty ?? 0);
            $avgGlobal = $totalQty > 0 ? ((float) ($g->total_cost_value ?? 0) / $totalQty) : 0.0;

            DB::table('stock_parts')
                ->where('customer_sistapp_id', $tenantId)
                ->where('id', $id)
                ->update([
                    'qty_on_hand_global'     => $totalQty,
                    'avg_cost_global'        => $avgGlobal,
                    'last_cost'              => $newLastCost,
                    'default_sale_price'     => $newSale,
                    'default_markup_percent' => $newMk,
                    'updated_at'             => $now,
                ]);

            // LOG: movement + item
            $movementId = (string) Str::uuid();

            $reasonId = DB::table('stock_movement_reasons')
                ->where('customer_sistapp_id', $tenantId)
                ->where('code', 'manual_adjust')
                ->value('id');

            $userId = auth()->id();

            $noteLines = [];
            $noteLines[] = 'Ajuste de estoque (set direto)';
            $noteLines[] = "Local: {$locId}";
            if ($newQty !== $before['qty_on_hand']) $noteLines[] = "Qtd: {$before['qty_on_hand']} -> {$newQty}";
            if ($newAvg !== $before['avg_cost'])    $noteLines[] = "Custo médio (local): {$before['avg_cost']} -> {$newAvg}";
            if ($newMin !== $before['min_qty'])     $noteLines[] = "Mínimo (local): {$before['min_qty']} -> {$newMin}";
            if ($newLastCost !== $before['last_cost']) $noteLines[] = "Último custo: {$before['last_cost']} -> {$newLastCost}";
            if ($newSale !== $before['default_sale_price']) $noteLines[] = "Venda padrão: {$before['default_sale_price']} -> {$newSale}";
            if ($newMk !== $before['default_markup_percent']) $noteLines[] = "Markup padrão: {$before['default_markup_percent']} -> {$newMk}";

            $userNotes = trim((string) ($data['notes'] ?? ''));
            if ($userNotes !== '') {
                $noteLines[] = '---';
                $noteLines[] = $userNotes;
            }

            $after = [
                'qty_on_hand' => (int) $newQty,
                'avg_cost'    => (float) $newAvg,
                'min_qty'     => (int) $newMin,
                'last_cost'              => (float) $newLastCost,
                'default_sale_price'     => (float) $newSale,
                'default_markup_percent' => (float) $newMk,
            ];

            $changes = $this->buildChanges($before, $after);
            $summary = $this->changesSummary($changes);

            DB::table('stock_movements')->insert([
                'id' => $movementId,
                'customer_sistapp_id' => $tenantId,
                'type' => 'adjust',
                'reason_id' => $reasonId ?: null,
                'source_type' => 'stock_adjust',
                'source_id' => $id,
                'user_id' => $userId ?: null,
                'notes' => implode("\n", $noteLines),

                'meta' => json_encode([
                    'stock_part_id' => $id,
                    'location_id' => $locId,
                    'changes' => $changes,
                    'changes_summary' => $summary,
                ], JSON_UNESCAPED_UNICODE),

                'created_at' => $now,
                'updated_at' => $now,
            ]);

            $totalCost = round($newQty * $newAvg, 2);

            $changedStockNumbers = (
                $newQty !== $before['qty_on_hand'] ||
                (string)$newAvg !== (string)$before['avg_cost'] ||
                $newMin !== $before['min_qty']
            );

            if ($changedStockNumbers) {
                $itemQty = $newQty;              // mantém seu padrão atual
                $itemUnit = $newAvg;
                $itemTotal = round($newQty * $newAvg, 2);
            } else {
                // ✅ ajuste só de preço/margem/último custo
                $itemQty = 0;
                $itemUnit = 0;
                $itemTotal = 0;
            }

            DB::table('stock_movement_items')->insert([
                'id' => (string) Str::uuid(),
                'customer_sistapp_id' => $tenantId,
                'movement_id' => $movementId,
                'stock_part_id' => $id,
                'location_id' => $locId,
                'code' => (string) ($part->code ?? ''),
                'description' => (string) (($part->description ?? $part->name) ?? ''),
                'ncm' => (string) ($part->ncm ?? ''),

                'qty' => $itemQty,
                'unit_cost' => $itemUnit,
                'total_cost' => $itemTotal,

                // mantém pra referência
                'sale_price' => $newSale,
                'markup_percent' => $newMk,

                'created_at' => $now,
                'updated_at' => $now,
            ]);

            // limpa cache KPIs (se você usa cacheKey com md5 do q, o mais seguro é limpar por prefixo via tags,
            // mas como Cache store pode não suportar, aqui vai o básico: esquece "loc atual" sem q)
            Cache::forget("stock:kpis:{$tenantId}:loc:{$locId}:active:1:q:" . md5(''));
            Cache::forget("stock:kpis:{$tenantId}:loc::active:1:q:" . md5(''));

            return response()->json([
                'ok' => true,
                'movement_id' => $movementId,
            ], 201);
        });
    }

    private function buildChanges(array $before, array $after): array
    {
        $out = [];
        foreach ($before as $k => $v) {
            $to = $after[$k] ?? null;

            // compara de forma estável
            if ((string)$v !== (string)$to) {
                $out[$k] = ['from' => $v, 'to' => $to];
            }
        }
        return $out;
    }

    private function changesSummary(array $changes): string
    {
        $map = [
            'qty_on_hand' => 'Qtd',
            'avg_cost' => 'Custo médio (local)',
            'min_qty' => 'Mínimo (local)',
            'last_cost' => 'Último custo',
            'default_sale_price' => 'Venda padrão',
            'default_markup_percent' => 'Markup',
        ];

        $parts = [];
        foreach ($changes as $k => $v) {
            $label = $map[$k] ?? $k;
            $from = $v['from'] ?? null;
            $to   = $v['to'] ?? null;

            if ($k === 'default_markup_percent') {
                $from = number_format((float)$from, 2, ',', '.') . '%';
                $to   = number_format((float)$to, 2, ',', '.') . '%';
            } elseif (in_array($k, ['avg_cost','last_cost'], true)) {
                $from = number_format((float)$from, 4, ',', '.');
                $to   = number_format((float)$to, 4, ',', '.');
            } elseif ($k === 'default_sale_price') {
                $from = number_format((float)$from, 2, ',', '.');
                $to   = number_format((float)$to, 2, ',', '.');
            }

            $parts[] = "{$label}: {$from} → {$to}";
        }

        return implode(' • ', $parts);
    }

    private function resolveLocationId(string $tenantId, Request $request): ?string
    {
        $loc = trim((string) $request->query('location_id', ''));

        if ($loc !== '') return $loc;

        $def = DB::table('stock_settings')
            ->where('customer_sistapp_id', $tenantId)
            ->value('default_location_id');

        $def = $def ? (string) $def : '';

        return $def !== '' ? $def : null;
    }
}
