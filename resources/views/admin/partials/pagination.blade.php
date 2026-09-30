{{-- Pagination au style DS Holding. Paramètres : $paginator, $label (ex. « établissement(s) »). --}}
<div class="pagination-wrapper">

    <div class="pagination-info">
        Affichage de <strong>{{ $paginator->count() }}</strong>
        sur <strong>{{ $paginator->total() }}</strong>
        {{ $label }}
    </div>

    @if ($paginator->hasPages())
        <div class="pagination">

            @unless ($paginator->onFirstPage())
                <a href="{{ $paginator->previousPageUrl() }}" class="pagination-btn" aria-label="Page précédente">
                    <i class="fa-solid fa-chevron-left"></i>
                </a>
            @endunless

            @foreach ($paginator->getUrlRange(1, $paginator->lastPage()) as $page => $url)
                <a href="{{ $url }}" class="pagination-btn {{ $page === $paginator->currentPage() ? 'active' : '' }}">{{ $page }}</a>
            @endforeach

            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" class="pagination-btn" aria-label="Page suivante">
                    <i class="fa-solid fa-chevron-right"></i>
                </a>
            @endif

        </div>
    @endif

</div>
