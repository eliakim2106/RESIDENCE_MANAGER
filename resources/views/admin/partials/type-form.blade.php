{{--
    Formulaire commun création / modification.
    Paramètres : $type (modèle, vide en création), $presets (« etablissement » ou « unite »), $choices (noms proposés), $label.
--}}
@php
    $currentName = old('nom', $type->name);
    $currentIcon = old('icon', $type->exists ? $type->fa_icon : '');

    // Un type créé hors de la liste proposée (données existantes) reste sélectionnable
    if ($currentName && ! in_array($currentName, $choices, true)) {
        $choices[] = $currentName;
    }
@endphp

<div class="form-grid">

    <div class="form-group">
        <label for="type-name">{{ $label }}</label>

        <select id="type-name" name="nom" data-presets="{{ $presets }}">
            <option value="">Sélectionner un type</option>

            @foreach ($choices as $choice)
                <option value="{{ $choice }}" @selected($currentName === $choice)>{{ $choice }}</option>
            @endforeach
        </select>
    </div>

    <div class="form-group">
        <label>Icône associée</label>

        <div class="icon-preview-card">
            <i id="type-icon-preview" class="fa-solid {{ $currentIcon ?: 'fa-question' }}"></i>
        </div>

        <input type="hidden" id="type-icon" name="icon" value="{{ $currentIcon }}">
    </div>

    <div class="form-group full-width">
        <label for="type-description">Description</label>

        <textarea id="type-description" name="description" rows="5" readonly>{{ old('description', $type->description) }}</textarea>
    </div>

    <div class="form-group">
        <label for="type-status">Statut</label>

        @php

            $status = old('status', $type->exists && ! $type->is_active ? 'inactif' : 'actif');

        @endphp

        <select id="type-status" name="status">
            <option value="actif" @selected($status === 'actif')>Actif</option>
            <option value="inactif" @selected($status === 'inactif')>Inactif</option>
        </select>
    </div>

</div>
