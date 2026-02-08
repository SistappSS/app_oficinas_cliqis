<?php

namespace App\Http\Controllers\Stock;

use App\Http\Controllers\Controller;
use App\Models\Stock\StockLocation;
use App\Models\Stock\StockPart;
use App\Support\CustomerContext;
use Illuminate\Http\Request;
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

        $q = trim((string)$request->query('q', ''));
        $locationId = (string)$request->query('location_id', '');
        $onlyActive = (int)$request->query('active', 1) === 1;

        // locations (pra filtro)
        $locations = StockLocation::where('customer_sistapp_id', $tenantId)
            ->orderByDesc('is_default')
            ->orderBy('name')
            ->get(['id','name','is_default']);

        // base
        $query = StockPart::query()
            ->where('customer_sistapp_id', $tenantId);

        if ($onlyActive) {
            $query->where('is_active', 1);
        }

        if ($q !== '') {
            $query->where(function ($w) use ($q) {
                $w->where('code', 'like', "%{$q}%")
                    ->orWhere('name', 'like', "%{$q}%")
                    ->orWhere('description', 'like', "%{$q}%");
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
                'stock_parts.*',
                DB::raw('COALESCE(sb.qty_on_hand, 0) as qty_location'),
                DB::raw('COALESCE(sb.avg_cost, 0) as avg_cost_location'),
                DB::raw('COALESCE(sb.min_qty, 0) as min_qty_location'),
            ]);
        } else {
            $query->addSelect('stock_parts.*');
        }

        $items = $query
            ->orderBy('code')
            ->paginate(25);

        return response()->json([
            'locations' => $locations,
            'items' => $items,
        ]);
    }
}
