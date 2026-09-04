<template>
  <header class="main-header">
    <div class="header-container">
      <!-- Botón hamburguesa (solo móvil) -->
      <button class="mobile-menu-toggle" type="button" :aria-expanded="isMobileMenuOpen" aria-label="Menú" @click="toggleMobileMenu">
        <span class="hamburger-bar"></span>
        <span class="hamburger-bar"></span>
        <span class="hamburger-bar"></span>
      </button>

      <!-- Logo -->
      <div class="content-logo2">
        <RouterLink to="/" class="brand-logo-link">
          <span v-if="showLogoShimmer" class="brand-logo-shimmer" aria-hidden="true"></span>
          <img :src="brandLogo" :alt="storeName" width="100" class="brand-logo-image" :class="{ 'is-ready': !showLogoShimmer }" @load="onLogoLoad" @error="onLogoError" />
        </RouterLink>
      </div>

      <!-- Buscador (desktop + tablet) -->
      <div class="search-bar">
        <form ref="searchContainer" class="search-form" autocomplete="off" @submit.prevent="onSearch">
          <input
            ref="searchInput"
            id="angelow-site-search"
            v-model="searchValue"
            name="angelow_site_search_query"
            type="search"
            placeholder="Buscar productos..."
            autocomplete="off"
            role="presentation"
            @focus="onSearchFocus"
            @keydown.esc="hideSuggestions"
          />
          <button type="submit" aria-label="Buscar">
            <i class="fas fa-search" />
          </button>

          <div v-if="showSuggestions" class="header-search-results">
            <template v-if="isSearching">
              <div class="header-search-loading"></div>
            </template>
            <template v-else-if="featuredSuggestion || searchTerms.length > 0">
              <div v-if="featuredSuggestion" class="header-search-featured">
                <RouterLink
                  :to="{ name: 'product', params: { slug: featuredSuggestion.slug } }"
                  class="header-product-item"
                  @click="onProductSuggestionClick(featuredSuggestion)"
                >
                  <img :src="resolveImageUrl(featuredSuggestion.image_path)" :alt="featuredSuggestion.name" />
                  <div class="header-product-info">
                    <div>{{ featuredSuggestion.name }}</div>
                  </div>
                </RouterLink>
              </div>
              <div v-if="searchTerms.length > 0" class="header-search-terms">
                <button
                  v-for="(term, index) in searchTerms"
                  :key="'term-' + index"
                  type="button"
                  class="header-suggestion-item"
                  @click="onSearchTermClick(term)"
                >
                  <i :class="['fas', wasTermSearched(term) ? 'fa-clock' : 'fa-search']"></i>
                  <span>{{ term }}</span>
                </button>
              </div>
            </template>
            <div v-else-if="searchValue.trim().length >= 2" class="header-no-results">
              No se encontraron resultados.
            </div>
          </div>
        </form>
      </div>

      <!-- Iconos de cuenta / notificaciones / favoritos / carrito -->
      <div class="header-icons">
        <RouterLink :to="accountRoute" aria-label="Mi cuenta">
          <i class="fas fa-user" />
        </RouterLink>
        <RouterLink :to="notificationsRoute" aria-label="Notificaciones">
          <i class="fas fa-bell" />
          <span v-if="notificationCount > 0" class="header-count-badge">{{ notificationBadge }}</span>
        </RouterLink>
        <RouterLink :to="favoritesRoute" aria-label="Favoritos">
          <i class="fas fa-heart" />
        </RouterLink>
        <RouterLink to="/carrito" aria-label="Carrito" class="cart-link">
          <i class="fas fa-shopping-cart" />
          <span v-if="cartCount > 0" class="cart-count">{{ cartCount }}</span>
        </RouterLink>
      </div>
    </div>

    <!-- Barra de búsqueda móvil (fila inferior en móvil) -->
    <div class="mobile-search-bar">
      <form ref="mobileSearchContainer" class="search-form" autocomplete="off" @submit.prevent="onSearch">
        <input
          v-model="searchValue"
          type="search"
          placeholder="Buscar productos..."
          autocomplete="off"
          @focus="onSearchFocus"
          @keydown.esc="hideSuggestions"
        />
        <button type="submit" aria-label="Buscar">
          <i class="fas fa-search" />
        </button>
        <div v-if="showSuggestions" class="header-search-results">
          <template v-if="isSearching">
            <div class="header-search-loading"></div>
          </template>
          <template v-else-if="featuredSuggestion || searchTerms.length > 0">
            <div v-if="featuredSuggestion" class="header-search-featured">
              <RouterLink
                :to="{ name: 'product', params: { slug: featuredSuggestion.slug } }"
                class="header-product-item"
                @click="onProductSuggestionClick(featuredSuggestion)"
              >
                <img :src="resolveImageUrl(featuredSuggestion.image_path)" :alt="featuredSuggestion.name" />
                <div class="header-product-info">
                  <div>{{ featuredSuggestion.name }}</div>
                </div>
              </RouterLink>
            </div>
            <div v-if="searchTerms.length > 0" class="header-search-terms">
              <button
                v-for="(term, index) in searchTerms"
                :key="'term-' + index"
                type="button"
                class="header-suggestion-item"
                @click="onSearchTermClick(term)"
              >
                <i :class="['fas', wasTermSearched(term) ? 'fa-clock' : 'fa-search']"></i>
                <span>{{ term }}</span>
              </button>
            </div>
          </template>
          <div v-else-if="searchValue.trim().length >= 2" class="header-no-results">
            No se encontraron resultados.
          </div>
        </div>
      </form>
    </div>

    <!-- Nav escritorio -->
    <nav class="main-nav">
      <ul>
        <li><RouterLink to="/" :class="['site-nav-link', { 'is-active': isNavActive('inicio') }]">Inicio</RouterLink></li>
        <li><RouterLink :to="{ name: 'store', query: { gender: 'nina' } }" :class="['site-nav-link', { 'is-active': isNavActive('nina') }]">Niñas</RouterLink></li>
        <li><RouterLink :to="{ name: 'store', query: { gender: 'nino' } }" :class="['site-nav-link', { 'is-active': isNavActive('nino') }]">Niños</RouterLink></li>
        <li><RouterLink :to="{ name: 'store', query: { gender: 'bebe' } }" :class="['site-nav-link', { 'is-active': isNavActive('bebe') }]">Bebés</RouterLink></li>
        <li><RouterLink :to="{ name: 'store', query: { offers: '1' } }" :class="['site-nav-link', { 'is-active': isNavActive('offers') }]">Ofertas</RouterLink></li>
        <li><RouterLink :to="{ name: 'collections' }" :class="['site-nav-link', { 'is-active': isNavActive('collections') }]">Colecciones</RouterLink></li>
      </ul>
    </nav>

    <!-- ══ Drawer móvil + backdrop ══ -->
    <transition name="drawer-backdrop">
      <div v-if="isMobileMenuOpen" class="mobile-drawer-backdrop" @click="closeMobileMenu"></div>
    </transition>
    <transition name="drawer-slide">
      <nav v-if="isMobileMenuOpen" class="mobile-drawer" role="dialog" aria-modal="true" aria-label="Menú principal">
        <!-- Cabecera del drawer -->
        <div class="mobile-drawer__header">
          <div class="mobile-drawer__logo-wrap">
            <span v-if="showLogoShimmer" class="mobile-drawer__logo-shimmer" aria-hidden="true"></span>
            <img :src="brandLogo" :alt="storeName" class="mobile-drawer__logo-image" :class="{ 'is-ready': !showLogoShimmer }" @load="onLogoLoad" @error="onLogoError" />
          </div>
          <button type="button" class="mobile-drawer__close" aria-label="Cerrar menú" @click="closeMobileMenu">
            <i class="fas fa-times"></i>
          </button>
        </div>

        <!-- Navegación principal -->
        <ul class="mobile-drawer__nav">
          <li>
            <RouterLink to="/" :class="['mobile-drawer__link', { 'is-active': isNavActive('inicio') }]">
              <span class="mobile-drawer__icon"><i class="fas fa-home"></i></span>
              Inicio
            </RouterLink>
          </li>
          <li>
            <RouterLink :to="{ name: 'store', query: { gender: 'nina' } }" :class="['mobile-drawer__link', { 'is-active': isNavActive('nina') }]">
              <span class="mobile-drawer__icon mobile-drawer__icon--nina"><i class="fas fa-star"></i></span>
              Niñas
            </RouterLink>
          </li>
          <li>
            <RouterLink :to="{ name: 'store', query: { gender: 'nino' } }" :class="['mobile-drawer__link', { 'is-active': isNavActive('nino') }]">
              <span class="mobile-drawer__icon mobile-drawer__icon--nino"><i class="fas fa-rocket"></i></span>
              Niños
            </RouterLink>
          </li>
          <li>
            <RouterLink :to="{ name: 'store', query: { gender: 'bebe' } }" :class="['mobile-drawer__link', { 'is-active': isNavActive('bebe') }]">
              <span class="mobile-drawer__icon mobile-drawer__icon--bebe"><i class="fas fa-baby"></i></span>
              Bebés
            </RouterLink>
          </li>
          <li>
            <RouterLink :to="{ name: 'store', query: { offers: '1' } }" :class="['mobile-drawer__link', 'mobile-drawer__link--offers', { 'is-active': isNavActive('offers') }]">
              <span class="mobile-drawer__icon mobile-drawer__icon--offers"><i class="fas fa-tag"></i></span>
              Ofertas
            </RouterLink>
          </li>
          <li>
            <RouterLink :to="{ name: 'collections' }" :class="['mobile-drawer__link', { 'is-active': isNavActive('collections') }]">
              <span class="mobile-drawer__icon"><i class="fas fa-layer-group"></i></span>
              Colecciones
            </RouterLink>
          </li>
        </ul>

        <!-- Acciones de usuario al pie del drawer -->
        <div class="mobile-drawer__footer">
          <RouterLink :to="accountRoute" class="mobile-drawer__footer-btn">
            <i class="fas fa-user"></i>
            Mi cuenta
          </RouterLink>
          <RouterLink :to="favoritesRoute" class="mobile-drawer__footer-btn">
            <i class="fas fa-heart"></i>
            Favoritos
          </RouterLink>
          <RouterLink :to="notificationsRoute" class="mobile-drawer__footer-btn">
            <i class="fas fa-bell"></i>
            Notificaciones
            <span v-if="notificationCount > 0" class="mobile-drawer__count-badge">{{ notificationBadge }}</span>
          </RouterLink>
          <RouterLink to="/carrito" class="mobile-drawer__footer-btn mobile-drawer__footer-btn--cart">
            <i class="fas fa-shopping-cart"></i>
            Carrito
            <span v-if="cartCount > 0" class="mobile-drawer__cart-badge">{{ cartCount }}</span>
          </RouterLink>
        </div>
      </nav>
    </transition>
  </header>
