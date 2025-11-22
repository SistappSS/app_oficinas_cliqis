<?php

namespace App\Http\Resources\Invoice;

use Illuminate\Http\Resources\Json\JsonResource;

class InvoiceResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            // sempre que vier de Invoice: 'invoice'
            'kind'          => 'invoice',

            'id'            => $this->id,
            'number'        => $this->number,
            'due_date'      => $this->due_date?->toDateString(),
            'amount'        => (float) $this->amount,
            'installments'  => (int) $this->installments,
            'status'        => $this->status,

            'is_overdue'    => (bool) (
                in_array($this->status, ['pending','overdue'], true)
                && $this->due_date
                && $this->due_date->lt(now())
            ),

            'is_recurring'    => (bool) $this->is_recurring,
            'recurring_period'=> $this->recurring_period,
            'auto_reminder'   => (bool) $this->auto_reminder,
            'sent_count'      => $this->sent_count,

            'customer' => [
                'id'   => $this->customer_id,
                'name' => $this->customer->name ?? null,
            ],

            'items' => $this->whenLoaded('items', fn() =>
            $this->items->map(fn($i) => [
                'id'          => $i->id,
                'description' => $i->description,
                'qty'         => $i->qty,
                'unit_amount' => (float) $i->unit_amount,
                'type'        => $i->type, // ex.: 'one_time', 'recurrence', etc.
                'service_id'  => $i->service_id,
            ])
            ),

            'budget' => $this->whenLoaded('budget', function () {
                $raw = $this->budget->budget_code;
                // formata no padrão #000001
                $code = $raw ? ('#' . str_pad((string)$raw, 6, '0', STR_PAD_LEFT)) : null;

                return [
                    'id'          => $this->budget->id,
                    'budget_code' => $raw,   // bruto, se precisar
                    'code'        => $code,  // pronto pra mostrar na UI
                    'created_at'  => optional($this->budget->created_at)->toDateString(),
                    'status'      => $this->budget->status,
                ];
            }),

            'created_at'     => $this->created_at,
        ];
    }
}
