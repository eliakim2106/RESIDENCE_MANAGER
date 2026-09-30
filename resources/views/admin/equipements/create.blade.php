@extends('layouts.admin')

@section('title', "Nouvel équipement")

@section('content')
    <div class="form-card">
        <div class="form-header">
            <div class="form-header-left">
                <h2>Nouvel équipement</h2>
                <p>Ajoutez un nouvel équipement d'hébergement</p>
            </div>

            <div class="form-header-right">
                <a href="{{ route('admin.equipements.index') }}" class="btn-secondary">
                    <i class="fa-solid fa-arrow-left"></i>
                    Retour
                </a>
            </div>
        </div>

        @include('partials.flash')

        <form class="property-type-form" method="POST" action="{{ route('admin.equipements.store') }}">
            @csrf

            @include('admin.equipements._form')

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
    @vite('resources/js/admin/equipement.js')
@endpush
