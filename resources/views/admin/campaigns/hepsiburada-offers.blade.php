@extends('layouts.admin')

@section('header')
    Hepsiburada Teklifler
@endsection

@section('content')
    <div class="commission-page">
        <div class="commission-hero">
            <div class="commission-hero-content">
                <div class="commission-hero-title">
                    <span class="commission-badge-hero">Teklif Yönetimi</span>
                    <span class="commission-status-pill">Canlı</span>
                </div>
                <h2>Hepsiburada tekliflerini komisyon tarifeleriyle aynı modern yapıda yönet.</h2>
                <p>Excel ile teklifleri içe aktar, kategoriye göre filtrele ve fiyat aralıklarını tek tabloda hızlıca güncelle.</p>
            </div>
            <div class="commission-hero-aside">
                <div class="commission-hero-actions">
                    <button id="commission-upload-btn" class="btn btn-solid-accent">
                        <i class="fa-solid fa-file-arrow-up mr-2"></i>
                        Excel Yükle
                    </button>
                    <button id="commission-errors-btn" class="btn btn-outline-accent">
                        <i class="fa-solid fa-triangle-exclamation mr-2"></i>
                        Hataları Gör
                    </button>
                    <button id="commission-bulk-download" class="btn btn-outline-accent">
                        <i class="fa-solid fa-file-export mr-2"></i>
                        Kaydet & İndir
                    </button>
                </div>
            </div>
        </div>

        <!-- Toplu atama kaldırıldı (Excel tabanlı akış) -->
    </div>

    <div id="commission-filters-visible" style="display:flex !important;visibility:visible !important;opacity:1 !important;position:relative;z-index:40;flex-wrap:wrap;align-items:flex-end;gap:14px;margin:0 0 12px 0;padding:14px 16px;border:1px solid #dbe3ee;border-radius:14px;background:linear-gradient(180deg,#ffffff,#f8fafc);box-shadow:0 8px 20px rgba(15,23,42,.06);">
            <div style="min-width:340px;flex:1 1 460px;">
                <label style="display:block;font-size:11px;font-weight:700;color:#475569;margin-bottom:6px;letter-spacing:.03em;text-transform:uppercase;">Arama</label>
                <input id="commission-search" type="text" placeholder="SKU, barkod veya ürün adı ara" style="width:100%;border:1px solid #cbd5e1;border-radius:10px;padding:10px 12px;font-size:14px;background:#fff;">
            </div>
            <div style="width:230px;min-width:230px;">
                <label style="display:block;font-size:11px;font-weight:700;color:#475569;margin-bottom:6px;letter-spacing:.03em;text-transform:uppercase;">Kategori</label>
                <select id="commission-category" style="width:100%;border:1px solid #cbd5e1;border-radius:10px;padding:10px 12px;font-size:14px;background:#fff;">
                    <option value="">Kategori (Tümü)</option>
                </select>
            </div>
            <div style="min-width:280px;flex:0 0 auto;">
                <label style="display:block;font-size:11px;font-weight:700;color:#475569;margin-bottom:6px;letter-spacing:.03em;text-transform:uppercase;">Toplu Seçim</label>
                <div style="display:flex;align-items:center;gap:14px;border:1px solid #e2e8f0;border-radius:10px;padding:10px 12px;background:#f8fafc;color:#334155;font-size:13px;">
                    <label style="display:inline-flex;align-items:center;gap:6px;white-space:nowrap;font-weight:600;">
                        <input type="checkbox" id="commission-select-all" class="rounded">
                        Tümünü seç
                    </label>
                    <label style="display:inline-flex;align-items:center;gap:6px;white-space:nowrap;font-weight:600;">
                        <input type="checkbox" id="commission-all-variants" class="rounded">
                        Tüm varyantları seç
                    </label>
                </div>
            </div>
    </div>

    <div class="panel-card p-4 commission-table-card">
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm commission-table">
                <thead>
                    <tr>
                        <th class="w-10"></th>
                        <th>Ürün</th>
                        <th>Stok</th>
                        <th>Güncel Fiyat (₺)</th>
                        <th>1. Fiyat Aralığı</th>
                        <th>2. Fiyat Aralığı</th>
                        <th>3. Fiyat Aralığı</th>
                        <th>Manuel Fiyat Girişi</th>
                    </tr>
                </thead>
                <tbody id="commission-table-body">
                    <tr>
                        <td colspan="8" class="text-center text-slate-500 py-6">Yükleniyor...</td>
                    </tr>
                </tbody>
            </table>
        </div>
        <div class="flex items-center justify-between text-xs text-slate-500 mt-4">
            <div class="flex items-center gap-3">
                <label for="commission-page-size" class="text-slate-500">Sayfa başına</label>
                <select id="commission-page-size" class="px-3 py-2 rounded-xl border border-slate-300 bg-white text-slate-700 shadow-sm">
                    <option value="10">10</option>
                    <option value="20" selected>20</option>
                    <option value="50">50</option>
                    <option value="100">100</option>
                </select>
                <div id="commission-pagination-info">Toplam kayıt: 0</div>
            </div>
            <div class="flex items-center gap-2">
                <button id="commission-prev" class="btn btn-outline btn-sm">Önceki</button>
                <button id="commission-next" class="btn btn-outline btn-sm">Sonraki</button>
            </div>
        </div>
    </div>

    <div id="commission-upload-modal" class="fixed inset-0 bg-slate-900/30 hidden items-center justify-center z-50">
        <div class="bg-white rounded-xl shadow-lg w-full max-w-2xl p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-sm font-semibold text-slate-800">Excel Yükle</h3>
                <button id="commission-upload-close" type="button" class="text-slate-400 hover:text-slate-600">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
            <form id="commission-upload-form" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-sm font-medium text-slate-700">Excel Dosyası</label>
                    <input type="file" name="file" accept=".xlsx,.xls,.csv" class="mt-2 w-full">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700">Pazaryeri</label>
                    <select name="marketplace" class="mt-2 w-full" required>
                        <option value="">Seç</option>
                        <option value="trendyol">Trendyol</option>
                        <option value="hepsiburada">Hepsiburada</option>
                    </select>
                </div>
                <div class="flex items-center gap-3 justify-end">
                    <button type="button" id="commission-upload-cancel" class="btn btn-outline">Vazgeç</button>
                    <button type="submit" class="btn btn-solid-accent">Yükle</button>
                </div>
            </form>
        </div>
    </div>

    <div id="commission-map-modal" class="fixed inset-0 bg-slate-900/30 hidden items-center justify-center z-50">
        <div class="bg-white rounded-xl shadow-lg w-full max-w-3xl p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-sm font-semibold text-slate-800">Kolon Eşleştirme</h3>
                <button id="commission-map-close" type="button" class="text-slate-400 hover:text-slate-600">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
            <div class="text-xs text-slate-500 mb-4">
                Excel kolonlarını sistem alanlarıyla eşleştir. Bu adım zorunludur.
            </div>
            <div id="commission-map-fields" class="grid grid-cols-1 md:grid-cols-2 gap-4"></div>
            <div class="flex items-center gap-3 justify-end mt-6">
                <button id="commission-map-cancel" type="button" class="btn btn-outline">Vazgeç</button>
                <button id="commission-map-save" type="button" class="btn btn-solid-accent">Eşleştir ve İçe Aktar</button>
            </div>
        </div>
    </div>

    <div id="commission-errors-modal" class="fixed inset-0 bg-slate-900/30 hidden items-center justify-center z-50">
        <div class="bg-white rounded-xl shadow-lg w-full max-w-3xl p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-sm font-semibold text-slate-800">Excel Hataları</h3>
                <button id="commission-errors-close" type="button" class="text-slate-400 hover:text-slate-600">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead>
                        <tr>
                            <th>Satır</th>
                            <th>Hata</th>
                            <th>Ham Veri</th>
                        </tr>
                    </thead>
                    <tbody id="commission-errors-body">
                        <tr>
                            <td colspan="3" class="text-center text-slate-500 py-6">Kayıt yok.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection

