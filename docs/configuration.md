# Configuration et mise en production

Toutes les clés se renseignent dans `.env`. Le modèle commenté est `.env.example`. Après une modification en production : `php artisan config:cache`.

## Application

| Clé | Rôle |
| --- | --- |
| `APP_URL` | Adresse publique du site. Elle sert aux liens des emails et aux adresses de retour des paiements. |
| `APP_DEBUG` | `false` en production : les pages d’erreur du site s’affichent sans détail technique. |
| `DB_*` | Connexion MySQL |

## Emails

Par défaut `MAIL_MAILER=log` : les emails sont écrits dans `storage/logs/laravel.log` au lieu d’être envoyés. C’est pratique en local pour récupérer un lien de réinitialisation ou de confirmation.

En production, renseigner un serveur SMTP :

```env
MAIL_MAILER=smtp
MAIL_HOST=…
MAIL_PORT=587
MAIL_USERNAME=…
MAIL_PASSWORD=…
MAIL_FROM_ADDRESS=no-reply@dsholding.ci
MAIL_FROM_NAME="DS HOLDING"
```

Ces emails dépendent de l’envoi : confirmation de l’adresse, mot de passe oublié, réservations, paiements, abonnements.

## Paiement en ligne

| Clé | Rôle |
| --- | --- |
| `PAYMENT_GATEWAY` | `cinetpay` (production) ou `fedapay` (tests en local, sans registre de commerce) |
| `CINETPAY_API_KEY`, `CINETPAY_SITE_ID`, `CINETPAY_SECRET_KEY` | Compte CinetPay (app.cinetpay.com > Intégrations) |
| `FEDAPAY_SECRET_KEY`, `FEDAPAY_ENVIRONMENT` | Clé `sk_sandbox_…` et `sandbox`, ou `live` |

Sans clé pour l’agrégateur choisi, le paiement en ligne est désactivé et les boutons « Payer en ligne » sont masqués.

L’agrégateur appelle `APP_URL/paiements/en-ligne/{cinetpay|fedapay}/notification`. Cette adresse doit être joignable depuis Internet, en HTTPS. Si une notification se perd, la tâche `payments:sync` revérifie toutes les 10 minutes les paiements restés en attente.

## Connexion avec Google / Facebook

Les boutons n’apparaissent que si l’identifiant **et** le secret du fournisseur sont renseignés.

| Clé | Où l’obtenir |
| --- | --- |
| `GOOGLE_CLIENT_ID`, `GOOGLE_CLIENT_SECRET` | console.cloud.google.com > API et services > Identifiants > ID client OAuth (application Web) |
| `FACEBOOK_CLIENT_ID`, `FACEBOOK_CLIENT_SECRET` | developers.facebook.com > Mes apps > Créer une app > Connexion Facebook |

Adresses de retour à déclarer chez chaque fournisseur :

- `APP_URL/connexion/google/retour`
- `APP_URL/connexion/facebook/retour`

## Réservation en ligne

Ces règles se modifient dans **Administration > Paramètres du site** (super administrateur). Les variables ci-dessous ne servent que de valeurs par défaut tant que rien n’y est enregistré.

| Clé | Défaut | Rôle |
| --- | --- | --- |
| `BOOKING_REQUEST_TTL_HOURS` | `48` | Délai laissé à l’établissement pour répondre. Passé ce délai, la demande expire. |
| `BOOKING_SERVICE_FEE_RATE` | `0` | Frais de service ajoutés au séjour, en % de l’hébergement. Affichés sur la fiche et le récapitulatif s’ils sont supérieurs à 0. |
| `BOOKING_MAX_NIGHTS` | `60` | Durée maximale d’un séjour réservé en ligne |
| `BOOKING_MAX_DAYS_AHEAD` | `365` | Réservation possible jusqu’à ce nombre de jours à l’avance |

## Abonnements des propriétaires

Réglés dans **Administration > Formules > Réglages** et non dans `.env` :

- **Abonnement obligatoire** : si activé, seuls les établissements des propriétaires en règle (ou exemptés) sont visibles sur le site.
- **Délai de grâce** : jours laissés après l’échéance d’une facture avant la suspension.

## Tâches planifiées

Une seule tâche cron sur le serveur suffit :

```cron
* * * * * cd /chemin/du/projet && php artisan schedule:run >> /dev/null 2>&1
```

| Commande | Fréquence | Rôle |
| --- | --- | --- |
| `reservations:expire` | toutes les heures | Annule les demandes sans réponse dans le délai, prévient le client et l’établissement, rembourse un paiement déjà effectué |
| `reservations:remind-arrivals` | chaque jour à 7 h | Prévient chaque propriétaire des arrivées du lendemain |
| `subscriptions:process` | chaque jour à 6 h | Fin des essais, factures de renouvellement, rappels avant l’échéance, suspensions |
| `payments:sync` | toutes les 10 minutes | Revérifie les paiements en ligne en attente (seulement si le paiement en ligne est configuré) |

Chaque commande peut aussi être lancée à la main, par exemple `php artisan reservations:expire`.

## Mise en production : liste de contrôle

1. `.env` : `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL` en HTTPS, base MySQL, SMTP.
2. Clés de paiement (`PAYMENT_GATEWAY=cinetpay`) et, si souhaité, Google / Facebook avec leurs adresses de retour.
3. Installer et compiler :

   ```bash
   composer install --no-dev --optimize-autoloader
   npm ci && npm run build
   php artisan migrate --force
   php artisan storage:link      # images envoyées depuis Paramètres du site > Contenu des pages
   php artisan config:cache && php artisan route:cache && php artisan view:cache
   ```

4. Tâche cron du planificateur.
5. Faire relire par un conseil juridique les pages **Conditions d’utilisation** et **Confidentialité**. Vérifier le nom, les coordonnées et les réseaux sociaux dans **Administration > Paramètres du site**.
6. Remplacer les formules de démonstration (prix fictifs) dans **Administration > Formules**.
