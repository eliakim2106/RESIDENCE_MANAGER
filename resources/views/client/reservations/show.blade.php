@extends('layouts.account')

@php
    use App\Enums\ReservationStatus;
    use App\Enums\TransactionStatus;

    $property = $reservation->property;
    $status = $reservation->statut;
    $money = fn (int $amount): string => number_format($amount, 0, ',', ' ').' FCFA';
    $balance = $reservation->balanceDue();
    $hour = fn (?string $time): ?string => $time ? substr($time, 0, 5) : null;

    // Étapes : demande → confirmée → séjour → terminé
    $steps = [
        ['Demande envoyée', 'fa-paper-plane', true],
        [$status === ReservationStatus::Pending ? 'En attente de confirmation' : 'Confirmée', $status === ReservationStatus::Pending ? 'fa-hourglass-half' : 'fa-circle-check', $status !== ReservationStatus::Pending],
        ['Séjour', 'fa-bed', $status === ReservationStatus::Completed || ($status === ReservationStatus::Confirmed && ! $reservation->check_in->isFuture())],
        ['Terminé', 'fa-flag-checkered', $status === ReservationStatus::Completed],
    ];
@endphp

@section('title', 'Réservation '.$reservation->reference)
@section('account_heading', $property?->name ?? 'Ma réservation')
@section('account_subtitle')
    Réservation <strong>{{ $reservation->reference }}</strong> · faite le {{ $reservation->created_at->translatedFormat('d F Y') }}
@endsection

@section('account_actions')
    <a href="{{ route('client.reservations.index') }}" class="acc-btn acc-btn-light"><i class="fa-solid fa-arrow-left"></i> Mes réservations</a>
    @if (! in_array($status, [ReservationStatus::Cancelled], true))
        <a href="{{ route('admin.reservations.voucher', $reservation) }}" class="acc-btn acc-btn-light" target="_blank" rel="noopener"><i class="fa-solid fa-print"></i> Bon de réservation</a>
    @endif
@endsection

