<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Casts\Attribute;

/**
 * Classes Font Awesome de la colonne icon.
 *
 * Les formulaires enregistrent un nom Font Awesome (« fa-hotel »), les données de démonstration
 * des noms courts (« hotel », « wifi ») : ces derniers sont traduits vers leur équivalent Font Awesome.
 */
trait HasFontAwesomeIcon
{
    /**
     * @var array<string, string>
     */
    private const SHORT_ICON_NAMES = [
        'building-2' => 'fa-building',
        'home' => 'fa-house-chimney',
        'palmtree' => 'fa-umbrella-beach',
        'bed-single' => 'fa-bed',
        'bed-double' => 'fa-bed',
        'crown' => 'fa-crown',
        'door-open' => 'fa-door-open',
        'wifi' => 'fa-wifi',
        'car' => 'fa-square-parking',
        'waves' => 'fa-person-swimming',
        'zap' => 'fa-bolt',
        'shield-check' => 'fa-shield-halved',
        'utensils' => 'fa-utensils',
        'coffee' => 'fa-mug-hot',
        'dumbbell' => 'fa-dumbbell',
        'plane' => 'fa-plane',
        'concierge-bell' => 'fa-bell-concierge',
        'sparkles' => 'fa-broom',
        'shirt' => 'fa-shirt',
        'umbrella' => 'fa-umbrella-beach',
        'trees' => 'fa-tree',
        'sun' => 'fa-sun',
        'tv' => 'fa-tv',
        'lock' => 'fa-lock',
        'layout-panel-top' => 'fa-building',
        'briefcase' => 'fa-briefcase',
        'chef-hat' => 'fa-kitchen-set',
        'refrigerator' => 'fa-temperature-low',
        'microwave' => 'fa-fire-burner',
        'washing-machine' => 'fa-soap',
        'shower-head' => 'fa-shower',
        'bath' => 'fa-bath',
        'thermometer' => 'fa-temperature-high',
        'package' => 'fa-pump-soap',
    ];

    /**
     * Nom Font Awesome de l'icône, ex. « fa-hotel ».
     *
     * @return Attribute<string, never>
     */
    protected function faIcon(): Attribute
    {
        return Attribute::get(function (): string {
            $icon = trim((string) $this->icon);

            if ($icon === '') {
                return 'fa-question';
            }

            if (str_starts_with($icon, 'fa-')) {
                return $icon;
            }

            return self::SHORT_ICON_NAMES[$icon] ?? 'fa-'.$icon;
        });
    }
}
