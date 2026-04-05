<template>
  <Teleport to="body">
    <div v-if="modelValue" class="gps-modal-overlay" @click="closeModal" />

    <div v-if="modelValue" class="gps-modal" :class="{ fullscreen: isMapFullscreen }">
      <header class="gps-modal-header">
        <div class="gps-header-top">
          <h3 class="gps-modal-title">
            <i class="fas fa-map-marked-alt" />
            Selecciona tu ubicacion
          </h3>

          <button type="button" class="btn-close-gps" aria-label="Cerrar" @click="closeModal">
            <i class="fas fa-times" />
          </button>
        </div>

        <div class="address-search-container">
          <div class="address-search-wrapper">
            <i class="fas fa-search search-icon" />
            <input
              v-model.trim="searchQuery"
              class="address-search-input"
              type="text"
              placeholder="Buscar direccion, lugar o barrio..."
              @input="onSearchInput"
              @keydown.enter.prevent="searchAddress"
            />

            <div v-if="showSearchResults" class="search-results">
              <div v-if="isSearching" class="search-loading">Buscando ubicacion...</div>

              <template v-else>
                <div
                  v-for="result in searchResults"
                  :key="`${result.lat}-${result.lon}-${result.display_name}`"
                  class="search-result-item"
                  @click="selectSearchResult(result)"
                >
                  <div class="search-result-name">{{ result.name }}</div>
                  <div class="search-result-address">{{ result.display_name }}</div>
                </div>

                <div v-if="searchedNoResults" class="search-no-results">
                  No se encontraron resultados para la busqueda.
                </div>
              </template>
            </div>
          </div>

          <button type="button" class="btn-search" :disabled="isSearching" @click="searchAddress">
            <i class="fas fa-search" />
            Buscar
          </button>
        </div>

        <button type="button" class="btn-use-location" @click="locateCurrentPosition">
          <i class="fas fa-location-crosshairs" />
          Usar mi ubicacion GPS
        </button>
      </header>

      <div class="gps-map-container">
        <div ref="mapElementRef" id="gps-map" />

        <div v-if="mapLoading" class="map-loading">
          <div class="loading-spinner" />
          <p class="loading-text">Obteniendo tu ubicacion...</p>
        </div>

        <div class="map-instructions" :class="{ hidden: hideMapInstructions }" @click="hideMapInstructions = true">
          <span>
            Haz clic en el mapa o mueve el marcador para ajustar la ubicacion.
          </span>
        </div>

        <div class="floating-actions">
          <button type="button" title="Mi ubicacion" @click="locateCurrentPosition">
            <i class="fas fa-crosshairs" />
          </button>

          <button type="button" title="Mostrar/Ocultar panel" @click="isPanelCollapsed = !isPanelCollapsed">
            <i :class="isPanelCollapsed ? 'fas fa-chevron-up' : 'fas fa-chevron-down'" />
          </button>
        </div>

        <button type="button" class="btn-fullscreen" title="Pantalla completa" @click="toggleMapFullscreen">
          <i :class="isMapFullscreen ? 'fas fa-compress' : 'fas fa-expand'" />
        </button>
      </div>

      <div class="address-info-panel" :class="{ collapsed: isPanelCollapsed }">
        <div class="address-preview-label">
          <i class="fas fa-map-marker-alt" />
          Direccion seleccionada
        </div>

        <div class="address-preview-text">
          {{ selectedAddressText }}
        </div>

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

      <div class="gps-modal-actions">
        <button type="button" class="btn-gps-action btn-cancel-gps" @click="closeModal">
          <i class="fas fa-times" />
          Cancelar
        </button>

        <button type="button" class="btn-gps-action btn-confirm-location" :disabled="!canConfirmGps" @click="confirmLocation">
          <i class="fas fa-check-circle" />
          Confirmar ubicacion
        </button>
      </div>
    </div>
  </Teleport>
</template>

<script setup>
import { computed, nextTick, onBeforeUnmount, ref, watch } from 'vue'
import { useSnackbarSystem } from '../../../composables/useSnackbarSystem'

const LEAFLET_CSS_URLS = [
  'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css',
  'https://cdn.jsdelivr.net/npm/leaflet@1.9.4/dist/leaflet.css',
]

const LEAFLET_JS_URLS = [
  'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js',
  'https://cdn.jsdelivr.net/npm/leaflet@1.9.4/dist/leaflet.js',
]

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
const DEFAULT_CENTER = Object.freeze({ lat: 6.25184, lng: -75.56359 })

