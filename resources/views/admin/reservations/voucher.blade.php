@php
    $property = $reservation->property;
    $money = fn (int $amount): string => number_format($amount, 0, ',', ' ').' FCFA';
    $hour = fn (?string $time): ?string => $time ? substr($time, 0, 5) : null;
@endphp
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>Bon de réservation {{ $reservation->reference }} · DS HOLDING</title>
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
            justify-content: center;
            gap: 12px;
            padding: 20px 16px 0;
        }

        .toolbar button,
        .toolbar a {
            display: inline-flex;
            align-items: center;
            gap: 8px;
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
            max-width: 800px;
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

        .head img { height: 64px; }

        .head-ref { text-align: right; }

        .head-ref span {
            display: block;
            color: var(--muted);
            font-size: 12px;
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }

        .head-ref strong {
            display: block;
            color: var(--navy);
            font-size: 24px;
            letter-spacing: 0.04em;
        }

        .badge {
            display: inline-block;
            margin-top: 6px;
            padding: 4px 12px;
            border-radius: 999px;
            background: #e8f7ee;
            color: #15803d;
            font-size: 12px;
            font-weight: 600;
        }

        .badge.is-warning { background: #fef5e6; color: #b45309; }
        .badge.is-critical { background: #fdecec; color: #dc2626; }

        h1 {
            margin: 28px 0 4px;
            color: var(--navy);
            font-size: 22px;
        }

        .lead { margin: 0; color: var(--muted); }

        .dates {
            display: grid;
            grid-template-columns: 1fr auto 1fr;
            align-items: center;
            gap: 16px;
            margin: 24px 0;
            padding: 20px 24px;
            border-radius: 14px;
            background: var(--soft);
        }

        .date span {
            display: block;
            color: var(--muted);
            font-size: 11px;
            font-weight: 600;
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }

        .date strong {
            display: block;
            color: var(--navy);
            font-size: 18px;
        }

        .date small { color: var(--muted); }

        .date:last-child { text-align: right; }

        .nights {
            padding: 6px 14px;
            border-radius: 999px;
            background: var(--navy);
            color: #fff;
            font-size: 12px;
            font-weight: 600;
            white-space: nowrap;
        }

        .grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 24px;
            margin-bottom: 24px;
        }

        h2 {
            margin: 0 0 10px;
            color: var(--navy);
            font-size: 13px;
            letter-spacing: 0.06em;
            text-transform: uppercase;
        }

        .block p { margin: 0 0 2px; }

        .block .muted { color: var(--muted); }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 8px;
        }

        th,
        td {
            padding: 10px 0;
            border-bottom: 1px solid var(--border);
            text-align: left;
        }

        th {
            color: var(--muted);
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
        }

        td.amount,
        th.amount { text-align: right; white-space: nowrap; }

        tr.total td {
            border-bottom: 0;
            color: var(--navy);
            font-size: 16px;
            font-weight: 700;
        }

        tr.due td { color: #b45309; font-weight: 600; }

        .notes {
            margin-top: 24px;
            padding: 16px 20px;
            border-left: 3px solid var(--gold);
            border-radius: 0 10px 10px 0;
            background: #fdf6e3;
            font-size: 13px;
        }

        .notes p { margin: 0 0 4px; }

        .foot {
            margin-top: 32px;
            padding-top: 16px;
            border-top: 1px solid var(--border);
            color: var(--muted);
            font-size: 12px;
            text-align: center;
        }

        @media (max-width: 640px) {
            .sheet { padding: 28px 20px; border-radius: 0; }
            .head { flex-direction: column; }
            .head-ref { text-align: left; }
            .grid { grid-template-columns: 1fr; }
            .dates { grid-template-columns: 1fr; text-align: left; }
            .date:last-child { text-align: left; }
            .nights { justify-self: start; }
        }

        @media print {
            @page { size: A4; margin: 14mm; }
            body { background: #fff; font-size: 12px; }
            .toolbar { display: none; }
            .sheet { max-width: none; margin: 0; padding: 0; box-shadow: none; }
            .dates, .notes, .badge, .nights { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        }
    </style>
</head>
<body>
    <div class="toolbar">
        <button type="button" class="primary" onclick="window.print()">Imprimer / Enregistrer en PDF</button>
        <a href="{{ route('admin.reservations.show', $reservation) }}">Retour à la réservation</a>
    </div>

    <main class="sheet">
        <header class="head">
            <img src="{{ asset('assets/images/logo/ds_holding_logo.png') }}" alt="DS HOLDING">
            <div class="head-ref">
                <span>Bon de réservation</span>
                <strong>{{ $reservation->reference }}</strong>
                <span class="badge {{ match ($reservation->statut->tone()) { 'warning' => 'is-warning', 'critical' => 'is-critical', default => '' } }}">{{ $reservation->statut->label() }}</span>
            </div>
        </header>

        <h1>{{ $property?->name }}</h1>
        <p class="lead">{{ collect([$property?->address, $property?->neighborhood, $property?->district, $property?->city?->name])->filter()->implode(', ') }}</p>

        <section class="dates">
            <div class="date">
                <span>Arrivée</span>
                <strong>{{ Str::ucfirst($reservation->check_in->translatedFormat('l d F Y')) }}</strong>
                @if ($hour($property?->check_in_from))
                    <small>À partir de {{ $hour($property->check_in_from) }}</small>
                @endif
            </div>
            <span class="nights">{{ $reservation->nights }} nuit{{ $reservation->nights > 1 ? 's' : '' }}</span>
            <div class="date">
                <span>Départ</span>
                <strong>{{ Str::ucfirst($reservation->check_out->translatedFormat('l d F Y')) }}</strong>
                @if ($hour($property?->check_out_until))
                    <small>Avant {{ $hour($property->check_out_until) }}</small>
                @endif
            </div>
        </section>

        <div class="grid">
            <section class="block">
                <h2>Voyageur</h2>
                <p><strong>{{ $reservation->guest_name }}</strong></p>
                <p>{{ $reservation->guest_email }}</p>
                <p>{{ $reservation->guest_phone }}</p>
                <p class="muted">{{ $reservation->adults }} adulte{{ $reservation->adults > 1 ? 's' : '' }}@if ($reservation->children > 0), {{ $reservation->children }} enfant{{ $reservation->children > 1 ? 's' : '' }}@endif</p>
                @foreach ($reservation->guests as $guest)
                    <p class="muted">{{ $guest->full_name }}{{ $guest->is_child ? ' (enfant)' : '' }}</p>
                @endforeach
            </section>
            <section class="block">
                <h2>Établissement</h2>
                <p><strong>{{ $property?->name }}</strong></p>
                @if ($property?->phone)
                    <p>{{ $property->phone }}</p>
                @endif
                @if ($property?->email)
                    <p>{{ $property->email }}</p>
                @endif
                <p class="muted">Annulation : {{ $reservation->cancellation_policy->label() }}</p>
            </section>
        </div>

        <table>
            <thead>
                <tr>
                    <th>Hébergement</th>
                    <th class="amount">Prix / nuit</th>
                    <th class="amount">Montant</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($reservation->items as $item)
                    <tr>
                        <td>{{ $item->quantity > 1 ? $item->quantity.' × ' : '' }}{{ $item->unit?->name }} <span class="muted">· {{ $item->unit?->unitType?->name }}</span></td>
                        <td class="amount">{{ $money($item->price_per_night) }}</td>
                        <td class="amount">{{ $money($item->subtotal) }}</td>
                    </tr>
                @endforeach
                @if ($reservation->cleaning_fee > 0)
                    <tr><td colspan="2">Frais de ménage</td><td class="amount">{{ $money($reservation->cleaning_fee) }}</td></tr>
                @endif
                @if ($reservation->service_fee > 0)
                    <tr><td colspan="2">Frais de service</td><td class="amount">{{ $money($reservation->service_fee) }}</td></tr>
                @endif
                @if ($reservation->tax_amount > 0)
                    <tr><td colspan="2">Taxes</td><td class="amount">{{ $money($reservation->tax_amount) }}</td></tr>
                @endif
                @if ($reservation->discount_amount > 0)
                    <tr><td colspan="2">Réduction</td><td class="amount">− {{ $money($reservation->discount_amount) }}</td></tr>
                @endif
                <tr class="total"><td colspan="2">Total</td><td class="amount">{{ $money($reservation->total_amount) }}</td></tr>
                <tr><td colspan="2">Déjà réglé</td><td class="amount">{{ $money($reservation->amount_paid) }}</td></tr>
                @if ($reservation->balanceDue() > 0)
                    <tr class="due"><td colspan="2">Reste à régler</td><td class="amount">{{ $money($reservation->balanceDue()) }}</td></tr>
                @endif
            </tbody>
        </table>

        @if ($reservation->special_requests || $property?->house_rules)
            <section class="notes">
                @if ($reservation->special_requests)
                    <p><strong>Demande du client :</strong> {{ $reservation->special_requests }}</p>
                @endif
                @if ($property?->house_rules)
                    <p><strong>Règlement intérieur :</strong> {{ $property->house_rules }}</p>
                @endif
            </section>
        @endif

        <footer class="foot">
            Bon émis le {{ now()->translatedFormat('d F Y à H:i') }} · Présentez ce bon (imprimé ou sur votre téléphone) à votre arrivée.
            <br>DS HOLDING · Réservation {{ $reservation->reference }}
        </footer>
    </main>
</body>
</html>
