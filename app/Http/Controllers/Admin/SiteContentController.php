<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\SiteContentEditor;
use App\Support\SiteSettings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Contenu des pages du site (super administrateur) : diaporama, sections de l'accueil, en-têtes, questions fréquentes…
 * Les blocs et leurs champs sont décrits dans config/site-content.php.
 */
class SiteContentController extends Controller
{
    public function __construct(private SiteContentEditor $editor) {}

    public function edit(SiteSettings $site, string $page): View
    {
        return view('admin.parametres.contenu', [
            'pages' => SiteContentEditor::pages(),
            'page' => $page,
            'blocks' => collect($this->editor->blocks($page))
                ->map(fn (array $definition, string $block): array => [...$definition, 'content' => $site->content($block)])
                ->all(),
            'icons' => config('site-content.icons'),
        ]);
    }

    public function update(Request $request, string $page): RedirectResponse
    {
        $request->validate($this->editor->rules($page), [
            '*.regex' => 'Format non valide.',
            'blocks.*.*.regex' => 'Indiquez un chemin du site (/residences), une ancre (#contact) ou une adresse complète (https://…).',
            'blocks.*.items.*.*.regex' => 'Indiquez un chemin du site (/residences), une ancre (#contact) ou une adresse complète (https://…).',
            'blocks.*.items.min' => 'Gardez au moins :min élément(s) dans cette liste.',
            'blocks.*.items.max' => 'Cette liste accepte :max éléments au plus.',
        ], $this->editor->attributes($request, $page));

        $this->editor->save($request, $page);

        return redirect()->route('admin.parametres.contenu', $page)
            ->with('success', 'Le contenu de la page « '.SiteContentEditor::pages()[$page]['label'].' » est enregistré.');
    }

    public function reset(string $page, string $block): RedirectResponse
    {
        abort_unless(in_array($block, SiteContentEditor::pages()[$page]['blocks'], true), 404);

        $this->editor->reset($block);

        return redirect()->to(route('admin.parametres.contenu', $page).'#bloc-'.$block)
            ->with('success', 'Le bloc « '.config("site-content.blocks.{$block}.label").' » a retrouvé son contenu d’origine.');
    }
}