</template>

<script setup>
// Importación de reactividad y ciclo de vida de Vue 3 (Composition API).
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue'
// Componentes y utilidades de enrutamiento de Vue Router.
import { RouterLink, useRoute, useRouter } from 'vue-router'
// Composable que expone el estado de la sesión del usuario (autenticación y datos).
import { useSession } from '../../composables/useSession'
import { resolveRoleLandingRoute } from '../../utils/authNavigation'
// Servicios de la API del catálogo para búsquedas: obtener sugerencias, historial y guardarlo.
import { getSearchHistory, getSearchSuggestions, saveSearchHistory } from '../../services/catalogApi'
// Utilidades para manejar errores de carga de imágenes y resolver URLs de medios.
import { handleMediaError, resolveMediaUrl } from '../../utils/media'
// Estilos CSS específicos del header.
import './Header.css'

// Definición de props que el componente Shell padre pasa al header.
const props = defineProps({
  settings: {
    type: Object,
    default: () => ({}),
  },
  cartCount: {
    type: Number,
    default: 0,
  },
  notificationCount: {
    type: Number,
    default: 0,
  },
  shellLoading: {
    type: Boolean,
    default: false,
  },
  initialSearch: {
    type: String,
    default: '',
  },
})

// Instancia del enrutador para navegación programática (push, replace, etc.).
const router = useRouter()
// Ruta actual reactiva, usada para determinar la sección activa del nav y cerrar menús al navegar.
const route = useRoute()
// Datos de sesión del usuario: isLoggedIn (booleano reactivo) y user (objeto con id, role, etc.).
const { isLoggedIn, user } = useSession()

