<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ReviewStatus;
use App\Http\Controllers\Controller;
use App\Models\Property;
use App\Models\Review;
use App\Models\User;
use App\Notifications\ReviewActivity;
use App\Support\ExcelExport;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Notification;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Avis des voyageurs dans l'administration.
 * Propriétaire : avis de ses établissements, réponse publique, signalement d'un avis abusif.
 * Administrateurs : tous les avis, masquage motivé et remise en ligne (modération).
 */
class ReviewController extends Controller
{
    /**
     * Onglets : clé de l'adresse => libellé.
     *
     * @var array<string, string>
     */
    public const TABS = [
        'tous' => 'Tous',
        'sans-reponse' => 'Sans réponse',
        'signales' => 'Signalés',
        'masques' => 'Masqués',
    ];

    /**
     * Filtre par note : clé => [libellé, note minimale, note maximale].
     *
     * @var array<string, array{0: string, 1: int, 2: int}>
     */
    public const RATINGS = [
        'excellents' => ['9 et 10', 9, 10],
        'bons' => ['7 et 8', 7, 8],
        'faibles' => ['6 et moins', 1, 6],
    ];

    public function index(Request $request): View
    {
        Gate::authorize('viewAny', Review::class);

        $user = $request->user();
        [$tab, $search, $property, $rating] = $this->filters($request);

        $counts = collect(self::TABS)->map(fn (string $label, string $key): int => $this->query($user, $search, $property, $rating, $key)->count())->all();
        $published = $this->scoped($user)->where('statut', ReviewStatus::Approved);

        return view('admin.avis.index', [
            'reviews' => $this->query($user, $search, $property, $rating, $tab)
                ->with(['user', 'property', 'reservation'])
                ->latest()
                ->paginate(12)
                ->withQueryString(),
            'statut' => $tab,
            'search' => $search,
            'property' => $property,
            'rating' => $rating,
            'counts' => $counts,
            'properties' => $this->properties($user),
            'summary' => [
                'average' => ($average = (clone $published)->avg('rating')) !== null ? round((float) $average, 1) : null,
                'published' => (clone $published)->count(),
                'thisMonth' => $this->scoped($user)->where('created_at', '>=', now()->startOfMonth())->count(),
                'unanswered' => (clone $published)->whereNull('owner_reply')->count(),
                'reported' => (clone $published)->whereNotNull('reported_at')->count(),
                'replyRate' => ($total = (clone $published)->count()) > 0
                    ? (int) round((clone $published)->whereNotNull('owner_reply')->count() / $total * 100)
                    : null,
            ],
        ]);
    }

    public function show(Review $review): View
    {
        Gate::authorize('view', $review);

        $review->load(['user', 'property.owner', 'reservation', 'reporter', 'moderator']);

        return view('admin.avis.show', ['review' => $review]);
    }

    /**
     * Réponse publique de l'établissement ; une réponse vide la retire.
     */
    public function reply(Request $request, Review $review): RedirectResponse
    {
        Gate::authorize('reply', $review);

        $validated = $request->validate(
            ['reponse' => ['nullable', 'string', 'min:10', 'max:1500']],
            ['reponse.min' => 'Votre réponse doit faire au moins 10 caractères.'],
        );
        $reply = trim((string) ($validated['reponse'] ?? ''));
        $isFirstReply = $review->owner_reply === null;

        if ($reply === '') {
            $review->update(['owner_reply' => null, 'replied_at' => null]);

            return back()->with('success', 'Votre réponse a été retirée.');
        }

        $review->update(['owner_reply' => $reply, 'replied_at' => now()]);

        if ($isFirstReply) {
            $review->user?->notify(new ReviewActivity($review, ReviewActivity::REPLIED_FOR_GUEST));
        }

        return back()->with('success', $isFirstReply ? 'Votre réponse est publiée sous l’avis.' : 'Votre réponse est modifiée.');
    }

    /**
     * Signalement d'un avis abusif par le propriétaire : les administrateurs sont prévenus.
     */
    public function report(Request $request, Review $review): RedirectResponse
    {
        Gate::authorize('report', $review);

        $validated = $request->validate(
            ['motif' => ['required', 'string', 'min:10', 'max:500']],
            ['motif.required' => 'Expliquez pourquoi cet avis vous semble abusif.', 'motif.min' => 'Précisez le motif en quelques mots (10 caractères au moins).'],
        );

        $review->update(['reported_at' => now(), 'report_reason' => trim($validated['motif']), 'reported_by' => $request->user()->id]);

        Notification::send(User::query()->backOffice()->get(), new ReviewActivity($review->fresh(['property', 'user', 'reporter']), ReviewActivity::REPORTED_FOR_ADMIN));

        return back()->with('success', 'L’avis est signalé à l’équipe DS HOLDING, qui va l’examiner.');
    }

    /**
     * Masquer un avis (motif obligatoire) : il disparaît du site et des notes de l'établissement.
     */
    public function hide(Request $request, Review $review): RedirectResponse
    {
        Gate::authorize('moderate', $review);

        $validated = $request->validate(
            ['motif' => ['required', 'string', 'min:5', 'max:500']],
            ['motif.required' => 'Indiquez le motif du masquage : il est communiqué à l’établissement.'],
        );

        $review->update([
            'statut' => ReviewStatus::Rejected,
            'moderation_note' => trim($validated['motif']),
            'moderated_at' => now(),
            'moderated_by' => $request->user()->id,
        ]);
        $review->property?->refreshRating();

        $review->property?->owner?->notify(new ReviewActivity($review, ReviewActivity::HIDDEN_FOR_OWNER));
        $review->user?->notify(new ReviewActivity($review, ReviewActivity::HIDDEN_FOR_GUEST));

        return back()->with('success', 'L’avis est masqué : il n’apparaît plus sur le site ni dans la note de l’établissement.');
    }

