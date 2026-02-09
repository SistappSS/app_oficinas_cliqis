<?php

namespace App\Http\Controllers\Stock;

use App\Http\Controllers\Controller;
use App\Models\Stock\StockBalance;
use App\Models\Stock\StockLocation;
use App\Models\Stock\StockMovementReason;
use App\Models\Stock\StockPart;
use App\Support\CustomerContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

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
        $locationId = (string) $request->query('location_id', '');
        $onlyActive = (int) $request->query('active', 1) === 1;

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

        // se filtrar por local: puxa saldo daquele local
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
            ]);
        }

        $items = $query
            ->orderBy('stock_parts.code')
            ->paginate(25);

        return response()->json([
            'locations' => $locations,
            'items' => $items,
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

            return [
                'id' => (string) $l->id,
                'name' => (string) $l->name,
                'is_default' => (bool) $l->is_default,
                'qty_on_hand' => (int) ($b->qty_on_hand ?? 0),
                'avg_cost' => (float) ($b->avg_cost ?? 0),
                'min_qty' => (int) ($b->min_qty ?? 0),
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
}
