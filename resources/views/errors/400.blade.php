@extends('errors.layout', ['icon' => '<path d="M12 3 2 20h20L12 3z"/><path d="M12 10v4M12 17v.01"/>'])

@section('code', '400')
@section('title', 'Requête incorrecte')
@section('message', 'La demande envoyée n’a pas pu être comprise. Vérifiez l’adresse ou recommencez depuis la page précédente.')