const props = defineProps({
  modelValue: {
    type: Boolean,
    default: false,
  },
  initialAddress: {
    type: String,
    default: '',
  },
  initialNeighborhood: {
    type: String,
    default: '',
  },
  initialGpsAddress: {
    type: String,
    default: '',
  },
  initialLatitude: {
    type: [Number, String, null],
    default: null,
  },
  initialLongitude: {
    type: [Number, String, null],
    default: null,
  },
})

const emit = defineEmits(['update:modelValue', 'confirm'])

const { showSnackbar } = useSnackbarSystem()

const mapElementRef = ref(null)
const mapLoading = ref(true)
const isMapFullscreen = ref(false)
const isPanelCollapsed = ref(false)
const hideMapInstructions = ref(false)

const searchQuery = ref('')
const isSearching = ref(false)
const searchedNoResults = ref(false)
const searchResults = ref([])

const mapInstance = ref(null)
const mapMarker = ref(null)
const mapTileLayer = ref(null)
const reverseRequestToken = ref(0)
const searchRequestToken = ref(0)

const currentLatitude = ref(null)
const currentLongitude = ref(null)
const currentAccuracy = ref(null)
const currentGpsAddress = ref('')
const currentAddress = ref('')
const currentNeighborhood = ref('')
let searchDebounceTimer = null

const hasGpsCoordinates = computed(() => isValidCoordinatePair(currentLatitude.value, currentLongitude.value))

const selectedAddressText = computed(() => {
  if (String(currentGpsAddress.value || '').trim()) {
    return String(currentGpsAddress.value).trim()
  }

  return 'Mueve el marcador para ver la direccion...'
})

const selectedLatitude = computed(() => {
  const value = toNullableNumber(currentLatitude.value)
  return Number.isFinite(value) ? value.toFixed(6) : '--'
})

const selectedLongitude = computed(() => {
  const value = toNullableNumber(currentLongitude.value)
  return Number.isFinite(value) ? value.toFixed(6) : '--'
})

const canConfirmGps = computed(() => hasGpsCoordinates.value)
const showSearchResults = computed(() => isSearching.value || searchResults.value.length > 0 || searchedNoResults.value)

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

onBeforeUnmount(() => {
  if (searchDebounceTimer) {
    clearTimeout(searchDebounceTimer)
    searchDebounceTimer = null
  }

  destroyMap()
})

function resetModalState() {
  isMapFullscreen.value = false
  isPanelCollapsed.value = false
  hideMapInstructions.value = false
  mapLoading.value = true
  searchedNoResults.value = false
  searchResults.value = []
  searchQuery.value = ''
}

function hydrateFromProps() {
  currentAddress.value = String(props.initialAddress || '')
  currentNeighborhood.value = String(props.initialNeighborhood || '')
  currentGpsAddress.value = String(props.initialGpsAddress || props.initialAddress || '')
  currentLatitude.value = toNullableNumber(props.initialLatitude)
  currentLongitude.value = toNullableNumber(props.initialLongitude)
  currentAccuracy.value = null
}

function closeModal() {
  emit('update:modelValue', false)
}

async function setupMap() {
  const leaflet = await loadLeaflet()

  if (!leaflet || !mapElementRef.value) {
    mapLoading.value = false
    showSnackbar({
      type: 'error',
      title: 'Mapa no disponible',
      message: 'No fue posible cargar el mapa de ubicacion.',
    })
    return
  }

  mapInstance.value = leaflet.map(mapElementRef.value, {
    zoomControl: true,
    attributionControl: true,
  }).setView([DEFAULT_CENTER.lat, DEFAULT_CENTER.lng], 13)

  mapTileLayer.value = attachTileLayerWithFallback(leaflet)

  mapMarker.value = leaflet.marker([DEFAULT_CENTER.lat, DEFAULT_CENTER.lng], {
    draggable: true,
  }).addTo(mapInstance.value)

  mapMarker.value.on('dragend', () => {
    const point = mapMarker.value.getLatLng()
    updateLocation(point.lat, point.lng, null)
  })

  mapInstance.value.on('click', (event) => {
    updateLocation(event.latlng.lat, event.latlng.lng, null)
  })

  setTimeout(() => {
    mapInstance.value?.invalidateSize()
  }, 120)

  const presetLatitude = toNullableNumber(currentLatitude.value)
  const presetLongitude = toNullableNumber(currentLongitude.value)

  if (isValidCoordinatePair(presetLatitude, presetLongitude)) {
    mapInstance.value.setView([presetLatitude, presetLongitude], 16)
    mapMarker.value.setLatLng([presetLatitude, presetLongitude])
    mapLoading.value = false
    return
  }

  await locateCurrentPosition()
}

