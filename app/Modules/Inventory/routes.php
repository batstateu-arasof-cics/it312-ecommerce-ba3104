<?php

use App\Modules\Inventory\Http\Controllers\InventoryController;
use Illuminate\Support\Facades\Route;

// Inventory team - Inventory & Stock

Route::middleware(['auth', 'admin'])->prefix('admin/inventory')->name('admin.inventory.')->group(function () {
    Route::get('/', [InventoryController::class, 'index'])->name('index');                     // stock levels
    Route::post('/{product}/restock', [InventoryController::class, 'restock'])->name('restock'); // restock

    // TODO (Inventory team, Stretch): per-product history and manual correction with a reason
    // Route::get('/{product}/history', [InventoryController::class, 'history'])->name('history');
    // Route::post('/{product}/adjust', [InventoryController::class, 'adjust'])->name('adjust');
});
