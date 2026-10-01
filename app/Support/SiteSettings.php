<?php

namespace App\Support;

use App\Models\Setting;

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
            fn (): array => Setting::query()->where('group', self::GROUP)->pluck('value', 'key')->all(),
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

    public static function current(): self
    {
        return app(self::class);
    }
}
