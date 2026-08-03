@props([
    'paginator',
    'label' => 'Pagination',
])

@if ($paginator->hasPages())
    @php
        $currentPage = $paginator->currentPage();
        $lastPage = $paginator->lastPage();
        $windowStart = max(1, $currentPage - 2);
        $windowEnd = min($lastPage, $currentPage + 2);
        $pages = collect([1, $lastPage])
            ->merge(range($windowStart, $windowEnd))
            ->unique()
            ->sort()
            ->values();
        $previousRenderedPage = null;
    @endphp

    <nav class="portal-pagination" role="navigation" aria-label="{{ $label }}">
        @if ($paginator->onFirstPage())
            <span class="portal-page-button is-disabled" aria-disabled="true" aria-label="Previous page">
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m15 18-6-6 6-6"/></svg>
            </span>
        @else
            <a class="portal-page-button" href="{{ $paginator->previousPageUrl() }}" rel="prev" aria-label="Previous page">
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m15 18-6-6 6-6"/></svg>
            </a>
        @endif

        @foreach ($pages as $page)
            @if ($previousRenderedPage !== null && $page > $previousRenderedPage + 1)
                <span class="portal-page-ellipsis" aria-hidden="true">&hellip;</span>
            @endif

            @if ($page === $currentPage)
                <span class="portal-page-button is-active" aria-current="page">{{ $page }}</span>
            @else
                <a class="portal-page-button" href="{{ $paginator->url($page) }}" aria-label="Go to page {{ $page }}">{{ $page }}</a>
            @endif

            @php($previousRenderedPage = $page)
        @endforeach

        @if ($paginator->hasMorePages())
            <a class="portal-page-button" href="{{ $paginator->nextPageUrl() }}" rel="next" aria-label="Next page">
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m9 18 6-6-6-6"/></svg>
            </a>
        @else
            <span class="portal-page-button is-disabled" aria-disabled="true" aria-label="Next page">
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m9 18 6-6-6-6"/></svg>
            </span>
        @endif
    </nav>
@endif
