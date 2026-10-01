@extends('layouts.admin')

@php
    $criteria = [
        'cleanliness' => 'Propreté',
        'comfort' => 'Confort',
        'location' => 'Emplacement',
        'staff' => 'Accueil',
        'value_for_money' => 'Qualité-prix',
    ];
    $scoreTone = match (true) {
        $review->rating >= 9 => 'is-excellent',
        $review->rating >= 7 => 'is-good',
        default => 'is-low',
    };
    $reservation = $review->reservation;
    $canReply = auth()->user()->can('reply', $review);
    $canReport = auth()->user()->can('report', $review);
    $canModerate = auth()->user()->can('moderate', $review);
@endphp

@section('title', 'Avis de '.$review->authorName())

@section('content')
    <header class="resa-header">
        <a href="{{ route('admin.avis.index') }}" class="resa-back">
            <i class="fa-solid fa-arrow-left"></i>
            Avis
        </a>

        <div class="resa-header-main">
            <div class="cell-entity">
                <img src="{{ $review->user?->avatarUrl() ?? 'https://ui-avatars.com/api/?name=Voyageur' }}" alt="" class="profile-hero-avatar">
                <div class="resa-header-title">
                    <div class="resa-header-line">
                        <h1 class="plain-title">Avis de {{ $review->authorName() }}</h1>
                        <span class="status-pill status-{{ $review->statut->tone() }} status-pill-lg">{{ $review->statut->label() }}</span>
                        @if ($review->isReported() && $review->isPublished())
                            <span class="review-flag is-reported"><i class="fa-solid fa-flag"></i> Signalé</span>
                        @endif
                    </div>
                    <p>
                        {{ $review->property?->name }}
                        <span class="dot-sep">·</span>
                        Publié le {{ $review->created_at->translatedFormat('d F Y') }}
                        @if ($review->property && $review->isPublished())
                            <span class="dot-sep">·</span>
                            <a href="{{ route('residences.show', $review->property) }}#avis" target="_blank" rel="noopener">Voir sur le site</a>
                        @endif
                    </p>
                </div>
            </div>
        </div>
    </header>

    @include('partials.flash')

    @unless ($review->isPublished())
        <div class="review-banner is-hidden">
            <i class="fa-solid fa-eye-slash"></i>
            <div>
                <strong>Avis masqué par la modération{{ $review->moderated_at ? ' le '.$review->moderated_at->translatedFormat('d F Y') : '' }}</strong>
                <span>Il n’apparaît plus sur le site et ne compte plus dans la note de l’établissement.@if ($review->moderation_note) Motif : « {{ $review->moderation_note }} »@endif</span>
            </div>
        </div>
    @endunless

    <div class="resa-layout">
        <div class="resa-main">

            {{-- ========== Avis ========== --}}
            <section class="dash-card">
                <div class="review-detail">
                    <div class="review-detail-score {{ $scoreTone }}">
                        <strong>{{ $review->rating }}</strong>
                        <span>/10</span>
                        <small>{{ $review->ratingLabel() }}</small>
                    </div>
                    <div class="review-detail-body">
                        @if ($review->title)
                            <h2>« {{ $review->title }} »</h2>
                        @endif
                        <p>{!! nl2br(e($review->comment)) !!}</p>
                    </div>
                </div>

                @if (collect($criteria)->keys()->contains(fn ($column) => $review->{$column}))
                    <ul class="review-criteria">
                        @foreach ($criteria as $column => $label)
                            @continue(! $review->{$column})
                            <li>
                                <span>{{ $label }}</span>
                                <span class="review-criteria-bar"><span style="width: {{ $review->{$column} * 10 }}%"></span></span>
                                <b>{{ $review->{$column} }}</b>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </section>

            {{-- ========== Réponse de l'établissement ========== --}}
            <section class="dash-card" id="reponse">
                <div class="dash-card-header">
                    <div>
                        <h2>Réponse de l’établissement</h2>
                        <p>Publiée sous l’avis, sur la fiche de la résidence. Le voyageur est prévenu de votre première réponse.</p>
                    </div>
                </div>

                @if ($canReply)
                    <form method="POST" action="{{ route('admin.avis.reply', $review) }}" class="admin-form">
                        @csrf
                        @method('PUT')
                        <div class="form-group">
                            <label for="reponse" class="visually-hidden">Votre réponse</label>
                            <textarea name="reponse" id="reponse" rows="5" maxlength="1500"
                                placeholder="Remerciez le voyageur, répondez à ses remarques avec courtoisie : votre réponse est lue par les futurs clients.">{{ old('reponse', $review->owner_reply) }}</textarea>
                            @error('reponse') <p class="field-error">{{ $message }}</p> @enderror
                            @if ($review->owner_reply)
                                <p class="field-help">Publiée le {{ $review->replied_at?->translatedFormat('d F Y à H:i') }}. Videz le champ pour la retirer.</p>
                            @endif
                        </div>
                        <div class="review-actions">
                            <button type="submit" class="btn-primary">
                                <i class="fa-solid fa-paper-plane"></i>
                                {{ $review->owner_reply ? 'Modifier la réponse' : 'Publier la réponse' }}
                            </button>
                        </div>
                    </form>
                @elseif ($review->owner_reply)
                    <blockquote class="review-reply">
                        <p>{!! nl2br(e($review->owner_reply)) !!}</p>
                        <footer>{{ $review->property?->name }} · {{ $review->replied_at?->translatedFormat('d F Y') }}</footer>
                    </blockquote>
                @else
                    <p class="resa-note">L’établissement n’a pas encore répondu.</p>
                @endif
            </section>
        </div>

        <aside class="resa-aside">

            {{-- ========== Modération (administrateurs) ========== --}}
            @if ($canModerate)
                <section class="dash-card">
                    <div class="dash-card-header">
                        <div>
                            <h2>Modération</h2>
                            <p>Masquez un avis injurieux, diffamatoire ou sans rapport avec le séjour.</p>
                        </div>
                    </div>

                    @if ($review->isReported() && $review->isPublished())
                        <div class="review-report">
                            <strong><i class="fa-solid fa-flag"></i> Signalé par {{ $review->reporter?->name ?? 'l’établissement' }}</strong>
                            <small>le {{ $review->reported_at->translatedFormat('d F Y à H:i') }}</small>
                            <p>« {{ $review->report_reason }} »</p>
                        </div>
                    @endif

                    @if ($review->isPublished())
                        <form method="POST" action="{{ route('admin.avis.hide', $review) }}" class="admin-form" data-confirm="Masquer cet avis ? Il disparaîtra du site et de la note de l’établissement ; l’établissement et le voyageur seront prévenus.">
                            @csrf
                            @method('PATCH')
                            <div class="form-group">
                                <label for="motif">Motif du masquage <span class="required">*</span></label>
                                <textarea name="motif" id="motif" rows="3" maxlength="500" placeholder="Propos injurieux, avis sans rapport avec le séjour…">{{ old('motif') }}</textarea>
                                @error('motif') <p class="field-error">{{ $message }}</p> @enderror
                                <p class="field-help">Communiqué à l’établissement.</p>
                            </div>
                            <button type="submit" class="btn-outline-danger review-block-btn"><i class="fa-solid fa-eye-slash"></i> Masquer l’avis</button>
                        </form>

                        @if ($review->isReported())
                            <form method="POST" action="{{ route('admin.avis.publish', $review) }}" class="review-keep">
                                @csrf
                                @method('PATCH')
                                <button type="submit" class="btn-secondary review-block-btn"><i class="fa-solid fa-check"></i> Maintenir en ligne et clore le signalement</button>
                            </form>
                        @endif
                    @else
                        <form method="POST" action="{{ route('admin.avis.publish', $review) }}">
                            @csrf
                            @method('PATCH')
                            <button type="submit" class="btn-secondary review-block-btn"><i class="fa-solid fa-eye"></i> Remettre l’avis en ligne</button>
                        </form>
                    @endif

                    @if ($review->moderated_at && $review->moderator)
                        <p class="field-help review-history">Dernière décision le {{ $review->moderated_at->translatedFormat('d F Y à H:i') }} par {{ $review->moderator->name }}.</p>
                    @endif
                </section>
            @endif

            {{-- ========== Signalement (propriétaire) ========== --}}
            @if (auth()->user()->isOwner())
                <section class="dash-card">
                    <div class="dash-card-header">
                        <div>
                            <h2>Signaler l’avis</h2>
                            <p>Un avis injurieux, diffamatoire ou sans rapport avec le séjour ? L’équipe DS HOLDING l’examine.</p>
                        </div>
                    </div>

                    @if ($canReport)
                        <form method="POST" action="{{ route('admin.avis.report', $review) }}" class="admin-form">
                            @csrf
                            <div class="form-group">
                                <label for="motif">Motif <span class="required">*</span></label>
                                <textarea name="motif" id="motif" rows="3" maxlength="500" placeholder="Expliquez ce qui pose problème dans cet avis.">{{ old('motif') }}</textarea>
                                @error('motif') <p class="field-error">{{ $message }}</p> @enderror
                            </div>
                            <button type="submit" class="btn-secondary review-block-btn"><i class="fa-solid fa-flag"></i> Signaler à DS HOLDING</button>
                        </form>
                    @elseif ($review->isReported() && $review->isPublished())
                        <div class="review-report">
                            <strong><i class="fa-solid fa-hourglass-half"></i> Signalement en cours d’examen</strong>
                            <small>envoyé le {{ $review->reported_at->translatedFormat('d F Y') }}</small>
                            <p>« {{ $review->report_reason }} »</p>
                        </div>
                    @else
                        <p class="resa-note">Cet avis a été traité par l’équipe DS HOLDING.</p>
                    @endif
                </section>
            @endif

            {{-- ========== Séjour ========== --}}
            <section class="dash-card">
                <div class="dash-card-header">
                    <div>
                        <h2>Séjour</h2>
                    </div>
                </div>
                <dl class="resa-amounts review-stay">
                    <div><dt>Établissement</dt><dd>{{ $review->property?->name }}</dd></div>
                    @if ($reservation)
                        <div><dt>Réservation</dt><dd><a href="{{ route('admin.reservations.show', $reservation) }}">{{ $reservation->reference }}</a></dd></div>
                        <div><dt>Dates</dt><dd>{{ $reservation->check_in->translatedFormat('d M') }} → {{ $reservation->check_out->translatedFormat('d M Y') }}</dd></div>
                        <div><dt>Voyageurs</dt><dd>{{ $reservation->adults + $reservation->children }}</dd></div>
                    @endif
                    <div><dt>Voyageur</dt><dd>{{ $review->user?->name ?? 'Compte supprimé' }}</dd></div>
                </dl>
            </section>
        </aside>
    </div>
@endsection