async function locateCurrentPosition() {
  mapLoading.value = true

  try {
    const position = await getCurrentPosition()
    const latitude = Number(position.coords.latitude)
    const longitude = Number(position.coords.longitude)
    const accuracy = Number(position.coords.accuracy)

    mapInstance.value?.setView([latitude, longitude], 16)
    mapMarker.value?.setLatLng([latitude, longitude])

    await updateLocation(latitude, longitude, accuracy)

    showSnackbar({
      type: 'success',
      title: 'Ubicacion detectada',
      message: 'Se detecto tu ubicacion actual correctamente.',
      durationMs: 2200,
    })
  } catch {
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

async function updateLocation(latitude, longitude, accuracy) {
  currentLatitude.value = Number(latitude.toFixed(6))
  currentLongitude.value = Number(longitude.toFixed(6))
  currentAccuracy.value = Number.isFinite(accuracy) ? Number(accuracy.toFixed(2)) : null

  const reverseData = await reverseGeocode(latitude, longitude)

  if (reverseData?.display_name) {
    currentGpsAddress.value = String(reverseData.display_name)

    if (!String(currentAddress.value || '').trim()) {
      currentAddress.value = String(reverseData.display_name)
    }

    const suggestedNeighborhood = extractNeighborhood(reverseData)
    if (suggestedNeighborhood && !String(currentNeighborhood.value || '').trim()) {
      currentNeighborhood.value = suggestedNeighborhood
    }
  }
}

async function reverseGeocode(latitude, longitude) {
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

    if (reverseRequestToken.value !== requestToken) {
      return null
    }

    return payload
  } catch {
    return null
  }
}

async function searchAddress() {
  return searchAddressWithOptions({ showWarning: true })
}

function onSearchInput() {
  if (searchDebounceTimer) {
    clearTimeout(searchDebounceTimer)
  }

  const query = searchQuery.value.trim()
  if (query.length < 3) {
    isSearching.value = false
    searchedNoResults.value = false
    searchResults.value = []
    return
  }

  searchDebounceTimer = setTimeout(() => {
    searchAddressWithOptions({ showWarning: false })
  }, 280)
}

async function searchAddressWithOptions(options = {}) {
  const { showWarning = false } = options
  const normalizedQuery = sanitizeSearchQuery(searchQuery.value)

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

  const requestToken = Date.now()
  searchRequestToken.value = requestToken

  isSearching.value = true
  searchedNoResults.value = false

  try {
    let normalizedResults = []
    let hadSuccessfulProvider = false

    const providers = [
      () => searchWithNominatim(normalizedQuery, { countryCode: 'co' }),
      () => searchWithNominatim(normalizedQuery),
      () => searchWithPhoton(normalizedQuery),
    ]

    for (const provider of providers) {
      try {
        const providerResults = await provider()
        hadSuccessfulProvider = true

        if (providerResults.length > 0) {
          normalizedResults = providerResults
          break
        }
      } catch {
        // Continuar con el siguiente proveedor sin interrumpir la búsqueda.
      }
    }

    if (searchRequestToken.value !== requestToken) {
      return
    }

    searchResults.value = normalizedResults
    searchedNoResults.value = normalizedResults.length === 0

    if (!hadSuccessfulProvider) {
      throw new Error('all_search_providers_failed')
    }
  } catch {
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
    if (searchRequestToken.value === requestToken) {
      isSearching.value = false
    }
  }
}

async function searchWithNominatim(query, options = {}) {
  const params = new URLSearchParams({
    format: 'jsonv2',
    addressdetails: '1',
    'accept-language': 'es',
    limit: '8',
    q: query,
  })

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

async function searchWithPhoton(query) {
  const endpoint = `https://photon.komoot.io/api/?lang=es&limit=8&q=${encodeURIComponent(query)}`
  const response = await fetch(endpoint)

  if (!response.ok) {
    throw new Error('photon_search_failed')
  }

  const payload = await response.json()
  const features = Array.isArray(payload?.features) ? payload.features : []

  return features
    .map((feature) => {
      const coordinates = Array.isArray(feature?.geometry?.coordinates)
        ? feature.geometry.coordinates
        : []

      const longitude = Number(coordinates[0])
      const latitude = Number(coordinates[1])
      if (!Number.isFinite(latitude) || !Number.isFinite(longitude)) {
        return null
      }

      const properties = feature?.properties || {}
      const name = String(
        properties?.name
          || properties?.street
          || properties?.district
          || properties?.city
          || 'Resultado',
      ).trim()

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

function sanitizeSearchQuery(rawQuery) {
  return String(rawQuery || '')
    .normalize('NFD')
    .replace(/[\u0300-\u036f]/g, '')
    .replace(/\s+/g, ' ')
    .trim()
}

function normalizeSearchResults(payload) {
  const items = Array.isArray(payload) ? payload : []

  return items
    .map((item) => {
      const latitude = Number(item?.lat)
      const longitude = Number(item?.lon)
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

async function selectSearchResult(result) {
  const latitude = Number(result?.lat)
  const longitude = Number(result?.lon)

  if (!Number.isFinite(latitude) || !Number.isFinite(longitude)) {
    return
  }

  searchResults.value = []
  searchedNoResults.value = false

  mapInstance.value?.setView([latitude, longitude], 17)
  mapMarker.value?.setLatLng([latitude, longitude])

  currentGpsAddress.value = String(result?.display_name || '')
  await updateLocation(latitude, longitude, null)
}

function confirmLocation() {
  if (!canConfirmGps.value) {
    showSnackbar({
      type: 'warning',
      title: 'Ubicacion incompleta',
      message: 'Selecciona primero una ubicacion valida en el mapa.',
    })
    return
  }

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

function toggleMapFullscreen() {
  isMapFullscreen.value = !isMapFullscreen.value

  setTimeout(() => {
    mapInstance.value?.invalidateSize()
  }, 150)
}

function extractNeighborhood(payload) {
  const address = payload?.address || {}
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

async function loadLeaflet() {
  if (typeof window === 'undefined') return null

  if (window.L) {
    return window.L
  }

  await loadLeafletStylesheetWithFallback()
  await loadLeafletScriptWithFallback()

  return window.L || null
}

function attachTileLayerWithFallback(leaflet) {
  let providerIndex = 0
  let switchedProvider = false

  const createLayer = () => leaflet.tileLayer(
    TILE_PROVIDERS[providerIndex].url,
    TILE_PROVIDERS[providerIndex].options,
  )

  const registerLayerErrorHandler = (layer) => {
    layer.on('tileerror', () => {
      if (providerIndex >= TILE_PROVIDERS.length - 1) {
        return
      }

      providerIndex += 1

      if (!switchedProvider) {
        switchedProvider = true
        showSnackbar({
          type: 'warning',
          title: 'Cambiando proveedor de mapa',
          message: 'El proveedor principal no respondio. Se aplico un respaldo automaticamente.',
          durationMs: 2600,
        })
      }

      mapInstance.value?.removeLayer(layer)
      const nextLayer = createLayer()
      registerLayerErrorHandler(nextLayer)
      nextLayer.addTo(mapInstance.value)
      mapTileLayer.value = nextLayer
    })
  }

  const layer = createLayer()
  registerLayerErrorHandler(layer)
  layer.addTo(mapInstance.value)

  return layer
}

async function loadLeafletStylesheetWithFallback() {
  let lastError = null

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

function ensureStylesheet(href, id) {
  if (document.getElementById(id)) {
    return Promise.resolve()
  }

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

function ensureScript(src, id) {
  if (document.getElementById(id)) {
    return Promise.resolve()
  }

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

function getCurrentPosition() {
  return new Promise((resolve, reject) => {
    if (!navigator.geolocation) {
      reject(new Error('not_supported'))
      return
    }

    navigator.geolocation.getCurrentPosition(resolve, reject, {
      enableHighAccuracy: true,
      timeout: 12000,
      maximumAge: 0,
    })
  })
}

function destroyMap() {
  if (mapInstance.value) {
    mapInstance.value.remove()
    mapInstance.value = null
  }

  mapTileLayer.value = null
  mapMarker.value = null
}

function toNullableNumber(value) {
  if (value === null || value === undefined) {
    return null
  }

  const normalized = String(value).trim()
  if (!normalized) {
    return null
  }

  const parsed = Number(normalized)
  return Number.isFinite(parsed) ? parsed : null
}

function isValidCoordinatePair(latitude, longitude) {
  const lat = toNullableNumber(latitude)
  const lng = toNullableNumber(longitude)

  if (!Number.isFinite(lat) || !Number.isFinite(lng)) {
    return false
  }

  const isInRange = lat >= -90 && lat <= 90 && lng >= -180 && lng <= 180
  const isZeroed = Math.abs(lat) < 0.000001 && Math.abs(lng) < 0.000001
  return isInRange && !isZeroed
}
</script>
