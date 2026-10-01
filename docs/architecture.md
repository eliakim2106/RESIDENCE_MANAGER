# Architecture

## Organisation du code

| Dossier | Contenu |
| --- | --- |
| `app/Http/Controllers` | Site public : accueil, résidences, réservation (`BookingController`), pages, contact, newsletter, retour des paiements |
| `app/Http/Controllers/Auth` | Connexion, inscription, vérification de l’email, mot de passe oublié, Google / Facebook |
| `app/Http/Controllers/Client` | Espace client `/mon-compte` |
| `app/Http/Controllers/Admin` | Administration `/admin` (propriétaires et administrateurs) |
| `app/Http/Middleware` | Rôles, compte actif, profil complet, redirection des clients hors de l’administration |
| `app/Services` | Règles métier (voir ci-dessous) |
| `app/Support` | Outils transverses : `ExcelExport`, `PhoneNumber`, `SiteSettings` |
| `app/Notifications` | Emails et notifications dans l’application |
| `routes/web.php` | Toutes les routes, regroupées par espace |
| `routes/console.php` | Commandes et tâches planifiées |
| `resources/views/site`, `client`, `auth`, `admin`, `errors` | Vues Blade |
| `resources/css`, `resources/js` | Styles et scripts : `site.css`/`site.js` (site et espace client), `admin.css`/`admin.js` |

## Services

| Service | Rôle |
| --- | --- |
| `BookingEngine` | Prix et disponibilités d’un séjour, création d’une demande de réservation |
| `ReservationWorkflow` | Cycle de vie : annonce, confirmation, refus, annulation, expiration, fin de séjour, paiements, remboursements, rappel des arrivées |
| `ResidenceSearch` | Recherche et filtres de la liste publique |
| `PropertyListing`, `UnitListing`, `ReservationListing`, `PaymentListing` | Filtres des listes de l’administration, partagés par la page et son export Excel |
| `PropertyModeration`, `PropertyInsights` | Soumission et validation des établissements, liste de contrôle avant publication |
| `OccupancyCalendar` | Calendrier d’occupation des unités |
| `Payments/OnlinePayments`, `Payments/PaymentGateways` | Paiement en ligne indépendant de l’agrégateur (CinetPay, FedaPay) |
| `PayoutLedger` | Sommes dues aux propriétaires et reversements |
| `SubscriptionManager` | Abonnements des propriétaires : essais, factures, rappels, suspensions |
| `DashboardStats` | Chiffres des tableaux de bord |
| `GalleryManager` | Logo et galeries photo sur le disque public |

## Prix d’une nuit et disponibilités

`BookingEngine::quote()` calcule le prix de chaque nuit, du plus précis au plus général :

1. prix fixé pour ce jour dans le calendrier (`availabilities.price`) ;
2. tarif saisonnier couvrant la date (`unit_rates`) ;
3. prix du week-end, pour les nuits du vendredi et du samedi ;
4. prix promotionnel, s’il est inférieur au prix de base ;
5. prix de base.

Logements disponibles pour une unité :

> quantité − réservations qui chevauchent et bloquent − plus fort blocage du calendrier − maintenances en cours

Une réservation **bloque** si elle est confirmée, terminée, ou en attente et non expirée (scope `Reservation::blocking()`).

`book()` revérifie tout dans une transaction, avec un verrou sur les unités (`lockForUpdate`) : deux clients ne peuvent pas prendre le dernier logement en même temps. Les prix nuit par nuit sont conservés dans `reservation_units.nightly_prices`.

Limites : 60 nuits au plus, arrivée jusqu’à 365 jours à l’avance, délai de réponse de l’établissement de 48 h, frais de service à 0 %. Elles se règlent dans **Paramètres du site** et sont lues par `SiteSettings::booking()`, qui retombe sur `config/booking.php` si rien n’est réglé.

## Paramètres du site

`App\Support\SiteSettings` (singleton) lit la table `settings`, groupe `site`, une seule fois par requête. Il est partagé avec toutes les vues sous le nom **`$site`** :

- `$site->name()`, `$site->email()`, `$site->get('contact_address')` ;
- `$site->phone()` et `$site->phoneHref()`, `$site->whatsappUrl()` ;
- `$site->socials()` : réseaux renseignés ;
- `$site->booking('request_ttl_hours')`.

