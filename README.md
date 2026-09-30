# Residence Manager — DS Holding

Application web de gestion de résidences, d'hôtels et de réservations d'appartements.
Reprise en Laravel de l'ancien projet PHP `residence-manager`.

## Stack

- Laravel 13, PHP 8.3, MySQL (base `residence_manager_database`)
- Bootstrap 5.3 (CSS et JS), Font Awesome 6, Leaflet, compilés par Vite
- Langue `fr`, fuseau `Africa/Abidjan`, montants en FCFA

## Installation

```bash
composer install
npm install
cp .env.example .env   # puis renseigner la connexion MySQL
php artisan key:generate
php artisan migrate --seed
php artisan storage:link
npm run build           # ou npm run dev pendant le développement
```

## Fonctionnalités

- **Site public** : accueil, liste des résidences, détail d'une résidence (contenu de démonstration).
- **Authentification** : connexion (5 essais par minute), inscription client ou propriétaire.
- **Administration** (`/admin`) :
  - types d'établissement, types d'unité, équipements (administrateurs) ;
  - établissements : formulaire en 6 étapes (informations, localisation, contact & accueil, médias, publication, SEO) ;
  - unités : caractéristiques, prix, équipements, galerie.
  Un propriétaire ne voit et ne modifie que ses propres établissements et unités.

## Organisation

| Dossier | Contenu |
| --- | --- |
| `app/Http/Controllers/Admin` | Back-office |
| `app/Http/Requests` | Validation des formulaires |
| `app/Services/GalleryManager.php` | Enregistrement du logo et des galeries sur le disque public |
| `resources/views/site`, `auth`, `admin` | Vues Blade |
| `resources/css`, `resources/js` | Styles et scripts (points d'entrée déclarés dans `vite.config.js`) |

## Tests

```bash
php artisan test
```
