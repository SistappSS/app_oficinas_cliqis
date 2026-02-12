<?php

namespace App\Services\Stock;

use App\Models\Stock\StockBalance;
use App\Models\Stock\StockLocation;
use App\Models\Stock\StockMovement;
use App\Models\Stock\StockMovementItem;
use App\Models\Stock\StockMovementReason;
use App\Models\Stock\StockPart;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class ManualStockMovementService
{
    public function manualIn(string $tenantId, array $payload, ?string $userId = null): string
    {
        StockBootstrapper::ensure($tenantId);

        $stockPartId = (string)($payload['stock_part_id'] ?? '');
        $locationId  = (string)($payload['location_id'] ?? '');
        $qty         = (int)($payload['qty'] ?? 0);
        $unitCostIn  = (float)($payload['unit_cost'] ?? 0);

        $salePriceIn = (float)($payload['sale_price'] ?? 0);
        $markupIn    = (float)($payload['markup_percent'] ?? 0);

        $reasonCode  = (string)($payload['reason_code'] ?? 'manual_in');
        $notes       = (string)($payload['notes'] ?? '');

        if ($stockPartId === '' || $locationId === '') throw new RuntimeException('Informe item e local.');
        if ($qty <= 0) throw new RuntimeException('Quantidade inválida.');
        if ($unitCostIn < 0) throw new RuntimeException('Custo inválido.');
        if ($markupIn < 0 || $markupIn > 100) throw new RuntimeException('Margem inválida (0–100).');
        if ($salePriceIn < 0) throw new RuntimeException('Preço de venda inválido.');

        return DB::transaction(function () use (
            $tenantId, $userId,
            $stockPartId, $locationId,
            $qty, $unitCostIn, $salePriceIn, $markupIn,
            $reasonCode, $notes
        ) {
            $loc = StockLocation::where('customer_sistapp_id', $tenantId)
                ->where('id', $locationId)
                ->first();

            if (!$loc) throw new RuntimeException('Local inválido.');

            /** @var StockPart $sp */
            $sp = StockPart::where('customer_sistapp_id', $tenantId)
                ->where('id', $stockPartId)
                ->lockForUpdate()
                ->first();

            if (!$sp) throw new RuntimeException('Item do estoque inválido.');

            $reason = $this->resolveReason($tenantId, $reasonCode, 'manual_in');

            // calcula venda/margem se vier 1 dos dois
            [$salePrice, $markup] = $this->normalizeSaleAndMarkup($unitCostIn, $salePriceIn, $markupIn);

            $mv = StockMovement::create([
                'id' => (string) Str::uuid(),
                'customer_sistapp_id' => $tenantId,
                'type' => 'in',
                'reason_id' => $reason->id,
                'source_type' => 'manual',
                'source_id' => null,
                'user_id' => $userId,
                'notes' => $notes ?: null,
            ]);

            // saldo do local
            $bal = StockBalance::where('customer_sistapp_id', $tenantId)
                ->where('stock_part_id', $sp->id)
                ->where('location_id', $loc->id)
                ->lockForUpdate()
                ->first();

            if (!$bal) {
                $bal = StockBalance::create([
                    'id' => (string) Str::uuid(),
                    'customer_sistapp_id' => $tenantId,
                    'stock_part_id' => $sp->id,
                    'location_id' => $loc->id,
                    'qty_on_hand' => 0,
                    'avg_cost' => 0,
                    'min_qty' => 0,
                ]);
                $bal->refresh();
            }

            $oldQty = (int) $bal->qty_on_hand;
            $oldAvg = (float) $bal->avg_cost;

            $newQty = $oldQty + $qty;

            $newAvg = $newQty > 0
                ? (($oldQty * $oldAvg) + ($qty * $unitCostIn)) / $newQty
                : 0;

            $bal->qty_on_hand = $newQty;
            $bal->avg_cost    = round($newAvg, 4);
            $bal->save();

            StockMovementItem::create([
                'id' => (string) Str::uuid(),
                'customer_sistapp_id' => $tenantId,
                'movement_id' => $mv->id,
                'stock_part_id' => $sp->id,
                'location_id' => $loc->id,
                'code' => $sp->code,
                'description' => $sp->description ?: $sp->name,
                'ncm' => $sp->ncm,
                'qty' => $qty,
                'unit_cost' => round($unitCostIn, 4),
                'total_cost' => round($unitCostIn * $qty, 2),
                'sale_price' => round($salePrice, 2),
                'markup_percent' => round($markup, 2),
            ]);

            // atualiza defaults do item (opcional)
            $sp->last_cost = round($unitCostIn, 2);
            if ($salePrice > 0) $sp->default_sale_price = round($salePrice, 2);
            if ($markup > 0) $sp->default_markup_percent = round($markup, 2);
            $sp->save();

            // recalcula global com base nos balances (mais seguro)
            $this->recalcStockPartGlobals($tenantId, $sp->id);

            return $mv->id;
        });
    }

    public function manualOut(string $tenantId, array $payload, ?string $userId = null): string
    {
        StockBootstrapper::ensure($tenantId);

        $stockPartId = (string)($payload['stock_part_id'] ?? '');
        $locationId  = (string)($payload['location_id'] ?? '');
        $qty         = (int)($payload['qty'] ?? 0);

        $unitCostOut = (float)($payload['unit_cost'] ?? 0); // opcional (override)
        $salePriceIn = (float)($payload['sale_price'] ?? 0);
        $markupIn    = (float)($payload['markup_percent'] ?? 0);

        $reasonCode  = (string)($payload['reason_code'] ?? 'manual_out');
        $notes       = (string)($payload['notes'] ?? '');

        if ($stockPartId === '' || $locationId === '') throw new RuntimeException('Informe item e local.');
        if ($qty <= 0) throw new RuntimeException('Quantidade inválida.');
        if ($unitCostOut < 0) throw new RuntimeException('Custo inválido.');
        if ($markupIn < 0 || $markupIn > 100) throw new RuntimeException('Margem inválida (0–100).');
        if ($salePriceIn < 0) throw new RuntimeException('Preço de venda inválido.');

        return DB::transaction(function () use (
            $tenantId, $userId,
            $stockPartId, $locationId, $qty,
            $unitCostOut, $salePriceIn, $markupIn,
            $reasonCode, $notes
        ) {
            $loc = StockLocation::where('customer_sistapp_id', $tenantId)
                ->where('id', $locationId)
                ->first();

            if (!$loc) throw new RuntimeException('Local inválido.');

            /** @var StockPart $sp */
            $sp = StockPart::where('customer_sistapp_id', $tenantId)
                ->where('id', $stockPartId)
                ->lockForUpdate()
                ->first();

            if (!$sp) throw new RuntimeException('Item do estoque inválido.');

            $reason = $this->resolveReason($tenantId, $reasonCode, 'manual_out');

            $bal = StockBalance::where('customer_sistapp_id', $tenantId)
                ->where('stock_part_id', $sp->id)
                ->where('location_id', $loc->id)
                ->lockForUpdate()
                ->first();

            if (!$bal) throw new RuntimeException('Sem saldo neste local.');

            $onHand = (int) $bal->qty_on_hand;
            if ($onHand < $qty) throw new RuntimeException("Saldo insuficiente. Disponível: {$onHand}.");

            // custo padrão = avg_cost do local
            $unitCost = (float) $bal->avg_cost;

            // override (se permitido) + exige observação
            if ($unitCostOut > 0 && abs($unitCostOut - $unitCost) > 0.0001) {
                $canOverride = auth()->user()?->can('stock.override_cost_out') ?? false;
                if ($canOverride) {
                    if (trim($notes) === '') {
                        throw new RuntimeException('Para editar custo na saída, informe uma observação.');
                    }
                    $unitCost = $unitCostOut;
                }
                // se não pode, ignora e segue avg_cost
            }

            // venda/margem (default do stock_part, mas pode vir override)
            $saleBase = $salePriceIn > 0 ? $salePriceIn : (float) $sp->default_sale_price;
            $mkBase   = $markupIn > 0 ? $markupIn : (float) $sp->default_markup_percent;
            [$salePrice, $markup] = $this->normalizeSaleAndMarkup($unitCost, $saleBase, $mkBase);

            $mv = StockMovement::create([
                'id' => (string) Str::uuid(),
                'customer_sistapp_id' => $tenantId,
                'type' => 'out',
                'reason_id' => $reason->id,
                'source_type' => 'manual',
                'source_id' => null,
                'user_id' => $userId,
                'notes' => $notes ?: null,
            ]);

            // saída: baixa só qty (não mexe avg_cost)
            $bal->qty_on_hand = $onHand - $qty;
            $bal->save();

            StockMovementItem::create([
                'id' => (string) Str::uuid(),
                'customer_sistapp_id' => $tenantId,
                'movement_id' => $mv->id,
                'stock_part_id' => $sp->id,
                'location_id' => $loc->id,
                'code' => $sp->code,
                'description' => $sp->description ?: $sp->name,
                'ncm' => $sp->ncm,
                'qty' => $qty,
                'unit_cost' => round($unitCost, 4),
                'total_cost' => round($unitCost * $qty, 2),
                'sale_price' => round($salePrice, 2),
                'markup_percent' => round($markup, 2),
            ]);

            // recalcula global com base nos balances
            $this->recalcStockPartGlobals($tenantId, $sp->id);

            return $mv->id;
        });
    }

    private function resolveReason(string $tenantId, string $requested, string $fallback): StockMovementReason
    {
        $code = trim($requested) !== '' ? trim($requested) : $fallback;

        $r = StockMovementReason::where('customer_sistapp_id', $tenantId)
            ->where('code', $code)
            ->where('is_active', 1)
            ->first();

        if ($r) return $r;

        $r = StockMovementReason::where('customer_sistapp_id', $tenantId)
            ->where('code', $fallback)
            ->where('is_active', 1)
            ->first();

        if (!$r) throw new RuntimeException('Motivo de movimentação inválido.');
        return $r;
    }

    private function normalizeSaleAndMarkup(float $unitCost, float $salePrice, float $markup): array
    {
        $salePrice = max(0, (float)$salePrice);
        $markup = max(0, min(100, (float)$markup));

        if ($unitCost > 0) {
            if ($salePrice <= 0 && $markup > 0) {
                $salePrice = $unitCost * (1 + ($markup / 100));
            } elseif ($markup <= 0 && $salePrice > 0) {
                $markup = (($salePrice / $unitCost) - 1) * 100;
                $markup = max(0, min(100, $markup));
            }
        }

        return [$salePrice, $markup];
    }

    private function recalcStockPartGlobals(string $tenantId, string $stockPartId): void
    {
        $agg = StockBalance::where('customer_sistapp_id', $tenantId)
            ->where('stock_part_id', $stockPartId)
            ->selectRaw('SUM(qty_on_hand) as qty, SUM(qty_on_hand * avg_cost) as total_cost')
            ->first();

        $qty = (int) ($agg->qty ?? 0);
        $totalCost = (float) ($agg->total_cost ?? 0);

        $avg = $qty > 0 ? ($totalCost / $qty) : 0;

        StockPart::where('customer_sistapp_id', $tenantId)
            ->where('id', $stockPartId)
            ->update([
                'qty_on_hand_global' => $qty,
                'avg_cost_global' => round($avg, 4),
            ]);
    }
}
