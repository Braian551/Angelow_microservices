<template>
  <div class="addr-map-viewer" :style="{ height: height }">
    <!-- Mapa Leaflet -->
    <div
      v-if="mapState !== 'unavailable'"
      ref="mapEl"
      class="addr-map-viewer__map"
    />

    <!-- Shimmer mientras carga -->
    <div v-if="mapState === 'loading'" class="addr-map-viewer__overlay">
      <div class="addr-map-viewer__spinner" />
      <span>Cargando mapa...</span>
    </div>

    <!-- Estado sin coordenadas disponibles -->
    <div v-if="mapState === 'unavailable'" class="addr-map-viewer__unavailable">
      <i class="fas fa-map-marked-alt" />
      <span>Mapa no disponible para esta dirección.</span>
    </div>

    <!-- Chip con coordenadas o indicador de posición aproximada -->
    <div v-if="mapState === 'ready'" class="addr-map-viewer__badge" :class="{ 'addr-map-viewer__badge--approx': isApproximate }">
      <i :class="isApproximate ? 'fas fa-circle-info' : 'fas fa-location-dot'" />
      <span>{{ isApproximate ? 'Posición aproximada' : 'Ubicación exacta' }}</span>
    </div>
  </div>
</template>

<script setup>
import { onBeforeUnmount, onMounted, ref, watch } from 'vue'

// ── Constantes ──────────────────────────────────────────────────────────────

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
    url: 'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',
    options: { maxZoom: 19, attribution: '&copy; OpenStreetMap contributors' },
  },
  {
    url: 'https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png',
    options: {
      maxZoom: 20,
      subdomains: 'abcd',
      attribution: '&copy; OpenStreetMap contributors &copy; CARTO',
    },
  },
]

const DEFAULT_CENTER = { lat: 6.25184, lng: -75.56359 }

// ── Props ────────────────────────────────────────────────────────────────────

const props = defineProps({
  /** Latitud de la dirección (exacta o desde coordenadas GPS de la BD). */
  latitude: {
    type: [Number, String, null],
    default: null,
  },
  /** Longitud de la dirección. */
  longitude: {
    type: [Number, String, null],
    default: null,
  },
  /** Texto de la dirección como respaldo para geocodificación aproximada. */
  addressText: {
    type: String,
    default: '',
  },
  /** Altura del contenedor del mapa. */
  height: {
    type: String,
    default: '220px',
  },
})

// ── Estado ───────────────────────────────────────────────────────────────────

const mapEl = ref(null)
// 'loading' | 'ready' | 'unavailable'
const mapState = ref('loading')
const isApproximate = ref(false)
let mapInstance = null
let providerIndex = 0

// ── Ciclo de vida ─────────────────────────────────────────────────────────────

onMounted(() => {
  initMap()
})

onBeforeUnmount(() => {
  destroyMap()
})

watch(
  () => [props.latitude, props.longitude, props.addressText],
  () => {
    destroyMap()
    mapState.value = 'loading'
    isApproximate.value = false
    providerIndex = 0
    initMap()
  },
)

// ── Inicialización del mapa ───────────────────────────────────────────────────

async function initMap() {
  const leaflet = await loadLeaflet()
  if (!leaflet || !mapEl.value) {
    mapState.value = 'unavailable'
    return
  }

  const lat = toNullableNumber(props.latitude)
  const lng = toNullableNumber(props.longitude)

  if (isValidCoordinatePair(lat, lng)) {
    // Coordenadas exactas desde la BD
    isApproximate.value = false
    renderMap(leaflet, lat, lng, 16)
    return
  }

  // Sin coordenadas: geocodificar el texto de la dirección
  const addressQuery = String(props.addressText || '').trim()
  if (addressQuery.length >= 5) {
    const coords = await geocodeAddress(addressQuery)
    if (coords) {
      isApproximate.value = true
      renderMap(leaflet, coords.lat, coords.lng, 14)
      return
    }
  }

  mapState.value = 'unavailable'
}

function renderMap(leaflet, lat, lng, zoom) {
  if (!mapEl.value) return

  mapInstance = leaflet.map(mapEl.value, {
    zoomControl: true,
    attributionControl: true,
    dragging: true,
    scrollWheelZoom: false,
    doubleClickZoom: false,
  }).setView([lat, lng], zoom)

  attachTileLayer(leaflet)

  // Marcador fijo no arrastrable
  leaflet.marker([lat, lng]).addTo(mapInstance)

  setTimeout(() => mapInstance?.invalidateSize(), 120)

  mapState.value = 'ready'
}

