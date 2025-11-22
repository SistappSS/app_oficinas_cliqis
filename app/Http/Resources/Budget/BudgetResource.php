<?php

namespace App\Http\Resources\Budget;

use Illuminate\Http\Resources\Json\JsonResource;

class BudgetResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id'           => $this->id,
            'code'         => $this->budget_code,
            'status'       => $this->status,
            'approved_at'  => $this->approved_at,
            'customer'     => [
                'id'    => $this->customer_id,
                'name'  => $this->customer->name ?? null,
                'email' => $this->customer_email ?? ($this->customer->email ?? null),
            ],
            'totals'       => [
                'signal'     => (float)$this->signal_price,
                'remaining'  => (float)$this->remaining_price,
                'total'      => (float)$this->total_budget_price,
                'discount'   => [
                    'percent' => (float)$this->discount_percent,
                    'scope'   => $this->discount_scope,
                ],
            ],
            'has_recurring'=> $this->monthlyItems()->exists() || $this->yearlyItems()->exists(),
            'created_at'   => $this->created_at,
        ];
    }
}
