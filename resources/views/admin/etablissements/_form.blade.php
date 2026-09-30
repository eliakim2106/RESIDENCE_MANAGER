{{-- Formulaire en 6 étapes, commun à la création et à la modification --}}
<div class="etablissement-page">

    <form id="etablissementForm" class="formulaire-etablissement" method="POST" action="{{ $action }}" enctype="multipart/form-data">
        @csrf

        @if ($etablissement->exists)
            @method('PUT')
        @endif

        <div class="page-card">
            <div class="page-card-header">
                <div>
                    <h2>{{ $title }}</h2>
                    <p>{{ $subtitle }}</p>
                </div>

                <a href="{{ route('admin.etablissements.index') }}" class="btn-secondary">
                    <i class="fa-solid fa-arrow-left"></i>
                    Retour
                </a>
            </div>
        </div>

        @include('partials.flash')

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
