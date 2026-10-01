<?php

namespace App\Support;

use App\Models\Setting;
use Illuminate\Support\Facades\Storage;

/**
 * Paramètres du site modifiables par le super administrateur (Administration > Paramètres du site) :
 * identité, coordonnées, réseaux sociaux, règles de réservation.
 *
 * Enregistrés dans la table `settings` (groupe « site ») ; une valeur absente prend sa valeur par défaut.
 * Les règles de réservation retombent sur config/booking.php (variables BOOKING_* du .env).
 * Disponible dans toutes les vues sous le nom $site.
 */
class SiteSettings
{
    public const GROUP = 'site';

    /** Contenu des pages (config/site-content.php) : un réglage JSON « content.{bloc} » par bloc */
    public const CONTENT_GROUP = 'content';

    /**
     * Valeurs par défaut : celles du site avant la création de ces réglages.
     *
     * @var array<string, string>
     */
    public const DEFAULTS = [
        'site_name' => 'DS HOLDING',
        'site_description' => 'DS HOLDING : résidences meublées, appartements et villas haut standing en Côte d’Ivoire. Réservez votre séjour en ligne.',
        'site_about' => 'DS HOLDING vous propose des résidences meublées haut standing alliant confort, sécurité et élégance.',
        'contact_address' => 'Cocody, Abidjan',
        'contact_email' => 'contact@dsholding.ci',
        'contact_phone' => '0141601278',
        'contact_phone_dial' => '+225',
        'contact_whatsapp' => '0141601278',
        'contact_whatsapp_dial' => '+225',
        'contact_hours' => '',
        'social_facebook' => '',
        'social_instagram' => '',
        'social_linkedin' => '',
        'social_tiktok' => '',
        'social_youtube' => '',
    ];

    /**
     * Réseaux sociaux proposés : clé => [libellé, icône].
     *
     * @var array<string, array{0: string, 1: string}>
     */
    public const SOCIALS = [
        'social_facebook' => ['Facebook', 'fa-facebook-f'],
        'social_instagram' => ['Instagram', 'fa-instagram'],
        'social_linkedin' => ['LinkedIn', 'fa-linkedin-in'],
        'social_tiktok' => ['TikTok', 'fa-tiktok'],
        'social_youtube' => ['YouTube', 'fa-youtube'],
    ];

    /**
     * Règles de réservation : clé de config/booking.php => clé du paramètre.
     *
     * @var array<string, string>
     */
    public const BOOKING = [
        'request_ttl_hours' => 'booking_request_ttl_hours',
        'service_fee_rate' => 'booking_service_fee_rate',
        'max_nights' => 'booking_max_nights',
        'max_days_ahead' => 'booking_max_days_ahead',
    ];

    /** @var array<string, string|null>|null */
    private ?array $values = null;

    /**
     * Lecture paresseuse, une fois par requête. Base injoignable (page d'erreur, installation) : valeurs par défaut.
     *
     * @return array<string, string|null>
     */
    public function all(): array
    {
        return $this->values ??= rescue(
            fn (): array => Setting::query()->whereIn('group', [self::GROUP, self::CONTENT_GROUP])->pluck('value', 'key')->all(),
            [],
            report: false,
        );
    }

    /**
     * Valeur enregistrée (même vide : un champ facultatif effacé reste vide), sinon valeur par défaut.
     */
    public function get(string $key): string
    {
        $values = $this->all();

        return (string) (array_key_exists($key, $values) ? $values[$key] : (self::DEFAULTS[$key] ?? ''));
    }

    /**
     * Enregistre les paramètres et oublie les valeurs lues pendant la requête.
     *
     * @param  array<string, mixed>  $values
     */
    public function save(array $values): void
    {
        foreach ($values as $key => $value) {
            Setting::set($key, (string) $value, self::GROUP);
        }

        $this->values = null;
    }

    /*
    |--------------------------------------------------------------------------
    | COORDONNÉES
    |--------------------------------------------------------------------------
    */

    public function name(): string
    {
        return $this->get('site_name');
    }

    public function email(): string
    {
        return $this->get('contact_email');
    }

