@extends('layouts.admin')

@php
    use App\Enums\PayoutMethod;

    $money = fn (int $amount): string => number_format($amount, 0, ',', ' ').' FCFA';
    $plan = $owner->currentSubscription?->plan;
    $canPay = $balance['available'] > 0;
@endphp

@section('title', 'Reversements de '.$owner->name)

@section('content')
    <header class="resa-header">
        <a href="{{ route('admin.reversements.index') }}" class="resa-back">
            <i class="fa-solid fa-arrow-left"></i>
            Reversements
        </a>

        <div class="resa-header-main">
            <div class="cell-entity">
                <img src="{{ $owner->avatarUrl() }}" alt="" class="profile-hero-avatar">
                <div class="resa-header-title">
                    <div class="resa-header-line">
                        <h1 class="plain-title">{{ $owner->name }}</h1>
                    </div>
                    <p>
                        {{ $owner->company_name ?: $owner->email }}
                        <span class="dot-sep">·</span>
                        {{ $plan ? 'Formule '.$plan->name : 'Sans abonnement' }}
                        <span class="dot-sep">·</span>
                        <a href="{{ route('admin.utilisateurs.show', $owner) }}">Voir le compte</a>
                    </p>
                </div>
            </div>

            @if ($canPay)
                <div class="resa-actionbar">
                    <button type="button" class="btn-primary" data-modal-open="payoutModal">
                        <i class="fa-solid fa-hand-holding-dollar"></i>
                        Reverser {{ $money($balance['available']) }}
                    </button>
                </div>
            @endif
        </div>
    </header>

    @include('partials.flash')

    @include('admin.reversements._summary', ['balance' => $balance, 'payouts' => $payouts, 'rate' => $rate])

    @if ($balance['available'] < 0)
        <div class="moderation-banner tone-critical" role="status">
            <i class="fa-solid fa-circle-minus"></i>
            <div>
                <strong>Solde débiteur de {{ $money(abs($balance['available'])) }}</strong>
                <p>Des remboursements ont été faits après un reversement : ils seront déduits des prochains encaissements.</p>
            </div>
        </div>
    @endif

    @include('admin.reversements._lines', ['lines' => $balance['lines']])

    <div class="resa-layout">
        <div class="resa-main">
            @include('admin.reversements._history', ['payouts' => $payouts, 'canCancel' => true])
        </div>

        <aside class="resa-aside">
            <section class="dash-card">
                <div class="dash-card-header">
                    <h2>Coordonnées de reversement</h2>
                </div>

                @if ($owner->payoutAccountSummary())
                    <dl class="resa-amounts">
                        <div>
                            <dt>Moyen</dt>
                            <dd>{{ $owner->payout_method->label() }}</dd>
                        </div>
                        <div>
                            <dt>{{ $owner->payout_method->accountLabel() }}</dt>
                            <dd class="text-wrap text-break">{{ $owner->payout_account }}</dd>
                        </div>
                        @if ($owner->payout_holder)
                            <div>
                                <dt>Titulaire</dt>
                                <dd>{{ $owner->payout_holder }}</dd>
                            </div>
                        @endif
                    </dl>
                @else
                    <p class="resa-note"><i class="fa-solid fa-triangle-exclamation text-tone-warning"></i> Non renseignées. Le propriétaire les saisit depuis « Mes reversements »{{ $owner->phone ? ' ; téléphone : '.$owner->phone : '' }}.</p>
                @endif
            </section>
        </aside>
    </div>

    {{-- ========== Enregistrer le reversement ========== --}}
    @if ($canPay)
        <div class="modal-overlay" id="payoutModal" data-action-modal>
            <div class="modal-card modal-form" role="dialog" aria-modal="true" aria-labelledby="payoutModalTitle">
                <div class="modal-icon modal-icon-info"><i class="fa-solid fa-hand-holding-dollar"></i></div>
                <h3 id="payoutModalTitle">Reverser {{ $money($balance['available']) }}</h3>
                <p>
                    Effectuez d’abord le virement à {{ $owner->name }}, puis enregistrez-le ici.
                    @if ($balance['commission'] > 0)
                        Encaissé {{ $money($balance['gross']) }}, commission {{ $money($balance['commission']) }}.
                    @endif
                </p>

                <form method="POST" action="{{ route('admin.reversements.store', $owner) }}">
                    @csrf
                    <div class="form-group">
                        <label for="moyen">Moyen</label>
                        <select name="moyen" id="moyen" required>
                            @foreach ($methods as $value => $label)
                                <option value="{{ $value }}" @selected(($owner->payout_method ?? PayoutMethod::MobileMoney)->value === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="reference">Référence du virement <span class="field-optional">(facultatif)</span></label>
                        <input type="text" name="reference" id="reference" maxlength="100" placeholder="ID de transaction Mobile Money, n° de virement…">
                    </div>
                    <div class="form-group">
                        <label for="date">Date du virement</label>
                        <input type="date" name="date" id="date" value="{{ now()->toDateString() }}" max="{{ now()->toDateString() }}" data-datepicker>
                    </div>
                    <div class="form-group">
                        <label for="notes">Note <span class="field-optional">(facultatif)</span></label>
                        <textarea name="notes" id="notes" rows="2" maxlength="500"></textarea>
                    </div>
                    <div class="modal-actions">
                        <button type="button" class="btn-cancel" data-modal-close>Retour</button>
                        <button type="submit" class="btn-save">Enregistrer le reversement</button>
                    </div>
                </form>
            </div>
        </div>
    @endif
@endsection
