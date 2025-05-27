<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class VirtualTryOnController extends Controller
{
    // Modelo único que sabemos que funciona
    private $fluxModel = [
        'version' => 'a02643ce418c0e12bad371c4adbfaec0dd1cb34b034ef37650ef205f92ad6199',
        'name' => 'Flux VTON'
    ];

    /**
     * Método principal para generar Virtual Try-On
     */
    public function generate(Request $request)
    {
        // Aumentar tiempo límite para predicciones largas
        set_time_limit(600); // 10 minutos
        ini_set('max_execution_time', 600);
        
        Log::info('=== VIRTUAL TRY-ON REQUEST RECEIVED ===', [
            'method' => $request->method(),
            'url' => $request->fullUrl(),
            'ip' => $request->ip(),
            'has_garment' => $request->hasFile('garment_image'),
            'has_person' => $request->hasFile('person_image'),
            'has_token' => $request->filled('api_token'),
            'environment' => app()->environment()
        ]);

        $validator = Validator::make($request->all(), [
            'garment_image' => 'required|image|mimes:jpeg,png,jpg,webp|max:10240',
            'person_image' => 'required|image|mimes:jpeg,png,jpg,webp|max:10240',
            'api_token' => 'required|string|min:10',
            'garment_part' => 'required|in:upper_body,lower_body,dresses'
        ]);

        if ($validator->fails()) {
            Log::error('Validation failed', ['errors' => $validator->errors()]);
            return response()->json([
                'success' => false,
                'error' => 'Datos de entrada inválidos: ' . $validator->errors()->first()
            ], 422);
        }

        try {
            $garmentImage = $request->file('garment_image');
            $personImage = $request->file('person_image');
            $apiToken = $request->input('api_token');
            $garmentPart = $request->input('garment_part', 'upper_body');

            // Validar token
            if (!$this->isValidReplicateToken($apiToken)) {
                return response()->json([
                    'success' => false,
                    'error' => 'El token de Replicate debe comenzar con "r8_" y tener al menos 10 caracteres.'
                ]);
            }

            Log::info('Processing with Flux VTON', [
                'garment_part' => $garmentPart,
                'garment_size' => $garmentImage->getSize(),
                'person_size' => $personImage->getSize(),
                'model' => $this->fluxModel['name']
            ]);

            // Optimizar imágenes antes de enviar
            $garmentData = $this->optimizeImage($garmentImage);
            $personData = $this->optimizeImage($personImage);

            $garmentUrl = 'data:image/jpeg;base64,' . base64_encode($garmentData);
            $personUrl = 'data:image/jpeg;base64,' . base64_encode($personData);

            // Preparar datos para Replicate
            $input = [
                'part' => $garmentPart,
                'image' => $personUrl,
                'garment' => $garmentUrl
            ];

            $postData = json_encode([
                'version' => $this->fluxModel['version'],
                'input' => $input
            ]);

            Log::info('Sending request to Replicate API', [
                'garment_size_kb' => round(strlen($garmentUrl) / 1024, 2),
                'person_size_kb' => round(strlen($personUrl) / 1024, 2)
            ]);

            // Crear predicción usando cURL con timeouts optimizados
            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => 'https://api.replicate.com/v1/predictions',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => $postData,
                CURLOPT_HTTPHEADER => [
                    'Authorization: Token ' . $apiToken,
                    'Content-Type: application/json',
                    'User-Agent: Laravel-VirtualTryOn/1.0'
                ],
                CURLOPT_TIMEOUT => 60, // Aumentado a 60 segundos
                CURLOPT_CONNECTTIMEOUT => 30, // Aumentado a 30 segundos
                CURLOPT_SSL_VERIFYPEER => false
            ]);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
            curl_close($ch);

            if ($response === false || !empty($curlError)) {
                throw new \Exception('Error cURL al crear predicción: ' . $curlError);
            }

            if ($httpCode !== 201) {
                $errorData = json_decode($response, true);
                $errorMessage = $this->getErrorMessage($httpCode, $errorData);
                return response()->json([
                    'success' => false,
                    'error' => $errorMessage
                ]);
            }

            $prediction = json_decode($response, true);
            
            if (!$prediction || !isset($prediction['id'])) {
                throw new \Exception('Respuesta inválida de Replicate API');
            }

            Log::info('Prediction created successfully', [
                'id' => $prediction['id'],
                'status' => $prediction['status'] ?? 'unknown'
            ]);

            // Esperar resultado con timeouts optimizados
            $result = $this->waitForPredictionOptimized($prediction['id'], $apiToken);

            if ($result['status'] === 'succeeded' && isset($result['output'])) {
                // Descargar imagen resultado
                $imageData = $this->downloadImageOptimized($result['output']);
                
                if (!$imageData) {
                    throw new \Exception('Error al descargar imagen resultado');
                }

                $imageBase64 = 'data:image/png;base64,' . base64_encode($imageData);

                Log::info('Virtual Try-On completed successfully', [
                    'prediction_id' => $prediction['id'],
                    'result_size_kb' => round(strlen($imageBase64) / 1024, 2)
                ]);

                return response()->json([
                    'success' => true,
                    'imageBase64' => $imageBase64,
                    'modelUsed' => $this->fluxModel['name'],
                    'predictionId' => $prediction['id'],
                    'processingTime' => $result['processing_time'] ?? null
                ]);
            } else {
                $errorMsg = $result['error'] ?? 'Error desconocido';
                Log::error('Prediction failed', [
                    'prediction_id' => $prediction['id'],
                    'status' => $result['status'],
                    'error' => $errorMsg
                ]);
                
                throw new \Exception('La predicción falló: ' . $errorMsg);
            }

        } catch (\Exception $e) {
            Log::error('Error in virtual try-on', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_ip' => $request->ip()
            ]);
            
            return response()->json([
                'success' => false,
                'error' => 'Error al procesar las imágenes: ' . $e->getMessage(),
                'suggestion' => $this->getSuggestionForError($e->getMessage())
            ], 500);
        }
    }

    /**
     * Verificar que el controlador funciona
     */
    public function checkTokens()
    {
        Log::info('CheckTokens method called');
        
        return response()->json([
            'success' => true,
            'message' => 'API funcionando correctamente',
            'controller' => 'VirtualTryOnController',
            'model' => $this->fluxModel['name'],
            'laravel_version' => app()->version(),
            'php_version' => PHP_VERSION,
            'environment' => app()->environment(),
            'timestamp' => now()->toISOString()
        ]);
    }

    /**
     * Método de prueba simple
     */
    public function testGenerate(Request $request)
    {
        Log::info('TestGenerate method called', [
            'has_garment' => $request->hasFile('garment_image'),
            'has_person' => $request->hasFile('person_image'),
            'has_token' => $request->filled('api_token')
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Test endpoint funcionando correctamente',
            'controller' => 'VirtualTryOnController@testGenerate',
            'model' => $this->fluxModel['name'],
            'received_data' => [
                'has_garment' => $request->hasFile('garment_image'),
                'has_person' => $request->hasFile('person_image'),
                'has_token' => $request->filled('api_token'),
                'garment_part' => $request->input('garment_part')
            ],
            'timestamp' => now()->toISOString()
        ]);
    }

    // Métodos auxiliares
    private function isValidReplicateToken($token)
    {
        return strlen($token) >= 10 && strpos($token, 'r8_') === 0;
    }

    private function getErrorMessage($httpCode, $errorData)
    {
        switch ($httpCode) {
            case 401:
                return 'Token de API inválido. Verifica tu token de Replicate.';
            case 402:
                return 'Créditos insuficientes en tu cuenta de Replicate.';
            case 422:
                $detail = $errorData['detail'] ?? 'Error en los parámetros';
                return 'Error en los datos enviados: ' . $detail;
            case 429:
                return 'Demasiadas solicitudes. Espera un momento e inténtalo de nuevo.';
            case 503:
                return 'El servicio no está disponible temporalmente.';
            default:
                return "Error del servidor: HTTP {$httpCode}";
        }
    }

    private function waitForPrediction($predictionId, $apiToken)
    {
        $attempts = 0;
        $maxAttempts = 60;
        
        while ($attempts < $maxAttempts) {
            sleep(3);
            $attempts++;

            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => "https://api.replicate.com/v1/predictions/{$predictionId}",
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER => [
                    'Authorization: Token ' . $apiToken,
                    'User-Agent: Laravel-VirtualTryOn/1.0'
                ],
                CURLOPT_TIMEOUT => 15,
                CURLOPT_CONNECTTIMEOUT => 5,
                CURLOPT_SSL_VERIFYPEER => false
            ]);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
            curl_close($ch);

            if ($response === false || !empty($curlError)) {
                Log::warning("Error checking prediction status (attempt {$attempts}): {$curlError}");
                continue;
            }

            if ($httpCode === 200) {
                $result = json_decode($response, true);
                Log::info("Prediction status (attempt {$attempts}): " . $result['status']);
                
                if (in_array($result['status'], ['succeeded', 'failed', 'canceled'])) {
                    return $result;
                }
            }
        }

        return [
            'status' => 'timeout',
            'error' => 'La predicción tardó demasiado tiempo'
        ];
    }

    private function downloadImage($imageUrl)
    {
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $imageUrl,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 60,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 3
        ]);

        $imageData = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($imageData === false || !empty($curlError)) {
            Log::error('Error downloading image: ' . $curlError);
            return false;
        }

        if ($httpCode !== 200) {
            Log::error('Error downloading image: HTTP ' . $httpCode);
            return false;
        }

        return $imageData;
    }

    private function optimizeImage($imageFile)
    {
        $imageData = file_get_contents($imageFile->getPathname());
        
        // Si la imagen es muy grande, redimensionarla
        $imageInfo = getimagesizefromstring($imageData);
        if ($imageInfo && ($imageInfo[0] > 1024 || $imageInfo[1] > 1024)) {
            Log::info('Optimizing large image', [
                'original_width' => $imageInfo[0],
                'original_height' => $imageInfo[1]
            ]);
            
            // Crear imagen desde string
            $image = imagecreatefromstring($imageData);
            if ($image) {
                // Calcular nuevas dimensiones manteniendo proporción
                $maxSize = 1024;
                $width = $imageInfo[0];
                $height = $imageInfo[1];
                
                if ($width > $height) {
                    $newWidth = $maxSize;
                    $newHeight = intval($height * ($maxSize / $width));
                } else {
                    $newHeight = $maxSize;
                    $newWidth = intval($width * ($maxSize / $height));
                }
                
                // Crear nueva imagen redimensionada
                $resizedImage = imagecreatetruecolor($newWidth, $newHeight);
                imagecopyresampled($resizedImage, $image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
                
                // Convertir a JPEG con calidad 85
                ob_start();
                imagejpeg($resizedImage, null, 85);
                $optimizedData = ob_get_contents();
                ob_end_clean();
                
                imagedestroy($image);
                imagedestroy($resizedImage);
                
                Log::info('Image optimized', [
                    'new_width' => $newWidth,
                    'new_height' => $newHeight,
                    'size_reduction' => round((strlen($imageData) - strlen($optimizedData)) / 1024, 2) . ' KB'
                ]);
                
                return $optimizedData;
            }
        }
        
        return $imageData;
    }

    private function waitForPredictionOptimized($predictionId, $apiToken)
    {
        $attempts = 0;
        $maxAttempts = 120; // 6 minutos máximo (120 * 3 segundos)
        $startTime = time();
        
        Log::info('Starting prediction wait', [
            'prediction_id' => $predictionId,
            'max_attempts' => $maxAttempts
        ]);
        
        while ($attempts < $maxAttempts) {
            $attempts++;
            $currentTime = time();
            $elapsedTime = $currentTime - $startTime;
            
            // Delay progresivo: empezar con 2 segundos, aumentar gradualmente
            if ($attempts <= 10) {
                $delay = 2; // Primeros 10 intentos: 2 segundos
            } elseif ($attempts <= 30) {
                $delay = 3; // Siguientes 20 intentos: 3 segundos
            } else {
                $delay = 5; // Resto: 5 segundos
            }
            
            sleep($delay);

            try {
                $ch = curl_init();
                curl_setopt_array($ch, [
                    CURLOPT_URL => "https://api.replicate.com/v1/predictions/{$predictionId}",
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_HTTPHEADER => [
                        'Authorization: Token ' . $apiToken,
                        'User-Agent: Laravel-VirtualTryOn/1.0'
                    ],
                    CURLOPT_TIMEOUT => 20, // Timeout más corto para checks
                    CURLOPT_CONNECTTIMEOUT => 10,
                    CURLOPT_SSL_VERIFYPEER => false
                ]);

                $response = curl_exec($ch);
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                $curlError = curl_error($ch);
                curl_close($ch);

                if ($response === false || !empty($curlError)) {
                    Log::warning("Error checking prediction status (attempt {$attempts}): {$curlError}");
                    continue;
                }

                if ($httpCode === 200) {
                    $result = json_decode($response, true);
                    $status = $result['status'] ?? 'unknown';
                    
                    Log::info("Prediction status check", [
                        'attempt' => $attempts,
                        'elapsed_time' => $elapsedTime,
                        'status' => $status,
                        'progress' => $result['progress'] ?? null
                    ]);
                    
                    if (in_array($status, ['succeeded', 'failed', 'canceled'])) {
                        $result['processing_time'] = $elapsedTime;
                        return $result;
                    }
                    
                    // Si está en processing, continuar esperando
                    if ($status === 'processing') {
                        continue;
                    }
                } else {
                    Log::warning("HTTP error checking prediction (attempt {$attempts}): {$httpCode}");
                }

            } catch (\Exception $e) {
                Log::warning("Exception checking prediction (attempt {$attempts}): " . $e->getMessage());
                continue;
            }
        }

        Log::error("Prediction timeout", [
            'prediction_id' => $predictionId,
            'total_attempts' => $attempts,
            'total_time' => time() - $startTime
        ]);

        return [
            'status' => 'timeout',
            'error' => 'La predicción tardó demasiado tiempo. El modelo puede estar muy ocupado.',
            'processing_time' => time() - $startTime
        ];
    }

    private function downloadImageOptimized($imageUrl)
    {
        Log::info('Downloading result image', ['url' => $imageUrl]);
        
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $imageUrl,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 120, // 2 minutos para descarga
            CURLOPT_CONNECTTIMEOUT => 30,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 5,
            CURLOPT_USERAGENT => 'Laravel-VirtualTryOn/1.0'
        ]);

        $imageData = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        $downloadSize = curl_getinfo($ch, CURLINFO_SIZE_DOWNLOAD);
        curl_close($ch);

        if ($imageData === false || !empty($curlError)) {
            Log::error('Error downloading image', ['error' => $curlError]);
            return false;
        }

        if ($httpCode !== 200) {
            Log::error('HTTP error downloading image', ['code' => $httpCode]);
            return false;
        }

        Log::info('Image downloaded successfully', [
            'size_bytes' => $downloadSize,
            'size_kb' => round($downloadSize / 1024, 2)
        ]);

        return $imageData;
    }

    private function getSuggestionForError($errorMessage)
    {
        if (strpos($errorMessage, 'timeout') !== false || strpos($errorMessage, 'tardó demasiado') !== false) {
            return 'El modelo está muy ocupado. Intenta de nuevo en 2-3 minutos o usa imágenes más pequeñas.';
        }
        
        if (strpos($errorMessage, 'cURL') !== false) {
            return 'Problema de conectividad. Verifica tu conexión a internet.';
        }
        
        if (strpos($errorMessage, 'Token') !== false) {
            return 'Verifica que tu token de Replicate sea válido y tenga créditos suficientes.';
        }
        
        return 'Verifica tu token de API, conexión a internet, y que las imágenes sean válidas.';
    }

    // Métodos de compatibilidad
    public function checkModels(Request $request)
    {
        return response()->json([
            'success' => true,
            'message' => 'Usando modelo único: ' . $this->fluxModel['name'],
            'model' => $this->fluxModel
        ]);
    }

    public function getAccountInfo() { return response()->json(['success' => false, 'message' => 'En desarrollo']); }
    public function checkPrediction() { return response()->json(['success' => false, 'message' => 'En desarrollo']); }
    public function cancelPrediction() { return response()->json(['success' => false, 'message' => 'En desarrollo']); }
}
