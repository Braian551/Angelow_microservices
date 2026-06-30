<template>
  <!-- Teleport transporta el modal al body del DOM para evitar problemas de z-index y overflow -->
  <Teleport to="body">
    <!-- Fondo oscuro del modal, cierra el modal al hacer clic fuera -->
    <div v-if="modelValue" class="gps-modal-overlay" @click="closeModal" />

    <!-- Contenedor principal del modal, cambia a pantalla completa si isMapFullscreen es true -->
    <div v-if="modelValue" class="gps-modal" :class="{ fullscreen: isMapFullscreen }">
      <!-- Encabezado del modal con título, buscador y botón de ubicación GPS -->
      <header class="gps-modal-header">
        <div class="gps-header-top">
          <!-- Título del modal -->
          <h3 class="gps-modal-title">
            <i class="fas fa-map-marked-alt" />
            Selecciona tu ubicacion
          </h3>

          <!-- Botón para cerrar el modal -->
          <button type="button" class="btn-close-gps" aria-label="Cerrar" @click="closeModal">
            <i class="fas fa-times" />
          </button>
        </div>

        <!-- Contenedor del buscador de direcciones -->
        <div class="address-search-container">
          <div class="address-search-wrapper">
            <i class="fas fa-search search-icon" />
            <!-- Campo de entrada para buscar direcciones, lugares o barrios -->
            <input
              v-model.trim="searchQuery"
              class="address-search-input"
              type="text"
              placeholder="Buscar direccion, lugar o barrio..."
              @input="onSearchInput"
              @keydown.enter.prevent="searchAddress"
            />

            <!-- Panel de resultados de búsqueda que se muestra cuando hay actividad de búsqueda -->
            <div v-if="showSearchResults" class="search-results">
              <!-- Indicador de carga mientras se busca -->
              <div v-if="isSearching" class="search-loading">Buscando ubicacion...</div>

              <!-- Lista de resultados de búsqueda cuando no está cargando -->
              <template v-else>
                <div
                  v-for="result in searchResults"
                  :key="`${result.lat}-${result.lon}-${result.display_name}`"
                  class="search-result-item"
                  @click="selectSearchResult(result)"
                >
                  <!-- Nombre corto del resultado de búsqueda -->
                  <div class="search-result-name">{{ result.name }}</div>
                  <!-- Dirección completa del resultado -->
                  <div class="search-result-address">{{ result.display_name }}</div>
                </div>

                <!-- Mensaje cuando no se encontraron resultados -->
                <div v-if="searchedNoResults" class="search-no-results">
                  No se encontraron resultados para la busqueda.
                </div>
              </template>
            </div>
          </div>

          <!-- Botón para ejecutar la búsqueda manual -->
          <button type="button" class="btn-search" :disabled="isSearching" @click="searchAddress">
            <i class="fas fa-search" />
            Buscar
          </button>
        </div>

        <!-- Botón para usar la ubicación GPS actual del dispositivo -->
        <button type="button" class="btn-use-location" @click="locateCurrentPosition">
          <i class="fas fa-location-crosshairs" />
          Usar mi ubicacion GPS
        </button>
      </header>

      <!-- Contenedor principal del mapa -->
      <div class="gps-map-container">
        <!-- Elemento DOM donde Leaflet renderiza el mapa -->
        <div ref="mapElementRef" id="gps-map" />

        <!-- Overlay de carga mientras se obtiene la ubicación -->
        <div v-if="mapLoading" class="map-loading">
          <div class="loading-spinner" />
          <p class="loading-text">Obteniendo tu ubicacion...</p>
        </div>

        <!-- Instrucciones flotantes en el mapa, se ocultan al hacer clic -->
        <div class="map-instructions" :class="{ hidden: hideMapInstructions }" @click="hideMapInstructions = true">
          <span>
            Haz clic en el mapa o mueve el marcador para ajustar la ubicacion.
          </span>
        </div>

        <!-- Acciones flotantes sobre el mapa -->
        <div class="floating-actions">
          <!-- Botón para centrar el mapa en la ubicación actual del usuario -->
          <button type="button" title="Mi ubicacion" @click="locateCurrentPosition">
            <i class="fas fa-crosshairs" />
          </button>

          <!-- Botón para colapsar o expandir el panel de información -->
          <button type="button" title="Mostrar/Ocultar panel" @click="isPanelCollapsed = !isPanelCollapsed">
            <i :class="isPanelCollapsed ? 'fas fa-chevron-up' : 'fas fa-chevron-down'" />
          </button>
        </div>

        <!-- Botón para alternar el mapa a modo pantalla completa -->
        <button type="button" class="btn-fullscreen" title="Pantalla completa" @click="toggleMapFullscreen">
          <i :class="isMapFullscreen ? 'fas fa-compress' : 'fas fa-expand'" />
        </button>
      </div>

      <!-- Panel de información de la dirección seleccionada, se puede colapsar -->
      <div class="address-info-panel" :class="{ collapsed: isPanelCollapsed }">
        <!-- Etiqueta del panel de dirección -->
        <div class="address-preview-label">
          <i class="fas fa-map-marker-alt" />
          Direccion seleccionada
        </div>

        <!-- Texto de la dirección seleccionada actualmente -->
        <div class="address-preview-text">
          {{ selectedAddressText }}
        </div>

        <!-- Coordenadas geográficas de la ubicación seleccionada -->
        <div class="coordinates-display">
          <div class="coord-item">
            <span class="coord-label">Latitud</span>
            <span class="coord-value">{{ selectedLatitude }}</span>
          </div>

          <div class="coord-item">
            <span class="coord-label">Longitud</span>
            <span class="coord-value">{{ selectedLongitude }}</span>
          </div>
        </div>
      </div>

      <!-- Acciones del modal: cancelar o confirmar ubicación -->
      <div class="gps-modal-actions">
        <!-- Botón para cancelar y cerrar el modal sin guardar cambios -->
        <button type="button" class="btn-gps-action btn-cancel-gps" @click="closeModal">
          <i class="fas fa-times" />
          Cancelar
        </button>

        <!-- Botón para confirmar la ubicación seleccionada, solo habilitado si hay coordenadas válidas -->
        <button type="button" class="btn-gps-action btn-confirm-location" :disabled="!canConfirmGps" @click="confirmLocation">
          <i class="fas fa-check-circle" />
          Confirmar ubicacion
        </button>
      </div>
    </div>
  </Teleport>
