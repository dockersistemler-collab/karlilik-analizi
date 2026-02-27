<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;

class CommunicationOverdueDigestMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly Collection $threads
    ) {
    }

    public function build(): self
    {
        return $this->subject('Gecikmiş İletişim Bildirimi')
            ->view('emails.communication-overdue-digest', [
                'threads' => $this->threads,
            ]);
    }
}

