@extends('errors.layout', ['icon' => '<path d="M6 2h12M6 22h12M7 2v4l5 6-5 6v4M17 2v4l-5 6 5 6v4"/>'])

@section('code', '429')
@section('title', 'Trop de tentatives')
@section('message', 'Vous avez effectué beaucoup de demandes en peu de temps. Patientez une minute avant de réessayer.')
