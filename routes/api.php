<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// Ruta de prueba básica
Route::get('test', function () {
    return response()->json([
        'success' => true,
        'message' => 'API funcionando correctamente con Laravel',
        'timestamp' => now(),
        'laravel_version' => app()->version(),
        'environment' => app()->environment()
    ]);
});

// Virtual Try-On routes - ESTAS SON LAS IMPORTANTES
Route::post('virtual-try-on', 'Api\VirtualTryOnController@generate');
Route::post('check-models', 'Api\VirtualTryOnController@checkModels');
Route::get('check-tokens', 'Api\VirtualTryOnController@checkTokens');

// Rutas adicionales
Route::get('account-info', 'Api\VirtualTryOnController@getAccountInfo');
Route::get('prediction/{predictionId}', 'Api\VirtualTryOnController@checkPrediction');
Route::post('prediction/{predictionId}/cancel', 'Api\VirtualTryOnController@cancelPrediction');

// Ruta de prueba específica para virtual try-on
Route::post('test-virtual-try-on', 'Api\VirtualTryOnController@testGenerate');

// Diagnóstico completo
Route::get('debug', function () {
    return response()->json([
        'success' => true,
        'message' => 'Diagnóstico completo',
        'environment' => [
            'app_env' => env('APP_ENV'),
            'app_debug' => env('APP_DEBUG'),
            'app_url' => env('APP_URL'),
        ],
        'server' => [
            'php_version' => PHP_VERSION,
            'laravel_version' => app()->version(),
            'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
        ],
        'routes' => [
            'api_prefix' => 'api/',
            'virtual_try_on_route' => url('api/virtual-try-on'),
            'test_route' => url('api/test'),
        ],
        'tokens' => [
            'replicate_configured' => !empty(env('REPLICATE_API_TOKEN')),
            'huggingface_configured' => !empty(env('HUGGINGFACE_API_TOKEN')),
        ]
    ]);
});

