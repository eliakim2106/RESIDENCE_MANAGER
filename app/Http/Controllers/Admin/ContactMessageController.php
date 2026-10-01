<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ContactMessageStatus;
use App\Http\Controllers\Controller;
use App\Models\ContactMessage;
use App\Models\NewsletterSubscriber;
use App\Support\ExcelExport;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Messages reçus par le formulaire de contact du site, et abonnés à la lettre d'information.
 */
class ContactMessageController extends Controller
{
    /**
     * Onglets : clé de l'adresse => [libellé, statut filtré].
     *
     * @var array<string, array{0: string, 1: ?ContactMessageStatus}>
     */
    public const TABS = [
        'tous' => ['Tous', null],
        'nouveaux' => ['Nouveaux', ContactMessageStatus::New],
        'lus' => ['Lus', ContactMessageStatus::Read],
        'repondus' => ['Répondus', ContactMessageStatus::Answered],
    ];

    public function index(Request $request): View
    {
        $tab = array_key_exists((string) $request->query('statut'), self::TABS) ? (string) $request->query('statut') : 'tous';
        $search = trim((string) $request->query('search', ''));

        $counts = collect(self::TABS)->map(fn (array $definition): int => $this->searched($search)
            ->when($definition[1], fn (Builder $query, ContactMessageStatus $status) => $query->where('statut', $status))
            ->count())->all();

        return view('admin.messages.index', [
            'messages' => $this->searched($search)
                ->when(self::TABS[$tab][1], fn (Builder $query, ContactMessageStatus $status) => $query->where('statut', $status))
                ->latest()
                ->paginate(15)
                ->withQueryString(),
            'statut' => $tab,
            'search' => $search,
            'counts' => $counts,
            'tabs' => collect(self::TABS)->map(fn (array $definition): string => $definition[0])->all(),
            'subscribers' => NewsletterSubscriber::query()->whereNull('unsubscribed_at')->count(),
        ]);
    }

    /**
     * Ouvrir un message le marque comme lu.
     */
    public function show(ContactMessage $message): View
    {
        if ($message->statut === ContactMessageStatus::New) {
            $message->update(['statut' => ContactMessageStatus::Read]);
        }

        return view('admin.messages.show', ['message' => $message]);
    }

    public function update(Request $request, ContactMessage $message): RedirectResponse
    {
        $validated = $request->validate(['statut' => ['required', Rule::enum(ContactMessageStatus::class)]]);
        $message->update(['statut' => $validated['statut']]);

        return back()->with('success', 'Message marqué « '.mb_strtolower($message->statut->label()).' ».');
    }

    public function destroy(ContactMessage $message): RedirectResponse
    {
        $message->delete();

        return redirect()->route('admin.messages.index')->with('success', 'Le message de '.$message->name.' a été supprimé.');
    }

    public function export(Request $request): BinaryFileResponse
    {
        $search = trim((string) $request->query('search', ''));

        return ExcelExport::download('messages-contact', 'Messages de contact', [
            ['label' => 'Reçu le', 'type' => 'datetime'],
            ['label' => 'Nom', 'width' => 24],
            ['label' => 'Email', 'width' => 28],
            ['label' => 'Téléphone', 'width' => 18],
            ['label' => 'Sujet', 'width' => 30],
            ['label' => 'Message', 'width' => 60],
            ['label' => 'Statut', 'width' => 12],
        ], $this->searched($search)->latest()->lazy()->map(fn (ContactMessage $message): array => [
            $message->created_at,
            $message->name,
            $message->email,
            $message->formattedPhone(),
            $message->subject,
            $message->message,
            $message->statut,
        ]));
    }

    /**
     * Abonnés actifs à la lettre d'information.
     */
    public function subscribers(): BinaryFileResponse
    {
        return ExcelExport::download('abonnes-newsletter', 'Abonnés à la lettre d’information', [
            ['label' => 'Email', 'width' => 34],
            ['label' => 'Inscrit le', 'type' => 'datetime'],
        ], NewsletterSubscriber::query()->whereNull('unsubscribed_at')->latest()->lazy()->map(fn (NewsletterSubscriber $subscriber): array => [
            $subscriber->email,
            $subscriber->created_at,
        ]));
    }

    /**
     * @return Builder<ContactMessage>
     */
    private function searched(string $search): Builder
    {
        return ContactMessage::query()->when($search !== '', fn (Builder $query) => $query->where(fn (Builder $query) => $query
            ->where('name', 'like', "%{$search}%")
            ->orWhere('email', 'like', "%{$search}%")
            ->orWhere('subject', 'like', "%{$search}%")
            ->orWhere('message', 'like', "%{$search}%")));
    }
}