</template>

<script setup>
// Importaciones de Vue Composition API para reactividad y ciclo de vida
import { computed, nextTick, onBeforeUnmount, ref, watch } from 'vue'
// Composable personalizado para mostrar notificaciones (snackbar)
import { useSnackbarSystem } from '../../../composables/useSnackbarSystem'

// URLs de respaldo para la hoja de estilos de Leaflet (se usa la primera que cargue correctamente)
const LEAFLET_CSS_URLS = [
  'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css',
  'https://cdn.jsdelivr.net/npm/leaflet@1.9.4/dist/leaflet.css',
]

// URLs de respaldo para el script JavaScript de Leaflet
const LEAFLET_JS_URLS = [
  'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js',
  'https://cdn.jsdelivr.net/npm/leaflet@1.9.4/dist/leaflet.js',
]

// Proveedores de teselas (tiles) del mapa: OpenStreetMap y CARTO como respaldo
const TILE_PROVIDERS = [
  {
    key: 'osm',
    url: 'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',
    options: { maxZoom: 19, attribution: '&copy; OpenStreetMap contributors' },
  },
  {
    key: 'carto',
    url: 'https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png',
    options: {
      maxZoom: 20,
      subdomains: 'abcd',
      attribution: '&copy; OpenStreetMap contributors &copy; CARTO',
    },
  },
]
// Centro por defecto del mapa (Medellín, Colombia) congelado para evitar modificaciones accidentales
const DEFAULT_CENTER = Object.freeze({ lat: 6.25184, lng: -75.56359 })

// Definición de las propiedades recibidas del componente padre
const props = defineProps({
  // Controla la visibilidad del modal (v-model)
  modelValue: {
    type: Boolean,
    default: false,
  },
  // Dirección inicial precargada desde el componente padre
  initialAddress: {
    type: String,
    default: '',
  },
  // Barrio o colonia inicial precargada
  initialNeighborhood: {
    type: String,
    default: '',
  },
  // Dirección GPS inicial precargada
  initialGpsAddress: {
    type: String,
    default: '',
  },
  // Latitud inicial en formato número, string o null
  initialLatitude: {
    type: [Number, String, null],
    default: null,
  },
  // Longitud inicial en formato número, string o null
  initialLongitude: {
    type: [Number, String, null],
    default: null,
  },
})

// Eventos que este componente puede emitir al padre
const emit = defineEmits(['update:modelValue', 'confirm'])

// Extraer la función para mostrar notificaciones del composable
const { showSnackbar } = useSnackbarSystem()

