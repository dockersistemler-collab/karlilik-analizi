<?php

namespace App\Notifications;

use App\Models\SupportAccessLog;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SupportViewStartedNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly SupportAccessLog $log,
        private readonly User $superAdmin
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage())
            ->subject('Support view aktif edildi')
            ->greeting('Merhaba '.$notifiable->name.',')
            ->line('Hesabiniz destek amaciyla goruntulendi.')
            ->line('Tarih/Saat: '.$this->log->started_at?->format('d.m.Y H:i'))
            ->line('Gerekce: '.$this->log->reason)
            ->line('Yetkili: '.$this->superAdmin->name.' ('.$this->superAdmin->email.')')
            ->line('Bu erisim salt okunur sekilde gerceklestirilmistir.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'support_access_log_id' => $this->log->id,
            'started_at' => $this->log->started_at,
            'reason' => $this->log->reason,
            'super_admin_id' => $this->superAdmin->id,
            'super_admin_name' => $this->superAdmin->name,
            'super_admin_email' => $this->superAdmin->email,
        ];
    }
}
