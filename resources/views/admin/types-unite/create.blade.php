@extends('layouts.admin')

@section('title', "Nouveau type d'unité")

@section('content')
    <div class="form-card">
        <div class="form-header">
            <div class="form-header-left">
                <h2>Nouveau type d'unité</h2>
                <p>Ajoutez un nouveau type d'unité</p>
            </div>

            <div class="form-header-right">
                <a href="{{ route('admin.types-unite.index') }}" class="btn-secondary">
                    <i class="fa-solid fa-arrow-left"></i>
                    Retour
                </a>
            </div>
        </div>

        @include('partials.flash')

        <form class="property-type-form" method="POST" action="{{ route('admin.types-unite.store') }}">
            @csrf

            @include('admin.partials.type-form', [
                'presets' => 'unite',
                'choices' => ['Chambre Standard', 'Chambre Deluxe', 'Suite Junior', 'Suite Présidentielle', 'Appartement entier', 'Villa entière', 'Studio'],
                'label' => "Type d'unité",
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