// Referencia al elemento DOM del mapa para Leaflet
const mapElementRef = ref(null)
// Estado de carga del mapa
const mapLoading = ref(true)
// Indica si el mapa está en modo pantalla completa
const isMapFullscreen = ref(false)
// Indica si el panel de información está colapsado
const isPanelCollapsed = ref(false)
// Controla la visibilidad de las instrucciones del mapa
const hideMapInstructions = ref(false)

// Texto de búsqueda ingresado por el usuario
const searchQuery = ref('')
// Indica si se está realizando una búsqueda
const isSearching = ref(false)
// Indica si la búsqueda no arrojó resultados
const searchedNoResults = ref(false)
// Lista de resultados de la búsqueda de direcciones
const searchResults = ref([])

// Instancia del mapa Leaflet
const mapInstance = ref(null)
// Marcador (pin) en el mapa
const mapMarker = ref(null)
// Capa de teselas actual del mapa (OpenStreetMap o CARTO)
const mapTileLayer = ref(null)
// Token para controlar solicitudes de geocodificación inversa obsoletas
const reverseRequestToken = ref(0)
// Token para controlar solicitudes de búsqueda obsoletas
const searchRequestToken = ref(0)

// Coordenadas GPS actuales seleccionadas por el usuario
const currentLatitude = ref(null)
const currentLongitude = ref(null)
// Precisión del GPS en metros
const currentAccuracy = ref(null)
// Dirección obtenida por geocodificación inversa
const currentGpsAddress = ref('')
// Dirección del campo de formulario
const currentAddress = ref('')
// Barrio o colonia extraído de la dirección
const currentNeighborhood = ref('')
// Temporizador para el debounce de la búsqueda (evita demasiadas peticiones)
let searchDebounceTimer = null

// Indica si hay coordenadas GPS válidas seleccionadas
const hasGpsCoordinates = computed(() => isValidCoordinatePair(currentLatitude.value, currentLongitude.value))

// Texto de la dirección seleccionada para mostrar en el panel
const selectedAddressText = computed(() => {
  if (String(currentGpsAddress.value || '').trim()) {
    return String(currentGpsAddress.value).trim()
  }

  return 'Mueve el marcador para ver la direccion...'
})

// Latitud formateada con 6 decimales para mostrar en el panel
const selectedLatitude = computed(() => {
  const value = toNullableNumber(currentLatitude.value)
  return Number.isFinite(value) ? value.toFixed(6) : '--'
})

// Longitud formateada con 6 decimales para mostrar en el panel
const selectedLongitude = computed(() => {
  const value = toNullableNumber(currentLongitude.value)
  return Number.isFinite(value) ? value.toFixed(6) : '--'
})

// El botón de confirmar solo se habilita si hay coordenadas GPS válidas
const canConfirmGps = computed(() => hasGpsCoordinates.value)
// Muestra el panel de resultados de búsqueda si hay actividad de búsqueda o resultados
const showSearchResults = computed(() => isSearching.value || searchResults.value.length > 0 || searchedNoResults.value)

// Watcher que reacciona a cambios en la visibilidad del modal
// Cuando se abre: resetea estado, hidrata datos iniciales y configura el mapa
// Cuando se cierra: destruye el mapa para liberar recursos
watch(
  () => props.modelValue,
  async (isOpen) => {
    if (!isOpen) {
      destroyMap()
      return
    }

    resetModalState()
    hydrateFromProps()

    await nextTick()
    await setupMap()
  },
)

// Limpieza antes de que el componente se desmonte del DOM
onBeforeUnmount(() => {
  // Cancelar el temporizador de debounce pendiente para evitar fugas de memoria
  if (searchDebounceTimer) {
    clearTimeout(searchDebounceTimer)
    searchDebounceTimer = null
  }

  destroyMap()
})

// Restablece todos los estados del modal a sus valores por defecto
function resetModalState() {
  isMapFullscreen.value = false
  isPanelCollapsed.value = false
  hideMapInstructions.value = false
  mapLoading.value = true
  searchedNoResults.value = false
  searchResults.value = []
  searchQuery.value = ''
}

// Carga los valores iniciales de las propiedades del componente padre
// Esto permite pre-llenar el formulario si el usuario ya tiene datos de ubicación
function hydrateFromProps() {
  currentAddress.value = String(props.initialAddress || '')
  currentNeighborhood.value = String(props.initialNeighborhood || '')
  currentGpsAddress.value = String(props.initialGpsAddress || props.initialAddress || '')
  currentLatitude.value = toNullableNumber(props.initialLatitude)
  currentLongitude.value = toNullableNumber(props.initialLongitude)
  currentAccuracy.value = null
}

// Cierra el modal emitiendo el evento update:modelValue con false
function closeModal() {
  emit('update:modelValue', false)
}

