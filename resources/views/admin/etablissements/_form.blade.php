{{-- Formulaire en 6 étapes, commun à la création et à la modification --}}
<div class="etablissement-page">

    <form id="etablissementForm" class="formulaire-etablissement" method="POST" action="{{ $action }}" enctype="multipart/form-data">
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
                <a href="{{ route('admin.etablissements.index') }}" class="btn-secondary">
                    <i class="fa-solid fa-arrow-left"></i>
                    Retour à la liste
                </a>
            </div>
        </div>

        @include('partials.flash')

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
