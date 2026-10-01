<?php

use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\EquipmentController;
use App\Http\Controllers\Admin\LoginLogController;
use App\Http\Controllers\Admin\NotificationController;
use App\Http\Controllers\Admin\OnlinePaymentController;
use App\Http\Controllers\Admin\OwnerPayoutController;
use App\Http\Controllers\Admin\OwnerSubscriptionController;
use App\Http\Controllers\Admin\PaymentController;
use App\Http\Controllers\Admin\PayoutController;
use App\Http\Controllers\Admin\ProfileController;
use App\Http\Controllers\Admin\PropertyController;
use App\Http\Controllers\Admin\PropertyTypeController;
use App\Http\Controllers\Admin\PropertyValidationController;
use App\Http\Controllers\Admin\ReservationCalendarController;
use App\Http\Controllers\Admin\ReservationController;
use App\Http\Controllers\Admin\SubscriptionController;
use App\Http\Controllers\Admin\SubscriptionPlanController;
use App\Http\Controllers\Admin\UnitController;
use App\Http\Controllers\Admin\UnitTypeController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Auth\EmailVerificationController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\PaymentGatewayController;
use App\Http\Controllers\ResidenceController;
use Illuminate\Support\Facades\Route;

// =========================
// SITE PUBLIC
// =========================

Route::get('/', HomeController::class)
    ->name('home');

// Résidences : liste et fiche détaillée
Route::get('/residences', [ResidenceController::class, 'index'])
    ->name('residences.index');

Route::get('/residences/details', [ResidenceController::class, 'show'])
    ->name('residences.show');

// =========================
// PAIEMENT EN LIGNE (CinetPay en production, FedaPay en test)
// =========================
// Notification et retour : appelés depuis l'agrégateur, sans les cookies du client, donc hors du groupe « web »
// (une session ouverte ici remplacerait celle du client et le déconnecterait). Le résultat, lui, retrouve la session.

Route::prefix('paiements/en-ligne')
    ->name('paiements.')
    ->group(function () {
        Route::match(['get', 'post'], '/{passerelle}/notification', [PaymentGatewayController::class, 'notify'])
            ->withoutMiddleware('web')
            ->whereIn('passerelle', ['cinetpay', 'fedapay'])
            ->name('notify');
        Route::match(['get', 'post'], '/retour/{transaction}', [PaymentGatewayController::class, 'return'])
            ->withoutMiddleware('web')
            ->name('return');
        Route::get('/resultat/{transaction}', [PaymentGatewayController::class, 'result'])
            ->middleware('auth')
            ->name('result');
    });

// =========================
// AUTHENTIFICATION
// =========================

// « guest » : un utilisateur déjà connecté est renvoyé vers son tableau de bord
Route::get('/connexion', [LoginController::class, 'create'])
    ->name('login')
    ->middleware('guest');

Route::post('/connexion', [LoginController::class, 'store'])
    ->name('login.store')
    ->middleware(['guest', 'throttle:10,1']);

Route::post('/deconnexion', [LoginController::class, 'destroy'])
    ->name('logout')
    ->middleware('auth');

// Inscription en deux temps : choix du profil, puis formulaire client ou propriétaire
Route::get('/inscription', [RegisterController::class, 'choice'])
    ->name('register')
    ->middleware('guest');

Route::prefix('inscription')
    ->name('register.')
    ->middleware('guest')
    ->group(function () {
        Route::get('/client', [RegisterController::class, 'createClient'])
            ->name('client');
        Route::post('/client', [RegisterController::class, 'storeClient'])
            ->name('client.store');
        Route::get('/proprietaire', [RegisterController::class, 'createOwner'])
            ->name('owner');
        Route::post('/proprietaire', [RegisterController::class, 'storeOwner'])
            ->name('owner.store');
    });

