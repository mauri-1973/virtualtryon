<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HomeController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

Route::get('/', [HomeController::class, 'index'])->name('home');

// Ruta de prueba para verificar que Laravel funciona
Route::get('/test-laravel', function () {
    return response()->json([
        'success' => true,
        'message' => 'Laravel funcionando correctamente',
        'timestamp' => now(),
        'environment' => app()->environment(),
        'laravel_version' => app()->version()
    ]);
});

// Rutas básicas
Route::get('/women', function () {
    return view('home')->with('message', 'Sección de Mujeres en desarrollo');
});

Route::get('/men', function () {
    return view('home')->with('message', 'Sección de Hombres en desarrollo');
});

Route::get('/accessories', function () {
    return view('home')->with('message', 'Sección de Accesorios en desarrollo');
});

Route::get('/sale', function () {
    return view('home')->with('message', 'Sección de Ofertas en desarrollo');
});

Route::get('/cart', function () {
    return view('home')->with('message', 'Carrito en desarrollo');
})->name('cart.index');

Route::get('/wishlist', function () {
    return view('home')->with('message', 'Lista de deseos en desarrollo');
});

// Fallback para SPA (si usas Vue/React en el frontend)
Route::fallback([HomeController::class, 'index']);
