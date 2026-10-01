{{--
    Champ du contenu des pages, selon son type (config/site-content.php).
    Paramètres : $spec (définition), $name (nom HTML), $id, $value, $errorKey (clé de validation, notation pointée), $icons
    $summaryRole (facultatif) : title, subtitle, thumb ou icon ; le résumé de l'élément de liste suit ce champ en direct.
--}}
@php
    $type = $spec['type'];
    $error = $errors->first($errorKey) ?: $errors->first($errorKey.'_file');
    $wide = in_array($type, ['textarea', 'image'], true);
    $summaryRole ??= null;
    $summaryAttribute = $summaryRole ? 'data-summary-source='.$summaryRole : '';
@endphp

<div class="form-group {{ $wide ? 'form-group-full' : '' }} {{ $error ? 'has-error' : '' }}">
    <label for="{{ $id }}">
        {{ $spec['label'] }}
        @if ($spec['required'] ?? false)
            <span class="required">*</span>
        @endif
    </label>

    @switch($type)
        @case('textarea')
            <textarea name="{{ $name }}" id="{{ $id }}" rows="3" maxlength="{{ $spec['max'] ?? 1000 }}" @required($spec['required'] ?? false) data-char-count {{ $summaryAttribute }}>{{ $value }}</textarea>
            <p class="field-help prm-help-row"><span>{{ $spec['help'] ?? '' }}</span> <span data-char-counter></span></p>
            @break

        @case('image')
            <div class="prm-image" data-image-field>
                <img src="{{ $site->image($value) }}" alt="" class="prm-image-preview" data-image-preview>
                <div class="prm-image-body">
                    <input type="hidden" name="{{ $name }}" value="{{ $value }}">
                    <label class="prm-image-button" for="{{ $id }}">
                        <i class="fa-solid fa-cloud-arrow-up"></i>
                        <span data-image-label>Remplacer l’image</span>
                    </label>
                    <input type="file" name="{{ Str::replaceLast(']', '_file]', $name) }}" id="{{ $id }}" accept="image/jpeg,image/png,image/webp" class="visually-hidden" data-image-input {{ $summaryAttribute }}>
                    <p class="field-help">{{ $spec['help'] ?? '' }} JPG, PNG ou WebP, 5 Mo maximum.</p>
                    <p class="prm-image-pending" data-image-pending hidden><i class="fa-solid fa-circle-info"></i> Nouvelle image : elle sera envoyée à l’enregistrement.</p>
                </div>
            </div>
            @break

        @case('icon')
            <div class="prm-icon-select">
                <span class="prm-icon-preview" aria-hidden="true"><i class="fa-solid {{ $value }}" data-icon-preview></i></span>
                <select name="{{ $name }}" id="{{ $id }}" data-icon-select {{ $summaryAttribute }}>
                    @foreach ($icons as $icon => $label)
                        <option value="{{ $icon }}" @selected($value === $icon)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            @break

        @case('link')
            <div class="settings-input-icon">
                <i class="fa-solid fa-link"></i>
                <input type="text" name="{{ $name }}" id="{{ $id }}" value="{{ $value }}" maxlength="{{ $spec['max'] ?? 255 }}" @required($spec['required'] ?? false) placeholder="/residences, #contact ou https://…" {{ $summaryAttribute }}>
            </div>
            @break

        @default
            <input type="text" name="{{ $name }}" id="{{ $id }}" value="{{ $value }}" maxlength="{{ $spec['max'] ?? 255 }}" @required($spec['required'] ?? false) {{ $summaryAttribute }}>
    @endswitch

    @if ($error)
        <p class="field-error">{{ $error }}</p>
    @endif
</div>
