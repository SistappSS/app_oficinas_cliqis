<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ServiceOrderPdfMail extends Mailable
{
    public function __construct(
        public $os,
        public string $subjectText,
        public ?string $customMessage,
        public string $pdfBinary,
        public string $fileName,
    ) {}

    public function build()
    {
        return $this->subject($this->subjectText)
            ->view('mail.service_order')
            ->with(['os' => $this->os, 'customMessage' => $this->customMessage])
            ->attachData($this->pdfBinary, $this->fileName, ['mime' => 'application/pdf']);
    }
}

