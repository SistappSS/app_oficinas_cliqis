<?php

namespace App\Http\Controllers\Stock;

use App\Http\Controllers\Controller;
use App\Models\Stock\StockMovement;
use App\Services\Stock\ManualStockMovementService;
use App\Support\TenantUser\CustomerContext;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class MovementStockController extends Controller
{
    public function view()
    {
        return view('app.stock.movements.movement_index');
    }

    public function movementsData(Request $request)
    {
        $tenantId = CustomerContext::get();

        $q = trim((string)$request->query('q', ''));
        $type = (string)$request->query('type', '');
        $stockPartId = (string)$request->query('stock_part_id', '');

        $period = (string)$request->query('period', '');      // '7' | '30' | 'custom'
        $dateFrom = (string)$request->query('date_from', ''); // YYYY-MM-DD
        $dateTo   = (string)$request->query('date_to', '');   // YYYY-MM-DD

        $from = null;
        $to = null;

        if ($period === '7' || $period === '30') {
            $days = (int)$period;
            $from = now()->subDays($days)->startOfDay();
            $to   = now()->endOfDay();
        } elseif ($period === 'custom') {
            if ($dateFrom !== '') $from = Carbon::parse($dateFrom)->startOfDay();
            if ($dateTo !== '')   $to   = Carbon::parse($dateTo)->endOfDay();

            if ($from && $to && $from->gt($to)) {
                [$from, $to] = [$to, $from];
            }
        }

        $agg = DB::table('stock_movement_items as i')
            ->select([
                'i.movement_id',
                DB::raw('SUM(i.qty) as total_qty'),
                DB::raw('SUM(i.total_cost) as total_cost'),
            ])
            ->where('i.customer_sistapp_id', $tenantId)
            ->groupBy('i.movement_id');

        $mov = DB::table('stock_movements as m')
            ->where('m.customer_sistapp_id', $tenantId)
            ->leftJoinSub($agg, 'a', function ($j) {
                $j->on('a.movement_id', '=', 'm.id');
            })
            ->leftJoin('stock_movement_reasons as r', function ($j) use ($tenantId) {
                $j->on('r.id', '=', 'm.reason_id')
                    ->where('r.customer_sistapp_id', '=', $tenantId);
            })
            ->leftJoin('users as u', 'u.id', '=', 'm.user_id');

        if ($type !== '') {
            $mov->where('m.type', $type);
        }

        if ($q !== '') {
            $mov->whereExists(function ($sub) use ($tenantId, $q) {
                $sub->select(DB::raw(1))
                    ->from('stock_movement_items as i2')
                    ->whereColumn('i2.movement_id', 'm.id')
                    ->where('i2.customer_sistapp_id', $tenantId)
                    ->where(function ($w) use ($q) {
                        $w->where('i2.code', 'like', "%{$q}%")
                            ->orWhere('i2.description', 'like', "%{$q}%");
                    });
            });
        }

        if ($stockPartId !== '') {
            $mov->where(function ($w) use ($tenantId, $stockPartId) {
                $w->whereExists(function ($sub) use ($tenantId, $stockPartId) {
                    $sub->select(DB::raw(1))
                        ->from('stock_movement_items as i')
                        ->whereColumn('i.movement_id', 'm.id')
                        ->where('i.customer_sistapp_id', $tenantId)
                        ->where('i.stock_part_id', $stockPartId);
                })
                    // ✅ fallback: movimentos antigos/sem item-âncora
                    ->orWhereRaw("JSON_UNQUOTE(JSON_EXTRACT(m.meta, '$.stock_part_id')) = ?", [$stockPartId]);
            });
        }

        // aplica filtro de período
        if ($from && $to) {
            $mov->whereBetween('m.created_at', [$from, $to]);
        } elseif ($from) {
            $mov->where('m.created_at', '>=', $from);
        } elseif ($to) {
            $mov->where('m.created_at', '<=', $to);
        }

        $rows = $mov
            ->orderByDesc('m.created_at')
            ->select([
                'm.id',
                'm.created_at',
                'm.type',
                'm.source_type',
                'm.source_id',
                'm.user_id',
                'm.meta',
                DB::raw('COALESCE(u.name, "") as user_name'),
                DB::raw('COALESCE(r.label, "") as reason_label'),
                DB::raw('COALESCE(a.total_qty, 0) as total_qty'),
                DB::raw('COALESCE(a.total_cost, 0) as total_cost'),
            ])
            ->paginate(5);

        $rows->getCollection()->transform(function ($r) {
            $meta = [];
            if (!empty($r->meta)) {
                $decoded = json_decode($r->meta, true);
                if (is_array($decoded)) $meta = $decoded;
            }

            $r->changes = $meta['changes'] ?? null;
            $r->changes_summary = $meta['changes_summary'] ?? '';

            return $r;
        });

        return response()->json(['items' => $rows]);
    }

    public function show(string $id)
    {
        $tenant = CustomerContext::get();

        $mv = StockMovement::query()
            ->where('customer_sistapp_id', $tenant)
            ->with([
                'reason:id,code,label',
                'user:id,name',
                'items:id,movement_id,location_id,code,description,ncm,qty,unit_cost,total_cost',
                'items.location:id,name',
            ])
            ->findOrFail($id);

        $totalQty  = (int) $mv->items->sum('qty');
        $totalCost = (float) $mv->items->sum('total_cost');

        return response()->json([
            'movement' => [
                'id' => (string) $mv->id,
                'type' => (string) $mv->type,
                'source_type' => (string) $mv->source_type,
                'source_id' => (string) $mv->source_id,
                'created_at' => optional($mv->created_at)?->toDateTimeString(),
                'notes' => (string) ($mv->notes ?? ''),
                'reason' => $mv->reason ? [
                    'code' => (string) $mv->reason->code,
                    'label' => (string) $mv->reason->label,
                ] : null,
                'user' => $mv->user ? [
                    'id' => (string) $mv->user->id,
                    'name' => (string) $mv->user->name,
                ] : null,
            ],
            'totals' => [
                'qty' => $totalQty,
                'cost' => $totalCost,
            ],
            'items' => $mv->items->map(fn ($it) => [
                'location' => $it->location ? [
                    'id' => (string) $it->location->id,
                    'name' => (string) $it->location->name,
                ] : null,
                'code' => (string) $it->code,
                'description' => (string) ($it->description ?? ''),
                'qty' => (int) $it->qty,
                'unit_cost' => (float) $it->unit_cost,
                'total_cost' => (float) $it->total_cost,
            ])->values(),
        ]);
    }

    public function manualIn(Request $request, ManualStockMovementService $svc)
    {
        $tenantId = CustomerContext::get();

        $v = Validator::make($request->all(), [
            'stock_part_id' => ['required','string'],
            'location_id'   => ['required','string'],
            'qty'           => ['required','integer','min:1'],
            'unit_cost'     => ['required','numeric','min:0'],

            'sale_price'      => ['nullable','numeric','min:0'],
            'markup_percent'  => ['nullable','numeric','min:0','max:100'],
            'reason_code'     => ['nullable','string'],
            'notes'           => ['nullable','string','max:1000'],
        ]);

        if ($v->fails()) {
            return response()->json(['message'=>'Dados inválidos.','errors'=>$v->errors()], 422);
        }

        try {
            $id = $svc->manualIn($tenantId, $v->validated(), auth()->id());
            return response()->json(['ok'=>true,'movement_id'=>$id]);
        } catch (\Throwable $e) {
            return response()->json(['message'=>$e->getMessage() ?: 'Falha.'], 422);
        }
    }

    public function manualOut(Request $request, ManualStockMovementService $svc)
    {
        $tenantId = CustomerContext::get();

        $v = Validator::make($request->all(), [
            'stock_part_id' => ['required','string'],
            'location_id'   => ['required','string'],
            'qty'           => ['required','integer','min:1'],

            // opcional: override
            'unit_cost'     => ['nullable','numeric','min:0'],

            'sale_price'      => ['nullable','numeric','min:0'],
            'markup_percent'  => ['nullable','numeric','min:0','max:100'],
            'reason_code'     => ['nullable','string'],
            'notes'           => ['nullable','string','max:1000'],
        ]);

        if ($v->fails()) {
            return response()->json(['message'=>'Dados inválidos.','errors'=>$v->errors()], 422);
        }

        try {
            $id = $svc->manualOut($tenantId, $v->validated(), auth()->id());
            return response()->json(['ok'=>true,'movement_id'=>$id]);
        } catch (\Throwable $e) {
            return response()->json(['message'=>$e->getMessage() ?: 'Falha.'], 422);
        }
    }
}
