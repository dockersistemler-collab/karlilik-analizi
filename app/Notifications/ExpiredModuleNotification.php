<?php

namespace App\Notifications;

use App\Models\ModulePurchase;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ExpiredModuleNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly ModulePurchase $purchase,
        public readonly int $daysAgo,
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
            ->subject("{$moduleName} süresi doldu")
            ->greeting('Merhaba,')
            ->line("{$moduleName} modülünüzün süresi {$this->daysAgo} gün önce doldu.")
            ->when($endsAt, fn (MailMessage $m) => $m->line("Bitiş tarihi: {$endsAt}"))
            ->action('Tekrar Satın Al / Yenile', route('portal.modules.upsell', ['code' => $this->purchase->module?->code ?? '']))
            ->line('Teşekkürler.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'expired',
            'days_ago' => $this->daysAgo,
            'purchase_id' => $this->purchase->id,
            'module_id' => $this->purchase->module_id,
            'module_code' => $this->purchase->module?->code, 'module_name' => $this->purchase->module?->name, 'ends_at' => $this->purchase->ends_at?->toISOString(),
        ];
    }
}



