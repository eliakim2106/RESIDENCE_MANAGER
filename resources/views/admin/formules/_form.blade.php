{{--
    Formulaire commun création / modification d'une formule d'abonnement.
    Paramètre : $plan (modèle, vide en création).
--}}
@php
    $active = old('statut', $plan->exists && ! $plan->isActive() ? 'inactif' : 'actif') === 'actif';
    $features = old('avantages', implode("\n", $plan->features ?? []));
@endphp

<div class="form-layout">

    <div class="form-main">
        <section class="form-section">
            <header class="form-section-header">
                <h2>Formule</h2>
                <p>Le nom et la description sont présentés aux propriétaires au moment de choisir.</p>
            </header>

            <div class="form-grid">
                <div class="form-group">
                    <label for="nom">Nom <span class="required">*</span></label>
                    <input type="text" id="nom" name="nom" value="{{ old('nom', $plan->name) }}" maxlength="100" required placeholder="Ex. : Essentiel, Pro, Entreprise">
                    @error('nom') <p class="field-error">{{ $message }}</p> @enderror
                </div>

                <div class="form-group">
                    <label for="position">Ordre d’affichage</label>
                    <input type="number" id="position" name="position" value="{{ old('position', $plan->position ?? 0) }}" min="0" max="100">
                    <p class="field-help">Les formules s’affichent de la plus petite valeur à la plus grande.</p>
                </div>

                <div class="form-group full-width">
                    <label for="description">Description</label>
                    <textarea id="description" name="description" rows="2" maxlength="500" placeholder="Ex. : Pour les propriétaires d’une villa ou de quelques appartements.">{{ old('description', $plan->description) }}</textarea>
                    @error('description') <p class="field-error">{{ $message }}</p> @enderror
                </div>

                <div class="form-group full-width">
                    <label for="avantages">Avantages affichés</label>
                    <textarea id="avantages" name="avantages" rows="4" maxlength="2000" placeholder="Un avantage par ligne, ex. : Calendrier d’occupation">{{ $features }}</textarea>
                    <p class="field-help">Un avantage par ligne. Les limites d’établissements et d’unités sont ajoutées automatiquement.</p>
                    @error('avantages') <p class="field-error">{{ $message }}</p> @enderror
                </div>
            </div>
        </section>

        <section class="form-section">
            <header class="form-section-header">
                <h2>Prix</h2>
                <p>En francs CFA. Laissez le prix annuel vide si la formule n’est proposée qu’au mois.</p>
            </header>

            <div class="form-grid">
                <div class="form-group">
                    <label for="prix_mensuel">Prix mensuel <span class="required">*</span></label>
                    <div class="input-affix">
                        <input type="text" id="prix_mensuel" name="prix_mensuel" inputmode="numeric" value="{{ old('prix_mensuel', $plan->monthly_price) }}" required>
                        <span>FCFA</span>
                    </div>
                    @error('prix_mensuel') <p class="field-error">{{ $message }}</p> @enderror
                </div>

                <div class="form-group">
                    <label for="prix_annuel">Prix annuel</label>
                    <div class="input-affix">
                        <input type="text" id="prix_annuel" name="prix_annuel" inputmode="numeric" value="{{ old('prix_annuel', $plan->yearly_price) }}" placeholder="Facultatif">
                        <span>FCFA</span>
                    </div>
                    <p class="field-help">Ex. : 10 mois payés pour 12 = 2 mois offerts.</p>
                    @error('prix_annuel') <p class="field-error">{{ $message }}</p> @enderror
                </div>

                <div class="form-group">
                    <label for="jours_essai">Essai gratuit</label>
                    <div class="input-affix">
                        <input type="text" id="jours_essai" name="jours_essai" inputmode="numeric" value="{{ old('jours_essai', $plan->trial_days) }}">
                        <span>jours</span>
                    </div>
                    <p class="field-help">Offert une seule fois, au premier abonnement du propriétaire. 0 : pas d’essai.</p>
                    @error('jours_essai') <p class="field-error">{{ $message }}</p> @enderror
                </div>

                <div class="form-group">
                    <label for="commission">Commission sur les réservations</label>
                    <div class="input-affix">
                        <input type="text" id="commission" name="commission" inputmode="decimal" value="{{ old('commission', rtrim(rtrim((string) $plan->commission_rate, '0'), '.') ?: 0) }}">
                        <span>%</span>
                    </div>
                    <p class="field-help">Facultatif : pour un modèle mixte abonnement + commission. 0 : aucune commission.</p>
                    @error('commission') <p class="field-error">{{ $message }}</p> @enderror
                </div>
            </div>
        </section>
    </div>

    <aside class="form-aside">
        <section class="form-section">
            <header class="form-section-header">
                <h2>Limites</h2>
                <p>Laissez vide pour « illimité ».</p>
            </header>

            <div class="form-group">
                <label for="max_etablissements">Établissements</label>
                <input type="text" id="max_etablissements" name="max_etablissements" inputmode="numeric" value="{{ old('max_etablissements', $plan->max_properties) }}" placeholder="Illimité">
                @error('max_etablissements') <p class="field-error">{{ $message }}</p> @enderror
            </div>

            <div class="form-group plan-limit-gap">
                <label for="max_unites">Unités (tous établissements)</label>
                <input type="text" id="max_unites" name="max_unites" inputmode="numeric" value="{{ old('max_unites', $plan->max_units) }}" placeholder="Illimité">
                @error('max_unites') <p class="field-error">{{ $message }}</p> @enderror
            </div>
        </section>

        <section class="form-section">
            <header class="form-section-header">
                <h2>Publication</h2>
            </header>

            <input type="hidden" name="statut" value="inactif">
            <label class="switch-field">
                <input type="checkbox" name="statut" value="actif" @checked($active)>
                <span class="switch-ui" aria-hidden="true"></span>
                <span class="switch-text">
                    <strong>Formule proposée</strong>
                    <small>Visible par les propriétaires. Désactivée, elle reste valable pour ceux qui l’ont déjà.</small>
                </span>
            </label>

            <input type="hidden" name="mise_en_avant" value="0">
            <label class="switch-field plan-limit-gap">
                <input type="checkbox" name="mise_en_avant" value="1" @checked(old('mise_en_avant', $plan->is_featured))>
                <span class="switch-ui" aria-hidden="true"></span>
                <span class="switch-text">
                    <strong>Recommandée</strong>
                    <small>Mise en avant avec le badge « Recommandée ».</small>
                </span>
            </label>
        </section>
    </aside>

</div>