@push('styles')
<style>
    @import url('https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;600&display=swap');
    .commission-page {
        --commission-primary: #0f172a;
        --commission-accent: #f97316;
        --commission-surface: #ffffff;
        --commission-muted: #64748b;
        --commission-soft: #fdf2e9;
        --commission-ink: #0b1220;
        font-family: "Space Grotesk", "Fira Sans", "Segoe UI", sans-serif;
        color: var(--commission-ink);
        position: relative;
        isolation: isolate;
        margin-bottom: 10px;
    }
    .commission-page::before {
        content: "";
        position: absolute;
        inset: -40px 0 auto 0;
        height: 280px;
        background: #f8fafc;
        z-index: -1;
    }
    .commission-hero {
        display: grid;
        grid-template-columns: minmax(0, 1fr) minmax(420px, 520px);
        gap: 16px;
        background: linear-gradient(135deg, rgba(255, 255, 255, 0.95), rgba(250, 250, 252, 0.7));
        border-radius: 20px;
        padding: 20px;
        margin-bottom: 10px;
        border: 1px solid #f3e8ff;
        box-shadow: 0 20px 60px rgba(15, 23, 42, 0.08);
    }
    .commission-hero-aside {
        display: flex;
        flex-direction: column;
        align-items: stretch;
        gap: 10px;
        background: rgba(255, 255, 255, 0.85);
        border: 1px solid #e2e8f0;
        border-radius: 16px;
        padding: 14px;
        box-shadow: inset 0 0 0 1px rgba(255, 255, 255, 0.7);
    }
    .commission-hero-title {
        display: flex;
        align-items: center;
        gap: 10px;
    }
    .commission-hero-content h2 {
        font-size: 32px;
        line-height: 1.2;
        font-weight: 600;
        margin-top: 8px;
        max-width: 760px;
    }
    .commission-hero-content p {
        margin-top: 6px;
        font-size: 15px;
        color: var(--commission-muted);
        max-width: 760px;
    }
    .commission-badge-hero {
        display: inline-flex;
        align-items: center;
        padding: 4px 10px;
        border-radius: 999px;
        font-size: 11px;
        font-weight: 600;
        background: linear-gradient(135deg, #fb923c, #f97316);
        color: #fff;
    }
    .commission-status-pill {
        font-size: 11px;
        font-weight: 600;
        padding: 3px 8px;
        border-radius: 999px;
        background: #e0f2fe;
        color: #0369a1;
    }
    .commission-hero-actions {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        justify-content: flex-start;
        gap: 10px;
        width: 100%;
    }
    .commission-hero-actions .btn {
        white-space: nowrap;
    }
    .commission-hero-panel {
        background: transparent;
        border: 0;
        border-radius: 0;
        padding: 0;
        display: grid;
        grid-template-columns: 1fr;
        align-items: end;
        gap: 12px;
        width: 100%;
    }
    .commission-table-toolbar {
        margin-bottom: 12px;
        display: flex;
        justify-content: flex-start;
    }
    .commission-table-filters {
        width: min(980px, 100%);
        display: grid;
        grid-template-columns: minmax(340px, 1.4fr) minmax(200px, 0.8fr) minmax(260px, 1fr);
        gap: 12px;
        padding: 14px;
        border: 1px solid #e2e8f0;
        border-radius: 14px;
        background: linear-gradient(180deg, #ffffff, #f8fafc);
        box-shadow: 0 8px 18px rgba(15, 23, 42, 0.05);
    }
    .commission-filter-wide {
        min-width: 0;
    }
    .commission-filter label {
        font-size: 11px;
        font-weight: 600;
        color: #475569;
    }
    .commission-filter input,
    .commission-filter select {
        margin-top: 6px;
        width: 100%;
        padding: 9px 12px;
        border-radius: 10px;
        border: 1px solid #e2e8f0;
        background: #fff;
        font-size: 14px;
    }
    .commission-toggle {
        margin-top: 6px;
        display: flex;
        flex-wrap: nowrap;
        gap: 12px;
        font-size: 12px;
        color: #475569;
    }
    .commission-toggle label {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        white-space: nowrap;
    }
    .commission-table {
        border-collapse: separate;
        border-spacing: 0 8px;
        table-layout: auto;
        width: 100%;
    }
    .commission-table th:nth-child(1),
    .commission-table td:nth-child(1) {
        width: 44px;
    }
    .commission-table th:nth-child(2),
    .commission-table td:nth-child(2) {
        width: 280px;
    }
    .commission-table th:nth-child(3),
    .commission-table td:nth-child(3) {
        width: 80px;
    }
    .commission-table th:nth-child(4),
    .commission-table td:nth-child(4) {
        width: 110px;
    }
    .commission-table th:nth-child(5),
    .commission-table td:nth-child(5),
    .commission-table th:nth-child(6),
    .commission-table td:nth-child(6),
    .commission-table th:nth-child(7),
    .commission-table td:nth-child(7) {
        width: 220px;
    }
    .commission-table th:nth-child(8),
    .commission-table td:nth-child(8) {
        width: 170px;
    }
    .commission-table-card {
        box-shadow: 0 18px 40px rgba(15, 23, 42, 0.08);
        border: 1px solid #e2e8f0;
        margin-top: 0;
    }
    .commission-table thead tr th {
        font-size: 11px;
        text-transform: uppercase;
        letter-spacing: 0.02em;
        color: #64748b;
        font-weight: 600;
        padding: 12px 10px;
        white-space: nowrap;
        background: #fff7ed;
        border-top: 1px solid #fed7aa;
        border-bottom: 1px solid #fed7aa;
        vertical-align: middle;
        position: relative;
        text-align: left;
        line-height: 1.2;
    }
    .commission-table thead tr th:nth-child(5),
    .commission-table thead tr th:nth-child(6),
    .commission-table thead tr th:nth-child(7) {
        text-align: center;
    }
    .commission-table thead tr th:nth-child(4) {
        text-align: center;
    }
    .commission-table thead tr th:nth-child(2),
    .commission-table thead tr th:nth-child(3),
    .commission-table thead tr th:nth-child(4) {
        border-right: 1px solid #fed7aa;
    }
    .commission-table thead tr th::after {
        content: "";
        position: absolute;
        right: 12px;
        top: 50%;
        width: 6px;
        height: 6px;
        border-right: 2px solid #fb923c;
        border-top: 2px solid #fb923c;
        transform: translateY(-50%) rotate(45deg);
        opacity: 0.6;
    }
    .commission-table thead tr th:first-child::after,
    .commission-table thead tr th:last-child::after {
        display: none;
    }
    .commission-table thead tr th:first-child {
        border-left: 1px solid #fed7aa;
        border-top-left-radius: 12px;
        border-bottom-left-radius: 12px;
    }
    .commission-table thead tr th:last-child {
        border-right: 1px solid #fed7aa;
        border-top-right-radius: 12px;
        border-bottom-right-radius: 12px;
    }
    .commission-table tbody tr td {
        padding: 12px 8px;
        background: #fff;
        border-top: 1px solid #fed7aa;
        border-bottom: 1px solid #fed7aa;
        vertical-align: middle;
        text-align: left;
    }
    .commission-table tbody tr td:nth-child(5),
    .commission-table tbody tr td:nth-child(6),
    .commission-table tbody tr td:nth-child(7),
    .commission-table tbody tr td:nth-child(8) {
        text-align: center;
    }
    .commission-table tbody tr td:nth-child(4) {
        text-align: center;
    }
    .commission-table thead tr th:nth-child(3) {
        text-align: center;
    }
    .commission-table tbody tr td:nth-child(3) {
        text-align: center;
        vertical-align: middle;
    }
    .commission-table tbody tr td:nth-child(2),
    .commission-table tbody tr td:nth-child(3),
    .commission-table tbody tr td:nth-child(4) {
        border-right: 1px solid #fed7aa;
    }
    .commission-table tbody tr {
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }
    .commission-table tbody tr:hover {
        transform: translateY(-2px);
        box-shadow: 0 14px 30px rgba(15, 23, 42, 0.08);
    }
    .commission-table tbody tr td:first-child {
        border-left: 1px solid #fed7aa;
        border-top-left-radius: 12px;
        border-bottom-left-radius: 12px;
    }
    .commission-table tbody tr td:last-child {
        border-right: 1px solid #fed7aa;
        border-top-right-radius: 12px;
        border-bottom-right-radius: 12px;
    }
    .commission-page .btn.btn-solid-accent {
        background: linear-gradient(135deg, #0f172a, #1f2937);
        color: #fff;
    }
    .commission-page .btn.btn-outline {
        border-color: #e2e8f0;
        color: #0f172a;
    }
    @media (max-width: 1024px) {
        .commission-hero {
            grid-template-columns: 1fr;
        }
        .commission-hero-aside {
            align-items: stretch;
        }
        .commission-hero-actions {
            justify-content: flex-start;
        }
        .commission-hero-panel {
            grid-template-columns: 1fr;
        }
        .commission-table-filters {
            grid-template-columns: 1fr;
        }
        .commission-toggle {
            flex-wrap: wrap;
        }
    }
    .commission-card {
        background: #ffffff;
        border: 1px solid #fde2e2;
        border-radius: 12px;
        padding: 10px;
        min-width: 0;
        text-align: center;
        display: flex;
        flex-direction: column;
        align-items: center;
        transition: transform 0.2s ease, box-shadow 0.2s ease, border-color 0.2s ease;
    }
    .commission-card.is-active {
        border-color: #22c55e;
        box-shadow: 0 0 0 1px rgba(34, 197, 94, 0.3);
        background: #f0fdf4;
        transform: translateY(-2px);
    }
    .commission-range {
        font-size: 13px;
        font-weight: 600;
        color: #111827;
    }
    .commission-range .max {
        color: #f97316;
        font-weight: 700;
    }
    .commission-line {
        margin-top: 6px;
        font-size: 11px;
        color: #111827;
        text-align: center;
    }
    .commission-pill {
        margin-top: 8px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 4px 10px;
        border-radius: 999px;
        background: #16a34a;
        color: #fff;
        font-size: 12px;
        font-weight: 600;
    }
    .commission-action {
        margin-top: 8px;
        font-size: 10px;
        border-radius: 9999px;
        padding: 4px 10px;
        border: 1px solid #9ca3af;
        color: #1f2937;
        background: #f3f4f6;
        min-height: 24px;
        min-width: 0;
        width: auto;
        max-width: fit-content;
        text-align: center;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        white-space: nowrap;
        margin-left: auto;
        margin-right: auto;
    }
    .commission-manual-cell {
        display: grid;
        grid-template-columns: 1fr;
        gap: 8px;
        min-width: 0;
    }
    .commission-manual-input {
        width: 100%;
        min-width: 0;
        text-align: center;
    }
    .commission-manual-input::placeholder {
        text-align: center;
    }
    .commission-action.is-selected {
        color: #ffffff;
        background: linear-gradient(135deg, #fb923c, #f97316);
        border-color: #ea580c;
        border-radius: 9999px;
        padding: 4px 10px;
        font-size: 10px;
        font-weight: 700;
        min-height: 0;
        line-height: 1.15;
        box-shadow: 0 4px 10px rgba(249, 115, 22, 0.28);
    }
    .commission-card h4 {
        font-size: 12px;
        font-weight: 600;
        color: #7c2d12;
        margin-bottom: 4px;
    }
    .commission-badge {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 4px 8px;
        border-radius: 999px;
        background: #ecfccb;
        color: #3f6212;
        font-size: 11px;
        font-weight: 600;
    }
    .commission-muted {
        font-size: 11px;
        color: #64748b;
        margin-top: 4px;
    }
    .commission-product {
        display: flex;
        gap: 12px;
        align-items: center;
    }
    .commission-thumb {
        width: 48px;
        height: 48px;
        border-radius: 10px;
        object-fit: cover;
        background: #f1f5f9;
    }
    .commission-product .title {
        font-weight: 600;
        color: #1f2937;
    }
    .commission-product .meta {
        font-size: 11px;
        color: #64748b;
        margin-top: 4px;
    }
    .commission-row-actions {
        display: flex;
        align-items: center;
        gap: 8px;
        margin-top: 6px;
    }
    .commission-action-menu {
        position: relative;
    }
    .commission-action-button {
        border: 1px solid #e2e8f0;
        border-radius: 999px;
        padding: 4px 10px;
        font-size: 11px;
        color: #0f172a;
        background: #f8fafc;
    }
    .commission-action-dropdown {
        position: absolute;
        top: 110%;
        left: 0;
        background: #fff;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        box-shadow: 0 12px 30px rgba(15, 23, 42, 0.12);
        min-width: 160px;
        padding: 6px;
        display: none;
        z-index: 10;
    }
    .commission-action-dropdown button {
        width: 100%;
        text-align: left;
        padding: 6px 8px;
        border-radius: 8px;
        font-size: 12px;
        color: #1f2937;
    }
    .commission-action-dropdown button:hover {
        background: #f1f5f9;
    }
    .commission-action-menu.is-open .commission-action-dropdown {
        display: block;
    }
</style>
@endpush

@push('scripts')
<script>
    const uploadModal = document.getElementById('commission-upload-modal');
    const mapModal = document.getElementById('commission-map-modal');
    const errorsModal = document.getElementById('commission-errors-modal');
    const uploadBtn = document.getElementById('commission-upload-btn');
    const uploadClose = document.getElementById('commission-upload-close');
    const uploadCancel = document.getElementById('commission-upload-cancel');
    const mapClose = document.getElementById('commission-map-close');
    const mapCancel = document.getElementById('commission-map-cancel');
    const mapSave = document.getElementById('commission-map-save');
    const errorsBtn = document.getElementById('commission-errors-btn');
    const errorsClose = document.getElementById('commission-errors-close');
    const errorsBody = document.getElementById('commission-errors-body');
    const mapFields = document.getElementById('commission-map-fields');
    const tableBody = document.getElementById('commission-table-body');
    const paginationInfo = document.getElementById('commission-pagination-info');
    const pageSizeSelect = document.getElementById('commission-page-size');
    const prevBtn = document.getElementById('commission-prev');
    const nextBtn = document.getElementById('commission-next');
    const searchInput = document.getElementById('commission-search');
    const categorySelect = document.getElementById('commission-category');
    const selectAllCheckbox = document.getElementById('commission-select-all');
    const allVariantsCheckbox = document.getElementById('commission-all-variants');

    let currentPage = 1;
    let lastPage = 1;
    let pageSize = Number(pageSizeSelect?.value || 20);
    let latestUploadId = null;
    let currentHeaders = [];

    const systemFields = [
        { key: 'marketplace', label: 'Pazaryeri' },
        { key: 'productId', label: 'Ürün ID' },
        { key: 'merchantSku', label: 'Merchant SKU' },
        { key: 'sku', label: 'SKU' },
        { key: 'barcode', label: 'Barkod' },
        { key: 'range1Min', label: '1. Aralık Min' },
        { key: 'range1Max', label: '1. Aralık Max' },
        { key: 'commission1Percent', label: '1. Komisyon %' },
        { key: 'range2Min', label: '2. Aralık Min' },
        { key: 'range2Max', label: '2. Aralık Max' },
        { key: 'commission2Percent', label: '2. Komisyon %' },
        { key: 'range3Min', label: '3. Aralık Min' },
        { key: 'range3Max', label: '3. Aralık Max' },
        { key: 'commission3Percent', label: '3. Komisyon %' },
    ];

    function toggleModal(modal, show) {
        modal.classList.toggle('hidden', !show);
        modal.classList.toggle('flex', show);
    }

    uploadBtn?.addEventListener('click', () => toggleModal(uploadModal, true));
    uploadClose?.addEventListener('click', () => toggleModal(uploadModal, false));
    uploadCancel?.addEventListener('click', () => toggleModal(uploadModal, false));
    mapClose?.addEventListener('click', () => toggleModal(mapModal, false));
    mapCancel?.addEventListener('click', () => toggleModal(mapModal, false));
    errorsClose?.addEventListener('click', () => toggleModal(errorsModal, false));

    document.getElementById('commission-upload-form')?.addEventListener('submit', async (event) => {
        event.preventDefault();
        const form = event.target;
        const formData = new FormData(form);
        const response = await fetch('{{ route('portal.campaigns.hepsiburada-offers.api.upload') }}', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
            },
            body: formData,
        });
        const data = await response.json();
        if (data.categories && categorySelect.options.length <= 1) {
            data.categories.forEach(cat => {
                const opt = document.createElement('option');
                opt.value = cat.id;
                opt.textContent = cat.name;
                categorySelect.appendChild(opt);
            });
        }
        latestUploadId = data.uploadId;
        currentHeaders = data.headers || [];
        renderMappingFields();
        toggleModal(uploadModal, false);
        toggleModal(mapModal, true);
    });

    function renderMappingFields() {
        mapFields.innerHTML = '';
        systemFields.forEach(field => {
            const wrapper = document.createElement('div');
            wrapper.innerHTML = `
                <label class="block text-xs font-medium text-slate-500 mb-2">${field.label}</label>
                <select data-key="${field.key}" class="w-full px-3 py-2 border border-slate-200 rounded-lg bg-white">
                    <option value="">Seç</option>
                    ${currentHeaders.map(h => `<option value="${h}">${h}</option>`).join('')}
                </select>
            `;
            mapFields.appendChild(wrapper);
        });
    }

    mapSave?.addEventListener('click', async () => {
        const mapping = {};
        mapFields.querySelectorAll('select').forEach(select => {
            if (select.value) {
                mapping[select.dataset.key] = select.value;
            }
        });
        await fetch('{{ route('portal.campaigns.hepsiburada-offers.api.column-map') }}', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                uploadId: latestUploadId,
                mapping,
            }),
        });
        toggleModal(mapModal, false);
    });

    errorsBtn?.addEventListener('click', async () => {
        if (!latestUploadId) {
            toggleModal(errorsModal, true);
            return;
        }
        const response = await fetch(`{{ url('/api/campaigns/hepsiburada-offers/errors') }}/${latestUploadId}`);
        const data = await response.json();
        renderErrors(data.rows || []);
        toggleModal(errorsModal, true);
    });

    function renderErrors(rows) {
        if (!rows.length) {
            errorsBody.innerHTML = '<tr><td colspan="3" class="text-center text-slate-500 py-6">Kayıt yok.</td></tr>';
            return;
        }
        errorsBody.innerHTML = rows.map(row => `
            <tr class="border-t border-slate-100">
                <td class="py-2 px-2">${row.row_no}</td>
                <td class="py-2 px-2 text-red-600">${row.error_message || '-'}</td>
                <td class="py-2 px-2 text-xs text-slate-500">${JSON.stringify(row.raw)}</td>
            </tr>
        `).join('');
    }

    async function loadTable(page = 1) {
        const params = new URLSearchParams({
            page,
            per_page: String(pageSize),
            search: searchInput.value || '',
            category_id: categorySelect.value || '',
        });
        const response = await fetch(`{{ route('portal.campaigns.hepsiburada-offers.api.list') }}?${params.toString()}`);
        const data = await response.json();
        currentPage = data.meta.current_page;
        lastPage = data.meta.last_page;
        paginationInfo.textContent = `Toplam kayıt: ${data.meta.total}`;
        renderTable(data.data || []);
    }

    function renderTable(rows) {
        if (!rows.length) {
            tableBody.innerHTML = '<tr><td colspan="8" class="text-center text-slate-500 py-6">Kayıt bulunamadı.</td></tr>';
            return;
        }
        tableBody.innerHTML = rows.map(row => `
            <tr>
                <td><input type="checkbox" class="commission-select" data-variant="${row.variant_id}" data-product="${row.product_id}"></td>
                <td>
                    <div class="commission-product">
                        <div class="commission-thumb"></div>
                        <div>
                            <div class="title">${row.name || '-'}</div>
                            <div class="meta">SKU: ${row.sku || '-'} • Barkod: ${row.barcode || '-'}</div>
                            <div class="commission-row-actions">
                                <div class="commission-action-menu">
                                    <button type="button" class="commission-action-button">Hızlı Aksiyon</button>
                                    <div class="commission-action-dropdown">
                                        <button type="button">Varyantı Seç</button>
                                        <button type="button">Satır Detayı</button>
                                        <button type="button">Atamaları Sıfırla</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </td>
                <td>${row.stock ?? 0}</td>
                <td>${row.current_price ?? 0}</td>
                ${row.ranges.map((r, idx) => renderRangeCard(r, row.current_price, idx + 1)).join('')}
                <td>
                    <div class="commission-manual-cell">
                        <input type="number" step="0.01" class="commission-manual-input px-2 py-1 border border-slate-200 rounded" placeholder="Manuel Fiyat">
                        <button class="btn btn-outline btn-sm commission-calc-btn w-full" data-product="${row.product_id}" data-variant="${row.variant_id}">Hesapla</button>
                    </div>
                </td>
            </tr>
        `).join('');
        bindCalcButtons();
    }

    function renderRangeCard(range, currentPrice, index) {
        const profitText = `${formatMoney(range.profit ?? 0)} Kâr (${formatNumber(range.profit_rate ?? 0)}%)`;
        const isActive = currentPrice !== null && currentPrice !== undefined
            && range.min !== null && range.max !== null
            && Number(currentPrice) >= Number(range.min) && Number(currentPrice) <= Number(range.max);
        return `
            <td>
                <div class="commission-card ${isActive ? 'is-active' : ''}" data-range-index="${index}">
                    <div class="commission-range">
                        ₺${formatMoney(range.min ?? 0)} - <span class="max">₺${formatMoney(range.max ?? 0)}</span>
                    </div>
                    <div class="commission-line">Komisyon ${formatNumber(range.commission_percent ?? 0)}</div>
                    <div class="commission-pill">₺${profitText}</div>
                    <button type="button" class="commission-action" data-range="${index}">Varyantı Seç</button>
                </div>
            </td>
        `;
    }

    function bindCalcButtons() {
        document.querySelectorAll('.commission-calc-btn').forEach(btn => {
            btn.addEventListener('click', async (event) => {
                const row = event.target.closest('tr');
                const input = row.querySelector('input[type="number"]');
                const manualPrice = input.value || 0;
                const productId = btn.dataset.product;
                const variantId = btn.dataset.variant;
                const response = await fetch('{{ route('portal.campaigns.hepsiburada-offers.api.recalc') }}', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ productId, variantId, manualPrice }),
                });
                const data = await response.json();
                if (data.ranges) {
                    const cells = row.querySelectorAll('td');
                    for (let i = 0; i < 3; i++) {
                        const cell = cells[4 + i];
                        if (cell) {
                            cell.innerHTML = renderRangeCard(data.ranges[i], manualPrice, i + 1);
                        }
                    }
                    row.querySelectorAll('.commission-card').forEach(card => card.classList.remove('is-active'));
                    if (data.chosenRange) {
                        const active = row.querySelector(`.commission-card[data-range-index="${data.chosenRange}"]`);
                        active?.classList.add('is-active');
                    }
                }
            });
        });

        document.querySelectorAll('.commission-action').forEach(btn => {
            btn.addEventListener('click', () => {
                const row = btn.closest('tr');
                const isSelected = btn.classList.contains('is-selected');

                row.querySelectorAll('.commission-action').forEach(other => {
                    other.classList.remove('is-selected');
                    other.textContent = 'Varyantı Seç';
                });

                if (!isSelected) {
                    btn.classList.add('is-selected');
                    btn.textContent = 'Varyantı Kaldır';
                }
            });
        });

        document.querySelectorAll('.commission-action-button').forEach(btn => {
            btn.addEventListener('click', (event) => {
                event.stopPropagation();
                const menu = btn.closest('.commission-action-menu');
                menu.classList.toggle('is-open');
            });
        });
    }

    document.addEventListener('click', () => {
        document.querySelectorAll('.commission-action-menu.is-open').forEach(menu => {
            menu.classList.remove('is-open');
        });
    });

    document.getElementById('commission-bulk-download')?.addEventListener('click', async () => {
        const selected = Array.from(document.querySelectorAll('.commission-select:checked'))
            .map(el => Number(el.dataset.variant));
        const response = await fetch('{{ route('portal.campaigns.hepsiburada-offers.api.export') }}', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ selected }),
        });
        const blob = await response.blob();
        const link = document.createElement('a');
        link.href = window.URL.createObjectURL(blob);
        link.download = 'komisyon-tarifeleri.xlsx';
        document.body.appendChild(link);
        link.click();
        link.remove();
    });

    selectAllCheckbox?.addEventListener('change', (event) => {
        const checked = event.target.checked;
        document.querySelectorAll('.commission-select').forEach(el => {
            el.checked = checked;
        });
    });


    prevBtn?.addEventListener('click', () => {
        if (currentPage > 1) loadTable(currentPage - 1);
    });
    nextBtn?.addEventListener('click', () => {
        if (currentPage < lastPage) loadTable(currentPage + 1);
    });
    pageSizeSelect?.addEventListener('change', () => {
        pageSize = Number(pageSizeSelect.value || 20);
        loadTable(1);
    });
    searchInput?.addEventListener('change', () => loadTable(1));
    categorySelect?.addEventListener('change', () => loadTable(1));

    function formatMoney(value) {
        const number = Number(value) || 0;
        return new Intl.NumberFormat('tr-TR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(number);
    }

    function formatNumber(value) {
        const number = Number(value) || 0;
        return new Intl.NumberFormat('tr-TR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(number);
    }

    loadTable(1);
</script>
@endpush










