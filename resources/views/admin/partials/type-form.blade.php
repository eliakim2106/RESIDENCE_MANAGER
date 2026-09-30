{{--
    Formulaire commun création / modification d'un type (établissement ou unité).
    Paramètres : $type (modèle, vide en création), $presets (« etablissement » ou « unite »), $choices (noms proposés), $label.
    Le choix d'un nom remplit l'icône et la description (resources/js/admin/types.js) ; l'aperçu suit en direct (admin.js).
--}}
@php
    $currentName = old('nom', $type->name);
    $currentIcon = old('icon', $type->exists ? $type->fa_icon : '');
    $active = old('status', $type->exists && ! $type->is_active ? 'inactif' : 'actif') === 'actif';

    // Un type créé hors de la liste proposée (données existantes) reste sélectionnable
    if ($currentName && ! in_array($currentName, $choices, true)) {
        $choices[] = $currentName;
    }
@endphp

<div class="form-layout">

    <section class="form-section">
        <header class="form-section-header">
            <h2>Informations</h2>
            <p>Choisissez un type : son icône et sa description sont proposées automatiquement.</p>
        </header>

        <div class="form-grid">
            <div class="form-group full-width">
                <label for="type-name">{{ $label }} <span class="required">*</span></label>
                <select id="type-name" name="nom" data-presets="{{ $presets }}" data-preview-source="name" required>
                    <option value="">Sélectionner un type</option>
                    @foreach ($choices as $choice)
                        <option value="{{ $choice }}" @selected($currentName === $choice)>{{ $choice }}</option>
                    @endforeach
                </select>
                @error('nom') <p class="field-error">{{ $message }}</p> @enderror
            </div>

            <div class="form-group full-width">
                <label for="type-description">Description</label>
                <textarea id="type-description" name="description" rows="4" readonly data-preview-source="description">{{ old('description', $type->description) }}</textarea>
                <p class="field-help">Rédigée automatiquement d'après le type choisi.</p>
                @error('description') <p class="field-error">{{ $message }}</p> @enderror
            </div>
        </div>

        <input type="hidden" id="type-icon" name="icon" value="{{ $currentIcon }}">
    </section>

    <aside class="form-aside">
        <section class="form-section">
            <header class="form-section-header">
                <h2>Aperçu</h2>
                <p>Tel qu'il apparaîtra dans les listes.</p>
            </header>

            <div class="preview-card">
                <span class="preview-icon"><i id="type-icon-preview" class="fa-solid {{ $currentIcon ?: 'fa-question' }}"></i></span>
                <div class="preview-body">
                    <strong data-preview="name" data-preview-empty="Nom du type">{{ $currentName ?: 'Nom du type' }}</strong>
                    <p data-preview="description" data-preview-empty="La description apparaîtra ici.">{{ old('description', $type->description) ?: 'La description apparaîtra ici.' }}</p>
                    <span class="{{ $active ? 'badge-success' : 'badge-neutral' }}" data-preview-status>{{ $active ? 'Actif' : 'Inactif' }}</span>
                </div>
            </div>
        </section>

        <section class="form-section">
            <header class="form-section-header">
                <h2>Publication</h2>
            </header>

            <input type="hidden" name="status" value="inactif">
            <label class="switch-field">
                <input type="checkbox" name="status" value="actif" @checked($active) data-status-toggle>
                <span class="switch-ui" aria-hidden="true"></span>
                <span class="switch-text">
                    <strong>Type actif</strong>
                    <small>Proposé lors de la création des établissements et des unités.</small>
                </span>
            </label>
        </section>
    </aside>

</div>
