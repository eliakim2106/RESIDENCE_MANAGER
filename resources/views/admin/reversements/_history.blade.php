{{-- Historique des reversements d'un propriétaire. Paramètres : $payouts, $canCancel (administrateurs) --}}
@php
    $money = fn (int $amount): string => number_format($amount, 0, ',', ' ').' FCFA';
@endphp

<section class="dash-card">
    <div class="dash-card-header">
        <div>
            <h2>Historique</h2>
            <p>{{ $payouts->count() }} reversement{{ $payouts->count() > 1 ? 's' : '' }}</p>
        </div>
    </div>

    @if ($payouts->isEmpty())
        <p class="resa-note">Aucun reversement pour le moment.</p>
    @else
        <ul class="invoice-list payout-list">
            @foreach ($payouts as $payout)
                <li class="{{ $payout->isPaid() ? '' : 'is-cancelled' }}">
                    <span class="cell-icon"><i class="fa-solid {{ $payout->method->icon() }}"></i></span>
                    <span class="resa-unit-text">
                        <strong>{{ $payout->number }} · {{ $money($payout->amount) }}</strong>
                        <small>
                            {{ $payout->paid_at->format('d/m/Y') }} · {{ $payout->method->label() }}{{ $payout->reference ? ' · réf. '.$payout->reference : '' }}
                            @if ($payout->cancelled_at)
                                · annulé le {{ $payout->cancelled_at->format('d/m/Y') }}
                            @endif
                        </small>
                    </span>
                    <span class="invoice-actions">
                        <span class="status-pill status-{{ $payout->statut->tone() }}">{{ $payout->statut->label() }}</span>
                        <a href="{{ route('admin.reversements.statement', $payout) }}" class="action-btn" target="_blank" rel="noopener" title="Relevé" aria-label="Imprimer le relevé {{ $payout->number }}">
                            <i class="fa-solid fa-print"></i>
                        </a>
                        @if ($canCancel && $payout->isPaid())
                            <form method="POST" action="{{ route('admin.reversements.cancel', $payout) }}" class="inline-action"
                                data-confirm="Annuler le reversement {{ $payout->number }} ? Les montants redeviendront à reverser.">
                                @csrf
                                @method('PATCH')
                                <button type="submit" class="action-btn delete" title="Annuler le reversement" aria-label="Annuler le reversement {{ $payout->number }}">
                                    <i class="fa-solid fa-xmark"></i>
                                </button>
                            </form>
                        @endif
                    </span>
                </li>
            @endforeach
        </ul>
    @endif
</section>
