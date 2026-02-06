<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class SystemSettingsTestMail extends Mailable
{
    public function __construct(private readonly ?object $actorUser = null)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Sistem Ayarlari Test Maili'
        );
    }

    public function content(): Content
    {
        $name = $this->actorUser?->name ?: 'System';

        return new Content(
            htmlString: '<p>Test mail basariyla gonderildi.</p><p>Gonderen: '.e($name).'</p>'
        );
    }
}
