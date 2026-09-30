<?php

use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\EquipmentController;
use App\Http\Controllers\Admin\PropertyController;
use App\Http\Controllers\Admin\PropertyTypeController;
use App\Http\Controllers\Admin\UnitController;
use App\Http\Controllers\Admin\UnitTypeController;
use App\Http\Controllers\Auth\EmailVerificationController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\HomeController;
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
// PAGE 404
// =========================

// Adresse inconnue : page 404 avec la session active (l'utilisateur connecté y est reconnu)
Route::fallback(fn () => abort(404));
