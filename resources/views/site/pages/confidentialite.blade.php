@extends('site.pages.layout')

{{-- Texte décrivant les données réellement traitées par l'application : à faire relire par un conseil juridique avant la mise en production. --}}

@section('title', 'Confidentialité')
@section('description', 'Politique de confidentialité de DS HOLDING : données collectées, utilisation, conservation et droits.')
@section('page_kicker', 'Informations légales')
@section('page_title', 'Politique de confidentialité')
@section('page_lead', 'Quelles données nous utilisons, pourquoi, et comment exercer vos droits.')

@section('page')
    <div class="container info-legal">
        <nav class="info-legal-toc" aria-label="Sommaire">
            <strong>Sommaire</strong>
            <a href="#donnees">1. Données collectées</a>
            <a href="#usages">2. Utilisation</a>
            <a href="#partage">3. Partage</a>
            <a href="#paiement">4. Paiement</a>
            <a href="#cookies">5. Cookies</a>
            <a href="#conservation">6. Conservation et sécurité</a>
            <a href="#droits">7. Vos droits</a>
        </nav>

        <article class="info-legal-text">
            <p class="info-legal-updated">Dernière mise à jour : {{ \Illuminate\Support\Carbon::parse('2026-10-01')->translatedFormat('d F Y') }}</p>

            <section id="donnees">
                <h2>1. Données collectées</h2>
                <ul>
                    <li><strong>Compte</strong> : nom, adresse email, téléphone, ville et pays, photo de profil le cas échéant, mot de passe (enregistré chiffré).</li>
                    <li><strong>Connexion avec Google ou Facebook</strong> : nom, adresse email, photo de profil et identifiant transmis par le service choisi.</li>
                    <li><strong>Réservations</strong> : dates, nombre de voyageurs, coordonnées du voyageur principal, heure d’arrivée et demandes particulières.</li>
                    <li><strong>Paiements</strong> : montant, moyen de paiement et référence de la transaction (sans numéro de carte).</li>
                    <li><strong>Contact et newsletter</strong> : nom, email, téléphone facultatif et contenu de votre message ; email d’inscription à la newsletter.</li>
                    <li><strong>Sécurité</strong> : historique des connexions (date, adresse IP, navigateur).</li>
                </ul>
            </section>

            <section id="usages">
                <h2>2. Utilisation</h2>
                <p>Ces données servent à créer et sécuriser votre compte, traiter vos réservations et paiements, vous envoyer les emails liés à vos séjours (confirmation, rappel, reçu, remboursement), répondre à vos messages et, si vous y êtes inscrit, vous adresser la newsletter.</p>
            </section>

            <section id="partage">
                <h2>3. Partage</h2>
                <p>Les informations d’une réservation sont transmises à l’établissement réservé, pour lui permettre de préparer votre séjour. Vos données ne sont ni vendues ni louées.</p>
            </section>

            <section id="paiement">
                <h2>4. Paiement</h2>
                <p>Les paiements en ligne sont traités par un agrégateur de paiement agréé. Les données de carte bancaire et les codes Mobile Money sont saisis chez cet agrégateur et ne transitent pas par nos serveurs.</p>
            </section>

            <section id="cookies">
                <h2>5. Cookies</h2>
                <p>Le site utilise uniquement les cookies nécessaires à son fonctionnement : maintien de votre session, protection des formulaires et option « Se souvenir de moi ». Aucun cookie publicitaire n’est déposé.</p>
            </section>

            <section id="conservation">
                <h2>6. Conservation et sécurité</h2>
                <p>Les données sont conservées pendant la durée de votre compte, puis le temps nécessaire au respect des obligations légales (notamment comptables pour les paiements). Les accès sont protégés par mot de passe et les échanges chiffrés.</p>
            </section>

            <section id="droits">
                <h2>7. Vos droits</h2>
                <p>Vous pouvez consulter et modifier vos informations depuis votre espace (« Mon profil »). Pour demander une copie ou la suppression de vos données, ou vous opposer à un traitement, écrivez-nous via le <a href="{{ route('pages.contact', ['sujet' => 'Données personnelles']) }}">formulaire de contact</a> ou à <a href="mailto:contact@dsholding.ci">contact@dsholding.ci</a>. Chaque newsletter contient un lien de désinscription.</p>
            </section>
        </article>
    </div>
@endsection
