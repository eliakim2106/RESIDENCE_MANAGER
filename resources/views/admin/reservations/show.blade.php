@extends('layouts.admin')

@php
    use App\Enums\PaymentMethod;
    use App\Enums\ReservationStatus;

    $property = $reservation->property;
    $status = $reservation->statut;
    $money = fn (int $amount): string => number_format($amount, 0, ',', ' ').' FCFA';
    $hour = fn (?string $time): ?string => $time ? substr($time, 0, 5) : null;
    $initials = collect(preg_split('/[\s-]+/', trim($reservation->guest_name)))->filter()->take(2)->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))->implode('');

    $canManage = auth()->user()->can('manage', $reservation);
    $canCancel = $actions['cancel'] && auth()->user()->can('cancel', $reservation);
    // Le client règle son solde en ligne (CinetPay), si le paiement en ligne est configuré
    $canPayOnline = ! $canManage
        && App\Services\Payments\PaymentGateways::available()
        && in_array($status, [ReservationStatus::Pending, ReservationStatus::Confirmed], true)
        && $reservation->balanceDue() > 0
        && auth()->user()->can('pay', $reservation);
    $deadline = $reservation->freeCancellationDeadline();
    $guests = $reservation->adults + $reservation->children;
    $paidRatio = $reservation->total_amount > 0 ? min(100, round($reservation->amount_paid / $reservation->total_amount * 100)) : 0;

    // Une demande en attente se « refuse » ; une réservation validée s'« annule »
    $refusing = $canManage && $status === ReservationStatus::Pending;
    $refused = $status === ReservationStatus::Cancelled && ! $reservation->confirmed_at;

    // Frise : demande → validée → séjour → terminé (ou arrêt sur une annulation)
    $inStay = $status === ReservationStatus::Confirmed && ! $reservation->check_in->isFuture();
    $steps = [
        ['Demande reçue', 'fa-inbox', $reservation->created_at, 'done'],
        [$status === ReservationStatus::Pending ? 'À valider' : 'Validée', $status === ReservationStatus::Pending ? 'fa-hourglass-half' : 'fa-check', $reservation->confirmed_at, $reservation->confirmed_at ? 'done' : ($status === ReservationStatus::Pending ? 'current' : 'skipped')],
        ['Séjour', 'fa-bed', $reservation->check_in, match (true) {
            $status === ReservationStatus::Completed => 'done',
            $inStay => 'current',
            default => 'todo',
        }],
        ['Terminé', 'fa-flag-checkered', $reservation->check_out, $status === ReservationStatus::Completed ? 'done' : 'todo'],
    ];
    $stopped = match ($status) {
        ReservationStatus::Cancelled => [$refused ? 'Refusée' : 'Annulée', $reservation->cancelled_at],
        ReservationStatus::NoShow => ['Client non présenté', null],
        default => null,
    };

    $methodIcon = fn (?PaymentMethod $method): string => match ($method) {
        PaymentMethod::Cash => 'fa-money-bill-wave',
        PaymentMethod::Card => 'fa-credit-card',
        PaymentMethod::Wallet => 'fa-wallet',
        default => 'fa-mobile-screen',
    };
@endphp

@section('title', 'Réservation '.$reservation->reference)

