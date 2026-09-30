{{-- Pagination de la liste des résidences. Paramètre : $paginator (conserve les filtres dans l'adresse) --}}
@if ($paginator->hasPages())
    @php
        $current = $paginator->currentPage();
        $last = $paginator->lastPage();
        // Première, dernière et pages voisines de la page en cours ; les trous sont remplacés par « … »
        $pages = collect([1, $last, $current - 1, $current, $current + 1])
            ->filter(fn (int $page): bool => $page >= 1 && $page <= $last)
            ->unique()
            ->sort()
            ->values();
    @endphp

    <nav class="listing-pagination" aria-label="Pagination des résidences">
        <p>
            {{ $paginator->firstItem() }}–{{ $paginator->lastItem() }} sur {{ $paginator->total() }}
        </p>

        <ul>
            <li>
                @if ($paginator->onFirstPage())
                    <span class="listing-page is-disabled" aria-hidden="true"><i class="fa-solid fa-chevron-left"></i></span>
                @else
                    <a href="{{ $paginator->previousPageUrl() }}" class="listing-page" rel="prev" aria-label="Page précédente"><i class="fa-solid fa-chevron-left"></i></a>
                @endif
            </li>

            @foreach ($pages as $i => $page)
                @if ($i > 0 && $page - $pages[$i - 1] > 1)
                    <li><span class="listing-page is-gap">…</span></li>
                @endif
                <li>
                    @if ($page === $current)
                        <span class="listing-page is-active" aria-current="page">{{ $page }}</span>
                    @else
                        <a href="{{ $paginator->url($page) }}" class="listing-page">{{ $page }}</a>
                    @endif
                </li>
            @endforeach

            <li>
                @if ($paginator->hasMorePages())
                    <a href="{{ $paginator->nextPageUrl() }}" class="listing-page" rel="next" aria-label="Page suivante"><i class="fa-solid fa-chevron-right"></i></a>
                @else
                    <span class="listing-page is-disabled" aria-hidden="true"><i class="fa-solid fa-chevron-right"></i></span>
                @endif
            </li>
        </ul>
    </nav>
@endif
