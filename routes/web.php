<?php

use App\Http\Controllers\Admin;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ResidenceController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Site web
|--------------------------------------------------------------------------
*/

Route::get('/', HomeController::class)->name('home');
Route::get('/residences', [ResidenceController::class, 'index'])->name('residences.index');
Route::get('/residences/details', [ResidenceController::class, 'show'])->name('residences.show');

/*
|--------------------------------------------------------------------------
| Authentification
|--------------------------------------------------------------------------
*/

Route::middleware('guest')->group(function () {
    Route::get('/connexion', [LoginController::class, 'create'])->name('login');
    Route::post('/connexion', [LoginController::class, 'store'])->middleware('throttle:10,1');

    Route::get('/inscription', [RegisterController::class, 'choice'])->name('register');
    Route::get('/inscription/client', [RegisterController::class, 'createClient'])->name('register.client');
    Route::post('/inscription/client', [RegisterController::class, 'storeClient']);
    Route::get('/inscription/proprietaire', [RegisterController::class, 'createOwner'])->name('register.owner');
    Route::post('/inscription/proprietaire', [RegisterController::class, 'storeOwner']);
});

Route::post('/deconnexion', [LoginController::class, 'destroy'])->middleware('auth')->name('logout');

/*
|--------------------------------------------------------------------------
| Administration
|--------------------------------------------------------------------------
*/

Route::middleware('auth')->prefix('admin')->group(function () {
    Route::get('/', Admin\DashboardController::class)->name('dashboard');

    Route::name('admin.')->group(function () {
        // Référentiels de la plateforme
        Route::middleware('role:super_admin,admin')->group(function () {
            Route::resource('types-etablissement', Admin\PropertyTypeController::class)
                ->except('show')
                ->parameters(['types-etablissement' => 'type']);

            Route::resource('types-unite', Admin\UnitTypeController::class)
                ->except('show')
                ->parameters(['types-unite' => 'type']);

            Route::resource('equipements', Admin\EquipmentController::class)
                ->except('show')
                ->parameters(['equipements' => 'equipement']);
        });

        // Hébergements : les administrateurs voient tout, un propriétaire uniquement les siens
        Route::middleware('role:super_admin,admin,owner')->group(function () {
            Route::resource('etablissements', Admin\PropertyController::class)
                ->except('show')
                ->parameters(['etablissements' => 'etablissement']);

            Route::get('unites', [Admin\UnitController::class, 'index'])->name('unites.index');
            Route::get('etablissements/{etablissement}/unites/creer', [Admin\UnitController::class, 'create'])->name('etablissements.unites.create');
            Route::post('etablissements/{etablissement}/unites', [Admin\UnitController::class, 'store'])->name('etablissements.unites.store');
            Route::get('unites/{unite}/modifier', [Admin\UnitController::class, 'edit'])->name('unites.edit');
            Route::put('unites/{unite}', [Admin\UnitController::class, 'update'])->name('unites.update');
            Route::delete('unites/{unite}', [Admin\UnitController::class, 'destroy'])->name('unites.destroy');
        });
    });
});
