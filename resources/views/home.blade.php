@extends('layouts.app')

@section('title', 'ROPA - Probador Virtual con IA')

@section('content')
    <div class="container mx-auto py-8 px-4">
        <!-- Hero Section -->
        <div class="text-center mb-8">
            <h1 class="text-4xl font-bold mb-4 text-gray-900">Probador Virtual con IA</h1>
            <p class="text-gray-600 max-w-2xl mx-auto text-lg">
                Experimenta la tecnología más avanzada en virtual try-on. 
                Sube una imagen de una prenda y una persona para ver cómo se vería la prenda puesta.
            </p>
        </div>

        <!-- Virtual Fitting Room -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-12">
            <!-- Upload Section -->
            <div class="bg-white p-6 rounded-lg shadow-sm border">
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
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-black focus:border-transparent pr-10">
                            <button type="button" onclick="toggleTokenVisibility()" 
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
                        <select id="garmentPart" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-black focus:border-transparent">
                            <option value="upper_body">Parte superior (camisetas, blusas, chaquetas)</option>
                            <option value="lower_body">Parte inferior (pantalones, faldas)</option>
                            <option value="dresses">Vestidos</option>
                        </select>
                    </div>
                </div>

                <!-- Image Upload Tabs -->
                <div class="mb-6">
                    <div class="flex border-b">
                        <button onclick="switchTab('garment')" id="garmentTab" 
                                class="flex-1 py-2 px-4 text-center border-b-2 border-black font-medium text-black transition-colors">
                            1. Prenda
                        </button>
                        <button onclick="switchTab('person')" id="personTab" 
                                class="flex-1 py-2 px-4 text-center border-b-2 border-transparent text-gray-500 hover:text-gray-700 transition-colors">
                            2. Persona
                        </button>
                    </div>

                    <!-- Garment Upload -->
                    <div id="garmentUpload" class="mt-4">
                        <h3 class="text-lg font-semibold mb-2">Sube la imagen de la prenda</h3>
                        <p class="text-sm text-gray-500 mb-4">
                            Sube una imagen clara de la prenda sobre fondo blanco o transparente para mejores resultados.
                        </p>
                        <div class="upload-area border-2 border-dashed border-gray-300 rounded-lg p-6 text-center cursor-pointer">
                            <input type="file" id="garmentImageInput" accept="image/*" class="hidden">
                            <div id="garmentPreview" class="hidden">
                                <img id="garmentImg" class="max-w-full h-auto mx-auto rounded-lg mb-2" alt="Prenda seleccionada">
                                <button onclick="removeGarmentImage()" class="text-red-500 text-sm hover:text-red-700">
                                    <i data-lucide="trash-2" class="h-4 w-4 inline mr-1"></i>
                                    Eliminar
                                </button>
                            </div>
                            <div id="garmentUploadArea">
                                <i data-lucide="image" class="h-12 w-12 text-gray-400 mx-auto mb-4"></i>
                                <p class="text-sm font-medium mb-1">Arrastra y suelta tu imagen aquí</p>
                                <p class="text-xs text-gray-500 mb-4">PNG, JPG o WEBP (máx. 10MB)</p>
                                <button type="button" onclick="document.getElementById('garmentImageInput').click()" 
                                        class="bg-gray-200 text-gray-800 px-4 py-2 rounded-md hover:bg-gray-300 transition-colors">
                                    <i data-lucide="upload" class="h-4 w-4 mr-2 inline"></i>
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
                        <div class="upload-area border-2 border-dashed border-gray-300 rounded-lg p-6 text-center cursor-pointer">
                            <input type="file" id="personImageInput" accept="image/*" class="hidden">
                            <div id="personPreview" class="hidden">
                                <img id="personImg" class="max-w-full h-auto mx-auto rounded-lg mb-2" alt="Persona seleccionada">
                                <button onclick="removePersonImage()" class="text-red-500 text-sm hover:text-red-700">
                                    <i data-lucide="trash-2" class="h-4 w-4 inline mr-1"></i>
                                    Eliminar
                                </button>
                            </div>
                            <div id="personUploadArea">
                                <i data-lucide="user" class="h-12 w-12 text-gray-400 mx-auto mb-4"></i>
                                <p class="text-sm font-medium mb-1">Arrastra y suelta tu imagen aquí</p>
                                <p class="text-xs text-gray-500 mb-4">PNG, JPG o WEBP (máx. 10MB)</p>
                                <button type="button" onclick="document.getElementById('personImageInput').click()" 
                                        class="bg-gray-200 text-gray-800 px-4 py-2 rounded-md hover:bg-gray-300 transition-colors">
                                    <i data-lucide="upload" class="h-4 w-4 mr-2 inline"></i>
                                    Seleccionar imagen
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Generate Button -->
                <button onclick="generateTryOn()" id="generateBtn" 
                        class="w-full bg-black text-white px-4 py-3 rounded-md hover:bg-gray-800 transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
                    <i data-lucide="sparkles" class="mr-2 h-4 w-4 inline"></i>
                    Generar Virtual Try-On
                </button>
            </div>

            <!-- Result Section -->
            <div class="bg-white p-6 rounded-lg shadow-sm border">
                <h2 class="text-xl font-semibold mb-4">Resultado</h2>
                
                <div id="errorAlert" class="hidden bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-4">
                    <div class="flex items-center">
                        <i data-lucide="alert-triangle" class="h-4 w-4 text-red-500 mr-2"></i>
                        <div class="font-medium text-red-800">Error:</div>
                    </div>
                    <div id="errorMessage" class="mt-1 text-sm"></div>
                </div>

                <div class="border rounded-lg overflow-hidden bg-gray-50 flex items-center justify-center min-h-96 relative">
                    <div id="loadingState" class="hidden flex flex-col items-center justify-center p-8">
                        <div class="loading-spinner rounded-full h-12 w-12 border-b-2 border-blue-500 mb-4"></div>
                        <p class="text-gray-500 text-center">
                            <span class="font-medium">Procesando con IA...</span>
                            <br>
                            <span class="text-sm">Conectando con Replicate API...</span>
                            <br>
                            <span class="text-xs text-gray-400 mt-2 block">💡 Esto puede tardar 1-4 minutos</span>
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

                <button onclick="downloadResult()" id="downloadBtn" 
                        class="hidden w-full mt-4 bg-black text-white px-4 py-2 rounded-md hover:bg-gray-800 transition-colors">
                    <i data-lucide="download" class="mr-2 h-4 w-4 inline"></i>
                    Descargar Resultado
                </button>
            </div>
        </div>

        <!-- Success Message -->
        <div class="text-center bg-green-50 border border-green-200 rounded-lg p-6">
            <h2 class="text-green-800 text-2xl font-bold mb-2">🎉 ¡Aplicación Simplificada Lista!</h2>
            <p class="text-green-700">
                ROPA Virtual Try-On ahora usa únicamente <strong>Flux VTON</strong> - el modelo que sabemos que funciona.
                <br>
                Solo necesitas subir las imágenes e ingresar tu token de Replicate.
            </p>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    // Variables globales simplificadas
    let garmentImage = null;
    let personImage = null;
    let resultImageData = null;

    console.log('🚀 ROPA Virtual Try-On (Versión Simplificada) cargado');

    // Funciones de pestañas
    function switchTab(tab) {
        const garmentTab = document.getElementById('garmentTab');
        const personTab = document.getElementById('personTab');
        const garmentUpload = document.getElementById('garmentUpload');
        const personUpload = document.getElementById('personUpload');

        if (tab === 'garment') {
            garmentTab.classList.add('border-black', 'font-medium', 'text-black');
            garmentTab.classList.remove('border-transparent', 'text-gray-500');
            personTab.classList.add('border-transparent', 'text-gray-500');
            personTab.classList.remove('border-black', 'font-medium', 'text-black');
            garmentUpload.classList.remove('hidden');
            personUpload.classList.add('hidden');
        } else {
            personTab.classList.add('border-black', 'font-medium', 'text-black');
            personTab.classList.remove('border-transparent', 'text-gray-500');
            garmentTab.classList.add('border-transparent', 'text-gray-500');
            garmentTab.classList.remove('border-black', 'font-medium', 'text-black');
            personUpload.classList.remove('hidden');
            garmentUpload.classList.add('hidden');
        }
    }

    // Función para mostrar/ocultar token
    function toggleTokenVisibility() {
        const tokenInput = document.getElementById('replicateToken');
        const toggleIcon = document.getElementById('tokenToggleIcon');

        if (tokenInput.type === 'password') {
            tokenInput.type = 'text';
            toggleIcon.setAttribute('data-lucide', 'eye');
        } else {
            tokenInput.type = 'password';
            toggleIcon.setAttribute('data-lucide', 'eye-off');
        }

        if (typeof lucide !== 'undefined') {
            lucide.createIcons();
        }
    }

    // Validación de token simplificada
    function isValidReplicateToken(token) {
        return token && token.length >= 10 && token.startsWith('r8_');
    }

    // Manejo de imágenes
    function handleGarmentUpload(event) {
        const file = event.target.files[0];
        if (file && file.type.startsWith('image/')) {
            garmentImage = file;
            const reader = new FileReader();
            reader.onload = (e) => {
                const img = document.getElementById('garmentImg');
                const preview = document.getElementById('garmentPreview');
                const uploadArea = document.getElementById('garmentUploadArea');
                
                if (img && preview && uploadArea) {
                    img.src = e.target.result;
                    preview.classList.remove('hidden');
                    uploadArea.classList.add('hidden');
                }
            };
            reader.readAsDataURL(file);
            updateGenerateButton();
        }
    }

    function handlePersonUpload(event) {
        const file = event.target.files[0];
        if (file && file.type.startsWith('image/')) {
            personImage = file;
            const reader = new FileReader();
            reader.onload = (e) => {
                const img = document.getElementById('personImg');
                const preview = document.getElementById('personPreview');
                const uploadArea = document.getElementById('personUploadArea');
                
                if (img && preview && uploadArea) {
                    img.src = e.target.result;
                    preview.classList.remove('hidden');
                    uploadArea.classList.add('hidden');
                }
            };
            reader.readAsDataURL(file);
            updateGenerateButton();
        }
    }

    function removeGarmentImage() {
        garmentImage = null;
        const input = document.getElementById('garmentImageInput');
        const preview = document.getElementById('garmentPreview');
        const uploadArea = document.getElementById('garmentUploadArea');
        
        if (input) input.value = '';
        if (preview) preview.classList.add('hidden');
        if (uploadArea) uploadArea.classList.remove('hidden');
        updateGenerateButton();
    }

    function removePersonImage() {
        personImage = null;
        const input = document.getElementById('personImageInput');
        const preview = document.getElementById('personPreview');
        const uploadArea = document.getElementById('personUploadArea');
        
        if (input) input.value = '';
        if (preview) preview.classList.add('hidden');
        if (uploadArea) uploadArea.classList.remove('hidden');
        updateGenerateButton();
    }

    // Lógica simplificada del botón
    function updateGenerateButton() {
        const generateBtn = document.getElementById('generateBtn');
        const tokenInput = document.getElementById('replicateToken');

        if (!generateBtn || !tokenInput) return;

        const hasValidToken = isValidReplicateToken(tokenInput.value);
        const hasImages = garmentImage && personImage;
        
        // Lógica simple: solo necesita token válido e imágenes
        const canGenerate = hasValidToken && hasImages;
        generateBtn.disabled = !canGenerate;

        if (canGenerate) {
            generateBtn.classList.remove('opacity-50', 'cursor-not-allowed');
            generateBtn.innerHTML = '<i data-lucide="sparkles" class="mr-2 h-4 w-4 inline"></i>Generar Virtual Try-On';
        } else {
            generateBtn.classList.add('opacity-50', 'cursor-not-allowed');
            
            if (!hasValidToken) {
                generateBtn.innerHTML = '<i data-lucide="key" class="mr-2 h-4 w-4 inline"></i>Ingresa token válido';
            } else if (!hasImages) {
                generateBtn.innerHTML = '<i data-lucide="image" class="mr-2 h-4 w-4 inline"></i>Sube las imágenes';
            }
        }

        if (typeof lucide !== 'undefined') {
            lucide.createIcons();
        }
    }

    // Funciones de UI
    function showLoadingState() {
        const initialState = document.getElementById('initialState');
        const resultState = document.getElementById('resultState');
        const loadingState = document.getElementById('loadingState');

        if (initialState) initialState.classList.add('hidden');
        if (resultState) resultState.classList.add('hidden');
        if (loadingState) {
            loadingState.classList.remove('hidden');
            loadingState.innerHTML = `
                <div class="loading-spinner rounded-full h-12 w-12 border-b-2 border-blue-500 mb-4"></div>
                <p class="text-gray-500 text-center">
                    <span class="font-medium">Procesando con Flux VTON...</span>
                    <br>
                    <span class="text-sm">Esto puede tardar 1-4 minutos</span>
                </p>
            `;
        }
    }

    function hideLoadingState() {
        const loadingState = document.getElementById('loadingState');
        if (loadingState) loadingState.classList.add('hidden');
    }

    function showResultState(result) {
        const resultImage = document.getElementById('resultImage');
        const resultInfo = document.getElementById('resultInfo');
        const downloadBtn = document.getElementById('downloadBtn');
        const resultState = document.getElementById('resultState');

        if (!resultImage || !resultInfo || !downloadBtn || !resultState) return;

        resultImage.src = result.imageBase64;
        resultImageData = result.imageBase64;

        let infoHTML = '';
        if (result.modelUsed) {
            infoHTML += `<div class="flex items-center justify-center">
                <i data-lucide="check-circle" class="h-4 w-4 text-green-500 mr-2"></i>
                <span class="text-sm text-gray-600">Generado con: ${result.modelUsed}</span>
            </div>`;
        }
        if (result.predictionId) {
            infoHTML += `<div class="text-xs text-gray-400">ID: ${result.predictionId}</div>`;
        }

        resultInfo.innerHTML = infoHTML;
        resultState.classList.remove('hidden');
        downloadBtn.classList.remove('hidden');

        if (typeof lucide !== 'undefined') {
            lucide.createIcons();
        }
    }

    function showError(message) {
        const errorAlert = document.getElementById('errorAlert');
        const errorMessage = document.getElementById('errorMessage');

        if (!errorAlert || !errorMessage) return;

        const formattedMessage = message.replace(/\n/g, '<br>');
        errorMessage.innerHTML = formattedMessage;
        errorAlert.classList.remove('hidden');
    }

    function hideError() {
        const errorAlert = document.getElementById('errorAlert');
        if (errorAlert) errorAlert.classList.add('hidden');
    }

    function downloadResult() {
        if (!resultImageData) return;

        try {
            const link = document.createElement('a');
            link.href = resultImageData;
            link.download = 'virtual-try-on-resultado.png';
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        } catch (error) {
            console.error('Error al descargar:', error);
            showError('Error al descargar la imagen');
        }
    }

    // Función principal simplificada
    async function generateTryOn() {
        const tokenInput = document.getElementById('replicateToken');
        const garmentPartSelect = document.getElementById('garmentPart');

        if (!tokenInput || !garmentPartSelect) {
            showError('Error: Elementos de la interfaz no encontrados.');
            return;
        }

        const token = tokenInput.value;
        const garmentPart = garmentPartSelect.value;

        // Validaciones
        if (!garmentImage || !personImage) {
            showError('Por favor, sube tanto la imagen de la prenda como la imagen de la persona.');
            return;
        }

        if (!isValidReplicateToken(token)) {
            showError('El token de Replicate debe comenzar con "r8_" y tener al menos 10 caracteres.');
            return;
        }

        showLoadingState();
        hideError();

        try {
            const formData = new FormData();
            formData.append('garment_image', garmentImage);
            formData.append('person_image', personImage);
            formData.append('api_token', token);
            formData.append('garment_part', garmentPart);

            const csrfToken = document.querySelector('meta[name="csrf-token"]');
            if (!csrfToken) {
                throw new Error('CSRF token no encontrado');
            }

            console.log('🚀 Enviando solicitud a Flux VTON...');

            const response = await fetch('{{ url("/api/virtual-try-on") }}', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrfToken.getAttribute('content'),
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: formData
            });

            const result = await response.json();

            if (!response.ok) {
                throw new Error(result.error || `Error HTTP ${response.status}`);
            }

            if (result.success && result.imageBase64) {
                console.log('✅ ¡Éxito! Mostrando resultado...');
                showResultState(result);
            } else {
                showError(result.error || 'Error al procesar las imágenes.');
            }
            
        } catch (error) {
            console.error('💥 Error:', error);
            showError('❌ Error: ' + error.message);
        } finally {
            hideLoadingState();
        }
    }

    // Configurar drag and drop
    function setupDragAndDrop() {
        const uploadAreas = document.querySelectorAll('.upload-area');

        uploadAreas.forEach((area) => {
            area.addEventListener('dragover', (e) => {
                e.preventDefault();
                area.classList.add('border-blue-500', 'bg-blue-50');
            });

            area.addEventListener('dragleave', () => {
                area.classList.remove('border-blue-500', 'bg-blue-50');
            });

            area.addEventListener('drop', (e) => {
                e.preventDefault();
                area.classList.remove('border-blue-500', 'bg-blue-50');

                const files = e.dataTransfer.files;
                if (files.length > 0) {
                    const file = files[0];
                    if (file.type.startsWith('image/')) {
                        if (area.closest('#garmentUpload')) {
                            garmentImage = file;
                            handleImagePreview(file, 'garment');
                        } else if (area.closest('#personUpload')) {
                            personImage = file;
                            handleImagePreview(file, 'person');
                        }
                    }
                }
            });
        });
    }

    function handleImagePreview(file, type) {
        const reader = new FileReader();
        reader.onload = (e) => {
            if (type === 'garment') {
                const img = document.getElementById('garmentImg');
                const preview = document.getElementById('garmentPreview');
                const uploadArea = document.getElementById('garmentUploadArea');

                if (img && preview && uploadArea) {
                    img.src = e.target.result;
                    preview.classList.remove('hidden');
                    uploadArea.classList.add('hidden');
                }
            } else {
                const img = document.getElementById('personImg');
                const preview = document.getElementById('personPreview');
                const uploadArea = document.getElementById('personUploadArea');

                if (img && preview && uploadArea) {
                    img.src = e.target.result;
                    preview.classList.remove('hidden');
                    uploadArea.classList.add('hidden');
                }
            }
        };
        reader.readAsDataURL(file);
        updateGenerateButton();
    }

    // Inicialización simplificada
    document.addEventListener('DOMContentLoaded', function() {
        console.log('🚀 Inicializando aplicación simplificada...');
        
        // Setup file inputs
        const garmentInput = document.getElementById('garmentImageInput');
        const personInput = document.getElementById('personImageInput');
        
        if (garmentInput) {
            garmentInput.addEventListener('change', handleGarmentUpload);
        }
        
        if (personInput) {
            personInput.addEventListener('change', handlePersonUpload);
        }
        
        // Setup token validation
        const tokenInput = document.getElementById('replicateToken');
        if (tokenInput) {
            tokenInput.addEventListener('input', function() {
                const token = this.value;
                const statusDiv = document.getElementById('tokenStatus');
                
                if (!statusDiv) return;
                
                if (token.length === 0) {
                    statusDiv.innerHTML = '';
                } else if (isValidReplicateToken(token)) {
                    statusDiv.innerHTML = '<span class="text-green-600">✅ Token válido - Listo para usar Flux VTON</span>';
                } else {
                    statusDiv.innerHTML = '<span class="text-red-600">❌ Token debe comenzar con "r8_" y tener al menos 10 caracteres</span>';
                }
                updateGenerateButton();
            });
        }

        // Setup drag and drop
        setupDragAndDrop();
        
        // Initialize Lucide icons
        if (typeof lucide !== 'undefined') {
            lucide.createIcons();
        }
        
        console.log('✅ Aplicación lista - Solo usando Flux VTON');
    });

    // Hacer funciones disponibles globalmente
    window.switchTab = switchTab;
    window.toggleTokenVisibility = toggleTokenVisibility;
    window.removeGarmentImage = removeGarmentImage;
    window.removePersonImage = removePersonImage;
    window.generateTryOn = generateTryOn;
    window.downloadResult = downloadResult;
    window.showError = showError;
    window.hideError = hideError;
    window.setupDragAndDrop = setupDragAndDrop;
</script>
@endpush
