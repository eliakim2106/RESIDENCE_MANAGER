@extends('layouts.admin')

@php
    use App\Enums\SubscriptionStatus;

    $money = fn (int $amount): string => number_format($amount, 0, ',', ' ').' FCFA';
    $plan = $subscription?->plan;
    $active = $subscription && $subscription->statut !== SubscriptionStatus::Cancelled;
    $openInvoice = $invoices->first(fn ($invoice) => $invoice->isUnpaid());
    $ratio = fn (int $used, ?int $max): int => $max ? min(100, (int) round($used / $max * 100)) : 0;
@endphp

@section('title', 'Mon abonnement')

@section('content')
    <div class="admin-page-header">
        <div class="admin-page-heading">
            <span class="admin-page-icon"><i class="fa-solid fa-id-card"></i></span>
            <div>
                <h1>Mon abonnement</h1>
                <p>Votre formule, vos factures et les limites de votre compte propriétaire.</p>
            </div>
        </div>
    </div>

    @include('partials.flash')

    {{-- ========== État de l'abonnement ========== --}}
    @if ($active)
        <section class="sub-hero tone-{{ $subscription->statut->tone() }}">
            <div class="sub-hero-main">
                <span class="status-pill status-{{ $subscription->statut->tone() }} status-pill-lg">{{ $subscription->statut->label() }}</span>
                <h2>Formule {{ $plan->name }}</h2>
                <p>
                    {{ $money($plan->priceFor($subscription->billing_cycle)) }} {{ $subscription->billing_cycle->per() }}
                    @if ($subscription->onTrial())
                        · essai gratuit jusqu’au <strong>{{ $subscription->trial_ends_at->translatedFormat('d F Y') }}</strong>
                        ({{ $subscription->trialDaysLeft() }} jour{{ $subscription->trialDaysLeft() > 1 ? 's' : '' }})
                    @elseif ($subscription->current_period_end)
                        · période du {{ $subscription->current_period_start->format('d/m/Y') }} au {{ $subscription->current_period_end->format('d/m/Y') }}
                    @endif
                </p>
            </div>

            <div class="sub-usage">
                <div class="usage-row">
                    <div class="linked-amount-line">
                        <span>Établissements</span>
                        <strong>{{ $usage['properties'] }} / {{ $plan->max_properties ?? '∞' }}</strong>
                    </div>
                    <span class="pay-progress pay-progress-lg"><span class="pay-progress-bar {{ $plan->max_properties && $usage['properties'] >= $plan->max_properties ? 'tone-warning' : 'tone-info' }}" style="width: {{ $ratio($usage['properties'], $plan->max_properties) }}%"></span></span>
                </div>
                <div class="usage-row">
                    <div class="linked-amount-line">
                        <span>Unités</span>
                        <strong>{{ $usage['units'] }} / {{ $plan->max_units ?? '∞' }}</strong>
                    </div>
                    <span class="pay-progress pay-progress-lg"><span class="pay-progress-bar {{ $plan->max_units && $usage['units'] >= $plan->max_units ? 'tone-warning' : 'tone-info' }}" style="width: {{ $ratio($usage['units'], $plan->max_units) }}%"></span></span>
                </div>
            </div>
        </section>
    @endif

    {{-- ========== Facture à régler ========== --}}
    @if ($openInvoice)
        <div class="moderation-banner {{ $openInvoice->isOverdue() || $subscription?->statut === SubscriptionStatus::Suspended ? 'tone-critical' : 'tone-warning' }}" role="status">
            <i class="fa-solid fa-file-invoice"></i>
            <div>
                <strong>Facture {{ $openInvoice->number }} : {{ $money($openInvoice->amount) }} à régler avant le {{ $openInvoice->due_on->translatedFormat('d F Y') }}</strong>
                <p>
                    @if ($subscription?->statut === SubscriptionStatus::Suspended)
                        Votre abonnement est suspendu : vos établissements ne sont plus visibles. Ils le redeviennent dès le paiement enregistré.
                    @else
                        @if (App\Services\Payments\CinetPay::enabled())
                            Payez-la en ligne (Mobile Money ou carte) : votre abonnement est mis à jour dès la confirmation du paiement.
                        @else
                            Réglez-la auprès de l’équipe DS Holding (Mobile Money, virement ou espèces) : votre paiement sera enregistré sur votre compte. Le paiement en ligne sera bientôt disponible.
                        @endif
                    @endif
                </p>
            </div>
            <a href="{{ route('admin.abonnement.invoice', $openInvoice) }}" class="btn-secondary btn-sm" target="_blank" rel="noopener">
                <i class="fa-solid fa-print"></i> Facture
            </a>
            @if (App\Services\Payments\CinetPay::enabled())
                <form method="POST" action="{{ route('admin.abonnement.pay-online', $openInvoice) }}" class="banner-pay">
                    @csrf
                    <button type="submit" class="btn-primary btn-sm"><i class="fa-solid fa-lock"></i> Payer en ligne</button>
                </form>
            @endif
        </div>
    @endif

    @if (! $active && $required)
        <div class="moderation-banner tone-critical" role="status">
            <i class="fa-solid fa-triangle-exclamation"></i>
            <div>
                <strong>Aucun abonnement actif</strong>
                <p>Choisissez une formule ci-dessous pour publier vos établissements et recevoir des réservations.</p>
            </div>
        </div>
    @endif

    {{-- ========== Formules ========== --}}
    <section class="plans-section">
        <div class="plans-section-head">
            <div>
                <h2>{{ $active ? 'Changer de formule' : 'Choisissez votre formule' }}</h2>
                <p>
                    @if ($active)
                        La nouvelle formule s’applique tout de suite et sera facturée à partir de votre prochaine période.
                    @elseif (! $hadTrial)
                        Profitez de l’essai gratuit prévu par la formule : vous ne payez rien pendant l’essai.
                    @else
                        Votre nouvel abonnement démarre dès la souscription.
                    @endif
                </p>
            </div>

            @if ($plans->contains(fn ($plan) => $plan->offersYearly()))
                <div class="cycle-toggle" role="radiogroup" aria-label="Facturation">
                    <label>
                        <input type="radio" name="cycle-choice" value="monthly" data-cycle-toggle @checked(! $active || $subscription->billing_cycle->value === 'monthly')>
                        <span>Mensuel</span>
                    </label>
                    <label>
                        <input type="radio" name="cycle-choice" value="yearly" data-cycle-toggle @checked($active && $subscription->billing_cycle->value === 'yearly')>
                        <span>Annuel <small>économisez</small></span>
                    </label>
                </div>
            @endif
        </div>

        @if ($plans->isEmpty())
            <div class="table-card">
                <div class="empty-state">
                    <i class="fa-solid fa-layer-group"></i>
                    <strong>Aucune formule disponible pour le moment</strong>
                    <span>L’équipe DS Holding prépare ses offres : vous serez prévenu dès leur ouverture.</span>
                </div>
            </div>
        @else
            <div class="plan-grid" data-plans data-cycle="{{ $active ? $subscription->billing_cycle->value : 'monthly' }}">
                @foreach ($plans as $option)
                    @php
                        $isCurrent = $active && $option->is($plan);
                    @endphp
                    <article class="plan-card {{ $option->is_featured ? 'is-featured' : '' }} {{ $isCurrent ? 'is-current' : '' }}">
                        @if ($isCurrent)
                            <span class="plan-ribbon is-current">Votre formule</span>
                        @elseif ($option->is_featured)
                            <span class="plan-ribbon">Recommandée</span>
                        @endif

                        <header class="plan-card-head">
                            <h2>{{ $option->name }}</h2>
                        </header>

                        @if ($option->description)
                            <p class="plan-card-desc">{{ $option->description }}</p>
                        @endif

                        <div class="plan-price" data-price-monthly>
                            @if ($option->monthly_price === 0)
                                <strong>Gratuit</strong>
                            @else
                                <strong>{{ $money($option->monthly_price) }}</strong>
                                <span>/ mois</span>
                            @endif
                        </div>
                        <div class="plan-price" data-price-yearly>
                            <strong>{{ $money($option->priceFor(App\Enums\BillingCycle::Yearly)) }}</strong>
                            <span>/ an</span>
                        </div>
                        <p class="plan-yearly" data-price-yearly>
                            @if ($option->yearlySavingMonths() > 0)
                                <span class="plan-saving">{{ $option->yearlySavingMonths() }} mois offerts</span>
                            @elseif (! $option->offersYearly())
                                Facturation mensuelle uniquement
                            @endif
                        </p>

                        <ul class="plan-features">
                            <li><i class="fa-solid fa-building"></i> {{ $option->max_properties === null ? 'Établissements illimités' : $option->max_properties.' établissement'.($option->max_properties > 1 ? 's' : '') }}</li>
                            <li><i class="fa-solid fa-door-open"></i> {{ $option->max_units === null ? 'Unités illimitées' : $option->max_units.' unité'.($option->max_units > 1 ? 's' : '') }}</li>
                            @if ($option->trial_days > 0 && ! $hadTrial)
                                <li><i class="fa-solid fa-gift"></i> {{ $option->trial_days }} jours d’essai gratuit</li>
                            @endif
                            @if ((float) $option->commission_rate > 0)
                                <li><i class="fa-solid fa-percent"></i> Commission de {{ rtrim(rtrim(number_format((float) $option->commission_rate, 2, ',', ' '), '0'), ',') }} % sur les réservations</li>
                            @endif
                            @foreach ($option->features ?? [] as $feature)
                                <li><i class="fa-solid fa-check"></i> {{ $feature }}</li>
                            @endforeach
                        </ul>

                        <footer class="plan-card-foot">
                            @if ($isCurrent)
                                <span class="plan-current-note"><i class="fa-solid fa-circle-check"></i> Formule actuelle</span>
                            @else
                                <form method="POST" action="{{ route('admin.abonnement.subscribe') }}" class="plan-choose"
                                    data-confirm="{{ $active ? 'Passer à la formule « '.$option->name.' » ?' : 'Souscrire la formule « '.$option->name.' » ?' }}">
                                    @csrf
                                    <input type="hidden" name="formule" value="{{ $option->id }}">
                                    <input type="hidden" name="cycle" value="{{ $active ? $subscription->billing_cycle->value : 'monthly' }}" data-cycle-input>
                                    <button type="submit" class="{{ $option->is_featured ? 'btn-primary' : 'btn-secondary' }}">
                                        {{ $active ? 'Choisir cette formule' : (! $hadTrial && $option->trial_days > 0 ? 'Commencer l’essai gratuit' : 'Souscrire') }}
                                    </button>
                                </form>
                            @endif
                        </footer>
                    </article>
                @endforeach
            </div>
        @endif
    </section>

    {{-- ========== Factures ========== --}}
    @if ($invoices->isNotEmpty())
        <section class="dash-card">
            <div class="dash-card-header">
                <div>
                    <h2>Mes factures</h2>
                    <p>{{ $invoices->count() }} facture{{ $invoices->count() > 1 ? 's' : '' }}</p>
                </div>
            </div>

            <ul class="invoice-list">
                @foreach ($invoices as $invoice)
                    <li class="{{ $invoice->isOverdue() ? 'is-overdue' : '' }}">
                        <span class="cell-icon"><i class="fa-solid fa-file-invoice"></i></span>
                        <span class="resa-unit-text">
                            <strong>{{ $invoice->number }} · {{ $money($invoice->amount) }}</strong>
                            <small>
                                {{ $invoice->plan_name }} · {{ $invoice->period_start->format('d/m/Y') }} → {{ $invoice->period_end->format('d/m/Y') }}
                                @if ($invoice->paid_at)
                                    · payée le {{ $invoice->paid_at->format('d/m/Y') }}
                                @elseif ($invoice->isUnpaid())
                                    · à régler avant le {{ $invoice->due_on->format('d/m/Y') }}
                                @endif
                            </small>
                        </span>
                        <span class="invoice-actions">
                            <span class="status-pill status-{{ $invoice->statut->tone() }}">{{ $invoice->isOverdue() ? 'Échue' : $invoice->statut->label() }}</span>
                            <a href="{{ route('admin.abonnement.invoice', $invoice) }}" class="action-btn" target="_blank" rel="noopener" title="Imprimer" aria-label="Imprimer la facture {{ $invoice->number }}">
                                <i class="fa-solid fa-print"></i>
                            </a>
                            @if ($invoice->isUnpaid() && App\Services\Payments\CinetPay::enabled())
                                <form method="POST" action="{{ route('admin.abonnement.pay-online', $invoice) }}" class="inline-action">
                                    @csrf
                                    <button type="submit" class="btn-primary btn-sm"><i class="fa-solid fa-lock"></i> Payer</button>
                                </form>
                            @endif
                        </span>
                    </li>
                @endforeach
            </ul>
        </section>
    @endif
@endsection
