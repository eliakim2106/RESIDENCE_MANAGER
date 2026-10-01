@extends('site.pages.layout')

{{-- Texte décrivant le fonctionnement réel de la plateforme : à faire relire par un conseil juridique avant la mise en production. --}}

@section('title', 'Conditions d’utilisation')
@section('description', 'Conditions d’utilisation de la plateforme de réservation DS HOLDING.')
@section('page_kicker', 'Informations légales')
@section('page_title', 'Conditions d’utilisation')
@section('page_lead', 'Les règles qui encadrent l’utilisation du site et des réservations.')

@section('page')
    <div class="container info-legal">
        <nav class="info-legal-toc" aria-label="Sommaire">
            <strong>Sommaire</strong>
            <a href="#objet">1. Objet</a>
            <a href="#comptes">2. Comptes</a>
            <a href="#reservations">3. Réservations</a>
            <a href="#paiements">4. Paiements</a>
            <a href="#annulations">5. Annulations</a>
            <a href="#sejour">6. Pendant le séjour</a>
            <a href="#avis">7. Avis</a>
            <a href="#proprietaires">8. Propriétaires</a>
            <a href="#responsabilite">9. Responsabilité</a>
            <a href="#contact">10. Contact</a>
        </nav>

        <article class="info-legal-text">
            <p class="info-legal-updated">Dernière mise à jour : {{ \Illuminate\Support\Carbon::parse('2026-10-01')->translatedFormat('d F Y') }}</p>

            <section id="objet">
                <h2>1. Objet</h2>
                <p>DS HOLDING met en relation des voyageurs avec des établissements d’hébergement (résidences meublées, hôtels, villas, appartements) situés en Côte d’Ivoire. Le site permet de consulter les établissements, de vérifier leurs disponibilités, de demander une réservation et de la régler en ligne.</p>
                <p>L’utilisation du site implique l’acceptation des présentes conditions.</p>
            </section>

            <section id="comptes">
                <h2>2. Comptes</h2>
                <p>La réservation nécessite un compte client. Le compte peut être créé avec une adresse email confirmée, ou avec un compte Google ou Facebook lorsque cette option est proposée. Vous êtes responsable de la confidentialité de votre mot de passe et des informations que vous renseignez, qui doivent être exactes.</p>
                <p>Les comptes propriétaires permettent de publier et de gérer des établissements. DS HOLDING peut suspendre un compte en cas d’utilisation frauduleuse ou contraire aux présentes conditions.</p>
            </section>

            <section id="reservations">
                <h2>3. Réservations</h2>
                <p>Une réservation envoyée depuis le site est une <strong>demande</strong> transmise à l’établissement. Elle devient ferme lorsque l’établissement la confirme ; vous en êtes informé par email et dans votre espace client. Sans réponse de l’établissement dans le délai indiqué lors de la demande, celle-ci expire automatiquement.</p>
                <p>Le prix affiché au récapitulatif comprend l’hébergement pour les nuits choisies et, le cas échéant, les frais de ménage et de service indiqués. Les taxes ou services complémentaires proposés sur place relèvent de l’établissement.</p>
            </section>

            <section id="paiements">
                <h2>4. Paiements</h2>
                <p>Le paiement en ligne est traité par un agrégateur de paiement agréé ; DS HOLDING ne conserve aucune donnée de carte bancaire ni code Mobile Money. Un reçu est disponible dans votre espace client pour chaque paiement.</p>
                <p>Les sommes réglées en ligne sont reversées par DS HOLDING aux établissements concernés.</p>
            </section>

            <section id="annulations">
                <h2>5. Annulations</h2>
                <p>Chaque établissement applique une politique d’annulation, affichée sur sa fiche et sur le récapitulatif de réservation :</p>
                <ul>
                    <li><strong>Flexible</strong> : annulation gratuite jusqu’à 24 heures avant l’arrivée ;</li>
                    <li><strong>Modérée</strong> : annulation gratuite jusqu’à 5 jours avant l’arrivée ;</li>
                    <li><strong>Stricte</strong> : réservation non remboursable une fois confirmée.</li>
                </ul>
                <p>Une annulation effectuée depuis votre espace avant la date limite d’annulation gratuite donne lieu au remboursement des sommes versées. L’établissement peut également annuler une réservation : vous en êtes prévenu par email, et le remboursement des sommes versées est alors traité avec lui.</p>
            </section>

            <section id="sejour">
                <h2>6. Pendant le séjour</h2>
                <p>Les horaires d’arrivée et de départ ainsi que les règles de la maison (animaux, tabac, fêtes…) sont fixés par l’établissement et rappelés sur sa fiche. Le voyageur s’engage à les respecter et à utiliser le logement de manière raisonnable.</p>
            </section>

            <section id="avis">
                <h2>7. Avis</h2>
                <p>Seuls les clients ayant séjourné dans un établissement peuvent le noter, après leur départ. Les avis doivent être sincères et respectueux ; DS HOLDING peut retirer un avis injurieux, diffamatoire ou sans rapport avec le séjour.</p>
            </section>

            <section id="proprietaires">
                <h2>8. Propriétaires</h2>
                <p>Le propriétaire garantit l’exactitude des informations publiées (description, photos, prix, disponibilités, conditions). Chaque établissement est vérifié par DS HOLDING avant sa mise en ligne. Lorsqu’un abonnement est requis, les établissements d’un propriétaire dont l’abonnement n’est pas à jour ne sont plus visibles sur le site.</p>
            </section>

            <section id="responsabilite">
                <h2>9. Responsabilité</h2>
                <p>L’établissement est seul responsable de l’hébergement fourni et de la qualité de ses prestations. DS HOLDING s’efforce d’assurer la disponibilité du site et l’exactitude des informations affichées, sans pouvoir garantir l’absence d’interruption.</p>
            </section>

            <section id="contact">
                <h2>10. Contact</h2>
                <p>Pour toute question sur ces conditions : <a href="{{ route('pages.contact') }}">formulaire de contact</a> ou <a href="mailto:contact@dsholding.ci">contact@dsholding.ci</a>.</p>
            </section>
        </article>
    </div>
@endsection
