<?php

use Assetplan\Dispatcher\Http\Controllers\DispatchBatchController;
use Assetplan\Dispatcher\Http\Controllers\DispatchJobController;
use Illuminate\Support\Facades\Route;

Route::prefix('dispatcher')->middleware(['dispatcher-middleware'])->group(function () {
    Route::post('dispatch/batch', DispatchBatchController::class)->name('dispatcher.dispatch.batch');
    Route::post('dispatch', DispatchJobController::class)->name('dispatcher.dispatch');
});
