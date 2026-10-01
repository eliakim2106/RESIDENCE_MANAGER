@extends('errors.layout', ['icon' => '<path d="M12 3 4 6v6c0 4.5 3.4 8.3 8 9 4.6-.7 8-4.5 8-9V6l-8-3z"/><path d="m9.5 9.5 5 5M14.5 9.5l-5 5"/>'])

@section('code', '403')
@section('title', 'Accès refusé')
@php
    $detail = isset($exception) ? trim($exception->getMessage()) : '';
    $detail = in_array($detail, ['', 'Forbidden', 'This action is unauthorized.'], true) ? null : $detail;
@endphp
@section('message', $detail ?? 'Vous n’avez pas l’autorisation d’accéder à cette page avec votre compte.')