// Confirmation de l’adresse email : page d’attente, lien signé reçu par email, renvoi du lien
Route::prefix('email')
    ->name('verification.')
    ->middleware('auth')
    ->group(function () {
        Route::get('/verification', [EmailVerificationController::class, 'notice'])
            ->name('notice');
        Route::get('/verification/{id}/{hash}', [EmailVerificationController::class, 'verify'])
            ->name('verify')
            ->middleware(['signed', 'throttle:6,1']);
        Route::post('/verification/renvoyer', [EmailVerificationController::class, 'send'])
            ->name('send')
            ->middleware('throttle:6,1');
    });

// =========================
// DASHBOARD
// =========================

// « verified » : un compte inscrit depuis le site doit d’abord confirmer son adresse email
Route::get('/admin', DashboardController::class)
    ->name('dashboard')
    ->middleware(['auth', 'verified']);

// =========================
// RÉFÉRENTIELS DE LA PLATEFORME (administrateurs)
// =========================

// Types d'établissement
Route::prefix('admin/types-etablissement')
    ->name('admin.types-etablissement.')
    ->middleware(['auth', 'verified', 'role:super_admin,admin'])
    ->group(function () {
        Route::get('/', [PropertyTypeController::class, 'index'])
            ->name('index');
        Route::get('/creer', [PropertyTypeController::class, 'create'])
            ->name('create');
        Route::post('/', [PropertyTypeController::class, 'store'])
            ->name('store');
        Route::get('/{type}/modifier', [PropertyTypeController::class, 'edit'])
            ->name('edit');
        Route::put('/{type}', [PropertyTypeController::class, 'update'])
            ->name('update');
        Route::delete('/{type}', [PropertyTypeController::class, 'destroy'])
            ->name('destroy');
    });

// Types d'unité
Route::prefix('admin/types-unite')
    ->name('admin.types-unite.')
    ->middleware(['auth', 'verified', 'role:super_admin,admin'])
    ->group(function () {
        Route::get('/', [UnitTypeController::class, 'index'])
            ->name('index');
        Route::get('/creer', [UnitTypeController::class, 'create'])
            ->name('create');
        Route::post('/', [UnitTypeController::class, 'store'])
            ->name('store');
        Route::get('/{type}/modifier', [UnitTypeController::class, 'edit'])
            ->name('edit');
        Route::put('/{type}', [UnitTypeController::class, 'update'])
            ->name('update');
        Route::delete('/{type}', [UnitTypeController::class, 'destroy'])
            ->name('destroy');
    });

// Équipements
Route::prefix('admin/equipements')
    ->name('admin.equipements.')
    ->middleware(['auth', 'verified', 'role:super_admin,admin'])
    ->group(function () {
        Route::get('/', [EquipmentController::class, 'index'])
            ->name('index');
        Route::get('/creer', [EquipmentController::class, 'create'])
            ->name('create');
        Route::post('/', [EquipmentController::class, 'store'])
            ->name('store');
        Route::get('/{equipement}/modifier', [EquipmentController::class, 'edit'])
            ->name('edit');
        Route::put('/{equipement}', [EquipmentController::class, 'update'])
            ->name('update');
        Route::delete('/{equipement}', [EquipmentController::class, 'destroy'])
            ->name('destroy');
    });

// =========================
// HÉBERGEMENTS (administrateurs et propriétaires)
// =========================
// Les administrateurs voient tout, un propriétaire uniquement les siens (PropertyPolicy)

// Établissements
Route::prefix('admin/etablissements')
    ->name('admin.etablissements.')
    ->middleware(['auth', 'verified', 'role:super_admin,admin,owner'])
    ->group(function () {
        Route::get('/', [PropertyController::class, 'index'])
            ->name('index');
        Route::get('/creer', [PropertyController::class, 'create'])
            ->name('create');
        Route::post('/', [PropertyController::class, 'store'])
            ->name('store');
        Route::get('/{etablissement}/modifier', [PropertyController::class, 'edit'])
            ->name('edit');
        Route::put('/{etablissement}', [PropertyController::class, 'update'])
            ->name('update');
        Route::delete('/{etablissement}', [PropertyController::class, 'destroy'])
            ->name('destroy');

        // Une unité se crée depuis son établissement
        Route::get('/{etablissement}/unites/creer', [UnitController::class, 'create'])
            ->name('unites.create');
        Route::post('/{etablissement}/unites', [UnitController::class, 'store'])
            ->name('unites.store');
    });

