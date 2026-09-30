@extends('layouts.admin')

@section('title', "Modifier l'équipement")

@section('content')
    <div class="admin-page-header">
        <div class="admin-page-heading">
            <span class="admin-page-icon"><i class="fa-solid fa-wifi"></i></span>
            <div>
                <h1>Modifier l'équipement</h1>
                <p>Mettez à jour ce service ou cette commodité.</p>
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

    <form class="admin-form" method="POST" action="{{ route('admin.equipements.update', $equipement) }}" novalidate>
        @csrf
        @method('PUT')

        @include('admin.equipements._form')

        <div class="form-actionbar">
            <a href="{{ route('admin.equipements.index') }}" class="btn-cancel">Annuler</a>
            <button type="submit" class="btn-save">
                <i class="fa-solid fa-check"></i>
                Enregistrer les modifications
            </button>
        </div>
    </form>
@endsection

@push('scripts')
    @vite('resources/js/admin/equipement.js')
@endpush