// Configura el mapa Leaflet: carga la librería, crea el mapa, agrega capas y marcador
async function setupMap() {
  // Cargar la biblioteca Leaflet dinámicamente
  const leaflet = await loadLeaflet()

  // Si Leaflet no se pudo cargar o el elemento DOM no existe, mostrar error
  if (!leaflet || !mapElementRef.value) {
    mapLoading.value = false
    showSnackbar({
      type: 'error',
      title: 'Mapa no disponible',
      message: 'No fue posible cargar el mapa de ubicacion.',
    })
    return
  }

  // Crear instancia del mapa con controles de zoom y atribución
  mapInstance.value = leaflet.map(mapElementRef.value, {
    zoomControl: true,
    attributionControl: true,
  }).setView([DEFAULT_CENTER.lat, DEFAULT_CENTER.lng], 13)

  // Agregar capa de teselas con sistema de respaldo automático
  mapTileLayer.value = attachTileLayerWithFallback(leaflet)

  // Crear marcador arrastrable en el centro por defecto
  mapMarker.value = leaflet.marker([DEFAULT_CENTER.lat, DEFAULT_CENTER.lng], {
    draggable: true,
  }).addTo(mapInstance.value)

  // Evento al soltar el marcador: actualizar ubicación con las nuevas coordenadas
  mapMarker.value.on('dragend', () => {
    const point = mapMarker.value.getLatLng()
    updateLocation(point.lat, point.lng, null)
  })

  // Evento al hacer clic en el mapa: mover el marcador a esa posición
  mapInstance.value.on('click', (event) => {
    updateLocation(event.latlng.lat, event.latlng.lng, null)
  })

  // Forzar recálculo del tamaño del mapa después de un breve retraso
  // para evitar problemas de renderizado cuando el modal se está abriendo
  setTimeout(() => {
    mapInstance.value?.invalidateSize()
  }, 120)

  // Si hay coordenadas iniciales válidas, centrar el mapa en esas coordenadas
  const presetLatitude = toNullableNumber(currentLatitude.value)
  const presetLongitude = toNullableNumber(currentLongitude.value)

  if (isValidCoordinatePair(presetLatitude, presetLongitude)) {
    mapInstance.value.setView([presetLatitude, presetLongitude], 16)
    mapMarker.value.setLatLng([presetLatitude, presetLongitude])
    mapLoading.value = false
    return
  }

  // Si no hay coordenadas iniciales, intentar obtener la ubicación GPS del usuario
  await locateCurrentPosition()
}

// Obtiene la ubicación GPS actual del dispositivo y centra el mapa en ella
async function locateCurrentPosition() {
  mapLoading.value = true

  try {
    // Solicitar permiso y obtener posición del navegador
    const position = await getCurrentPosition()
    const latitude = Number(position.coords.latitude)
    const longitude = Number(position.coords.longitude)
    const accuracy = Number(position.coords.accuracy)

    // Centrar el mapa y mover el marcador a la ubicación obtenida
    mapInstance.value?.setView([latitude, longitude], 16)
    mapMarker.value?.setLatLng([latitude, longitude])

    // Actualizar estado interno con geocodificación inversa
    await updateLocation(latitude, longitude, accuracy)

    showSnackbar({
      type: 'success',
      title: 'Ubicacion detectada',
      message: 'Se detecto tu ubicacion actual correctamente.',
      durationMs: 2200,
    })
  } catch {
    // Si falla la obtención de GPS, usar el centro por defecto (Medellín)
    mapInstance.value?.setView([DEFAULT_CENTER.lat, DEFAULT_CENTER.lng], 13)
    mapMarker.value?.setLatLng([DEFAULT_CENTER.lat, DEFAULT_CENTER.lng])
    await updateLocation(DEFAULT_CENTER.lat, DEFAULT_CENTER.lng, null)

    showSnackbar({
      type: 'warning',
      title: 'Sin GPS exacto',
      message: 'No se pudo obtener tu ubicacion exacta. Puedes mover el marcador manualmente.',
    })
  } finally {
    mapLoading.value = false
  }
}