@section('content')
    {{-- ========== En-tête et actions ========== --}}
    <header class="resa-header">
        <a href="{{ route('admin.reservations.index') }}" class="resa-back">
            <i class="fa-solid fa-arrow-left"></i>
            {{ $canManage ? 'Réservations' : 'Mes réservations' }}
        </a>

        <div class="resa-header-main">
            <div class="resa-header-title">
                <div class="resa-header-line">
                    <h1>{{ $reservation->reference }}</h1>
                    <span class="status-pill status-{{ $status->tone() }} status-pill-lg">{{ $refused ? 'Refusée' : $status->label() }}</span>
                </div>
                <p>
                    <i class="fa-solid fa-building"></i> {{ $property?->name ?? 'Établissement supprimé' }}
                    <span class="dot-sep">·</span>
                    Réservée le {{ $reservation->created_at->translatedFormat('d F Y à H:i') }}
                </p>
            </div>

            <div class="resa-actionbar">
                <a href="{{ route('admin.reservations.voucher', $reservation) }}" class="btn-secondary" target="_blank" rel="noopener">
                    <i class="fa-solid fa-print"></i>
                    Bon de réservation
                </a>

                @if ($canManage && $actions['payment'])
                    <button type="button" class="btn-secondary" data-modal-open="paymentModal">
                        <i class="fa-solid fa-wallet"></i>
                        Encaisser
                    </button>
                @endif

                @if ($canManage && $actions['complete'])
                    <form method="POST" action="{{ route('admin.reservations.complete', $reservation) }}" data-confirm="Marquer ce séjour comme terminé ?">
                        @csrf
                        @method('PATCH')
                        <button type="submit" class="btn-secondary"><i class="fa-solid fa-flag-checkered"></i> Terminer le séjour</button>
                    </form>
                @endif

                @if ($canManage && $actions['noShow'])
                    <form method="POST" action="{{ route('admin.reservations.no-show', $reservation) }}" data-confirm="Déclarer que le client ne s’est pas présenté ?">
                        @csrf
                        @method('PATCH')
                        <button type="submit" class="btn-secondary"><i class="fa-solid fa-user-slash"></i> Non présenté</button>
                    </form>
                @endif

                @if ($canManage && $actions['refund'])
                    <form method="POST" action="{{ route('admin.reservations.refund', $reservation) }}"
                        data-confirm="Rembourser {{ $money($reservation->amount_paid) }} au client ? Le versement se fait par le moyen de paiement d’origine.">
                        @csrf
                        @method('PATCH')
                        <button type="submit" class="btn-secondary"><i class="fa-solid fa-rotate-left"></i> Rembourser</button>
                    </form>
                @endif

                @if ($canPayOnline)
                    <form method="POST" action="{{ route('admin.reservations.pay-online', $reservation) }}">
                        @csrf
                        <button type="submit" class="btn-primary"><i class="fa-solid fa-lock"></i> Payer {{ $money($reservation->balanceDue()) }} en ligne</button>
                    </form>
                @endif

                @if ($canCancel)
                    <button type="button" class="btn-outline-danger" data-modal-open="cancelModal">
                        <i class="fa-solid {{ $refusing ? 'fa-xmark' : 'fa-ban' }}"></i>
                        {{ $refusing ? 'Refuser' : 'Annuler' }}
                    </button>
                @endif

                @if ($canManage && $actions['confirm'])
                    <form method="POST" action="{{ route('admin.reservations.confirm', $reservation) }}">
                        @csrf
                        @method('PATCH')
                        <button type="submit" class="btn-primary"><i class="fa-solid fa-check"></i> Valider la réservation</button>
                    </form>
                @endif
            </div>
        </div>

        {{-- Frise de progression --}}
        <ol class="resa-stepper" aria-label="Avancement de la réservation">
            @foreach ($steps as [$label, $icon, $date, $state])
                @continue($stopped && $state !== 'done')
                <li class="step is-{{ $state }}">
                    <span class="step-dot"><i class="fa-solid {{ $icon }}"></i></span>
                    <span class="step-text">
                        <strong>{{ $label }}</strong>
                        @if ($date && $state !== 'skipped')
                            <small>{{ $date->translatedFormat('d M') }}</small>
                        @endif
                    </span>
                </li>
            @endforeach
            @if ($stopped)
                <li class="step is-stopped">
                    <span class="step-dot"><i class="fa-solid fa-xmark"></i></span>
                    <span class="step-text">
                        <strong>{{ $stopped[0] }}</strong>
                        @if ($stopped[1])
                            <small>{{ $stopped[1]->translatedFormat('d M') }}</small>
                        @endif
                    </span>
                </li>
            @endif
        </ol>
    </header>

    @include('partials.flash')

    @if ($reservation->cancellation_reason && $status === ReservationStatus::Cancelled)
        <div class="moderation-banner tone-critical" role="status">
            <i class="fa-solid fa-quote-left"></i>
            <div>
                <strong>Motif {{ $refused ? 'du refus' : 'de l’annulation' }}</strong>
                <p>{{ $reservation->cancellation_reason }}</p>
            </div>
        </div>
    @endif

    <div class="resa-layout">

        {{-- ========== Colonne principale ========== --}}
        <div class="resa-main">

            {{-- Séjour --}}
            <section class="dash-card stay-card">
                <div class="stay-card-dates">
                    <div class="stay-big-date">
                        <span class="stay-big-label">Arrivée</span>
                        <strong>{{ $reservation->check_in->format('d') }}</strong>
                        <span>{{ Str::ucfirst($reservation->check_in->translatedFormat('F Y')) }}</span>
                        <small>{{ Str::ucfirst($reservation->check_in->translatedFormat('l')) }}@if ($hour($property?->check_in_from)) · dès {{ $hour($property->check_in_from) }}@endif</small>
                    </div>

                    <div class="stay-card-line" aria-hidden="true">
                        <span class="stay-card-nights">{{ $reservation->nights }} nuit{{ $reservation->nights > 1 ? 's' : '' }}</span>
                    </div>

                    <div class="stay-big-date">
                        <span class="stay-big-label">Départ</span>
                        <strong>{{ $reservation->check_out->format('d') }}</strong>
                        <span>{{ Str::ucfirst($reservation->check_out->translatedFormat('F Y')) }}</span>
                        <small>{{ Str::ucfirst($reservation->check_out->translatedFormat('l')) }}@if ($hour($property?->check_out_until)) · avant {{ $hour($property->check_out_until) }}@endif</small>
                    </div>
                </div>

                <div class="stay-card-facts">
                    <span><i class="fa-solid fa-user-group"></i> {{ $reservation->adults }} adulte{{ $reservation->adults > 1 ? 's' : '' }}@if ($reservation->children > 0), {{ $reservation->children }} enfant{{ $reservation->children > 1 ? 's' : '' }}@endif</span>
                    @if ($reservation->estimated_arrival_time)
                        <span><i class="fa-regular fa-clock"></i> Arrivée prévue vers {{ $reservation->estimated_arrival_time }}</span>
                    @endif
                    <span><i class="fa-solid fa-shield-halved"></i> Annulation {{ Str::lower($reservation->cancellation_policy->label()) }}</span>
                </div>

                @if ($reservation->items->isNotEmpty())
                    <ul class="resa-units">
                        @foreach ($reservation->items as $item)
                            <li>
                                <span class="cell-icon"><i class="fa-solid fa-bed"></i></span>
                                <span class="resa-unit-text">
                                    <strong>{{ $item->quantity > 1 ? $item->quantity.' × ' : '' }}{{ $item->unit?->name ?? 'Unité supprimée' }}</strong>
                                    <small>{{ $item->unit?->unitType?->name }}{{ $item->unit?->unitType ? ' · ' : '' }}{{ $money($item->price_per_night) }} / nuit en moyenne</small>
                                </span>
                                <span class="resa-unit-amount">{{ $money($item->subtotal) }}</span>
                            </li>
                        @endforeach
                    </ul>
                @endif

                @if ($reservation->special_requests)
                    <div class="resa-request">
                        <i class="fa-regular fa-comment-dots"></i>
                        <div>
                            <strong>Demande du client</strong>
                            <p>{{ $reservation->special_requests }}</p>
                        </div>
                    </div>
                @endif
            </section>

            {{-- Voyageur et établissement --}}
            <div class="resa-contacts">
                <section class="dash-card contact-card">
                    <div class="contact-card-head">
                        <span class="guest-avatar guest-avatar-lg" aria-hidden="true">{{ $initials }}</span>
                        <div>
                            <span class="contact-card-label">Voyageur principal</span>
                            <h2>{{ $reservation->guest_name }}</h2>
                            @if ($reservation->user)
                                <small>Client inscrit depuis {{ $reservation->user->created_at->translatedFormat('F Y') }}</small>
                            @endif
                        </div>
                    </div>

                    <div class="contact-links">
                        <a href="mailto:{{ $reservation->guest_email }}"><i class="fa-regular fa-envelope"></i> {{ $reservation->guest_email }}</a>
                        @if ($reservation->guest_phone)
                            <a href="tel:{{ preg_replace('/\s+/', '', $reservation->guest_phone) }}"><i class="fa-solid fa-phone"></i> {{ $reservation->guest_phone }}</a>
                        @endif
                    </div>

                    @if ($reservation->guests->isNotEmpty())
                        <h3 class="resa-subtitle">Autres voyageurs</h3>
                        <ul class="resa-guests">
                            @foreach ($reservation->guests as $guest)
                                <li>
                                    <i class="fa-solid {{ $guest->is_child ? 'fa-child' : 'fa-user' }}"></i>
                                    {{ $guest->full_name }}
                                    @if ($guest->is_child)
                                        <small>(enfant)</small>
                                    @endif
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </section>

                <section class="dash-card contact-card">
                    <div class="contact-card-head">
                        <span class="guest-avatar guest-avatar-lg is-property" aria-hidden="true"><i class="fa-solid fa-building"></i></span>
                        <div>
                            <span class="contact-card-label">Établissement</span>
                            <h2>{{ $property?->name ?? 'Établissement supprimé' }}</h2>
                            @if ($property)
                                <small>{{ collect([$property->neighborhood, $property->district, $property->city?->name])->filter()->implode(', ') }}</small>
                            @endif
                        </div>
                    </div>

                    @if ($property)
                        <div class="contact-links">
                            @if ($property->address)
                                <span><i class="fa-solid fa-location-dot"></i> {{ $property->address }}</span>
                            @endif
                            @if ($property->phone)
                                <a href="tel:{{ preg_replace('/\s+/', '', $property->phone) }}"><i class="fa-solid fa-phone"></i> {{ $property->phone }}</a>
                            @endif
                            @if (auth()->user()->isAdmin() && $property->owner)
                                <span><i class="fa-solid fa-user-tie"></i> Propriétaire : {{ $property->owner->name }}</span>
                            @endif
                        </div>
                    @endif
                </section>
            </div>

            {{-- Paiements --}}
            <section class="dash-card">
                <div class="dash-card-header">
                    <div>
                        <h2>Paiements</h2>
                        <p>{{ $reservation->payments->count() }} opération{{ $reservation->payments->count() > 1 ? 's' : '' }}</p>
                    </div>
                    @if ($canManage && $actions['payment'])
                        <button type="button" class="btn-secondary btn-sm" data-modal-open="paymentModal">
                            <i class="fa-solid fa-plus"></i>
                            Enregistrer un paiement
                        </button>
                    @endif
                </div>

                @if ($reservation->payments->isEmpty())
                    <p class="resa-note">Aucun paiement pour le moment.</p>
                @else
                    <ul class="resa-payments">
                        @foreach ($reservation->payments as $payment)
                            <li>
                                <span class="cell-icon"><i class="fa-solid {{ $methodIcon($payment->method) }}"></i></span>
                                <span class="resa-unit-text">
                                    @if ($canManage)
                                        <a href="{{ route('admin.paiements.show', $payment) }}" class="cell-title-link"><strong>{{ $payment->method?->label() ?? 'Paiement' }}{{ $payment->operator ? ' · '.$payment->operator : '' }}</strong></a>
                                    @else
                                        <strong>{{ $payment->method?->label() ?? 'Paiement' }}{{ $payment->operator ? ' · '.$payment->operator : '' }}</strong>
                                    @endif
                                    <small>
                                        {{ ($payment->paid_at ?? $payment->created_at)->translatedFormat('d M Y à H:i') }} · {{ $payment->operator_reference ?: $payment->transaction_id }}
                                        @if ($payment->refunded_at)
                                            · remboursé le {{ $payment->refunded_at->translatedFormat('d M Y') }}
                                        @endif
                                    </small>
                                </span>
                                <span class="resa-payment-end">
                                    <span class="resa-unit-amount">{{ $money($payment->amount) }}</span>
                                    <span class="status-pill status-{{ $payment->statut->tone() }}">{{ $payment->statut->label() }}</span>
                                    @if (in_array($payment->statut, [App\Enums\TransactionStatus::Accepted, App\Enums\TransactionStatus::Refunded], true))
                                        <a href="{{ route('admin.paiements.receipt', $payment) }}" class="receipt-link" target="_blank" rel="noopener"><i class="fa-solid fa-receipt"></i> Reçu</a>
                                    @endif
                                </span>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </section>
        </div>

        {{-- ========== Colonne latérale ========== --}}
        <aside class="resa-aside">

            {{-- Montant --}}
            <section class="dash-card">
                <div class="dash-card-header">
                    <h2>Montant</h2>
                    <span class="status-pill status-{{ $reservation->payment_state->tone() }}">{{ $reservation->payment_state->label() }}</span>
                </div>

                <div class="amount-summary">
                    <strong>{{ $money($reservation->total_amount) }}</strong>
                    <span class="pay-progress pay-progress-lg" role="img" aria-label="{{ $paidRatio }} % réglé">
                        <span class="pay-progress-bar tone-{{ $reservation->payment_state->tone() }}" style="width: {{ $paidRatio }}%"></span>
                    </span>
                    <small>{{ $paidRatio }} % réglé</small>
                </div>

                <dl class="resa-amounts">
                    <div>
                        <dt>Hébergement</dt>
                        <dd>{{ $money($reservation->subtotal) }}</dd>
                    </div>
                    @if ($reservation->cleaning_fee > 0)
                        <div>
                            <dt>Frais de ménage</dt>
                            <dd>{{ $money($reservation->cleaning_fee) }}</dd>
                        </div>
                    @endif
                    @if ($reservation->service_fee > 0)
                        <div>
                            <dt>Frais de service</dt>
                            <dd>{{ $money($reservation->service_fee) }}</dd>
                        </div>
                    @endif
                    @if ($reservation->tax_amount > 0)
                        <div>
                            <dt>Taxes</dt>
                            <dd>{{ $money($reservation->tax_amount) }}</dd>
                        </div>
                    @endif
                    @if ($reservation->discount_amount > 0)
                        <div>
                            <dt>Réduction</dt>
                            <dd>− {{ $money($reservation->discount_amount) }}</dd>
                        </div>
                    @endif
                    <div class="resa-amount-total">
                        <dt>Total</dt>
                        <dd>{{ $money($reservation->total_amount) }}</dd>
                    </div>
                    <div>
                        <dt>Déjà réglé</dt>
                        <dd>{{ $money($reservation->amount_paid) }}</dd>
                    </div>
                    @unless (in_array($status, [ReservationStatus::Cancelled, ReservationStatus::NoShow], true))
                        <div class="resa-amount-due {{ $reservation->balanceDue() > 0 ? 'is-due' : 'is-clear' }}">
                            <dt>Reste à payer</dt>
                            <dd>{{ $money($reservation->balanceDue()) }}</dd>
                        </div>
                    @endunless
                </dl>

                @if ($canPayOnline)
                    <form method="POST" action="{{ route('admin.reservations.pay-online', $reservation) }}" class="online-pay">
                        @csrf
                        <button type="submit" class="btn-primary w-100"><i class="fa-solid fa-lock"></i> Payer en ligne</button>
                        <small><i class="fa-solid fa-mobile-screen"></i> Mobile Money ou carte bancaire · paiement sécurisé {{ App\Services\Payments\PaymentGateways::current()?->label() }}</small>
                    </form>
                @endif

                @if ($canCancel && ! $canManage)
                    <p class="resa-hint">
                        @if ($deadline && $deadline->isFuture())
                            <i class="fa-solid fa-circle-info"></i>
                            Annulation gratuite jusqu’au {{ $deadline->translatedFormat('d F Y à H:i') }}.
                        @else
                            <i class="fa-solid fa-triangle-exclamation"></i>
                            Le délai d’annulation gratuite est dépassé : les conditions de l’établissement s’appliquent.
                        @endif
                    </p>
                @endif
            </section>

            {{-- Historique --}}
            <section class="dash-card">
                <div class="dash-card-header">
                    <h2>Historique</h2>
                </div>

                <ol class="resa-timeline">
                    <li>
                        <strong>Réservation créée</strong>
                        <small>{{ $reservation->created_at->translatedFormat('d M Y à H:i') }}</small>
                    </li>
                    @if ($status === ReservationStatus::Pending && $reservation->expires_at)
                        <li class="is-warning">
                            <strong>En attente de validation</strong>
                            <small>Unités retenues jusqu’au {{ $reservation->expires_at->translatedFormat('d M Y à H:i') }}</small>
                        </li>
                    @endif
                    @if ($reservation->confirmed_at)
                        <li class="is-good">
                            <strong>Validée</strong>
                            <small>{{ $reservation->confirmed_at->translatedFormat('d M Y à H:i') }}</small>
                        </li>
                    @endif
                    @if ($reservation->cancelled_at)
                        <li class="is-critical">
                            <strong>{{ $refused ? 'Refusée' : 'Annulée' }}</strong>
                            <small>{{ $reservation->cancelled_at->translatedFormat('d M Y à H:i') }}</small>
                        </li>
                    @endif
                    @if ($reservation->payments->whereNotNull('refunded_at')->isNotEmpty())
                        <li class="is-info">
                            <strong>Remboursée</strong>
                            <small>{{ $reservation->payments->whereNotNull('refunded_at')->max('refunded_at')->translatedFormat('d M Y à H:i') }}</small>
                        </li>
                    @endif
                    @if ($status === ReservationStatus::Completed)
                        <li class="is-info">
                            <strong>Séjour terminé</strong>
                            <small>Départ le {{ $reservation->check_out->translatedFormat('d M Y') }}</small>
                        </li>
                    @endif
                    @if ($status === ReservationStatus::NoShow)
                        <li>
                            <strong>Client non présenté</strong>
                        </li>
                    @endif
                </ol>
            </section>

            {{-- Notes internes : visibles seulement par l'établissement et les administrateurs --}}
            @if ($canManage)
                <section class="dash-card">
                    <div class="dash-card-header">
                        <div>
                            <h2>Notes internes</h2>
                            <p>Jamais visibles par le client.</p>
                        </div>
                    </div>

                    <form method="POST" action="{{ route('admin.reservations.notes', $reservation) }}" class="resa-notes">
                        @csrf
                        @method('PATCH')
                        <div class="form-group">
                            <label for="notes" class="visually-hidden">Notes internes</label>
                            <textarea name="notes" id="notes" maxlength="2000" placeholder="Préférences du client, remarques de l’équipe…">{{ old('notes', $reservation->owner_notes) }}</textarea>
                        </div>
                        <button type="submit" class="btn-secondary">
                            <i class="fa-solid fa-floppy-disk"></i>
                            Enregistrer les notes
                        </button>
                    </form>
                </section>
            @endif
        </aside>
    </div>

    {{-- ========== Modales ========== --}}
    @if ($canCancel)
        <div class="modal-overlay" id="cancelModal" data-action-modal @if ($errors->has('motif')) data-open-on-load @endif>
            <div class="modal-card modal-form" role="dialog" aria-modal="true" aria-labelledby="cancelModalTitle">
                <div class="modal-icon"><i class="fa-solid {{ $refusing ? 'fa-xmark' : 'fa-ban' }}"></i></div>
                <h3 id="cancelModalTitle">{{ $refusing ? 'Refuser' : 'Annuler' }} la réservation {{ $reservation->reference }} ?</h3>
                <p>
                    Le client est prévenu par email{{ $canManage ? ', avec le motif' : '' }}.
                    Les unités redeviennent disponibles. Cette action est définitive.
                </p>

                <form method="POST" action="{{ route('admin.reservations.cancel', $reservation) }}">
                    @csrf
                    @method('PATCH')
                    <div class="form-group">
                        <label for="motif">Motif <span class="field-optional">(facultatif)</span></label>
                        <textarea name="motif" id="motif" maxlength="500" rows="3" placeholder="{{ $refusing ? 'Ex. : établissement complet à ces dates, travaux en cours…' : ($canManage ? 'Ex. : demande du client, problème technique…' : 'Ex. : changement de programme…') }}">{{ old('motif') }}</textarea>
                    </div>
                    @if ($canManage && $reservation->amount_paid > 0)
                        <label class="check-option">
                            <input type="checkbox" name="rembourser" value="1" @checked(old('rembourser'))>
                            <span>
                                <strong>Rembourser {{ $money($reservation->amount_paid) }} au client</strong>
                                <small>Le remboursement est enregistré ; le versement se fait par le moyen de paiement d’origine.</small>
                            </span>
                        </label>
                    @endif
                    <div class="modal-actions">
                        <button type="button" class="btn-cancel" data-modal-close>Retour</button>
                        <button type="submit" class="btn-delete">{{ $refusing ? 'Refuser' : 'Annuler' }} la réservation</button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    @if ($canManage && $actions['payment'])
        <div class="modal-overlay" id="paymentModal" data-action-modal @if ($errors->hasAny(['montant', 'moyen', 'reference'])) data-open-on-load @endif>
            <div class="modal-card modal-form" role="dialog" aria-modal="true" aria-labelledby="paymentModalTitle">
                <div class="modal-icon modal-icon-info"><i class="fa-solid fa-wallet"></i></div>
                <h3 id="paymentModalTitle">Enregistrer un paiement</h3>
                <p>Somme reçue directement par l’établissement. Reste à payer : <strong>{{ $money($reservation->balanceDue()) }}</strong>.</p>

                <form method="POST" action="{{ route('admin.reservations.payments.store', $reservation) }}">
                    @csrf
                    <div class="form-group">
                        <label for="montant">Montant reçu <span class="required">*</span></label>
                        <div class="input-affix">
                            <input type="text" name="montant" id="montant" inputmode="numeric" required
                                value="{{ old('montant', $reservation->balanceDue()) }}">
                            <span>FCFA</span>
                        </div>
                        @error('montant')
                            <p class="field-error">{{ $message }}</p>
                        @enderror
                    </div>
                    <div class="form-group">
                        <label for="moyen">Moyen de paiement <span class="required">*</span></label>
                        <select name="moyen" id="moyen" required>
                            @foreach ($paymentMethods as $value => $label)
                                <option value="{{ $value }}" @selected(old('moyen', 'cash') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('moyen')
                            <p class="field-error">{{ $message }}</p>
                        @enderror
                    </div>
                    <div class="form-group">
                        <label for="reference">Référence <span class="field-optional">(facultatif)</span></label>
                        <input type="text" name="reference" id="reference" maxlength="100" value="{{ old('reference') }}" placeholder="N° de reçu, ID de transaction…">
                    </div>
                    <div class="modal-actions">
                        <button type="button" class="btn-cancel" data-modal-close>Retour</button>
                        <button type="submit" class="btn-save">Enregistrer</button>
                    </div>
                </form>
            </div>
        </div>
    @endif
@endsection
