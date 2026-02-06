<?php

namespace Database\Seeders;

use App\Models\MailTemplate;
use Illuminate\Database\Seeder;

class MailTemplateSeeder extends Seeder
{
    public function run(): void
    {
        MailTemplate::updateOrCreate(
            ['key' => 'security.support_view_used'],
            [
                'channel' => 'email',
                'category' => 'security',
                'subject' => 'Hesabınız destek amacıyla görüntülendi',
                'body_html' => '<p>Merhaba {{user_name}},</p><p>Hesabınız, destek talebiniz kapsamında görüntülendi.</p><ul>  <li><strong>Destek Temsilcisi:</strong> {{admin_name}}</li>  <li><strong>Başlangıç:</strong> {{started_at}}</li>  <li><strong>Neden:</strong> {{reason}}</li>  <li><strong>Kayıt ID:</strong> {{log_id}}</li></ul><p>Bu işlem yalnızca görüntüleme amaçlıdır ve kayıt altına alınır.</p>',
                'enabled' => true,
            ]
        );

        MailTemplate::updateOrCreate(
            ['key' => 'mp.connection_lost'],
            [
                'channel' => 'email',
                'category' => 'marketplace',
                'subject' => '{{marketplace}} bağlantınız koptu — yeniden bağlayın',
                'body_html' => '<p>Merhaba {{user_name}},</p><p>{{marketplace}} mağaza bağlantınız ({{store_id}}) şu anda kullanılamıyor.</p><ul>  <li><strong>Neden:</strong> {{reason}}</li>  <li><strong>Zaman:</strong> {{occurred_at}}</li></ul><p>Sipariş, stok ve fiyat senkronizasyonu durabilir. Lütfen bağlantıyı yeniden doğrulayın.</p><p>Yardıma ihtiyacınız olursa destek ekibimize yazabilirsiniz.</p>',
                'enabled' => true,
            ]
        );

        MailTemplate::updateOrCreate(
            ['key' => 'payment.failed'],
            [
                'channel' => 'email',
                'category' => 'billing',
                'subject' => 'Ödeme başarısız — ödeme yönteminizi güncelleyin',
                'body_html' => '<p>Merhaba {{user_name}},</p><p>Ödeme işlemi tamamlanamadı.</p><ul>  <li><strong>Tutar:</strong> {{amount}} {{currency}}</li>  <li><strong>Hata:</strong> {{error_message}}</li></ul><p><a href="{{retry_url}}">Ödemeyi tekrar dene</a></p><p><a href="{{billing_settings_url}}">Ödeme ayarlarını güncelle</a></p><p>Devam ederse destek ekibimize yazabilirsiniz.</p>',
                'enabled' => true,
            ]
        );

        MailTemplate::updateOrCreate(
            ['key' => 'quota.exceeded'],
            [
                'channel' => 'email',
                'category' => 'usage',
                'subject' => 'Kotanız doldu — {{quota_key}}',
                'body_html' => '<p>Merhaba {{user_name}},</p><p>{{quota_key}} kotanız doldu.</p><ul>  <li><strong>Kullanım:</strong> {{used}} / {{limit}} {{period}}</li>  <li><strong>Sıfırlanma:</strong> {{reset_at}}</li></ul><p>Kesinti yaşamamak için paketinizi yükseltebilirsiniz.</p><p><a href="{{pricing_url}}">Paketleri gör</a></p>',
                'enabled' => true,
            ]
        );

        MailTemplate::updateOrCreate(
            ['key' => 'invoice.created'],
            [
                'channel' => 'email',
                'category' => 'billing',
                'subject' => 'Faturanız hazır — {{invoice_number}}',
                'body_html' => '<p>Merhaba {{user_name}},</p><p>Faturanız oluşturuldu.</p><ul>  <li><strong>Fatura No:</strong> {{invoice_number}}</li>  <li><strong>Tutar:</strong> {{total_amount}} {{currency}}</li>  <li><strong>Pazaryeri:</strong> {{marketplace}}</li>  <li><strong>Sipariş:</strong> {{order_id}}</li></ul><p><a href="{{invoice_url}}">Faturayı görüntüle</a></p><p>Sorunuz olursa destek ekibimize yazabilirsiniz.</p>',
                'enabled' => true,
            ]
        );

        MailTemplate::updateOrCreate(
            ['key' => 'invoice.failed'],
            [
                'channel' => 'email',
                'category' => 'billing',
                'subject' => 'Fatura oluşturulamadı — işlem gerekli',
                'body_html' => '<p>Merhaba {{user_name}},</p><p>Fatura oluşturma işlemi tamamlanamadı.</p><ul>  <li><strong>Pazaryeri:</strong> {{marketplace}}</li>  <li><strong>Sipariş:</strong> {{order_id}}</li>  <li><strong>Hata:</strong> {{error_message}}</li></ul><p><a href="{{retry_url}}">Tekrar dene</a></p><p><a href="{{support_url}}">Destek</a></p>',
                'enabled' => true,
            ]
        );

        MailTemplate::updateOrCreate(
            ['key' => 'subscription.started'],
            [
                'channel' => 'email',
                'category' => 'billing',
                'subject' => 'Aboneliğiniz başladı — {{plan_name}}',
                'body_html' => '<p>Merhaba {{user_name}},</p><p>Aboneliğiniz başarıyla başlatıldı.</p><ul>  <li><strong>Paket:</strong> {{plan_name}}</li>  <li><strong>Başlangıç:</strong> {{started_at}}</li>  <li><strong>Bitiş:</strong> {{ends_at}}</li></ul><p><a href="{{panel_url}}">Paneli aç</a></p>',
                'enabled' => true,
            ]
        );

        MailTemplate::updateOrCreate(
            ['key' => 'subscription.renewed'],
            [
                'channel' => 'email',
                'category' => 'billing',
                'subject' => 'Aboneliğiniz yenilendi — {{plan_name}}',
                'body_html' => '<p>Merhaba {{user_name}},</p><p>Aboneliğiniz yenilendi.</p><ul>  <li><strong>Paket:</strong> {{plan_name}}</li>  <li><strong>Dönem:</strong> {{period_start}} – {{period_end}}</li>  <li><strong>Tutar:</strong> {{amount}} {{currency}}</li></ul><p><a href="{{panel_url}}">Paneli aç</a></p>',
                'enabled' => true,
            ]
        );

        MailTemplate::updateOrCreate(
            ['key' => 'subscription.cancelled'],
            [
                'channel' => 'email',
                'category' => 'billing',
                'subject' => 'Aboneliğiniz iptal edildi',
                'body_html' => '<p>Merhaba {{user_name}},</p><p>Aboneliğiniz iptal edildi.</p><ul>  <li><strong>Paket:</strong> {{plan_name}}</li>  <li><strong>Erişim bitiş:</strong> {{access_ends_at}}</li></ul><p><a href="{{reactivate_url}}">Aboneliği yeniden başlat</a></p><p><a href="{{plans_url}}">Paketleri gör</a></p>',
                'enabled' => true,
            ]
        );

        MailTemplate::updateOrCreate(
            ['key' => 'trial.ended'],
            [
                'channel' => 'email',
                'category' => 'billing',
                'subject' => 'Deneme süreniz sona erdi — paketinizi seçin',
                'body_html' => '<p>Merhaba {{user_name}},</p><p>Deneme süreniz {{trial_ended_at}} itibarıyla sona erdi.</p><p>Hizmeti kesintisiz kullanmaya devam etmek için bir paket seçebilirsiniz.</p><p><a href="{{pricing_url}}">Paketleri gör</a></p><p><a href="{{dashboard_url}}">Paneli aç</a></p><p>Yardıma ihtiyacınız olursa destek ekibimize yazabilirsiniz.</p>',
                'enabled' => true,
            ]
        );

        MailTemplate::updateOrCreate(
            ['key' => 'payment.succeeded'],
            [
                'channel' => 'email',
                'category' => 'billing',
                'subject' => 'Ödeme alındı — teşekkürler',
                'body_html' => '<p>Merhaba {{user_name}},</p><p>Ödemeniz başarıyla alındı.</p><ul>  <li><strong>Tutar:</strong> {{amount}} {{currency}}</li>  <li><strong>Tarih:</strong> {{occurred_at}}</li>  <li><strong>Sağlayıcı:</strong> {{provider}}</li>  <li><strong>İşlem No:</strong> {{transaction_id}}</li></ul><p><a href="{{receipt_url}}">Makbuzu görüntüle</a></p><p><a href="{{billing_url}}">Ödeme ayarları</a></p>',
                'enabled' => true,
            ]
        );

        MailTemplate::updateOrCreate(
            ['key' => 'mp.token_expiring'],
            [
                'channel' => 'email',
                'category' => 'marketplace',
                'subject' => '{{marketplace}} bağlantınız {{days_left}} gün içinde yenileme gerektirecek',
                'body_html' => '<p>Merhaba {{user_name}},</p><p>{{marketplace}} bağlantınızın erişim süresi yakında dolacak.</p><ul>  <li><strong>Bitiş tarihi:</strong> {{expires_at}}</li>  <li><strong>Kalan gün:</strong> {{days_left}}</li></ul><p>Sipariş, stok ve fiyat senkronizasyonunun kesintiye uğramaması için bağlantınızı yenileyin.</p><p><a href="{{reconnect_url}}">Bağlantıyı yenile</a> · <a href="{{dashboard_url}}">Paneli aç</a></p><p>Yardıma ihtiyacınız olursa: <a href="{{support_url}}">Destek</a></p>',
                'enabled' => true,
            ]
        );

        MailTemplate::updateOrCreate(
            ['key' => 'quota.warning_80'],
            [
                'channel' => 'email',
                'category' => 'usage',
                'subject' => '{{quota_type_label}} kotanızın %{{percent}} seviyesine ulaştınız',
                'body_html' => '<p>Merhaba {{user_name}},</p><p>{{quota_type_label}} kotanızın %{{percent}} seviyesine ulaştınız.</p><ul>  <li><strong>Kullanım:</strong> {{used}} / {{limit}}</li></ul><p>Kesinti yaşamamak için paketinizi yükseltebilirsiniz.</p><p><a href="{{pricing_url}}">Paketleri gör</a></p><p>Bu otomatik bir bilgilendirmedir. Yardıma ihtiyacınız olursa panel üzerinden destek talebi oluşturabilirsiniz.</p>',
                'enabled' => true,
            ]
        );
    }
}