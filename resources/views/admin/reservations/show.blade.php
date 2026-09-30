@extends('layouts.admin')

@php
    $property = $reservation->property;
    $money = fn (int $amount): string => number_format($amount, 0, ',', ' ').' FCFA';
    $hour = fn (?string $time): ?string => $time ? substr($time, 0, 5) : null;

    $canManage = auth()->user()->can('manage', $reservation);
    $canCancel = $actions['cancel'] && auth()->user()->can('cancel', $reservation);
    $managerActions = $canManage && ($actions['confirm'] || $actions['complete'] || $actions['noShow'] || $actions['payment']);

    // Une demande en attente se « refuse » ; une réservation validée s'« annule »
    $refusing = $canManage && $reservation->statut === App\Enums\ReservationStatus::Pending;
    $deadline = $reservation->freeCancellationDeadline();
    $guests = $reservation->adults + $reservation->children;
@endphp

@section('title', 'Réservation '.$reservation->reference)

@section('content')
    <div class="admin-page-header">
        <div class="admin-page-heading">
            <span class="admin-page-icon"><i class="fa-solid fa-receipt"></i></span>
            <div>
                <h1>Réservation {{ $reservation->reference }}</h1>
                <p>{{ $property?->name ?? 'Établissement supprimé' }} · réservée le {{ $reservation->created_at->translatedFormat('d F Y à H:i') }}</p>
            </div>
        </div>

        <div class="admin-page-actions">
            <span class="status-pill status-{{ $reservation->statut->tone() }} status-pill-lg">{{ $reservation->statut->label() }}</span>
            <a href="{{ route('admin.reservations.index') }}" class="btn-secondary">
                <i class="fa-solid fa-arrow-left"></i>
                Retour
            </a>
        </div>
    </div>

    @include('partials.flash')

    <div class="resa-layout">

        {{-- ========== Colonne principale ========== --}}
        <div class="resa-main">

            {{-- Séjour --}}
            <section class="dash-card">
                <div class="dash-card-header">
                    <div>
                        <h2>Séjour</h2>
                        <p>{{ $reservation->nights }} nuit{{ $reservation->nights > 1 ? 's' : '' }} · {{ $guests }} voyageur{{ $guests > 1 ? 's' : '' }}</p>
                    </div>
                </div>

                <div class="resa-dates">
                    <div class="resa-date">
                        <span class="resa-date-label"><i class="fa-solid fa-plane-arrival"></i> Arrivée</span>
                        <strong>{{ Str::ucfirst($reservation->check_in->translatedFormat('l d F Y')) }}</strong>
                        @if ($hour($property?->check_in_from))
                            <small>À partir de {{ $hour($property->check_in_from) }}{{ $hour($property->check_in_until) ? ' jusqu’à '.$hour($property->check_in_until) : '' }}</small>
                        @endif
                    </div>
                    <div class="resa-date-sep" aria-hidden="true"><i class="fa-solid fa-arrow-right"></i></div>
                    <div class="resa-date">
                        <span class="resa-date-label"><i class="fa-solid fa-plane-departure"></i> Départ</span>
                        <strong>{{ Str::ucfirst($reservation->check_out->translatedFormat('l d F Y')) }}</strong>
                        @if ($hour($property?->check_out_until))
                            <small>Avant {{ $hour($property->check_out_until) }}</small>
                        @endif
                    </div>
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
            </section>

            {{-- Voyageur et établissement --}}
            <div class="resa-contacts">
                <section class="dash-card">
                    <div class="dash-card-header">
                        <h2>Voyageur principal</h2>
                    </div>

                    <dl class="resa-info">
                        <div>
                            <dt>Nom</dt>
                            <dd>{{ $reservation->guest_name }}</dd>
                        </div>
                        <div>
                            <dt>Email</dt>
                            <dd><a href="mailto:{{ $reservation->guest_email }}">{{ $reservation->guest_email }}</a></dd>
                        </div>
                        <div>
                            <dt>Téléphone</dt>
                            <dd><a href="tel:{{ preg_replace('/\s+/', '', $reservation->guest_phone) }}">{{ $reservation->guest_phone }}</a></dd>
                        </div>
                        <div>
                            <dt>Voyageurs</dt>
                            <dd>{{ $reservation->adults }} adulte{{ $reservation->adults > 1 ? 's' : '' }}@if ($reservation->children > 0), {{ $reservation->children }} enfant{{ $reservation->children > 1 ? 's' : '' }}@endif</dd>
                        </div>
                        @if ($reservation->estimated_arrival_time)
                            <div>
                                <dt>Arrivée prévue</dt>
                                <dd>{{ $reservation->estimated_arrival_time }}</dd>
                            </div>
                        @endif
                    </dl>

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

                    @if ($reservation->special_requests)
                        <h3 class="resa-subtitle">Demandes particulières</h3>
                        <p class="resa-note">{{ $reservation->special_requests }}</p>
                    @endif
                </section>

                <section class="dash-card">
                    <div class="dash-card-header">
                        <h2>Établissement</h2>
                    </div>

                    @if ($property)
                        <dl class="resa-info">
                            <div>
                                <dt>Nom</dt>
                                <dd>{{ $property->name }}</dd>
                            </div>
                            <div>
                                <dt>Adresse</dt>
                                <dd>{{ collect([$property->address, $property->neighborhood, $property->district, $property->city?->name])->filter()->implode(', ') }}</dd>
                            </div>
                            @if ($property->phone)
                                <div>
                                    <dt>Téléphone</dt>
                                    <dd><a href="tel:{{ preg_replace('/\s+/', '', $property->phone) }}">{{ $property->phone }}</a></dd>
                                </div>
                            @endif
                            @if (auth()->user()->isAdmin() && $property->owner)
                                <div>
                                    <dt>Propriétaire</dt>
                                    <dd>{{ $property->owner->name }}</dd>
                                </div>
                            @endif
                            <div>
                                <dt>Annulation</dt>
                                <dd>{{ $reservation->cancellation_policy->label() }}</dd>
                            </div>
                        </dl>
                    @else
                        <p class="resa-note">Cet établissement n’existe plus.</p>
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
                                <span class="cell-icon"><i class="fa-solid {{ $payment->method === App\Enums\PaymentMethod::Cash ? 'fa-money-bill-wave' : ($payment->method === App\Enums\PaymentMethod::Card ? 'fa-credit-card' : 'fa-mobile-screen') }}"></i></span>
                                <span class="resa-unit-text">
                                    <strong>{{ $payment->method?->label() ?? 'Paiement' }}{{ $payment->operator ? ' · '.$payment->operator : '' }}</strong>
                                    <small>{{ ($payment->paid_at ?? $payment->created_at)->translatedFormat('d M Y à H:i') }} · {{ $payment->operator_reference ?: $payment->transaction_id }}</small>
                                </span>
                                <span class="resa-payment-end">
                                    <span class="resa-unit-amount">{{ $money($payment->amount) }}</span>
                                    <span class="status-pill status-{{ $payment->statut->tone() }}">{{ $payment->statut->label() }}</span>
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
                    <div class="resa-amount-due {{ $reservation->balanceDue() > 0 ? 'is-due' : 'is-clear' }}">
                        <dt>Reste à payer</dt>
                        <dd>{{ $money($reservation->balanceDue()) }}</dd>
                    </div>
                </dl>
            </section>

            {{-- Actions --}}
            @if ($managerActions || $canCancel)
                <section class="dash-card">
                    <div class="dash-card-header">
                        <h2>Actions</h2>
                    </div>

                    <div class="resa-actions">
                        @if ($canManage && $actions['confirm'])
                            <form method="POST" action="{{ route('admin.reservations.confirm', $reservation) }}">
                                @csrf
                                @method('PATCH')
                                <button type="submit" class="btn-primary">
                                    <i class="fa-solid fa-check"></i>
                                    Valider la réservation
                                </button>
                            </form>

                            @if ($canCancel)
                                <button type="button" class="btn-outline-danger" data-modal-open="cancelModal">
                                    <i class="fa-solid fa-xmark"></i>
                                    Refuser la réservation
                                </button>
                            @endif
                        @endif

                        @if ($canManage && $actions['payment'])
                            <button type="button" class="btn-secondary" data-modal-open="paymentModal">
                                <i class="fa-solid fa-wallet"></i>
                                Enregistrer un paiement
                            </button>
                        @endif

                        @if ($canManage && $actions['complete'])
                            <form method="POST" action="{{ route('admin.reservations.complete', $reservation) }}"
                                data-confirm="Marquer ce séjour comme terminé ?">
                                @csrf
                                @method('PATCH')
                                <button type="submit" class="btn-secondary">
                                    <i class="fa-solid fa-flag-checkered"></i>
                                    Marquer comme terminé
                                </button>
                            </form>
                        @endif

                        @if ($canManage && $actions['noShow'])
                            <form method="POST" action="{{ route('admin.reservations.no-show', $reservation) }}"
                                data-confirm="Déclarer que le client ne s’est pas présenté ?">
                                @csrf
                                @method('PATCH')
                                <button type="submit" class="btn-secondary">
                                    <i class="fa-solid fa-user-slash"></i>
                                    Client non présenté
                                </button>
                            </form>
                        @endif

                        @if ($canCancel && ! $refusing)
                            <button type="button" class="btn-outline-danger" data-modal-open="cancelModal">
                                <i class="fa-solid fa-ban"></i>
                                Annuler la réservation
                            </button>

                            @unless ($canManage)
                                <p class="resa-hint">
                                    @if ($deadline && $deadline->isFuture())
                                        <i class="fa-solid fa-circle-info"></i>
                                        Annulation gratuite jusqu’au {{ $deadline->translatedFormat('d F Y à H:i') }}.
                                    @else
                                        <i class="fa-solid fa-triangle-exclamation"></i>
                                        Le délai d’annulation gratuite est dépassé : les conditions de l’établissement s’appliquent.
                                    @endif
                                </p>
                            @endunless
                        @endif
                    </div>
                </section>
            @endif

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
                    @if ($reservation->statut === App\Enums\ReservationStatus::Pending && $reservation->expires_at)
                        <li class="is-warning">
                            <strong>En attente de paiement</strong>
                            <small>Unités libérées le {{ $reservation->expires_at->translatedFormat('d M Y à H:i') }}</small>
                        </li>
                    @endif
                    @if ($reservation->confirmed_at)
                        <li class="is-good">
                            <strong>Confirmée</strong>
                            <small>{{ $reservation->confirmed_at->translatedFormat('d M Y à H:i') }}</small>
                        </li>
                    @endif
                    @if ($reservation->cancelled_at)
                        <li class="is-critical">
                            <strong>Annulée</strong>
                            <small>{{ $reservation->cancelled_at->translatedFormat('d M Y à H:i') }}</small>
                            @if ($reservation->cancellation_reason)
                                <p>« {{ $reservation->cancellation_reason }} »</p>
                            @endif
                        </li>
                    @endif
                    @if ($reservation->statut === App\Enums\ReservationStatus::Completed)
                        <li class="is-info">
                            <strong>Séjour terminé</strong>
                            <small>Départ le {{ $reservation->check_out->translatedFormat('d M Y') }}</small>
                        </li>
                    @endif
                    @if ($reservation->statut === App\Enums\ReservationStatus::NoShow)
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
                    {{ $refusing ? 'Le client sera informé que sa demande n’est pas acceptée.' : '' }}
                    Les unités seront de nouveau disponibles à la réservation. Cette action est définitive.
                </p>

                <form method="POST" action="{{ route('admin.reservations.cancel', $reservation) }}">
                    @csrf
                    @method('PATCH')
                    <div class="form-group">
                        <label for="motif">Motif <span class="field-optional">(facultatif)</span></label>
                        <textarea name="motif" id="motif" maxlength="500" rows="3" placeholder="{{ $refusing ? 'Ex. : établissement complet à ces dates, travaux en cours…' : ($canManage ? 'Ex. : demande du client, problème technique…' : 'Ex. : changement de programme…') }}">{{ old('motif') }}</textarea>
                    </div>
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
