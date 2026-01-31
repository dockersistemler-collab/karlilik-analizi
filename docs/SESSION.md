# Session Memory

**Last updated:** 2026-01-31

## Status
- Rapor modülleri sıfırdan kuruldu: Çok Satan Ürünler, Satılan Ürünler, Sipariş/Ciro, Kategori, Marka, KDV, Komisyon, Stok Değeri.
- Yeni servis katmanı eklendi: `app/Services/Reports/*` ve ortak filtre yardımcıları.
- Rapor exportları CSV/Excel (CSV) olarak eklendi; exportlar için global aç/kapa ayarı (super-admin) ve middleware eklendi.
- Sipariş/Ciro raporuna tablo + Chart.js grafikler eklendi; faturalı export placeholder olarak bağlandı.
- Satılan Ürünler raporuna yazdırma (print) sayfası eklendi.
- Kategori/Marka raporlarında grafik tipi seçimi (Pasta/Yatay/Çubuk) eklendi.
- Komisyon raporu grafik yerine kartlar ile gösteriliyor.
- Stok değeri raporu özet kartlar + tablo ile yenilendi.
- Admin/super-admin panelleri beyaz tema + #ff4439 aksan rengine çekildi.
- Sidebar hover ile açılır/kapanır yapıldı; yazılar antrasit, hover rengi #ff4439.
- Fatura oluştur sayfasında arama sonrası “Müşteri Ekle” butonu iyileştirildi (admin + super-admin).
- Panel radius/gölge azaltıldı; genel radius 5px’e çekildi; sidebar faturalar “+” kaldırıldı.
- Müşteri ekleme modalında JSON dönüş problemi ve modal kapanmama takibi için iyileştirmeler yapıldı.
- Müşteri e‑posta tekilliği doğrulaması eklendi (admin + super-admin).
- Alt kullanıcı (sub_users) modülü için altyapı ve ekranlar eklendi; super admin için ayrı liste hazır.
- Admin/super-admin profil dropdown menüleri eklendi, admin sidebar’a “Alt Kullanıcılar” linki geldi.
- Ürün ekleme/düzenleme ekranına marka/kategori arama + hızlı ekleme, barkod üret, desi hesaplama, KDV oranı, açıklama editörü ve çoklu resim yükleme eklendi; ürün açıklaması HTML olarak gösteriliyor.
- Desi hesaplama formülü (En x Boy x Yukseklik / 3) ve agirlik karsilastirmasi eklendi; KDV oranina %0 secenegi geldi; desi/barkod butonlari kucultuldu ve modal daha sik hale getirildi.
- TinyMCE Community (sade/word-benzeri toolbar) aktif; urun aciklamasi whitelist ile sanitize ediliyor; rich content icin CSS normalize edildi; desi modal UI daha zarif hale getirildi.
- Ürün listesinde satırlar arası boşluk ve hafif gölge eklendi (kart görünümlü satırlar).
- Super admin layout buton stili `btn` sistemine alındı; odak çizgisi nötrleştirildi.
- Public home/pricing butonları `btn` sistemine taşındı (solid/outline hover uyumlu).
- Admin/super-admin layout `main button` stili, `.btn` sınıflarını ezmeyecek şekilde güncellendi.
- Banner sistemi eklendi (super admin yönetimi + admin/public gösterim).
- Banner için geri sayım (ends_at) desteği eklendi.
- Ürün listesi arama/pagination AJAX yapıldı, banner yerleşimi header altına alındı.
- Profil sayfası admin layout içine alındı ve Türkçeleştirildi; destek sayfası Türkçe karakterleri düzeltildi.
- Ürün import/ekleme için SKU boşsa otomatik üretim eklendi; SKU eşsizliği kullanıcı bazına taşındı (migrasyon eklendi).
- Ürün import sonucuna detaylı atlama nedenleri eklendi (boş/invalid/eksik/limit/aynı SKU).
- Sidebar üst ve sağ kenarları #ff4439 aksan rengiyle vurgulandı; sağ kenarlar ovalleştirildi (admin/super-admin).
- Sidebar sabit (fixed) hale getirildi; içeriğe sol boşluk ve hover/pin genişleme paddingi eklendi (admin/super-admin).
- KDV raporu pazaryeri bazlı kartlara çevrildi; renkler super-admin ayarlarından yönetilebilir hale getirildi.
- Dashboard’ta harita tabı büyütülüp Leaflet + Türkiye GeoJSON ile çiziliyor; il bazlı sipariş adedi tooltip’te gösteriliyor, il isimleri harita üzerinde sabit yazıyor (map verisi `/public/maps/turkey-provinces.geojson`, Leaflet assets local `public/vendor/leaflet`).
- Admin paneline sabit hızlı menü (floating + butonu) eklendi; seçenekler super-admin ayarlarından yönetilebiliyor.
- Hızlı menüde sıralama sürükle-bırak, ikon/renk seçimi ve rol bazlı görünürlük ayarları eklendi.
- Hızlı menü sayfa yüklenişinde otomatik kapanacak şekilde güvence eklendi (DOMContentLoaded/pageshow).
- Admin/super-admin için `.btn-outline` butonlar güçlendirildi (dashed border + zorlayan stiller), rapor sayfalarında görünür.
- Hızlı menü kapalı başlatma/hidden davranışı sağlamlaştırıldı (menu hidden attribute + CSS override).
- Kategori eşitleme altyapısı eklendi: pazaryeri kategori cache + kategori↔pazaryeri mapping ekranı.
- Ürünlerde kategori alanı internal kategoriye taşındı (`products.category_id`) ve ürün formu select olarak güncellendi.
- Admin sidebar: submenu açıkken sidebar kapanınca görünüm bozan durum için CSS güvence eklendi.
- Super admin ayarlarına “Kategori Eşitleme” sekmesi eklendi (aktif/pasif, auto-sync, ürün ekranında inline panel, içe aktarım varsayılanları).
- Güvenlik: `.env` repo kökünden kaldırıldı; arşiv dosyaları ignore edildi (zip/rar/7z). Yeni arşiv oluştururken `.env` dahil edilmemeli.
- Queue: production ortamında `QUEUE_CONNECTION=sync` engellendi; Redis/database queue + worker zorunlu. Marketplace kategori senkronu artık request içinde değil job olarak kuyruklanıyor.
- Panel görünümü ayarları eklendi (font/aksan rengi/radius) ve varsayılan font Poppins yapıldı (admin + super-admin).
- Paket bazlı modül aç/kapa altyapısı eklendi: süper admin paket düzenle ekranında modül + alt-modül seçimi (Rapor alt sayfaları, Entegrasyon pazaryerleri dahil) + route middleware ile erişim kontrolü + menü/hızlı menü görünürlüğü.
- Exportlar paket bazlı aç/kapat yapıldı (ürün/sipariş/fatura + rapor exportları ayrı ayrı).
- Destek sayfası görsel olarak iyileştirildi (soft gradient arkaplan, dekoratif şekiller, renkli kartlar) ve paketinde “Destek” kapalıysa buton buna göre davranıyor.
- Ticket ekranları renklendirildi (liste/oluştur/detay): soft gradient header, renkli kartlar ve daha okunur badge/mesaj görünümleri.

