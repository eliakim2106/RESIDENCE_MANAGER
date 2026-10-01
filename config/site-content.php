<?php

/*
|--------------------------------------------------------------------------
| CONTENU DES PAGES DU SITE
|--------------------------------------------------------------------------
| Textes, images et listes modifiables par le super administrateur (Paramètres du site > Contenu des pages).
| Chaque bloc décrit ses champs ; ses valeurs par défaut sont le contenu d'origine du site.
|
| Types de champ : text, textarea, image, link (adresse https://… ou chemin du site /…, #…), icon.
| « visible » : le bloc peut être masqué. « list » : liste d'éléments (diapositives, étapes…), réordonnable ;
| son « summary » désigne les champs montrés quand un élément est replié (titre, sous-titre, vignette ou icône).
| « icon » : icône du bloc dans l'administration.
| Images par défaut : chemins dans public/ (assets/…) ; images envoyées : disque public, dossier site/.
*/

$slideFields = [
    'image' => ['type' => 'image', 'label' => 'Image de fond', 'help' => 'Paysage, 1920 × 1080 px conseillé.'],
    'eyebrow' => ['type' => 'text', 'label' => 'Surtitre', 'max' => 60],
    'title' => ['type' => 'text', 'label' => 'Titre', 'max' => 70, 'required' => true],
    'highlight' => ['type' => 'text', 'label' => 'Suite du titre (en doré)', 'max' => 70],
    'text' => ['type' => 'textarea', 'label' => 'Texte', 'max' => 220],
    'button_label' => ['type' => 'text', 'label' => 'Bouton secondaire', 'max' => 40],
    'button_link' => ['type' => 'link', 'label' => 'Lien du bouton', 'max' => 255],
];

$cardFields = [
    'icon' => ['type' => 'icon', 'label' => 'Icône'],
    'title' => ['type' => 'text', 'label' => 'Titre', 'max' => 60, 'required' => true],
    'text' => ['type' => 'textarea', 'label' => 'Texte', 'max' => 200],
];

$heading = fn (string $kicker, string $title, string $highlight = '', ?string $lead = null): array => array_filter([
    'kicker' => $kicker,
    'title' => $title,
    'highlight' => $highlight,
    'lead' => $lead,
], fn ($value) => $value !== null);

$headingFields = fn (bool $lead = true): array => array_filter([
    'kicker' => ['type' => 'text', 'label' => 'Surtitre', 'max' => 60],
    'title' => ['type' => 'text', 'label' => 'Titre', 'max' => 80, 'required' => true],
    'highlight' => ['type' => 'text', 'label' => 'Suite du titre (en couleur)', 'max' => 80],
    'lead' => $lead ? ['type' => 'textarea', 'label' => 'Texte d’introduction', 'max' => 300] : null,
]);

