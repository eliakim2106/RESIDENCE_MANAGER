@php
    $owner = $payout->user;
    $money = fn (int $amount): string => number_format($amount, 0, ',', ' ').' FCFA';
@endphp
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>Relevé {{ $payout->number }} · DS HOLDING</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --navy: #0a1f44;
            --gold: #d4a72c;
            --blue: #1565c0;
            --text: #0f1b33;
            --muted: #64748b;
            --border: #e6eaf2;
            --soft: #f8fafc;
            --danger: #b91c1c;
        }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            background: #eef1f7;
            color: var(--text);
            font-family: "Poppins", system-ui, sans-serif;
            font-size: 14px;
            line-height: 1.5;
        }

        .toolbar {
            display: flex;
            justify-content: center;
            padding: 20px 16px 0;
        }

        .toolbar button {
            padding: 10px 20px;
            border: 1px solid var(--blue);
            border-radius: 10px;
            background: var(--blue);
            color: #fff;
            font: 600 14px "Poppins", sans-serif;
            cursor: pointer;
        }

        .sheet {
            width: 100%;
            max-width: 860px;
            margin: 20px auto 40px;
            padding: 44px 48px;
            border-radius: 16px;
            background: #fff;
            box-shadow: 0 20px 50px rgba(10, 31, 68, 0.12);
        }

        .head {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 24px;
            padding-bottom: 24px;
            border-bottom: 3px solid var(--gold);
        }

        .head img { height: 60px; }

        .head-ref { text-align: right; }

        .head-ref h1 {
            margin: 0;
            color: var(--navy);
            font-size: 22px;
            letter-spacing: 0.06em;
            text-transform: uppercase;
        }

        .head-ref p { margin: 2px 0 0; color: var(--muted); }

        .badge {
            display: inline-block;
            margin-top: 8px;
            padding: 4px 12px;
            border-radius: 999px;
            background: #e8f7ee;
            color: #15803d;
            font-size: 12px;
            font-weight: 600;
        }

        .badge.is-cancelled { background: #f1f5f9; color: var(--muted); }

        .parties {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 24px;
            margin: 28px 0;
        }

        .parties h2 {
            margin: 0 0 6px;
            color: var(--muted);
            font-size: 12px;
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }

        .parties p { margin: 0; }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th,
        td {
            padding: 10px 8px;
            border-bottom: 1px solid var(--border);
            text-align: left;
            vertical-align: top;
        }

        th:first-child,
        td:first-child { padding-left: 0; }

        th:last-child,
        td:last-child { padding-right: 0; }

        th {
            color: var(--muted);
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
        }

        td small { display: block; color: var(--muted); font-size: 12px; }

        .amount { text-align: right; white-space: nowrap; }

        .negative { color: var(--danger); }

        .totals {
            width: 320px;
            margin: 20px 0 0 auto;
        }

        .totals div {
            display: flex;
            justify-content: space-between;
            gap: 16px;
            padding: 6px 0;
        }

        .totals .grand {
            margin-top: 6px;
            padding-top: 12px;
            border-top: 2px solid var(--navy);
            color: var(--navy);
            font-size: 18px;
            font-weight: 700;
        }

        .dates {
            display: flex;
            flex-wrap: wrap;
            gap: 12px 32px;
            margin-top: 24px;
            padding: 16px 20px;
            border-radius: 12px;
            background: var(--soft);
            font-size: 13px;
        }

        .dates strong { display: block; color: var(--navy); }

        .foot {
            margin-top: 32px;
            color: var(--muted);
            font-size: 12px;
            text-align: center;
        }

        @media (max-width: 640px) {
            .sheet { padding: 28px 16px; border-radius: 0; }
            .head { flex-direction: column; }
            .head-ref { text-align: left; }
            .parties { grid-template-columns: 1fr; }
            .table-wrap { overflow-x: auto; }
            .totals { width: 100%; }
        }

        @media print {
            @page { size: A4; margin: 14mm; }
            body { background: #fff; font-size: 12px; }
            .toolbar { display: none; }
            .sheet { max-width: none; margin: 0; padding: 0; box-shadow: none; }
            .badge, .dates { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        }
    </style>
</head>
<body>
    <div class="toolbar">
        <button type="button" onclick="window.print()">Imprimer / Enregistrer en PDF</button>
    </div>

    <main class="sheet">
        <header class="head">
            <img src="{{ asset('assets/images/logo/ds_holding_logo.png') }}" alt="DS HOLDING">
            <div class="head-ref">
                <h1>Relevé de reversement</h1>
                <p>N° {{ $payout->number }}</p>
                <p>Du {{ $payout->paid_at->format('d/m/Y') }}</p>
                <span class="badge {{ $payout->isPaid() ? '' : 'is-cancelled' }}">{{ $payout->statut->label() }}</span>
            </div>
        </header>

        <section class="parties">
            <div>
                <h2>Versé par</h2>
                <p><strong>DS HOLDING</strong></p>
                <p>Plateforme de réservation de résidences</p>
            </div>
            <div>
                <h2>Bénéficiaire</h2>
                <p><strong>{{ $owner->company_name ?: $owner->name }}</strong></p>
                @if ($owner->company_name)
                    <p>{{ $owner->name }}</p>
                @endif
                <p>{{ $owner->email }}</p>
                @if ($payout->account)
                    <p>{{ $payout->account }}</p>
                @endif
            </div>
        </section>

        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Réservation</th>
                        <th>Paiement</th>
                        <th class="amount">Encaissé</th>
                        <th class="amount">Commission</th>
                        <th class="amount">Reversé</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($payout->items as $item)
                        @php
                            $payment = $item->payment;
                            $reservation = $payment?->reservation;
                        @endphp
                        <tr>
                            <td>
                                {{ $reservation?->reference ?? '—' }}
                                @if ($reservation)
                                    <small>{{ $reservation->property->name }} · {{ $reservation->check_in->format('d/m') }} → {{ $reservation->check_out->format('d/m/Y') }}</small>
                                @endif
                            </td>
                            <td>
                                {{ $payment?->transaction_id ?? '—' }}
                                <small>{{ $item->gross_amount < 0 ? 'Remboursement client' : $payment?->paid_at?->format('d/m/Y') }}</small>
                            </td>
                            <td class="amount {{ $item->gross_amount < 0 ? 'negative' : '' }}">{{ $money($item->gross_amount) }}</td>
                            <td class="amount">{{ $item->commission_amount !== 0 ? $money(-$item->commission_amount) : '—' }}</td>
                            <td class="amount {{ $item->amount < 0 ? 'negative' : '' }}">{{ $money($item->amount) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="totals">
            <div><span>Total encaissé</span><strong>{{ $money($payout->gross_amount) }}</strong></div>
            <div><span>Commission DS Holding</span><strong>{{ $payout->commission_amount > 0 ? $money(-$payout->commission_amount) : '—' }}</strong></div>
            <div class="grand"><span>Montant reversé</span><span>{{ $money($payout->amount) }}</span></div>
        </div>

        <div class="dates">
            <span><strong>Date du virement</strong>{{ $payout->paid_at->translatedFormat('d F Y') }}</span>
            <span><strong>Moyen</strong>{{ $payout->method->label() }}</span>
            @if ($payout->reference)
                <span><strong>Référence</strong>{{ $payout->reference }}</span>
            @endif
            @if ($payout->cancelled_at)
                <span><strong>Annulé le</strong>{{ $payout->cancelled_at->translatedFormat('d F Y') }}</span>
            @endif
        </div>

        <footer class="foot">
            DS HOLDING · Relevé de reversement {{ $payout->number }}
        </footer>
    </main>
</body>
</html>
