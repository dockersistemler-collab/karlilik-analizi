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
                'name' => 'Gelismis Raporlar',
                'description' => 'Rapor ekranlari ve gelismis raporlar.',
                'type' => 'feature',
                'billing_type' => 'recurring',
                'is_active' => true,
                'sort_order' => 0,
            ]
        );
        Module::query()->updateOrCreate(
            ['code' => 'feature.reports.profitability'],
            [
                'name' => 'Siparis Karlilik Analizi',
                'description' => 'Siparis karlilik analizi raporu.',
                'type' => 'feature',
                'billing_type' => 'recurring',
                'is_active' => true,
                'sort_order' => 0,
            ]
        );
        Module::query()->updateOrCreate(
            ['code' => 'feature.hakedis'],
            [
                'name' => 'Hakediş Kontrol Merkezi',
                'description' => 'Payout, mutabakat ve sapma merkezi.',
                'type' => 'feature',
                'billing_type' => 'recurring',
                'is_active' => true,
                'sort_order' => 0,
            ]
        );
        Module::query()->updateOrCreate(
            ['code' => 'ne_kazanirim'],
            [
                'name' => 'Ne Kazanirim',
                'description' => 'Hesaplama araci (manual giris ekrani)',
                'type' => 'feature',
                'billing_type' => 'recurring',
                'is_active' => true,
                'sort_order' => 0,
            ]
        );
        Module::query()->updateOrCreate(
            ['code' => 'profit_engine'],
            [
                'name' => 'Profit Engine',
                'description' => 'Siparis bazli net karlilik hesaplama modulu.',
                'type' => 'feature',
                'billing_type' => 'recurring',
                'is_active' => true,
                'sort_order' => 0,
            ]
        );
        Module::query()->updateOrCreate(
            ['code' => 'marketplace_risk'],
            [
                'name' => 'Marketplace Risk',
                'description' => 'Pazaryeri KPI risk skoru ve alarm modulu.',
                'type' => 'feature',
                'billing_type' => 'recurring',
                'is_active' => true,
                'sort_order' => 0,
            ]
        );
        Module::query()->updateOrCreate(
            ['code' => 'action_engine'],
            [
                'name' => 'Action Engine',
                'description' => 'Risk ve karlilik verisinden aksiyon onerileri uretir.',
                'type' => 'feature',
                'billing_type' => 'recurring',
                'is_active' => true,
                'sort_order' => 0,
            ]
        );
        Module::query()->updateOrCreate(
            ['code' => 'buybox_engine'],
            [
                'name' => 'BuyBox Engine',
                'description' => 'BuyBox/One Cikan Teklif snapshot, import ve izleme modulu.',
                'type' => 'feature',
                'billing_type' => 'recurring',
                'is_active' => true,
                'sort_order' => 0,
            ]
        );
        Module::query()->updateOrCreate(
            ['code' => 'feature.control_tower'],
            [
                'name' => 'Marketplace Intelligence Control Tower',
                'description' => 'CFO + OPS birlesik kontrol ekrani.',
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
                'description' => '�r�n, siparis ve rapor exportlari.',
                'type' => 'feature',
                'billing_type' => 'recurring',
                'is_active' => true,
                'sort_order' => 0,
            ]
        );

        Module::query()->updateOrCreate(
            ['code' => 'feature.integrations'],
            [
                'name' => 'Pazaryeri Entegrasyonlari',
                'description' => 'Pazaryeri entegrasyon ekranlarina erisim.',
                'type' => 'feature',
                'billing_type' => 'recurring',
                'is_active' => true,
                'sort_order' => 0,
            ]
        );

        Module::query()->updateOrCreate(
            ['code' => 'feature.category_mapping'],
            [
                'name' => 'Kategori Esitleme',
                'description' => 'Pazaryeri kategori esitleme mod�l�.',
                'type' => 'feature',
                'billing_type' => 'recurring',
                'is_active' => true,
                'sort_order' => 0,
            ]
        );

        Module::query()->updateOrCreate(
            ['code' => 'feature.sub_users'],
            [
                'name' => 'Alt Kullanicilar',
                'description' => 'Alt kullanici y�netimi mod�l�.',
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
                'description' => 'Ticket olusturma ve y�netimi.',
                'type' => 'feature',
                'billing_type' => 'recurring',
                'is_active' => true,
                'sort_order' => 0,
            ]
        );

        Module::query()->updateOrCreate(
            ['code' => 'feature.quick_actions'],
            [
                'name' => 'Hizli Men�',
                'description' => 'Hizli men�/aksiyonlar.',
                'type' => 'feature',
                'billing_type' => 'recurring',
                'is_active' => true,
                'sort_order' => 0,
            ]
        );

        Module::query()->updateOrCreate(
            ['code' => 'feature.einvoice_api'],
            [
                'name' => 'E-Fatura API Erisimi',
                'description' => 'E-Fatura verilerine API �zerinden erisim.',
                'type' => 'feature',
                'billing_type' => 'recurring',
                'is_active' => true,
                'sort_order' => 0,
            ]
        );

        Module::query()->updateOrCreate(
            ['code' => 'feature.einvoice_webhooks'],
            [
                'name' => 'E-Fatura Webhooklari',
                'description' => 'E-Fatura eventlerini dis sistemlere webhook ile g�nderme.',
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
                'description' => 'Kargo entegrasyonlari ve takip akisi.',
                'type' => 'feature',
                'billing_type' => 'recurring',
                'is_active' => true,
                'sort_order' => 0,
            ]
        );

        Module::query()->updateOrCreate(
            ['code' => 'feature.cargo_webhooks'],
            [
                'name' => 'Kargo Webhooklari',
                'description' => 'Kargo takip eventlerini webhook ile g�nderme.',
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
                'name' => 'Yurti�i Kargo Entegrasyonu',
                'description' => 'Yurti�i kargo entegrasyonu.',
                'type' => 'integration',
                'billing_type' => 'recurring',
                'is_active' => true,
                'sort_order' => 0,
            ]
        );
        Module::query()->updateOrCreate(
            ['code' => 'feature.inventory'],
            [
                'name' => 'Stok Takibi',
                'description' => 'Stok hareketleri, kritik stok uyarıları ve envanter ekranları.',
                'type' => 'feature',
                'billing_type' => 'recurring',
                'is_active' => false,
                'sort_order' => 0,
            ]
        );

        Module::query()->updateOrCreate(
            ['code' => 'integration.inventory.trendyol'],
            [
                'name' => 'Stok - Trendyol Connector',
                'description' => 'Stok modülü için Trendyol stok senkronizasyon connectorü.',
                'type' => 'integration',
                'billing_type' => 'recurring',
                'is_active' => false,
                'sort_order' => 0,
            ]
        );

        Module::query()->updateOrCreate(
            ['code' => 'integration.inventory.hepsiburada'],
            [
                'name' => 'Stok - Hepsiburada Connector',
                'description' => 'Stok modülü için Hepsiburada stok senkronizasyon connectorü.',
                'type' => 'integration',
                'billing_type' => 'recurring',
                'is_active' => false,
                'sort_order' => 0,
            ]
        );

        Module::query()->updateOrCreate(
            ['code' => 'integration.inventory.n11'],
            [
                'name' => 'Stok - N11 Connector',
                'description' => 'Stok modülü için N11 stok senkronizasyon connectorü.',
                'type' => 'integration',
                'billing_type' => 'recurring',
                'is_active' => false,
                'sort_order' => 0,
            ]
        );

        Module::query()->updateOrCreate(
            ['code' => 'integration.inventory.amazon'],
            [
                'name' => 'Stok - Amazon Connector',
                'description' => 'Stok modülü için Amazon stok senkronizasyon connectorü.',
                'type' => 'integration',
                'billing_type' => 'recurring',
                'is_active' => false,
                'sort_order' => 0,
            ]
        );
    }
}