@section('account')
    {{-- ========== État ========== --}}
    @if ($status === ReservationStatus::Cancelled)
        <div class="acc-banner tone-critical">
            <i class="fa-solid fa-ban"></i>
            <div>
                <strong>Réservation annulée{{ $reservation->cancelled_at ? ' le '.$reservation->cancelled_at->translatedFormat('d F Y') : '' }}</strong>
                @if ($reservation->cancellation_reason)
                    <p>{{ $reservation->cancellation_reason }}</p>
                @endif
            </div>
        </div>
    @else
        <ol class="acc-steps" aria-label="Avancement de la réservation">
            @foreach ($steps as [$label, $icon, $done])
                <li class="{{ $done ? 'is-done' : '' }}">
                    <span><i class="fa-solid {{ $done ? 'fa-check' : $icon }}"></i></span>
                    <small>{{ $label }}</small>
                </li>
            @endforeach
        </ol>

        @if ($status === ReservationStatus::Pending)
            <div class="acc-banner tone-warning">
                <i class="fa-solid fa-hourglass-half"></i>
                <div>
                    <strong>En attente de confirmation par l’établissement</strong>
                    <p>Vous recevrez un email dès que {{ $property?->name }} aura confirmé{{ $reservation->expires_at ? ' (au plus tard le '.$reservation->expires_at->translatedFormat('d F à H:i').')' : '' }}. Vous pouvez déjà régler votre séjour en ligne.</p>
                </div>
            </div>
        @endif
    @endif

    <div class="acc-grid acc-grid-wide">
        <div class="acc-col">
            {{-- Séjour --}}
            <section class="acc-card">
                <div class="acc-stay-dates">
                    <div>
                        <small>Arrivée</small>
                        <strong>{{ $reservation->check_in->translatedFormat('D d M Y') }}</strong>
                        <span>à partir de {{ $hour($property?->check_in_from) ?? '14:00' }}{{ $reservation->estimated_arrival_time ? ' · prévue vers '.$reservation->estimated_arrival_time : '' }}</span>
                    </div>
                    <span class="acc-stay-nights">{{ $reservation->nights }} nuit{{ $reservation->nights > 1 ? 's' : '' }}</span>
                    <div>
                        <small>Départ</small>
                        <strong>{{ $reservation->check_out->translatedFormat('D d M Y') }}</strong>
                        <span>avant {{ $hour($property?->check_out_until) ?? '12:00' }}</span>
                    </div>
                </div>

                <ul class="acc-units">
                    @foreach ($reservation->items as $item)
                        <li>
                            <img src="{{ $item->unit?->images->first()?->url ?? $property?->coverImage?->url ?? asset('assets/images/home/residence-1.webp') }}" alt="">
                            <span>
                                <strong>{{ $item->quantity > 1 ? $item->quantity.' × ' : '' }}{{ $item->unit?->name ?? 'Logement' }}</strong>
                                <small>{{ $money($item->price_per_night) }} / nuit en moyenne</small>
                            </span>
                            <b>{{ $money($item->subtotal) }}</b>
                        </li>
                    @endforeach
                </ul>

                <p class="acc-muted acc-guests">
                    <i class="fa-solid fa-user-group"></i>
                    {{ $reservation->adults }} adulte{{ $reservation->adults > 1 ? 's' : '' }}{{ $reservation->children > 0 ? ', '.$reservation->children.' enfant'.($reservation->children > 1 ? 's' : '') : '' }}
                    · au nom de {{ $reservation->guest_name }}
                </p>
                @if ($reservation->special_requests)
                    <p class="acc-note"><i class="fa-regular fa-comment-dots"></i> {{ $reservation->special_requests }}</p>
                @endif
            </section>

            {{-- Avis --}}
            @if ($reservation->review)
                <section class="acc-card" id="avis">
                    <div class="acc-card-head"><h2>Votre avis</h2></div>
                    <div class="acc-review">
                        <span class="acc-review-score">{{ $reservation->review->rating }}<small>/10</small></span>
                        <div>
                            @if ($reservation->review->title)
                                <strong>{{ $reservation->review->title }}</strong>
                            @endif
                            <p>{{ $reservation->review->comment }}</p>
                            @if ($reservation->review->isPublished())
                                <small>Publié le {{ $reservation->review->created_at->translatedFormat('d F Y') }}</small>
                            @else
                                <small class="acc-review-hidden"><i class="fa-solid fa-eye-slash"></i> Retiré par la modération : il n’apparaît plus sur le site.</small>
                            @endif
                        </div>
                    </div>
                    @if ($reservation->review->owner_reply && $reservation->review->isPublished())
                        <div class="acc-review-reply">
                            <strong><i class="fa-solid fa-reply"></i> Réponse de {{ $property?->name ?? 'l’établissement' }}</strong>
                            <p>{{ $reservation->review->owner_reply }}</p>
                            <small>{{ $reservation->review->replied_at?->translatedFormat('d F Y') }}</small>
                        </div>
                    @endif
                </section>
            @elseif ($canReview)
                <section class="acc-card acc-card-highlight" id="avis">
                    <div class="acc-card-head"><h2><i class="fa-solid fa-star"></i> Comment s’est passé votre séjour ?</h2></div>
                    <form method="POST" action="{{ route('client.reservations.review', $reservation) }}" class="acc-form">
                        @csrf
                        <fieldset class="acc-rating" aria-label="Note globale">
                            <legend>Note globale <span class="acc-required">*</span></legend>
                            <div class="acc-rating-scale">
                                @for ($i = 1; $i <= 10; $i++)
                                    <label>
                                        <input type="radio" name="note" value="{{ $i }}" @checked((int) old('note') === $i) required>
                                        <span>{{ $i }}</span>
                                    </label>
                                @endfor
                            </div>
                            @error('note') <p class="acc-error">{{ $message }}</p> @enderror
                        </fieldset>

                        <div class="acc-criteria">
                            @foreach (['proprete' => 'Propreté', 'confort' => 'Confort', 'emplacement' => 'Emplacement', 'accueil' => 'Accueil', 'rapport' => 'Rapport qualité-prix'] as $field => $label)
                                <label>
                                    <span>{{ $label }}</span>
                                    <select name="{{ $field }}">
                                        <option value="">—</option>
                                        @for ($i = 10; $i >= 1; $i--)
                                            <option value="{{ $i }}" @selected((int) old($field) === $i)>{{ $i }}/10</option>
                                        @endfor
                                    </select>
                                </label>
                            @endforeach
                        </div>

                        <label class="acc-field">
                            <span>Titre <em>(facultatif)</em></span>
                            <input type="text" name="titre" maxlength="120" value="{{ old('titre') }}" placeholder="Ex : Séjour parfait, accueil chaleureux">
                        </label>
                        <label class="acc-field">
                            <span>Votre avis <span class="acc-required">*</span></span>
                            <textarea name="commentaire" rows="4" maxlength="2000" required placeholder="Ce que vous avez aimé, ce qui pourrait être amélioré…">{{ old('commentaire') }}</textarea>
                            @error('commentaire') <p class="acc-error">{{ $message }}</p> @enderror
                        </label>
                        <button type="submit" class="acc-btn acc-btn-gold">Publier mon avis</button>
                    </form>
                </section>
            @endif

            {{-- Paiements --}}
            @if ($reservation->payments->isNotEmpty())
                <section class="acc-card">
                    <div class="acc-card-head"><h2>Paiements</h2></div>
                    <ul class="acc-payments">
                        @foreach ($reservation->payments as $payment)
                            <li>
                                <span class="acc-feed-icon tone-{{ $payment->statut->tone() }}"><i class="fa-solid {{ $payment->isManual() ? 'fa-money-bill-wave' : 'fa-mobile-screen' }}"></i></span>
                                <span>
                                    <strong>{{ $money($payment->amount) }}</strong>
                                    <small>{{ ($payment->paid_at ?? $payment->created_at)->translatedFormat('d F Y à H:i') }} · {{ $payment->method?->label() ?? 'Paiement en ligne' }}</small>
                                </span>
                                <span class="acc-pill tone-{{ $payment->statut->tone() }}">{{ $payment->statut->label() }}</span>
                                @if ($payment->statut === TransactionStatus::Accepted || $payment->statut === TransactionStatus::Refunded)
                                    <a href="{{ route('admin.paiements.receipt', $payment) }}" target="_blank" rel="noopener" class="acc-icon-link" title="Reçu"><i class="fa-solid fa-receipt"></i></a>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                </section>
            @endif
        </div>

        <aside class="acc-col">
            {{-- Montant et paiement --}}
            <section class="acc-card acc-amount-card">
                <dl class="acc-amounts">
                    <div><dt>Hébergement</dt><dd>{{ $money($reservation->subtotal) }}</dd></div>
                    @if ($reservation->cleaning_fee > 0)
                        <div><dt>Ménage</dt><dd>{{ $money($reservation->cleaning_fee) }}</dd></div>
                    @endif
                    @if ($reservation->service_fee > 0)
                        <div><dt>Frais de service</dt><dd>{{ $money($reservation->service_fee) }}</dd></div>
                    @endif
                    <div class="is-total"><dt>Total</dt><dd>{{ $money($reservation->total_amount) }}</dd></div>
                    <div><dt>Déjà réglé</dt><dd>{{ $money($reservation->amount_paid) }}</dd></div>
                    @if ($status !== ReservationStatus::Cancelled)
                        <div class="{{ $balance > 0 ? 'is-due' : 'is-clear' }}"><dt>Reste à payer</dt><dd>{{ $money($balance) }}</dd></div>
                    @endif
                </dl>

                @if ($canPay)
                    <form method="POST" action="{{ route('client.reservations.pay', $reservation) }}">
                        @csrf
                        <button type="submit" class="acc-btn acc-btn-gold acc-btn-block"><i class="fa-solid fa-lock"></i> Payer {{ $money($balance) }} en ligne</button>
                    </form>
                    <p class="acc-small"><i class="fa-solid fa-mobile-screen"></i> Mobile Money ou carte bancaire, paiement sécurisé.</p>
                @elseif ($balance > 0 && $status !== ReservationStatus::Cancelled)
                    <p class="acc-small"><i class="fa-solid fa-circle-info"></i> Le solde se règle auprès de l’établissement, à votre arrivée.</p>
                @endif
            </section>

            {{-- Établissement --}}
            <section class="acc-card">
                <div class="acc-card-head"><h2>Établissement</h2></div>
                <ul class="acc-contact">
                    <li><i class="fa-solid fa-location-dot"></i> {{ $property?->address }}{{ $property?->city ? ', '.$property->city->name : '' }}</li>
                    @if ($property?->phone)
                        <li><i class="fa-solid fa-phone"></i> <a href="tel:{{ $property->internationalPhone() }}">{{ $property->formattedPhone() }}</a></li>
                    @endif
                    @if ($property?->email)
                        <li><i class="fa-regular fa-envelope"></i> <a href="mailto:{{ $property->email }}">{{ $property->email }}</a></li>
                    @endif
                    @if ($property?->latitude && $property?->longitude)
                        <li><i class="fa-solid fa-map"></i> <a href="https://www.google.com/maps/dir/?api=1&destination={{ $property->latitude }},{{ $property->longitude }}" target="_blank" rel="noopener">Itinéraire</a></li>
                    @endif
                </ul>
                @if ($property)
                    <a href="{{ route('residences.show', $property) }}" class="acc-link">Voir la fiche de la résidence <i class="fa-solid fa-arrow-right"></i></a>
                @endif
            </section>

            {{-- Annulation --}}
            <section class="acc-card">
                <div class="acc-card-head"><h2>Annulation</h2></div>
                <p class="acc-small">
                    {{ $reservation->cancellation_policy->label() }}.
                    @if ($deadline && $deadline->isFuture())
                        <br><strong>Gratuite jusqu’au {{ $deadline->translatedFormat('d F Y à H:i') }}</strong> : vos paiements vous seront remboursés.
                    @elseif ($canCancel)
                        <br>Le délai d’annulation gratuite est dépassé : les sommes réglées restent acquises à l’établissement.
                    @endif
                </p>
                @if ($canCancel)
                    <details class="acc-cancel">
                        <summary class="acc-btn acc-btn-danger-light">Annuler ma réservation</summary>
                        <form method="POST" action="{{ route('client.reservations.cancel', $reservation) }}" class="acc-form">
                            @csrf
                            @method('PATCH')
                            <label class="acc-field">
                                <span>Motif <em>(facultatif)</em></span>
                                <textarea name="motif" rows="2" maxlength="500" placeholder="Changement de programme…"></textarea>
                            </label>
                            <button type="submit" class="acc-btn acc-btn-danger acc-btn-block">Confirmer l’annulation</button>
                        </form>
                    </details>
                @endif
            </section>
        </aside>
    </div>
@endsection
