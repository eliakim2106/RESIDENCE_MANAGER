{{-- Élément d'une liste du contenu des pages. Paramètres : $key (bloc), $list (définition), $index, $item (valeurs) --}}
<li class="content-item" data-list-item>
    <div class="content-item-head">
        <strong>{{ $list['label'] }} <span data-item-number></span></strong>
        <span class="content-item-tools">
            <button type="button" class="action-btn" data-item-up title="Monter" aria-label="Monter"><i class="fa-solid fa-arrow-up"></i></button>
            <button type="button" class="action-btn" data-item-down title="Descendre" aria-label="Descendre"><i class="fa-solid fa-arrow-down"></i></button>
            <button type="button" class="action-btn delete" data-item-remove title="Retirer" aria-label="Retirer"><i class="fa-solid fa-trash"></i></button>
        </span>
    </div>

    <div class="form-grid">
        @foreach ($list['fields'] as $field => $spec)
            @include('admin.parametres.partials.field', [
                'spec' => $spec,
                'name' => "blocks[{$key}][items][{$index}][{$field}]",
                'id' => "{$key}-{$index}-{$field}",
                'value' => $item[$field] ?? ($spec['type'] === 'icon' ? array_key_first($icons) : ''),
                'errorKey' => "blocks.{$key}.items.{$index}.{$field}",
            ])
        @endforeach
    </div>
</li>
