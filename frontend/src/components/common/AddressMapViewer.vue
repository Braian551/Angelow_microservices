<!--
  AddressMapViewer.vue
  ────────────────────
  Componente que muestra un mapa interactivo (Leaflet + OpenStreetMap)
  para visualizar la ubicación de una dirección. Soporta dos modos:
    1) Coordenadas exactas (lat/lng provenientes de la base de datos).
    2) Geocodificación aproximada a partir del texto de la dirección
       usando el servicio Nominatim (OpenStreetMap).
  
  El mapa se carga de forma dinámica (lazy-load) incluyendo CSS y JS
  de CDN con sistema de respaldo (fallback) si el primer proveedor falla.
-->
<template>
  <!-- Contenedor raíz del visor; la altura es configurable mediante prop -->
  <div class="addr-map-viewer" :style="{ height: height }">
    <!-- Mapa Leaflet: se oculta cuando el estado es 'unavailable' -->
    <div
      v-if="mapState !== 'unavailable'"
      ref="mapEl"
      class="addr-map-viewer__map"
    />

    <!-- Overlay de carga: se muestra mientras el mapa se está inicializando.
         Incluye un spinner animado y un texto indicativo. -->
    <div v-if="mapState === 'loading'" class="addr-map-viewer__overlay">
      <div class="addr-map-viewer__spinner" />
      <span>Cargando mapa...</span>
    </div>

    <!-- Estado no disponible: se muestra cuando no se pudieron obtener
         coordenadas ni geocodificar la dirección (datos insuficientes). -->
    <div v-if="mapState === 'unavailable'" class="addr-map-viewer__unavailable">
      <i class="fas fa-map-marked-alt" />
      <span>Mapa no disponible para esta dirección.</span>
    </div>

    <!-- Badge (chip) flotante que indica si la ubicación es exacta o aproximada.
         Se posiciona en la esquina inferior izquierda del mapa. -->
    <div v-if="mapState === 'ready'" class="addr-map-viewer__badge" :class="{ 'addr-map-viewer__badge--approx': isApproximate }">
      <!-- El icono cambia según el tipo de ubicación -->
      <i :class="isApproximate ? 'fas fa-circle-info' : 'fas fa-location-dot'" />
      <!-- Texto descriptivo del tipo de ubicación -->
      <span>{{ isApproximate ? 'Posición aproximada' : 'Ubicación exacta' }}</span>
    </div>
  </div>
</template>

<script setup>
/*
 * Script del componente AddressMapViewer.
 * Utiliza la Composition API de Vue 3 con <script setup>.
 * Gestiona la carga dinámica de Leaflet, la visualización del mapa,
 * la geocodificación de direcciones y el manejo del ciclo de vida.
 */
import { onBeforeUnmount, onMounted, ref, watch } from 'vue'

// ── Constantes ──────────────────────────────────────────────────────────────

/*
 * URLs de los archivos CSS de Leaflet desde diferentes CDN (unpkg y jsDelivr).
 * Se usan como fallback: si el primero falla, se intenta el segundo.
 * Leaflet se carga de forma dinámica (no está en package.json) para
 * reducir el tamaño del bundle y permitir caché del navegador.
 */
const LEAFLET_CSS_URLS = [
  'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css',
  'https://cdn.jsdelivr.net/npm/leaflet@1.9.4/dist/leaflet.css',
]

/*
 * URLs de los archivos JavaScript de Leaflet desde diferentes CDN.
 * Mismo sistema de fallback que el CSS: si un proveedor no responde,
 * se prueba con el siguiente para mayor disponibilidad.
 */
const LEAFLET_JS_URLS = [
  'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js',
  'https://cdn.jsdelivr.net/npm/leaflet@1.9.4/dist/leaflet.js',
]

/*
 * Proveedor de mosaicos (tiles) del mapa.
 * Se definen dos opciones con diferentes estilos visuales:
 *   1) OpenStreetMap estándar (más oscuro, clásico).
 *   2) CARTO Light (más claro, minimalista).
 * Si el primer proveedor falla al cargar los mosaicos, se intenta con el segundo.
 * El objeto 'options' incluye maxZoom y attribution requeridos por Leaflet.
 */
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

