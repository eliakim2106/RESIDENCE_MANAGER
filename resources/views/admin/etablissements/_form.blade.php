{{-- Formulaire en 6 étapes, commun à la création et à la modification --}}
<div class="etablissement-page">

    @php
        $serverErrors = $errors->getMessages();
        $errorStep = collect(array_keys($serverErrors))->map(fn (string $field) => App\Http\Requests\Admin\PropertyRequest::stepOf($field))->min();
        $stepNames = ['Informations', 'Localisation', 'Accueil & conditions', 'Médias', 'Publication', 'SEO'];
    @endphp

    <form id="etablissementForm" class="formulaire-etablissement" method="POST" action="{{ $action }}" enctype="multipart/form-data"
        data-start-step="{{ $errorStep ?? ($startStep ?? 1) }}"
        data-edit-mode="{{ $etablissement->exists ? '1' : '0' }}"
        data-server-errors='@json((object) $serverErrors)'>
        @csrf

        @if ($etablissement->exists)
            @method('PUT')
        @endif

        <div class="admin-page-header">
            <div class="admin-page-heading">
                <span class="admin-page-icon"><i class="fa-solid fa-building"></i></span>
                <div>
                    <h1>{{ $title }}</h1>
                    <p>{{ $subtitle }}</p>
                </div>
            </div>

            <div class="admin-page-actions">
                <a href="{{ $etablissement->exists ? route('admin.etablissements.show', $etablissement) : route('admin.etablissements.index') }}" class="btn-secondary">
                    <i class="fa-solid fa-arrow-left"></i>
                    {{ $etablissement->exists ? 'Retour à la fiche' : 'Retour à la liste' }}
                </a>
            </div>
        </div>

        @include('partials.flash', ['withErrors' => false])

        {{-- Erreurs renvoyées par le serveur : regroupées par étape, l'étape concernée s'ouvre --}}
        @if ($serverErrors !== [])
            <div class="moderation-banner tone-critical form-error-summary" role="alert">
                <i class="fa-solid fa-circle-exclamation"></i>
                <div>
                    <strong>{{ count($serverErrors) > 1 ? count($serverErrors).' points à corriger' : 'Un point à corriger' }} avant d’enregistrer</strong>
                    <ul>
                        @foreach ($serverErrors as $field => $messages)
                            @php($step = App\Http\Requests\Admin\PropertyRequest::stepOf($field))
                            <li>
                                <button type="button" class="link-btn" data-go-step="{{ $step }}">{{ $stepNames[$step - 1] }}</button>
                                — {{ $messages[0] }}
                            </li>
                        @endforeach
                    </ul>
                    @if (isset($serverErrors['gallery']) || collect(array_keys($serverErrors))->contains(fn ($field) => str_starts_with($field, 'gallery.')) || ! $etablissement->exists)
                        <p class="form-error-note">Par sécurité, le navigateur ne conserve pas les nouvelles photos choisies : ajoutez-les de nouveau à l’étape Médias.</p>
                    @endif
                </div>
            </div>
        @endif

        {{-- État de la validation par un administrateur --}}
        @if ($etablissement->exists)
            @if ($etablissement->isPending())
                <div class="moderation-banner tone-warning" role="status">
                    <i class="fa-solid fa-hourglass-half"></i>
                    <div>
                        <strong>En attente de validation</strong>
                        <p>Soumis {{ $etablissement->submitted_at?->diffForHumans() ?? '' }}. L’établissement sera visible sur le site dès qu’un administrateur l’aura approuvé.</p>
                    </div>
                </div>
            @elseif ($etablissement->isSuspended())
                <div class="moderation-banner tone-critical" role="status">
                    <i class="fa-solid fa-ban"></i>
                    <div>
                        <strong>Établissement suspendu{{ $etablissement->moderated_at ? ' le '.$etablissement->moderated_at->translatedFormat('d F Y') : '' }}</strong>
                        <p>{{ $etablissement->moderation_note ?: 'Il n’est plus visible sur le site.' }}</p>
                    </div>
                </div>
            @elseif ($etablissement->wasRejected())
                <div class="moderation-banner tone-critical" role="status">
                    <i class="fa-solid fa-circle-xmark"></i>
                    <div>
                        <strong>Demande de publication refusée</strong>
                        <p>{{ $etablissement->moderation_note }}</p>
                        <p>Corrigez les points indiqués, puis choisissez « Soumettre pour validation » à l’étape Publication.</p>
                    </div>
                </div>
            @endif
        @endif

        @include('admin.etablissements.partials._stepper')

        <div class="etablissement-layout">

            <div class="contenu-formulaire">
                @include('admin.etablissements.partials._general')
                @include('admin.etablissements.partials._localisation')
                @include('admin.etablissements.partials._contact-accueil')
                @include('admin.etablissements.partials._media')
                @include('admin.etablissements.partials._publication')
                @include('admin.etablissements.partials._seo')
            </div>

            <aside class="barre-laterale"></aside>

        </div>

        @include('admin.etablissements.partials._footer')
    </form>

</div>

@push('styles')
    @vite('resources/css/admin/etablissement.css')
@endpush

@push('scripts')
    @vite('resources/js/admin/etablissement.js')
@endpush
