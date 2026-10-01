@extends('site.pages.layout')

@section('title', 'Questions fréquentes')
@section('description', 'Réservation, paiement, annulation, compte client et propriétaires : les réponses aux questions les plus fréquentes sur DS HOLDING.')
@section('page_kicker', 'Aide')
@section('page_title', 'Questions fréquentes')
@section('page_lead', 'Tout ce qu’il faut savoir pour réserver, payer et gérer votre séjour.')

@section('page')
    <div class="container info-faq" data-faq>
        <aside class="info-faq-nav">
            <div class="info-search">
                <i class="fa-solid fa-magnifying-glass"></i>
                <input type="search" placeholder="Rechercher une question…" aria-label="Rechercher une question" data-faq-search>
            </div>
            <nav aria-label="Thèmes">
                @foreach ($questions as $theme => $items)
                    <a href="#{{ Str::slug($theme) }}">{{ $theme }} <span>{{ count($items) }}</span></a>
                @endforeach
            </nav>
        </aside>

        <div class="info-faq-list">
            @foreach ($questions as $theme => $items)
                <section id="{{ Str::slug($theme) }}" class="info-faq-group" data-faq-group>
                    <h2>{{ $theme }}</h2>
                    @foreach ($items as [$question, $answer])
                        <details class="info-faq-item" data-faq-item>
                            <summary>{{ $question }} <i class="fa-solid fa-chevron-down"></i></summary>
                            <p>{{ $answer }}</p>
                        </details>
                    @endforeach
                </section>
            @endforeach

            <p class="info-faq-empty" data-faq-empty hidden>Aucune question ne correspond à votre recherche.</p>

            <div class="info-cta">
                <div>
                    <strong>Vous ne trouvez pas votre réponse ?</strong>
                    <span>Notre équipe vous répond par email.</span>
                </div>
                <a href="{{ route('pages.contact') }}" class="site-btn site-btn-gold">Nous écrire <i class="fa-solid fa-arrow-right"></i></a>
            </div>
        </div>
    </div>
@endsection