/*
 * Coordenadas por defecto (centro de Medellín, Colombia).
 * Se usa como fallback cuando no hay coordenadas disponibles
 * y tampoco se puede geocodificar la dirección. Actualmente no se
 * utiliza directamente pero se mantiene por si se necesita en el futuro.
 */
const DEFAULT_CENTER = { lat: 6.25184, lng: -75.56359 }

// ── Props ────────────────────────────────────────────────────────────────────

/*
 * Props del componente. Se definen las propiedades que el componente
 * recibe desde su componente padre. Las coordenadas pueden venir como
 * Number o String (por si vienen de inputs de formulario o de la BD).
 */
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
  /*
   * Texto de la dirección como respaldo para geocodificación aproximada.
   * Se usa cuando no hay coordenadas disponibles en la BD; el componente
   * consulta Nominatim para obtener una ubicación aproximada.
   */
  addressText: {
    type: String,
    default: '',
  },
  /** Altura del contenedor del mapa (formato CSS: '220px', '20vh', etc.). */
  height: {
    type: String,
    default: '220px',
  },
})

// ── Estado ───────────────────────────────────────────────────────────────────

/*
 * Referencia al elemento DOM del mapa (<div> donde Leaflet renderiza).
 * Se usa ref(null) y se asigna mediante el atributo ref="mapEl" en el template.
 * Leaflet necesita este elemento DOM para inicializarse.
 */
const mapEl = ref(null)

/*
 * Estado actual del mapa. Controla qué se muestra en el template:
 *   'loading'     → Se está cargando Leaflet o inicializando el mapa.
 *   'ready'       → El mapa se renderizó correctamente con coordenadas.
 *   'unavailable' → No se pudieron obtener coordenadas (ni exactas ni por geocodificación).
 */
const mapState = ref('loading')

/*
 * Indica si la ubicación mostrada es aproximada (geocodificada)
 * o exacta (coordenadas de la BD). Afecta el estilo y texto del badge.
 */
const isApproximate = ref(false)

/*
 * Instancia activa de Leaflet.Map. Se guarda en una variable reactiva
 * (no ref) porque solo se usa internamente en el script, nunca en el template.
 * Se destruye y recrea cada vez que cambian las props.
 */
let mapInstance = null

/*
 * Índice del proveedor de mosaicos actualmente en uso.
 * Se incrementa automáticamente si el proveedor falla al cargar tiles,
 * permitiendo el fallback entre OpenStreetMap y CARTO.
 */
let providerIndex = 0

// ── Ciclo de vida ─────────────────────────────────────────────────────────────

/*
 * Hook que se ejecuta cuando el componente se monta en el DOM.
 * Inicia la carga de Leaflet y la renderización del mapa.
 * Se usa onMounted porque Leaflet necesita acceso al elemento DOM
 * para crear la instancia del mapa.
 */
onMounted(() => {
  initMap()
})

/*
 * Hook que se ejecuta antes de que el componente se desmonte del DOM.
 * Limpia la instancia de Leaflet para evitar memory leaks.
 * Es importante destruir el mapa porque Leaflet registra listeners
 * en el DOM y en eventos del navegador que persistirían si no se limpian.
 */
onBeforeUnmount(() => {
  destroyMap()
})

/*
 * Watcher que observa cambios en latitude, longitude y addressText.
 * Cuando cualquiera de estas props cambia:
 *   1) Destruye el mapa actual (libera recursos y listeners).
 *   2) Resetea el estado a 'loading' y la bandera de aproximación.
 *   3) Reinicia el índice del proveedor de tiles (por si el anterior falló).
 *   4) Vuelve a inicializar el mapa con los nuevos datos.
 * Esto permite que el componente sea reactivo a cambios en las props
 * sin necesidad de destruir y recrear el componente completo.
 */
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

/*
 * Función principal que orquesta la inicialización del mapa.
 * Flujo:
 *   1) Carga dinámica de Leaflet (CSS + JS desde CDN).
 *   2) Verifica que Leaflet y el elemento DOM estén disponibles.
 *   3) Intenta usar coordenadas exactas (lat/lng) de las props.
 *   4) Si no hay coordenadas válidas, intenta geocodificar el texto de la dirección.
 *   5) Si nada funciona, marca el estado como 'unavailable'.
 *
 * Es async porque loadLeaflet() y geocodeAddress() son operaciones asíncronas
 * (realizan requests HTTP para cargar scripts o consultar la API de Nominatim).
 */