// Validation des établissements soumis par les propriétaires (administrateurs)
Route::prefix('admin/validations')
    ->name('admin.validations.')
    ->middleware(['auth', 'verified', 'role:super_admin,admin'])
    ->group(function () {
        Route::get('/', [PropertyValidationController::class, 'index'])
            ->name('index');
        Route::patch('/{etablissement}/approuver', [PropertyValidationController::class, 'approve'])
            ->name('approve');
        Route::patch('/{etablissement}/refuser', [PropertyValidationController::class, 'reject'])
            ->name('reject');
        Route::patch('/{etablissement}/suspendre', [PropertyValidationController::class, 'suspend'])
            ->name('suspend');
        Route::patch('/{etablissement}/retablir', [PropertyValidationController::class, 'reinstate'])
            ->name('reinstate');
    });

// Unités
Route::prefix('admin/unites')
    ->name('admin.unites.')
    ->middleware(['auth', 'verified', 'role:super_admin,admin,owner'])
    ->group(function () {
        Route::get('/', [UnitController::class, 'index'])
            ->name('index');
        Route::get('/{unite}/modifier', [UnitController::class, 'edit'])
            ->name('edit');
        Route::put('/{unite}', [UnitController::class, 'update'])
            ->name('update');
        Route::delete('/{unite}', [UnitController::class, 'destroy'])
            ->name('destroy');
    });

// =========================
// RÉSERVATIONS ET PAIEMENTS
// =========================
// Tous les rôles : un administrateur voit tout, un propriétaire les réservations de ses établissements,
// un client les siennes (ReservationPolicy). Une réservation est désignée par sa référence.

// Réservations
Route::prefix('admin/reservations')
    ->name('admin.reservations.')
    ->middleware(['auth', 'verified'])
    ->group(function () {
        Route::get('/', [ReservationController::class, 'index'])
            ->name('index');
        Route::get('/export', [ReservationController::class, 'export'])
            ->name('export');
        Route::get('/calendrier', [ReservationCalendarController::class, 'index'])
            ->middleware('role:super_admin,admin,owner')
            ->name('calendar');
        Route::get('/{reservation}', [ReservationController::class, 'show'])
            ->name('show');
        Route::get('/{reservation}/bon', [ReservationController::class, 'voucher'])
            ->name('voucher');
        Route::patch('/{reservation}/confirmer', [ReservationController::class, 'confirm'])
            ->name('confirm');
        Route::patch('/{reservation}/annuler', [ReservationController::class, 'cancel'])
            ->name('cancel');
        Route::patch('/{reservation}/terminer', [ReservationController::class, 'complete'])
            ->name('complete');
        Route::patch('/{reservation}/non-presente', [ReservationController::class, 'noShow'])
            ->name('no-show');
        Route::patch('/{reservation}/rembourser', [ReservationController::class, 'refund'])
            ->name('refund');
        Route::patch('/{reservation}/notes', [ReservationController::class, 'updateNotes'])
            ->name('notes');
        Route::post('/{reservation}/paiements', [ReservationController::class, 'storePayment'])
            ->name('payments.store');
        Route::post('/{reservation}/payer-en-ligne', [OnlinePaymentController::class, 'reservation'])
            ->name('pay-online');
    });

