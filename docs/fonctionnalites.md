# Fonctionnalités

Ce que chaque profil peut faire, avec l’adresse des pages. Quatre rôles : **visiteur** (sans compte), **client**, **propriétaire**, **administrateur** (et super administrateur).

## Visiteur

| Page | Adresse | Contenu |
| --- | --- | --- |
| Accueil | `/` | Résidences à la une, chiffres de la plateforme et avis vérifiés, tirés de la base. Recherche (destination, dates, voyageurs). |
| Résidences | `/residences` | Liste filtrable : type, budget, ville, note, équipements, tri. Avec des dates, seules les résidences disponibles apparaissent. |
| Fiche | `/residences/{slug}` | Galerie (une miniature cliquée s’affiche en grand), présentation, équipements, logements avec prix du séjour et disponibilités, conditions, carte, avis, résidences similaires. |
| Questions fréquentes | `/questions-frequentes` | Réponses classées par thème, avec recherche instantanée. |
| Propriétaires | `/proprietaires` | Fonctionnement, outils de gestion, formules actives (tirées de l’administration). |
| Contact | `/contact` | Formulaire (message enregistré, administrateurs prévenus). Aussi en bas de l’accueil. |
| Conditions, confidentialité | `/conditions-utilisation`, `/confidentialite` | Textes décrivant le fonctionnement réel de la plateforme, à faire valider juridiquement. |

En bas de chaque page : inscription à la newsletter (lien de désinscription personnel `/newsletter/desinscription/{jeton}`).

**Réserver sans compte** : le bouton « Réserver » de la fiche mène à la création d’un compte client. Après l’inscription (ou la connexion), le visiteur revient sur le récapitulatif avec ses dates et ses logements.

## Compte et connexion

- Inscription client ou propriétaire, confirmation de l’adresse email.
- Connexion avec email et mot de passe, ou avec **Google / Facebook** pour les clients (boutons affichés seulement si les clés sont renseignées).
  - Compte inconnu : un compte client est créé.
  - Compte client existant avec la même adresse : il est relié.
  - Compte propriétaire ou administrateur : refusé, le mot de passe reste exigé.
- À la première connexion par Google / Facebook, le client complète son téléphone, sa ville et son pays (`/mon-compte/completer-mon-profil`).
- Mot de passe oublié : lien par email valable 60 minutes (`/mot-de-passe-oublie`). Le message est le même que l’adresse existe ou non.

## Client — espace `/mon-compte`

| Page | Contenu |
| --- | --- |
| Tableau de bord | Prochain séjour, chiffres (séjours à venir, nuits passées, reste à payer, favoris), activité récente. |
| Mes réservations | Onglets À venir / Passées / Annulées. Fiche détaillée : étapes, logements, paiements, montants, contact de l’établissement. |
| Payer | Paiement en ligne du solde (Mobile Money, carte) depuis la fiche de la réservation. |
| Annuler | Remboursement automatique si l’annulation intervient avant la date limite d’annulation gratuite. |
| Donner un avis | Après le séjour, une fois par réservation : note sur 10 et critères (propreté, confort, emplacement, accueil, qualité-prix). |
| Favoris | Cœur sur les cartes et les fiches ; liste des résidences enregistrées. |
| Notifications, Mon profil | Historique des notifications ; coordonnées, téléphone avec indicatif, mot de passe. |

Un client qui ouvre une ancienne adresse de l’administration (`/admin/...`) est renvoyé vers la page équivalente de son espace. Le bon de réservation et le reçu de paiement imprimables restent accessibles.

### Parcours de réservation

1. Sur la fiche, le client choisit ses dates et ses voyageurs : chaque logement affiche son prix pour le séjour et le nombre encore disponible.
2. Il choisit le nombre de logements par type ; le total se met à jour en direct.
3. Le récapitulatif (`/reserver/{slug}`) demande le voyageur principal, le téléphone, l’heure d’arrivée et l’acceptation des conditions.
4. La demande est créée **en attente** et l’établissement est prévenu. Le client peut déjà payer en ligne.
5. L’établissement confirme ou refuse.
6. Sans réponse dans le délai (48 h par défaut), la demande **expire** : le client et l’établissement sont prévenus et un paiement déjà effectué est remboursé.

## Propriétaire — administration `/admin`

- **Établissements** : formulaire par étapes (informations, localisation, contact et accueil, médias, publication, SEO), liste de contrôle avant soumission, soumission à validation, mise hors ligne.
- **Unités** : caractéristiques, prix (base, promotion, week-end, ménage), tarifs par période, galerie, équipements ; suppression bloquée si des séjours sont à venir.
- **Réservations** : liste filtrable, calendrier d’occupation, confirmation, refus, annulation, séjour terminé, client absent, paiements reçus sur place, remboursements, bon de réservation.
- **Paiements** : historique et reçus.
- **Mes reversements** : sommes reversées par DS Holding et coordonnées de versement.
- **Mon abonnement** : formule, factures, paiement en ligne.
- **Notifications** : nouvelle demande, paiement reçu, annulation par le client, demande expirée, arrivées du lendemain, rappel de facture.

Un propriétaire ne voit et ne modifie que ses propres établissements.

## Administrateur — administration `/admin`

- **Validations** : établissements soumis (publier ou refuser avec un motif), décisions récentes.
- **Référentiels** : types d’établissement, types d’unité, équipements. La suppression est bloquée tant qu’un élément est utilisé.
- **Utilisateurs** : clients, propriétaires, comptes à surveiller, suspension, historique des connexions.
- **Abonnements et formules** : formules et réglages (abonnement obligatoire, délai de grâce), factures, essais, résiliation.
- **Reversements** : sommes dues à chaque propriétaire, enregistrement d’un versement, relevé.
- **Messages** : messages du formulaire de contact (nouveau, lu, répondu), réponse par email, export des abonnés à la newsletter.
- **Notifications** : nouvel établissement à valider, message de contact, facture réglée en ligne, abonnement suspendu.

### Super administrateur seulement : Paramètres du site (`/admin/parametres`)

| Bloc | Réglages | Où ils apparaissent |
| --- | --- | --- |
| Identité | Nom du site, description pour les moteurs de recherche, présentation du pied de page | Titres d’onglet, balise description, pied de page |
| Coordonnées | Adresse, email, téléphone et WhatsApp (avec indicatif), horaires | Pied de page, menu mobile, page Contact, pages d’erreur, pages légales |
| Réseaux sociaux | Facebook, Instagram, LinkedIn, TikTok, YouTube | Icônes du pied de page, pour les réseaux renseignés |
| Réservation en ligne | Délai de réponse, frais de service, durée maximale, anticipation maximale | Fiche, récapitulatif, création et expiration des demandes |

Un champ facultatif laissé vide (WhatsApp, horaires, réseau) n’est pas affiché. Les changements s’appliquent immédiatement.

## Exports Excel

Chaque liste propose un bouton **Exporter** qui reprend les filtres en cours :

- établissements, unités, types d’établissement, types d’unité, équipements ;
- utilisateurs, réservations, paiements, reversements, abonnements, messages ;
- abonnés à la newsletter.

Le fichier `.xlsx` contient un en-tête figé, des filtres automatiques et des colonnes formatées (montants en FCFA, dates).

## Pages d’erreur

Pages aux couleurs du site pour les codes 400, 401, 403, 404, 405, 419, 429, 500 et 503, avec une action adaptée (se connecter, voir les résidences, revenir au formulaire, réessayer). Elles s’affichent même si l’application ou la base est en panne.