async function initMap() {
  // Carga Leaflet dinámicamente; devuelve la referencia global window.L
  const leaflet = await loadLeaflet()
  if (!leaflet || !mapEl.value) {
    // Si Leaflet no se pudo cargar o el elemento DOM no existe, abortar
    mapState.value = 'unavailable'
    return
  }

  // Convierte las props a números nullable (pueden venir como string)
  const lat = toNullableNumber(props.latitude)
  const lng = toNullableNumber(props.longitude)

  if (isValidCoordinatePair(lat, lng)) {
    // Las coordenadas son válidas: renderizar el mapa con zoom alto (16)
    // para mostrar la ubicación exacta
    isApproximate.value = false
    renderMap(leaflet, lat, lng, 16)
    return
  }

  /*
   * No hay coordenadas válidas: intentar geocodificación aproximada.
   * Se usa el texto de la dirección para consultar Nominatim.
   * Se requiere al menos 5 caracteres para evitar búsquedas muy genéricas.
   */
  const addressQuery = String(props.addressText || '').trim()
  if (addressQuery.length >= 5) {
    const coords = await geocodeAddress(addressQuery)
    if (coords) {
      // Geocodificación exitosa: marcar como aproximada y renderizar
      isApproximate.value = true
      // Zoom 14 para ubicaciones aproximadas (más zoom out que las exactas)
      renderMap(leaflet, coords.lat, coords.lng, 14)
      return
    }
  }

  // No se pudieron obtener coordenadas por ningún método
  mapState.value = 'unavailable'
}

/*
 * Renderiza el mapa Leaflet en el elemento DOM.
 * Configura el mapa con opciones de solo lectura (sin zoom scroll,
 * sin doble clic) y añade un marcador fijo en la posición indicada.
 *
 * @param {Object} leaflet - Referencia global de Leaflet (window.L).
 * @param {number} lat - Latitud del centro del mapa.
 * @param {number} lng - Longitud del centro del mapa.
 * @param {number} zoom - Nivel de zoom inicial (mayor = más cercano).
 */
function renderMap(leaflet, lat, lng, zoom) {
  if (!mapEl.value) return

  // Crear la instancia del mapa con opciones de solo visualización
  mapInstance = leaflet.map(mapEl.value, {
    zoomControl: true,         // Muestra los controles +/- de zoom
    attributionControl: true,  // Muestra la atribución de OpenStreetMap
    dragging: true,            // Permite arrastrar el mapa con el mouse
    scrollWheelZoom: false,    // Desactiva zoom con rueda del mouse (evita scroll accidental)
    doubleClickZoom: false,    // Desactiva zoom con doble clic (mejor UX para visualización)
  }).setView([lat, lng], zoom) // Centra el mapa en las coordenadas con el zoom indicado

  // Añade la capa de mosaicos (tiles) con sistema de fallback
  attachTileLayer(leaflet)

  // Añade un marcador fijo no arrastrable en la posición exacta
  leaflet.marker([lat, lng]).addTo(mapInstance)

  /*
   * invalidateSize() fuerza a Leaflet a recalcular el tamaño del contenedor.
   * Es necesario porque el mapa puede haberse renderizado mientras el DOM
   * aún no tenía sus dimensiones finales (por ejemplo, dentro de un contenedor
   * con flexbox o grid). El setTimeout de 120ms da tiempo al navegador
   * para calcular el layout completo.
   */
  setTimeout(() => mapInstance?.invalidateSize(), 120)

  // Marcar el estado como listo para que el template muestre el mapa y el badge
  mapState.value = 'ready'
}

/*
 * Adjunta la capa de mosaicos (tile layer) al mapa con sistema de fallback.
 * Si el proveedor actual falla al cargar algún mosaico (evento 'tileerror'),
 * automáticamente cambia al siguiente proveedor disponible.
 * Esto garantiza que el mapa siempre intente mostrar contenido,
 * incluso si un CDN está temporalmente caído.
 *
 * @param {Object} leaflet - Referencia global de Leaflet (window.L).
 */
