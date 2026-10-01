@extends('layouts.account')

@section('title', 'Mes favoris')
@section('account_heading', 'Mes favoris')
@section('account_subtitle', 'Les résidences que vous avez mises de côté.')

@section('account')
    @if ($favorites->isEmpty())
        <div class="acc-empty">
            <i class="fa-regular fa-heart"></i>
            <strong>Aucun favori pour le moment</strong>
            <p>Touchez le cœur d’une résidence pour la retrouver ici.</p>
            <a href="{{ route('residences.index') }}" class="acc-btn acc-btn-gold">Explorer les résidences</a>
        </div>
    @else
        <div class="acc-fav-grid">
            @foreach ($favorites as $residence)
                <article class="acc-fav-card">
                    <a href="{{ route('residences.show', $residence) }}" class="acc-fav-media">
                        <img src="{{ $residence->coverImage?->url ?? asset('assets/images/home/residence-1.webp') }}" alt="{{ $residence->name }}" loading="lazy">
                    </a>
                    <form method="POST" action="{{ route('client.favorites.toggle', $residence) }}" class="acc-fav-remove">
                        @csrf
                        <button type="submit" title="Retirer des favoris" aria-label="Retirer {{ $residence->name }} des favoris"><i class="fa-solid fa-heart"></i></button>
                    </form>
                    <div class="acc-fav-body">
                        <small>{{ $residence->propertyType?->name }} · {{ $residence->city?->name }}</small>
                        <h3><a href="{{ route('residences.show', $residence) }}">{{ $residence->name }}</a></h3>
                        <div class="acc-fav-foot">
                            @if ($residence->prix_min)
                                <span>À partir de <strong>{{ number_format((int) $residence->prix_min, 0, ',', ' ') }} FCFA</strong> / nuit</span>
                            @endif
                            @if ($residence->reviews_count > 0)
                                <span class="acc-fav-rating"><i class="fa-solid fa-star"></i> {{ number_format((float) $residence->rating_average, 1, ',', ' ') }}</span>
                            @endif
                        </div>
                    </div>
                </article>
            @endforeach
        </div>

        @if ($favorites->hasPages())
            <div class="acc-pagination">{{ $favorites->links('pagination::bootstrap-5') }}</div>
        @endif
    @endif
@endsection
