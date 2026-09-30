@extends('layouts.site')

@section('title', 'Inscription')

@section('content')

    <div class="auth-container">

        <!-- =====================================
            PARTIE GAUCHE
        ====================================== -->

        <div class="auth-banner">

            <div class="overlay"></div>

            <div class="banner-content">

                <img src="{{ asset('assets/images/logo/ds_holding_logo.png') }}" alt="DS Holding">

                <h1>

                    Rejoignez
                    <span>DS HOLDING</span>

                </h1>

                <p>

                    Créez votre compte et profitez d'une plateforme moderne
                    pour réserver ou gérer vos établissements.

                </p>

                <div class="stats">

                    <div class="stat-box">

                        <h3>5000+</h3>

                        <span>Réservations</span>

                    </div>

                    <div class="stat-box">

                        <h3>500+</h3>

                        <span>Établissements</span>

                    </div>

                    <div class="stat-box">

                        <h3>98%</h3>

                        <span>Satisfaction</span>

                    </div>

                </div>

            </div>

        </div>

        <!-- =====================================
            PARTIE DROITE
        ====================================== -->

        <div class="auth-form-side">

            <div class="auth-card register-card">

                <img src="{{ asset('assets/images/logo/ds_holding_logo.png') }}" class="form-logo" alt="DS Holding">

                <span class="register-badge">

                    ÉTAPE 1 SUR 2

                </span>

                <h2>

                    Créer un compte

                </h2>

                <p>

                    Choisissez votre type de compte.

                </p>

                <form id="registerTypeForm">

                    <label class="account-option">

                        <input type="radio" name="account_type" value="client">

                        <div class="account-option-content">

                            <div class="option-icon">

                                <i class="fas fa-user"></i>

                            </div>

                            <div>

                                <h3>Client</h3>

                                <p style="text-align:left;">
                                    suivre vos réservations et retrouver facilement votre historique de séjour
                                </p>

                            </div>

                        </div>

                    </label>

                    <label class="account-option">

                        <input type="radio" name="account_type" value="owner">

                        <div class="account-option-content">

                            <div class="option-icon">

                                <i class="fas fa-hotel"></i>

                            </div>

                            <div>

                                <h3>Propriétaire</h3>

                                <p style="text-align:left;">
                                    Gérez vos établissements, réservations et revenus.
                                </p>

                            </div>

                        </div>

                    </label>

                    <button type="button" id="continueBtn" data-client-url="{{ route('register.client') }}"
                        data-owner-url="{{ route('register.owner') }}" class="btn-auth">

                        Continuer

                        <i class="fas fa-arrow-right"></i>

                    </button>

                </form>

                <div class="register-footer">

                    <span>

                        Vous avez déjà un compte ?

                    </span>

                    <a href="{{ route('login') }}">

                        Se connecter

                    </a>

                </div>

                <div class="auth-divider">

                    <span>ou</span>

                </div>

                <a href="{{ route('home') }}" class="back-link">

                    <i class="fas fa-arrow-left"></i>

                    <span>

                        Retour à l'accueil

                    </span>

                </a>

            </div>

        </div>

    </div>

@endsection