## Next steps
1) Süper admin → Paketler bölümünden her paket için modül seçimlerini yap (ilk kayıtta legacy `features` listesi otomatik olarak `marketing` altına alınır ve `modules` oluşturulur).
1) `php artisan migrate` çalıştır (marketplace_categories, category_mappings, products.category_id).
2) Entegrasyon ekranından Trendyol bağla/aktif et → kategorilerin cache’e çekildiğini doğrula (manuel “Senkronla” da var).
3) Kategoriler ekranında “Pazaryerinden İçe Aktar” ile internal kategori oluştur + eşleme otomatik gelsin.
4) Ürün ekle/düzenle: kategori seçimi + ürün detayında kategori gösterimi kontrol et.
5) Diğer pazaryerleri için kategori provider’larını ekle (şu an sadece Trendyol implement edildi).
6) (Önceki) rapor/export testleri ve SKU unique migrasyonu testleri.

## Notes
- Değişiklikler: `app/Services/Reports/*`,
  `app/Http/Controllers/Admin/ReportController.php`,
  `routes/customer.php`,
  `resources/views/admin/reports.blade.php`,
  `resources/views/admin/reports/top-products.blade.php`,
  `resources/views/admin/reports/sold-products.blade.php`,
  `resources/views/admin/reports/sold-products-print.blade.php`,
  `resources/views/admin/reports/category-sales.blade.php`,
  `resources/views/admin/reports/brand-sales.blade.php`,
  `resources/views/admin/reports/vat.blade.php`,
  `resources/views/admin/reports/commission.blade.php`,
  `resources/views/admin/reports/stock-value.blade.php`,
  `app/Models/AppSetting.php`,
  `app/Http/Middleware/EnsureReportExportsEnabled.php`,
  `database/migrations/2026_01_27_120000_create_app_settings_table.php`,
  `app/Http/Controllers/SuperAdmin/SettingsController.php`,
  `resources/views/super-admin/settings/index.blade.php`,
  `routes/admin.php`,
  `bootstrap/app.php`.
  Yeni değişiklikler: `app/Http/Controllers/Admin/ProductController.php`,
  `database/migrations/2026_01_28_000000_make_products_sku_unique_per_user.php`.
  Yeni değişiklikler: `resources/views/layouts/admin.blade.php`,
  `resources/views/layouts/super-admin.blade.php`.
  KDV renk ayarları: `app/Services/Reports/VatReportService.php`,
  `app/Http/Controllers/Admin/ReportController.php`,
  `app/Http/Controllers/SuperAdmin/SettingsController.php`,
  `routes/admin.php`,
  `resources/views/admin/reports/vat.blade.php`,
  `resources/views/super-admin/settings/index.blade.php`.
  Dashboard harita: `app/Http/Controllers/Admin/DashboardController.php`,
  `resources/views/admin/dashboard.blade.php`,
  `public/maps/turkey-provinces.geojson`.
  Hızlı menü: `resources/views/layouts/admin.blade.php`,
  `app/Http/Controllers/SuperAdmin/SettingsController.php`,
  `resources/views/super-admin/settings/index.blade.php`,
  `routes/admin.php`.
  Buton/menü düzeltmeleri: `resources/views/layouts/admin.blade.php`,
  `resources/views/layouts/super-admin.blade.php`.
  Önceki değişiklikler: `resources/views/admin/invoice-create.blade.php`,
  `resources/views/layouts/super-admin.blade.php`,
  `resources/views/public/home.blade.php`,
  `resources/views/public/pricing.blade.php`,
  `resources/views/super-admin/invoices/create.blade.php`,
  `app/Http/Controllers/Admin/CustomerController.php`,
  `app/Http/Controllers/SuperAdmin/CustomerController.php`,
  `app/Http/Controllers/Admin/SubUserController.php`,
  `app/Http/Controllers/SuperAdmin/SubUserController.php`,
  `app/Http/Middleware/EnsureClientOrSubUser.php`,
  `app/Http/Middleware/EnsureSubUserPermission.php`,
  `database/migrations/2026_01_25_090000_create_sub_users_table.php`,
  `database/migrations/2026_01_25_090010_create_sub_user_permissions_table.php`,
  `database/migrations/2026_01_25_120000_add_desi_vat_to_products_table.php`,
  `resources/views/admin/sub-users/index.blade.php`,
  `resources/views/admin/sub-users/create.blade.php`,
  `resources/views/admin/sub-users/edit.blade.php`,
  `resources/views/super-admin/sub-users/index.blade.php`,
  `resources/views/admin/products/create.blade.php`,
  `resources/views/admin/products/edit.blade.php`,
  `resources/views/admin/products/show.blade.php`,
  `resources/views/layouts/admin.blade.php`,
  `resources/views/layouts/super-admin.blade.php`,
  `routes/customer.php`,
  `routes/admin.php`,
  `routes/auth.php`,
  `config/auth.php`,
  `config/purifier.php`,
  `app/Http/Controllers/Admin/ProductController.php`,
  `app/Models/Product.php`,
  `resources/views/admin/products/index.blade.php`,
  `resources/views/layouts/admin.blade.php`,
  `app/Models/SubUser.php`,
  `app/Models/SubUserPermission.php`.
  Kategori eşitleme: `database/migrations/2026_01_30_*`, `app/Services/Marketplace/Category/*`, `app/Http/Controllers/Admin/MarketplaceCategoryController.php`, `app/Http/Controllers/Admin/CategoryMappingController.php`, `resources/views/admin/products/categories/index.blade.php`, `routes/customer.php`.
  Ürün kategori alanı: `database/migrations/2026_01_30_000003_add_category_id_to_products_table.php`, `app/Http/Controllers/Admin/ProductController.php`, `app/Models/Product.php`, `resources/views/admin/products/create.blade.php`, `resources/views/admin/products/edit.blade.php`, `resources/views/admin/products/show.blade.php`.

