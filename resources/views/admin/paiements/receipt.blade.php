@php
    $property = $reservation?->property;
    $money = fn (int $amount): string => number_format($amount, 0, ',', ' ').' FCFA';
    $date = $payment->paid_at ?? $payment->created_at;
    $refunded = $payment->refunded_amount > 0;
@endphp
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>Reçu {{ $payment->transaction_id }} · DS HOLDING</title>
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
            flex-wrap: wrap;
            justify-content: center;
            gap: 12px;
            padding: 20px 16px 0;
        }

        .toolbar button,
        .toolbar a {
            display: inline-flex;
            align-items: center;
            padding: 10px 20px;
            border: 1px solid var(--border);
            border-radius: 10px;
            background: #fff;
            color: var(--text);
            font: 600 14px "Poppins", sans-serif;
            text-decoration: none;
            cursor: pointer;
        }

        .toolbar .primary {
            border-color: var(--blue);
            background: var(--blue);
            color: #fff;
        }

        .sheet {
            width: 100%;
            max-width: 640px;
            margin: 20px auto 40px;
            padding: 40px 44px;
            border-radius: 16px;
            background: #fff;
            box-shadow: 0 20px 50px rgba(10, 31, 68, 0.12);
        }

        .head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
            padding-bottom: 20px;
            border-bottom: 3px solid var(--gold);
        }

        .head img { height: 56px; }

        .head h1 {
            margin: 0;
            color: var(--navy);
            font-size: 20px;
            letter-spacing: 0.04em;
            text-align: right;
            text-transform: uppercase;
        }

        .head span {
            display: block;
            color: var(--muted);
            font-size: 12px;
            text-align: right;
        }

        .amount {
            margin: 28px 0;
            padding: 24px;
            border-radius: 14px;
            background: var(--soft);
            text-align: center;
        }

        .amount span {
            display: block;
            color: var(--muted);
            font-size: 12px;
            font-weight: 600;
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }

        .amount strong {
            display: block;
            margin: 4px 0;
            color: var(--navy);
            font-size: 32px;
        }

        .amount small { color: var(--muted); }

        .amount .stamp {
            display: inline-block;
            margin-top: 8px;
            padding: 4px 14px;
            border: 2px solid #16a34a;
            border-radius: 8px;
            color: #16a34a;
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 0.1em;
            text-transform: uppercase;
            transform: rotate(-3deg);
        }

        .amount .stamp.is-refunded { border-color: var(--blue); color: var(--blue); }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th,
        td {
            padding: 9px 0;
            border-bottom: 1px solid var(--border);
            text-align: left;
            vertical-align: top;
        }

        th {
            width: 45%;
            color: var(--muted);
            font-weight: 500;
        }

        td { font-weight: 600; }

        .mono { font-family: ui-monospace, Consolas, monospace; font-size: 13px; }

        .foot {
            margin-top: 28px;
            color: var(--muted);
            font-size: 12px;
            text-align: center;
        }

        @media (max-width: 640px) {
            .sheet { padding: 28px 20px; border-radius: 0; }
            .head { flex-direction: column; align-items: flex-start; }
            .head h1, .head span { text-align: left; }
            th { width: 50%; }
        }

        @media print {
            @page { size: A5; margin: 12mm; }
            body { background: #fff; font-size: 12px; }
            .toolbar { display: none; }
            .sheet { max-width: none; margin: 0; padding: 0; box-shadow: none; }
            .amount, .stamp { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        }
    </style>
</head>
<body>
    <div class="toolbar">
        <button type="button" class="primary" onclick="window.print()">Imprimer / Enregistrer en PDF</button>
        @can('manage', $payment)
            <a href="{{ route('admin.paiements.show', $payment) }}">Retour au paiement</a>
        @elseif ($reservation)
            <a href="{{ route('admin.reservations.show', $reservation) }}">Retour à la réservation</a>
        @endcan
    </div>

    <main class="sheet">
        <header class="head">
            <img src="{{ asset('assets/images/logo/ds_holding_logo.png') }}" alt="DS HOLDING">
            <div>
                <h1>Reçu de paiement</h1>
                <span class="mono">{{ $payment->transaction_id }}</span>
            </div>
        </header>

        <section class="amount">
            <span>Montant reçu</span>
            <strong>{{ $money($payment->amount) }}</strong>
            <small>le {{ $date->translatedFormat('d F Y à H:i') }}</small>
            <br>
            @if ($refunded)
                <span class="stamp is-refunded">{{ $payment->refunded_amount >= $payment->amount ? 'Remboursé' : 'Remboursé en partie' }}</span>
            @else
                <span class="stamp">Payé</span>
            @endif
        </section>

        <table>
            <tr><th>Reçu de</th><td>{{ $reservation?->guest_name ?? '—' }}</td></tr>
            <tr><th>Réservation</th><td class="mono">{{ $reservation?->reference ?? '—' }}</td></tr>
            <tr><th>Établissement</th><td>{{ $property?->name ?? '—' }}{{ $property?->city ? ', '.$property->city->name : '' }}</td></tr>
            @if ($reservation)
                <tr><th>Séjour</th><td>Du {{ $reservation->check_in->format('d/m/Y') }} au {{ $reservation->check_out->format('d/m/Y') }}</td></tr>
            @endif
            <tr><th>Moyen de paiement</th><td>{{ $payment->method?->label() ?? '—' }}{{ $payment->operator ? ' · '.$payment->operator : '' }}</td></tr>
            @if ($payment->operator_reference)
                <tr><th>Référence opérateur</th><td class="mono">{{ $payment->operator_reference }}</td></tr>
            @endif
            @if ($refunded)
                <tr><th>Remboursé</th><td>− {{ $money($payment->refunded_amount) }}{{ $payment->refunded_at ? ' le '.$payment->refunded_at->format('d/m/Y') : '' }}</td></tr>
                <tr><th>Montant conservé</th><td>{{ $money($payment->netAmount()) }}</td></tr>
            @endif
            @if ($reservation)
                <tr><th>Total de la réservation</th><td>{{ $money($reservation->total_amount) }}</td></tr>
                <tr><th>Reste à régler</th><td>{{ $money($reservation->balanceDue()) }}</td></tr>
            @endif
        </table>

        <footer class="foot">
            Reçu émis le {{ now()->translatedFormat('d F Y à H:i') }} · DS HOLDING
        </footer>
    </main>
</body>
</html>
