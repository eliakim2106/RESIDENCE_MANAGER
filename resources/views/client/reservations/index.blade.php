@extends('layouts.account')

@section('title', 'Mes réservations')
@section('account_heading', 'Mes réservations')
@section('account_subtitle', 'Suivez vos demandes, réglez vos séjours et retrouvez vos bons de réservation.')

@section('account_actions')
    <a href="{{ route('residences.index') }}" class="acc-btn acc-btn-gold"><i class="fa-solid fa-plus"></i> Nouvelle réservation</a>
@endsection

@section('account')
    <nav class="acc-tabs" aria-label="Filtrer mes réservations">
        @foreach (App\Http\Controllers\Client\ReservationController::TABS as $key => $label)
            <a href="{{ route('client.reservations.index', $key === 'a-venir' ? [] : ['onglet' => $key]) }}" class="acc-tab {{ $tab === $key ? 'is-active' : '' }}" @if ($tab === $key) aria-current="page" @endif>
                {{ $label }} <span>{{ $counts[$key] }}</span>
            </a>
        @endforeach
    </nav>

    @if ($reservations->isEmpty())
        <div class="acc-empty">
            <i class="fa-solid {{ $tab === 'annulees' ? 'fa-ban' : 'fa-calendar-plus' }}"></i>
            <strong>{{ match ($tab) { 'passees' => 'Aucun séjour passé', 'annulees' => 'Aucune réservation annulée', default => 'Aucun séjour à venir' } }}</strong>
            @if ($tab === 'a-venir')
                <p>Choisissez une résidence, vos dates et vos logements : la réservation se fait en quelques minutes.</p>
                <a href="{{ route('residences.index') }}" class="acc-btn acc-btn-gold">Trouver une résidence</a>
            @endif
        </div>
    @else
        <div class="acc-resa-list">
            @foreach ($reservations as $reservation)
                @include('client.partials.reservation-card', ['reservation' => $reservation])
            @endforeach
        </div>

        @if ($reservations->hasPages())
            <div class="acc-pagination">{{ $reservations->onEachSide(1)->links('pagination::bootstrap-5') }}</div>
        @endif
    @endif
@endsection
