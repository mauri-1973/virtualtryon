// Variables globales para el Virtual Try-On
let garmentImage = null
let personImage = null
let resultImageData = null
let currentPredictionId = null
let availableModels = {}

// Función para verificar si Lucide está disponible
function isLucideReady() {
  return (
    typeof window.lucide !== "undefined" && window.lucide !== null && typeof window.lucide.createIcons === "function"
  )
}

// Función para crear iconos de forma segura
function safeCreateIcons() {
  if (isLucideReady()) {
    try {
      window.lucide.createIcons()
    } catch (error) {
      console.log("Error creating Lucide icons:", error)
    }
  }
}

// Función para esperar a que Lucide esté listo
function waitForLucide(callback, maxAttempts = 10) {
  let attempts = 0

  function checkLucide() {
    attempts++

    if (isLucideReady()) {
      callback()
    } else if (attempts < maxAttempts) {
      setTimeout(checkLucide, 200)
    } else {
      console.log("Lucide no se cargó después de", maxAttempts, "intentos")
    }
  }

  checkLucide()
}

// Inicialización
document.addEventListener("DOMContentLoaded", () => {
  waitForLucide(() => {
    safeCreateIcons()
    console.log("Virtual Try-On: Lucide inicializado correctamente")
  })

  initializeVirtualTryOn()
})

// Función principal de inicialización
function initializeVirtualTryOn() {
  checkTokenStatus()
  setupDragAndDrop()
  setupImageValidation()
}

// Funciones de pestañas
function switchTab(tab) {
  const garmentTab = document.getElementById("garmentTab")
  const personTab = document.getElementById("personTab")
  const garmentUpload = document.getElementById("garmentUpload")
  const personUpload = document.getElementById("personUpload")

  if (!garmentTab || !personTab || !garmentUpload || !personUpload) {
    console.error("Elementos de pestañas no encontrados")
    return
  }

  if (tab === "garment") {
    garmentTab.classList.add("border-black", "font-medium")
    garmentTab.classList.remove("border-transparent", "text-gray-500")
    personTab.classList.add("border-transparent", "text-gray-500")
    personTab.classList.remove("border-black", "font-medium")
    garmentUpload.classList.remove("hidden")
    personUpload.classList.add("hidden")
  } else {
    personTab.classList.add("border-black", "font-medium")
    personTab.classList.remove("border-transparent", "text-gray-500")
    garmentTab.classList.add("border-transparent", "text-gray-500")
    garmentTab.classList.remove("border-black", "font-medium")
    personUpload.classList.remove("hidden")
    garmentUpload.classList.add("hidden")
  }
}

// Funciones de manejo de imágenes
function handleGarmentUpload(event) {
  const file = event.target.files[0]
  if (file && file.type.startsWith("image/")) {
    try {
      if (window.validateImageFile) {
        window.validateImageFile(file)
      }

      garmentImage = file
      const reader = new FileReader()
      reader.onload = (e) => {
        const img = document.getElementById("garmentImg")
        const preview = document.getElementById("garmentPreview")
        const uploadArea = document.getElementById("garmentUploadArea")

        if (img && preview && uploadArea) {
          img.src = e.target.result
          preview.classList.remove("hidden")
          uploadArea.classList.add("hidden")
        }
      }
      reader.readAsDataURL(file)
      updateGenerateButton()

      if (window.showNotification) {
        window.showNotification("Imagen de prenda cargada correctamente", "success")
      }
    } catch (error) {
      if (window.handleError) {
        window.handleError(error, "al cargar imagen de prenda")
      } else {
        console.error("Error al cargar imagen de prenda:", error)
      }
    }
  }
}

function handlePersonUpload(event) {
  const file = event.target.files[0]
  if (file && file.type.startsWith("image/")) {
    try {
      if (window.validateImageFile) {
        window.validateImageFile(file)
      }

      personImage = file
      const reader = new FileReader()
      reader.onload = (e) => {
        const img = document.getElementById("personImg")
        const preview = document.getElementById("personPreview")
        const uploadArea = document.getElementById("personUploadArea")

        if (img && preview && uploadArea) {
          img.src = e.target.result
          preview.classList.remove("hidden")
          uploadArea.classList.add("hidden")
        }
      }
      reader.readAsDataURL(file)
      updateGenerateButton()

      if (window.showNotification) {
        window.showNotification("Imagen de persona cargada correctamente", "success")
      }
    } catch (error) {
      if (window.handleError) {
        window.handleError(error, "al cargar imagen de persona")
      } else {
        console.error("Error al cargar imagen de persona:", error)
      }
    }
  }
}