function attachTileLayer(leaflet) {
  const createLayer = () =>
    leaflet.tileLayer(TILE_PROVIDERS[providerIndex].url, TILE_PROVIDERS[providerIndex].options)

  const addLayer = () => {
    const layer = createLayer()
    layer.on('tileerror', () => {
      if (providerIndex < TILE_PROVIDERS.length - 1) {
        mapInstance?.removeLayer(layer)
        providerIndex += 1
        addLayer()
      }
    })
    layer.addTo(mapInstance)
  }

  addLayer()
}

// ── Geocodificación aproximada (Nominatim) ─────────────────────────────────

async function geocodeAddress(query) {
  const params = new URLSearchParams({
    format: 'jsonv2',
    limit: '1',
    'accept-language': 'es',
    q: query,
  })

  try {
    const response = await fetch(`https://nominatim.openstreetmap.org/search?${params.toString()}`)
    if (!response.ok) return null
    const results = await response.json()
    const first = Array.isArray(results) ? results[0] : null
    if (!first) return null
    const lat = Number(first.lat)
    const lng = Number(first.lon)
    return isValidCoordinatePair(lat, lng) ? { lat, lng } : null
  } catch {
    return null
  }
}

// ── Utilidades (autocontenidas, sin dependencia de módulo externo) ────────────

function toNullableNumber(value) {
  if (value === null || value === undefined) return null
  const parsed = Number(String(value).trim())
  return Number.isFinite(parsed) ? parsed : null
}

function isValidCoordinatePair(lat, lng) {
  if (!Number.isFinite(lat) || !Number.isFinite(lng)) return false
  const inRange = lat >= -90 && lat <= 90 && lng >= -180 && lng <= 180
  const notZeroed = !(Math.abs(lat) < 0.000001 && Math.abs(lng) < 0.000001)
  return inRange && notZeroed
}

async function loadLeaflet() {
  if (typeof window === 'undefined') return null
  if (window.L) return window.L
  try {
    await loadLeafletCss()
    await loadLeafletJs()
    return window.L || null
  } catch {
    return null
  }
}

function loadLeafletCss() {
  return loadWithFallback(LEAFLET_CSS_URLS, (href, id) => {
    const link = document.createElement('link')
    link.id = id; link.rel = 'stylesheet'; link.href = href
    return link
  }, 'leaflet-css')
}

function loadLeafletJs() {
  return loadWithFallback(LEAFLET_JS_URLS, (src, id) => {
    const script = document.createElement('script')
    script.id = id; script.src = src; script.async = true
    return script
  }, 'leaflet-js')
}

function loadWithFallback(urls, createElement, prefix) {
  const tryNext = (index) => {
    const id = `${prefix}-${index}`
    if (document.getElementById(id)) return Promise.resolve()
    return new Promise((resolve, reject) => {
      const el = createElement(urls[index], id)
      el.onload = resolve
      el.onerror = () => {
        if (index + 1 < urls.length) tryNext(index + 1).then(resolve, reject)
        else reject(new Error(`load_failed: ${prefix}`))
      }
      document.head.appendChild(el)
    })
  }
  return tryNext(0)
}

function destroyMap() {
  if (mapInstance) {
    mapInstance.remove()
    mapInstance = null
  }
}
</script>

<style scoped>
.addr-map-viewer {
  position: relative;
  width: 100%;
  border-radius: 10px;
  overflow: hidden;
  background: #f0f4f8;
  margin-top: 1.6rem;
}

.addr-map-viewer__map {
  width: 100%;
  height: 100%;
}

/* Overlay de carga */
.addr-map-viewer__overlay {
  position: absolute;
  inset: 0;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 0.8rem;
  background: rgba(240, 244, 248, 0.85);
  font-size: 1.3rem;
  color: #6b7280;
  z-index: 10;
}

.addr-map-viewer__spinner {
  width: 28px;
  height: 28px;
  border: 3px solid #d1dbe6;
  border-top-color: #0077b6;
  border-radius: 50%;
  animation: addrMapSpin 0.8s linear infinite;
}

@keyframes addrMapSpin {
  to { transform: rotate(360deg); }
}

/* Estado no disponible */
.addr-map-viewer__unavailable {
  height: 100%;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 0.7rem;
  color: #9ca3af;
  font-size: 1.35rem;
}

.addr-map-viewer__unavailable i {
  font-size: 2.4rem;
  color: #cbd5e1;
}

/* Chip flotante de tipo de ubicación */
.addr-map-viewer__badge {
  position: absolute;
  bottom: 0.9rem;
  left: 0.9rem;
  z-index: 800;
  display: inline-flex;
  align-items: center;
  gap: 0.45rem;
  padding: 0.38rem 0.9rem;
  border-radius: 999px;
  font-size: 1.18rem;
  font-weight: 600;
  background: #0077b6;
  color: #fff;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.18);
  pointer-events: none;
}

.addr-map-viewer__badge--approx {
  background: #f59e0b;
}
</style>
