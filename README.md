# Residence Manager — DS Holding

Plateforme de réservation de résidences meublées, hôtels, villas et appartements en Côte d’Ivoire :

- un **site public** où les voyageurs cherchent, comparent et réservent ;
- un **espace client** (`/mon-compte`) pour suivre, payer, annuler et noter ses séjours ;
- une **administration** (`/admin`) pour les propriétaires (établissements, réservations, paiements, reversements, abonnement) et l’équipe DS Holding (validation, référentiels, utilisateurs, abonnements, messages).

## Stack

- Laravel 13, PHP 8.3, MySQL
- Bootstrap 5.3, Font Awesome 6, Leaflet, compilés par Vite (HTML/CSS/JS, sans composants Blade)
- Laravel Socialite (Google, Facebook), OpenSpout (exports Excel)
- Paiement en ligne : CinetPay (production) ou FedaPay (tests)
- Langue `fr`, fuseau `Africa/Abidjan`, montants en FCFA

## Installation

```bash
composer install
npm install
cp .env.example .env          # renseigner la connexion MySQL
php artisan key:generate
php artisan migrate --seed    # données de démonstration
php artisan storage:link
npm run dev                   # ou npm run build
php artisan serve
```

Les paiements en ligne, la connexion Google / Facebook et l’envoi réel des emails demandent des clés : voir [docs/configuration.md](docs/configuration.md).

## Comptes de démonstration

Mot de passe commun : `Residence@2026`.

| Compte | Rôle |
| --- | --- |
| `superadmin@dsholding.ci` | Super administrateur |
| `admin@dsholding.ci` | Administrateur |
| `owner@dsholding.ci` | Propriétaire |
| `client@dsholding.ci` | Client |

Ces quatre comptes sont exemptés d’abonnement (colonne `subscription_exempt`). Les prix des formules de démonstration sont fictifs.

## Documentation

| Document | Contenu |
| --- | --- |
| [docs/fonctionnalites.md](docs/fonctionnalites.md) | Ce que chaque profil peut faire, page par page |
| [docs/architecture.md](docs/architecture.md) | Organisation du code, services, parcours de réservation, conventions |
| [docs/configuration.md](docs/configuration.md) | Variables `.env`, paiement, connexion sociale, emails, tâches planifiées, mise en production |

## Tests

```bash
php artisan test        # suite complète (base SQLite en mémoire)
vendor/bin/pint         # style du code
```
