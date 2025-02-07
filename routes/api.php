<?php

use App\Http\Controllers\API\Auth\LoginController;
use App\Http\Controllers\API\Auth\RegisterController;
use App\Http\Controllers\API\Auth\VerificationController;
use App\Http\Controllers\API\Schemas\Bioschemas\MolecularEntityController;
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

Route::post('search', [\App\Http\Controllers\API\SearchController::class, 'search'])->name('api.search');

Route::middleware(['auth:sanctum', 'verified'])->group(function () {
    Rest::resource('molecules', \App\Rest\Controllers\MoleculesController::class);
    Rest::resource('collections', \App\Rest\Controllers\CollectionsController::class);
    Rest::resource('citations', \App\Rest\Controllers\CitationsController::class);
    Rest::resource('organisms', \App\Rest\Controllers\OrganismsController::class);
    Rest::resource('users', \App\Rest\Controllers\UsersController::class);
    Rest::resource('properties', \App\Rest\Controllers\PropertiesController::class);
});

Route::prefix('schemas')->group(function () {
    Route::prefix('bioschemas')->group(function () {
        Route::get('/{id}', [MolecularEntityController::class, 'moleculeSchema']);
    });
});
