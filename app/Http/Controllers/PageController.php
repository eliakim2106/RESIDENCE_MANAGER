<?php

namespace App\Http\Controllers;

use App\Models\SubscriptionPlan;
use App\Support\SiteSettings;
use Illuminate\View\View;

/**
 * Pages d'information du site : questions fréquentes, contact, propriétaires, conditions et confidentialité.
 */
class PageController extends Controller
{
    /**
     * Questions réglées dans Paramètres du site > Contenu des pages, regroupées par thème dans l'ordre de la liste.
     */
    public function faq(SiteSettings $site): View
    {
        return view('site.pages.faq', [
            'questions' => collect($site->content('faq_questions')['items'])
                ->groupBy(fn (array $item): string => trim($item['theme']) ?: 'Questions')
                ->map(fn ($items) => $items->map(fn (array $item): array => [$item['question'], $item['answer']])->all())
                ->all(),
        ]);
    }

    public function contact(): View
    {
        return view('site.pages.contact');
    }

    public function owners(): View
    {
        return view('site.pages.proprietaires', [
            'plans' => SubscriptionPlan::query()->active()->ordered()->get(),
        ]);
    }

    public function terms(): View
    {
        return view('site.pages.conditions');
    }

    public function privacy(): View
    {
        return view('site.pages.confidentialite');
    }
}
