<?php

use App\Http\Controllers\FileController;

require __DIR__.'/auth.php';

Route::resource('files', FileController::class);
