@extends('layouts.admin')

@php
    use App\Enums\UserRole;

    $money = fn (int $amount): string => number_format($amount, 0, ',', ' ').' FCFA';
    $ownersWithout = App\Models\User::query()->where('role', UserRole::Owner)->whereDoesntHave('subscriptions')->orderBy('name')->get(['id', 'name', 'email']);
    $plans = App\Models\SubscriptionPlan::active()->ordered()->get();
@endphp

@section('title', 'Abonnements')

@section('content')
    <div class="admin-page-header">
        <div class="admin-page-heading">
            <span class="admin-page-icon"><i class="fa-solid fa-id-card"></i></span>
            <div>
                <h1>Abonnements</h1>
                <p>L’abonnement de chaque propriétaire, ses factures et ses paiements.</p>
            </div>
        </div>

        <div class="admin-page-actions">
            <a href="{{ route('admin.formules.index') }}" class="btn-secondary">
                <i class="fa-solid fa-layer-group"></i>
                Formules
            </a>
            @if ($ownersWithout->isNotEmpty() && $plans->isNotEmpty())
                <button type="button" class="btn-primary" data-modal-open="subscribeModal">
                    <i class="fa-solid fa-plus"></i>
                    Abonner un propriétaire
                </button>
            @endif
        </div>
    </div>

    @include('partials.flash')

    {{-- ========== Synthèse ========== --}}
    <div class="resa-today">
        <div class="resa-today-card tone-good">
            <span class="resa-today-icon"><i class="fa-solid fa-arrows-rotate"></i></span>
            <span class="resa-today-text">
                <strong class="is-amount">{{ $money($summary['recurring']) }}</strong>
                <span>Revenu mensuel récurrent</span>
            </span>
        </div>
        <a href="{{ route('admin.abonnements.index', ['statut' => 'essai']) }}" class="resa-today-card tone-info">
            <span class="resa-today-icon"><i class="fa-solid fa-gift"></i></span>
            <span class="resa-today-text">
                <strong>{{ $counts['essai'] }}</strong>
                <span>En essai gratuit</span>
            </span>
        </a>
        <a href="{{ route('admin.abonnements.index', ['statut' => 'en-retard']) }}" class="resa-today-card tone-warning {{ $summary['unpaidCount'] > 0 ? 'has-alert' : '' }}">
            <span class="resa-today-icon"><i class="fa-solid fa-file-invoice"></i></span>
            <span class="resa-today-text">
                <strong class="is-amount">{{ $money($summary['unpaidAmount']) }}</strong>
                <span>{{ $summary['unpaidCount'] }} facture{{ $summary['unpaidCount'] > 1 ? 's' : '' }} à encaisser</span>
            </span>
        </a>
        <div class="resa-today-card tone-gold">
            <span class="resa-today-icon"><i class="fa-solid fa-user-clock"></i></span>
            <span class="resa-today-text">
                <strong>{{ $summary['withoutSubscription'] }}</strong>
                <span>Propriétaire{{ $summary['withoutSubscription'] > 1 ? 's' : '' }} sans abonnement</span>
            </span>
        </div>
    </div>

    @unless ($required)
        <div class="moderation-banner tone-warning" role="status">
            <i class="fa-solid fa-circle-info"></i>
            <div>
                <strong>Abonnement non obligatoire pour le moment</strong>
                <p>Les établissements restent en ligne même sans abonnement. Activez l’obligation depuis <a href="{{ route('admin.formules.index') }}">Formules</a> quand vous serez prêt.</p>
            </div>
        </div>
    @endunless

    <section class="resa-filters">
        @include('admin.partials.list-toolbar', [
            'tabs' => $tabs,
            'counts' => $counts,
            'statut' => $tab,
            'search' => $search,
            'placeholder' => 'Propriétaire, email, entreprise…',
        ])
    </section>

    <div class="table-card resa-table-card">
        <table class="custom-table resa-table">
            <thead>
                <tr>
                    <th>Propriétaire</th>
                    <th>Formule</th>
                    <th>Période</th>
                    <th>Facture en attente</th>
                    <th>Statut</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($subscriptions as $subscription)
                    @php
                        $owner = $subscription->user;
                        $invoice = $subscription->openInvoice;
                    @endphp
                    <tr class="{{ $invoice?->isOverdue() ? 'is-pending' : '' }}">
                        <td class="cell-main">
                            <div class="cell-entity">
                                <img src="{{ $owner->avatarUrl() }}" alt="" class="cell-avatar" loading="lazy">
                                <span class="cell-entity-text guest-cell">
                                    <a href="{{ route('admin.abonnements.show', $subscription) }}" class="cell-title-link"><strong>{{ $owner->name }}</strong></a>
                                    <small>{{ $owner->company_name ?: $owner->email }}</small>
                                </span>
                            </div>
                        </td>
                        <td>
                            <span class="cell-stack">
                                <span>{{ $subscription->plan->name }}</span>
                                <small>{{ $money($subscription->plan->priceFor($subscription->billing_cycle)) }} {{ $subscription->billing_cycle->per() }}</small>
                            </span>
                        </td>
                        <td class="text-nowrap">
                            @if ($subscription->onTrial())
                                <span class="cell-stack">
                                    <span>Jusqu’au {{ $subscription->trial_ends_at->format('d/m/Y') }}</span>
                                    <small>{{ $subscription->trialDaysLeft() }} jour{{ $subscription->trialDaysLeft() > 1 ? 's' : '' }} d’essai restant{{ $subscription->trialDaysLeft() > 1 ? 's' : '' }}</small>
                                </span>
                            @elseif ($subscription->current_period_end)
                                <span class="cell-stack">
                                    <span>{{ $subscription->current_period_start->format('d/m') }} → {{ $subscription->current_period_end->format('d/m/Y') }}</span>
                                    <small>{{ $subscription->billing_cycle->label() }}</small>
                                </span>
                            @else
                                <span class="cell-muted">—</span>
                            @endif
                        </td>
                        <td>
                            @if ($invoice)
                                <span class="cell-stack">
                                    <span class="cell-amount">{{ $money($invoice->amount) }}</span>
                                    <small class="{{ $invoice->isOverdue() ? 'text-tone-critical' : 'text-tone-warning' }}">
                                        {{ $invoice->isOverdue() ? 'Échue le' : 'Échéance le' }} {{ $invoice->due_on->format('d/m/Y') }}
                                    </small>
                                </span>
                            @else
                                <span class="cell-muted">Aucune</span>
                            @endif
                        </td>
                        <td><span class="status-pill status-{{ $subscription->statut->tone() }}">{{ $subscription->statut->label() }}</span></td>
                        <td>
                            <a href="{{ route('admin.abonnements.show', $subscription) }}" class="action-btn edit" title="Voir" aria-label="Voir l’abonnement de {{ $owner->name }}">
                                <i class="fa-solid fa-eye"></i>
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="empty-table">
                            @include('admin.partials.empty-state', ['icon' => 'fa-id-card', 'search' => $search])
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @include('admin.partials.pagination', ['paginator' => $subscriptions, 'label' => 'abonnement(s)'])

    {{-- ========== Abonner un propriétaire ========== --}}
    @if ($ownersWithout->isNotEmpty() && $plans->isNotEmpty())
        <div class="modal-overlay" id="subscribeModal" data-action-modal>
            <div class="modal-card modal-form" role="dialog" aria-modal="true" aria-labelledby="subscribeModalTitle">
                <div class="modal-icon modal-icon-info"><i class="fa-solid fa-id-card"></i></div>
                <h3 id="subscribeModalTitle">Abonner un propriétaire</h3>
                <p>Un propriétaire sans abonnement bénéficie de l’essai gratuit prévu par la formule.</p>

                <form method="POST" action="{{ route('admin.abonnements.store') }}">
                    @csrf
                    <div class="form-group">
                        <label for="proprietaire">Propriétaire</label>
                        <select name="proprietaire" id="proprietaire" required>
                            @foreach ($ownersWithout as $owner)
                                <option value="{{ $owner->id }}">{{ $owner->name }} · {{ $owner->email }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="formule">Formule</label>
                        <select name="formule" id="formule" required>
                            @foreach ($plans as $plan)
                                <option value="{{ $plan->id }}">{{ $plan->name }} · {{ $money($plan->monthly_price) }} / mois{{ $plan->trial_days > 0 ? ' · '.$plan->trial_days.' j d’essai' : '' }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="cycle">Facturation</label>
                        <select name="cycle" id="cycle">
                            @foreach (App\Enums\BillingCycle::options() as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="modal-actions">
                        <button type="button" class="btn-cancel" data-modal-close>Retour</button>
                        <button type="submit" class="btn-save">Abonner</button>
                    </div>
                </form>
            </div>
        </div>
    @endif
@endsection
