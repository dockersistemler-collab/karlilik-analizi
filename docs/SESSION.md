# Session Memory

**Last updated:** 2026-01-26

## Status
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

## Next steps
1) `php artisan migrate` çalıştır (sub_users + desi/vat alanları).
2) Ürün oluştur/düzenle akışını test et (marka/kategori hızlı ekleme, barkod üret, desi hesaplama, resim yükleme, editor görünümü).
3) Ürün listesi satır boşluk/gölge görünümünü kontrol et.
4) Alt kullanıcı girişini ve yetki kısıtlarını test et (dashboard + modül erişimleri).
5) Super admin alt kullanıcı listesi ve filtreleri test et.

## Notes
- Değişiklikler: `resources/views/admin/invoice-create.blade.php`,
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

