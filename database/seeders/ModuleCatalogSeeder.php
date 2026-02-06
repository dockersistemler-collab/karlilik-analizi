<?php

namespace Database\Seeders;

use App\Models\Module;
use Illuminate\Database\Seeder;

class ModuleCatalogSeeder extends Seeder
{
    public function run(): void
    {
        Module::query()->updateOrCreate(
            ['code' => 'feature.reports'],
            [
                'name' => 'Gelişmiş Raporlar',
                'description' => 'Rapor ekranları ve gelişmiş raporlar.',
                'type' => 'feature',
                'billing_type' => 'recurring',
                'is_active' => true,
                'sort_order' => 0,
            ]
        );

        Module::query()->updateOrCreate(
            ['code' => 'feature.exports'],
            [
                'name' => 'Exportlar',
                'description' => 'Ürün, sipariş ve rapor exportları.',
                'type' => 'feature',
                'billing_type' => 'recurring',
                'is_active' => true,
                'sort_order' => 0,
            ]
        );

        Module::query()->updateOrCreate(
            ['code' => 'feature.integrations'],
            [
                'name' => 'Pazaryeri Entegrasyonları',
                'description' => 'Pazaryeri entegrasyon ekranlarına erişim.',
                'type' => 'feature',
                'billing_type' => 'recurring',
                'is_active' => true,
                'sort_order' => 0,
            ]
        );

        Module::query()->updateOrCreate(
            ['code' => 'feature.category_mapping'],
            [
                'name' => 'Kategori Eşitleme',
                'description' => 'Pazaryeri kategori eşitleme modülü.',
                'type' => 'feature',
                'billing_type' => 'recurring',
                'is_active' => true,
                'sort_order' => 0,
            ]
        );

        Module::query()->updateOrCreate(
            ['code' => 'feature.sub_users'],
            [
                'name' => 'Alt Kullanıcılar',
                'description' => 'Alt kullanıcı yönetimi modülü.',
                'type' => 'feature',
                'billing_type' => 'recurring',
                'is_active' => true,
                'sort_order' => 0,
            ]
        );

        Module::query()->updateOrCreate(
            ['code' => 'feature.tickets'],
            [
                'name' => 'Destek (Ticket)',
                'description' => 'Ticket oluşturma ve yönetimi.',
                'type' => 'feature',
                'billing_type' => 'recurring',
                'is_active' => true,
                'sort_order' => 0,
            ]
        );

        Module::query()->updateOrCreate(
            ['code' => 'feature.quick_actions'],
            [
                'name' => 'Hızlı Menü',
                'description' => 'Hızlı menü/aksiyonlar.',
                'type' => 'feature',
                'billing_type' => 'recurring',
                'is_active' => true,
                'sort_order' => 0,
            ]
        );

        Module::query()->updateOrCreate(
            ['code' => 'feature.einvoice_api'],
            [
                'name' => 'E-Fatura API Erişimi',
                'description' => 'E-Fatura verilerine API üzerinden erişim.',
                'type' => 'feature',
                'billing_type' => 'recurring',
                'is_active' => true,
                'sort_order' => 0,
            ]
        );

        Module::query()->updateOrCreate(
            ['code' => 'feature.einvoice_webhooks'],
            [
                'name' => 'E-Fatura Webhookları',
                'description' => 'E-Fatura eventlerini dış sistemlere webhook ile gönderme.',
                'type' => 'feature',
                'billing_type' => 'recurring',
                'is_active' => true,
                'sort_order' => 0,
            ]
        );

        Module::query()->updateOrCreate(
            ['code' => 'feature.cargo_tracking'],
            [
                'name' => 'Kargo Takip',
                'description' => 'Kargo entegrasyonları ve takip akışı.',
                'type' => 'feature',
                'billing_type' => 'recurring',
                'is_active' => true,
                'sort_order' => 0,
            ]
        );

        Module::query()->updateOrCreate(
            ['code' => 'feature.cargo_webhooks'],
            [
                'name' => 'Kargo Webhookları',
                'description' => 'Kargo takip eventlerini webhook ile gönderme.',
                'type' => 'feature',
                'billing_type' => 'recurring',
                'is_active' => true,
                'sort_order' => 0,
            ]
        );

        Module::query()->updateOrCreate(
            ['code' => 'integration.cargo.trendyol_express'],
            [
                'name' => 'Trendyol Express Entegrasyonu',
                'description' => 'Trendyol Express kargo entegrasyonu.',
                'type' => 'integration',
                'billing_type' => 'recurring',
                'is_active' => true,
                'sort_order' => 0,
            ]
        );

        Module::query()->updateOrCreate(
            ['code' => 'integration.cargo.aras'],
            [
                'name' => 'Aras Kargo Entegrasyonu',
                'description' => 'Aras kargo entegrasyonu.',
                'type' => 'integration',
                'billing_type' => 'recurring',
                'is_active' => true,
                'sort_order' => 0,
            ]
        );

        Module::query()->updateOrCreate(
            ['code' => 'integration.cargo.yurtici'],
            [
                'name' => 'Yurtiçi Kargo Entegrasyonu',
                'description' => 'Yurtiçi kargo entegrasyonu.',
                'type' => 'integration',
                'billing_type' => 'recurring',
                'is_active' => true,
                'sort_order' => 0,
            ]
        );
    }
}
