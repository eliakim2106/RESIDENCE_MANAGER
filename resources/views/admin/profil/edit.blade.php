@extends('layouts.admin')

@section('title', 'Mon profil')

@section('content')
    <div class="admin-page-header">
        <div class="admin-page-heading">
            <span class="admin-page-icon"><i class="fa-solid fa-user"></i></span>
            <div>
                <h1>Mon profil</h1>
                <p>Vos informations personnelles, votre photo et la sécurité de votre compte.</p>
            </div>
        </div>
    </div>

    @include('partials.flash')

    <div class="resa-layout">

        {{-- ========== Informations ========== --}}
        <form method="POST" action="{{ route('admin.profil.update') }}" enctype="multipart/form-data" class="admin-form resa-main">
            @csrf
            @method('PUT')

            <section class="form-section">
                <div class="form-section-header">
                    <h2>Photo de profil</h2>
                    <p>Visible dans votre espace et par l’équipe DS Holding.</p>
                </div>

                <div class="avatar-field" data-avatar-field>
                    <img src="{{ $user->avatarUrl() }}" alt="" class="avatar-preview" data-avatar-preview
                        data-fallback="https://ui-avatars.com/api/?background=d4a72c&color=0a1f44&bold=true&name={{ urlencode($user->name) }}">
                    <div class="avatar-actions">
                        <label class="btn-secondary btn-sm" for="photo">
                            <i class="fa-solid fa-camera"></i>
                            {{ $user->avatar_path ? 'Changer la photo' : 'Ajouter une photo' }}
                        </label>
                        <input type="file" name="photo" id="photo" accept="image/jpeg,image/png,image/webp" class="visually-hidden" data-avatar-input>
                        @if ($user->avatar_path)
                            <label class="avatar-remove">
                                <input type="checkbox" name="supprimer_photo" value="1" data-avatar-remove>
                                Retirer la photo
                            </label>
                        @endif
                        <p class="field-help">JPG, PNG ou WebP, 2 Mo maximum.</p>
                        @error('photo')
                            <p class="field-error">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </section>

            <section class="form-section">
                <div class="form-section-header">
                    <h2>Informations personnelles</h2>
                    <p>Un changement d’adresse email devra être confirmé par un lien envoyé à la nouvelle adresse.</p>
                </div>

                <div class="form-grid">
                    <div class="form-group">
                        <label for="nom">Nom complet <span class="required">*</span></label>
                        <input type="text" name="nom" id="nom" value="{{ old('nom', $user->name) }}" required maxlength="255" autocomplete="name">
                        @error('nom')
                            <p class="field-error">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label for="email">Adresse email <span class="required">*</span></label>
                        <input type="email" name="email" id="email" value="{{ old('email', $user->email) }}" required maxlength="255" autocomplete="email">
                        @error('email')
                            <p class="field-error">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label for="telephone">Téléphone</label>
                        <input type="tel" name="telephone" id="telephone" value="{{ old('telephone', $user->phone) }}" maxlength="30" autocomplete="tel" placeholder="+225 07 00 00 00 00">
                        @error('telephone')
                            <p class="field-error">{{ $message }}</p>
                        @enderror
                    </div>

                    @if ($user->isOwner())
                        <div class="form-group">
                            <label for="entreprise">Entreprise</label>
                            <input type="text" name="entreprise" id="entreprise" value="{{ old('entreprise', $user->company_name) }}" maxlength="255" autocomplete="organization">
                            @error('entreprise')
                                <p class="field-error">{{ $message }}</p>
                            @enderror
                        </div>
                    @endif

                    <div class="form-group">
                        <label for="ville">Ville</label>
                        <input type="text" name="ville" id="ville" value="{{ old('ville', $user->city) }}" maxlength="100" autocomplete="address-level2">
                    </div>

                    <div class="form-group">
                        <label for="pays">Pays</label>
                        <input type="text" name="pays" id="pays" value="{{ old('pays', $user->country) }}" maxlength="100" autocomplete="country-name">
                    </div>
                </div>

                <div class="profile-form-actions">
                    <button type="submit" class="btn-primary">
                        <i class="fa-solid fa-floppy-disk"></i>
                        Enregistrer
                    </button>
                </div>
            </section>
        </form>

        {{-- ========== Sécurité ========== --}}
        <aside class="resa-aside">
            <section class="dash-card">
                <div class="dash-card-header">
                    <div>
                        <h2>Mot de passe</h2>
                        <p>8 caractères minimum, avec des lettres et des chiffres.</p>
                    </div>
                </div>

                <form method="POST" action="{{ route('admin.profil.password') }}" class="admin-form resa-notes">
                    @csrf
                    @method('PUT')

                    <div class="form-group">
                        <label for="mot_de_passe_actuel">Mot de passe actuel</label>
                        <input type="password" name="mot_de_passe_actuel" id="mot_de_passe_actuel" required autocomplete="current-password">
                        @error('mot_de_passe_actuel', 'password')
                            <p class="field-error">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label for="mot_de_passe">Nouveau mot de passe</label>
                        <input type="password" name="mot_de_passe" id="mot_de_passe" required autocomplete="new-password" minlength="8">
                        @error('mot_de_passe', 'password')
                            <p class="field-error">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label for="mot_de_passe_confirmation">Confirmation</label>
                        <input type="password" name="mot_de_passe_confirmation" id="mot_de_passe_confirmation" required autocomplete="new-password">
                    </div>

                    <button type="submit" class="btn-secondary">
                        <i class="fa-solid fa-key"></i>
                        Modifier le mot de passe
                    </button>
                </form>
            </section>

            <section class="dash-card">
                <div class="dash-card-header">
                    <div>
                        <h2>Activité du compte</h2>
                        <p>Vous ne reconnaissez pas une connexion ? Changez votre mot de passe.</p>
                    </div>
                </div>

                @include('admin.utilisateurs.partials.login-list', ['logins' => $logins])
            </section>
        </aside>
    </div>
@endsection
