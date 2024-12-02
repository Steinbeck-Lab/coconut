<?php

use App\Http\Controllers\ApplicationController;
use App\Http\Controllers\Auth\SocialController;
use App\Http\Controllers\MoleculeController;
use App\Livewire\About;
use App\Livewire\CollectionList;
use App\Livewire\Download;
use App\Livewire\Guides;
use App\Livewire\Policy;
use App\Livewire\Search;
use App\Livewire\Terms;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::group([
    'prefix' => 'auth',
], function () {
    Route::get('/login/{service}', [SocialController::class, 'redirectToProvider']);
    Route::get('/login/{service}/callback', [SocialController::class, 'handleProviderCallback']);
});

Route::get('/', ApplicationController::class);

Route::get('/privacy-policy', Policy::class);
Route::get('/terms-of-service', Terms::class);
Route::get('/guidelines', Guides::class);
Route::get('/about', About::class);
Route::get('/download', Download::class);

Route::get('/collections', CollectionList::class)->name('collections.index');

// Compound pages
Route::get('compound/coconut_id/{id}', MoleculeController::class)->name('old_compound');
Route::get('compounds/{id}', MoleculeController::class)->name('compound');
Route::get('/search', Search::class)->name('browse');