// Paiements : un paiement est désigné par son identifiant de transaction.
// Le reçu est aussi accessible au client, pour ses propres paiements (PaymentPolicy).
Route::prefix('admin/paiements')
    ->name('admin.paiements.')
    ->middleware(['auth', 'verified'])
    ->group(function () {
        Route::get('/{paiement}/recu', [PaymentController::class, 'receipt'])
            ->name('receipt');

        Route::middleware('role:super_admin,admin,owner')->group(function () {
            Route::get('/', [PaymentController::class, 'index'])
                ->name('index');
            Route::get('/export', [PaymentController::class, 'export'])
                ->name('export');
            Route::get('/{paiement}', [PaymentController::class, 'show'])
                ->name('show');
            Route::patch('/{paiement}/rembourser', [PaymentController::class, 'refund'])
                ->name('refund');
        });
    });

// =========================
// REVERSEMENTS AUX PROPRIÉTAIRES
// =========================
// DS Holding encaisse les réservations en ligne puis reverse à chaque propriétaire sa part.
// Un reversement est désigné par son numéro ; son relevé est aussi ouvert au propriétaire concerné.

Route::prefix('admin/reversements')
    ->name('admin.reversements.')
    ->middleware(['auth', 'verified'])
    ->group(function () {
        Route::get('/{reversement}/releve', [PayoutController::class, 'statement'])
            ->name('statement');

        Route::middleware('role:super_admin,admin')->group(function () {
            Route::get('/', [PayoutController::class, 'index'])
                ->name('index');
            Route::get('/proprietaires/{proprietaire}', [PayoutController::class, 'owner'])
                ->name('owner');
            Route::post('/proprietaires/{proprietaire}', [PayoutController::class, 'store'])
                ->name('store');
            Route::patch('/{reversement}/annuler', [PayoutController::class, 'cancel'])
                ->name('cancel');
        });
    });

// Mes reversements (propriétaire)
Route::prefix('admin/mes-reversements')
    ->name('admin.mes-reversements.')
    ->middleware(['auth', 'verified', 'role:owner'])
    ->group(function () {
        Route::get('/', [OwnerPayoutController::class, 'index'])
            ->name('index');
        Route::put('/coordonnees', [OwnerPayoutController::class, 'updateAccount'])
            ->name('account');
    });

// =========================
// ABONNEMENTS DES PROPRIÉTAIRES
// =========================
// Formules et suivi : administrateurs. « Mon abonnement » : propriétaire.

// Formules d'abonnement et réglages
Route::prefix('admin/formules')
    ->name('admin.formules.')
    ->middleware(['auth', 'verified', 'role:super_admin,admin'])
    ->group(function () {
        Route::get('/', [SubscriptionPlanController::class, 'index'])
            ->name('index');
        Route::get('/creer', [SubscriptionPlanController::class, 'create'])
            ->name('create');
        Route::post('/', [SubscriptionPlanController::class, 'store'])
            ->name('store');
        Route::put('/reglages', [SubscriptionPlanController::class, 'updateSettings'])
            ->name('settings');
        Route::get('/{formule}/modifier', [SubscriptionPlanController::class, 'edit'])
            ->name('edit');
        Route::put('/{formule}', [SubscriptionPlanController::class, 'update'])
            ->name('update');
        Route::delete('/{formule}', [SubscriptionPlanController::class, 'destroy'])
            ->name('destroy');
    });

// Abonnements des propriétaires et factures
Route::prefix('admin/abonnements')
    ->name('admin.abonnements.')
    ->middleware(['auth', 'verified', 'role:super_admin,admin'])
    ->group(function () {
        Route::get('/', [SubscriptionController::class, 'index'])
            ->name('index');
        Route::post('/', [SubscriptionController::class, 'store'])
            ->name('store');
        Route::get('/{abonnement}', [SubscriptionController::class, 'show'])
            ->name('show');
        Route::patch('/{abonnement}/formule', [SubscriptionController::class, 'changePlan'])
            ->name('plan');
        Route::patch('/{abonnement}/prolonger-essai', [SubscriptionController::class, 'extendTrial'])
            ->name('extend');
        Route::patch('/{abonnement}/resilier', [SubscriptionController::class, 'cancel'])
            ->name('cancel');
        Route::patch('/factures/{facture}/payer', [SubscriptionController::class, 'markPaid'])
            ->name('invoices.pay');
        Route::patch('/factures/{facture}/annuler', [SubscriptionController::class, 'cancelInvoice'])
            ->name('invoices.cancel');
    });

