{{--
    Élément d'une liste du contenu des pages : résumé (vignette ou icône, titre, sous-titre) qui se déplie pour modifier.
    Paramètres : $key (bloc), $list (définition), $index, $item (valeurs)
--}}
@php
    $summary = $list['summary'] ?? [];
    $value = fn (string $field) => $item[$field] ?? '';
    $isNew = $item === [];
    // Élément contenant une erreur : déplié d'office
    $hasError = collect($errors->keys())->contains(fn (string $error) => str_starts_with($error, "blocks.{$key}.items.{$index}."));
@endphp

<li class="prm-item {{ $isNew || $hasError ? 'is-open' : '' }} {{ $hasError ? 'has-error' : '' }}" data-list-item>
    <div class="prm-item-head">
        <span class="prm-item-handle" data-drag-handle title="Glisser pour déplacer" aria-hidden="true"><i class="fa-solid fa-grip-vertical"></i></span>

        <button type="button" class="prm-item-summary" data-item-toggle aria-expanded="{{ $isNew || $hasError ? 'true' : 'false' }}">
            @if ($summary['thumb'] ?? null)
                <img src="{{ $site->image($value($summary['thumb'])) }}" alt="" class="prm-item-thumb" data-summary-thumb>
            @elseif ($summary['icon'] ?? null)
                <span class="prm-item-icon"><i class="fa-solid {{ $value($summary['icon']) ?: array_key_first($icons) }}" data-summary-icon></i></span>
            @endif
            <span class="prm-item-text">
                <small>{{ $list['label'] }} <span data-item-number></span></small>
                <strong data-summary-title data-empty="Sans titre">{{ $value($summary['title'] ?? '') ?: 'Sans titre' }}</strong>
                @if ($summary['subtitle'] ?? null)
                    <span data-summary-subtitle>{{ Str::limit($value($summary['subtitle']), 90) }}</span>
                @endif
            </span>
            @if ($hasError)
                <span class="prm-chip tone-red"><i class="fa-solid fa-circle-exclamation"></i> À corriger</span>
            @endif
        </button>

        <span class="prm-item-tools">
            <button type="button" class="prm-icon-btn" data-item-up title="Monter" aria-label="Monter"><i class="fa-solid fa-arrow-up"></i></button>
            <button type="button" class="prm-icon-btn" data-item-down title="Descendre" aria-label="Descendre"><i class="fa-solid fa-arrow-down"></i></button>
            <button type="button" class="prm-icon-btn is-danger" data-item-remove title="Retirer" aria-label="Retirer"><i class="fa-solid fa-trash"></i></button>
        </span>
    </div>

    <div class="prm-item-body">
        <div class="form-grid">
            @foreach ($list['fields'] as $field => $spec)
                @include('admin.parametres.partials.field', [
                    'spec' => $spec,
                    'name' => "blocks[{$key}][items][{$index}][{$field}]",
                    'id' => "{$key}-{$index}-{$field}",
                    'value' => $item[$field] ?? ($spec['type'] === 'icon' ? array_key_first($icons) : ''),
                    'errorKey' => "blocks.{$key}.items.{$index}.{$field}",
                    'summaryRole' => array_search($field, $summary, true) ?: null,
                ])
            @endforeach
        </div>
    </div>
</li>
