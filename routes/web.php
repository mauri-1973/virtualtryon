<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

Route::get('/', 'HomeController@index')->name('home');

// Ruta de prueba para verificar que Laravel funciona
Route::get('/test-laravel', function () {
    return response()->json([
        'success' => true,
        'message' => 'Laravel funcionando correctamente',
        'timestamp' => now(),
        'environment' => app()->environment()
    ]);
});

// Fallback para SPA (si usas Vue/React en el frontend)
Route::get('/{any}', 'HomeController@index')->where('any', '.*');