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
        Route::get('/user/info', [UserController::class, 'info']);

        Route::get('/email/verify/{id}', [VerificationController::class, 'verify']);
        Route::get('/email/resend', [VerificationController::class, 'resend']);
    });
});

Route::prefix('v1')->group(function () {

    // Search
    Route::post('/search/{smiles?}', [SearchController::class, 'search']);

    // Compounds and details
    Route::get('/compounds', [CompoundController::class, 'list'])->name('compounds.list');
    Route::get('/compounds/{id}/{property?}/{key?}', [CompoundController::class, 'id'])->name('compound.property');
    Route::get('/compounds/{id}/toggleBookmark', [CompoundController::class, 'toggleBookmark'])
        ->name('compound.toggle-bookmark');

    // Schemas
    Route::get('/compounds', [CompoundController::class, 'list']);
    Route::prefix('schemas')->group(function () {
        Route::prefix('bioschema')->group(function () {
            Route::get('/{id}', [MolecularEntityController::class, 'moleculeSchema']);
        });
    });

    // Submissions
    Route::middleware([
        'auth:sanctum',
    ])->group(function () {
        Route::delete('/compounds', [SubmissionController::class, 'report'])->name('submission.report');
    });

    // Compounds and details
    Route::get('/{id}/report', function ($id) {
        // return redirect('compounds/'.$id.'/report');
        return redirect(env('APP_URL').'/dashboard/reports/create'.'?compound_id='.$id);
    })->name('compound.report');

    Route::post('/compounds', [SubmissionAPIController::class, 'submission'])->name('compound.submission');
    Route::get('/download', [DownloadController::class, 'getDataDumpURL'])->name('downloadDataDump');
});
