<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class VirtualTryOnControllerSimple extends Controller
{
    public function generate(Request $request)
    {
        set_time_limit(300);
        
        Log::info('Virtual Try-On request received (Simple)', [
            'ip' => $request->ip(),
            'has_garment' => $request->hasFile('garment_image'),
            'has_person' => $request->hasFile('person_image'),
            'has_token' => $request->filled('api_token')
        ]);

        $validator = Validator::make($request->all(), [
            'garment_image' => 'required|image|mimes:jpeg,png,jpg,webp|max:10240',
            'person_image' => 'required|image|mimes:jpeg,png,jpg,webp|max:10240',
            'api_token' => 'required|string|min:10',
            'garment_part' => 'required|in:upper_body,lower_body,dresses'
        ]);

        if ($validator->fails()) {
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

            Log::info('Processing with simple implementation', [
                'garment_part' => $garmentPart,
                'garment_size' => $garmentImage->getSize(),
                'person_size' => $personImage->getSize()
            ]);

            // Convertir imágenes a base64
            $garmentBase64 = base64_encode(file_get_contents($garmentImage->getPathname()));
            $personBase64 = base64_encode(file_get_contents($personImage->getPathname()));

            $garmentUrl = 'data:image/jpeg;base64,' . $garmentBase64;
            $personUrl = 'data:image/jpeg;base64,' . $personBase64;

            // Preparar datos para Replicate
            $input = [
                'part' => $garmentPart,
                'image' => $personUrl,
                'garment' => $garmentUrl
            ];

            $postData = json_encode([
                'version' => 'a02643ce418c0e12bad371c4adbfaec0dd1cb34b034ef37650ef205f92ad6199',
                'input' => $input
            ]);

            // Configurar contexto para la solicitud HTTP
            $context = stream_context_create([
                'http' => [
                    'method' => 'POST',
                    'header' => [
                        'Authorization: Token ' . $apiToken,
                        'Content-Type: application/json',
                        'Content-Length: ' . strlen($postData)
                    ],
                    'content' => $postData,
                    'timeout' => 30
                ]
            ]);

            Log::info('Sending request to Replicate API');

            // Crear predicción
            $response = file_get_contents('https://api.replicate.com/v1/predictions', false, $context);
            
            if ($response === false) {
                throw new \Exception('Error al conectar con Replicate API');
            }

            $prediction = json_decode($response, true);
            
            if (!$prediction || !isset($prediction['id'])) {
                throw new \Exception('Respuesta inválida de Replicate API');
            }

            Log::info('Prediction created', ['id' => $prediction['id']]);

            // Esperar resultado
            $result = $this->waitForPrediction($prediction['id'], $apiToken);

            if ($result['status'] === 'succeeded' && isset($result['output'])) {
                // Descargar imagen resultado
                $imageData = file_get_contents($result['output']);
                if ($imageData === false) {
                    throw new \Exception('Error al descargar imagen resultado');
                }

                $imageBase64 = 'data:image/png;base64,' . base64_encode($imageData);

                Log::info('Virtual Try-On completed successfully');

                return response()->json([
                    'success' => true,
                    'imageBase64' => $imageBase64,
                    'modelUsed' => 'Flux VTON (Simple)',
                    'predictionId' => $prediction['id']
                ]);
            } else {
                throw new \Exception('La predicción falló: ' . ($result['error'] ?? 'Error desconocido'));
            }

        } catch (\Exception $e) {
            Log::error('Error in simple virtual try-on', [
                'message' => $e->getMessage(),
                'user_ip' => $request->ip()
            ]);
            
            return response()->json([
                'success' => false,
                'error' => 'Error al procesar las imágenes: ' . $e->getMessage(),
                'suggestion' => 'Verifica tu token de API y conexión a internet.'
            ], 500);
        }
    }

    private function isValidReplicateToken($token)
    {
        return strlen($token) >= 10 && strpos($token, 'r8_') === 0;
    }

    private function waitForPrediction($predictionId, $apiToken)
    {
        $attempts = 0;
        $maxAttempts = 40;
        
        while ($attempts < $maxAttempts) {
            sleep(3);
            $attempts++;

            $context = stream_context_create([
                'http' => [
                    'method' => 'GET',
                    'header' => 'Authorization: Token ' . $apiToken,
                    'timeout' => 15
                ]
            ]);

            $response = file_get_contents("https://api.replicate.com/v1/predictions/{$predictionId}", false, $context);
            
            if ($response === false) {
                Log::warning("Error checking prediction status (attempt {$attempts})");
                continue;
            }

            $result = json_decode($response, true);
            
            Log::info("Prediction status (attempt {$attempts}): " . $result['status']);

            if (in_array($result['status'], ['succeeded', 'failed', 'canceled'])) {
                return $result;
            }
        }

        return [
            'status' => 'timeout',
            'error' => 'La predicción tardó demasiado tiempo'
        ];
    }

    public function checkTokens()
    {
        return response()->json([
            'success' => true,
            'message' => 'Simple controller working',
            'laravel_version' => app()->version()
        ]);
    }
}
