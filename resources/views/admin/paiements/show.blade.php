@extends('layouts.admin')

@php
    use App\Enums\PaymentMethod;
    use App\Enums\TransactionStatus;

    $money = fn (int $amount): string => number_format($amount, 0, ',', ' ').' FCFA';
    $methodIcon = fn (?PaymentMethod $method): string => match ($method) {
        PaymentMethod::Cash => 'fa-money-bill-wave',
        PaymentMethod::Card => 'fa-credit-card',
        PaymentMethod::Wallet => 'fa-wallet',
        default => 'fa-mobile-screen',
    };

    $date = $payment->paid_at ?? $payment->created_at;
    $refundable = $payment->refundableAmount();
    $hasReceipt = in_array($payment->statut, [TransactionStatus::Accepted, TransactionStatus::Refunded], true);
    $paidRatio = $reservation && $reservation->total_amount > 0 ? min(100, round($reservation->amount_paid / $reservation->total_amount * 100)) : 0;
@endphp

@section('title', 'Paiement '.$payment->transaction_id)

@section('content')
    {{-- ========== En-tête ========== --}}
    <header class="resa-header">
        <a href="{{ route('admin.paiements.index') }}" class="resa-back">
            <i class="fa-solid fa-arrow-left"></i>
            Paiements
        </a>

        <div class="resa-header-main">
            <div class="resa-header-title">
                <div class="resa-header-line">
                    <h1>{{ $payment->transaction_id }}</h1>
                    <span class="status-pill status-{{ $payment->statut->tone() }} status-pill-lg">{{ $payment->statut->label() }}</span>
                    @if ($payment->isPartiallyRefunded())
                        <span class="status-pill status-info status-pill-lg">Remboursé en partie</span>
                    @endif
                </div>
                <p>
                    <i class="fa-solid {{ $methodIcon($payment->method) }}"></i> {{ $payment->method?->label() ?? 'Paiement' }}{{ $payment->operator ? ' · '.$payment->operator : '' }}
                    <span class="dot-sep">·</span>
                    {{ $date->translatedFormat('d F Y à H:i') }}
                </p>
            </div>

            <div class="resa-actionbar">
                @if ($hasReceipt)
                    <a href="{{ route('admin.paiements.receipt', $payment) }}" class="btn-secondary" target="_blank" rel="noopener">
                        <i class="fa-solid fa-receipt"></i>
                        Reçu
                    </a>
                @endif
                @if ($refundable > 0)
                    <button type="button" class="btn-outline-danger" data-modal-open="refundModal">
                        <i class="fa-solid fa-rotate-left"></i>
                        Rembourser
                    </button>
                @endif
            </div>
        </div>
    </header>

    @include('partials.flash')

    <div class="resa-layout">

        {{-- ========== Transaction ========== --}}
        <div class="resa-main">
            <section class="dash-card">
                <div class="pay-amount-hero">
                    <span class="stay-big-label">Montant</span>
                    <strong>{{ $money($payment->amount) }}</strong>
                    @if ($payment->refunded_amount > 0)
                        <p>
                            <span class="text-tone-info">− {{ $money($payment->refunded_amount) }} remboursé</span>
                            <span class="dot-sep">·</span>
                            Net conservé : <strong>{{ $money($payment->netAmount()) }}</strong>
                        </p>
                    @endif
                </div>

                <dl class="detail-grid">
                    <div>
                        <dt>Identifiant de transaction</dt>
                        <dd class="cell-mono">{{ $payment->transaction_id }}</dd>
                    </div>
                    <div>
                        <dt>Référence opérateur</dt>
                        <dd class="cell-mono">{{ $payment->operator_reference ?: '—' }}</dd>
                    </div>
                    <div>
                        <dt>Moyen de paiement</dt>
                        <dd>{{ $payment->method?->label() ?? '—' }}{{ $payment->operator ? ' · '.$payment->operator : '' }}</dd>
                    </div>
                    <div>
                        <dt>Source</dt>
                        <dd>
                            @if ($payment->isManual())
                                Saisie manuelle{{ $payment->user ? ' par '.$payment->user->name : '' }}
                            @else
                                En ligne ({{ Str::ucfirst($payment->provider) }})
                            @endif
                        </dd>
                    </div>
                    <div>
                        <dt>Créé le</dt>
                        <dd>{{ $payment->created_at->translatedFormat('d F Y à H:i') }}</dd>
                    </div>
                    <div>
                        <dt>Encaissé le</dt>
                        <dd>{{ $payment->paid_at?->translatedFormat('d F Y à H:i') ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt>Devise</dt>
                        <dd>{{ $payment->currency === 'XOF' ? 'Franc CFA (XOF)' : $payment->currency }}</dd>
                    </div>
                    @if ($payment->refunded_at)
                        <div>
                            <dt>Remboursé le</dt>
                            <dd>{{ $payment->refunded_at->translatedFormat('d F Y à H:i') }}</dd>
                        </div>
                    @endif
                </dl>

                @if ($payment->refund_reason)
                    <div class="resa-request">
                        <i class="fa-solid fa-rotate-left"></i>
                        <div>
                            <strong>Motif du remboursement</strong>
                            <p>{{ $payment->refund_reason }}</p>
                        </div>
                    </div>
                @endif
            </section>

            {{-- Autres paiements de la réservation --}}
            @if ($siblings->isNotEmpty())
                <section class="dash-card">
                    <div class="dash-card-header">
                        <div>
                            <h2>Autres paiements de la réservation</h2>
                            <p>{{ $siblings->count() }} opération{{ $siblings->count() > 1 ? 's' : '' }}</p>
                        </div>
                    </div>

                    <ul class="resa-payments">
                        @foreach ($siblings as $sibling)
                            <li>
                                <span class="cell-icon"><i class="fa-solid {{ $methodIcon($sibling->method) }}"></i></span>
                                <span class="resa-unit-text">
                                    <a href="{{ route('admin.paiements.show', $sibling) }}" class="cell-title-link"><strong>{{ $sibling->method?->label() ?? 'Paiement' }}</strong></a>
                                    <small>{{ ($sibling->paid_at ?? $sibling->created_at)->translatedFormat('d M Y à H:i') }} · {{ $sibling->transaction_id }}</small>
                                </span>
                                <span class="resa-payment-end">
                                    <span class="resa-unit-amount">{{ $money($sibling->amount) }}</span>
                                    <span class="status-pill status-{{ $sibling->statut->tone() }}">{{ $sibling->statut->label() }}</span>
                                </span>
                            </li>
                        @endforeach
                    </ul>
                </section>
            @endif
        </div>

        {{-- ========== Réservation liée ========== --}}
        <aside class="resa-aside">
            @if ($reservation)
                <section class="dash-card">
                    <div class="dash-card-header">
                        <h2>Réservation</h2>
                        <span class="status-pill status-{{ $reservation->statut->tone() }}">{{ $reservation->statut->label() }}</span>
                    </div>

                    <a href="{{ route('admin.reservations.show', $reservation) }}" class="resa-ref">{{ $reservation->reference }}</a>

                    <dl class="resa-info linked-info">
                        <div>
                            <dt>Client</dt>
                            <dd>{{ $reservation->guest_name }}</dd>
                        </div>
                        <div>
                            <dt>Établissement</dt>
                            <dd>{{ $reservation->property?->name ?? '—' }}</dd>
                        </div>
                        <div>
                            <dt>Séjour</dt>
                            <dd>{{ $reservation->check_in->format('d/m/Y') }} → {{ $reservation->check_out->format('d/m/Y') }} ({{ $reservation->nights }} nuit{{ $reservation->nights > 1 ? 's' : '' }})</dd>
                        </div>
                    </dl>

                    <div class="amount-summary linked-amount">
                        <div class="linked-amount-line">
                            <span>Réglé</span>
                            <strong>{{ $money($reservation->amount_paid) }} / {{ $money($reservation->total_amount) }}</strong>
                        </div>
                        <span class="pay-progress pay-progress-lg" role="img" aria-label="{{ $paidRatio }} % réglé">
                            <span class="pay-progress-bar tone-{{ $reservation->payment_state->tone() }}" style="width: {{ $paidRatio }}%"></span>
                        </span>
                        <small class="text-tone-{{ $reservation->payment_state->tone() }}">{{ $reservation->payment_state->label() }}</small>
                    </div>

                    <a href="{{ route('admin.reservations.show', $reservation) }}" class="btn-secondary linked-btn">
                        <i class="fa-solid fa-arrow-right"></i>
                        Ouvrir la réservation
                    </a>
                </section>
            @endif
        </aside>
    </div>

    {{-- ========== Remboursement ========== --}}
    @if ($refundable > 0)
        <div class="modal-overlay" id="refundModal" data-action-modal @if ($errors->hasAny(['montant', 'motif'])) data-open-on-load @endif>
            <div class="modal-card modal-form" role="dialog" aria-modal="true" aria-labelledby="refundModalTitle">
                <div class="modal-icon modal-icon-info"><i class="fa-solid fa-rotate-left"></i></div>
                <h3 id="refundModalTitle">Rembourser ce paiement</h3>
                <p>Jusqu’à <strong>{{ $money($refundable) }}</strong>. Le client est prévenu par email ; le versement se fait par le moyen de paiement d’origine.</p>

                <form method="POST" action="{{ route('admin.paiements.refund', $payment) }}">
                    @csrf
                    @method('PATCH')
                    <div class="form-group">
                        <label for="montant">Montant à rembourser <span class="required">*</span></label>
                        <div class="input-affix">
                            <input type="text" name="montant" id="montant" inputmode="numeric" required value="{{ old('montant', $refundable) }}">
                            <span>FCFA</span>
                        </div>
                        @error('montant')
                            <p class="field-error">{{ $message }}</p>
                        @enderror
                    </div>
                    <div class="form-group">
                        <label for="motif">Motif <span class="required">*</span></label>
                        <textarea name="motif" id="motif" rows="3" maxlength="500" required placeholder="Ex. : séjour écourté d’une nuit, geste commercial, erreur de saisie…">{{ old('motif') }}</textarea>
                        @error('motif')
                            <p class="field-error">{{ $message }}</p>
                        @enderror
                    </div>
                    <div class="modal-actions">
                        <button type="button" class="btn-cancel" data-modal-close>Retour</button>
                        <button type="submit" class="btn-save">Rembourser</button>
                    </div>
                </form>
            </div>
        </div>
    @endif
@endsection
