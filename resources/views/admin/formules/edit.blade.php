@extends('layouts.admin')

@section('title', 'Modifier la formule')

@section('content')
    <div class="admin-page-header">
        <div class="admin-page-heading">
            <span class="admin-page-icon"><i class="fa-solid fa-layer-group"></i></span>
            <div>
                <h1>Modifier la formule</h1>
                <p>Les nouveaux prix s’appliquent aux prochaines factures.</p>
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

    <form class="admin-form" method="POST" action="{{ route('admin.formules.update', $plan) }}" novalidate>
        @csrf
        @method('PUT')

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
