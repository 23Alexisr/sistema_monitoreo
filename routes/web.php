<?php

use App\Http\Controllers\FotoDescargaController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/fotos/{foto}/descargar', FotoDescargaController::class)
    ->middleware('auth')
    ->name('fotos.descargar');
