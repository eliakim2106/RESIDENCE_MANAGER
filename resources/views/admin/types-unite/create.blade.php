@extends('layouts.admin')

@section('title', "Nouveau type d'unité")

@section('content')
    <div class="admin-page-header">
        <div class="admin-page-heading">
            <span class="admin-page-icon"><i class="fa-solid fa-bed"></i></span>
            <div>
                <h1>Nouveau type d'unité</h1>
                <p>Ajoutez une catégorie de chambre ou de logement.</p>
            </div>
        </div>

        <div class="admin-page-actions">
            <a href="{{ route('admin.types-unite.index') }}" class="btn-secondary">
                <i class="fa-solid fa-arrow-left"></i>
                Retour à la liste
            </a>
        </div>
    </div>

    @include('partials.flash')

    <form class="admin-form" method="POST" action="{{ route('admin.types-unite.store') }}" novalidate>
        @csrf

        @include('admin.partials.type-form', [
            'presets' => 'unite',
            'choices' => ['Chambre Standard', 'Chambre Deluxe', 'Suite Junior', 'Suite Présidentielle', 'Appartement entier', 'Villa entière', 'Studio'],
            'label' => "Type d'unité",
        ])

        <div class="form-actionbar">
            <a href="{{ route('admin.types-unite.index') }}" class="btn-cancel">Annuler</a>
            <button type="submit" class="btn-save">
                <i class="fa-solid fa-check"></i>
                Enregistrer
            </button>
        </div>
    </form>
@endsection

@push('scripts')
    @vite('resources/js/admin/types.js')
@endpush
