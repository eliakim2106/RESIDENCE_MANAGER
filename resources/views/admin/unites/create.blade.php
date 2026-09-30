@extends('layouts.admin')

@section('title', 'Ajouter une unité')

@section('content')
    @include('admin.unites._form', ['action' => route('admin.etablissements.unites.store', $etablissement)])
@endsection
