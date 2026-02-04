<?php

namespace App\Mail\PartOrders;

use App\Models\PartOrder;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class PartOrderToSupplierMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public PartOrder $order,
        public string $subjectText,
        public string $bodyText,
        public string $pdfContent
    ) {}

    public function build()
    {
        $filename = 'Proposta-' . ($this->order->order_number ?? 'pedido') . '.pdf';

        return $this->subject($this->subjectText)
            ->view('mail.part_orders.part_order_to_supplier', [
                'order' => $this->order,
                'bodyText' => $this->bodyText,
            ])
            ->attachData($this->pdfContent, $filename, ['mime' => 'application/pdf']);
    }
}
