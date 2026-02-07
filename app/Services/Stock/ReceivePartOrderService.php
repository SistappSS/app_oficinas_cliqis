<?php

namespace App\Services\Stock;

use App\Models\PartOrder;
use App\Models\PartOrderItem;
use App\Models\Stock\StockBalance;
use App\Models\Stock\StockLocation;
use App\Models\Stock\StockMovement;
use App\Models\Stock\StockMovementItem;
use App\Models\Stock\StockMovementReason;
use App\Models\Stock\StockPart;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class ReceivePartOrderService
{
    public function receive(PartOrder $order, array $payload, ?string $userId = null): string
    {
        $tenantId = (string) $order->customer_sistapp_id;

        StockBootstrapper::ensure($tenantId);

        $mode = (string)($payload['mode'] ?? 'total');
        if (!in_array($mode, ['total', 'partial'], true)) {
            throw new RuntimeException('Modo inválido.');
        }

        // status operacional permitido pra receber
        if (!in_array($order->status, ['open', 'pending', 'partial'], true)) {
            throw new RuntimeException('Pedido não pode receber entrada neste status.');
        }

        $order->loadMissing(['items','items.part']);

        $locCount  = StockLocation::where('customer_sistapp_id', $tenantId)->count();
        $mustSplit = $locCount > 1;

        return DB::transaction(function () use ($tenantId, $order, $payload, $mode, $userId, $mustSplit) {

            $reason = StockMovementReason::where('customer_sistapp_id', $tenantId)
                ->where('code', 'receive_part_order')
                ->first();

            if (!$reason) {
                throw new RuntimeException('Motivo receive_part_order não encontrado (bootstrap incompleto).');
            }

            $defaultLoc = StockLocation::where('customer_sistapp_id', $tenantId)
                ->where('is_default', true)
                ->first();

            if (!$defaultLoc) {
                throw new RuntimeException('Local padrão não encontrado.');
            }

            // Mapa itens do pedido (pra validação rápida)
            $byId = $order->items->keyBy('id');

            // Payload vindo do front
            $itemsPayload = $payload['items'] ?? [];

            // TOTAL = cria payload pra todos com qty = remaining (permite override de preço/margem/locais)
            if ($mode === 'total') {
                $overrides = collect($itemsPayload)
                    ->filter(fn ($x) => !empty($x['part_order_item_id']))
                    ->keyBy(fn ($x) => (string)$x['part_order_item_id'])
                    ->all();

                $itemsPayload = $order->items->map(function ($it) use ($overrides) {
                    $qtyInt = (int) $it->quantity;
                    $remaining = max(0, $qtyInt - (int)$it->received_qty);

                    $ov = $overrides[(string)$it->id] ?? [];

                    return [
                        'part_order_item_id' => (string)$it->id,
                        'qty' => $remaining,
                        'sale_price' => $ov['sale_price'] ?? 0,
                        'markup_percent' => $ov['markup_percent'] ?? 0,
                        'locations' => $ov['locations'] ?? [],
                    ];
                })->values()->all();
            }

            // Filtra somente itens com algo >0 (qty ou locations)
            $itemsPayload = array_values(array_filter($itemsPayload, function ($row) {
                $qty = (int)($row['qty'] ?? 0);
                if ($qty > 0) return true;

                $locs = $row['locations'] ?? [];
                if (is_array($locs)) {
                    foreach ($locs as $l) {
                        if ((int)($l['qty'] ?? 0) > 0) return true;
                    }
                }
                return false;
            }));

            if (count($itemsPayload) === 0) {
                throw new RuntimeException('Nada para dar entrada.');
            }

            $movement = StockMovement::create([
                'id' => (string) Str::uuid(),
                'customer_sistapp_id' => $tenantId,
                'type' => 'in',
                'reason_id' => $reason->id,
                'source_type' => 'part_order',
                'source_id' => $order->id,
                'user_id' => $userId,
                'notes' => null,
            ]);

            foreach ($itemsPayload as $row) {
                $poiId = (string)($row['part_order_item_id'] ?? '');
                if (!$poiId) continue;

                /** @var PartOrderItem|null $poiMem */
                $poiMem = $byId->get($poiId);
                if (!$poiMem) throw new RuntimeException('Item do pedido inválido.');

                // lock do item pra concorrência
                /** @var PartOrderItem $poi */
                $poi = PartOrderItem::where('customer_sistapp_id', $tenantId)
                    ->where('part_order_id', $order->id)
                    ->where('id', $poiId)
                    ->lockForUpdate()
                    ->firstOrFail();

                $qtyOrderInt = (int) $poi->quantity;
                $remaining = max(0, $qtyOrderInt - (int)$poi->received_qty);

                if ($remaining <= 0) {
                    continue; // já recebeu tudo
                }

                $code = trim((string)($poi->code ?? ''));
                if ($code === '') throw new RuntimeException('Item sem code não pode entrar no estoque.');

                // ===== define qty real (aceita qty via locations) =====
                $locs = $row['locations'] ?? [];
                $sumLoc = 0;

                if (is_array($locs)) {
                    $sumLoc = array_sum(array_map(fn($x) => (int)($x['qty'] ?? 0), $locs));
                }

                $qty = (int)($row['qty'] ?? 0);

                // se veio distribuição por locais e qty não veio, inferimos pelo somatório
                if ($qty <= 0 && $sumLoc > 0) {
                    $qty = $sumLoc;
                }

                if ($qty <= 0) continue;
                if ($qty > $remaining) {
                    throw new RuntimeException("Quantidade maior que o restante no item {$code}.");
                }

                // ===== custo unitário =====
                $lineTotal = (float)($poi->line_total ?? 0);
                $unitCost = ($qtyOrderInt > 0 && $lineTotal > 0)
                    ? ($lineTotal / $qtyOrderInt)
                    : (float)($poi->unit_price ?? 0);

                if ($unitCost < 0) $unitCost = 0;

                // ===== venda/margem (opcional) =====
                $salePrice = (float)($row['sale_price'] ?? 0);
                $markup    = (float)($row['markup_percent'] ?? 0);

                if ($salePrice < 0) throw new RuntimeException('Preço de venda inválido.');
                if ($markup < 0 || $markup > 100) throw new RuntimeException('Margem inválida (0–100).');

                // se vier um, calcula o outro
                if ($salePrice <= 0 && $markup > 0 && $unitCost > 0) {
                    $salePrice = $unitCost * (1 + ($markup / 100));
                } elseif ($markup <= 0 && $salePrice > 0 && $unitCost > 0) {
                    $markup = (($salePrice / $unitCost) - 1) * 100;
                }

                // ===== stock_part (unificado por code + tenant) =====
                $sp = StockPart::where('customer_sistapp_id', $tenantId)
                    ->where('code', $code)
                    ->lockForUpdate()
                    ->first();

                if (!$sp) {
                    $sp = StockPart::create([
                        'id' => (string) Str::uuid(),
                        'customer_sistapp_id' => $tenantId,
                        'part_id' => $poi->part_id,
                        'code' => $code,
                        'name' => $poi->description,
                        'description' => $poi->description,
                        'ncm' => $poi->ncm,
                        'qty_on_hand_global' => 0,
                        'avg_cost_global' => 0,
                        'last_cost' => 0,
                        'default_sale_price' => 0,
                        'default_markup_percent' => 0,
                        'is_active' => true,
                    ]);
                } else {
                    if ($poi->part_id && !$sp->part_id) $sp->part_id = $poi->part_id;
                    if (!$sp->description && $poi->description) $sp->description = $poi->description;
                    if (!$sp->ncm && $poi->ncm) $sp->ncm = $poi->ncm;
                    if (!$sp->name && $poi->description) $sp->name = $poi->description;
                }

                // ===== locais =====
                if ($mustSplit) {
                    // obrigatório distribuir
                    if (!is_array($locs) || $sumLoc <= 0) {
                        throw new RuntimeException("Selecione destino por local (item {$code}).");
                    }
                    if ($sumLoc !== $qty) {
                        throw new RuntimeException("Distribuição por locais não fecha no item {$code}.");
                    }

                    $locIds = collect($locs)->pluck('location_id')->filter()->unique()->values()->all();
                    $validCount = StockLocation::where('customer_sistapp_id', $tenantId)
                        ->whereIn('id', $locIds)
                        ->count();

                    if ($validCount !== count($locIds)) {
                        throw new RuntimeException("Local inválido na distribuição do item {$code}.");
                    }
                } else {
                    // se não veio locais, joga no default
                    if (!is_array($locs) || count($locs) === 0) {
                        $locs = [['location_id' => $defaultLoc->id, 'qty' => $qty]];
                        $sumLoc = $qty;
                    } else {
                        if ($sumLoc !== $qty) {
                            // se o user não preencheu qty e só preencheu locais, você já inferiu qty acima.
                            // aqui fica só a validação final.
                            throw new RuntimeException("Distribuição por locais não fecha no item {$code}.");
                        }
                    }
                }

                // ===== aplica por local (saldo + movimento) =====
                foreach ($locs as $l) {
                    $locId = (string)($l['location_id'] ?? '');
                    $qLoc  = (int)($l['qty'] ?? 0);
                    if ($locId === '' || $qLoc <= 0) continue;

                    $bal = StockBalance::where('customer_sistapp_id', $tenantId)
                        ->where('stock_part_id', $sp->id)
                        ->where('location_id', $locId)
                        ->lockForUpdate()
                        ->first();

                    if (!$bal) {
                        $bal = StockBalance::create([
                            'id' => (string) Str::uuid(),
                            'customer_sistapp_id' => $tenantId,
                            'stock_part_id' => $sp->id,
                            'location_id' => $locId,
                            'qty_on_hand' => 0,
                            'avg_cost' => 0,
                            'min_qty' => 0,
                        ]);
                    }

                    $oldQty = (int)$bal->qty_on_hand;
                    $oldAvg = (float)$bal->avg_cost;
                    $newQty = $oldQty + $qLoc;

                    $newAvg = $newQty > 0
                        ? (($oldQty * $oldAvg) + ($qLoc * $unitCost)) / $newQty
                        : 0;

                    $bal->qty_on_hand = $newQty;
                    $bal->avg_cost = round($newAvg, 4);
                    $bal->save();

                    StockMovementItem::create([
                        'id' => (string) Str::uuid(),
                        'customer_sistapp_id' => $tenantId,
                        'movement_id' => $movement->id,
                        'stock_part_id' => $sp->id,
                        'location_id' => $locId,
                        'code' => $code,
                        'description' => $poi->description,
                        'ncm' => $poi->ncm,
                        'qty' => $qLoc,
                        'unit_cost' => round($unitCost, 4),
                        'total_cost' => round($unitCost * $qLoc, 2),
                        'sale_price' => round($salePrice, 2),
                        'markup_percent' => round($markup, 2),
                    ]);
                }

                // ===== atualiza globais no stock_part =====
                $gOldQty = (int)$sp->qty_on_hand_global;
                $gOldAvg = (float)$sp->avg_cost_global;
                $gNewQty = $gOldQty + $qty;

                $gNewAvg = $gNewQty > 0
                    ? (($gOldQty * $gOldAvg) + ($qty * $unitCost)) / $gNewQty
                    : 0;

                $sp->qty_on_hand_global = $gNewQty;
                $sp->avg_cost_global = round($gNewAvg, 4);
                $sp->last_cost = round($unitCost, 2);

                if ($salePrice > 0) $sp->default_sale_price = round($salePrice, 2);
                if ($markup > 0) $sp->default_markup_percent = round($markup, 2);

                $sp->save();

                // ===== marca recebido =====
                $poi->received_qty = (int)$poi->received_qty + $qty;
                if ($poi->received_qty > $qtyOrderInt) $poi->received_qty = $qtyOrderInt;
                $poi->save();
            }

            // status final do pedido
            $order->refresh();
            $order->loadMissing('items');

            $allReceived = $order->items->every(function ($it) {
                $qInt = (int)$it->quantity;
                return (int)$it->received_qty >= $qInt;
            });

            $order->status = $allReceived ? 'completed' : 'partial';
            $order->save();

            return $movement->id;
        });
    }
}
