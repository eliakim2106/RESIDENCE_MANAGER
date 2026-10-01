@php
    use App\Enums\PropertyStatus;

    $current = $etablissement->statut ?? PropertyStatus::Draft;
    $online = in_array($current, [PropertyStatus::Published, PropertyStatus::Pending, PropertyStatus::Suspended], true);
    $status = old('statut', $etablissement->exists ? ($online ? 'actif' : 'inactif') : 'actif');

    // Un administrateur publie directement ; un propriétaire soumet à validation
    if (auth()->user()->isAdmin()) {
        $options = [
            'actif' => ['Actif', "L'établissement sera immédiatement visible et pourra recevoir des réservations.", "L'établissement sera publié immédiatement après son enregistrement."],
            'inactif' => ['Inactif', "L'établissement sera enregistré mais restera invisible jusqu'à son activation.", "L'établissement sera enregistré mais restera masqué jusqu'à son activation."],
        ];
        $intro = 'Choisissez si cet établissement est immédiatement visible sur la plateforme.';
    } else {
        $options = [
            'actif' => match ($current) {
                PropertyStatus::Published => ['En ligne', "L'établissement reste visible et continue de recevoir des réservations.", "L'établissement reste en ligne ; vos modifications sont visibles dès l'enregistrement."],
                PropertyStatus::Pending => ['En attente de validation', 'Votre demande est en cours d’examen par un administrateur.', 'Votre demande de publication reste en cours d’examen.'],
                PropertyStatus::Suspended => ['Suspendu', 'Seul un administrateur peut rétablir la publication.', 'L’établissement reste suspendu : contactez l’administration pour le rétablir.'],
                default => ['Soumettre pour validation', 'Un administrateur vérifiera votre établissement avant sa mise en ligne.', "L'établissement sera envoyé pour validation et publié dès son approbation."],
            },
            'inactif' => ['Brouillon', "L'établissement est enregistré sans être visible ni envoyé pour validation.", "L'établissement sera enregistré comme brouillon, invisible sur le site."],
        ];
        $intro = 'Tout nouvel établissement est vérifié par un administrateur avant d’être publié.';
    }
@endphp

