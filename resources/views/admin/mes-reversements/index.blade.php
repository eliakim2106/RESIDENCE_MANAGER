@extends('layouts.admin')

@php
    use App\Enums\PayoutMethod;

    $money = fn (int $amount): string => number_format($amount, 0, ',', ' ').' FCFA';
    $method = old('moyen', $owner->payout_method?->value ?? PayoutMethod::MobileMoney->value);
@endphp

@section('title', 'Mes reversements')

@section('content')
    <div class="admin-page-header">
        <div class="admin-page-heading">
            <span class="admin-page-icon"><i class="fa-solid fa-hand-holding-dollar"></i></span>
            <div>
                <h1>Mes reversements</h1>
                <p>Les paiements en ligne de vos clients sont encaissés par DS Holding, qui vous reverse votre part.</p>
            </div>
        </div>
    </div>

    @include('partials.flash')

    @unless ($owner->payoutAccountSummary())
        <div class="moderation-banner tone-warning" role="status">
            <i class="fa-solid fa-triangle-exclamation"></i>
            <div>
                <strong>Indiquez où recevoir vos reversements</strong>
                <p>Renseignez votre numéro Mobile Money ou votre RIB ci-dessous pour que DS Holding puisse vous verser vos gains.</p>
            </div>
        </div>
    @endunless

    @include('admin.reversements._summary', ['balance' => $balance, 'payouts' => $payouts, 'rate' => $rate])

    @if ($balance['available'] < 0)
        <div class="moderation-banner tone-critical" role="status">
            <i class="fa-solid fa-circle-minus"></i>
            <div>
                <strong>Solde débiteur de {{ $money(abs($balance['available'])) }}</strong>
                <p>Des remboursements ont été faits à vos clients après un reversement : ce montant sera déduit de vos prochains encaissements.</p>
            </div>
        </div>
    @endif

    @include('admin.reversements._lines', ['lines' => $balance['lines']])

    <div class="resa-layout">
        <div class="resa-main">
            @include('admin.reversements._history', ['payouts' => $payouts, 'canCancel' => false])
        </div>

        <aside class="resa-aside">
            <section class="dash-card">
                <div class="dash-card-header">
                    <h2>Coordonnées de reversement</h2>
                </div>

                <form method="POST" action="{{ route('admin.mes-reversements.account') }}" class="payout-account-form" data-payout-account>
                    @csrf
                    @method('PUT')

                    <div class="form-group">
                        <label for="moyen">Recevoir par</label>
                        <select name="moyen" id="moyen" required data-payout-method>
                            @foreach ($methods as $value => $label)
                                <option value="{{ $value }}" data-account-label="{{ PayoutMethod::from($value)->accountLabel() }}" @selected($method === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('moyen') <p class="field-error">{{ $message }}</p> @enderror
                    </div>
                    <div class="form-group">
                        <label for="compte" data-account-label>{{ PayoutMethod::from($method)->accountLabel() }}</label>
                        <input type="text" name="compte" id="compte" maxlength="100" required value="{{ old('compte', $owner->payout_account) }}" placeholder="Ex. 07 00 00 00 00">
                        @error('compte') <p class="field-error">{{ $message }}</p> @enderror
                    </div>
                    <div class="form-group">
                        <label for="titulaire">Titulaire du compte</label>
                        <input type="text" name="titulaire" id="titulaire" maxlength="191" required value="{{ old('titulaire', $owner->payout_holder ?? $owner->name) }}">
                        @error('titulaire') <p class="field-error">{{ $message }}</p> @enderror
                    </div>
                    <button type="submit" class="btn-primary w-100">
                        <i class="fa-solid fa-floppy-disk"></i>
                        Enregistrer
                    </button>
                </form>
            </section>

            <section class="dash-card">
                <div class="dash-card-header">
                    <h2>Comment ça marche</h2>
                </div>
                <ul class="payout-steps">
                    <li><i class="fa-solid fa-credit-card"></i> Vos clients paient en ligne ; DS Holding encaisse le paiement.</li>
                    <li><i class="fa-solid fa-hourglass-half"></i> Le montant devient disponible quand le séjour commence.</li>
                    <li><i class="fa-solid fa-percent"></i> La commission de votre formule ({{ rtrim(rtrim(number_format($rate, 2, ',', ' '), '0'), ',') }} %) est déduite.</li>
                    <li><i class="fa-solid fa-hand-holding-dollar"></i> DS Holding vous reverse le solde ; vous êtes prévenu par e-mail avec le relevé.</li>
                </ul>
            </section>
        </aside>
    </div>
@endsection
