<?php

/*
|--------------------------------------------------------------------------
| Indicatifs téléphoniques
|--------------------------------------------------------------------------
| Pays proposés dans tous les formulaires qui demandent un téléphone (champ indicatif_telephone).
|
| Pour chaque indicatif :
|   iso      code du pays (drapeau)
|   name     nom affiché
|   lengths  nombre(s) de chiffres du numéro national, sans le « 0 » de tête quand trunk est vrai
|   groups   découpage pour l'affichage (ex. 07 01 23 45 67)
|   example  exemple affiché dans le champ
|   trunk    les habitants composent un « 0 » devant le numéro en local (retiré à l'enregistrement)
|
| Le numéro est enregistré sans indicatif ni espaces (colonne phone), l'indicatif à part.
*/

return [

    'default' => '+225',

    // Pays proposés en premier dans la liste
    'preferred' => ['+225', '+221', '+223', '+226', '+228', '+229', '+224', '+227'],

    'countries' => [
        // Afrique de l'Ouest
        '+225' => ['iso' => 'ci', 'name' => 'Côte d’Ivoire', 'lengths' => [10], 'groups' => [2, 2, 2, 2, 2], 'example' => '07 01 23 45 67', 'trunk' => false],
        '+221' => ['iso' => 'sn', 'name' => 'Sénégal', 'lengths' => [9], 'groups' => [2, 3, 2, 2], 'example' => '77 123 45 67', 'trunk' => false],
        '+223' => ['iso' => 'ml', 'name' => 'Mali', 'lengths' => [8], 'groups' => [2, 2, 2, 2], 'example' => '65 12 34 56', 'trunk' => false],
        '+226' => ['iso' => 'bf', 'name' => 'Burkina Faso', 'lengths' => [8], 'groups' => [2, 2, 2, 2], 'example' => '70 12 34 56', 'trunk' => false],
        '+228' => ['iso' => 'tg', 'name' => 'Togo', 'lengths' => [8], 'groups' => [2, 2, 2, 2], 'example' => '90 12 34 56', 'trunk' => false],
        '+229' => ['iso' => 'bj', 'name' => 'Bénin', 'lengths' => [10], 'groups' => [2, 2, 2, 2, 2], 'example' => '01 97 12 34 56', 'trunk' => false],
        '+224' => ['iso' => 'gn', 'name' => 'Guinée', 'lengths' => [9], 'groups' => [3, 2, 2, 2], 'example' => '620 12 34 56', 'trunk' => false],
        '+227' => ['iso' => 'ne', 'name' => 'Niger', 'lengths' => [8], 'groups' => [2, 2, 2, 2], 'example' => '90 12 34 56', 'trunk' => false],
        '+233' => ['iso' => 'gh', 'name' => 'Ghana', 'lengths' => [9], 'groups' => [2, 3, 4], 'example' => '24 123 4567', 'trunk' => true],
        '+234' => ['iso' => 'ng', 'name' => 'Nigeria', 'lengths' => [10], 'groups' => [3, 3, 4], 'example' => '803 123 4567', 'trunk' => true],

        // Afrique centrale et du Nord
        '+237' => ['iso' => 'cm', 'name' => 'Cameroun', 'lengths' => [9], 'groups' => [1, 2, 2, 2, 2], 'example' => '6 71 23 45 67', 'trunk' => false],
        '+242' => ['iso' => 'cg', 'name' => 'Congo', 'lengths' => [9], 'groups' => [2, 3, 4], 'example' => '06 612 3456', 'trunk' => false],
        '+243' => ['iso' => 'cd', 'name' => 'RD Congo', 'lengths' => [9], 'groups' => [2, 3, 4], 'example' => '81 234 5678', 'trunk' => true],
        '+212' => ['iso' => 'ma', 'name' => 'Maroc', 'lengths' => [9], 'groups' => [1, 2, 2, 2, 2], 'example' => '6 12 34 56 78', 'trunk' => true],
        '+216' => ['iso' => 'tn', 'name' => 'Tunisie', 'lengths' => [8], 'groups' => [2, 3, 3], 'example' => '20 123 456', 'trunk' => false],

        // Europe
        '+33' => ['iso' => 'fr', 'name' => 'France', 'lengths' => [9], 'groups' => [1, 2, 2, 2, 2], 'example' => '6 12 34 56 78', 'trunk' => true],
        '+32' => ['iso' => 'be', 'name' => 'Belgique', 'lengths' => [8, 9], 'groups' => [3, 2, 2, 2], 'example' => '470 12 34 56', 'trunk' => true],
        '+41' => ['iso' => 'ch', 'name' => 'Suisse', 'lengths' => [9], 'groups' => [2, 3, 2, 2], 'example' => '78 123 45 67', 'trunk' => true],
        '+34' => ['iso' => 'es', 'name' => 'Espagne', 'lengths' => [9], 'groups' => [3, 3, 3], 'example' => '612 345 678', 'trunk' => false],
        '+39' => ['iso' => 'it', 'name' => 'Italie', 'lengths' => [9, 10], 'groups' => [3, 3, 4], 'example' => '312 345 6789', 'trunk' => false],
        '+49' => ['iso' => 'de', 'name' => 'Allemagne', 'lengths' => [10, 11], 'groups' => [3, 4, 4], 'example' => '151 2345 6789', 'trunk' => true],
        '+44' => ['iso' => 'gb', 'name' => 'Royaume-Uni', 'lengths' => [10], 'groups' => [4, 6], 'example' => '7400 123456', 'trunk' => true],

        // Amérique, Moyen-Orient, Asie
        '+1' => ['iso' => 'ca', 'name' => 'Canada / États-Unis', 'lengths' => [10], 'groups' => [3, 3, 4], 'example' => '514 123 4567', 'trunk' => false],
        '+971' => ['iso' => 'ae', 'name' => 'Émirats arabes unis', 'lengths' => [9], 'groups' => [2, 3, 4], 'example' => '50 123 4567', 'trunk' => true],
        '+961' => ['iso' => 'lb', 'name' => 'Liban', 'lengths' => [7, 8], 'groups' => [2, 3, 3], 'example' => '71 123 456', 'trunk' => true],
        '+86' => ['iso' => 'cn', 'name' => 'Chine', 'lengths' => [11], 'groups' => [3, 4, 4], 'example' => '131 2345 6789', 'trunk' => false],
    ],

];
