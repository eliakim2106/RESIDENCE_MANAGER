@extends('layouts.admin')

@php
    $money = fn (int $amount): string => number_format($amount, 0, ',', ' ').' FCFA';
@endphp

@section('title', 'Reversements')

@section('content')
    <div class="admin-page-header">
        <div class="admin-page-heading">
            <span class="admin-page-icon"><i class="fa-solid fa-hand-holding-dollar"></i></span>
            <div>
                <h1>Reversements</h1>
                <p>La part de chaque propriétaire sur les paiements en ligne encaissés par DS Holding.</p>
            </div>
        </div>

        <div class="admin-page-actions">
            <a href="{{ route('admin.reversements.export', request()->only('search')) }}" class="btn-secondary btn-export" title="Exporter l’historique des reversements au format Excel">
                <i class="fa-solid fa-file-excel"></i>
                Exporter l’historique
            </a>
        </div>
    </div>

    @include('partials.flash')

    {{-- ========== Synthèse ========== --}}
    <div class="resa-today">
        <a href="{{ route('admin.reversements.index') }}" class="resa-today-card tone-good {{ $summary['available'] > 0 ? 'has-alert' : '' }}">
            <span class="resa-today-icon"><i class="fa-solid fa-hand-holding-dollar"></i></span>
            <span class="resa-today-text">
                <strong class="is-amount">{{ $money($summary['available']) }}</strong>
                <span>À reverser maintenant · {{ $counts['a-reverser'] }} propriétaire{{ $counts['a-reverser'] > 1 ? 's' : '' }}</span>
            </span>
        </a>
        <div class="resa-today-card tone-info">
            <span class="resa-today-icon"><i class="fa-solid fa-hourglass-half"></i></span>
            <span class="resa-today-text">
                <strong class="is-amount">{{ $money($summary['upcoming']) }}</strong>
                <span>À venir (séjours pas encore commencés)</span>
            </span>
        </div>
        <a href="{{ route('admin.reversements.index', ['statut' => 'historique']) }}" class="resa-today-card tone-neutral">
            <span class="resa-today-icon"><i class="fa-solid fa-clock-rotate-left"></i></span>
            <span class="resa-today-text">
                <strong class="is-amount">{{ $money($summary['paidThisMonth']) }}</strong>
                <span>Reversé ce mois-ci</span>
            </span>
        </a>
        <div class="resa-today-card tone-gold">
            <span class="resa-today-icon"><i class="fa-solid fa-percent"></i></span>
            <span class="resa-today-text">
                <strong class="is-amount">{{ $money($summary['commissionThisYear']) }}</strong>
                <span>Commissions perçues en {{ now()->year }}</span>
            </span>
        </div>
    </div>

    <div class="moderation-banner tone-info" role="note">
        <i class="fa-solid fa-circle-info"></i>
        <div>
            <strong>Comment le solde est calculé</strong>
            <p>
                Paiements en ligne encaissés − remboursements − commission de la formule du propriétaire − montants déjà reversés.
                Un montant devient disponible quand le séjour commence. Les paiements encaissés sur place par l’établissement ne sont pas concernés.
            </p>
        </div>
    </div>

    <section class="resa-filters">
        @include('admin.partials.list-toolbar', [
            'tabs' => $tabs,
            'counts' => $counts,
            'statut' => $tab,
            'search' => $search,
            'placeholder' => $tab === 'historique' ? 'N° de reversement, référence, propriétaire…' : 'Propriétaire, email, entreprise…',
        ])
    </section>

    @if ($tab === 'historique')
        {{-- ========== Historique des reversements ========== --}}
        <div class="table-card resa-table-card">
            <table class="custom-table resa-table">
                <thead>
                    <tr>
                        <th>Reversement</th>
                        <th>Propriétaire</th>
                        <th>Moyen</th>
                        <th class="text-end">Encaissé</th>
                        <th class="text-end">Commission</th>
                        <th class="text-end">Reversé</th>
                        <th>Statut</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($list as $payout)
                        <tr class="{{ $payout->isPaid() ? '' : 'is-muted-row' }}">
                            <td>
                                <span class="cell-stack">
                                    <strong>{{ $payout->number }}</strong>
                                    <small>{{ $payout->paid_at->format('d/m/Y') }}{{ $payout->recorder ? ' · par '.$payout->recorder->name : '' }}</small>
                                </span>
                            </td>
                            <td>
                                <span class="cell-stack">
                                    <a href="{{ route('admin.reversements.owner', $payout->user) }}" class="cell-title-link">{{ $payout->user->name }}</a>
                                    <small>{{ $payout->user->company_name ?: $payout->user->email }}</small>
                                </span>
                            </td>
                            <td>
                                <span class="cell-stack">
                                    <span class="text-nowrap"><i class="fa-solid {{ $payout->method->icon() }} cell-muted"></i> {{ $payout->method->label() }}</span>
                                    <small>{{ $payout->reference ? 'Réf. '.$payout->reference : '—' }}</small>
                                </span>
                            </td>
                            <td class="text-end text-nowrap">{{ $money($payout->gross_amount) }}</td>
                            <td class="text-end text-nowrap cell-muted">{{ $payout->commission_amount > 0 ? '− '.$money($payout->commission_amount) : '—' }}</td>
                            <td class="text-end text-nowrap"><strong class="cell-amount">{{ $money($payout->amount) }}</strong></td>
                            <td><span class="status-pill status-{{ $payout->statut->tone() }}">{{ $payout->statut->label() }}</span></td>
                            <td>
                                <span class="invoice-actions">
                                    <a href="{{ route('admin.reversements.statement', $payout) }}" class="action-btn" target="_blank" rel="noopener" title="Relevé" aria-label="Imprimer le relevé {{ $payout->number }}">
                                        <i class="fa-solid fa-print"></i>
                                    </a>
                                    @if ($payout->isPaid())
                                        <form method="POST" action="{{ route('admin.reversements.cancel', $payout) }}" class="inline-action"
                                            data-confirm="Annuler le reversement {{ $payout->number }} ? Les montants redeviendront à reverser.">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="action-btn delete" title="Annuler" aria-label="Annuler le reversement {{ $payout->number }}">
                                                <i class="fa-solid fa-xmark"></i>
                                            </button>
                                        </form>
                                    @endif
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="empty-table">
                                @include('admin.partials.empty-state', ['icon' => 'fa-clock-rotate-left', 'search' => $search])
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @include('admin.partials.pagination', ['paginator' => $list, 'label' => 'reversement(s)'])
    @else
        {{-- ========== Soldes des propriétaires ========== --}}
        <div class="table-card resa-table-card">
            <table class="custom-table resa-table">
                <thead>
                    <tr>
                        <th>Propriétaire</th>
                        <th>Coordonnées de reversement</th>
                        <th class="text-end">Disponible</th>
                        <th class="text-end">À venir</th>
                        <th>Dernier reversement</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($list as $owner)
                        @php
                            $last = $owner->payouts->first();
                        @endphp
                        <tr class="{{ $owner->balance['available'] > 0 ? 'is-pending' : '' }}">
                            <td class="cell-main">
                                <div class="cell-entity">
                                    <img src="{{ $owner->avatarUrl() }}" alt="" class="cell-avatar" loading="lazy">
                                    <span class="cell-entity-text guest-cell">
                                        <a href="{{ route('admin.reversements.owner', $owner) }}" class="cell-title-link"><strong>{{ $owner->name }}</strong></a>
                                        <small>{{ $owner->company_name ?: $owner->email }}</small>
                                    </span>
                                </div>
                            </td>
                            <td>
                                @if ($owner->payoutAccountSummary())
                                    <span class="cell-stack">
                                        <span><i class="fa-solid {{ $owner->payout_method->icon() }} cell-muted"></i> {{ $owner->payout_method->label() }}</span>
                                        <small>{{ $owner->payout_account }}</small>
                                    </span>
                                @else
                                    <span class="text-tone-warning"><i class="fa-solid fa-triangle-exclamation"></i> Non renseignées</span>
                                @endif
                            </td>
                            <td class="text-end text-nowrap">
                                <strong class="{{ $owner->balance['available'] > 0 ? 'cell-amount' : ($owner->balance['available'] < 0 ? 'text-tone-critical' : 'cell-muted') }}">{{ $money($owner->balance['available']) }}</strong>
                            </td>
                            <td class="text-end text-nowrap cell-muted">{{ $money($owner->balance['upcoming']) }}</td>
                            <td>
                                @if ($last)
                                    <span class="cell-stack">
                                        <span>{{ $money($last->amount) }}</span>
                                        <small>{{ $last->paid_at->format('d/m/Y') }}</small>
                                    </span>
                                @else
                                    <span class="cell-muted">Aucun</span>
                                @endif
                            </td>
                            <td>
                                <a href="{{ route('admin.reversements.owner', $owner) }}" class="action-btn {{ $owner->balance['available'] > 0 ? 'add-unit' : 'edit' }}"
                                    title="{{ $owner->balance['available'] > 0 ? 'Reverser' : 'Voir' }}" aria-label="Voir le solde de {{ $owner->name }}">
                                    <i class="fa-solid {{ $owner->balance['available'] > 0 ? 'fa-hand-holding-dollar' : 'fa-eye' }}"></i>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="empty-table">
                                @if ($tab === 'a-reverser' && $search === '')
                                    <div class="empty-state">
                                        <i class="fa-solid fa-circle-check"></i>
                                        <strong>Tout est à jour</strong>
                                        <span>Aucun propriétaire n’a de montant disponible à reverser.</span>
                                    </div>
                                @else
                                    @include('admin.partials.empty-state', ['icon' => 'fa-hand-holding-dollar', 'search' => $search])
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @include('admin.partials.pagination', ['paginator' => $list, 'label' => 'propriétaire(s)'])
    @endif
@endsection