// Actualiza la ubicación interna: guarda coordenadas, realiza geocodificación inversa
// y extrae la dirección y barrio de la respuesta del servicio de geocoding
async function updateLocation(latitude, longitude, accuracy) {
  // Guardar coordenadas con 6 decimales de precisión
  currentLatitude.value = Number(latitude.toFixed(6))
  currentLongitude.value = Number(longitude.toFixed(6))
  // Guardar precisión del GPS en metros (si está disponible)
  currentAccuracy.value = Number.isFinite(accuracy) ? Number(accuracy.toFixed(2)) : null

  // Realizar geocodificación inversa para obtener la dirección legible
  const reverseData = await reverseGeocode(latitude, longitude)

  // Si se obtuvo una dirección válida, actualizar los campos
  if (reverseData?.display_name) {
    currentGpsAddress.value = String(reverseData.display_name)

    // Solo actualizar la dirección del formulario si está vacía
    if (!String(currentAddress.value || '').trim()) {
      currentAddress.value = String(reverseData.display_name)
    }

    // Extraer y asignar el barrio si no tiene uno precargado
    const suggestedNeighborhood = extractNeighborhood(reverseData)
    if (suggestedNeighborhood && !String(currentNeighborhood.value || '').trim()) {
      currentNeighborhood.value = suggestedNeighborhood
    }
  }
}

// Realiza geocodificación inversa: convierte coordenadas en dirección legible
// Usa el servicio Nominatim de OpenStreetMap
async function reverseGeocode(latitude, longitude) {
  // Token para descartar respuestas obsoletas de solicitudes anteriores
  const requestToken = Date.now()
  reverseRequestToken.value = requestToken

  try {
    const response = await fetch(
      `https://nominatim.openstreetmap.org/reverse?format=jsonv2&addressdetails=1&accept-language=es&lat=${encodeURIComponent(String(latitude))}&lon=${encodeURIComponent(String(longitude))}`,
    )

    if (!response.ok) {
      return null
    }

    const payload = await response.json()

    // Verificar que esta respuesta corresponda a la solicitud más reciente
    if (reverseRequestToken.value !== requestToken) {
      return null
    }

    return payload
  } catch {
    return null
  }
}

// Función de búsqueda manual (botón): busca y muestra advertencia si el texto es muy corto
async function searchAddress() {
  return searchAddressWithOptions({ showWarning: true })
}

// Maneja el evento de entrada en el campo de búsqueda con debounce
// Esto evita hacer una petición por cada tecla presionada
function onSearchInput() {
  if (searchDebounceTimer) {
    clearTimeout(searchDebounceTimer)
  }

  const query = searchQuery.value.trim()
  // Si la consulta tiene menos de 3 caracteres, limpiar resultados
  if (query.length < 3) {
    isSearching.value = false
    searchedNoResults.value = false
    searchResults.value = []
    return
  }

  // Esperar 280ms después de la última tecla antes de buscar (debounce)
  searchDebounceTimer = setTimeout(() => {
    searchAddressWithOptions({ showWarning: false })
  }, 280)
}

// Función principal de búsqueda con opciones configurables
// Intenta múltiples proveedores de geocoding en orden de preferencia
async function searchAddressWithOptions(options = {}) {
  const { showWarning = false } = options
  // Normalizar la consulta: quitar tildes, espacios múltiples y ajustar
  const normalizedQuery = sanitizeSearchQuery(searchQuery.value)

  // Si la consulta normalizada tiene menos de 3 caracteres, no buscar
  if (normalizedQuery.length < 3) {
    searchedNoResults.value = false
    searchResults.value = []

    if (showWarning) {
      showSnackbar({
        type: 'warning',
        title: 'Busqueda incompleta',
        message: 'Escribe al menos 3 caracteres para buscar.',
      })
    }

    return
  }

  // Token para descartar búsquedas obsoletas
  const requestToken = Date.now()
  searchRequestToken.value = requestToken

  isSearching.value = true
  searchedNoResults.value = false

  try {
    let normalizedResults = []
    let hadSuccessfulProvider = false

    // Cadena de proveedores en orden de preferencia:
    // 1. Nominatim filtrado por Colombia
    // 2. Nominatim global
    // 3. Photon (respaldo alternativo)
    const providers = [
      () => searchWithNominatim(normalizedQuery, { countryCode: 'co' }),
      () => searchWithNominatim(normalizedQuery),
      () => searchWithPhoton(normalizedQuery),
    ]

    // Intentar cada proveedor en secuencia hasta obtener resultados
    for (const provider of providers) {
      try {
        const providerResults = await provider()
        hadSuccessfulProvider = true

        // Si el proveedor devolvió resultados, usarlos y detener la búsqueda
        if (providerResults.length > 0) {
          normalizedResults = providerResults
          break
        }
      } catch {
        // Continuar con el siguiente proveedor sin interrumpir la búsqueda.
      }
    }

    // Verificar que esta respuesta corresponda a la búsqueda más reciente
    if (searchRequestToken.value !== requestToken) {
      return
    }

    searchResults.value = normalizedResults
    searchedNoResults.value = normalizedResults.length === 0

    // Si ningún proveedor funcionó, lanzar error
    if (!hadSuccessfulProvider) {
      throw new Error('all_search_providers_failed')
    }
  } catch {
    // Verificar que esta respuesta corresponda a la búsqueda más reciente
    if (searchRequestToken.value !== requestToken) {
      return
    }

    searchedNoResults.value = true
    searchResults.value = []

    showSnackbar({
      type: 'error',
      title: 'Busqueda no disponible',
      message: 'No fue posible realizar la busqueda en este momento.',
    })
  } finally {
    // Solo ocultar el indicador de carga si esta es la búsqueda más reciente
    if (searchRequestToken.value === requestToken) {
      isSearching.value = false
    }
  }
}

