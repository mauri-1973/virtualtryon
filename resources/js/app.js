// Importar dependencias básicas
require("./bootstrap")

// Importar Alpine.js
import Alpine from "alpinejs"

// Hacer Alpine disponible globalmente
window.Alpine = Alpine

// Configurar Alpine
Alpine.start()

// Utilidades globales
window.utils = {
  // Función para mostrar notificaciones
  showNotification(message, type = "info", duration = 5000) {
    const notification = document.createElement("div")
    notification.className = `notification notification-${type}`

    notification.innerHTML = `
      <div class="flex items-center">
        <span class="flex-1">${message}</span>
        <button onclick="this.parentElement.parentElement.remove()" class="ml-2 text-white hover:text-gray-200">
          ×
        </button>
      </div>
    `

    document.body.appendChild(notification)

    // Auto-remove
    setTimeout(() => {
      if (notification.parentElement) {
        notification.remove()
      }
    }, duration)
  },

  // Función para manejar errores
  handleError(error, context = "") {
    console.error(`Error ${context}:`, error)
    this.showNotification(`Error: ${error.message || error}`, "error")
  },

  // Función para formatear bytes
  formatBytes(bytes, decimals = 2) {
    if (bytes === 0) return "0 Bytes"

    const k = 1024
    const dm = decimals < 0 ? 0 : decimals
    const sizes = ["Bytes", "KB", "MB", "GB"]

    const i = Math.floor(Math.log(bytes) / Math.log(k))

    return Number.parseFloat((bytes / Math.pow(k, i)).toFixed(dm)) + " " + sizes[i]
  },

  // Función para validar archivos de imagen
  validateImageFile(file, maxSize = 10 * 1024 * 1024) {
    const validTypes = ["image/jpeg", "image/jpg", "image/png", "image/webp"]

    if (!validTypes.includes(file.type)) {
      throw new Error("Tipo de archivo no válido. Solo se permiten JPG, PNG y WEBP.")
    }

    if (file.size > maxSize) {
      throw new Error(`El archivo es demasiado grande. Máximo permitido: ${this.formatBytes(maxSize)}`)
    }

    return true
  },

  // Función para cargar Lucide
  async loadLucide() {
    return new Promise((resolve, reject) => {
      if (typeof window.lucide !== "undefined") {
        resolve(window.lucide)
        return
      }

      const script = document.createElement("script")
      script.src = "https://unpkg.com/lucide@latest/dist/umd/lucide.js"
      script.onload = () => {
        setTimeout(() => {
          if (typeof window.lucide !== "undefined" && window.lucide.createIcons) {
            window.lucide.createIcons()
            resolve(window.lucide)
          } else {
            reject(new Error("Lucide no se cargó correctamente"))
          }
        }, 100)
      }
      script.onerror = () => {
        reject(new Error("Error al cargar Lucide"))
      }
      document.head.appendChild(script)
    })
  },
}

// Cargar Lucide cuando el DOM esté listo
document.addEventListener("DOMContentLoaded", () => {
  window.utils
    .loadLucide()
    .then(() => {
      console.log("Lucide cargado exitosamente")
    })
    .catch((error) => {
      console.error("Error cargando Lucide:", error)
    })
})

// Hacer funciones disponibles globalmente para compatibilidad
window.showNotification = window.utils.showNotification.bind(window.utils)
window.handleError = window.utils.handleError.bind(window.utils)
window.formatBytes = window.utils.formatBytes.bind(window.utils)
window.validateImageFile = window.utils.validateImageFile.bind(window.utils)

