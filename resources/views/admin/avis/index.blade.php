@extends('layouts.admin')

@php
    use App\Http\Controllers\Admin\ReviewController;

    $isAdmin = auth()->user()->isAdmin();
    $scoreTone = fn (int $rating): string => match (true) {
        $rating >= 9 => 'is-excellent',
        $rating >= 7 => 'is-good',
        default => 'is-low',
    };
@endphp

@section('title', 'Avis')

@section('content')
    <div class="admin-page-header">
        <div class="admin-page-heading">
            <span class="admin-page-icon"><i class="fa-solid fa-star"></i></span>
            <div>
                <h1>Avis des voyageurs</h1>
                <p>{{ $isAdmin ? 'Tous les avis publiés sur le site : modérez les avis signalés par les établissements.' : 'Les avis laissés sur vos établissements : répondez-y publiquement, signalez un avis abusif.' }}</p>
            </div>
        </div>

        <div class="admin-page-actions">
            @include('admin.partials.export-button', ['route' => 'admin.avis.export'])
        </div>
    </div>

    @include('partials.flash')

    {{-- ========== Synthèse ========== --}}
    <div class="resa-today">
        <div class="resa-today-card tone-gold">
            <span class="resa-today-icon"><i class="fa-solid fa-star"></i></span>
            <span class="resa-today-text">
                <strong>{{ $summary['average'] !== null ? number_format($summary['average'], 1, ',', ' ').' / 10' : '—' }}</strong>
                <span>Note moyenne · {{ $summary['published'] }} avis publié{{ $summary['published'] > 1 ? 's' : '' }}</span>
            </span>
        </div>
        <div class="resa-today-card tone-info">
            <span class="resa-today-icon"><i class="fa-solid fa-calendar-plus"></i></span>
            <span class="resa-today-text">
                <strong>{{ $summary['thisMonth'] }}</strong>
                <span>Reçu{{ $summary['thisMonth'] > 1 ? 's' : '' }} ce mois-ci</span>
            </span>
        </div>
        <a href="{{ request()->fullUrlWithQuery(['statut' => 'sans-reponse', 'page' => null]) }}" class="resa-today-card tone-neutral">
            <span class="resa-today-icon"><i class="fa-solid fa-reply"></i></span>
            <span class="resa-today-text">
                <strong>{{ $summary['unanswered'] }}</strong>
                <span>Sans réponse{{ $summary['replyRate'] !== null ? ' · '.$summary['replyRate'].' % de réponses' : '' }}</span>
            </span>
        </a>
        <a href="{{ request()->fullUrlWithQuery(['statut' => 'signales', 'page' => null]) }}" class="resa-today-card {{ $summary['reported'] > 0 ? 'tone-warning' : 'tone-good' }}">
            <span class="resa-today-icon"><i class="fa-solid fa-flag"></i></span>
            <span class="resa-today-text">
                <strong>{{ $summary['reported'] }}</strong>
                <span>{{ $isAdmin ? 'Signalé'.($summary['reported'] > 1 ? 's' : '').' à examiner' : 'Signalé'.($summary['reported'] > 1 ? 's' : '').' en cours d’examen' }}</span>
            </span>
        </a>
    </div>

    {{-- ========== Filtres ========== --}}
    <section class="resa-filters">
        @include('admin.partials.list-toolbar', ['counts' => $counts, 'statut' => $statut, 'search' => $search, 'tabs' => ReviewController::TABS, 'placeholder' => 'Rechercher un voyageur, un mot, une réservation…'])

        <form method="GET" class="resa-filter-row" aria-label="Filtrer les avis">
            @foreach (request()->except(['etablissement', 'note', 'page']) as $name => $value)
                @if (is_string($value) && $value !== '')
                    <input type="hidden" name="{{ $name }}" value="{{ $value }}">
                @endif
            @endforeach

            <div class="period-chips" role="group" aria-label="Note">
                <span class="period-chips-label"><i class="fa-solid fa-star-half-stroke"></i> Note</span>
                <a href="{{ request()->fullUrlWithQuery(['note' => null, 'page' => null]) }}" class="period-chip {{ $rating === null ? 'is-active' : '' }}">Toutes</a>
                @foreach (ReviewController::RATINGS as $key => [$label])
                    <a href="{{ request()->fullUrlWithQuery(['note' => $key, 'page' => null]) }}" class="period-chip {{ $rating === $key ? 'is-active' : '' }}">{{ $label }}</a>
                @endforeach
            </div>

            @if ($properties->count() > 1)
                <div class="list-filter">
                    <i class="fa-solid fa-building"></i>
                    <select name="etablissement" data-auto-submit aria-label="Établissement">
                        <option value="">Tous les établissements</option>
                        @foreach ($properties as $option)
                            <option value="{{ $option->slug }}" @selected($property?->is($option))>{{ $option->name }}</option>
                        @endforeach
                    </select>
                </div>
            @endif
            <input type="hidden" name="note" value="{{ $rating }}">
        </form>
    </section>

    {{-- ========== Liste ========== --}}
    <div class="table-card resa-table-card">
        <table class="custom-table resa-table">
            <thead>
                <tr>
                    <th>Avis</th>
                    <th>Établissement</th>
                    <th>Note</th>
                    <th>Statut</th>
                    <th>Action</th>
                </tr>
            </thead>

            <tbody>
                @forelse ($reviews as $review)
                    <tr class="{{ $review->isPublished() ? '' : 'is-muted-row' }}">
                        <td class="cell-main">
                            <div class="cell-entity">
                                <img src="{{ $review->user?->avatarUrl() ?? 'https://ui-avatars.com/api/?name=Voyageur' }}" alt="" class="cell-avatar">
                                <span class="cell-entity-text">
                                    <a href="{{ route('admin.avis.show', $review) }}" class="cell-title-link">
                                        <strong>{{ $review->title ?: Str::limit($review->comment, 50) }}</strong>
                                    </a>
                                    <small>{{ $review->authorName() }} · {{ $review->created_at->translatedFormat('d M Y') }}</small>
                                </span>
                            </div>
                        </td>
                        <td>
                            <span class="d-block">{{ $review->property?->name }}</span>
                            <small class="cell-muted">{{ $review->reservation?->reference }}</small>
                        </td>
                        <td>
                            <span class="review-score {{ $scoreTone($review->rating) }}">{{ $review->rating }}<small>/10</small></span>
                            <small class="cell-muted d-block">{{ $review->ratingLabel() }}</small>
                        </td>
                        <td>
                            <span class="status-pill status-{{ $review->statut->tone() }}">{{ $review->statut->label() }}</span>
                            <span class="review-flags">
                                @if ($review->owner_reply)
                                    <span class="review-flag is-replied" title="Réponse publiée le {{ $review->replied_at?->translatedFormat('d M Y') }}"><i class="fa-solid fa-reply"></i> Répondu</span>
                                @elseif ($review->isPublished())
                                    <span class="review-flag is-waiting"><i class="fa-regular fa-clock"></i> Sans réponse</span>
                                @endif
                                @if ($review->isReported() && $review->isPublished())
                                    <span class="review-flag is-reported" title="{{ $review->report_reason }}"><i class="fa-solid fa-flag"></i> Signalé</span>
                                @endif
                            </span>
                        </td>
                        <td>
                            <span class="invoice-actions">
                                <a href="{{ route('admin.avis.show', $review) }}" class="action-btn view" title="{{ $isAdmin ? 'Voir et modérer' : 'Voir et répondre' }}" aria-label="Voir l’avis de {{ $review->authorName() }}">
                                    <i class="fa-solid {{ $isAdmin ? 'fa-eye' : 'fa-reply' }}"></i>
                                </a>
                            </span>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="empty-table">
                            @include('admin.partials.empty-state', ['icon' => 'fa-star', 'search' => $search])
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @include('admin.partials.pagination', ['paginator' => $reviews, 'label' => 'avis'])
@endsection