## Değişiklik Günlüğü
- 2026-01-26: Git başlatıldı, ilk commit alındı.
- 2026-01-26: Super admin layout buton stili `btn` sistemine alındı.
- 2026-01-26: Public home/pricing butonları `btn` sistemine taşındı.
- 2026-01-26: Admin/super-admin `main button` kuralı `.btn` hoverlarını ezmeyecek şekilde düzeltildi.
- 2026-01-26: Banner modülü eklendi (super admin CRUD + admin/public yerleşim).
- 2026-01-26: Banner geri sayım (countdown) desteği eklendi.
- 2026-01-27: Ürün listesi AJAX arama ve pagination eklendi; banner header altına alındı.
- 2026-01-27: Profil sayfası admin layout ile uyumlu hale getirildi ve Türkçeleştirildi; destek sayfası Türkçe karakterleri düzeltildi.

## Commands run (optional)
- php artisan migrate (Nothing to migrate)

## Files touched (optional)
- resources/views/admin/invoice-create.blade.php
- resources/views/super-admin/invoices/create.blade.php
- app/Http/Controllers/Admin/CustomerController.php
- app/Http/Controllers/SuperAdmin/CustomerController.php
- resources/views/admin/products/create.blade.php
- resources/views/admin/products/edit.blade.php
- resources/views/admin/products/index.blade.php
- resources/views/admin/products/show.blade.php
- resources/views/layouts/admin.blade.php
- app/Http/Controllers/Admin/ProductController.php
- app/Models/Product.php
- config/purifier.php
