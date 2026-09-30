@extends('layouts.admin')

@section('title', "Nouveau type d'établissement")

@section('content')
    <div class="form-card">
        <div class="form-header">
            <div class="form-header-left">
                <h2>Nouveau type d'établissement</h2>
                <p>Ajoutez une nouvelle catégorie d'hébergement</p>
            </div>

            <div class="form-header-right">
                <a href="{{ route('admin.types-etablissement.index') }}" class="btn-secondary">
                    <i class="fa-solid fa-arrow-left"></i>
                    Retour
                </a>
            </div>
        </div>

        @include('partials.flash')

        <form class="property-type-form" method="POST" action="{{ route('admin.types-etablissement.store') }}">
            @csrf

            @include('admin.partials.type-form', [
                'presets' => 'etablissement',
                'choices' => ['Hôtel', 'Résidence meublée', 'Villa', 'Appartement', 'Auberge', 'Guest House'],
                'label' => "Type d'établissement",
            ])

            <div class="form-actions">
                <button type="submit" class="btn-save">
                    <i class="fa-solid fa-floppy-disk"></i>
                    Enregistrer
                </button>
            </div>
        </form>
    </div>
@endsection

@push('scripts')
    @vite('resources/js/admin/types.js')
@endpush
