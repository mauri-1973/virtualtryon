<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'ROPA - Probador Virtual con IA')</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Lucide Icons CDN -->
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>
    
    <!-- Custom Styles -->
    <style>
        body {
            font-family: 'Inter', sans-serif;
        }
        
        .upload-area {
            transition: all 0.3s ease;
        }
        
        .upload-area:hover {
            border-color: #3b82f6;
            background-color: #eff6ff;
        }
        
        .upload-area.dragover {
            border-color: #2563eb;
            background-color: #dbeafe;
        }
        
        .loading-spinner {
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }
        
        .notification {
            position: fixed;
            top: 1rem;
            right: 1rem;
            z-index: 50;
            padding: 1rem;
            border-radius: 0.5rem;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
            max-width: 24rem;
            animation: slideIn 0.3s ease-out;
        }
        
        @keyframes slideIn {
            from {
                transform: translateX(100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }
        
        .notification-success {
            background-color: #10b981;
            color: white;
        }
        
        .notification-error {
            background-color: #ef4444;
            color: white;
        }
        
        .notification-warning {
            background-color: #f59e0b;
            color: white;
        }
        
        .notification-info {
            background-color: #3b82f6;
            color: white;
        }
    </style>
    
    @stack('styles')
</head>
<body class="bg-gray-50">
    <!-- Header -->
    <header class="bg-white shadow-sm border-b">
        <div class="container mx-auto py-4 px-4 flex items-center justify-between">
            <a href="{{ route('home') }}" class="text-2xl font-bold text-black hover:text-gray-700">ROPA</a>
            
            <div class="flex items-center space-x-4">
                <!-- Search -->
                <div class="relative hidden md:block">
                    <i data-lucide="search" class="absolute left-2.5 top-2.5 h-4 w-4 text-gray-400"></i>
                    <input type="search" placeholder="Buscar..." 
                           class="w-64 pl-8 pr-4 py-2 rounded-full bg-gray-100 border-0 focus:ring-2 focus:ring-black focus:outline-none">
                </div>
                
                <!-- Navigation -->
                <nav class="hidden md:flex items-center space-x-6">
                    <a href="/women" class="text-sm font-medium hover:underline">Mujeres</a>
                    <a href="/men" class="text-sm font-medium hover:underline">Hombres</a>
                    <a href="/accessories" class="text-sm font-medium hover:underline">Accesorios</a>
                    <a href="/sale" class="text-sm font-medium text-red-500 hover:underline">Ofertas</a>
                </nav>
                
                <!-- User Actions -->
                <div class="flex items-center space-x-4">
                    <a href="/wishlist" class="hover:text-gray-600">
                        <i data-lucide="heart" class="h-6 w-6"></i>
                    </a>
                    <a href="/cart" class="relative hover:text-gray-600">
                        <i data-lucide="shopping-cart" class="h-6 w-6"></i>
                        <span class="absolute -top-2 -right-2 flex h-5 w-5 items-center justify-center rounded-full bg-black text-xs text-white">3</span>
                    </a>
                </div>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="flex-1">
        @yield('content')
    </main>

    <!-- Footer -->
    <footer class="bg-white border-t mt-12 py-6">
        <div class="container mx-auto px-4 text-center text-sm text-gray-500">
            © 2025 ROPA. Todos los derechos reservados.
        </div>
    </footer>

    <!-- Scripts -->
    <script>
        // Inicializar Lucide
        document.addEventListener('DOMContentLoaded', function() {
            if (typeof lucide !== 'undefined') {
                lucide.createIcons();
            }
        });
        
        // Utilidades globales
        window.utils = {
            showNotification(message, type = 'info', duration = 5000) {
                const notification = document.createElement('div');
                notification.className = `notification notification-${type}`;
                
                notification.innerHTML = `
                    <div class="flex items-center">
                        <span class="flex-1">${message}</span>
                        <button onclick="this.parentElement.parentElement.remove()" class="ml-2 text-white hover:text-gray-200">
                            ×
                        </button>
                    </div>
                `;
                
                document.body.appendChild(notification);
                
                setTimeout(() => {
                    if (notification.parentElement) {
                        notification.remove();
                    }
                }, duration);
            },
            
            handleError(error, context = '') {
                console.error(`Error ${context}:`, error);
                this.showNotification(`Error: ${error.message || error}`, 'error');
            },
            
            formatBytes(bytes, decimals = 2) {
                if (bytes === 0) return '0 Bytes';
                const k = 1024;
                const dm = decimals < 0 ? 0 : decimals;
                const sizes = ['Bytes', 'KB', 'MB', 'GB'];
                const i = Math.floor(Math.log(bytes) / Math.log(k));
                return parseFloat((bytes / Math.pow(k, i)).toFixed(dm)) + ' ' + sizes[i];
            },
            
            validateImageFile(file, maxSize = 10 * 1024 * 1024) {
                const validTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'];
                
                if (!validTypes.includes(file.type)) {
                    throw new Error('Tipo de archivo no válido. Solo se permiten JPG, PNG y WEBP.');
                }
                
                if (file.size > maxSize) {
                    throw new Error(`El archivo es demasiado grande. Máximo permitido: ${this.formatBytes(maxSize)}`);
                }
                
                return true;
            }
        };
        
        // Hacer funciones disponibles globalmente
        window.showNotification = window.utils.showNotification.bind(window.utils);
        window.handleError = window.utils.handleError.bind(window.utils);
        window.formatBytes = window.utils.formatBytes.bind(window.utils);
        window.validateImageFile = window.utils.validateImageFile.bind(window.utils);
    </script>
    
    @stack('scripts')
</body>
</html>