<div
    class="carte-formulaire etape-contenu"
    data-step="5">

    <!-- ==========================================
        HEADER
    =========================================== -->

    <div class="carte-header">

        <div class="carte-titre">

            <div class="carte-icone">

                <i class="fa-solid fa-globe"></i>

            </div>

            <div>

                <h3>

                    Publication

                </h3>

                <p>

                    {{ $intro }}

                </p>

            </div>

        </div>

        <span class="badge-etape">

            Étape 5 / 6

        </span>

    </div>

    <!-- ==========================================
        CONTENU
    =========================================== -->

    <div class="carte-body">

        <div class="publication-section">

            <h4>

                <i class="fa-solid fa-eye"></i>

                Statut de l'établissement

            </h4>

            <div class="publication-grid">

                <!-- ACTIF -->

                <label class="publication-card">

                    <input
                        type="radio"
                        name="statut"
                        value="actif"
                        data-resume="{{ $options['actif'][2] }}"
                        @checked($status === 'actif')>

                    <div class="publication-content">

                        <div class="publication-icon actif">

                            <i class="fa-solid fa-circle-check"></i>

                        </div>

                        <div>

                            <h5>

                                {{ $options['actif'][0] }}

                            </h5>

                            <p>

                                {{ $options['actif'][1] }}

                            </p>

                        </div>

                    </div>

                </label>

                <!-- INACTIF -->

                <label class="publication-card">

                    <input
                        type="radio"
                        name="statut"
                        value="inactif"
                        data-resume="{{ $options['inactif'][2] }}"
                        @checked($status === 'inactif')>

                    <div class="publication-content">

                        <div class="publication-icon inactif">

                            <i class="fa-solid fa-eye-slash"></i>

                        </div>

                        <div>

                            <h5>

                                {{ $options['inactif'][0] }}

                            </h5>

                            <p>

                                {{ $options['inactif'][1] }}

                            </p>

                        </div>

                    </div>

                </label>

            </div>

        </div>

        <!-- ==========================================
            AVANT LA MISE EN LIGNE
        =========================================== -->

        @php
            $owner = $etablissement->owner ?? auth()->user();
            $subscription = $owner->currentSubscription;
            $plan = $subscription?->plan;
            $activeUnits = $etablissement->exists ? $etablissement->units()->where('statut', App\Enums\ActiveStatus::Active)->count() : 0;
            $photos = $etablissement->exists ? $etablissement->images->count() : 0;
            $subscriptionRequired = App\Services\SubscriptionManager::required();
            $isSelf = $owner->is(auth()->user());

            $checks = [
                [
                    'ok' => $photos > 0,
                    'icon' => 'fa-images',
                    'title' => $photos > 0 ? $photos.' photo'.($photos > 1 ? 's' : '').' dans la galerie' : 'Photos',
                    'text' => $photos > 0 ? 'La photo de couverture apparaît sur le site.' : 'Ajoutées à l’étape Médias.',
                    'link' => null,
                ],
                [
                    'ok' => $activeUnits > 0,
                    'icon' => 'fa-door-open',
                    'title' => $activeUnits > 0 ? $activeUnits.' unité'.($activeUnits > 1 ? 's' : '').' active'.($activeUnits > 1 ? 's' : '') : 'Aucune unité pour le moment',
                    'text' => $activeUnits > 0 ? 'Les clients peuvent réserver ces unités.' : 'Ajoutez vos chambres ou logements après l’enregistrement : sans unité, rien n’est réservable.',
                    'link' => $etablissement->exists && $activeUnits === 0 ? [route('admin.etablissements.unites.create', $etablissement), 'Ajouter une unité'] : null,
                ],
                [
                    'ok' => ! $subscriptionRequired || ($subscription && $subscription->statut->isInGoodStanding()),
                    'warn' => $subscriptionRequired && ! ($subscription && $subscription->statut->isInGoodStanding()),
                    'icon' => 'fa-id-card',
                    'title' => $plan ? 'Formule '.$plan->name.' · '.$subscription->statut->label() : 'Sans abonnement',
                    'text' => match (true) {
                        $subscriptionRequired && ! ($subscription && $subscription->statut->isInGoodStanding()) => 'L’abonnement est obligatoire : sans abonnement en règle, l’établissement ne sera pas visible sur le site.',
                        $plan !== null => 'Commission sur les réservations : '.rtrim(rtrim(number_format((float) $plan->commission_rate, 2, ',', ' '), '0'), ',').' %.',
                        default => 'L’abonnement n’est pas encore obligatoire.',
                    },
                    'link' => $isSelf && auth()->user()->isOwner() ? [route('admin.abonnement.show'), $plan ? 'Mon abonnement' : 'Choisir une formule'] : ($subscription ? [route('admin.abonnements.show', $subscription), 'Voir l’abonnement'] : null),
                ],
                [
                    'ok' => filled($owner->payoutAccountSummary()),
                    'icon' => 'fa-hand-holding-dollar',
                    'title' => $owner->payoutAccountSummary() ? 'Reversements : '.$owner->payout_method->label() : 'Coordonnées de reversement',
                    'text' => $owner->payoutAccountSummary()
                        ? 'Les paiements en ligne des clients sont encaissés par DS Holding puis reversés sur ce compte.'
                        : 'Indiquez où recevoir l’argent des réservations payées en ligne.',
                    'link' => ! $owner->payoutAccountSummary() && $isSelf && auth()->user()->isOwner() ? [route('admin.mes-reversements.index'), 'Renseigner'] : null,
                ],
            ];
        @endphp

        <div class="publication-section">

            <h4>

                <i class="fa-solid fa-list-check"></i>

                Avant la mise en ligne

            </h4>

            <ul class="readiness-list">

                @foreach ($checks as $check)

                    <li class="{{ $check['ok'] ? 'is-ok' : (($check['warn'] ?? false) ? 'is-warning' : 'is-todo') }}">

                        <span class="readiness-icon"><i class="fa-solid {{ $check['icon'] }}"></i></span>

                        <span class="readiness-text">
                            <strong>{{ $check['title'] }}</strong>
                            <small>{{ $check['text'] }}</small>
                        </span>

                        @if ($check['link'])
                            <a href="{{ $check['link'][0] }}" class="readiness-link" target="_blank" rel="noopener">{{ $check['link'][1] }} <i class="fa-solid fa-arrow-up-right-from-square"></i></a>
                        @else
                            <span class="readiness-state" aria-label="{{ $check['ok'] ? 'Prêt' : 'À compléter' }}">
                                <i class="fa-solid {{ $check['ok'] ? 'fa-circle-check' : (($check['warn'] ?? false) ? 'fa-triangle-exclamation' : 'fa-circle') }}"></i>
                            </span>
                        @endif

                    </li>

                @endforeach

            </ul>

        </div>

        <!-- ==========================================
            RESUME
        =========================================== -->

        <div class="publication-section">

            <h4>

                <i class="fa-solid fa-clipboard-check"></i>

                Résumé

            </h4>

            <div class="publication-resume">

                <div class="resume-item">

                    <i class="fa-solid fa-circle-info"></i>

                    <div>

                        <strong>

                            Statut actuel

                        </strong>

                        <p id="publicationResume">

                            {{ $options[$status][2] ?? '' }}

                        </p>

                    </div>

                </div>

            </div>

        </div>

    </div>

</div>
