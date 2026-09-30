@extends('layouts.admin')

@section('title', "Modifier l'unité")

@section('content')
    @include('admin.unites._form', ['action' => route('admin.unites.update', $unite)])
@endsection
