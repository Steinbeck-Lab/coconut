<?php

use App\Http\Controllers\ApplicationController;
use App\Http\Controllers\Auth\SocialController;
use App\Http\Controllers\CollectionController;
use App\Http\Controllers\MoleculeController;
use App\Livewire\About;
use App\Livewire\CollectionList;
use App\Livewire\Download;
use App\Livewire\Guides;
use App\Livewire\Policy;
use App\Livewire\Search;
use App\Livewire\Stats;
use App\Livewire\Terms;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Request;

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
Route::get('/stats', Stats::class);

Route::get('/collections', CollectionList::class)->name('collections.index');

// Compound pages
Route::get('compound/coconut_id/{id}', MoleculeController::class)->name('old_compound');
Route::get('compounds/{id}', MoleculeController::class)->name('compound');

Route::get('collections/{id}', CollectionController::class)->name('collection');

Route::get('/search', Search::class)->name('browse');

Route::get('/_debug-headers', function (Request $request) {
    return [
        'config_app_url'    => config('app.url'),
        'config_app_env'    => config('app.env'),
        'request_scheme'    => $request->getScheme(),
        'full_url'          => $request->fullUrl(),
        'x_forwarded_proto' => $request->server('HTTP_X_FORWARDED_PROTO'),
        'x_forwarded_host'  => $request->server('HTTP_X_FORWARDED_HOST'),
        'x_forwarded_port'  => $request->server('HTTP_X_FORWARDED_PORT'),
        'host'              => $request->getHost(),
    ];
});