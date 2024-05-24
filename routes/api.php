<?php

use App\Http\Controllers\API\Auth\LoginController;
use App\Http\Controllers\API\Auth\RegisterController;
use App\Http\Controllers\API\Auth\UserController;
use App\Http\Controllers\API\Auth\VerificationController;
use App\Http\Controllers\API\CompoundController;
use App\Http\Controllers\API\Schemas\Bioschema\MolecularEntityController;
use App\Http\Controllers\API\SearchController;
use App\Http\Controllers\API\SubmissionController;
use App\Http\Controllers\DownloadController;
use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Features;
use \Lomkit\Rest\Facades\Rest;

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

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
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
    Rest::resource('users', \App\Rest\Controllers\UsersController::class);
});
    
// Route::middleware(['auth:sanctum', 'verified'])->group(function () {
    // Route::prefix('v1')->group(function () {

    //     // Search
    //     Route::post('/search/{smiles?}', [SearchController::class, 'search']);

    //     // Compounds and details
    //     Route::get('/compounds', [CompoundController::class, 'list'])->name('compounds.list');
    //     // Route::get('/compounds/{id}/{property?}/{key?}', [CompoundController::class, 'id'])->name('compound.property');

    //     // Schemas
    //     Route::get('/compounds', [CompoundController::class, 'list']);
    //     Route::prefix('schemas')->group(function () {
    //         Route::prefix('bioschema')->group(function () {
    //             Route::get('/{id}', [MolecularEntityController::class, 'moleculeSchema']);
    //         });
    //     });

// });