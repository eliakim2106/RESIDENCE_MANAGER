@extends('layouts.admin')

@php
    $current = $pages[$page];
    // Sections du sommaire contenant une erreur
    $blockHasError = fn (string $block): bool => collect($errors->keys())->contains(fn (string $key) => str_starts_with($key, "blocks.{$block}."));
@endphp

@section('title', 'Contenu des pages')

@section('content')
    <div class="admin-page-header">
        <div class="admin-page-heading">
            <span class="admin-page-icon"><i class="fa-solid fa-sliders"></i></span>
            <div>
                <h1>Paramètres du site</h1>
                <p>Textes, images et sections des pages publiques. Un bloc modifié peut toujours retrouver son contenu d’origine.</p>
            </div>
        </div>

        <div class="admin-page-actions">
            <a href="{{ url($current['url']) }}" target="_blank" rel="noopener" class="btn-secondary">
                <i class="fa-solid fa-arrow-up-right-from-square"></i>
                Voir la page
            </a>
        </div>
    </div>

    @include('admin.parametres.partials.tabs')

    @include('partials.flash')

    @if ($errors->any())
        <div class="settings-alert" role="alert">
            <i class="fa-solid fa-circle-exclamation"></i>
            {{ $errors->count() > 1 ? $errors->count().' champs sont à corriger.' : 'Un champ est à corriger.' }} Rien n’a été enregistré.
        </div>
    @endif

    <form method="POST" action="{{ route('admin.parametres.contenu.update', $page) }}" enctype="multipart/form-data" class="admin-form settings-layout" novalidate>
        @csrf
        @method('PUT')

        {{-- ========== Pages et blocs ========== --}}
        <aside class="settings-nav">
            <nav aria-label="Pages du site" class="settings-pages">
                @foreach ($pages as $key => $definition)
                    <a href="{{ route('admin.parametres.contenu', $key) }}" class="{{ $key === $page ? 'is-active' : '' }}" @if ($key === $page) aria-current="page" @endif>
                        <i class="fa-solid {{ $definition['icon'] }}"></i>
                        {{ $definition['label'] }}
                    </a>
                @endforeach
            </nav>

            <nav aria-label="Blocs de la page" class="settings-blocks">
                @foreach ($blocks as $key => $block)
                    <a href="#bloc-{{ $key }}" class="{{ $blockHasError($key) ? 'has-error' : '' }}">
                        {{ $block['label'] }}
                        @if (($block['visible'] ?? false) && ! ($block['content']['visible'] ?? true))
                            <span class="settings-hidden-tag">masqué</span>
                        @endif
                        @if ($blockHasError($key))
                            <span class="settings-nav-error" aria-label="contient une erreur"><i class="fa-solid fa-circle-exclamation"></i></span>
                        @endif
                    </a>
                @endforeach
            </nav>

            <button type="submit" class="btn-primary settings-save">
                <i class="fa-solid fa-floppy-disk"></i>
                Enregistrer la page
            </button>
            <p class="field-help">Les changements s’appliquent immédiatement sur le site.</p>
        </aside>

        {{-- ========== Blocs ========== --}}
        <div class="settings-main">
            @foreach ($blocks as $key => $block)
                @php
                    $content = $block['content'];
                    $prefix = "blocks[{$key}]";
                    $visible = (bool) old("blocks.{$key}.visible", $content['visible'] ?? true);
                    $list = $block['list'] ?? null;
                @endphp

                <section class="form-section content-block {{ ($block['visible'] ?? false) && ! $visible ? 'is-hidden' : '' }}" id="bloc-{{ $key }}" data-content-block>
                    <div class="content-block-header">
                        <div class="form-section-header">
                            <h2>{{ $block['label'] }}</h2>
                            @if ($block['help'] ?? null)
                                <p>{{ $block['help'] }}</p>
                            @endif
                        </div>

                        <div class="content-block-tools">
                            @if ($block['visible'] ?? false)
                                <input type="hidden" name="{{ $prefix }}[visible]" value="0">
                                <label class="switch-field content-visible">
                                    <input type="checkbox" name="{{ $prefix }}[visible]" value="1" @checked($visible) data-visible-toggle>
                                    <span class="switch-ui" aria-hidden="true"></span>
                                    <span class="switch-text"><strong>Afficher</strong></span>
                                </label>
                            @endif
                            <button type="submit" form="reset-{{ $key }}" class="btn-link-muted" title="Rétablir le contenu d’origine de ce bloc">
                                <i class="fa-solid fa-rotate-left"></i>
                                Contenu d’origine
                            </button>
                        </div>
                    </div>

                    @if (! empty($block['fields']))
                        <div class="form-grid">
                            @foreach ($block['fields'] as $field => $spec)
                                @include('admin.parametres.partials.field', [
                                    'spec' => $spec,
                                    'name' => "{$prefix}[{$field}]",
                                    'id' => "{$key}-{$field}",
                                    'value' => old("blocks.{$key}.{$field}", $content[$field] ?? ''),
                                    'errorKey' => "blocks.{$key}.{$field}",
                                ])
                            @endforeach
                        </div>
                    @endif

                    @if ($list)
                        @php $items = old("blocks.{$key}.items", $content['items'] ?? []); @endphp

                        <div class="content-list" data-content-list data-min="{{ $list['min'] }}" data-max="{{ $list['max'] }}">
                            <div class="content-list-head">
                                <h3>{{ $list['label'] }}s <span data-list-count>{{ count($items) }}</span></h3>
                                <small>De {{ $list['min'] }} à {{ $list['max'] }} · utilisez les flèches pour changer l’ordre</small>
                            </div>

                            @error("blocks.{$key}.items")
                                <p class="field-error">{{ $message }}</p>
                            @enderror

                            <ol class="content-items" data-list-items>
                                @foreach ($items as $index => $item)
                                    @include('admin.parametres.partials.list-item', ['key' => $key, 'list' => $list, 'index' => $index, 'item' => $item])
                                @endforeach
                            </ol>

                            {{-- Modèle d'un nouvel élément : __INDEX__ est remplacé par un numéro libre --}}
                            <template data-list-template>
                                @include('admin.parametres.partials.list-item', ['key' => $key, 'list' => $list, 'index' => '__INDEX__', 'item' => []])
                            </template>

                            <button type="button" class="btn-secondary btn-sm content-list-add" data-list-add>
                                <i class="fa-solid fa-plus"></i>
                                Ajouter : {{ mb_strtolower($list['label']) }}
                            </button>
                        </div>
                    @endif
                </section>
            @endforeach
        </div>
    </form>

    {{-- Formulaires « contenu d'origine », hors du formulaire principal --}}
    @foreach ($blocks as $key => $block)
        <form method="POST" action="{{ route('admin.parametres.contenu.reset', [$page, $key]) }}" id="reset-{{ $key }}" class="d-none"
            data-confirm="Rétablir le contenu d’origine du bloc « {{ $block['label'] }} » ? Vos modifications et images envoyées pour ce bloc seront perdues.">
            @csrf
            @method('DELETE')
        </form>
    @endforeach
@endsection