function attachTileLayer(leaflet) {
  // Función que crea una nueva capa de mosaicos del proveedor actual
  const createLayer = () =>
    leaflet.tileLayer(TILE_PROVIDERS[providerIndex].url, TILE_PROVIDERS[providerIndex].options)

  // Función que añade la capa al mapa y configura el fallback
  const addLayer = () => {
    const layer = createLayer()
    // Escuchar errores de carga de mosaicos
    layer.on('tileerror', () => {
      if (providerIndex < TILE_PROVIDERS.length - 1) {
        // Hay otro proveedor disponible: eliminar la capa fallida
        mapInstance?.removeLayer(layer)
        // Avanzar al siguiente proveedor y reintentar
        providerIndex += 1
        addLayer()
      }
      // Si no hay más proveedores, el mapa mostrará las áreas vacías
    })
    // Añadir la capa al mapa
    layer.addTo(mapInstance)
  }

  addLayer()
}

// ── Geocodificación aproximada (Nominatim) ─────────────────────────────────

/*
 * Función que consulta la API de Nominatim (OpenStreetMap) para obtener
 * coordenadas a partir de un texto de dirección. Nominatim es un servicio
 * gratuito de geocodificación que no requiere API key.
 *
 * Limitaciones conocidas:
 *   - Rate limiting: ~1 request/segundo (no documentado oficialmente).
 *   - Precisión variable: depende de la calidad del texto de entrada.
 *   - No funciona offline.
 *
 * @param {string} query - Texto de la dirección a geocodificar.
 * @returns {Object|null} - Objeto { lat, lng } o null si no se encontraron resultados.
 */
async function geocodeAddress(query) {
  // Construir los parámetros de la query string
  const params = new URLSearchParams({
    format: 'jsonv2',         // Formato de respuesta v2 (más detallado)
    limit: '1',               // Solo el resultado más relevante
    'accept-language': 'es',  // Respuestas en español cuando sea posible
    q: query,                 // Texto de la dirección a buscar
  })

  try {
    // Realizar la solicitud GET a Nominatim
    const response = await fetch(`https://nominatim.openstreetmap.org/search?${params.toString()}`)
    if (!response.ok) return null  // Error HTTP (4xx o 5xx)

    const results = await response.json()
    // Nominatim retorna un array; tomar el primer resultado
    const first = Array.isArray(results) ? results[0] : null
    if (!first) return null  // No se encontraron resultados

    // Extraer latitud y longitud del resultado
    const lat = Number(first.lat)
    const lng = Number(first.lon)  // Nominatim usa 'lon', no 'lng'

    // Validar que las coordenadas estén en rangos geográficos válidos
    return isValidCoordinatePair(lat, lng) ? { lat, lng } : null
  } catch {
    // Cualquier error de red o parsing: retornar null silenciosamente
    return null
  }
}

// ── Utilidades (autocontenidas, sin dependencia de módulo externo) ────────────

/*
 * Convierte un valor a número o null.
 * Maneja casos edge:
 *   - null/undefined → null
 *   - Strings con espacios → se recortan antes de parsear
 *   - NaN o Infinity → null
 * Esto es necesario porque las props pueden venir como strings
 * desde inputs de formulario o desde la base de datos.
 *
 * @param {*} value - Valor a convertir.
 * @returns {number|null} - Número válido o null.
 */
function toNullableNumber(value) {
  if (value === null || value === undefined) return null
  const parsed = Number(String(value).trim())
  return Number.isFinite(parsed) ? parsed : null
}

/*
 * Valida que un par de coordenadas esté en rangos geográficos válidos:
 *   - Latitud: entre -90 y 90 grados.
 *   - Longitud: entre -180 y 180 grados.
 *   - Ambas deben ser números finitos (no NaN ni Infinity).
 *   - No pueden ser ambas cero (0, 0) exactas, ya que eso corresponde
 *     al Golfo de Guinea en África y probablemente indica datos faltantes.
 *
 * @param {number} lat - Latitud a validar.
 * @param {number} lng - Longitud a validar.
 * @returns {boolean} - true si el par es válido.
 */