// Valor del campo de búsqueda, sincronizado con la prop initialSearch.
const searchValue = ref(props.initialSearch)
// Sugerencias de productos devueltas por la API (resultado destacado + lista).
const searchSuggestions = ref([])
// Términos de sugerencia textual (autocomplete por texto) que aparecen debajo del producto destacado.
const searchTerms = ref([])
// Términos que el usuario ya ha buscado previamente (historial local o del servidor).
const searchedTerms = ref([])
// Indica si actualmente se está realizando una petición de búsqueda a la API.
const isSearching = ref(false)
// Controla la visibilidad del panel desplegable de sugerencias.
const showSuggestions = ref(false)
// Estado del menú hamburguesa móvil (abierto/cerrado).
const isMobileMenuOpen = ref(false)
// Referencia template ref al contenedor del formulario de búsqueda de escritorio (para detectar clics fuera).
const searchContainer = ref(null)
// Referencia template ref al contenedor del formulario de búsqueda móvil.
const mobileSearchContainer = ref(null)
// Referencia template ref al input de búsqueda de escritorio (para foco).
const searchInput = ref(null)
// Indica si la imagen del logo se ha cargado correctamente.
const logoLoaded = ref(false)
// Indica si la imagen del logo ha fallado al cargar.
const logoFailed = ref(false)
// Temporizador del debounce para la búsqueda: evita hacer peticiones en cada tecla.
let searchDebounceTimer = null
// Clave de localStorage para guardar el historial de búsquedas de usuarios no autenticados.
const GUEST_SEARCH_HISTORY_KEY = 'angelow_search_history'

