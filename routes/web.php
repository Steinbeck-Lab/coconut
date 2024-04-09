<?php

use App\Livewire\Guides;
use App\Livewire\MoleculeDetails;
use App\Livewire\Policy;
use App\Livewire\Search;
use App\Livewire\Terms;
use App\Livewire\Welcome;
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

Route::get('/', Welcome::class);
Route::get('/policy', Policy::class);
Route::get('/terms', Terms::class);
Route::get('/guidelines', Guides::class);

// Compound pages
Route::get('compounds/{id}', MoleculeDetails::class)->name('compound');
Route::get('/search', Search::class)->name('browse');
