@extends('layouts.admin')

@php
    $current = $pages[$page];
    // Bloc contenant une erreur : signalé dans le sommaire et ouvert d'office
    $blockHasError = fn (string $block): bool => collect($errors->keys())->contains(fn (string $key) => str_starts_with($key, "blocks.{$block}."));
@endphp

@section('title', 'Contenu des pages')

@section('content')
    @include('admin.parametres.partials.header', ['lastChange' => $lastChange, 'previewUrl' => url($current['url'])])

    @include('partials.flash')

    @if ($errors->any())
        <div class="prm-alert" role="alert">
            <i class="fa-solid fa-circle-exclamation"></i>
            <div>
                <strong>{{ $errors->count() > 1 ? $errors->count().' champs sont à corriger' : 'Un champ est à corriger' }}</strong>
                <span>Rien n’a été enregistré. Les blocs concernés sont ouverts et signalés.</span>
            </div>
        </div>
    @endif

    <form method="POST" action="{{ route('admin.parametres.contenu.update', $page) }}" enctype="multipart/form-data" id="contentForm" class="admin-form prm-content" novalidate data-dirty-form>
        @csrf
        @method('PUT')

        {{-- ========== Pages et blocs ========== --}}
        <aside class="prm-sidebar">
            <p class="prm-sidebar-title">Pages</p>
            <nav aria-label="Pages du site" class="prm-pages">
                @foreach ($pages as $key => $definition)
                    <a href="{{ route('admin.parametres.contenu', $key) }}" class="{{ $key === $page ? 'is-active' : '' }}" @if ($key === $page) aria-current="page" @endif>
                        <i class="fa-solid {{ $definition['icon'] }}"></i>
                        <span>{{ $definition['label'] }}</span>
                        <small title="Blocs personnalisés">{{ $definition['customized'] }}/{{ count($definition['blocks']) }}</small>
                    </a>
                @endforeach
            </nav>

            <p class="prm-sidebar-title">Blocs de la page</p>
            <nav aria-label="Blocs de la page" class="prm-blocks-nav">
                @foreach ($blocks as $key => $block)
                    @php
                        $hidden = ($block['visible'] ?? false) && ! ($block['content']['visible'] ?? true);
                        $state = match (true) {
                            $blockHasError($key) => ['is-error', 'Contient une erreur'],
                            $hidden => ['is-hidden', 'Masqué sur le site'],
                            $block['change'] !== null => ['is-custom', 'Personnalisé'],
                            default => ['is-original', 'Contenu d’origine'],
                        };
                    @endphp
                    <a href="#bloc-{{ $key }}" data-block-link="{{ $key }}">
                        <span class="prm-state {{ $state[0] }}" title="{{ $state[1] }}" aria-label="{{ $state[1] }}"></span>
                        {{ $block['label'] }}
                    </a>
                @endforeach
            </nav>

            <ul class="prm-legend" aria-label="Légende">
                <li><span class="prm-state is-custom"></span> Personnalisé</li>
                <li><span class="prm-state is-original"></span> Contenu d’origine</li>
                <li><span class="prm-state is-hidden"></span> Masqué</li>
            </ul>
        </aside>

        {{-- ========== Blocs ========== --}}
        <div class="prm-blocks">
            <div class="prm-blocks-toolbar">
                <div>
                    <h2>{{ $current['label'] }}</h2>
                    <p>{{ count($blocks) }} blocs · {{ $current['customized'] }} personnalisé{{ $current['customized'] > 1 ? 's' : '' }}</p>
                </div>
                <div class="prm-toolbar-actions">
                    <button type="button" class="prm-btn-ghost" data-blocks-toggle="open"><i class="fa-solid fa-angles-down"></i> Tout déplier</button>
                    <button type="button" class="prm-btn-ghost" data-blocks-toggle="close"><i class="fa-solid fa-angles-up"></i> Tout replier</button>
                </div>
            </div>

            @foreach ($blocks as $key => $block)
                @php
                    $content = $block['content'];
                    $prefix = "blocks[{$key}]";
                    $visible = (bool) old("blocks.{$key}.visible", $content['visible'] ?? true);
                    $list = $block['list'] ?? null;
                    $items = $list ? old("blocks.{$key}.items", $content['items'] ?? []) : [];
                    $change = $block['change'];
                    $open = $blockHasError($key);
                @endphp

                <section class="prm-block {{ ($block['visible'] ?? false) && ! $visible ? 'is-hidden' : '' }} {{ $open ? 'is-open' : '' }} {{ $blockHasError($key) ? 'has-error' : '' }}"
                    id="bloc-{{ $key }}" data-block="{{ $key }}">

                    <div class="prm-block-head">
                        <button type="button" class="prm-block-toggle" aria-expanded="{{ $open ? 'true' : 'false' }}" aria-controls="bloc-{{ $key }}-body" data-block-toggle>
                            <span class="prm-block-icon"><i class="fa-solid {{ $block['icon'] ?? 'fa-square' }}"></i></span>
                            <span class="prm-block-title">
                                <strong>{{ $block['label'] }}</strong>
                                <span class="prm-block-meta">
                                    @if ($list)
                                        <span class="prm-chip"><span data-list-count>{{ count($items) }}</span> {{ mb_strtolower($list['label']) }}{{ count($items) > 1 ? 's' : '' }}</span>
                                    @endif
                                    @if ($change)
                                        <span class="prm-chip tone-gold" title="{{ $change->updated_at->translatedFormat('d M Y à H:i') }}{{ $change->updater ? ' par '.$change->updater->name : '' }}">
                                            <i class="fa-solid fa-pen"></i> Modifié {{ $change->updated_at->diffForHumans() }}
                                        </span>
                                    @else
                                        <span class="prm-chip">Contenu d’origine</span>
                                    @endif
                                    <span class="prm-chip tone-muted" data-hidden-chip @if (! (($block['visible'] ?? false) && ! $visible)) hidden @endif><i class="fa-solid fa-eye-slash"></i> Masqué</span>
                                    @if ($blockHasError($key))
                                        <span class="prm-chip tone-red"><i class="fa-solid fa-circle-exclamation"></i> À corriger</span>
                                    @endif
                                </span>
                            </span>
                            <i class="fa-solid fa-chevron-down prm-block-chevron"></i>
                        </button>

                        @if ($block['visible'] ?? false)
                            <input type="hidden" name="{{ $prefix }}[visible]" value="0">
                            <label class="prm-switch" title="Afficher ce bloc sur le site">
                                <input type="checkbox" name="{{ $prefix }}[visible]" value="1" @checked($visible) data-visible-toggle>
                                <span class="prm-switch-ui" aria-hidden="true"></span>
                                <span class="prm-switch-label">Affiché</span>
                            </label>
                        @endif
                    </div>

                    <div class="prm-block-body" id="bloc-{{ $key }}-body">
                        @if ($block['help'] ?? null)
                            <p class="prm-block-help"><i class="fa-solid fa-circle-info"></i> {{ $block['help'] }}</p>
                        @endif

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
                        @elseif (! $list)
                            <p class="prm-empty-note">Ce bloc n’a pas de texte à régler : il peut seulement être affiché ou masqué.</p>
                        @endif

                        @if ($list)
                            <div class="prm-list" data-content-list data-min="{{ $list['min'] }}" data-max="{{ $list['max'] }}" data-label="{{ mb_strtolower($list['label']) }}">
                                <div class="prm-list-head">
                                    <h3>{{ $list['label'] }}s</h3>
                                    <small>De {{ $list['min'] }} à {{ $list['max'] }} · glissez-déposez ou utilisez les flèches pour changer l’ordre</small>
                                </div>

                                @error("blocks.{$key}.items")
                                    <p class="field-error">{{ $message }}</p>
                                @enderror

                                <ol class="prm-items" data-list-items>
                                    @foreach ($items as $index => $item)
                                        @include('admin.parametres.partials.list-item', ['key' => $key, 'list' => $list, 'index' => $index, 'item' => $item])
                                    @endforeach
                                </ol>

                                {{-- Modèle d'un nouvel élément : __INDEX__ est remplacé par un numéro libre --}}
                                <template data-list-template>
                                    @include('admin.parametres.partials.list-item', ['key' => $key, 'list' => $list, 'index' => '__INDEX__', 'item' => []])
                                </template>

                                <button type="button" class="prm-list-add" data-list-add>
                                    <i class="fa-solid fa-plus"></i>
                                    Ajouter : {{ mb_strtolower($list['label']) }}
                                </button>
                            </div>
                        @endif

                        <footer class="prm-block-foot">
                            <span>
                                @if ($change)
                                    Modifié le {{ $change->updated_at->translatedFormat('d M Y à H:i') }}@if ($change->updater) par {{ $change->updater->name }}@endif
                                @else
                                    Contenu d’origine du site
                                @endif
                            </span>
                            @if ($change)
                                <button type="submit" form="reset-{{ $key }}" class="prm-btn-ghost prm-btn-danger">
                                    <i class="fa-solid fa-rotate-left"></i>
                                    Rétablir le contenu d’origine
                                </button>
                            @endif
                        </footer>
                    </div>
                </section>
            @endforeach
        </div>
    </form>

    {{-- Formulaires « contenu d'origine », hors du formulaire principal --}}
    @foreach ($blocks as $key => $block)
        @if ($block['change'])
            <form method="POST" action="{{ route('admin.parametres.contenu.reset', [$page, $key]) }}" id="reset-{{ $key }}" class="d-none"
                data-confirm="Rétablir le contenu d’origine du bloc « {{ $block['label'] }} » ? Ses textes et images personnalisés seront supprimés." data-skip-dirty>
                @csrf
                @method('DELETE')
            </form>
        @endif
    @endforeach

    @include('admin.parametres.partials.savebar', ['form' => 'contentForm', 'label' => 'Enregistrer la page'])
@endsection

@push('scripts')
    @vite('resources/js/admin/parametres.js')
@endpush
