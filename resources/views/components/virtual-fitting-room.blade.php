<div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
    <!-- Upload Section -->
    <div class="card p-6">
        <div class="mb-6">
            <div class="flex items-center mb-4">
                <i data-lucide="sparkles" class="h-6 w-6 mr-2 text-blue-500"></i>
                <h2 class="text-xl font-semibold">Virtual Try-On con IA</h2>
            </div>
        </div>

        <!-- Token Input -->
        <div class="mb-6 space-y-4">
            <div>
                <label class="block text-sm font-medium mb-2">
                    <div class="flex items-center">
                        <i data-lucide="key" class="h-4 w-4 mr-2"></i>
                        Token de API de Replicate
                    </div>
                </label>
                <div class="relative">
                    <input type="password" id="replicateToken" 
                           placeholder="r8_xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx"
                           class="input-field pr-10">
                    <button type="button" onclick="window.virtualTryOn.toggleTokenVisibility()" 
                            class="absolute inset-y-0 right-0 pr-3 flex items-center">
                        <i data-lucide="eye-off" id="tokenToggleIcon" class="h-4 w-4 text-gray-500"></i>
                    </button>
                </div>
                <div id="tokenStatus" class="mt-2 text-xs"></div>
                <p class="text-xs text-gray-500 mt-1">
                    Obtén tu token en 
                    <a href="https://replicate.com/account/api-tokens" target="_blank" class="text-blue-500 hover:underline">
                        replicate.com/account/api-tokens
                    </a>
                </p>
            </div>

            <!-- Garment Type -->
            <div>
                <label class="block text-sm font-medium mb-2">Tipo de prenda</label>
                <select id="garmentPart" class="input-field">
                    <option value="upper_body">Parte superior (camisetas, blusas, chaquetas)</option>
                    <option value="lower_body">Parte inferior (pantalones, faldas)</option>
                    <option value="dresses">Vestidos</option>
                </select>
                <p class="text-xs text-gray-500 mt-1">
                    Selecciona el tipo correcto según la prenda que quieres probar
                </p>
            </div>
        </div>

        <!-- Image Upload Tabs -->
        <div class="mb-6">
            <div class="flex border-b">
                <button onclick="window.virtualTryOn.switchTab('garment')" id="garmentTab" 
                        class="tab-button active">
                    1. Prenda
                </button>
                <button onclick="window.virtualTryOn.switchTab('person')" id="personTab" 
                        class="tab-button inactive">
                    2. Persona
                </button>
            </div>

            <!-- Garment Upload -->
            <div id="garmentUpload" class="mt-4">
                <h3 class="text-lg font-semibold mb-2">Sube la imagen de la prenda</h3>
                <p class="text-sm text-gray-500 mb-4">
                    Sube una imagen clara de la prenda sobre fondo blanco o transparente para mejores resultados.
                </p>
                <div class="upload-area">
                    <input type="file" id="garmentImageInput" accept="image/*" class="hidden">
                    <div id="garmentPreview" class="hidden">
                        <img id="garmentImg" class="max-w-full h-auto mx-auto rounded-lg" alt="Prenda seleccionada">
                        <button onclick="window.virtualTryOn.removeGarmentImage()" class="mt-2 text-red-500 text-sm">Eliminar</button>
                    </div>
                    <div id="garmentUploadArea">
                        <i data-lucide="image" class="h-12 w-12 text-gray-400 mx-auto mb-4"></i>
                        <p class="text-sm font-medium mb-1">Arrastra y suelta tu imagen aquí</p>
                        <p class="text-xs text-gray-500 mb-4">PNG, JPG o WEBP (máx. 10MB)</p>
                        <button onclick="document.getElementById('garmentImageInput').click()" 
                                class="btn-secondary">
                            <i data-lucide="upload" class="h-4 w-4 mr-2"></i>
                            Seleccionar imagen
                        </button>
                    </div>
                </div>
            </div>

            <!-- Person Upload -->
            <div id="personUpload" class="mt-4 hidden">
                <h3 class="text-lg font-semibold mb-2">Sube la imagen de la persona</h3>
                <p class="text-sm text-gray-500 mb-4">
                    Sube una imagen de la persona en posición frontal con el cuerpo completo visible.
                </p>
                <div class="upload-area">
                    <input type="file" id="personImageInput" accept="image/*" class="hidden">
                    <div id="personPreview" class="hidden">
                        <img id="personImg" class="max-w-full h-auto mx-auto rounded-lg" alt="Persona seleccionada">
                        <button onclick="window.virtualTryOn.removePersonImage()" class="mt-2 text-red-500 text-sm">Eliminar</button>
                    </div>
                    <div id="personUploadArea">
                        <i data-lucide="image" class="h-12 w-12 text-gray-400 mx-auto mb-4"></i>
                        <p class="text-sm font-medium mb-1">Arrastra y suelta tu imagen aquí</p>
                        <p class="text-xs text-gray-500 mb-4">PNG, JPG o WEBP (máx. 10MB)</p>
                        <button onclick="document.getElementById('personImageInput').click()" 
                                class="btn-secondary">
                            <i data-lucide="upload" class="h-4 w-4 mr-2"></i>
                            Seleccionar imagen
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Generate Button -->
        <button onclick="window.virtualTryOn.generateTryOn()" id="generateBtn" 
                class="w-full btn-primary">
            <i data-lucide="sparkles" class="mr-2 h-4 w-4"></i>
            Generar Virtual Try-On
        </button>
    </div>

    <!-- Result Section -->
    <div class="card p-6">
        <h2 class="text-xl font-semibold mb-4">Resultado</h2>
        
        <div id="errorAlert" class="hidden alert-error">
            <div class="flex items-center">
                <i data-lucide="alert-triangle" class="h-4 w-4 text-red-500 mr-2"></i>
                <div class="font-medium text-red-800">Error:</div>
            </div>
            <div id="errorMessage" class="mt-1 text-sm"></div>
        </div>

        <div id="resultContainer" class="result-container">
            <div id="loadingState" class="hidden flex flex-col items-center justify-center p-8">
                <div class="loading-spinner"></div>
                <p class="text-gray-500 text-center">
                    <span class="font-medium">Procesando...</span>
                    <br>
                    <span class="text-sm">Esto puede tardar unos momentos</span>
                </p>
            </div>

            <div id="resultState" class="hidden w-full p-4">
                <img id="resultImage" class="max-w-full h-auto mx-auto rounded-lg shadow-lg" alt="Resultado del virtual try-on">
                <div id="resultInfo" class="text-center mt-4 space-y-2"></div>
            </div>

            <div id="initialState" class="text-center p-8">
                <i data-lucide="sparkles" class="h-16 w-16 text-gray-300 mx-auto mb-4"></i>
                <p class="text-gray-500 mb-2"><strong>Virtual Try-On</strong></p>
                <p class="text-sm text-gray-400">
                    Sube una imagen de prenda y una persona, ingresa tu token y haz clic en generar.
                </p>
            </div>
        </div>

        <button onclick="window.virtualTryOn.downloadResult()" id="downloadBtn" 
                class="hidden w-full mt-4 btn-primary">
            <i data-lucide="download" class="mr-2 h-4 w-4"></i>
            Descargar Resultado
        </button>
    </div>
</div>

@push('scripts')
<script src="{{ mix('js/virtual-try-on.js') }}"></script>
@endpush

