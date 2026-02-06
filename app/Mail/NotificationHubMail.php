<?php

namespace App\Mail;

use App\Models\Notification;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class NotificationHubMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public readonly Notification $notification)
    {
    }

    public function build(): self
    {
        return $this->subject($this->notification->title)
            ->view('emails.notification-hub', [
                'notification' => $this->notification,
            ]);
    }
}