    /**
     * Téléphone affiché : « +225 01 41 60 12 78 » ; vide si aucun numéro.
     */
    public function phone(): string
    {
        return filled($this->get('contact_phone')) ? PhoneNumber::format($this->get('contact_phone_dial'), $this->get('contact_phone')) : '';
    }

    public function phoneHref(): string
    {
        return 'tel:'.PhoneNumber::e164($this->get('contact_phone_dial'), $this->get('contact_phone'));
    }

    /**
     * Lien WhatsApp (wa.me attend le numéro international sans « + ») ; null si aucun numéro.
     */
    public function whatsappUrl(): ?string
    {
        $number = $this->get('contact_whatsapp');

        return filled($number) ? 'https://wa.me/'.ltrim(PhoneNumber::e164($this->get('contact_whatsapp_dial'), $number), '+') : null;
    }

    /**
     * Réseaux renseignés, dans l'ordre de SOCIALS.
     *
     * @return list<array{label: string, icon: string, url: string}>
     */
    public function socials(): array
    {
        $socials = [];

        foreach (self::SOCIALS as $key => [$label, $icon]) {
            if (filled($url = $this->get($key))) {
                $socials[] = ['label' => $label, 'icon' => $icon, 'url' => $url];
            }
        }

        return $socials;
    }

    /*
    |--------------------------------------------------------------------------
    | RÉSERVATION
    |--------------------------------------------------------------------------
    */

    /**
     * Règle de réservation : valeur réglée dans l'administration, sinon celle de config/booking.php.
     */
    public function booking(string $key): int|float
    {
        $value = $this->all()[self::BOOKING[$key]] ?? null;

        return is_numeric($value) ? $value + 0 : config("booking.{$key}");
    }

    /*
    |--------------------------------------------------------------------------
    | CONTENU DES PAGES
    |--------------------------------------------------------------------------
    */

    /**
     * Contenu d'un bloc : valeurs enregistrées complétées par celles d'origine.
     * Les éléments d'une liste (« items ») enregistrés remplacent entièrement ceux d'origine.
     *
     * @return array<string, mixed>
     */
    public function content(string $block): array
    {
        $definition = config("site-content.blocks.{$block}") ?? throw new \InvalidArgumentException("Bloc de contenu inconnu : {$block}");
        $defaults = ['visible' => true, ...$definition['defaults']];

        $stored = json_decode((string) ($this->all()['content.'.$block] ?? ''), true);

        return is_array($stored) ? array_replace($defaults, $stored) : $defaults;
    }

    /**
     * Le bloc est-il affiché ? (un bloc sans option « visible » l'est toujours)
     */
    public function visible(string $block): bool
    {
        return ! (config("site-content.blocks.{$block}.visible") ?? false) || (bool) ($this->content($block)['visible'] ?? true);
    }

    /**
     * Enregistre le contenu d'un bloc (null : retour au contenu d'origine).
     *
     * @param  array<string, mixed>|null  $content
     */
    public function saveContent(string $block, ?array $content): void
    {
        if ($content === null) {
            // Par le modèle : son événement « deleted » vide le cache des réglages
            Setting::query()->where('key', 'content.'.$block)->first()?->delete();
        } else {
            Setting::set('content.'.$block, json_encode($content, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), self::CONTENT_GROUP);
        }

        $this->values = null;
    }

    /**
     * Adresse d'une image : fichier du site (assets/…) ou image envoyée (disque public, dossier site/).
     */
    public function image(?string $path, string $fallback = 'assets/images/home/slide-1.webp'): string
    {
        $path = filled($path) ? $path : $fallback;

        return str_starts_with($path, 'assets/') ? asset($path) : Storage::disk('public')->url($path);
    }

    /**
     * Lien saisi dans le contenu : adresse complète, chemin du site (/residences) ou ancre (#contact).
     */
    public function link(?string $link): string
    {
        $link = trim((string) $link);

        return match (true) {
            $link === '' => url('/'),
            str_starts_with($link, '#'), str_starts_with($link, 'http://'), str_starts_with($link, 'https://'), str_starts_with($link, 'mailto:'), str_starts_with($link, 'tel:') => $link,
            default => url($link),
        };
    }

    public static function current(): self
    {
        return app(self::class);
    }
}
