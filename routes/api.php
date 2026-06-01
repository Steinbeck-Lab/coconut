<?php

use App\Http\Controllers\API\Auth\LoginController;
use App\Http\Controllers\API\Auth\RegisterController;
use App\Http\Controllers\API\Auth\VerificationController;
use App\Http\Controllers\API\Schemas\Bioschemas\MolecularEntityController;
use App\Http\Controllers\API\SearchController;
use App\Rest\Controllers\CitationsController;
use App\Rest\Controllers\CollectionsController;
use App\Rest\Controllers\MoleculesController;
use App\Rest\Controllers\OrganismsController;
use App\Rest\Controllers\PropertiesController;
use App\Rest\Controllers\ReportsController;
use App\Rest\Controllers\UsersController;
use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Features;
use Lomkit\Rest\Facades\Rest;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// User authentication
Route::prefix('auth')->group(function () {
    Route::post('/login', [LoginController::class, 'login']);
    Route::post('/register', [RegisterController::class, 'register']);

    Route::middleware(['auth:sanctum'])->group(function () {
        Route::get('/logout', [LoginController::class, 'logout']);

        if (Features::enabled(Features::emailVerification())) {
            Route::middleware(['auth:sanctum'])->group(function () {
                Route::get('/email/resend', [VerificationController::class, 'resend']);
            });
            Route::get('/email/verify/{id}/{hash}', [VerificationController::class, 'verify'])->name('verification.verify');
        }
    });
});

Route::middleware(['auth:sanctum', 'verified'])->group(function () {
    Rest::resource('molecules', MoleculesController::class);
    Rest::resource('collections', CollectionsController::class);
    Rest::resource('citations', CitationsController::class);
    Rest::resource('organisms', OrganismsController::class);
    Rest::resource('users', UsersController::class);
    Rest::resource('properties', PropertiesController::class);
    Rest::resource('reports', ReportsController::class);
});
Route::post('search', [SearchController::class, 'search'])->name('api.search');

Route::prefix('schemas')->group(function () {
    Route::prefix('bioschemas')->group(function () {
        Route::get('/{id}', [MolecularEntityController::class, 'moleculeSchema']);
    });
});
