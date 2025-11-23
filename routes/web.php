<?php

use App\Http\Controllers\ApplicationController;
use App\Http\Controllers\Auth\SocialController;
use App\Http\Controllers\CmsProxyController;
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

// CMS API Proxy - CSRF protected for generic proxy
Route::match(['GET', 'POST'], '/cms-proxy', [CmsProxyController::class, 'proxy'])->name('cms.proxy');

// CMS Depict endpoint - No CSRF needed (used in img tags)
// Rate limited per minute based on CMS_RATE_LIMIT env variable (default: 200, 0 = no limit)
$rateLimit = (int) config('services.cheminf.rate_limit', 200);
$depictRoute = Route::get('/cms/depict2d', [CmsProxyController::class, 'depict2D'])->name('cms.depict2d');
if ($rateLimit > 0) {
    $depictRoute->middleware('throttle:'.$rateLimit.',1');
}

// Compound pages
Route::get('compound/coconut_id/{id}', MoleculeController::class)->name('old_compound');
Route::get('compounds/{id}', MoleculeController::class)->name('compound');

Route::get('collections/{id}', CollectionController::class)->name('collection');

Route::get('/search', Search::class)->name('browse');