// Watcher: sincroniza el valor del input de búsqueda cuando el Shell actualiza la prop initialSearch
// (por ejemplo, al llegar con un término pre-llenado desde otra página).
watch(
  () => props.initialSearch,
  (value) => {
    searchValue.value = value || ''
  },
)

// Watcher: al cambiar la ruta completa, cierra el menú móvil y oculta las sugerencias de búsqueda
// para evitar que queden abiertos paneles obsoletos tras navegar.
watch(
  () => route.fullPath,
  () => {
    isMobileMenuOpen.value = false
    hideSuggestions()
  },
)

// Watcher principal de búsqueda con debounce de 280ms.
// Cada vez que el usuario escribe, se cancela el temporizador anterior y se programa una nueva petición.
// Esto evita saturar la API con una solicitud por cada pulsación de tecla.
watch(searchValue, (value) => {
  // Cancelar cualquier temporizador de debounce anterior pendiente.
  if (searchDebounceTimer) {
    clearTimeout(searchDebounceTimer)
    searchDebounceTimer = null
  }

  // Normalizar el término: recortar espacios y verificar longitud mínima de 2 caracteres.
  const term = String(value || '').trim()
  if (term.length < 2) {
    // Si el término es muy corto, limpiar todo y ocultar sugerencias.
    searchSuggestions.value = []
    searchTerms.value = []
    showSuggestions.value = false
    isSearching.value = false
    return
  }

  // Mostrar panel de sugerencias y activar indicador de carga.
  showSuggestions.value = true
  isSearching.value = true

  // Programar la petición a la API después de 280ms de inactividad.
  searchDebounceTimer = setTimeout(async () => {
    try {
      // Llamar a la API de sugerencias pasando el término y el id del usuario (para historial personalizado).
      const response = await getSearchSuggestions(term, user.value?.id)

      // Guarda de carrera: si el usuario escribió algo más mientras se resolvía la petición,
      // descartar este resultado obsoleto.
      if (searchValue.value.trim() !== term) {
        return
      }

      // Asignar las sugerencias de productos (primer ítem es el destacado) y los términos textuales (máx. 4).
      searchSuggestions.value = response?.data?.suggestions || []
      searchTerms.value = (response?.data?.terms || []).slice(0, 4)
      showSuggestions.value = true
    } catch {
      // En caso de error de red o de la API, limpiar sugerencias si el término sigue siendo el mismo.
      if (searchValue.value.trim() === term) {
        searchSuggestions.value = []
        searchTerms.value = []
        showSuggestions.value = true
      }
    } finally {
      // Ocultar indicador de carga solo si el término no ha cambiado (evita parpadeos).
      if (searchValue.value.trim() === term) {
        isSearching.value = false
      }
    }
  }, 280)
})