// Busca direcciones usando Nominatim (OpenStreetMap) con parámetros configurables
async function searchWithNominatim(query, options = {}) {
  // Construir parámetros de búsqueda para Nominatim
  const params = new URLSearchParams({
    format: 'jsonv2',
    addressdetails: '1',
    'accept-language': 'es',
    limit: '8',
    q: query,
  })

  // Filtrar por país si se especificó (ej: 'co' para Colombia)
  if (options.countryCode) {
    params.set('countrycodes', String(options.countryCode).toLowerCase())
  }

  const endpoint = `https://nominatim.openstreetmap.org/search?${params.toString()}`
  const response = await fetch(endpoint)

  if (!response.ok) {
    throw new Error('nominatim_search_failed')
  }

  const payload = await response.json()
  return normalizeSearchResults(payload)
}

// Busca direcciones usando Photon (servicio de geocoding basado en OpenStreetMap)
async function searchWithPhoton(query) {
  const endpoint = `https://photon.komoot.io/api/?lang=es&limit=8&q=${encodeURIComponent(query)}`
  const response = await fetch(endpoint)

  if (!response.ok) {
    throw new Error('photon_search_failed')
  }

  const payload = await response.json()
  const features = Array.isArray(payload?.features) ? payload.features : []

  // Transformar los resultados de GeoJSON a formato estándar del componente
  return features
    .map((feature) => {
      // Extraer coordenadas del GeoJSON (formato: [longitude, latitude])
      const coordinates = Array.isArray(feature?.geometry?.coordinates)
        ? feature.geometry.coordinates
        : []

      const longitude = Number(coordinates[0])
      const latitude = Number(coordinates[1])
      // Validar que las coordenadas sean números finitos
      if (!Number.isFinite(latitude) || !Number.isFinite(longitude)) {
        return null
      }

      // Extraer nombre del lugar de las propiedades del feature
      const properties = feature?.properties || {}
      const name = String(
        properties?.name
          || properties?.street
          || properties?.district
          || properties?.city
          || 'Resultado',
      ).trim()

      // Construir la dirección completa a partir de las partes disponibles
      const addressParts = [
        properties?.street,
        properties?.district,
        properties?.city,
        properties?.state,
        properties?.country,
      ].filter(Boolean)

      const displayName = String(addressParts.join(', ') || name).trim()

      return {
        lat: String(latitude),
        lon: String(longitude),
        name,
        display_name: displayName,
      }
    })
    .filter(Boolean)
}

// Normaliza la consulta de búsqueda: quita tildes, espacios múltiples y ajusta
// Esto mejora la búsqueda ya que los servicios de geocoding a veces no manejan bien las tildes
function sanitizeSearchQuery(rawQuery) {
  return String(rawQuery || '')
    .normalize('NFD') // Descomponer caracteres acentuados
    .replace(/[\u0300-\u036f]/g, '') // Eliminar marcas diacríticas (tildes)
    .replace(/\s+/g, ' ') // Reemplazar múltiples espacios por uno solo
    .trim()
}

// Transforma los resultados crudos de la API a un formato estándar del componente
function normalizeSearchResults(payload) {
  const items = Array.isArray(payload) ? payload : []

  return items
    .map((item) => {
      const latitude = Number(item?.lat)
      const longitude = Number(item?.lon)
      // Validar que las coordenadas sean números finitos
      if (!Number.isFinite(latitude) || !Number.isFinite(longitude)) {
        return null
      }

      return {
        lat: String(latitude),
        lon: String(longitude),
        name: String(item?.name || item?.display_name || 'Resultado').trim(),
        display_name: String(item?.display_name || item?.name || '').trim(),
      }
    })
    .filter(Boolean)
}

