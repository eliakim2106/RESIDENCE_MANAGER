@extends('layouts.admin')

@section('title', "Modifier le type d'établissement")

@section('content')
    <div class="admin-page-header">
        <div class="admin-page-heading">
            <span class="admin-page-icon"><i class="fa-solid fa-hotel"></i></span>
            <div>
                <h1>Modifier le type d'établissement</h1>
                <p>Mettez à jour cette catégorie d'hébergement.</p>
            </div>
        </div>

        <div class="admin-page-actions">
            <a href="{{ route('admin.types-etablissement.index') }}" class="btn-secondary">
                <i class="fa-solid fa-arrow-left"></i>
                Retour à la liste
            </a>
        </div>
    </div>

    @include('partials.flash')

    <form class="admin-form" method="POST" action="{{ route('admin.types-etablissement.update', $type) }}" novalidate>
        @csrf
        @method('PUT')

        @include('admin.partials.type-form', [
            'presets' => 'etablissement',
            'choices' => ['Hôtel', 'Résidence meublée', 'Villa', 'Appartement', 'Auberge', 'Guest House'],
            'label' => "Type d'établissement",
        ])

        <div class="form-actionbar">
            <a href="{{ route('admin.types-etablissement.index') }}" class="btn-cancel">Annuler</a>
            <button type="submit" class="btn-save">
                <i class="fa-solid fa-check"></i>
                Enregistrer les modifications
            </button>
        </div>
    </form>
@endsection

@push('scripts')
    @vite('resources/js/admin/types.js')
@endpush
