{{-- Détail du solde, paiement par paiement. Paramètres : $lines (PayoutLedger::lines) --}}
@php
    $money = fn (int $amount): string => number_format($amount, 0, ',', ' ').' FCFA';
@endphp

<section class="dash-card payout-lines">
    <div class="dash-card-header">
        <div>
            <h2>Détail du solde</h2>
            <p>Paiements en ligne encaissés par DS Holding, nets des remboursements et de ce qui a déjà été reversé.</p>
        </div>
    </div>

    @if ($lines->isEmpty())
        <p class="resa-note">Aucun montant en attente de reversement.</p>
    @else
        <div class="table-card payout-table-card">
            <table class="custom-table resa-table">
                <thead>
                    <tr>
                        <th>Paiement</th>
                        <th>Réservation</th>
                        <th class="text-end">Encaissé</th>
                        <th class="text-end">Commission</th>
                        <th class="text-end">À reverser</th>
                        <th>Statut</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($lines as $line)
                        @php
                            $payment = $line['payment'];
                            $reservation = $payment->reservation;
                        @endphp
                        <tr>
                            <td>
                                <span class="cell-stack">
                                    <a href="{{ route('admin.paiements.show', $payment) }}" class="cell-title-link">{{ $payment->transaction_id }}</a>
                                    <small>{{ $payment->paid_at?->format('d/m/Y') ?? '—' }}</small>
                                </span>
                            </td>
                            <td>
                                <span class="cell-stack">
                                    <a href="{{ route('admin.reservations.show', $reservation) }}" class="cell-title-link">{{ $reservation->reference }}</a>
                                    <small>{{ $reservation->property->name }} · {{ $reservation->check_in->format('d/m') }} → {{ $reservation->check_out->format('d/m/Y') }}</small>
                                </span>
                            </td>
                            <td class="text-end text-nowrap">{{ $money($line['gross']) }}</td>
                            <td class="text-end text-nowrap cell-muted">{{ $line['commission'] !== 0 ? '− '.$money(abs($line['commission'])) : '—' }}</td>
                            <td class="text-end text-nowrap"><strong class="{{ $line['amount'] < 0 ? 'text-tone-critical' : '' }}">{{ $money($line['amount']) }}</strong></td>
                            <td>
                                @if ($line['gross'] < 0)
                                    <span class="status-pill status-critical" title="Remboursé au client après un reversement : déduit du prochain">Remboursement</span>
                                @elseif ($line['available'])
                                    <span class="status-pill status-good">Disponible</span>
                                @else
                                    <span class="status-pill status-info" title="Disponible à partir du {{ $reservation->check_in->format('d/m/Y') }}">À venir</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</section>
