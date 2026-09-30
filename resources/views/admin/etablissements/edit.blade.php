@extends('layouts.admin')

@section('title', "Modifier l'établissement")

@section('content')
    @include('admin.etablissements._form', [
        'action' => route('admin.etablissements.update', $etablissement),
        'title' => "Modifier l'établissement",
        'subtitle' => "Modifiez les informations de l'établissement.",
    ])
@endsection
