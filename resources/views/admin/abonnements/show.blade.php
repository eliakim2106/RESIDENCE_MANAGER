@extends('layouts.admin')

@php
    use App\Enums\SubscriptionStatus;

    $money = fn (int $amount): string => number_format($amount, 0, ',', ' ').' FCFA';
    $plan = $subscription->plan;
    $ratio = fn (int $used, ?int $max): int => $max ? min(100, (int) round($used / $max * 100)) : 0;
@endphp

@section('title', 'Abonnement de '.$owner->name)

@section('content')
    <header class="resa-header">
        <a href="{{ route('admin.abonnements.index') }}" class="resa-back">
            <i class="fa-solid fa-arrow-left"></i>
            Abonnements
        </a>

        <div class="resa-header-main">
            <div class="cell-entity">
                <img src="{{ $owner->avatarUrl() }}" alt="" class="profile-hero-avatar">
                <div class="resa-header-title">
                    <div class="resa-header-line">
                        <h1 class="plain-title">{{ $owner->name }}</h1>
                        <span class="status-pill status-{{ $subscription->statut->tone() }} status-pill-lg">{{ $subscription->statut->label() }}</span>
                    </div>
                    <p>
                        Formule <strong>{{ $plan->name }}</strong> · {{ $subscription->billing_cycle->label() }}
                        <span class="dot-sep">·</span>
                        <a href="{{ route('admin.utilisateurs.show', $owner) }}">Voir le compte</a>
                    </p>
                </div>
            </div>

            @if ($subscription->statut !== SubscriptionStatus::Cancelled)
                <div class="resa-actionbar">
                    @if ($subscription->onTrial())
                        <button type="button" class="btn-secondary" data-modal-open="extendModal">
                            <i class="fa-solid fa-gift"></i>
                            Prolonger l’essai
                        </button>
                    @endif
                    <button type="button" class="btn-secondary" data-modal-open="planModal">
                        <i class="fa-solid fa-layer-group"></i>
                        Changer de formule
                    </button>
                    <form method="POST" action="{{ route('admin.abonnements.cancel', $subscription) }}"
                        data-confirm="Résilier l’abonnement de {{ $owner->name }} ? Ses factures à payer seront annulées.">
                        @csrf
                        @method('PATCH')
                        <button type="submit" class="btn-outline-danger"><i class="fa-solid fa-ban"></i> Résilier</button>
                    </form>
                </div>
            @endif
        </div>
    </header>

    @include('partials.flash')

    <div class="resa-layout">
        <div class="resa-main">

            {{-- Factures --}}
            <section class="dash-card">
                <div class="dash-card-header">
                    <div>
                        <h2>Factures</h2>
                        <p>{{ $subscription->invoices->count() }} facture{{ $subscription->invoices->count() > 1 ? 's' : '' }}</p>
                    </div>
                </div>

                @if ($subscription->invoices->isEmpty())
                    <p class="resa-note">
                        {{ $subscription->onTrial() ? 'La première facture sera émise à la fin de l’essai, le '.$subscription->trial_ends_at->translatedFormat('d F Y').'.' : 'Aucune facture pour le moment.' }}
                    </p>
                @else
                    <ul class="invoice-list">
                        @foreach ($subscription->invoices as $invoice)
                            <li class="{{ $invoice->isOverdue() ? 'is-overdue' : '' }}">
                                <span class="cell-icon"><i class="fa-solid fa-file-invoice"></i></span>
                                <span class="resa-unit-text">
                                    <strong>{{ $invoice->number }} · {{ $money($invoice->amount) }}</strong>
                                    <small>
                                        {{ $invoice->period_start->format('d/m/Y') }} → {{ $invoice->period_end->format('d/m/Y') }}
                                        @if ($invoice->isUnpaid())
                                            · {{ $invoice->isOverdue() ? 'échue le' : 'échéance le' }} {{ $invoice->due_on->format('d/m/Y') }}
                                        @elseif ($invoice->paid_at)
                                            · payée le {{ $invoice->paid_at->format('d/m/Y') }}{{ $invoice->payment_method ? ' ('.$invoice->payment_method->label().')' : '' }}{{ $invoice->recorder ? ', saisie par '.$invoice->recorder->name : '' }}
                                        @endif
                                    </small>
                                </span>
                                <span class="invoice-actions">
                                    <span class="status-pill status-{{ $invoice->statut->tone() }}">{{ $invoice->isOverdue() ? 'Échue' : $invoice->statut->label() }}</span>
                                    <a href="{{ route('admin.abonnement.invoice', $invoice) }}" class="action-btn" target="_blank" rel="noopener" title="Imprimer" aria-label="Imprimer la facture {{ $invoice->number }}">
                                        <i class="fa-solid fa-print"></i>
                                    </a>
                                    @if ($invoice->isUnpaid())
                                        <button type="button" class="action-btn add-unit" title="Marquer comme payée" aria-label="Marquer la facture {{ $invoice->number }} comme payée"
                                            data-modal-open="payModal" data-form-action="{{ route('admin.abonnements.invoices.pay', $invoice) }}" data-name="{{ $invoice->number }} · {{ $money($invoice->amount) }}">
                                            <i class="fa-solid fa-check"></i>
                                        </button>
                                        <form method="POST" action="{{ route('admin.abonnements.invoices.cancel', $invoice) }}" class="inline-action"
                                            data-confirm="Annuler la facture {{ $invoice->number }} ?">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="action-btn delete" title="Annuler la facture" aria-label="Annuler la facture {{ $invoice->number }}">
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
        </div>

        <aside class="resa-aside">
            <section class="dash-card">
                <div class="dash-card-header">
                    <h2>Formule</h2>
                </div>

                <div class="amount-summary">
                    <strong>{{ $money($plan->priceFor($subscription->billing_cycle)) }}</strong>
                    <small>{{ $plan->name }} · {{ Str::lower($subscription->billing_cycle->label()) }}</small>
                </div>

                <dl class="resa-amounts">
                    @if ($subscription->onTrial())
                        <div>
                            <dt>Fin de l’essai</dt>
                            <dd>{{ $subscription->trial_ends_at->format('d/m/Y') }}</dd>
                        </div>
                    @endif
                    @if ($subscription->current_period_end)
                        <div>
                            <dt>Période en cours</dt>
                            <dd>{{ $subscription->current_period_start->format('d/m') }} → {{ $subscription->current_period_end->format('d/m/Y') }}</dd>
                        </div>
                    @endif
                    <div>
                        <dt>Abonné depuis</dt>
                        <dd>{{ $subscription->created_at->format('d/m/Y') }}</dd>
                    </div>
                    @if ((float) $plan->commission_rate > 0)
                        <div>
                            <dt>Commission</dt>
                            <dd>{{ rtrim(rtrim(number_format((float) $plan->commission_rate, 2, ',', ' '), '0'), ',') }} %</dd>
                        </div>
                    @endif
                </dl>
            </section>

            <section class="dash-card">
                <div class="dash-card-header">
                    <h2>Utilisation</h2>
                </div>

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
            </section>
        </aside>
    </div>

    {{-- ========== Modales ========== --}}
    <div class="modal-overlay" id="payModal" data-action-modal>
        <div class="modal-card modal-form" role="dialog" aria-modal="true" aria-labelledby="payModalTitle">
            <div class="modal-icon modal-icon-info"><i class="fa-solid fa-check"></i></div>
            <h3 id="payModalTitle">Paiement reçu</h3>
            <p>Facture <strong data-modal-name></strong>. L’abonnement redevient actif s’il ne reste rien à payer.</p>

            <form method="POST" action="">
                @csrf
                @method('PATCH')
                <div class="form-group">
                    <label for="moyen">Moyen de paiement</label>
                    <select name="moyen" id="moyen" required>
                        @foreach ($paymentMethods as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label for="reference">Référence <span class="field-optional">(facultatif)</span></label>
                    <input type="text" name="reference" id="reference" maxlength="100" placeholder="ID de transaction Mobile Money, n° de reçu…">
                </div>
                <div class="modal-actions">
                    <button type="button" class="btn-cancel" data-modal-close>Retour</button>
                    <button type="submit" class="btn-save">Enregistrer</button>
                </div>
            </form>
        </div>
    </div>

    <div class="modal-overlay" id="planModal" data-action-modal>
        <div class="modal-card modal-form" role="dialog" aria-modal="true" aria-labelledby="planModalTitle">
            <div class="modal-icon modal-icon-info"><i class="fa-solid fa-layer-group"></i></div>
            <h3 id="planModalTitle">Changer de formule</h3>
            <p>La nouvelle formule s’applique tout de suite ; elle est facturée à partir de la prochaine période.</p>

            <form method="POST" action="{{ route('admin.abonnements.plan', $subscription) }}">
                @csrf
                @method('PATCH')
                <div class="form-group">
                    <label for="formule">Formule</label>
                    <select name="formule" id="formule">
                        @foreach ($plans as $option)
                            <option value="{{ $option->id }}" @selected($option->is($plan))>{{ $option->name }} · {{ $money($option->monthly_price) }} / mois</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label for="cycle">Facturation</label>
                    <select name="cycle" id="cycle">
                        @foreach (App\Enums\BillingCycle::options() as $value => $label)
                            <option value="{{ $value }}" @selected($subscription->billing_cycle->value === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="modal-actions">
                    <button type="button" class="btn-cancel" data-modal-close>Retour</button>
                    <button type="submit" class="btn-save">Enregistrer</button>
                </div>
            </form>
        </div>
    </div>

    @if ($subscription->onTrial())
        <div class="modal-overlay" id="extendModal" data-action-modal>
            <div class="modal-card modal-form" role="dialog" aria-modal="true" aria-labelledby="extendModalTitle">
                <div class="modal-icon modal-icon-info"><i class="fa-solid fa-gift"></i></div>
                <h3 id="extendModalTitle">Prolonger l’essai</h3>
                <p>Fin actuelle : {{ $subscription->trial_ends_at->translatedFormat('d F Y') }}.</p>

                <form method="POST" action="{{ route('admin.abonnements.extend', $subscription) }}">
                    @csrf
                    @method('PATCH')
                    <div class="form-group">
                        <label for="jours">Jours supplémentaires</label>
                        <div class="input-affix">
                            <input type="number" name="jours" id="jours" min="1" max="365" value="15" required>
                            <span>jours</span>
                        </div>
                    </div>
                    <div class="modal-actions">
                        <button type="button" class="btn-cancel" data-modal-close>Retour</button>
                        <button type="submit" class="btn-save">Prolonger</button>
                    </div>
                </form>
            </div>
        </div>
    @endif
@endsection
