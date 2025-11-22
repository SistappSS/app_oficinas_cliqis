<?php

namespace App\Http\Resources\Invoice\Subscriptions;

use Illuminate\Http\Resources\Json\JsonResource;

class SubscriptionResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id'           => $this->id,
            'name'         => $this->name,
            'amount'       => (float)$this->amount,
            'period'       => $this->period,
            'next_due_date'=> $this->next_due_date?->toDateString(),
            'auto_reminder'=> (bool)$this->auto_reminder,
            'active'       => (bool)$this->active,
            'customer'     => [
                'id'   => $this->customer_id,
                'name' => $this->customer->name ?? null,
                'email'=> $this->customer->email ?? null,
            ],
        ];
    }
}
