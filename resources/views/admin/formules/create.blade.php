@extends('layouts.admin')

@section('title', 'Nouvelle formule')

@section('content')
    <div class="admin-page-header">
        <div class="admin-page-heading">
            <span class="admin-page-icon"><i class="fa-solid fa-layer-group"></i></span>
            <div>
                <h1>Nouvelle formule</h1>
                <p>Créez une formule d’abonnement pour les propriétaires.</p>
            </div>
        </div>

        <div class="admin-page-actions">
            <a href="{{ route('admin.formules.index') }}" class="btn-secondary">
                <i class="fa-solid fa-arrow-left"></i>
                Retour aux formules
            </a>
        </div>
    </div>

    @include('partials.flash')

    <form class="admin-form" method="POST" action="{{ route('admin.formules.store') }}" novalidate>
        @csrf

        @include('admin.formules._form')

        <div class="form-actionbar">
            <a href="{{ route('admin.formules.index') }}" class="btn-cancel">Annuler</a>
            <button type="submit" class="btn-save">
                <i class="fa-solid fa-check"></i>
                Enregistrer
            </button>
        </div>
    </form>
@endsection
