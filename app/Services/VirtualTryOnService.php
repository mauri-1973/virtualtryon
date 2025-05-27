<?php

namespace App\Services;

use GuzzleHttp\Client;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use GuzzleHttp\Exception\RequestException;

class VirtualTryOnService
{
    protected $httpClient;
    
    const FLUX_VTON_MODEL = [
        'version' => 'a02643ce418c0e12bad371c4adbfaec0dd1cb34b034ef37650ef205f92ad6199',
        'name' => 'Flux Virtual Try-On',
        'id' => 'subhash25rawat/flux-vton'
    ];

    const IDM_VTON_MODEL = [
        'version' => '0513734a452173b8173e907e3a59d19a36266e55b48528559432bd21c7d7e985',
        'name' => 'IDM-VTON',
        'id' => 'cuuupid/idm-vton'
    ];

    public function __construct()
    {
        $this->httpClient = new Client([
            'timeout' => 30,
            'connect_timeout' => 10,
            'headers' => [
                'User-Agent' => 'laravel-virtual-tryon/1.0'
            ]
        ]);
    }

    public function generateWithReplicate(UploadedFile $garmentImage, UploadedFile $personImage, string $apiToken, string $garmentPart = 'upper_body')
    {
        set_time_limit(300);

        try {
            if (!$this->isValidReplicateToken($apiToken)) {
                return [
                    'success' => false,
                    'error' => 'El token de Replicate debe comenzar con "r8_" y tener al menos 10 caracteres.'
                ];
            }

            Log::info('Iniciando generación de Virtual Try-On con múltiples modelos');

            // Intentar primero con Flux VTON
            $result = $this->tryWithFluxVTON($garmentImage, $personImage, $apiToken, $garmentPart);
            
            if ($result['success']) {
                return $result;
            }

            // Si Flux VTON falla, intentar con IDM-VTON
            Log::info('Flux VTON falló, intentando con IDM-VTON', ['flux_error' => $result['error']]);
            
            $idmResult = $this->tryWithIDMVTON($garmentImage, $personImage, $apiToken, $garmentPart);
            
            if ($idmResult['success']) {
                return $idmResult;
            }

            // Si ambos fallan, devolver el error más informativo
            return [
                'success' => false,
                'error' => 'Ambos modelos no están disponibles en este momento.',
                'details' => [
                    'flux_error' => $result['error'],
                    'idm_error' => $idmResult['error']
                ],
                'suggestion' => 'Los modelos pueden estar ocupados. Inténtalo de nuevo en unos minutos.'
            ];

        } catch (\Exception $e) {
            Log::error('Error general en generateWithReplicate: ' . $e->getMessage());
            
            return [
                'success' => false,
                'error' => 'Ocurrió un error al procesar las imágenes.'
            ];
        }
    }

    protected function tryWithFluxVTON(UploadedFile $garmentImage, UploadedFile $personImage, string $apiToken, string $garmentPart)
    {
        try {
            Log::info('Intentando con Flux VTON', ['model' => self::FLUX_VTON_MODEL['name']]);

            $garmentBase64 = base64_encode(file_get_contents($garmentImage->getPathname()));
            $personBase64 = base64_encode(file_get_contents($personImage->getPathname()));

            $garmentUrl = 'data:image/jpeg;base64,' . $garmentBase64;
            $personUrl = 'data:image/jpeg;base64,' . $personBase64;

            $input = [
                'part' => $garmentPart,
                'image' => $personUrl,
                'garment' => $garmentUrl
            ];

            $response = $this->httpClient->post('https://api.replicate.com/v1/predictions', [
                'headers' => [
                    'Authorization' => 'Token ' . $apiToken,
                    'Content-Type' => 'application/json'
                ],
                'json' => [
                    'version' => self::FLUX_VTON_MODEL['version'],
                    'input' => $input
                ]
            ]);

            if ($response->getStatusCode() !== 201) {
                return $this->handleReplicateError($response, 'Flux VTON');
            }

            $prediction = json_decode($response->getBody(), true);
            Log::info('Predicción Flux VTON creada', ['id' => $prediction['id']]);

            $result = $this->waitForPredictionOptimized($prediction['id'], $apiToken);

            if ($result['status'] === 'succeeded') {
                return $this->processSuccessfulResult($result, self::FLUX_VTON_MODEL['name']);
            } else {
                return [
                    'success' => false,
                    'error' => 'Flux VTON falló: ' . ($result['error'] ?? 'Error desconocido'),
                    'model_used' => 'flux_vton'
                ];
            }

        } catch (\Exception $e) {
            Log::error('Error en Flux VTON: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Error en Flux VTON: ' . $e->getMessage(),
                'model_used' => 'flux_vton'
            ];
        }
    }

