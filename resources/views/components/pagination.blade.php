@props([
    'paginator',
    'label' => 'Pagination',
    'perPageOptions' => [5, 10, 25, 50],
    'mode' => 'full',
])

@if ($paginator->total() > 0)
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
        $perPage = $paginator->perPage();
        $previousRenderedPage = null;
    @endphp

    @if ($mode === 'full')
    <div class="portal-pagination-bar">
    @endif

        @if ($mode !== 'navigation')
        <div class="portal-pagination-meta">
            <form class="portal-page-size" method="GET" action="{{ url()->current() }}">
                @foreach (request()->except(['page', 'per_page']) as $name => $value)
                    @if (is_scalar($value))
                        <input type="hidden" name="{{ $name }}" value="{{ $value }}">
                    @endif
                @endforeach
                <label>
                    <span>Show</span>
                    <select name="per_page" aria-label="Records per page" onchange="this.form.submit()">
                        @foreach ($perPageOptions as $option)
                            <option value="{{ $option }}" @selected($perPage === $option)>{{ $option }}</option>
                        @endforeach
                    </select>
                    <span>records</span>
                </label>
            </form>

            <p class="portal-pagination-summary" aria-live="polite">
                @if ($paginator->hasPages())
                    Page <strong>{{ number_format($currentPage) }}</strong> of <strong>{{ number_format($lastPage) }}</strong>
                    <span class="portal-pagination-separator" aria-hidden="true">&middot;</span>
                @endif
                Showing <strong>{{ number_format($paginator->firstItem()) }}&ndash;{{ number_format($paginator->lastItem()) }}</strong>
                of <strong>{{ number_format($paginator->total()) }}</strong> records
            </p>
        </div>
        @endif

        @if ($mode !== 'summary' && $paginator->hasPages())
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

    @if ($mode === 'full')
    </div>
    @endif
@endif
