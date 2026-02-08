<?php

namespace App\Http\Controllers\Stock;

use App\Http\Controllers\Controller;
use App\Models\Stock\StockMovement;
use App\Support\CustomerContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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

        $mov = DB::table('stock_movements as m')
            ->where('m.customer_sistapp_id', $tenantId);

        if ($type !== '') {
            $mov->where('m.type', $type);
        }

        if ($q !== '') {
            $mov->whereExists(function ($sub) use ($tenantId, $q) {
                $sub->select(DB::raw(1))
                    ->from('stock_movement_items as i')
                    ->whereColumn('i.movement_id', 'm.id')
                    ->where('i.customer_sistapp_id', $tenantId)
                    ->where(function ($w) use ($q) {
                        $w->where('i.code', 'like', "%{$q}%")
                            ->orWhere('i.description', 'like', "%{$q}%");
                    });
            });
        }

        $rows = $mov
            ->orderByDesc('m.created_at')
            ->paginate(25);

        return response()->json(['items' => $rows]);
    }

    public function show(string $id)
    {
        $tenant = CustomerContext::get();

        $mv = StockMovement::query()
            ->where('customer_sistapp_id', $tenant)
            ->with([
                'reason:id,code,label',
                'items:id,movement_id,location_id,code,description,ncm,qty,unit_cost,total_cost,sale_price,markup_percent',
                'items.location:id,name',
                // se tiver relação user no model:
                // 'user:id,name',
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
                // 'user' => $mv->user ? ['id'=>(string)$mv->user->id,'name'=>(string)$mv->user->name] : null,
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
}