// Selecciona un resultado de búsqueda: centra el mapa y actualiza la ubicación
async function selectSearchResult(result) {
  // Extraer y validar coordenadas del resultado seleccionado
  const latitude = Number(result?.lat)
  const longitude = Number(result?.lon)

  if (!Number.isFinite(latitude) || !Number.isFinite(longitude)) {
    return
  }

  // Limpiar la lista de resultados de búsqueda
  searchResults.value = []
  searchedNoResults.value = false

  // Centrar el mapa en la ubicación seleccionada con zoom alto
  mapInstance.value?.setView([latitude, longitude], 17)
  mapMarker.value?.setLatLng([latitude, longitude])

  // Asignar la dirección del resultado seleccionado
  currentGpsAddress.value = String(result?.display_name || '')
  await updateLocation(latitude, longitude, null)
}

// Confirma la ubicación seleccionada y emite los datos al componente padre
function confirmLocation() {
  // Validar que haya coordenadas antes de confirmar
  if (!canConfirmGps.value) {
    showSnackbar({
      type: 'warning',
      title: 'Ubicacion incompleta',
      message: 'Selecciona primero una ubicacion valida en el mapa.',
    })
    return
  }

  // Emitir evento con todos los datos de la ubicación seleccionada
  emit('confirm', {
    gps_latitude: toNullableNumber(currentLatitude.value),
    gps_longitude: toNullableNumber(currentLongitude.value),
    gps_accuracy: toNullableNumber(currentAccuracy.value),
    gps_address: String(currentGpsAddress.value || ''),
    suggested_address: String(currentGpsAddress.value || currentAddress.value || ''),
    suggested_neighborhood: String(currentNeighborhood.value || ''),
  })

  closeModal()
}

// Alterna el modo pantalla completa del mapa y fuerza el redimensionamiento
function toggleMapFullscreen() {
  isMapFullscreen.value = !isMapFullscreen.value

  // Esperar a que termine la transición CSS antes de redimensionar el mapa
  setTimeout(() => {
    mapInstance.value?.invalidateSize()
  }, 150)
}

// Extrae el barrio o colonia de la respuesta de geocodificación inversa
// Intenta varios campos en orden de prioridad según la estructura de Nominatim
function extractNeighborhood(payload) {
  const address = payload?.address || {}
  // Buscar el barrio en múltiples campos posibles de la dirección
  return String(
    address?.suburb
      || address?.neighbourhood
      || address?.city_district
      || address?.quarter
      || address?.village
      || address?.town
      || address?.city
      || '',
  ).trim()
}

// Carga la biblioteca Leaflet de forma dinámica con sistema de respaldo
async function loadLeaflet() {
  // Verificar que estamos en el navegador (no en SSR)
  if (typeof window === 'undefined') return null

  // Si Leaflet ya está cargado, devolver la instancia existente
  if (window.L) {
    return window.L
  }

  // Cargar estilos CSS y script JavaScript de Leaflet con fallback
  await loadLeafletStylesheetWithFallback()
  await loadLeafletScriptWithFallback()

  return window.L || null
}

// Agrega la capa de teselas al mapa con sistema de respaldo automático
// Si un proveedor falla, cambia al siguiente automáticamente
function attachTileLayerWithFallback(leaflet) {
  // Índice del proveedor de teselas actual (0 = OpenStreetMap, 1 = CARTO)
  let providerIndex = 0
  // Indica si ya se realizó un cambio de proveedor (para mostrar snackbar solo una vez)
  let switchedProvider = false

  // Función para crear una capa de teselas con el proveedor actual
  const createLayer = () => leaflet.tileLayer(
    TILE_PROVIDERS[providerIndex].url,
    TILE_PROVIDERS[providerIndex].options,
  )

  // Registra manejador de errores para cambiar de proveedor automáticamente
  const registerLayerErrorHandler = (layer) => {
    // Escuchar errores de carga de teselas
    layer.on('tileerror', () => {
      // Si ya se agotaron todos los proveedores, no hacer nada
      if (providerIndex >= TILE_PROVIDERS.length - 1) {
        return
      }

      // Avanzar al siguiente proveedor
      providerIndex += 1

      // Mostrar notificación solo en el primer cambio de proveedor
      if (!switchedProvider) {
        switchedProvider = true
        showSnackbar({
          type: 'warning',
          title: 'Cambiando proveedor de mapa',
          message: 'El proveedor principal no respondio. Se aplico un respaldo automaticamente.',
          durationMs: 2600,
        })
      }

      // Remover la capa fallida y crear una nueva con el siguiente proveedor
      mapInstance.value?.removeLayer(layer)
      const nextLayer = createLayer()
      registerLayerErrorHandler(nextLayer)
      nextLayer.addTo(mapInstance.value)
      mapTileLayer.value = nextLayer
    })
  }

  // Crear la primera capa y registrar el manejador de errores
  const layer = createLayer()
  registerLayerErrorHandler(layer)
  layer.addTo(mapInstance.value)

  return layer
}

