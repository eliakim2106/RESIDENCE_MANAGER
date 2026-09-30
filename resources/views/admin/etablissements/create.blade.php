@extends('layouts.admin')

@section('title', 'Nouvel établissement')

@section('content')
    @include('admin.etablissements._form', [
        'action' => route('admin.etablissements.store'),
        'title' => 'Nouvel établissement',
        'subtitle' => 'Ajoutez un nouvel établissement sur la plateforme.',
    ])
@endsection
