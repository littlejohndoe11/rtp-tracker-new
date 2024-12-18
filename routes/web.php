<?php

use App\Http\Controllers\Admin\GameController as AdminGameController;
use App\Http\Controllers\GameController;
use Illuminate\Support\Facades\Route;

// Public routes
Route::get('/', [GameController::class, 'index'])->name('games.index');
Route::get('/scrape', [GameController::class, 'scrapeRTP'])->name('games.scrape');

// Admin routes
Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('/games', [AdminGameController::class, 'index'])->name('games.index');
    Route::get('/games/create', [AdminGameController::class, 'create'])->name('games.create');
    Route::post('/games', [AdminGameController::class, 'store'])->name('games.store');
    Route::get('/games/{game}/edit', [AdminGameController::class, 'edit'])->name('games.edit');
    Route::put('/games/{game}', [AdminGameController::class, 'update'])->name('games.update');
    Route::delete('/games/{game}', [AdminGameController::class, 'destroy'])->name('games.destroy');
    Route::post('/games/{game}/update-image', [AdminGameController::class, 'updateImage'])
        ->name('games.update-image');
});
