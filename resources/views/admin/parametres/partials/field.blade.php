{{--
    Champ du contenu des pages, selon son type (config/site-content.php).
    Paramètres : $spec (définition), $name (nom HTML), $id, $value, $errorKey (clé de validation, notation pointée), $icons
--}}
@php
    $type = $spec['type'];
    $error = $errors->first($errorKey) ?: $errors->first($errorKey.'_file');
    $wide = in_array($type, ['textarea', 'image'], true);
@endphp

<div class="form-group {{ $wide ? 'form-group-full' : '' }}">
    <label for="{{ $id }}">
        {{ $spec['label'] }}
        @if ($spec['required'] ?? false)
            <span class="required">*</span>
        @endif
    </label>

    @switch($type)
        @case('textarea')
            <textarea name="{{ $name }}" id="{{ $id }}" rows="3" maxlength="{{ $spec['max'] ?? 1000 }}" @required($spec['required'] ?? false) data-char-count>{{ $value }}</textarea>
            <p class="field-help">{{ $spec['help'] ?? '' }} <span data-char-counter></span></p>
            @break

        @case('image')
            <div class="content-image" data-image-field>
                <img src="{{ $site->image($value) }}" alt="" class="content-image-preview" data-image-preview>
                <div class="content-image-actions">
                    <input type="hidden" name="{{ $name }}" value="{{ $value }}">
                    <label class="btn-secondary btn-sm" for="{{ $id }}">
                        <i class="fa-solid fa-image"></i>
                        Changer l’image
                    </label>
                    <input type="file" name="{{ Str::replaceLast(']', '_file]', $name) }}" id="{{ $id }}" accept="image/jpeg,image/png,image/webp" class="visually-hidden" data-image-input>
                    <p class="field-help">{{ $spec['help'] ?? '' }} JPG, PNG ou WebP, 5 Mo maximum.</p>
                </div>
            </div>
            @break

        @case('icon')
            <div class="content-icon-select">
                <span class="content-icon-preview" aria-hidden="true"><i class="fa-solid {{ $value }}" data-icon-preview></i></span>
                <select name="{{ $name }}" id="{{ $id }}" data-icon-select>
                    @foreach ($icons as $icon => $label)
                        <option value="{{ $icon }}" @selected($value === $icon)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            @break

        @case('link')
            <div class="settings-input-icon">
                <i class="fa-solid fa-link"></i>
                <input type="text" name="{{ $name }}" id="{{ $id }}" value="{{ $value }}" maxlength="{{ $spec['max'] ?? 255 }}" @required($spec['required'] ?? false) placeholder="/residences, #contact ou https://…">
            </div>
            @break

        @default
            <input type="text" name="{{ $name }}" id="{{ $id }}" value="{{ $value }}" maxlength="{{ $spec['max'] ?? 255 }}" @required($spec['required'] ?? false)>
    @endswitch

    @if ($error)
        <p class="field-error">{{ $error }}</p>
    @endif
</div>