return [

    'pages' => [
        'accueil' => [
            'label' => 'Accueil',
            'icon' => 'fa-house',
            'url' => '/',
            'blocks' => ['home_slides', 'home_stats', 'home_featured', 'home_why', 'home_steps', 'home_reviews', 'home_cta', 'home_contact'],
        ],
        'residences' => [
            'label' => 'Résidences',
            'icon' => 'fa-building',
            'url' => '/residences',
            'blocks' => ['listing_hero', 'listing_cta'],
        ],
        'informations' => [
            'label' => 'Pages d’information',
            'icon' => 'fa-circle-info',
            'url' => '/questions-frequentes',
            'blocks' => ['faq_hero', 'faq_questions', 'contact_hero', 'owners_hero'],
        ],
    ],

    'blocks' => [

        /* ---------------- Accueil ---------------- */

        'home_slides' => [
            'icon' => 'fa-images',
            'label' => 'Diaporama d’accueil',
            'help' => 'Grandes images qui défilent en haut de l’accueil. Le bouton « Réserver maintenant » est ajouté à chaque diapositive.',
            'list' => ['label' => 'Diapositive', 'min' => 1, 'max' => 6, 'fields' => $slideFields, 'summary' => ['title' => 'title', 'subtitle' => 'eyebrow', 'thumb' => 'image']],
            'defaults' => [
                'items' => [
                    [
                        'image' => 'assets/images/home/slide-1.webp',
                        'eyebrow' => 'Résidences meublées haut standing',
                        'title' => 'Trouvez votre',
                        'highlight' => 'résidence idéale',
                        'text' => 'Séjournez avec confort, sécurité et élégance dans nos résidences sélectionnées à Abidjan et partout en Côte d’Ivoire.',
                        'button_label' => 'Découvrir nos résidences',
                        'button_link' => '/residences',
                    ],
                    [
                        'image' => 'assets/images/home/slide-2.webp',
                        'eyebrow' => 'Complexes avec piscine',
                        'title' => 'Le confort d’un hôtel,',
                        'highlight' => 'la liberté d’un chez-soi',
                        'text' => 'Appartements équipés, services inclus et espaces de détente pour des séjours courts ou prolongés.',
                        'button_label' => 'Voir les disponibilités',
                        'button_link' => '/residences',
                    ],
                    [
                        'image' => 'assets/images/home/slide-3.webp',
                        'eyebrow' => 'Villas d’exception',
                        'title' => 'Des villas pensées pour',
                        'highlight' => 'vos plus beaux séjours',
                        'text' => 'Piscine privée, jardin tropical et prestations sur mesure : l’adresse idéale en famille ou entre amis.',
                        'button_label' => 'Voir les résidences',
                        'button_link' => '/residences',
                    ],
                ],
            ],
        ],

        'home_stats' => [
            'icon' => 'fa-chart-simple',
            'label' => 'Chiffres de la plateforme',
            'help' => 'Résidences en ligne, villes, logements et note moyenne : calculés automatiquement.',
            'visible' => true,
            'fields' => [],
            'defaults' => [],
        ],

        'home_featured' => [
            'icon' => 'fa-star',
            'label' => 'Résidences à la une',
            'help' => 'Les résidences affichées sont choisies automatiquement (coups de cœur puis mieux notées).',
            'visible' => true,
            'fields' => $headingFields(false),
            'defaults' => $heading('Sélection DS HOLDING', 'Nos résidences', 'à la une'),
        ],

        'home_why' => [
            'icon' => 'fa-medal',
            'label' => 'Pourquoi nous choisir',
            'visible' => true,
            'fields' => [
                ...$headingFields(),
                'image' => ['type' => 'image', 'label' => 'Grande image', 'help' => 'Format portrait ou carré.'],
                'image_tag' => ['type' => 'text', 'label' => 'Étiquette de l’image', 'max' => 40],
                'image_title' => ['type' => 'text', 'label' => 'Titre sur l’image', 'max' => 80],
                'image_text' => ['type' => 'textarea', 'label' => 'Texte sur l’image', 'max' => 200],
            ],
            'list' => ['label' => 'Atout', 'min' => 2, 'max' => 6, 'fields' => $cardFields, 'summary' => ['title' => 'title', 'subtitle' => 'text', 'icon' => 'icon']],
            'defaults' => [
                ...$heading('Pourquoi DS HOLDING', 'Tout est prévu pour', 'un séjour sans souci', 'Des résidences modernes et sécurisées, et une équipe qui s’occupe de chaque détail pour rendre votre séjour exceptionnel.'),
                'image' => 'assets/images/home/interieur.webp',
                'image_tag' => 'Intérieurs soignés',
                'image_title' => 'Des logements entièrement équipés, prêts à vivre',
                'image_text' => 'Cuisine équipée, literie de qualité et climatisation pour vous sentir comme chez vous.',
                'items' => [
                    ['icon' => 'fa-shield-halved', 'title' => 'Sécurité 24h/24', 'text' => 'Gardiennage, vidéosurveillance et accès contrôlé dans chaque résidence.'],
                    ['icon' => 'fa-wifi', 'title' => 'Wi-Fi haut débit', 'text' => 'Une connexion fiable pour travailler ou vous divertir sans interruption.'],
                    ['icon' => 'fa-water-ladder', 'title' => 'Piscine & détente', 'text' => 'Espaces de repos, piscines et jardins pour vous ressourcer.'],
                    ['icon' => 'fa-car', 'title' => 'Parking privé', 'text' => 'Places sécurisées réservées aux résidents, sans supplément.'],
                    ['icon' => 'fa-broom', 'title' => 'Ménage & conciergerie', 'text' => 'Linge fourni, ménage régulier et conciergerie à votre écoute.'],
                    ['icon' => 'fa-headset', 'title' => 'Assistance 24h/24', 'text' => 'Une équipe joignable à toute heure par téléphone ou WhatsApp.'],
                ],
            ],
        ],

        'home_steps' => [
            'icon' => 'fa-list-ol',
            'label' => 'Étapes de réservation',
            'visible' => true,
            'fields' => $headingFields(false),
            'list' => ['label' => 'Étape', 'min' => 2, 'max' => 4, 'fields' => $cardFields, 'summary' => ['title' => 'title', 'subtitle' => 'text', 'icon' => 'icon']],
            'defaults' => [
                ...$heading('Simple et rapide', 'Réservez en', 'trois étapes'),
                'items' => [
                    ['icon' => 'fa-magnifying-glass-location', 'title' => 'Choisissez', 'text' => 'Parcourez nos résidences, comparez les équipements et trouvez celle qui vous ressemble.'],
                    ['icon' => 'fa-calendar-check', 'title' => 'Réservez', 'text' => 'Sélectionnez vos dates et confirmez en quelques clics, avec un paiement sécurisé.'],
                    ['icon' => 'fa-key', 'title' => 'Profitez', 'text' => 'Accueil personnalisé, remise des clés et assistance pendant tout votre séjour.'],
                ],
            ],
        ],

        'home_reviews' => [
            'icon' => 'fa-comments',
            'label' => 'Avis clients',
            'help' => 'Les avis affichés sont les avis vérifiés des voyageurs, choisis automatiquement.',
            'visible' => true,
            'fields' => $headingFields(),
            'defaults' => $heading('Avis clients', 'Ils ont séjourné', 'chez nous', 'Seuls les voyageurs ayant séjourné dans une résidence peuvent la noter : chaque avis correspond à une réservation réelle.'),
        ],

        'home_cta' => [
            'icon' => 'fa-bullhorn',
            'label' => 'Bandeau de réservation',
            'visible' => true,
            'fields' => [
                'image' => ['type' => 'image', 'label' => 'Image de fond'],
                'kicker' => ['type' => 'text', 'label' => 'Surtitre', 'max' => 60],
                'title' => ['type' => 'text', 'label' => 'Titre', 'max' => 80, 'required' => true],
                'text' => ['type' => 'textarea', 'label' => 'Texte', 'max' => 220],
                'primary_label' => ['type' => 'text', 'label' => 'Bouton principal', 'max' => 40, 'required' => true],
                'primary_link' => ['type' => 'link', 'label' => 'Lien du bouton principal', 'max' => 255, 'required' => true],
                'secondary_label' => ['type' => 'text', 'label' => 'Bouton secondaire', 'max' => 40],
                'secondary_link' => ['type' => 'link', 'label' => 'Lien du bouton secondaire', 'max' => 255],
            ],
            'defaults' => [
                'image' => 'assets/images/home/slide-2.webp',
                'kicker' => 'Réservation en ligne',
                'title' => 'Prêt à réserver votre prochain séjour ?',
                'text' => 'Choisissez votre résidence, vos dates, et recevez la confirmation de votre séjour en quelques minutes.',
                'primary_label' => 'Réserver maintenant',
                'primary_link' => '/residences',
                'secondary_label' => 'Nous contacter',
                'secondary_link' => '#contact',
            ],
        ],

        'home_contact' => [
            'icon' => 'fa-envelope',
            'label' => 'Section contact',
            'help' => 'Les coordonnées se règlent dans l’onglet Général.',
            'visible' => true,
            'fields' => $headingFields(),
            'defaults' => $heading('Contact', 'Besoin', 'd’informations ?', 'Notre équipe vous répond rapidement pour toute question sur une résidence ou une réservation.'),
        ],

        /* ---------------- Résidences ---------------- */

        'listing_hero' => [
            'icon' => 'fa-panorama',
            'label' => 'En-tête de la liste des résidences',
            'help' => 'La phrase qui suit le titre (nombre d’établissements et de villes) est calculée automatiquement.',
            'fields' => [
                'image' => ['type' => 'image', 'label' => 'Image de fond'],
                'title' => ['type' => 'text', 'label' => 'Titre', 'max' => 80, 'required' => true],
                'highlight' => ['type' => 'text', 'label' => 'Suite du titre (en doré)', 'max' => 80],
            ],
            'defaults' => [
                'image' => 'assets/images/home/slide-2.webp',
                'title' => 'Trouvez la résidence',
                'highlight' => 'qui vous ressemble',
            ],
        ],

        'listing_cta' => [
            'icon' => 'fa-building-circle-check',
            'label' => 'Bandeau propriétaires',
            'help' => 'Affiché sous la liste des résidences.',
            'visible' => true,
            'fields' => [
                'title' => ['type' => 'text', 'label' => 'Titre', 'max' => 90, 'required' => true],
                'text' => ['type' => 'textarea', 'label' => 'Texte', 'max' => 200],
                'button_label' => ['type' => 'text', 'label' => 'Bouton', 'max' => 40, 'required' => true],
                'button_link' => ['type' => 'link', 'label' => 'Lien du bouton', 'max' => 255, 'required' => true],
            ],
            'defaults' => [
                'title' => 'Vous êtes propriétaire ou gérant d’un établissement ?',
                'text' => 'Publiez vos logements sur DS HOLDING, gérez vos unités et recevez des réservations en ligne.',
                'button_label' => 'Publier mon établissement',
                'button_link' => '/inscription/proprietaire',
            ],
        ],

        /* ---------------- Pages d'information ---------------- */

        'faq_hero' => [
            'icon' => 'fa-heading',
            'label' => 'Questions fréquentes : en-tête',
            'fields' => [
                'kicker' => ['type' => 'text', 'label' => 'Surtitre', 'max' => 60],
                'title' => ['type' => 'text', 'label' => 'Titre', 'max' => 90, 'required' => true],
                'lead' => ['type' => 'textarea', 'label' => 'Texte', 'max' => 220],
            ],
            'defaults' => ['kicker' => 'Aide', 'title' => 'Questions fréquentes', 'lead' => 'Tout ce qu’il faut savoir pour réserver, payer et gérer votre séjour.'],
        ],

        'faq_questions' => [
            'icon' => 'fa-circle-question',
            'label' => 'Questions fréquentes : questions',
            'help' => 'Les questions sont regroupées par thème, dans l’ordre de la liste.',
            'list' => [
                'label' => 'Question',
                'min' => 1,
                'max' => 60,
                'summary' => ['title' => 'question', 'subtitle' => 'theme'],
                'fields' => [
                    'theme' => ['type' => 'text', 'label' => 'Thème', 'max' => 40, 'required' => true],
                    'question' => ['type' => 'text', 'label' => 'Question', 'max' => 160, 'required' => true],
                    'answer' => ['type' => 'textarea', 'label' => 'Réponse', 'max' => 1000, 'required' => true],
                ],
            ],
            'defaults' => [
                'items' => [
                    ['theme' => 'Réserver', 'question' => 'Comment réserver une résidence ?', 'answer' => 'Choisissez vos dates sur la fiche de la résidence, sélectionnez un ou plusieurs logements puis cliquez sur « Réserver ». Vous vérifiez le récapitulatif, indiquez le voyageur principal et envoyez votre demande.'],
                    ['theme' => 'Réserver', 'question' => 'Dois-je créer un compte ?', 'answer' => 'Oui : un compte client permet de suivre vos demandes, de régler en ligne et de retrouver vos bons de réservation. Il se crée en une minute, avec votre email ou avec Google / Facebook lorsque cette option est proposée. Vos dates et logements choisis sont conservés pendant l’inscription.'],
                    ['theme' => 'Réserver', 'question' => 'Ma réservation est-elle confirmée immédiatement ?', 'answer' => 'Votre demande est transmise à l’établissement, qui la confirme. Vous êtes prévenu par email et dans votre espace. Sans réponse dans le délai indiqué, la demande expire automatiquement.'],
                    ['theme' => 'Réserver', 'question' => 'Puis-je réserver plusieurs logements pour un groupe ?', 'answer' => 'Oui : sur la fiche, choisissez le nombre de logements souhaité pour chaque type. Le total se met à jour automatiquement.'],
                    ['theme' => 'Payer', 'question' => 'Quels moyens de paiement sont acceptés ?', 'answer' => 'Le paiement en ligne passe par notre agrégateur de paiement sécurisé : Mobile Money (Orange, MTN, Moov, Wave selon disponibilité) et carte bancaire. L’établissement peut aussi enregistrer un paiement reçu sur place.'],
                    ['theme' => 'Payer', 'question' => 'Quand dois-je payer ?', 'answer' => 'Vous pouvez régler depuis votre espace dès l’envoi de votre demande, ou attendre sa confirmation. Le reste éventuel se règle selon les modalités de l’établissement.'],
                    ['theme' => 'Payer', 'question' => 'Où trouver mon reçu ?', 'answer' => 'Chaque paiement génère un reçu téléchargeable depuis le détail de votre réservation, dans votre espace client.'],
                    ['theme' => 'Annuler et modifier', 'question' => 'Comment annuler une réservation ?', 'answer' => 'Depuis votre espace, ouvrez la réservation puis « Annuler ma réservation ». Les conditions d’annulation de l’établissement s’appliquent : elles sont rappelées sur la fiche et sur le récapitulatif.'],
                    ['theme' => 'Annuler et modifier', 'question' => 'Serai-je remboursé ?', 'answer' => 'Si vous annulez avant la date limite d’annulation gratuite, le paiement effectué est remboursé. Après cette date, ou avec une politique non remboursable, il reste acquis à l’établissement.'],
                    ['theme' => 'Annuler et modifier', 'question' => 'Puis-je modifier mes dates ?', 'answer' => 'Contactez directement l’établissement (ses coordonnées figurent dans votre réservation) : il pourra adapter votre séjour selon ses disponibilités.'],
                    ['theme' => 'Mon compte', 'question' => 'J’ai oublié mon mot de passe', 'answer' => 'Sur la page de connexion, cliquez sur « Mot de passe oublié ? » : vous recevrez un lien par email pour en choisir un nouveau.'],
                    ['theme' => 'Mon compte', 'question' => 'Comment laisser un avis ?', 'answer' => 'Après votre départ, un formulaire d’avis apparaît dans le détail de la réservation. Seuls les voyageurs ayant séjourné peuvent noter une résidence.'],
                    ['theme' => 'Propriétaires', 'question' => 'Comment proposer mon établissement ?', 'answer' => 'Créez un compte propriétaire, ajoutez votre établissement et ses logements puis soumettez-le : notre équipe le vérifie avant sa mise en ligne.'],
                    ['theme' => 'Propriétaires', 'question' => 'Comment suis-je payé ?', 'answer' => 'Les paiements en ligne de vos clients vous sont reversés par DS HOLDING ; le détail figure dans « Mes reversements » de votre espace.'],
                ],
            ],
        ],

        'contact_hero' => [
            'icon' => 'fa-heading',
            'label' => 'Page Contact : en-tête',
            'fields' => [
                'kicker' => ['type' => 'text', 'label' => 'Surtitre', 'max' => 60],
                'title' => ['type' => 'text', 'label' => 'Titre', 'max' => 90, 'required' => true],
                'lead' => ['type' => 'textarea', 'label' => 'Texte', 'max' => 220],
            ],
            'defaults' => ['kicker' => 'Contact', 'title' => 'Parlons de votre projet', 'lead' => 'Une question sur une résidence, une réservation ou un partenariat ? Écrivez-nous.'],
        ],

        'owners_hero' => [
            'icon' => 'fa-heading',
            'label' => 'Page Propriétaires : en-tête',
            'fields' => [
                'kicker' => ['type' => 'text', 'label' => 'Surtitre', 'max' => 60],
                'title' => ['type' => 'text', 'label' => 'Titre', 'max' => 90, 'required' => true],
                'lead' => ['type' => 'textarea', 'label' => 'Texte', 'max' => 220],
            ],
            'defaults' => ['kicker' => 'Propriétaires et gestionnaires', 'title' => 'Proposez votre établissement sur DS HOLDING', 'lead' => 'Résidences, hôtels, villas, appartements : recevez des réservations en ligne et gérez tout depuis un seul espace.'],
        ],
    ],

    /*
    | Icônes proposées (Font Awesome) : classe => libellé
    */
    'icons' => [
        'fa-shield-halved' => 'Sécurité',
        'fa-lock' => 'Cadenas',
        'fa-wifi' => 'Wi-Fi',
        'fa-water-ladder' => 'Piscine',
        'fa-umbrella-beach' => 'Plage',
        'fa-car' => 'Voiture / parking',
        'fa-plane-arrival' => 'Aéroport',
        'fa-broom' => 'Ménage',
        'fa-bell-concierge' => 'Conciergerie',
        'fa-headset' => 'Assistance',
        'fa-phone' => 'Téléphone',
        'fa-comments' => 'Discussion',
        'fa-snowflake' => 'Climatisation',
        'fa-utensils' => 'Restauration',
        'fa-mug-hot' => 'Petit-déjeuner',
        'fa-dumbbell' => 'Salle de sport',
        'fa-spa' => 'Spa / bien-être',
        'fa-tv' => 'Télévision',
        'fa-bed' => 'Literie',
        'fa-couch' => 'Salon',
        'fa-house' => 'Maison',
        'fa-building' => 'Immeuble',
        'fa-key' => 'Clés',
        'fa-door-open' => 'Accueil',
        'fa-calendar-check' => 'Calendrier',
        'fa-clock' => 'Horloge',
        'fa-magnifying-glass-location' => 'Recherche',
        'fa-map-location-dot' => 'Carte',
        'fa-credit-card' => 'Paiement par carte',
        'fa-mobile-screen' => 'Téléphone mobile',
        'fa-wallet' => 'Portefeuille',
        'fa-tag' => 'Prix',
        'fa-star' => 'Étoile',
        'fa-heart' => 'Cœur',
        'fa-thumbs-up' => 'Satisfaction',
        'fa-leaf' => 'Nature',
        'fa-child' => 'Enfants',
        'fa-paw' => 'Animaux',
        'fa-briefcase' => 'Affaires',
        'fa-circle-check' => 'Validation',
    ],
];