function isValidCoordinatePair(lat, lng) {
  if (!Number.isFinite(lat) || !Number.isFinite(lng)) return false
  const inRange = lat >= -90 && lat <= 90 && lng >= -180 && lng <= 180
  const notZeroed = !(Math.abs(lat) < 0.000001 && Math.abs(lng) < 0.000001)
  return inRange && notZeroed
}

/*
 * Carga Leaflet de forma dinámica desde CDN.
 * Primero verifica si ya está cargado (window.L) para evitar recargas.
 * Carga el CSS y JS en orden (CSS primero porque Leaflet depende de él).
 *
 * @returns {Object|null} - Referencia global de Leaflet (window.L) o null si falla.
 */
async function loadLeaflet() {
  // Verificar si estamos en el navegador (no en SSR)
  if (typeof window === 'undefined') return null
  // Si Leaflet ya está cargado, retornarlo directamente
  if (window.L) return window.L
  try {
    // Cargar CSS primero (necesario para los estilos del mapa)
    await loadLeafletCss()
    // Luego cargar JS (necesario para la API de Leaflet)
    await loadLeafletJs()
    return window.L || null
  } catch {
    // Si falla la carga de cualquiera de los dos, abortar
    return null
  }
}

/*
 * Carga el archivo CSS de Leaflet desde CDN con sistema de fallback.
 * Crea un elemento <link> y lo añade al <head> del documento.
 *
 * @returns {Promise<void>} - Promesa que se resuelve cuando el CSS se carga.
 */
function loadLeafletCss() {
  return loadWithFallback(LEAFLET_CSS_URLS, (href, id) => {
    const link = document.createElement('link')
    link.id = id; link.rel = 'stylesheet'; link.href = href
    return link
  }, 'leaflet-css')
}

/*
 * Carga el archivo JavaScript de Leaflet desde CDN con sistema de fallback.
 * Crea un elemento <script> asíncrono y lo añade al <head> del documento.
 *
 * @returns {Promise<void>} - Promesa que se resuelve cuando el JS se carga.
 */
function loadLeafletJs() {
  return loadWithFallback(LEAFLET_JS_URLS, (src, id) => {
    const script = document.createElement('script')
    script.id = id; script.src = src; script.async = true
    return script
  }, 'leaflet-js')
}

/*
 * Función genérica de carga con fallback para recursos externos.
 * Implementa un patrón de retry recursivo:
 *   1) Intenta cargar el recurso en el índice actual.
 *   2) Si falla, avanza al siguiente índice (siguiente CDN).
 *   3) Si ya no hay más índices, rechaza la promesa con error.
 *   4) Si el elemento ya existe en el DOM, no lo vuelve a cargar.
 *
 * @param {string[]} urls - Array de URLs a intentar en orden.
 * @param {Function} createElement - Función factory que crea el elemento DOM.
 * @param {string} prefix - Prefijo para generar IDs únicos (evita duplicados).
 * @returns {Promise<void>} - Promesa que se resuelve cuando se carga exitosamente.
 */
function loadWithFallback(urls, createElement, prefix) {
  const tryNext = (index) => {
    const id = `${prefix}-${index}`
    // Si el elemento ya existe en el DOM, no recargarlo (caché del navegador)
    if (document.getElementById(id)) return Promise.resolve()
    return new Promise((resolve, reject) => {
      const el = createElement(urls[index], id)
      el.onload = resolve
      el.onerror = () => {
        // Error de carga: intentar con el siguiente proveedor
        if (index + 1 < urls.length) tryNext(index + 1).then(resolve, reject)
        else reject(new Error(`load_failed: ${prefix}`))
      }
      // Añadir el elemento al <head> para que el navegador lo cargue
      document.head.appendChild(el)
    })
  }
  return tryNext(0)
}

/*
 * Destruye la instancia de Leaflet y libera todos los recursos asociados.
 * Leaflet mantiene listeners en el DOM y en eventos del navegador;
 * no limpiarlos causaría memory leaks, especialmente en SPAs
 * donde los componentes se montan y desmontan frecuentemente.
 */
function destroyMap() {
  if (mapInstance) {
    mapInstance.remove()
    mapInstance = null
  }
}
</script>

