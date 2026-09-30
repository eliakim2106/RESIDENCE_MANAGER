@extends('layouts.admin')

@section('title', "Nouvel équipement")

@section('content')
    <div class="admin-page-header">
        <div class="admin-page-heading">
            <span class="admin-page-icon"><i class="fa-solid fa-wifi"></i></span>
            <div>
                <h1>Nouvel équipement</h1>
                <p>Ajoutez un service ou une commodité proposée par les hébergements.</p>
            </div>
        </div>

        <div class="admin-page-actions">
            <a href="{{ route('admin.equipements.index') }}" class="btn-secondary">
                <i class="fa-solid fa-arrow-left"></i>
                Retour à la liste
            </a>
        </div>
    </div>

    @include('partials.flash')

    <form class="admin-form" method="POST" action="{{ route('admin.equipements.store') }}" novalidate>
        @csrf

        @include('admin.equipements._form')

        <div class="form-actionbar">
            <a href="{{ route('admin.equipements.index') }}" class="btn-cancel">Annuler</a>
            <button type="submit" class="btn-save">
                <i class="fa-solid fa-check"></i>
                Enregistrer
            </button>
        </div>
    </form>
@endsection

@push('scripts')
    @vite('resources/js/admin/equipement.js')
@endpush
