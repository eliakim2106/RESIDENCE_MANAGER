@php
    $owner = $invoice->user;
    $money = fn (int $amount): string => number_format($amount, 0, ',', ' ').' FCFA';
@endphp
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>Facture {{ $invoice->number }} · DS HOLDING</title>
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

        .head img { height: 60px; }

        .head-ref { text-align: right; }

        .head-ref h1 {
            margin: 0;
            color: var(--navy);
            font-size: 24px;
            letter-spacing: 0.06em;
            text-transform: uppercase;
        }

        .head-ref p { margin: 2px 0 0; color: var(--muted); }

        .badge {
            display: inline-block;
            margin-top: 8px;
            padding: 4px 12px;
            border-radius: 999px;
            background: #fef5e6;
            color: #b45309;
            font-size: 12px;
            font-weight: 600;
        }

        .badge.is-paid { background: #e8f7ee; color: #15803d; }
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
            padding: 12px 0;
            border-bottom: 1px solid var(--border);
            text-align: left;
        }

        th {
            color: var(--muted);
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
        }

        .amount { text-align: right; white-space: nowrap; }

        tr.total td {
            border-bottom: 0;
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
            .sheet { padding: 28px 20px; border-radius: 0; }
            .head { flex-direction: column; }
            .head-ref { text-align: left; }
            .parties { grid-template-columns: 1fr; }
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
                <h1>Facture</h1>
                <p>N° {{ $invoice->number }}</p>
                <p>Émise le {{ $invoice->created_at->format('d/m/Y') }}</p>
                <span class="badge {{ match ($invoice->statut->value) { 'paid' => 'is-paid', 'cancelled' => 'is-cancelled', default => '' } }}">{{ $invoice->statut->label() }}</span>
            </div>
        </header>

        <section class="parties">
            <div>
                <h2>Émetteur</h2>
                <p><strong>DS HOLDING</strong></p>
                <p>Plateforme de réservation de résidences</p>
            </div>
            <div>
                <h2>Facturé à</h2>
                <p><strong>{{ $owner->company_name ?: $owner->name }}</strong></p>
                @if ($owner->company_name)
                    <p>{{ $owner->name }}</p>
                @endif
                <p>{{ $owner->email }}</p>
                @if ($owner->phone)
                    <p>{{ $owner->formattedPhone() }}</p>
                @endif
            </div>
        </section>

        <table>
            <thead>
                <tr>
                    <th>Désignation</th>
                    <th>Période</th>
                    <th class="amount">Montant</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Abonnement « {{ $invoice->plan_name }} » · {{ Str::lower($invoice->billing_cycle->label()) }}</td>
                    <td>{{ $invoice->period_start->format('d/m/Y') }} → {{ $invoice->period_end->format('d/m/Y') }}</td>
                    <td class="amount">{{ $money($invoice->amount) }}</td>
                </tr>
                <tr class="total">
                    <td colspan="2">Total</td>
                    <td class="amount">{{ $money($invoice->amount) }}</td>
                </tr>
            </tbody>
        </table>

        <div class="dates">
            <span><strong>Échéance</strong>{{ $invoice->due_on->translatedFormat('d F Y') }}</span>
            @if ($invoice->paid_at)
                <span><strong>Payée le</strong>{{ $invoice->paid_at->translatedFormat('d F Y') }}</span>
                @if ($invoice->payment_method)
                    <span><strong>Moyen</strong>{{ $invoice->payment_method->label() }}</span>
                @endif
                @if ($invoice->payment_reference)
                    <span><strong>Référence</strong>{{ $invoice->payment_reference }}</span>
                @endif
            @endif
        </div>

        <footer class="foot">
            DS HOLDING · Facture {{ $invoice->number }}
        </footer>
    </main>
</body>
</html>