    /**
     * Remettre un avis en ligne, ou le maintenir malgré un signalement (le signalement est clos).
     */
    public function publish(Request $request, Review $review): RedirectResponse
    {
        Gate::authorize('moderate', $review);

        $wasReported = $review->isReported();
        $wasHidden = ! $review->isPublished();

        $review->update([
            'statut' => ReviewStatus::Approved,
            'reported_at' => null,
            'report_reason' => null,
            'reported_by' => null,
            'moderated_at' => now(),
            'moderated_by' => $request->user()->id,
        ]);
        $review->property?->refreshRating();

        if ($wasReported || $wasHidden) {
            $review->property?->owner?->notify(new ReviewActivity($review, ReviewActivity::PUBLISHED_FOR_OWNER));
        }

        return back()->with('success', $wasHidden ? 'L’avis est de nouveau en ligne.' : 'Le signalement est clos : l’avis reste en ligne.');
    }

    public function export(Request $request): BinaryFileResponse
    {
        Gate::authorize('viewAny', Review::class);

        [$tab, $search, $property, $rating] = $this->filters($request);

        return ExcelExport::download('avis', 'Avis des voyageurs', [
            ['label' => 'Date', 'type' => 'date'],
            ['label' => 'Établissement', 'width' => 26],
            ['label' => 'Voyageur', 'width' => 22],
            ['label' => 'Réservation', 'width' => 16],
            ['label' => 'Note', 'type' => 'number', 'width' => 8],
            ['label' => 'Propreté', 'type' => 'number', 'width' => 10],
            ['label' => 'Confort', 'type' => 'number', 'width' => 10],
            ['label' => 'Emplacement', 'type' => 'number', 'width' => 12],
            ['label' => 'Accueil', 'type' => 'number', 'width' => 10],
            ['label' => 'Qualité-prix', 'type' => 'number', 'width' => 12],
            ['label' => 'Titre', 'width' => 26],
            ['label' => 'Commentaire', 'width' => 60],
            ['label' => 'Réponse de l’établissement', 'width' => 50],
            ['label' => 'Statut', 'width' => 10],
            ['label' => 'Signalement', 'width' => 40],
        ], $this->query($request->user(), $search, $property, $rating, $tab)
            ->with(['user', 'property', 'reservation'])
            ->latest()
            ->lazy()
            ->map(fn (Review $review): array => [
                $review->created_at,
                $review->property?->name,
                $review->user?->name,
                $review->reservation?->reference,
                $review->rating,
                $review->cleanliness,
                $review->comfort,
                $review->location,
                $review->staff,
                $review->value_for_money,
                $review->title,
                $review->comment,
                $review->owner_reply,
                $review->statut,
                $review->report_reason,
            ]));
    }

    /*
    |--------------------------------------------------------------------------
    | OUTILS
    |--------------------------------------------------------------------------
    */

    /**
     * @return array{0: string, 1: string, 2: ?Property, 3: ?string}
     */
    private function filters(Request $request): array
    {
        $tab = array_key_exists((string) $request->query('statut'), self::TABS) ? (string) $request->query('statut') : 'tous';
        $property = filled($request->query('etablissement'))
            ? $this->properties($request->user())->firstWhere('slug', (string) $request->query('etablissement'))
            : null;
        $rating = array_key_exists((string) $request->query('note'), self::RATINGS) ? (string) $request->query('note') : null;

        return [$tab, trim((string) $request->query('search', '')), $property, $rating];
    }

    /**
     * Avis visibles par ce compte : tous pour un administrateur, ceux de ses établissements pour un propriétaire.
     *
     * @return Builder<Review>
     */
    private function scoped(User $user): Builder
    {
        return Review::query()->unless($user->isAdmin(), fn (Builder $query) => $query->forOwner($user));
    }

    /**
     * @return Builder<Review>
     */
    private function query(User $user, string $search, ?Property $property, ?string $rating, string $tab): Builder
    {
        $query = $this->scoped($user)
            ->when($property, fn (Builder $query, Property $property) => $query->where('property_id', $property->id))
            ->when($rating, fn (Builder $query, string $rating) => $query->whereBetween('rating', [self::RATINGS[$rating][1], self::RATINGS[$rating][2]]))
            ->when($search !== '', fn (Builder $query) => $query->where(fn (Builder $query) => $query
                ->where('title', 'like', "%{$search}%")
                ->orWhere('comment', 'like', "%{$search}%")
                ->orWhereHas('user', fn (Builder $query) => $query->where('name', 'like', "%{$search}%"))
                ->orWhereHas('reservation', fn (Builder $query) => $query->where('reference', 'like', "%{$search}%"))));

        return match ($tab) {
            'sans-reponse' => $query->where('statut', ReviewStatus::Approved)->whereNull('owner_reply'),
            'signales' => $query->where('statut', ReviewStatus::Approved)->whereNotNull('reported_at'),
            'masques' => $query->where('statut', ReviewStatus::Rejected),
            default => $query,
        };
    }

    /**
     * Établissements proposés dans le filtre : ceux qui ont des avis, parmi ceux du compte.
     *
     * @return Collection<int, Property>
     */
    private function properties(User $user)
    {
        return Property::query()
            ->unless($user->isAdmin(), fn (Builder $query) => $query->where('owner_id', $user->id))
            ->whereHas('reviews')
            ->orderBy('name')
            ->get(['id', 'name', 'slug']);
    }
}
