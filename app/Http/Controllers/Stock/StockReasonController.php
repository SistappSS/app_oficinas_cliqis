<?php

namespace App\Http\Controllers\Stock;

use App\Http\Controllers\Controller;
use App\Models\Stock\StockMovementReason;
use App\Support\CustomerContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class StockReasonController extends Controller
{
    public function view()
    {
        return view('app.stock.settings.setting_reason_index');
    }

    public function index(Request $request)
    {
        $tenantId = CustomerContext::get();

        $q = trim((string) $request->query('q', ''));
        $active = $request->query('active', '1'); // 1|0|all
        $system = $request->query('system', 'all'); // 1|0|all

        $qry = DB::table('stock_movement_reasons')
            ->where('customer_sistapp_id', $tenantId);

        if ($q !== '') {
            $qry->where(function ($w) use ($q) {
                $w->where('code', 'like', "%{$q}%")
                    ->orWhere('label', 'like', "%{$q}%");
            });
        }

        if ($active !== 'all') {
            $qry->where('is_active', (int) $active);
        }

        if ($system !== 'all') {
            $qry->where('is_system', (int) $system);
        }

        $items = $qry
            ->orderByDesc('is_system')
            ->orderBy('label')
            ->paginate(25);

        return response()->json(['items' => $items]);
    }

    public function store(Request $request)
    {
        $tenantId = CustomerContext::get();

        $data = $request->validate([
            'label' => ['required','string','max:120'],
            'code'  => ['nullable','string','max:80','regex:/^[a-z][a-z0-9_]*$/'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $id = (string) Str::uuid();
        $now = now();

        $code = $request->input('code');
        if (!$code) {
            $code = \Illuminate\Support\Str::slug($request->label, '_');
        }

        $code = strtolower($code);

        $base = $code;
        $i = 2;
        while (StockMovementReason::where('customer_sistapp_id',$tenantId)->where('code',$code)->exists()) {
            $code = $base.'_'.$i++;
        }

        DB::table('stock_movement_reasons')->insert([
            'id' => $id,
            'customer_sistapp_id' => $tenantId,
            'code' => $code,
            'label' => $data['label'],
            'is_system' => 0,
            'is_active' => (int)($data['is_active'] ?? 1),
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        return response()->json(['id' => $id], 201);
    }

    public function update(Request $request, string $id)
    {
        $tenantId = CustomerContext::get();

        $row = DB::table('stock_movement_reasons')
            ->where('customer_sistapp_id', $tenantId)
            ->where('id', $id)
            ->first();

        abort_if(!$row, 404);

        $rules = [
            'label' => ['required', 'string', 'max:120'],
            'is_active' => ['nullable', 'boolean'],
        ];

// NÃO SYSTEM: code pode ser null → gera pelo label
        if (!(int)$row->is_system) {
            $rules['code'] = ['nullable','string','max:80','regex:/^[a-z][a-z0-9_]*$/'];
        }

        $data = $request->validate($rules);

        $payload = [
            'label' => $data['label'],
            'is_active' => (int)($data['is_active'] ?? 1),
            'updated_at' => now(),
        ];

        if (!(int)$row->is_system) {
            $code = $data['code'] ?? null;

            if (!$code) {
                $code = \Illuminate\Support\Str::slug($data['label'], '_');
            }

            $code = strtolower($code);

            // se começar com número, prefixa (pra bater regex)
            if ($code !== '' && ctype_digit(substr($code, 0, 1))) {
                $code = 'r_' . $code;
            }

            // garante único por tenant ignorando o próprio ID
            $base = $code;
            $i = 2;
            while (
            DB::table('stock_movement_reasons')
                ->where('customer_sistapp_id', $tenantId)
                ->where('code', $code)
                ->where('id', '!=', $id)
                ->exists()
            ) {
                $code = $base . '_' . $i++;
            }

            $payload['code'] = $code;
        }

        DB::table('stock_movement_reasons')
            ->where('customer_sistapp_id', $tenantId)
            ->where('id', $id)
            ->update($payload);

        return response()->json(['ok' => true]);
    }

    public function destroy(string $id)
    {
        $tenantId = CustomerContext::get();

        $row = DB::table('stock_movement_reasons')
            ->where('customer_sistapp_id', $tenantId)
            ->where('id', $id)
            ->first();

        abort_if(!$row, 404);

        if ((int)$row->is_system) {
            return response()->json(['message' => 'Motivo de sistema não pode ser removido. Apenas ativar/desativar.'], 422);
        }

        DB::table('stock_movement_reasons')
            ->where('customer_sistapp_id', $tenantId)
            ->where('id', $id)
            ->delete();

        return response()->json(['ok' => true]);
    }

    public function picklist()
    {
        $tenantId = CustomerContext::get();

        $items = DB::table('stock_movement_reasons')
            ->where('customer_sistapp_id', $tenantId)
            ->where('is_active', 1)
            ->orderByDesc('is_system')
            ->orderBy('label')
            ->get(['id', 'code', 'label', 'is_system']);

        return response()->json(['items' => $items]);
    }
}
