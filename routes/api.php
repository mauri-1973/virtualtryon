<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\VirtualTryOnController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// Ruta de prueba básica
Route::get('test', function () {
    return response()->json([
        'success' => true,
        'message' => 'API funcionando correctamente con Laravel 9',
        'timestamp' => now(),
        'laravel_version' => app()->version(),
        'environment' => app()->environment()
    ]);
});

// Virtual Try-On routes - MÉTODOS CORRECTOS
Route::post('virtual-try-on', [VirtualTryOnController::class, 'generate']);
Route::post('check-models', [VirtualTryOnController::class, 'checkModels']);
Route::get('check-tokens', [VirtualTryOnController::class, 'checkTokens']);

// Rutas adicionales
Route::get('account-info', [VirtualTryOnController::class, 'getAccountInfo']);
Route::get('prediction/{predictionId}', [VirtualTryOnController::class, 'checkPrediction']);
Route::post('prediction/{predictionId}/cancel', [VirtualTryOnController::class, 'cancelPrediction']);

// Ruta de prueba específica para virtual try-on
Route::post('test-virtual-try-on', [VirtualTryOnController::class, 'testGenerate']);

// Diagnóstico completo
Route::get('debug', function () {
    return response()->json([
        'success' => true,
        'message' => 'Diagnóstico completo - Laravel 9',
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

// Diagnóstico de conectividad
Route::get('test-connectivity', function () {
    $results = [];
    
    // Test 1: Verificar configuración PHP
    $results['php_config'] = [
        'allow_url_fopen' => ini_get('allow_url_fopen') ? 'Habilitado' : 'Deshabilitado',
        'curl_available' => function_exists('curl_init') ? 'Disponible' : 'No disponible',
        'openssl_available' => extension_loaded('openssl') ? 'Disponible' : 'No disponible',
        'user_agent_restriction' => ini_get('user_agent') ?: 'No configurado'
    ];
    
    // Test 2: Probar conectividad básica
    try {
        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'timeout' => 10,
                'header' => 'User-Agent: Laravel-Test/9.0'
            ]
        ]);
        
        $response = @file_get_contents('https://httpbin.org/get', false, $context);
        $results['basic_connectivity'] = $response ? 'OK' : 'FAIL';
    } catch (Exception $e) {
        $results['basic_connectivity'] = 'ERROR: ' . $e->getMessage();
    }
    
    // Test 3: Probar cURL si está disponible
    if (function_exists('curl_init')) {
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => 'https://httpbin.org/get',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_USERAGENT => 'Laravel-cURL-Test/9.0'
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);
        
        $results['curl_test'] = [
            'response' => $response ? 'OK' : 'FAIL',
            'http_code' => $httpCode,
            'error' => $curlError ?: 'Ninguno'
        ];
    } else {
        $results['curl_test'] = 'cURL no disponible';
    }
    
    // Test 4: Probar conexión específica a Replicate
    if (function_exists('curl_init')) {
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => 'https://api.replicate.com/v1/models',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 15,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_USERAGENT => 'Laravel-Replicate-Test/9.0'
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);
        
        $results['replicate_connectivity'] = [
            'response' => $response ? 'OK' : 'FAIL',
            'http_code' => $httpCode,
            'error' => $curlError ?: 'Ninguno'
        ];
    }
    
    return response()->json([
        'success' => true,
        'message' => 'Diagnóstico de conectividad completo - Laravel 9',
        'results' => $results,
        'timestamp' => now()
    ]);
});



