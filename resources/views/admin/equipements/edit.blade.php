@extends('layouts.admin')

@section('title', "Modifier l'équipement")

@section('content')
    <div class="form-card">
        <div class="form-header">
            <div class="form-header-left">
                <h2>Modifier l'équipement</h2>
                <p>Modifiez l'équipement d'hébergement</p>
            </div>

            <div class="form-header-right">
                <a href="{{ route('admin.equipements.index') }}" class="btn-secondary">
                    <i class="fa-solid fa-arrow-left"></i>
                    Retour
                </a>
            </div>
        </div>

        @include('partials.flash')

        <form class="property-type-form" method="POST" action="{{ route('admin.equipements.update', $equipement) }}">
            @csrf
            @method('PUT')

            @include('admin.equipements._form')

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
    @vite('resources/js/admin/equipement.js')
@endpush