**Contenu des pages** : les blocs (champs, listes, valeurs d’origine) sont décrits dans `config/site-content.php`. `$site->content('home_slides')` renvoie le contenu enregistré (réglage JSON `content.{bloc}`, groupe `content`) ou celui d’origine. `$site->visible('home_steps')` indique si une section est affichée. `$site->image($chemin)` et `$site->link($lien)` produisent les adresses. `SiteContentEditor` déduit du schéma les règles de validation, enregistre les images sur le disque `public` (dossier `site/`) et supprime celles qui sont remplacées. Pour rendre un nouveau texte modifiable : le décrire dans `config/site-content.php`, l’ajouter à une page, puis le lire dans la vue avec `$site->content()`. Le formulaire d’administration se construit seul.

Une clé jamais enregistrée prend sa valeur de `SiteSettings::DEFAULTS`. Une valeur enregistrée vide (champ facultatif effacé) reste vide. Si la base est injoignable, par exemple sur une page d’erreur, les valeurs par défaut s’appliquent. Ne plus écrire de coordonnées en dur dans les vues : passer par `$site`.

## Notifications

`ReservationUpdated` porte un événement :

| Événement | Destinataire |
| --- | --- |
| `CONFIRMED`, `REFUSED`, `CANCELLED`, `REFUNDED`, `PAID`, `EXPIRED` | Client : email et notification ; voyageur sans compte : email seul |
| `NEW_FOR_OWNER` | Propriétaire : email et notification |
| `PAID_FOR_OWNER`, `CANCELLED_BY_GUEST`, `EXPIRED_FOR_OWNER` | Propriétaire : notification seule |

Le lien d’une notification mène à l’espace client pour un client, à l’administration pour les autres.

Autres notifications :

- `ArrivalsReminder` : arrivées du lendemain ;
- `PropertySubmitted`, `PropertyModerated` : validation des établissements ;
- `SubscriptionUpdated`, `AdminSubscriptionAlert` : abonnements ;
- `PayoutRecorded` : reversements ;
- `NewContactMessage` : formulaire de contact ;
- `ResetPasswordNotification` : mot de passe oublié.

## Accès

- Middleware `role:…` sur chaque groupe de routes, politiques (`app/Policies`) sur chaque réservation ou paiement.
- `RedirectClientsToTheirSpace` : une page `GET /admin…` ouverte par un client le renvoie vers son espace. Restent accessibles le bon de réservation, le reçu de paiement et les notifications.
- `profile.complete` : un client sans téléphone, ville ou pays complète son profil avant d’accéder à son espace ou de réserver.
- Le retour et la notification des agrégateurs de paiement sont hors du groupe `web` : aucune session n’y est ouverte, ce qui évite de déconnecter le client.

## Conventions du projet

- **Pas de composants Blade** (`<x-…>`) : HTML direct ou `@include` de partiels.
- La colonne des boutons des tableaux s’intitule **« Action »**.
- **Téléphone** : toujours le partiel `partials/phone-field` (drapeau, indicatif, longueur selon le pays), avec une colonne `indicatif_telephone` à côté du numéro. Ses paramètres sont préfixés (`phoneValue`, `phoneDial`, `phoneRequired`, `phoneVariant`, `phoneId`, `phoneName`, `phoneDialName`, `phoneInvalid`), pour qu’une variable `$name` ou `$value` de la vue appelante ne s’y substitue pas. Le numéro est enregistré sans espaces ni indicatif (`PhoneNumber::normalize`) et validé par `PhoneNumberRule`.
- **Exports** : `ExcelExport::download($fichier, $titre, $colonnes, $lignes)`. Les types de colonne sont `text`, `money`, `number`, `decimal`, `percent`, `date` et `datetime`. Les lignes sont lues avec `lazy()`.
- **Modèles** : `$fillable` explicite, `#[Override]` sur `casts()`, bannières de sections (RELATIONS, SCOPES…), colonne `statut` castée en enum.
- Les montants sont des entiers en FCFA (`XOF`).
- Textes de l’interface en français, avec l’apostrophe typographique ’.

## Tests

`tests/Feature` couvre chaque module : réservation en ligne, espace client, connexion sociale, mot de passe oublié, paiements en ligne (agrégateur simulé avec `Http::fake`), reversements, abonnements, exports, pages et formulaires du site. Les exports sont relus avec le trait `Tests\Concerns\ReadsExcelExports`.

```bash
php artisan test
php artisan test --filter=BookingTest
```
