@php
    /** @var \Illuminate\Contracts\Pagination\LengthAwarePaginator|\Illuminate\Pagination\LengthAwarePaginator $paginator */
    $paginator = $paginator ?? null;
    $perPageName = $perPageName ?? 'per_page';
    $perPageOptions = $perPageOptions ?? [10, 25, 50, 100];
    $perPageLabel = $perPageLabel ?? 'Sayfa başına';
    $query = request()->query();
    $selectedPerPage = (int) request()->query($perPageName, $paginator?->perPage() ?? 25);
@endphp

@if($paginator)
    @once
        @push('styles')
            <style>
                .modern-pagination-bar {
                    display: flex;
                    align-items: center;
                    justify-content: space-between;
                    gap: 14px;
                    padding: 14px 16px;
                    border: 1px solid #dbe3ee;
                    border-radius: 12px;
                    background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
                }
                .modern-pagination-left {
                    display: inline-flex;
                    align-items: center;
                    gap: 10px;
                    color: #0f172a;
                    font-weight: 700;
                    font-size: 13px;
                }
                .modern-pagination-select {
                    min-width: 84px;
                    height: 44px;
                    padding: 0 12px;
                    border: 1px solid #d1d9e4;
                    border-radius: 8px;
                    background: #fff;
                    color: #111827;
                    font-size: 13px;
                    font-weight: 600;
                }
                .modern-pagination-right {
                    display: inline-flex;
                    align-items: center;
                    justify-content: flex-end;
                    gap: 12px;
                    flex-wrap: wrap;
                }
                .modern-pagination-info {
                    color: #475569;
                    font-size: 14px;
                    font-weight: 500;
                }
                .modern-pagination-nav {
                    display: inline-flex;
                    align-items: stretch;
                    border: 1px solid #d1d9e4;
                    border-radius: 8px;
                    overflow: hidden;
                    background: #fff;
                }
                .modern-page-link,
                .modern-page-gap {
                    min-width: 46px;
                    height: 42px;
                    padding: 0 12px;
                    display: inline-flex;
                    align-items: center;
                    justify-content: center;
                    border-right: 1px solid #d1d9e4;
                    color: #334155;
                    font-size: 13px;
                    font-weight: 600;
                }
                .modern-page-link:last-child,
                .modern-page-gap:last-child {
                    border-right: 0;
                }
                .modern-page-link:hover {
                    background: #f8fafc;
                }
                .modern-page-link.is-active {
                    background: #eef2f7;
                    color: #0f172a;
                    pointer-events: none;
                }
                .modern-page-link.is-disabled {
                    color: #9ca3af;
                    background: #f8fafc;
                    pointer-events: none;
                }
                .modern-page-gap {
                    color: #94a3b8;
                    background: #fff;
                }
                @media (max-width: 900px) {
                    .modern-pagination-bar {
                        flex-direction: column;
                        align-items: stretch;
                    }
                    .modern-pagination-right {
                        justify-content: space-between;
                    }
                }
            </style>
        @endpush
    @endonce

    @php
        $firstItem = $paginator->total() > 0 ? $paginator->firstItem() : 0;
        $lastItem = $paginator->total() > 0 ? $paginator->lastItem() : 0;
        $currentPage = $paginator->currentPage();
        $lastPage = $paginator->lastPage();
        $pages = collect([1, $currentPage - 1, $currentPage, $currentPage + 1, $lastPage])
            ->filter(fn ($page) => $page >= 1 && $page <= $lastPage)
            ->unique()
            ->sort()
            ->values();
    @endphp

    <div class="modern-pagination-bar mt-4">
        <div class="modern-pagination-left">
            <span>{{ $perPageLabel }}</span>
            <form method="GET" action="{{ url()->current() }}">
                @foreach($query as $key => $value)
                    @continue($key === $perPageName || $key === 'page')
                    @if(is_array($value))
                        @foreach($value as $item)
                            <input type="hidden" name="{{ $key }}[]" value="{{ $item }}">
                        @endforeach
                    @else
                        <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                    @endif
                @endforeach
                <select name="{{ $perPageName }}" class="modern-pagination-select" onchange="this.form.submit()">
                    @foreach($perPageOptions as $option)
                        <option value="{{ $option }}" @selected($selectedPerPage === (int) $option)>{{ $option }}</option>
                    @endforeach
                </select>
            </form>
        </div>

        <div class="modern-pagination-right">
            <div class="modern-pagination-info">
                {{ $firstItem }} - {{ $lastItem }} arası, toplam {{ $paginator->total() }} sonuç
            </div>

            <div class="modern-pagination-nav">
                <a href="{{ $paginator->onFirstPage() ? '#' : $paginator->previousPageUrl() }}"
                   class="modern-page-link {{ $paginator->onFirstPage() ? 'is-disabled' : '' }}"
                   aria-label="Previous page">
                    <i class="fa-solid fa-chevron-left text-[12px]"></i>
                </a>

                @php $lastRendered = 0; @endphp
                @foreach($pages as $page)
                    @if($lastRendered > 0 && $page - $lastRendered > 1)
                        <span class="modern-page-gap">...</span>
                    @endif
                    <a href="{{ $paginator->url($page) }}"
                       class="modern-page-link {{ $page === $currentPage ? 'is-active' : '' }}">
                        {{ $page }}
                    </a>
                    @php $lastRendered = $page; @endphp
                @endforeach

                <a href="{{ $paginator->hasMorePages() ? $paginator->nextPageUrl() : '#' }}"
                   class="modern-page-link {{ $paginator->hasMorePages() ? '' : 'is-disabled' }}"
                   aria-label="Next page">
                    <i class="fa-solid fa-chevron-right text-[12px]"></i>
                </a>
            </div>
        </div>
    </div>
@endif