    protected function tryWithIDMVTON(UploadedFile $garmentImage, UploadedFile $personImage, string $apiToken, string $garmentPart)
    {
        try {
            Log::info('Intentando con IDM-VTON', ['model' => self::IDM_VTON_MODEL['name']]);

            // Subir imágenes a un servicio temporal o convertir a URLs públicas
            $garmentUrl = $this->uploadImageToTempService($garmentImage);
            $personUrl = $this->uploadImageToTempService($personImage);

            // Generar descripción de la prenda basada en el tipo
            $garmentDescription = $this->generateGarmentDescription($garmentPart);

            $input = [
                'garm_img' => $garmentUrl,
                'human_img' => $personUrl,
                'garment_des' => $garmentDescription
            ];

            $response = $this->httpClient->post('https://api.replicate.com/v1/predictions', [
                'headers' => [
                    'Authorization' => 'Token ' . $apiToken,
                    'Content-Type' => 'application/json'
                ],
                'json' => [
                    'version' => self::IDM_VTON_MODEL['version'],
                    'input' => $input
                ]
            ]);

            if ($response->getStatusCode() !== 201) {
                return $this->handleReplicateError($response, 'IDM-VTON');
            }

            $prediction = json_decode($response->getBody(), true);
            Log::info('Predicción IDM-VTON creada', ['id' => $prediction['id']]);

            $result = $this->waitForPredictionOptimized($prediction['id'], $apiToken);

            if ($result['status'] === 'succeeded') {
                return $this->processSuccessfulResult($result, self::IDM_VTON_MODEL['name']);
            } else {
                return [
                    'success' => false,
                    'error' => 'IDM-VTON falló: ' . ($result['error'] ?? 'Error desconocido'),
                    'model_used' => 'idm_vton'
                ];
            }

        } catch (\Exception $e) {
            Log::error('Error en IDM-VTON: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Error en IDM-VTON: ' . $e->getMessage(),
                'model_used' => 'idm_vton'
            ];
        }
    }

    protected function uploadImageToTempService(UploadedFile $image)
    {
        // Para IDM-VTON necesitamos URLs públicas, no base64
        // Opción 1: Usar un servicio temporal como imgbb, imgur, etc.
        // Opción 2: Subir a tu propio storage público
        // Opción 3: Usar data URLs (menos confiable pero funciona)
        
        $base64 = base64_encode(file_get_contents($image->getPathname()));
        $mimeType = $image->getMimeType();
        
        return "data:{$mimeType};base64,{$base64}";
    }

    protected function generateGarmentDescription(string $garmentPart)
    {
        $descriptions = [
            'upper_body' => [
                'stylish shirt',
                'fashionable top',
                'trendy blouse',
                'modern t-shirt',
                'elegant sweater'
            ],
            'lower_body' => [
                'stylish pants',
                'fashionable jeans',
                'trendy trousers',
                'modern skirt',
                'elegant shorts'
            ],
            'dresses' => [
                'beautiful dress',
                'elegant gown',
                'stylish dress',
                'fashionable outfit',
                'trendy dress'
            ]
        ];

        $options = $descriptions[$garmentPart] ?? $descriptions['upper_body'];
        return $options[array_rand($options)];
    }

    protected function processSuccessfulResult($result, $modelName)
    {
        try {
            $imageUrl = $result['output'];
            
            // IDM-VTON devuelve directamente una URL, Flux VTON también
            $imageResponse = $this->httpClient->get($imageUrl, [
                'timeout' => 60
            ]);
            
            $imageData = $imageResponse->getBody()->getContents();
            $imageBase64 = 'data:image/png;base64,' . base64_encode($imageData);

            Log::info('Virtual Try-On completado exitosamente', ['model' => $modelName]);

            return [
                'success' => true,
                'imageBase64' => $imageBase64,
                'modelUsed' => $modelName,
                'predictionId' => $result['id']
            ];
        } catch (\Exception $e) {
            Log::error('Error procesando resultado exitoso: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Error al descargar la imagen resultado'
            ];
        }
    }