function removeGarmentImage() {
  garmentImage = null
  const input = document.getElementById("garmentImageInput")
  const preview = document.getElementById("garmentPreview")
  const uploadArea = document.getElementById("garmentUploadArea")

  if (input) input.value = ""
  if (preview) preview.classList.add("hidden")
  if (uploadArea) uploadArea.classList.remove("hidden")

  updateGenerateButton()
}

function removePersonImage() {
  personImage = null
  const input = document.getElementById("personImageInput")
  const preview = document.getElementById("personPreview")
  const uploadArea = document.getElementById("personUploadArea")

  if (input) input.value = ""
  if (preview) preview.classList.add("hidden")
  if (uploadArea) uploadArea.classList.remove("hidden")

  updateGenerateButton()
}

// Función para mostrar/ocultar token
function toggleTokenVisibility() {
  const tokenInput = document.getElementById("replicateToken")
  const toggleIcon = document.getElementById("tokenToggleIcon")

  if (!tokenInput) return

  if (tokenInput.type === "password") {
    tokenInput.type = "text"
    if (toggleIcon) {
      toggleIcon.setAttribute("data-lucide", "eye")
    }
  } else {
    tokenInput.type = "password"
    if (toggleIcon) {
      toggleIcon.setAttribute("data-lucide", "eye-off")
    }
  }

  safeCreateIcons()
}

// Validación de token
function isValidReplicateToken(token) {
  return token && token.length >= 10 && token.startsWith("r8_")
}

function checkTokenStatus() {
  const tokenInput = document.getElementById("replicateToken")
  const statusDiv = document.getElementById("tokenStatus")

  if (!tokenInput || !statusDiv) {
    console.log("Elementos de token no encontrados")
    return
  }

  tokenInput.addEventListener("input", function () {
    const token = this.value
    if (token.length === 0) {
      statusDiv.innerHTML = ""
      availableModels = {}
    } else if (isValidReplicateToken(token)) {
      statusDiv.innerHTML = '<span class="text-green-600">✅ Token válido</span>'
      setTimeout(() => checkAvailableModels(), 500)
    } else {
      statusDiv.innerHTML =
        '<span class="text-red-600">❌ Token debe comenzar con "r8_" y tener al menos 10 caracteres</span>'
      availableModels = {}
    }
    updateGenerateButton()
  })
}

// Función para verificar modelos disponibles
async function checkAvailableModels() {
  const token = document.getElementById("replicateToken")?.value

  if (!isValidReplicateToken(token)) {
    return
  }

  try {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')
    if (!csrfToken) {
      console.log("CSRF token no encontrado")
      return
    }

    const response = await fetch("/api/check-models", {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
        "X-CSRF-TOKEN": csrfToken.getAttribute("content"),
      },
      body: JSON.stringify({ api_token: token }),
    })

    const result = await response.json()

    if (result.success) {
      availableModels = result.models
      updateModelStatus()
    }
  } catch (error) {
    console.log("No se pudo verificar disponibilidad de modelos:", error)
  }
}

// Función para mostrar estado de los modelos
function updateModelStatus() {
  const statusDiv = document.getElementById("tokenStatus")

  if (!statusDiv || Object.keys(availableModels).length === 0) {
    return
  }

  let statusHTML = '<div class="mt-2 text-xs space-y-1">'

  Object.entries(availableModels).forEach(([key, model]) => {
    const icon = model.available ? "✅" : "❌"
    const color = model.available ? "text-green-600" : "text-red-600"
    statusHTML += `<div class="${color}">${icon} ${model.name}</div>`
  })

  statusHTML += "</div>"

  const currentStatus = statusDiv.innerHTML
  if (currentStatus.includes("Token válido")) {
    statusDiv.innerHTML = currentStatus + statusHTML
  }
}

// Actualizar estado del botón generar
function updateGenerateButton() {
  const generateBtn = document.getElementById("generateBtn")
  const tokenInput = document.getElementById("replicateToken")

  if (!generateBtn || !tokenInput) {
    return
  }

  const canGenerate = garmentImage && personImage && isValidReplicateToken(tokenInput.value)
  generateBtn.disabled = !canGenerate

  if (canGenerate) {
    generateBtn.classList.remove("opacity-50", "cursor-not-allowed")
  } else {
    generateBtn.classList.add("opacity-50", "cursor-not-allowed")
  }
}

