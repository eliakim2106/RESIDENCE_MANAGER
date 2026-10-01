<?php

namespace App\Support;

/**
 * Numéros de téléphone : indicatif (ex. « +225 ») et numéro national enregistrés séparément.
 *
 * Les pays, leurs longueurs et leur format d'affichage sont dans config/phone.php.
 */
class PhoneNumber
{
    /**
     * Indicatifs proposés, les pays préférés en premier.
     *
     * @return array<string, array{iso: string, name: string, lengths: list<int>, groups: list<int>, example: string, trunk: bool}>
     */
    public static function countries(): array
    {
        $countries = config('phone.countries');
        $preferred = array_intersect_key(array_flip(config('phone.preferred', [])), $countries);

        return array_replace($preferred, $countries);
    }

    public static function defaultDial(): string
    {
        return (string) config('phone.default', '+225');
    }

    /**
     * @return array{iso: string, name: string, lengths: list<int>, groups: list<int>, example: string, trunk: bool}|null
     */
    public static function country(?string $dial): ?array
    {
        return config('phone.countries')[$dial] ?? null;
    }

    /**
     * Numéro national sans espaces, indicatif ni « 0 » de tête local.
     * Accepte une saisie libre : « 07 01 02 03 04 », « +225 0701020304 », « 00225… », « 06 12 34 56 78 » (France)…
     */
    public static function normalize(?string $dial, ?string $number): string
    {
        $digits = preg_replace('/\D/', '', (string) $number);
        $country = self::country($dial);

        if ($digits === '' || $country === null) {
            return $digits;
        }

        $code = ltrim((string) $dial, '+');
        $max = max($country['lengths']);

        // Indicatif tapé dans le numéro (+225…, 00225…)
        foreach (['00'.$code, $code] as $prefix) {
            if (str_starts_with($digits, $prefix) && strlen($digits) - strlen($prefix) >= min($country['lengths'])) {
                $digits = substr($digits, strlen($prefix));

                break;
            }
        }

        // « 0 » composé en local devant le numéro (France, Ghana, Royaume-Uni…)
        if ($country['trunk'] && str_starts_with($digits, '0') && strlen($digits) > $max - 1 && strlen($digits) <= $max + 1) {
            $digits = substr($digits, 1);
        }

        return $digits;
    }

    /**
     * Le numéro (normalisé) a une longueur valable pour le pays.
     */
    public static function isValid(?string $dial, ?string $number): bool
    {
        $country = self::country($dial);

        return $country !== null
            && preg_match('/^\d+$/', (string) $number) === 1
            && in_array(strlen((string) $number), $country['lengths'], true);
    }

    /**
     * Affichage lisible : « +225 07 01 23 45 67 ».
     */
    public static function format(?string $dial, ?string $number): string
    {
        $number = (string) $number;

        if ($number === '') {
            return '';
        }

        $country = self::country($dial);

        if ($country === null) {
            return trim($dial.' '.$number);
        }

        return $dial.' '.self::group($number, $country['groups']);
    }

    /**
     * Format international pour les liens tel: et les services externes : « +2250701234567 ».
     */
    public static function e164(?string $dial, ?string $number): string
    {
        return (string) $number === '' ? '' : ($dial ?: self::defaultDial()).$number;
    }

    /**
     * Sépare un numéro enregistré d'un bloc (« +225 07 01 02 03 04 ») en indicatif et numéro national.
     *
     * @return array{0: string, 1: string}
     */
    public static function split(?string $raw, ?string $fallbackDial = null): array
    {
        $raw = trim((string) $raw);
        $fallbackDial ??= self::defaultDial();

        if (str_starts_with($raw, '+') || str_starts_with($raw, '00')) {
            $digits = preg_replace('/\D/', '', $raw);
            $digits = str_starts_with($raw, '00') ? substr($digits, 2) : $digits;

            // Indicatif le plus long d'abord (+225 avant +2…)
            $dials = array_keys(config('phone.countries'));
            usort($dials, fn (string $a, string $b): int => strlen($b) <=> strlen($a));

            foreach ($dials as $dial) {
                if (str_starts_with($digits, ltrim($dial, '+'))) {
                    return [$dial, self::normalize($dial, substr($digits, strlen($dial) - 1))];
                }
            }
        }

        return [$fallbackDial, self::normalize($fallbackDial, $raw)];
    }

    /**
     * @param  list<int>  $groups
     */
    private static function group(string $number, array $groups): string
    {
        $parts = [];
        $position = 0;

        foreach ($groups as $size) {
            if ($position >= strlen($number)) {
                break;
            }

            $parts[] = substr($number, $position, $size);
            $position += $size;
        }

        // Chiffres au-delà du découpage prévu (numéro plus long que l'exemple)
        if ($position < strlen($number)) {
            $parts[] = substr($number, $position);
        }

        return implode(' ', $parts);
    }
}
