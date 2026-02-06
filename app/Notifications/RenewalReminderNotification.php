<?php

namespace App\Notifications;

use App\Models\ModulePurchase;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class RenewalReminderNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly ModulePurchase $purchase,
        public readonly int $daysLeft,
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $moduleName = $this->purchase->module?->name ?? 'Modül';
        $endsAt = $this->purchase->ends_at?->timezone('Europe/Istanbul')?->format('d.m.Y');

        return (new MailMessage())
            ->subject("{$moduleName} yenileme hatırlatması")
            ->greeting('Merhaba,')
            ->line("{$moduleName} modülünüzün süresi {$this->daysLeft} gün içinde dolacak.")
            ->when($endsAt, fn (MailMessage $m) => $m->line("Bitiş tarihi: {$endsAt}"))
            ->action('Modülü Yenile', route('portal.modules.mine'))
            ->line('Teşekkürler.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'renewal',
            'days_left' => $this->daysLeft,
            'purchase_id' => $this->purchase->id,
            'module_id' => $this->purchase->module_id,
            'module_code' => $this->purchase->module?->code, 'module_name' => $this->purchase->module?->name, 'ends_at' => $this->purchase->ends_at?->toISOString(), 'default_period' => 'monthly',
            'renew_url' => route('portal.modules.mine'),
        ];
    }
}