// Función principal para generar try-on
async function generateTryOn() {
  const tokenInput = document.getElementById("replicateToken")
  const garmentPartSelect = document.getElementById("garmentPart")

  if (!tokenInput || !garmentPartSelect) {
    showError("Error: Elementos de la interfaz no encontrados.")
    return
  }

  const token = tokenInput.value
  const garmentPart = garmentPartSelect.value

  if (!garmentImage || !personImage) {
    showError("Por favor, sube tanto la imagen de la prenda como la imagen de la persona.")
    return
  }

  if (!isValidReplicateToken(token)) {
    showError('El token de Replicate debe comenzar con "r8_" y tener al menos 10 caracteres.')
    return
  }

  showLoadingState()
  hideError()
  currentPredictionId = null

  try {
    const formData = new FormData()
    formData.append("garment_image", garmentImage)
    formData.append("person_image", personImage)
    formData.append("api_token", token)
    formData.append("garment_part", garmentPart)

    const csrfToken = document.querySelector('meta[name="csrf-token"]')
    if (!csrfToken) {
      throw new Error("CSRF token no encontrado")
    }

    const controller = new AbortController()
    const timeoutId = setTimeout(() => controller.abort(), 300000) // 5 minutos

    const response = await fetch("/api/virtual-try-on", {
      method: "POST",
      headers: {
        "X-CSRF-TOKEN": csrfToken.getAttribute("content"),
      },
      body: formData,
      signal: controller.signal,
    })

    clearTimeout(timeoutId)

    const result = await response.json()

    if (result.success && result.imageBase64) {
      showResultState(result)
      if (window.showNotification) {
        window.showNotification("Virtual Try-On generado exitosamente", "success")
      }
    } else {
      let errorMessage = result.error || "Ocurrió un error al procesar las imágenes."

      if (result.suggestion) {
        errorMessage += "\n\n💡 " + result.suggestion
      }

      if (result.details) {
        errorMessage += "\n\n🔄 Se intentó con múltiples modelos:"
        if (result.details.flux_error) {
          errorMessage += "\n• Flux VTON: " + result.details.flux_error
        }
        if (result.details.idm_error) {
          errorMessage += "\n• IDM-VTON: " + result.details.idm_error
        }
      }

      showError(errorMessage)
    }
  } catch (error) {
    console.error("Error generating try-on:", error)

    if (error.name === "AbortError") {
      showError(
        "⏱️ La solicitud tardó demasiado tiempo.\n\nEsto puede suceder cuando:\n• Los modelos están muy ocupados\n• Tu conexión es lenta\n• Las imágenes son muy grandes\n\n💡 Inténtalo de nuevo en unos minutos.",
      )
    } else {
      showError(
        "❌ Error de conexión.\n\nVerifica:\n• Tu conexión a internet\n• Que tu token sea válido\n• Que tengas créditos en Replicate",
      )
    }
  } finally {
    hideLoadingState()
  }
}

// Funciones de UI
function showLoadingState() {
  const initialState = document.getElementById("initialState")
  const resultState = document.getElementById("resultState")
  const loadingState = document.getElementById("loadingState")

  if (initialState) initialState.classList.add("hidden")
  if (resultState) resultState.classList.add("hidden")
  if (loadingState) {
    loadingState.classList.remove("hidden")
    loadingState.innerHTML = `
      <div class="loading-spinner"></div>
      <p class="text-gray-500 text-center">
        <span class="font-medium">Procesando con IA...</span>
        <br>
        <span class="text-sm">Probando múltiples modelos (1-4 minutos)</span>
        <br>
        <span class="text-xs text-gray-400 mt-2 block">💡 Se intentará con Flux VTON y luego IDM-VTON</span>
      </p>
    `
  }
}

function hideLoadingState() {
  const loadingState = document.getElementById("loadingState")
  if (loadingState) {
    loadingState.classList.add("hidden")
  }
}

function showResultState(result) {
  const resultImage = document.getElementById("resultImage")
  const resultInfo = document.getElementById("resultInfo")
  const downloadBtn = document.getElementById("downloadBtn")
  const resultState = document.getElementById("resultState")

  if (!resultImage || !resultInfo || !downloadBtn || !resultState) {
    console.error("Elementos de resultado no encontrados")
    return
  }

  resultImage.src = result.imageBase64
  resultImageData = result.imageBase64

  let infoHTML = ""
  if (result.modelUsed) {
    infoHTML += `<div class="flex items-center justify-center">
      <i data-lucide="check-circle" class="h-4 w-4 text-green-500 mr-2"></i>
      <span class="text-sm text-gray-600">Generado con: ${result.modelUsed}</span>
    </div>`
  }
  if (result.predictionId) {
    infoHTML += `<div class="text-xs text-gray-400">ID: ${result.predictionId}</div>`
    currentPredictionId = result.predictionId
  }

  resultInfo.innerHTML = infoHTML
  resultState.classList.remove("hidden")
  downloadBtn.classList.remove("hidden")

  safeCreateIcons()
}

