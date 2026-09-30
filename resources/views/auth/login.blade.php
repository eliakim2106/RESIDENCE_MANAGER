@extends('layouts.site')

@section('title', 'Connexion')

@section('content')

    <div class="auth-container">

        <div class="auth-banner">

            <div class="auth-overlay"></div>

            <div class="banner-content">

                <img src="{{ asset('assets/images/logo/ds_holding_logo.png') }}" alt="DS Holding">

                <h1>
                    Gérez vos établissements
                    <span>en toute simplicité</span>
                </h1>

                <p>
                    La solution complète pour gérer vos résidences,
                    réservations et revenus.
                </p>

                <div class="auth-stats">

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

        <div class="auth-form-side">

            <div class="auth-card">

                <img src="{{ asset('assets/images/logo/ds_holding_logo.png') }}" class="form-logo">

                <h2>Bon retour 👋</h2>

                <p>
                    Connectez-vous à votre espace.
                </p>

                @include('auth.partials.alerts')

                <form method="POST" action="{{ route('login') }}">

                    @csrf

                    <div class="auth-field">

                        <label>Email</label>

                        <input type="email" name="email" value="{{ old('email') }}" placeholder="exemple@email.com"
                            required>

                    </div>

                    <div class="auth-field">

                        <label>Mot de passe</label>

                        <div class="password-wrapper">

                            <input type="password" id="password" name="password" placeholder="********" required>

                            <button type="button" class="toggle-password" data-target="password">

                                <i class="fas fa-eye"></i>

                            </button>

                        </div>

                    </div>

                    <div class="auth-options">

                        <label>
                            <input type="checkbox" name="remember" value="1" @checked(old('remember'))>
                            Se souvenir de moi
                        </label>

                        <a href="#">
                            Mot de passe oublié ?
                        </a>

                    </div>

                    <button class="btn-auth">

                        Se connecter

                    </button>

                </form>

                <div class="auth-register">

                    <span>Vous n'avez pas encore de compte ?</span>

                    <a href="{{ route('register') }}">
                        Créer un compte
                    </a>

                </div>

                <div class="auth-divider">
                    <span>ou</span>
                </div>

                <a href="{{ route('home') }}" class="back-link">
                    <i class="fas fa-arrow-left"></i>
                    <span>Retour à l'accueil</span>
                </a>

            </div>

        </div>

    </div>

@endsection
