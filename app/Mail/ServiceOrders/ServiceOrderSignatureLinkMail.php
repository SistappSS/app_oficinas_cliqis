<?php

namespace App\Mail\ServiceOrders;

use Illuminate\Mail\Mailable;

class ServiceOrderSignatureLinkMail extends Mailable
{
    public function __construct(
        public string $order_number,
        public string $link,
        public ?string $customer_name,
        public string $expires_at,
    ) {}

    public function build()
    {
        return $this
            ->subject("Assinatura da OS {$this->order_number}")
            ->view('mail.service_orders.signature_link', [
                'order_number'   => $this->order_number,
                'link'           => $this->link,
                'customer_name'  => $this->customer_name,
                'expires_at'     => $this->expires_at,
            ]);
    }
}