function showError(message) {
  const errorAlert = document.getElementById("errorAlert")
  const errorMessage = document.getElementById("errorMessage")

  if (!errorAlert || !errorMessage) {
    console.error("Elementos de error no encontrados")
    return
  }

  const formattedMessage = message.replace(/\n/g, "<br>")
  errorMessage.innerHTML = formattedMessage
  errorAlert.classList.remove("hidden")
}

function hideError() {
  const errorAlert = document.getElementById("errorAlert")
  if (errorAlert) {
    errorAlert.classList.add("hidden")
  }
}

function downloadResult() {
  if (!resultImageData) {
    console.log("No hay imagen para descargar")
    return
  }

  try {
    const link = document.createElement("a")
    link.href = resultImageData
    link.download = "virtual-try-on-resultado.png"
    document.body.appendChild(link)
    link.click()
    document.body.removeChild(link)

    if (window.showNotification) {
      window.showNotification("Imagen descargada correctamente", "success")
    }
  } catch (error) {
    console.error("Error al descargar:", error)
    showError("Error al descargar la imagen")
  }
}

// Configurar drag and drop
function setupDragAndDrop() {
  const uploadAreas = document.querySelectorAll(".upload-area")

  uploadAreas.forEach((area) => {
    area.addEventListener("dragover", (e) => {
      e.preventDefault()
      area.classList.add("dragover")
    })

    area.addEventListener("dragleave", () => {
      area.classList.remove("dragover")
    })

    area.addEventListener("drop", (e) => {
      e.preventDefault()
      area.classList.remove("dragover")

      const files = e.dataTransfer.files
      if (files.length > 0) {
        const file = files[0]
        if (file.type.startsWith("image/")) {
          // Determinar si es para prenda o persona basado en el área
          if (area.closest("#garmentUpload")) {
            garmentImage = file
            handleImagePreview(file, "garment")
          } else if (area.closest("#personUpload")) {
            personImage = file
            handleImagePreview(file, "person")
          }
        }
      }
    })
  })
}

function handleImagePreview(file, type) {
  const reader = new FileReader()
  reader.onload = (e) => {
    if (type === "garment") {
      const img = document.getElementById("garmentImg")
      const preview = document.getElementById("garmentPreview")
      const uploadArea = document.getElementById("garmentUploadArea")

      if (img && preview && uploadArea) {
        img.src = e.target.result
        preview.classList.remove("hidden")
        uploadArea.classList.add("hidden")
      }
    } else {
      const img = document.getElementById("personImg")
      const preview = document.getElementById("personPreview")
      const uploadArea = document.getElementById("personUploadArea")

      if (img && preview && uploadArea) {
        img.src = e.target.result
        preview.classList.remove("hidden")
        uploadArea.classList.add("hidden")
      }
    }
  }
  reader.readAsDataURL(file)
  updateGenerateButton()
}

function setupImageValidation() {
  // Configurar validación automática en inputs de archivo
  const garmentInput = document.getElementById("garmentImageInput")
  const personInput = document.getElementById("personImageInput")

  if (garmentInput) {
    garmentInput.addEventListener("change", handleGarmentUpload)
  }

  if (personInput) {
    personInput.addEventListener("change", handlePersonUpload)
  }
}

// Hacer funciones disponibles globalmente
window.virtualTryOn = {
  switchTab,
  handleGarmentUpload,
  handlePersonUpload,
  removeGarmentImage,
  removePersonImage,
  toggleTokenVisibility,
  generateTryOn,
  downloadResult,
  showError,
  hideError,
  updateGenerateButton,
}

// Compatibilidad con funciones globales
window.switchTab = switchTab
window.handleGarmentUpload = handleGarmentUpload
window.handlePersonUpload = handlePersonUpload
window.removeGarmentImage = removeGarmentImage
window.removePersonImage = removePersonImage
window.toggleTokenVisibility = toggleTokenVisibility
window.generateTryOn = generateTryOn
window.downloadResult = downloadResult