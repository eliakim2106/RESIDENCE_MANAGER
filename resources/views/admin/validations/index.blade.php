@extends('layouts.admin')

@section('title', 'Validation des établissements')

@php
    use App\Enums\PropertyStatus;

    // Après une erreur sur le motif : rouvrir la bonne modale, sur le bon établissement
    $previousAction = (string) old('_form_action');
    $reopen = $errors->has('motif') && str_starts_with($previousAction, url('admin/validations/')) ? old('_modal') : null;

    $descriptions = [
        'a-valider' => 'Établissements soumis par les propriétaires, du plus ancien au plus récent.',
        'publies' => 'Établissements visibles sur le site. Vous pouvez en suspendre un en cas de problème.',
        'refuses' => 'Demandes renvoyées aux propriétaires, en attente de leurs corrections.',
        'suspendus' => 'Établissements retirés du site par un administrateur.',
    ];
@endphp

@section('content')
    <div class="admin-page-header">
        <div class="admin-page-heading">
            <span class="admin-page-icon"><i class="fa-solid fa-building-circle-check"></i></span>
            <div>
                <h1>Validation des établissements</h1>
                <p>{{ $descriptions[$tab] }}</p>
            </div>
        </div>
    </div>

    @include('partials.flash')

    {{-- ========== Synthèse ========== --}}
    <div class="resa-today">
        <a href="{{ route('admin.validations.index') }}" class="resa-today-card tone-warning {{ $summary['pending'] > 0 ? 'has-alert' : '' }}">
            <span class="resa-today-icon"><i class="fa-solid fa-hourglass-half"></i></span>
            <span class="resa-today-text">
                <strong>{{ $summary['pending'] }}</strong>
                <span>{{ $summary['pending'] > 0 ? 'À valider · la plus ancienne depuis '.($summary['oldestDays'] > 0 ? $summary['oldestDays'].' jour'.($summary['oldestDays'] > 1 ? 's' : '') : 'aujourd’hui') : 'Aucune demande en attente' }}</span>
            </span>
        </a>
        <div class="resa-today-card tone-info">
            <span class="resa-today-icon"><i class="fa-solid fa-gavel"></i></span>
            <span class="resa-today-text">
                <strong>{{ $summary['decisionsThisMonth'] }}</strong>
                <span>Décision{{ $summary['decisionsThisMonth'] > 1 ? 's' : '' }} en {{ now()->translatedFormat('F') }}</span>
            </span>
        </div>
        <a href="{{ route('admin.validations.index', ['statut' => 'publies']) }}" class="resa-today-card tone-good">
            <span class="resa-today-icon"><i class="fa-solid fa-globe"></i></span>
            <span class="resa-today-text">
                <strong>{{ $summary['published'] }}</strong>
                <span>Publié{{ $summary['published'] > 1 ? 's' : '' }} sur le site</span>
            </span>
        </a>
        <a href="{{ route('admin.validations.index', ['statut' => 'refuses']) }}" class="resa-today-card tone-gold">
            <span class="resa-today-icon"><i class="fa-solid fa-rotate"></i></span>
            <span class="resa-today-text">
                <strong>{{ $counts['refuses'] }}</strong>
                <span>Refusé{{ $counts['refuses'] > 1 ? 's' : '' }}, en cours de correction</span>
            </span>
        </a>
    </div>

    @include('admin.partials.list-toolbar', [
        'tabs' => $tabs,
        'counts' => $counts,
        'statut' => $tab,
        'search' => $search,
        'placeholder' => 'Établissement, ville, propriétaire…',
    ])

    @if ($properties->isEmpty())
        <div class="table-card">
            @if ($search === '' && $tab === 'a-valider')
                <div class="empty-state">
                    <i class="fa-solid fa-circle-check"></i>
                    <strong>Tout est à jour</strong>
                    <span>Aucun établissement n’attend de validation. Vous serez prévenu dès qu’un propriétaire en soumettra un.</span>
                </div>

                @if ($recentDecisions->isNotEmpty())
                    <div class="recent-decisions">
                        <h2>Dernières décisions</h2>
                        <ul>
                            @foreach ($recentDecisions as $decision)
                                <li>
                                    <span class="status-pill status-{{ $decision->statut->tone() }}">{{ $decision->wasRejected() ? 'Refusé' : $decision->statut->label() }}</span>
                                    <a href="{{ route('admin.etablissements.show', $decision) }}" class="cell-title-link"><strong>{{ $decision->name }}</strong></a>
                                    <small>{{ $decision->owner?->name }} · {{ $decision->moderated_at->diffForHumans() }}{{ $decision->moderator ? ' par '.$decision->moderator->name : '' }}</small>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            @else
                @include('admin.partials.empty-state', ['icon' => 'fa-building', 'search' => $search])
            @endif
        </div>
    @else
        <div class="moderation-list">
            @foreach ($properties as $property)
                @php
                    // Points à vérifier avant publication
                    $checks = [
                        ['Photos', $property->images_count > 0, $property->images_count.' photo'.($property->images_count > 1 ? 's' : '')],
                        ['Unités', $property->units_count > 0, $property->units_count.' unité'.($property->units_count > 1 ? 's' : '')],
                        ['Description', filled($property->description), filled($property->description) ? 'Renseignée' : 'Manquante'],
                        ['Carte', filled($property->latitude) && filled($property->longitude), filled($property->latitude) ? 'Position GPS' : 'Sans position'],
                    ];
                @endphp

                <article class="moderation-card">
                    <div class="moderation-media">
                        @if ($property->coverImage)
                            <img src="{{ $property->coverImage->url }}" alt="" loading="lazy">
                        @else
                            <span class="moderation-media-empty"><i class="fa-solid {{ $property->propertyType?->fa_icon ?? 'fa-building' }}"></i></span>
                        @endif
                    </div>

                    <div class="moderation-body">
                        <header class="moderation-header">
                            <div>
                                <h2>{{ $property->name }}</h2>
                                <p>
                                    {{ $property->propertyType?->name }}
                                    · <i class="fa-solid fa-location-dot"></i> {{ collect([$property->district, $property->city?->name])->filter()->implode(', ') }}
                                </p>
                            </div>
                            <span class="status-pill status-{{ $property->statut->tone() }}">{{ $property->wasRejected() ? 'Refusé' : $property->statut->label() }}</span>
                        </header>

                        <dl class="moderation-meta">
                            <div>
                                <dt>Propriétaire</dt>
                                <dd>
                                    {{ $property->owner?->name ?? '—' }}
                                    @if ($property->owner)
                                        <a href="mailto:{{ $property->owner->email }}">{{ $property->owner->email }}</a>
                                    @endif
                                </dd>
                            </div>
                            <div>
                                @if ($property->statut === PropertyStatus::Pending)
                                    <dt>Soumis</dt>
                                    <dd>{{ $property->submitted_at ? $property->submitted_at->diffForHumans() : '—' }}</dd>
                                @else
                                    <dt>{{ match (true) { $property->statut === PropertyStatus::Suspended => 'Suspendu', $property->wasRejected() => 'Refusé', default => 'Publié' } }}</dt>
                                    <dd>
                                        {{ ($property->moderated_at ?? $property->published_at)?->translatedFormat('d M Y') ?? '—' }}
                                        @if ($property->moderator)
                                            <small>par {{ $property->moderator->name }}</small>
                                        @endif
                                    </dd>
                                @endif
                            </div>
                        </dl>

                        @if ($property->statut === PropertyStatus::Pending)
                            <ul class="moderation-checks" aria-label="Points à vérifier">
                                @foreach ($checks as [$label, $ok, $detail])
                                    <li class="{{ $ok ? 'is-ok' : 'is-missing' }}">
                                        <i class="fa-solid {{ $ok ? 'fa-circle-check' : 'fa-triangle-exclamation' }}"></i>
                                        <span><strong>{{ $label }}</strong> {{ $detail }}</span>
                                    </li>
                                @endforeach
                            </ul>
                        @endif

                        @if ($property->moderation_note)
                            <p class="moderation-note">
                                <i class="fa-solid fa-quote-left"></i>
                                {{ $property->statut === PropertyStatus::Pending ? 'Motif du refus précédent : ' : '' }}{{ $property->moderation_note }}
                            </p>
                        @endif

                        <footer class="moderation-actions">
                            <a href="{{ route('admin.etablissements.show', $property) }}" class="btn-secondary btn-sm">
                                <i class="fa-solid fa-eye"></i>
                                Voir la fiche
                            </a>

                            @if ($property->statut === PropertyStatus::Pending)
                                <button type="button" class="btn-outline-danger btn-sm" data-modal-open="rejectModal"
                                    data-form-action="{{ route('admin.validations.reject', $property) }}" data-name="{{ $property->name }}">
                                    <i class="fa-solid fa-xmark"></i>
                                    Refuser
                                </button>
                                <form method="POST" action="{{ route('admin.validations.approve', $property) }}">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="btn-primary btn-sm">
                                        <i class="fa-solid fa-check"></i>
                                        Approuver et publier
                                    </button>
                                </form>
                            @elseif ($property->wasRejected())
                                <span class="moderation-waiting"><i class="fa-solid fa-hourglass-half"></i> En attente des corrections du propriétaire</span>
                            @elseif ($property->statut === PropertyStatus::Published)
                                <button type="button" class="btn-outline-danger btn-sm" data-modal-open="suspendModal"
                                    data-form-action="{{ route('admin.validations.suspend', $property) }}" data-name="{{ $property->name }}">
                                    <i class="fa-solid fa-ban"></i>
                                    Suspendre
                                </button>
                            @else
                                <form method="POST" action="{{ route('admin.validations.reinstate', $property) }}"
                                    data-confirm="Publier de nouveau « {{ $property->name }} » ?">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="btn-primary btn-sm">
                                        <i class="fa-solid fa-rotate-left"></i>
                                        Rétablir
                                    </button>
                                </form>
                            @endif
                        </footer>
                    </div>
                </article>
            @endforeach
        </div>
    @endif

    @include('admin.partials.pagination', ['paginator' => $properties, 'label' => 'établissement(s)'])

    {{-- ========== Modales : refus et suspension (motif transmis au propriétaire) ========== --}}
    @foreach ([
        'reject' => ['rejectModal', 'fa-xmark', 'Refuser', 'Le propriétaire recevra ce motif et pourra corriger puis soumettre de nouveau son établissement.', 'Ex. : les photos ne correspondent pas à l’établissement, l’adresse est incomplète…', 'Refuser'],
        'suspend' => ['suspendModal', 'fa-ban', 'Suspendre', 'L’établissement sera retiré du site immédiatement. Les réservations existantes sont conservées.', 'Ex. : plaintes répétées de clients, informations trompeuses…', 'Suspendre'],
    ] as $modal => [$id, $icon, $verb, $help, $placeholder, $submit])
        @php
            $isReopened = $reopen === $modal;
        @endphp
        <div class="modal-overlay" id="{{ $id }}" data-action-modal @if ($isReopened) data-open-on-load @endif>
            <div class="modal-card modal-form" role="dialog" aria-modal="true" aria-labelledby="{{ $id }}Title">
                <div class="modal-icon"><i class="fa-solid {{ $icon }}"></i></div>
                <h3 id="{{ $id }}Title">{{ $verb }} « <span data-modal-name>{{ $isReopened ? old('_form_name') : '' }}</span> » ?</h3>
                <p>{{ $help }}</p>

                <form method="POST" action="{{ $isReopened ? $previousAction : '' }}">
                    @csrf
                    @method('PATCH')
                    <input type="hidden" name="_modal" value="{{ $modal }}">
                    <input type="hidden" name="_form_action" value="{{ $isReopened ? $previousAction : '' }}">
                    <input type="hidden" name="_form_name" value="{{ $isReopened ? old('_form_name') : '' }}">
                    <div class="form-group">
                        <label for="motif-{{ $modal }}">Motif <span class="required">*</span></label>
                        <textarea name="motif" id="motif-{{ $modal }}" rows="4" minlength="10" maxlength="1000" required
                            placeholder="{{ $placeholder }}">{{ $isReopened ? old('motif') : '' }}</textarea>
                        @if ($isReopened)
                            @error('motif')
                                <p class="field-error">{{ $message }}</p>
                            @enderror
                        @endif
                    </div>
                    <div class="modal-actions">
                        <button type="button" class="btn-cancel" data-modal-close>Retour</button>
                        <button type="submit" class="btn-delete">{{ $submit }}</button>
                    </div>
                </form>
            </div>
        </div>
    @endforeach
@endsection
