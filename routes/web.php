<?php

use App\Livewire\NewOrder;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

// ── Billing UI ─────────────────────────────────────────────────────────────
Route::get('/billing', NewOrder::class)->name('billing');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::view('dashboard', 'dashboard')->name('dashboard');
});

require __DIR__.'/settings.php';