// Carga la hoja de estilos de Leaflet con fallback entre múltiples CDN
async function loadLeafletStylesheetWithFallback() {
  let lastError = null

  // Intentar cada URL en orden hasta que una cargue correctamente
  for (const [index, href] of LEAFLET_CSS_URLS.entries()) {
    const id = `leaflet-css-${index}`

    try {
      await ensureStylesheet(href, id)
      return
    } catch (error) {
      lastError = error
    }
  }

  throw lastError || new Error('stylesheet_load_failed')
}

// Carga el script JavaScript de Leaflet con fallback entre múltiples CDN
async function loadLeafletScriptWithFallback() {
  let lastError = null

  for (const [index, src] of LEAFLET_JS_URLS.entries()) {
    const id = `leaflet-js-${index}`

    try {
      await ensureScript(src, id)
      return
    } catch (error) {
      lastError = error
    }
  }

  throw lastError || new Error('script_load_failed')
}

// Asegura que una hoja de estilos esté cargada en el DOM
// Si ya existe (mismo id), no la vuelve a cargar
function ensureStylesheet(href, id) {
  // Si la hoja de estilos ya está en el DOM, devolver promesa resuelta
  if (document.getElementById(id)) {
    return Promise.resolve()
  }

  // Crear elemento link y agregarlo al head del documento
  return new Promise((resolve, reject) => {
    const link = document.createElement('link')
    link.id = id
    link.rel = 'stylesheet'
    link.href = href
    link.onload = () => resolve()
    link.onerror = () => reject(new Error('stylesheet_load_failed'))
    document.head.appendChild(link)
  })
}

// Asegura que un script esté cargado en el DOM
// Si ya existe (mismo id), no lo vuelve a cargar
function ensureScript(src, id) {
  // Si el script ya está en el DOM, devolver promesa resuelta
  if (document.getElementById(id)) {
    return Promise.resolve()
  }

  // Crear elemento script y agregarlo al head del documento
  return new Promise((resolve, reject) => {
    const script = document.createElement('script')
    script.id = id
    script.src = src
    script.async = true
    script.onload = () => resolve()
    script.onerror = () => reject(new Error('script_load_failed'))
    document.head.appendChild(script)
  })
}

// Envuelve la API de geolocalización del navegador en una Promesa
// para poder usar async/await
function getCurrentPosition() {
  return new Promise((resolve, reject) => {
    // Verificar que el navegador soporte geolocalización
    if (!navigator.geolocation) {
      reject(new Error('not_supported'))
      return
    }

    // Solicitar posición con alta precisión, timeout de 12 segundos y sin caché
    navigator.geolocation.getCurrentPosition(resolve, reject, {
      enableHighAccuracy: true,
      timeout: 12000,
      maximumAge: 0,
    })
  })
}

// Destruye la instancia del mapa y libera todos los recursos asociados
function destroyMap() {
  // Remover la instancia del mapa del DOM y liberar memoria
  if (mapInstance.value) {
    mapInstance.value.remove()
    mapInstance.value = null
  }

  mapTileLayer.value = null
  mapMarker.value = null
}

// Convierte un valor a número o null si no es válido
// Útil para manejar props que pueden ser string, número o null
function toNullableNumber(value) {
  if (value === null || value === undefined) {
    return null
  }

  const normalized = String(value).trim()
  if (!normalized) {
    return null
  }

  // Intentar convertir a número y verificar que sea finito
  const parsed = Number(normalized)
  return Number.isFinite(parsed) ? parsed : null
}

// Valida que un par de coordenadas geográficas sea válido
// Verifica que estén en rango y que no sean (0,0) exacto
function isValidCoordinatePair(latitude, longitude) {
  const lat = toNullableNumber(latitude)
  const lng = toNullableNumber(longitude)

  // Verificar que ambos valores sean números finitos
  if (!Number.isFinite(lat) || !Number.isFinite(lng)) {
    return false
  }

  // Verificar que estén dentro de los rangos válidos de coordenadas
  const isInRange = lat >= -90 && lat <= 90 && lng >= -180 && lng <= 180
  // Descartar la coordenada (0,0) ya que es un punto inválido en la práctica
  const isZeroed = Math.abs(lat) < 0.000001 && Math.abs(lng) < 0.000001
  return isInRange && !isZeroed
}
</script>