    protected function waitForPredictionOptimized(string $predictionId, string $apiToken)
    {
        $attempts = 0;
        $maxAttempts = 40;
        $baseDelay = 3;
        $maxDelay = 10;
        
        $startTime = time();
        $maxWaitTime = 240;
        
        while ($attempts < $maxAttempts) {
            if (time() - $startTime > $maxWaitTime) {
                Log::warning("Timeout total alcanzado para predicción {$predictionId}");
                return [
                    'status' => 'timeout',
                    'error' => 'Tiempo de espera agotado'
                ];
            }

            $delay = min($baseDelay + ($attempts * 0.5), $maxDelay);
            sleep($delay);
            $attempts++;

            try {
                $response = $this->httpClient->get("https://api.replicate.com/v1/predictions/{$predictionId}", [
                    'headers' => [
                        'Authorization' => 'Token ' . $apiToken
                    ],
                    'timeout' => 15
                ]);

                $result = json_decode($response->getBody(), true);
                
                Log::info("Estado de la predicción (intento {$attempts}, delay {$delay}s): " . $result['status']);

                if (in_array($result['status'], ['succeeded', 'failed', 'canceled'])) {
                    return $result;
                }

                if ($result['status'] === 'processing' && $attempts < 10) {
                    continue;
                }

            } catch (RequestException $e) {
                Log::error("Error checking prediction status (intento {$attempts}): " . $e->getMessage());
                
                if ($e->getCode() === 28 && $attempts < $maxAttempts - 5) {
                    continue;
                }
                
                if ($attempts > 5) {
                    break;
                }
            }
        }

        Log::warning("Predicción {$predictionId} no completada después de {$attempts} intentos");
        return [
            'status' => 'timeout',
            'error' => 'La predicción tardó demasiado tiempo'
        ];
    }

    protected function isValidReplicateToken(string $token): bool
    {
        return strlen($token) >= 10 && strpos($token, 'r8_') === 0;
    }

    protected function handleReplicateError($response, $modelName = 'Unknown')
    {
        $statusCode = $response->getStatusCode();
        
        switch ($statusCode) {
            case 401:
                return [
                    'success' => false,
                    'error' => "Token de API inválido para {$modelName}"
                ];
            case 402:
                return [
                    'success' => false,
                    'error' => "Créditos insuficientes para {$modelName}"
                ];
            case 422:
                $errorBody = json_decode($response->getBody(), true);
                $errorDetail = $errorBody['detail'] ?? 'Error en los parámetros';
                return [
                    'success' => false,
                    'error' => "{$modelName} - Error en parámetros: {$errorDetail}"
                ];
            case 429:
                return [
                    'success' => false,
                    'error' => "{$modelName} - Demasiadas solicitudes",
                    'rate_limit' => true
                ];
            case 503:
                return [
                    'success' => false,
                    'error' => "{$modelName} - Servicio no disponible",
                    'service_unavailable' => true
                ];
            default:
                return [
                    'success' => false,
                    'error' => "{$modelName} - Error del servidor: {$statusCode}"
                ];
        }
    }

    /**
     * Método para verificar disponibilidad de modelos
     */
    public function checkModelAvailability(string $apiToken)
    {
        $models = [
            'flux_vton' => self::FLUX_VTON_MODEL,
            'idm_vton' => self::IDM_VTON_MODEL
        ];

        $availability = [];

        foreach ($models as $key => $model) {
            try {
                $response = $this->httpClient->get("https://api.replicate.com/v1/models/{$model['id']}", [
                    'headers' => [
                        'Authorization' => 'Token ' . $apiToken
                    ],
                    'timeout' => 10
                ]);

                $availability[$key] = [
                    'available' => $response->getStatusCode() === 200,
                    'name' => $model['name'],
                    'id' => $model['id']
                ];
            } catch (\Exception $e) {
                $availability[$key] = [
                    'available' => false,
                    'name' => $model['name'],
                    'id' => $model['id'],
                    'error' => $e->getMessage()
                ];
            }
        }

        return $availability;
    }

    /**
     * Método para obtener información de uso de la cuenta
     */
    public function getAccountInfo(string $apiToken)
    {
        try {
            $response = $this->httpClient->get('https://api.replicate.com/v1/account', [
                'headers' => [
                    'Authorization' => 'Token ' . $apiToken
                ],
                'timeout' => 10
            ]);

            return [
                'success' => true,
                'account' => json_decode($response->getBody(), true)
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => 'Error al obtener información de la cuenta'
            ];
        }
    }

    public function checkPredictionStatus(string $predictionId, string $apiToken)
    {
        try {
            $response = $this->httpClient->get("https://api.replicate.com/v1/predictions/{$predictionId}", [
                'headers' => [
                    'Authorization' => 'Token ' . $apiToken
                ],
                'timeout' => 10
            ]);

            return [
                'success' => true,
                'prediction' => json_decode($response->getBody(), true)
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => 'Error al verificar el estado de la predicción'
            ];
        }
    }

    public function cancelPrediction(string $predictionId, string $apiToken)
    {
        try {
            $response = $this->httpClient->post("https://api.replicate.com/v1/predictions/{$predictionId}/cancel", [
                'headers' => [
                    'Authorization' => 'Token ' . $apiToken
                ],
                'timeout' => 10
            ]);

            return [
                'success' => $response->getStatusCode() === 200,
                'message' => 'Predicción cancelada'
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => 'Error al cancelar la predicción'
            ];
        }
    }
}
