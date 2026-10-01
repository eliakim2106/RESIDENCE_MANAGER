<?php

namespace App\Http\Controllers;

use App\Exceptions\WorkflowException;
use App\Models\Property;
use App\Rules\PhoneNumberRule;
use App\Services\BookingEngine;
use App\Support\PhoneNumber;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Throwable;

/**
 * Réservation depuis la fiche d'une résidence : récapitulatif puis création de la demande.
 *
 * Un visiteur sans compte est dirigé vers la création d'un compte client ; il revient ensuite sur ce récapitulatif,
 * avec les dates et les logements choisis.
 */
class BookingController extends Controller
{
    public function __construct(private BookingEngine $engine) {}

    public function checkout(Request $request, Property $residence): View|RedirectResponse
    {
        if ($redirect = $this->guard($request, $residence)) {
            return $redirect;
        }

        try {
            [$arrival, $departure, $selection, $adults, $children] = $this->stay($request);
            $lines = $this->lines($residence, $selection, $arrival, $departure);
        } catch (WorkflowException $exception) {
            return redirect()->route('residences.show', [$residence, ...$request->only('arrivee', 'depart', 'adultes', 'enfants')])
                ->with('error', $exception->getMessage())
                ->withFragment('logements');
        }

        $residence->load(['city', 'coverImage', 'propertyType']);

        return view('site.checkout', [
            'residence' => $residence,
            'arrival' => $arrival,
            'departure' => $departure,
            'adults' => $adults,
            'children' => $children,
            'lines' => $lines,
            'totals' => $this->engine->totals($lines),
            'user' => $request->user(),
            'serviceRate' => BookingEngine::serviceFeeRate(),
        ]);
    }

    public function store(Request $request, Property $residence): RedirectResponse
    {
        if ($redirect = $this->guard($request, $residence)) {
            return $redirect;
        }

        $request->merge([
            'indicatif_telephone' => $request->input('indicatif_telephone') ?: PhoneNumber::defaultDial(),
            'telephone' => PhoneNumber::normalize((string) $request->input('indicatif_telephone'), (string) $request->input('telephone')),
        ]);

        $validated = $request->validate([
            'nom' => ['required', 'string', 'max:191'],
            'email' => ['required', 'email', 'max:191'],
            'indicatif_telephone' => ['required', Rule::in(array_keys(config('phone.countries')))],
            'telephone' => ['required', new PhoneNumberRule],
            'heure_arrivee' => ['nullable', 'date_format:H:i'],
            'demandes' => ['nullable', 'string', 'max:1000'],
            'conditions' => ['accepted'],
        ], [
            'nom.required' => 'Indiquez le nom du voyageur principal.',
            'telephone.required' => 'Le numéro permet à l’établissement de vous joindre pour votre arrivée.',
            'conditions.accepted' => 'Acceptez les conditions de réservation et d’annulation pour continuer.',
        ]);

        try {
            [$arrival, $departure, $selection, $adults, $children] = $this->stay($request);

            $reservation = $this->engine->book($request->user(), $residence, $selection, $arrival, $departure, [
                'adults' => $adults,
                'children' => $children,
                'name' => trim($validated['nom']),
                'email' => mb_strtolower(trim($validated['email'])),
                'phone' => $validated['telephone'],
                'dial' => $validated['indicatif_telephone'],
                'arrival_time' => $validated['heure_arrivee'] ?? null,
                'requests' => $validated['demandes'] ?? null,
            ]);
        } catch (WorkflowException $exception) {
            return back()->withInput()->with('error', $exception->getMessage());
        }

        return redirect()->route('client.reservations.show', $reservation)->with('success', 'Votre demande est envoyée à '.$residence->name.'. Vous recevrez un email dès qu’elle sera confirmée ; vous pouvez déjà la régler en ligne.');
    }

    /*
    |--------------------------------------------------------------------------
    | OUTILS
    |--------------------------------------------------------------------------
    */

    /**
     * Visiteur : création de compte puis retour ici. Compte non client : réservation impossible.
     */
    private function guard(Request $request, Property $residence): ?RedirectResponse
    {
        abort_unless(Property::query()->onSite()->whereKey($residence->id)->exists(), 404);

        if (! Auth::check()) {
            $request->session()->put('url.intended', route('residences.checkout', [$residence, ...$request->except('_token')]));

            return redirect()->route('register.client')->with('info', 'Créez votre compte client (ou connectez-vous) pour finaliser votre réservation. Vos dates et vos logements sont conservés.');
        }

        if (! $request->user()->isClient()) {
            return redirect()->route('residences.show', $residence)->with('error', 'Les réservations se font avec un compte client. Vous êtes connecté avec un compte '.mb_strtolower($request->user()->role->label()).'.');
        }

        if (! $request->user()->hasVerifiedEmail()) {
            $request->session()->put('url.intended', $request->fullUrl());

            return redirect()->route('verification.notice');
        }

        if ($request->user()->needsProfileCompletion()) {
            $request->session()->put('url.intended', $request->isMethod('GET') ? $request->fullUrl() : route('residences.show', $residence));

            return redirect()->route('client.profile.complete');
        }

        return null;
    }

    /**
     * Dates, logements et voyageurs demandés.
     *
     * @return array{0: CarbonImmutable, 1: CarbonImmutable, 2: array<int, int>, 3: int, 4: int}
     */
    private function stay(Request $request): array
    {
        try {
            $arrival = CarbonImmutable::parse((string) $request->input('arrivee'))->startOfDay();
            $departure = CarbonImmutable::parse((string) $request->input('depart'))->startOfDay();
        } catch (Throwable) {
            throw new WorkflowException('Choisissez vos dates d’arrivée et de départ.');
        }

        if (! $request->filled('arrivee') || ! $request->filled('depart')) {
            throw new WorkflowException('Choisissez vos dates d’arrivée et de départ.');
        }

        $this->engine->assertDates($arrival, $departure);

        $selection = collect((array) $request->input('unites', []))
            ->mapWithKeys(fn ($quantity, $unitId): array => [(int) $unitId => max(0, min(20, (int) $quantity))])
            ->filter()
            ->all();

        if ($selection === []) {
            throw new WorkflowException('Choisissez au moins un logement dans la liste.');
        }

        return [$arrival, $departure, $selection, max(1, min(30, (int) $request->input('adultes', 1))), max(0, min(20, (int) $request->input('enfants', 0)))];
    }

    /**
     * Devis des logements choisis (vérifie qu'ils sont encore disponibles).
     *
     * @param  array<int, int>  $selection
     * @return list<array{quote: array<string, mixed>, quantity: int}>
     */
    private function lines(Property $residence, array $selection, CarbonImmutable $arrival, CarbonImmutable $departure): array
    {
        $units = $residence->units()->whereKey(array_keys($selection))->with(['unitType', 'images'])->get()->keyBy('id');
        $lines = [];

        foreach ($selection as $unitId => $quantity) {
            $unit = $units->get($unitId) ?? throw new WorkflowException('Un des logements choisis n’est plus proposé.');
            $quote = $this->engine->quote($unit, $arrival, $departure);

            if ($quote['available'] < $quantity || ($quote['issues'] !== [] && $quote['available'] > 0)) {
                throw new WorkflowException($unit->name.' : '.($quote['issues'][0] ?? 'il n’en reste que '.$quote['available'].' à ces dates.'));
            }

            $lines[] = ['quote' => $quote, 'quantity' => $quantity];
        }

        return $lines;
    }
}
