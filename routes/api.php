<?php

use App\Http\Controllers\FileController;
use App\Http\Controllers\FolderController;
use Illuminate\Support\Facades\Route;

require __DIR__.'/auth.php';

Route::middleware('auth:sanctum')->group(function () {
    Route::ApiResource('files', FileController::class);
    Route::get('files/download/{file}',[ FileController::class, 'download']);
    Route::post('folders', [FolderController::class, 'store']);
    Route::get('folders/{folder}/contents', [FolderController::class, 'index']);
    Route::get('folders/{folder}', [FolderController::class, 'show']);
    Route::delete('folders/{folder}', [FolderController::class, 'destroy']);

});
