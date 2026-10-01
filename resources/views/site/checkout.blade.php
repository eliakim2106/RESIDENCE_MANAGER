@extends('layouts.site')

@php
    $money = fn (int $amount): string => number_format($amount, 0, ',', ' ').' FCFA';
    $stay = array_filter(['arrivee' => $arrival->toDateString(), 'depart' => $departure->toDateString(), 'adultes' => $adults, 'enfants' => $children ?: null]);
    $selection = collect($lines)->mapWithKeys(fn ($line) => [$line['quote']['unit']->id => $line['quantity']]);
    $policy = $residence->cancellation_policy;
    $freeHours = $policy?->freeCancellationHours();
    $freeUntil = $freeHours !== null ? $arrival->setTimeFromTimeString($residence->check_in_from ?: '14:00')->subHours($freeHours) : null;
@endphp

@section('title', 'Finaliser ma réservation')

@section('content')
    @include('site.partials.navbar')

    <div class="rd-page">
        <div class="container">
            @include('partials.flash')

            <a href="{{ route('residences.show', [$residence, ...$stay]) }}#logements" class="rd-link co-back"><i class="fa-solid fa-arrow-left"></i> Modifier mon choix</a>

            <header class="co-header">
                <ol class="co-steps" aria-label="Étapes de la réservation">
                    <li class="is-done"><span><i class="fa-solid fa-check"></i></span> Logements</li>
                    <li class="is-current" aria-current="step"><span>2</span> Vos informations</li>
                    <li><span>3</span> Confirmation</li>
                </ol>
                <h1>Finaliser ma réservation</h1>
                <p class="rd-muted">Vérifiez votre séjour et indiquez qui sera le voyageur principal.</p>
            </header>

            <form method="POST" action="{{ route('residences.book', $residence) }}" class="rd-layout co-layout" novalidate>
                @csrf
                @foreach ($stay as $field => $fieldValue)
                    <input type="hidden" name="{{ $field }}" value="{{ $fieldValue }}">
                @endforeach
                @foreach ($selection as $unitId => $quantity)
                    <input type="hidden" name="unites[{{ $unitId }}]" value="{{ $quantity }}">
                @endforeach

                <div class="rd-main">
                    <section class="rd-card">
                        <h2>Voyageur principal</h2>
                        <p class="rd-muted co-intro">L’établissement utilisera ces coordonnées pour préparer votre arrivée.</p>

                        <div class="co-grid">
                            <div class="co-field">
                                <label for="nom">Nom complet</label>
                                <input type="text" id="nom" name="nom" value="{{ old('nom', $user->name) }}" autocomplete="name" required class="@error('nom') is-invalid @enderror">
                                @error('nom') <p class="co-error">{{ $message }}</p> @enderror
                            </div>

                            <div class="co-field">
                                <label for="email">Email</label>
                                <input type="email" id="email" name="email" value="{{ old('email', $user->email) }}" autocomplete="email" required class="@error('email') is-invalid @enderror">
                                @error('email') <p class="co-error">{{ $message }}</p> @enderror
                            </div>

                            <div class="co-field co-span">
                                <label for="telephone">Téléphone</label>
                                @include('partials.phone-field', ['phoneVariant' => 'auth', 'phoneValue' => $user->phone, 'phoneDial' => $user->indicatif_telephone, 'phoneRequired' => true])
                                @error('telephone') <p class="co-error">{{ $message }}</p> @enderror
                            </div>

                            <div class="co-field">
                                <label for="heure_arrivee">Heure d’arrivée prévue <small>(facultatif)</small></label>
                                <input type="time" id="heure_arrivee" name="heure_arrivee" value="{{ old('heure_arrivee') }}" class="@error('heure_arrivee') is-invalid @enderror">
                                <small class="co-hint">Arrivée possible dès {{ substr($residence->check_in_from ?: '14:00', 0, 5) }}{{ $residence->check_in_until ? ' et jusqu’à '.substr($residence->check_in_until, 0, 5) : '' }}.</small>
                                @error('heure_arrivee') <p class="co-error">{{ $message }}</p> @enderror
                            </div>

                            <div class="co-field co-span">
                                <label for="demandes">Demandes particulières <small>(facultatif)</small></label>
                                <textarea id="demandes" name="demandes" rows="4" maxlength="1000" placeholder="Lit bébé, arrivée tardive, transfert depuis l’aéroport…">{{ old('demandes') }}</textarea>
                                <small class="co-hint">Les demandes sont transmises à l’établissement, sans garantie qu’elles puissent être satisfaites.</small>
                            </div>
                        </div>
                    </section>

                    <section class="rd-card">
                        <h2>Conditions</h2>
                        <ul class="co-conditions">
                            <li><i class="fa-solid fa-hourglass-half"></i> Votre demande est envoyée à l’établissement, qui la confirme. Sans réponse sous {{ $site->booking('request_ttl_hours') }} h, elle expire automatiquement.</li>
                            <li><i class="fa-solid fa-credit-card"></i> Vous pouvez régler en ligne depuis votre espace dès l’envoi de la demande, ou attendre sa confirmation.</li>
                            @if ($freeUntil && $freeUntil->isFuture())
                                <li><i class="fa-solid fa-rotate-left"></i> Annulation gratuite jusqu’au {{ $freeUntil->translatedFormat('d F Y à H:i') }} : un paiement déjà effectué est alors remboursé.</li>
                            @elseif ($policy)
                                <li><i class="fa-solid fa-ban"></i> {{ $policy->label() }} : un paiement effectué ne sera pas remboursé en cas d’annulation.</li>
                            @endif
                        </ul>

                        <label class="co-check @error('conditions') is-invalid @enderror">
                            <input type="checkbox" name="conditions" value="1" @checked(old('conditions')) required>
                            <span>J’accepte les conditions de réservation et d’annulation de l’établissement ainsi que les <a href="{{ route('pages.conditions') }}" target="_blank">conditions d’utilisation</a>.</span>
                        </label>
                        @error('conditions') <p class="co-error">{{ $message }}</p> @enderror
                    </section>
                </div>

                <aside class="rd-sidebar">
                    <section class="rd-booking co-summary">
                        <div class="co-residence">
                            <img src="{{ $residence->coverImage?->url ?? asset('assets/images/home/residence-1.webp') }}" alt="">
                            <div>
                                <small>{{ $residence->propertyType?->name }} · {{ $residence->city?->name }}</small>
                                <strong>{{ $residence->name }}</strong>
                            </div>
                        </div>

                        <dl class="co-stay">
                            <div><dt>Arrivée</dt><dd>{{ $arrival->translatedFormat('D d M Y') }}</dd></div>
                            <div><dt>Départ</dt><dd>{{ $departure->translatedFormat('D d M Y') }}</dd></div>
                            <div><dt>Durée</dt><dd>{{ $totals['nights'] }} nuit{{ $totals['nights'] > 1 ? 's' : '' }}</dd></div>
                            <div><dt>Voyageurs</dt><dd>{{ $adults }} adulte{{ $adults > 1 ? 's' : '' }}{{ $children ? ', '.$children.' enfant'.($children > 1 ? 's' : '') : '' }}</dd></div>
                        </dl>

                        <div class="rd-summary-lines">
                            @foreach ($lines as $line)
                                <div>
                                    <span>{{ $line['quantity'] }} × {{ $line['quote']['unit']->name }}</span>
                                    <b>{{ $money($line['quote']['subtotal'] * $line['quantity']) }}</b>
                                </div>
                            @endforeach
                        </div>

                        <dl class="rd-summary-totals">
                            <div><dt>Hébergement</dt><dd>{{ $money($totals['subtotal']) }}</dd></div>
                            @if ($totals['cleaning'] > 0)
                                <div><dt>Ménage</dt><dd>{{ $money($totals['cleaning']) }}</dd></div>
                            @endif
                            @if ($totals['service'] > 0)
                                <div><dt>Frais de service ({{ rtrim(rtrim(number_format($serviceRate, 2, ',', ''), '0'), ',') }} %)</dt><dd>{{ $money($totals['service']) }}</dd></div>
                            @endif
                            <div class="is-total"><dt>Total</dt><dd>{{ $money($totals['total']) }}</dd></div>
                        </dl>

                        <button type="submit" class="rd-btn rd-btn-gold rd-btn-block co-submit">
                            <i class="fa-solid fa-paper-plane"></i> Envoyer ma demande
                        </button>
                        <p class="rd-small">Aucun paiement n’est prélevé à cette étape.</p>
                    </section>
                </aside>
            </form>
        </div>
    </div>

    @include('site.partials.footer')
@endsection
