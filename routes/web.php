<?php

use App\Livewire\History\HistoryPage;
use App\Livewire\Inventory\InventoryList;
use App\Livewire\Proposals\ProposalPage;
use App\Livewire\Recipes\RecipeSearch;
use App\Livewire\Settings\SettingsPage;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect('/propose'));
Route::get('/inventory', InventoryList::class)->name('inventory');
Route::get('/propose', ProposalPage::class)->name('propose');
Route::get('/search', RecipeSearch::class)->name('search');
Route::get('/history', HistoryPage::class)->name('history');
Route::get('/settings', SettingsPage::class)->name('settings');