// Mon abonnement (propriétaire) ; la facture imprimable est aussi ouverte aux administrateurs
Route::prefix('admin/mon-abonnement')
    ->name('admin.abonnement.')
    ->middleware(['auth', 'verified'])
    ->group(function () {
        Route::get('/factures/{facture}', [OwnerSubscriptionController::class, 'invoice'])
            ->name('invoice');

        Route::middleware('role:owner')->group(function () {
            Route::get('/', [OwnerSubscriptionController::class, 'show'])
                ->name('show');
            Route::post('/', [OwnerSubscriptionController::class, 'subscribe'])
                ->name('subscribe');
            Route::post('/factures/{facture}/payer-en-ligne', [OnlinePaymentController::class, 'invoice'])
                ->name('pay-online');
        });
    });

// =========================
// UTILISATEURS (administrateurs)
// =========================
// Personne n'agit sur son propre compte ; seul un super administrateur agit sur un administrateur (UserPolicy)

Route::prefix('admin/utilisateurs')
    ->name('admin.utilisateurs.')
    ->middleware(['auth', 'verified', 'role:super_admin,admin'])
    ->group(function () {
        Route::get('/', [UserController::class, 'index'])
            ->name('index');
        Route::get('/connexions', [LoginLogController::class, 'index'])
            ->name('connexions');
        Route::get('/{utilisateur}', [UserController::class, 'show'])
            ->name('show');
        Route::patch('/{utilisateur}/suspendre', [UserController::class, 'suspend'])
            ->name('suspend');
        Route::patch('/{utilisateur}/reactiver', [UserController::class, 'reactivate'])
            ->name('reactivate');
        Route::patch('/{utilisateur}/role', [UserController::class, 'updateRole'])
            ->name('role');
    });

// =========================
// MON PROFIL (tous les rôles)
// =========================
// Sans « verified » : après un changement d'adresse, la page reste accessible le temps de la confirmer

Route::prefix('admin/profil')
    ->name('admin.profil.')
    ->middleware(['auth'])
    ->group(function () {
        Route::get('/', [ProfileController::class, 'edit'])
            ->name('edit');
        Route::put('/', [ProfileController::class, 'update'])
            ->name('update');
        Route::put('/mot-de-passe', [ProfileController::class, 'updatePassword'])
            ->name('password');
    });

// =========================
// NOTIFICATIONS (tous les rôles)
// =========================

Route::prefix('admin/notifications')
    ->name('admin.notifications.')
    ->middleware(['auth', 'verified'])
    ->group(function () {
        Route::get('/', [NotificationController::class, 'index'])
            ->name('index');
        Route::get('/flux', [NotificationController::class, 'feed'])
            ->name('feed');
        Route::post('/tout-lire', [NotificationController::class, 'readAll'])
            ->name('read-all');
        Route::delete('/lues', [NotificationController::class, 'destroyRead'])
            ->name('destroy-read');
        Route::get('/{notification}', [NotificationController::class, 'open'])
            ->name('open');
        Route::patch('/{notification}/lue', [NotificationController::class, 'markRead'])
            ->name('read');
        Route::delete('/{notification}', [NotificationController::class, 'destroy'])
            ->name('destroy');
    });

// =========================
// PAGE 404
// =========================

// Adresse inconnue : page 404 avec la session active (l'utilisateur connecté y est reconnu)
Route::fallback(fn () => abort(404));
