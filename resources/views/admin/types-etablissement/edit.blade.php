@extends('layouts.admin')

@section('title', "Modifier le type d'établissement")

@section('content')
    <div class="form-card">
        <div class="form-header">
            <div class="form-header-left">
                <h2>Modifier le type d'établissement</h2>
                <p>Modifiez les informations de cette catégorie d'hébergement</p>
            </div>

            <div class="form-header-right">
                <a href="{{ route('admin.types-etablissement.index') }}" class="btn-secondary">
                    <i class="fa-solid fa-arrow-left"></i>
                    Retour
                </a>
            </div>
        </div>

        @include('partials.flash')

        <form class="property-type-form" method="POST" action="{{ route('admin.types-etablissement.update', $type) }}">
            @csrf
            @method('PUT')

            @include('admin.partials.type-form', [
                'presets' => 'etablissement',
                'choices' => ['Hôtel', 'Résidence meublée', 'Villa', 'Appartement', 'Auberge', 'Guest House'],
                'label' => "Type d'établissement",
            ])

            <div class="form-actions">
                <button type="submit" class="btn-save">
                    <i class="fa-solid fa-pen-to-square"></i>
                    Mettre à jour
                </button>
            </div>
        </form>
    </div>
@endsection

@push('scripts')
    @vite('resources/js/admin/types.js')
@endpush