// Ruta de la imagen del logo de la marca: prioriza la variante secundaria sobre la principal.
// Se usa trim() para evitar URLs con espacios en blanco que romperían la carga.
const brandLogoPath = computed(() => {
  return String(props.settings?.brand_logo_secondary || props.settings?.brand_logo || '').trim()
})

// URL final del logo resuelta a través de la utilidad resolveMediaUrl,
// que agrega la URL base del CDN o del backend según el contexto.
const brandLogo = computed(() => {
  return resolveMediaUrl(brandLogoPath.value, 'brand')
})

// Controla si se muestra la animación "shimmer" (placeholder brillante) mientras carga el logo.
// Se muestra si: el Shell está cargando, O hay ruta de logo pero aún no se ha cargado ni fallado.
const showLogoShimmer = computed(() => {
  if (props.shellLoading) return true
  if (!brandLogoPath.value) return false
  return !logoLoaded.value && !logoFailed.value
})

// Nombre de la tienda para el atributo alt del logo (accesibilidad).
const storeName = computed(() => props.settings?.store_name || 'Angelow')

// Ruta dinámica del enlace "Mi cuenta": redirige a login si no está autenticado,
// al dashboard de admin si tiene rol admin/super_admin, o al dashboard de cliente.
const accountRoute = computed(() => {
  if (!isLoggedIn.value) {
    return { name: 'login', query: { redirect: '/mi-cuenta/resumen' } }
  }

  return resolveRoleLandingRoute(user.value)
})

// Ruta de favoritos: redirige a login con redirect si el usuario no está autenticado.
const favoritesRoute = computed(() => (
  isLoggedIn.value
    ? { name: 'account-wishlist' }
    : { name: 'login', query: { redirect: '/favoritos' } }
))

// Ruta de notificaciones: misma lógica que favoritos pero con su propia ruta de redirección.
const notificationsRoute = computed(() => (
  isLoggedIn.value
    ? { name: 'account-notifications' }
    : { name: 'login', query: { redirect: '/mi-cuenta/notificaciones' } }
))

// Badge de notificaciones: muestra el número exacto si es <= 99, o "99+" si excede ese límite.
// Evita que el badge se desborde visualmente con números muy grandes.
const notificationBadge = computed(() => (props.notificationCount > 99 ? '99+' : props.notificationCount))

// Primer ítem de las sugerencias de búsqueda: se muestra como producto destacado en el panel.
const featuredSuggestion = computed(() => searchSuggestions.value[0] || null)

// Normaliza una lista de términos de búsqueda: convierte a minúsculas, elimina espacios
// y elimina duplicados usando un Set. Devuelve un array limpio y ordenado.
function normalizeHistoryTerms(terms) {
  return Array.from(new Set(
    (Array.isArray(terms) ? terms : [])
      .map((term) => String(term || '').trim().toLowerCase())
      .filter(Boolean),
  ))
}

// Lee el historial de búsquedas del localStorage para usuarios no autenticados (invitados).
// Usa try/catch porque localStorage podría estar bloqueado (modo incógnito, políticas de seguridad).
function getGuestSearchHistory() {
  try {
    return normalizeHistoryTerms(JSON.parse(localStorage.getItem(GUEST_SEARCH_HISTORY_KEY) || '[]'))
  } catch {
    return []
  }
}