<style scoped>
/*
 * Estilos del componente AddressMapViewer.
 * Se usa scoped para evitar que los estilos afecten otros componentes.
 * Los estilos de Leaflet se cargan dinámicamente desde CDN;
 * estos estilos complementan la apariencia del contenedor y los overlays.
 */

/* Contenedor principal del visor del mapa */
.addr-map-viewer {
  position: relative;   /* Permite posicionar overlays e hijos absolutos dentro */
  width: 100%;          /* Ocupa todo el ancho del contenedor padre */
  border-radius: 10px;  /* Bordes redondeados para un aspecto moderno */
  overflow: hidden;     /* Recorta el mapa y overlays que se salgan del borde */
  background: #f0f4f8;  /* Color de fondo claro para mientras carga el mapa */
  margin-top: 1.6rem;   /* Espacio superior para separar de otros elementos */
}

/* Elemento DOM donde Leaflet renderiza el mapa */
.addr-map-viewer__map {
  width: 100%;   /* Ocupa todo el ancho del contenedor */
  height: 100%;  /* Ocupa toda la altura del contenedor */
}

/*
 * Overlay de carga: se superpone al mapa mientras se está inicializando.
 * Ocupa todo el espacio del contenedor (inset: 0) y muestra un spinner
 * animado con un texto descriptivo. La opacidad de 0.85 permite ver
 * parcialmente el mapa detrás si ya está cargado.
 */
.addr-map-viewer__overlay {
  position: absolute;  /* Se posiciona sobre el mapa */
  inset: 0;            /* Abreviación de top/right/bottom/left: 0 */
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 0.8rem;
  background: rgba(240, 244, 248, 0.85);  /* Fondo semitransparente */
  font-size: 1.3rem;
  color: #6b7280;  /* Texto gris medio */
  z-index: 10;     /* Se确保 que esté sobre el mapa pero bajo el badge */
}

/* Spinner de carga circular con animación de rotación */
.addr-map-viewer__spinner {
  width: 28px;
  height: 28px;
  border: 3px solid #d1dbe6;      /* Borde gris claro */
  border-top-color: #0077b6;      /* Borde superior azul (la parte animada) */
  border-radius: 50%;             /* Forma circular */
  animation: addrMapSpin 0.8s linear infinite;  /* Rotación continua */
}

/* Keyframe de la animación del spinner: rotación completa de 360 grados */
@keyframes addrMapSpin {
  to { transform: rotate(360deg); }
}

/*
 * Estado no disponible: se muestra cuando no hay coordenadas disponibles
 * y la geocodificación tampoco funcionó. Ocupa toda la altura del contenedor
 * y muestra un icono grande con un mensaje explicativo.
 */
.addr-map-viewer__unavailable {
  height: 100%;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 0.7rem;
  color: #9ca3af;    /* Texto gris claro */
  font-size: 1.35rem;
}

/* Icono del estado no disponible: más grande y color más suave */
.addr-map-viewer__unavailable i {
  font-size: 2.4rem;
  color: #cbd5e1;
}

/*
 * Badge (chip) flotante que indica el tipo de ubicación.
 * Se posiciona en la esquina inferior izquierda del mapa.
 * Tiene forma de cápsula (border-radius: 999px) y sombra suave.
 * pointer-events: none evita que el badge interfiera con la interacción del mapa.
 */
.addr-map-viewer__badge {
  position: absolute;
  bottom: 0.9rem;
  left: 0.9rem;
  z-index: 800;     /* Alto z-index para estar sobre todo lo demás */
  display: inline-flex;
  align-items: center;
  gap: 0.45rem;
  padding: 0.38rem 0.9rem;
  border-radius: 999px;     /* Cápsula perfecta */
  font-size: 1.18rem;
  font-weight: 600;
  background: #0077b6;      /* Azul para ubicación exacta */
  color: #fff;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.18);  /* Sombra suave */
  pointer-events: none;     /* No bloquea clicks en el mapa */
}

/* Variante del badge para ubicación aproximada: color ámbar/naranja */
.addr-map-viewer__badge--approx {
  background: #f59e0b;  /* Amarillo/naranja para indicar aproximación */
}
</style>
