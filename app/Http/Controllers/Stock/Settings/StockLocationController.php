<?php

namespace App\Http\Controllers\Stock\Settings;

use App\Http\Controllers\Controller;
use App\Support\CustomerContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class StockLocationController extends Controller
{
    public function view()
    {
        return view('app.stock.settings.location.setting_location_index');
    }

    public function index(Request $request)
    {
        $tenantId = CustomerContext::get();

        $q = trim((string) $request->query('q', ''));
        $default = (string) $request->query('default', 'all'); // 1|0|all

        $qry = DB::table('stock_locations')
            ->where('customer_sistapp_id', $tenantId);

        if ($q !== '') {
            $qry->where('name', 'like', "%{$q}%");
        }

        if ($default !== 'all') {
            $qry->where('is_default', (int) $default);
        }

        $items = $qry
            ->orderByDesc('is_default')
            ->orderBy('name')
            ->paginate(25);

        return response()->json(['items' => $items]);
    }

    public function store(Request $request)
    {
        $tenantId = CustomerContext::get();

        $data = $request->validate([
            'name' => [
                'required','string','max:120',
                Rule::unique('stock_locations', 'name')->where(fn($q) => $q->where('customer_sistapp_id', $tenantId)),
            ],
            'is_default' => ['nullable','boolean'],
        ]);

        $id = (string) Str::uuid();
        $now = now();
        $isDefault = (int) ($data['is_default'] ?? 0);

        DB::transaction(function () use ($tenantId, $id, $now, $data, $isDefault) {
            if ($isDefault === 1) {
                DB::table('stock_locations')
                    ->where('customer_sistapp_id', $tenantId)
                    ->update(['is_default' => 0, 'updated_at' => $now]);
            }

            DB::table('stock_locations')->insert([
                'id' => $id,
                'customer_sistapp_id' => $tenantId,
                'name' => $data['name'],
                'is_default' => $isDefault,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            // se criou sem default e o tenant ficou sem nenhum default, promove este como default
            $hasDefault = DB::table('stock_locations')
                ->where('customer_sistapp_id', $tenantId)
                ->where('is_default', 1)
                ->exists();

            if (!$hasDefault) {
                DB::table('stock_locations')
                    ->where('customer_sistapp_id', $tenantId)
                    ->where('id', $id)
                    ->update(['is_default' => 1, 'updated_at' => $now]);
            }
        });

        return response()->json(['id' => $id], 201);
    }

    public function update(Request $request, string $id)
    {
        $tenantId = CustomerContext::get();

        $row = DB::table('stock_locations')
            ->where('customer_sistapp_id', $tenantId)
            ->where('id', $id)
            ->first();

        abort_if(!$row, 404);

        $data = $request->validate([
            'name' => [
                'required','string','max:120',
                Rule::unique('stock_locations', 'name')
                    ->where(fn($q) => $q->where('customer_sistapp_id', $tenantId))
                    ->ignore($id, 'id'),
            ],
            'is_default' => ['nullable','boolean'],
        ]);

        $wantDefault = (int) ($data['is_default'] ?? (int)$row->is_default);
        $now = now();

        // não deixa o tenant ficar sem default (tentou desmarcar o default atual)
        if ((int)$row->is_default === 1 && $wantDefault === 0) {
            return response()->json(['message' => 'Precisa existir 1 local padrão. Selecione outro como padrão antes.'], 422);
        }

        DB::transaction(function () use ($tenantId, $id, $data, $wantDefault, $now) {
            if ($wantDefault === 1) {
                DB::table('stock_locations')
                    ->where('customer_sistapp_id', $tenantId)
                    ->update(['is_default' => 0, 'updated_at' => $now]);
            }

            DB::table('stock_locations')
                ->where('customer_sistapp_id', $tenantId)
                ->where('id', $id)
                ->update([
                    'name' => $data['name'],
                    'is_default' => $wantDefault,
                    'updated_at' => $now,
                ]);
        });

        return response()->json(['ok' => true]);
    }

    public function destroy(string $id)
    {
        $tenantId = CustomerContext::get();

        $row = DB::table('stock_locations')
            ->where('customer_sistapp_id', $tenantId)
            ->where('id', $id)
            ->first();

        abort_if(!$row, 404);

        if ((int) $row->is_default === 1) {
            return response()->json(['message' => 'Local padrão não pode ser removido. Defina outro como padrão primeiro.'], 422);
        }

        $hasStock = DB::table('stock_balances')
            ->where('customer_sistapp_id', $tenantId)
            ->where('location_id', $id)
            ->where('qty_on_hand', '>', 0)
            ->exists();

        if ($hasStock) {
            return response()->json(['message' => 'Este local possui saldo em estoque. Zere o saldo antes de excluir.'], 422);
        }

        DB::table('stock_locations')
            ->where('customer_sistapp_id', $tenantId)
            ->where('id', $id)
            ->delete();

        return response()->json(['ok' => true]);
    }
    public function picklist()
    {
        $tenantId = CustomerContext::get();

        $items = DB::table('stock_locations')
            ->where('customer_sistapp_id', $tenantId)
            ->orderByDesc('is_default')
            ->orderBy('name')
            ->get(['id','name','is_default']);

        return response()->json(['items' => $items]);
    }

    public function deleteCheck(string $id)
    {
        $tenantId = CustomerContext::get();

        $loc = DB::table('stock_locations')
            ->where('customer_sistapp_id', $tenantId)
            ->where('id', $id)
            ->first();

        abort_if(!$loc, 404);

        // saldo (se tem qualquer qty > 0)
        $hasQty = DB::table('stock_balances')
            ->where('customer_sistapp_id', $tenantId)
            ->where('location_id', $id)
            ->where('qty_on_hand', '>', 0)
            ->exists();

        // stats
        $stats = DB::table('stock_balances as sb')
            ->join('stock_parts as sp', function ($j) use ($tenantId) {
                $j->on('sp.id', '=', 'sb.stock_part_id')
                    ->where('sp.customer_sistapp_id', '=', $tenantId);
            })
            ->where('sb.customer_sistapp_id', $tenantId)
            ->where('sb.location_id', $id)
            ->selectRaw('
            SUM(CASE WHEN sb.qty_on_hand > 0 THEN 1 ELSE 0 END) as skus_with_qty,
            SUM(sb.qty_on_hand) as total_qty,
            SUM(sb.qty_on_hand * sb.avg_cost) as total_cost_est
        ')
            ->first();

        // preview (top itens)
        $items = DB::table('stock_balances as sb')
            ->join('stock_parts as sp', function ($j) use ($tenantId) {
                $j->on('sp.id', '=', 'sb.stock_part_id')
                    ->where('sp.customer_sistapp_id', '=', $tenantId);
            })
            ->where('sb.customer_sistapp_id', $tenantId)
            ->where('sb.location_id', $id)
            ->where('sb.qty_on_hand', '>', 0)
            ->orderByDesc('sb.qty_on_hand')
            ->limit(10)
            ->get([
                'sp.id as stock_part_id',
                'sp.code',
                'sp.description',
                'sb.qty_on_hand as qty',
                'sb.avg_cost',
            ]);

        $blockers = [];
        if ((int)$loc->is_default === 1) $blockers[] = 'default_location';
        if ($hasQty) $blockers[] = 'has_stock';

        return response()->json([
            'location' => [
                'id' => (string)$loc->id,
                'name' => (string)$loc->name,
                'is_default' => (int)$loc->is_default,
            ],
            'blockers' => $blockers,
            'stats' => [
                'skus_with_qty' => (int)($stats->skus_with_qty ?? 0),
                'total_qty' => (int)($stats->total_qty ?? 0),
                'total_cost_est' => (float)($stats->total_cost_est ?? 0),
            ],
            'items' => $items,
        ]);
    }
}