// Guarda el historial de búsquedas del invitado en localStorage.
// Limita a 50 términos máximo para no saturar el almacenamiento local.
function setGuestSearchHistory(terms) {
  localStorage.setItem(GUEST_SEARCH_HISTORY_KEY, JSON.stringify(normalizeHistoryTerms(terms).slice(0, 50)))
}

// Carga el historial de búsquedas al montar el componente.
// Si el usuario está autenticado, lo obtiene de la API del servidor.
// Si es invitado, lo lee del localStorage.
async function hydrateSearchHistory() {
  if (isLoggedIn.value && user.value?.id) {
    try {
      const response = await getSearchHistory(user.value.id)
      searchedTerms.value = normalizeHistoryTerms(response?.data?.terms || [])
      return
    } catch {
      searchedTerms.value = []
      return
    }
  }

  searchedTerms.value = getGuestSearchHistory()
}

// Registra un término en el historial de búsquedas.
// Lo mueve al inicio de la lista (más reciente primero) y elimina duplicados.
// Si el usuario está autenticado, persiste en la API; si no, en localStorage.
function rememberSearchTerm(term) {
  const normalizedTerm = String(term || '').trim()
  // No guardar términos demasiado cortos (menos de 2 caracteres).
  if (normalizedTerm.length < 2) {
    return
  }

  const normalizedLower = normalizedTerm.toLowerCase()
  // Insertar al inicio y eliminar cualquier ocurrencia previa del mismo término.
  searchedTerms.value = [
    normalizedLower,
    ...searchedTerms.value.filter((item) => item !== normalizedLower),
  ].slice(0, 50)

  // Persistir según el estado de autenticación.
  if (isLoggedIn.value && user.value?.id) {
    // .catch vacío: si falla la petición, no afecta la experiencia del usuario.
    saveSearchHistory(normalizedTerm, user.value.id).catch(() => {
      // No bloquea la UX si falla la persistencia del historial.
    })
    return
  }

  setGuestSearchHistory(searchedTerms.value)
}

// Verifica si un término dado ya existe en el historial de búsquedas del usuario.
// Se usa para mostrar un ícono de reloj en lugar de una lupa en las sugerencias.
function wasTermSearched(term) {
  return searchedTerms.value.includes(String(term || '').trim().toLowerCase())
}

// Maneja el envío del formulario de búsqueda (Enter o clic en el botón de lupa).
// Oculta sugerencias, guarda el término en el historial y navega a la tienda con el filtro de búsqueda.
function onSearch() {
  const search = searchValue.value.trim()
  hideSuggestions()

  if (search) {
    rememberSearchTerm(search)
  }

  // Navegar a la vista 'store' con el parámetro de query search.
  // Si el búsqueda está vacía, se omite el parámetro para mostrar todos los productos.
  router.push({
    name: 'store',
    query: search ? { search } : {},
  })
}

// Maneja el foco del input de búsqueda.
// Si ya hay sugerencias cargadas o el texto tiene al menos 2 caracteres, reabre el panel.
function onSearchFocus() {
  if (searchSuggestions.value.length > 0 || searchTerms.value.length > 0 || (searchValue.value || '').trim().length >= 2) {
    showSuggestions.value = true
  }
}

// Oculta el panel desplegable de sugerencias de búsqueda.
function hideSuggestions() {
  showSuggestions.value = false
}

// Alterna el estado del menú hamburguesa móvil (abierto ↔ cerrado).
function toggleMobileMenu() {
  isMobileMenuOpen.value = !isMobileMenuOpen.value
}

// Cierra el menú móvil forzadamente (usado al hacer clic en el backdrop o al navegar).
function closeMobileMenu() {
  isMobileMenuOpen.value = false
}

