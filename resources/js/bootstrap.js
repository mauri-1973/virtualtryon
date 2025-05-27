window._ = require("lodash")

/**
 * Configuración de Axios
 */
window.axios = require("axios")

window.axios.defaults.headers.common["X-Requested-With"] = "XMLHttpRequest"

// Configurar CSRF token automáticamente
const token = document.head.querySelector('meta[name="csrf-token"]')

if (token) {
  window.axios.defaults.headers.common["X-CSRF-TOKEN"] = token.content
} else {
  console.error("CSRF token not found: https://laravel.com/docs/csrf#csrf-x-csrf-token")
}

// Configurar interceptores para manejo de errores
window.axios.interceptors.response.use(
  (response) => response,
  (error) => {
    if (error.response) {
      // El servidor respondió con un código de estado que no está en el rango 2xx
      console.error("Error de respuesta:", error.response.data)

      if (error.response.status === 419) {
        // Token CSRF expirado
        window.location.reload()
      }
    } else if (error.request) {
      // La solicitud se hizo pero no se recibió respuesta
      console.error("Error de red:", error.request)
    } else {
      // Algo pasó al configurar la solicitud
      console.error("Error:", error.message)
    }

    return Promise.reject(error)
  },
)