// Determina si un enlace de navegación debe estar resaltado como "activo".
// Compara la ruta actual con la sección indicada usando el nombre de ruta y los query params.
function isNavActive(section) {
  const routeName = String(route.name || '')
  const gender = String(route.query?.gender || '').toLowerCase()
  const offers = String(route.query?.offers || '')

  // "Inicio" se activa solo en la ruta raíz exacta.
  if (section === 'inicio') {
    return route.path === '/'
  }

  // "Colecciones" se activa por nombre de ruta.
  if (section === 'collections') {
    return routeName === 'collections'
  }

  // "Ofertas" se activa cuando se está en la tienda con el parámetro offers=1.
  if (section === 'offers') {
    return routeName === 'store' && offers === '1'
  }

  // Categorías de género (Niñas, Niños, Bebés): se activan en la tienda con el gender correspondiente.
  if (section === 'nina' || section === 'nino' || section === 'bebe') {
    return routeName === 'store' && gender === section
  }

  return false
}

// Maneja el clic en un término de sugerencia de búsqueda.
// Rellena el input, guarda en historial, navega a la tienda y cierra el menú móvil.
function onSearchTermClick(term) {
  searchValue.value = term
  hideSuggestions()
  rememberSearchTerm(term)
  router.push({
    name: 'store',
    query: { search: term },
  })
  isMobileMenuOpen.value = false
}

// Maneja el clic en un producto destacado de las sugerencias.
// Guarda el nombre del producto en el historial y cierra el panel de sugerencias.
// La navegación ya se encarga el RouterLink con el slug del producto.
function onProductSuggestionClick(item) {
  rememberSearchTerm(item?.name || searchValue.value)
  hideSuggestions()
}

// Resuelve la URL de una imagen de producto usando la utilidad de medios centralizada.
function resolveImageUrl(path) {
  return resolveMediaUrl(path, 'product')
}

// Listener global de clics en el documento.
// Se usa para cerrar el panel de sugerencias cuando el usuario hace clic fuera del área de búsqueda.
// Se registra en onMounted y se elimina en onBeforeUnmount para evitar memory leaks.
function onDocumentClick(event) {
  const desktop = searchContainer.value
  const mobile = mobileSearchContainer.value
  const clickedInsideDesktop = desktop && desktop.contains(event.target)
  const clickedInsideMobile = mobile && mobile.contains(event.target)
  // Si el clic fue fuera de ambos contenedores de búsqueda, ocultar sugerencias.
  if (!clickedInsideDesktop && !clickedInsideMobile) {
    hideSuggestions()
  }
}

// Hook de ciclo de vida: antes de desmontar el componente.
// Limpia el temporizador de debounce y elimina el listener global de clics para evitar fugas de memoria.
onBeforeUnmount(() => {
  if (searchDebounceTimer) {
    clearTimeout(searchDebounceTimer)
    searchDebounceTimer = null
  }
  document.removeEventListener('mousedown', onDocumentClick)
})

// Hook de ciclo de vida: al montar el componente.
// Carga el historial de búsquedas del usuario (o del invitado) y registra el listener global de clics.
onMounted(() => {
  hydrateSearchHistory()
  document.addEventListener('mousedown', onDocumentClick)
})

// Watcher: cuando cambia el id del usuario (login/logout), recargar el historial de búsquedas.
// Así se obtiene el historial del servidor al iniciar sesión y se limpia al cerrar sesión.
watch(
  () => user.value?.id,
  () => {
    hydrateSearchHistory()
  },
)

// Watcher: cuando cambia la ruta de la imagen del logo (por ejemplo, al actualizar settings),
// resetear los estados de carga/error para que se muestre el shimmer de nuevo.
// El immediate: true hace que se ejecute también al montar el componente.
watch(
  () => brandLogoPath.value,
  () => {
    logoLoaded.value = false
    logoFailed.value = false
  },
  { immediate: true },
)

// Callback del evento load de la imagen del logo: marca que se cargó correctamente.
function onLogoLoad() {
  logoLoaded.value = true
}

// Callback del evento error de la imagen del logo: marca que falló y reporta el error a la utilidad centralizada.
function onLogoError(event) {
  logoFailed.value = true
  handleMediaError(event, brandLogoPath.value, 'brand')
}
</script>
