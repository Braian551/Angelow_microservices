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

      <!-- Iconos de cuenta / favoritos / carrito -->
      <div class="header-icons">
        <RouterLink :to="accountRoute" aria-label="Mi cuenta">
          <i class="fas fa-user" />
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
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue'
import { RouterLink, useRoute, useRouter } from 'vue-router'
import { useSession } from '../../composables/useSession'
import { getSearchHistory, getSearchSuggestions, saveSearchHistory } from '../../services/catalogApi'
import { handleMediaError, resolveMediaUrl } from '../../utils/media'
import './Header.css'

const props = defineProps({
  settings: {
    type: Object,
    default: () => ({}),
  },
  cartCount: {
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

const router = useRouter()
const route = useRoute()
const { isLoggedIn, user } = useSession()
const searchValue = ref(props.initialSearch)
const searchSuggestions = ref([])
const searchTerms = ref([])
const searchedTerms = ref([])
const isSearching = ref(false)
const showSuggestions = ref(false)
const isMobileMenuOpen = ref(false)
const searchContainer = ref(null)
const mobileSearchContainer = ref(null)
const searchInput = ref(null)
const logoLoaded = ref(false)
const logoFailed = ref(false)
let searchDebounceTimer = null
const GUEST_SEARCH_HISTORY_KEY = 'angelow_search_history'

watch(
  () => props.initialSearch,
  (value) => {
    searchValue.value = value || ''
  },
)

watch(
  () => route.fullPath,
  () => {
    isMobileMenuOpen.value = false
    hideSuggestions()
  },
)

watch(searchValue, (value) => {
  if (searchDebounceTimer) {
    clearTimeout(searchDebounceTimer)
    searchDebounceTimer = null
  }

  const term = String(value || '').trim()
  if (term.length < 2) {
    searchSuggestions.value = []
    searchTerms.value = []
    showSuggestions.value = false
    isSearching.value = false
    return
  }

  showSuggestions.value = true
  isSearching.value = true

  searchDebounceTimer = setTimeout(async () => {
    try {
      const response = await getSearchSuggestions(term, user.value?.id)

      if (searchValue.value.trim() !== term) {
        return
      }

      searchSuggestions.value = response?.data?.suggestions || []
      searchTerms.value = (response?.data?.terms || []).slice(0, 4)
      showSuggestions.value = true
    } catch {
      if (searchValue.value.trim() === term) {
        searchSuggestions.value = []
        searchTerms.value = []
        showSuggestions.value = true
      }
    } finally {
      if (searchValue.value.trim() === term) {
        isSearching.value = false
      }
    }
  }, 280)
})

const brandLogoPath = computed(() => {
  return String(props.settings?.brand_logo_secondary || props.settings?.brand_logo || '').trim()
})

const brandLogo = computed(() => {
  return resolveMediaUrl(brandLogoPath.value, 'brand')
})

const showLogoShimmer = computed(() => {
  if (props.shellLoading) return true
  if (!brandLogoPath.value) return false
  return !logoLoaded.value && !logoFailed.value
})

const storeName = computed(() => props.settings?.store_name || 'Angelow')

const accountRoute = computed(() => {
  if (!isLoggedIn.value) {
    return { name: 'login', query: { redirect: '/mi-cuenta/resumen' } }
  }

  const role = String(user.value?.role || '').toLowerCase()
  if (role === 'admin' || role === 'super_admin') {
    return { name: 'admin-dashboard' }
  }

  return { name: 'account-dashboard' }
})

const favoritesRoute = computed(() => (
  isLoggedIn.value
    ? { name: 'account-wishlist' }
    : { name: 'login', query: { redirect: '/favoritos' } }
))

const featuredSuggestion = computed(() => searchSuggestions.value[0] || null)

function normalizeHistoryTerms(terms) {
  return Array.from(new Set(
    (Array.isArray(terms) ? terms : [])
      .map((term) => String(term || '').trim().toLowerCase())
      .filter(Boolean),
  ))
}

function getGuestSearchHistory() {
  try {
    return normalizeHistoryTerms(JSON.parse(localStorage.getItem(GUEST_SEARCH_HISTORY_KEY) || '[]'))
  } catch {
    return []
  }
}

function setGuestSearchHistory(terms) {
  localStorage.setItem(GUEST_SEARCH_HISTORY_KEY, JSON.stringify(normalizeHistoryTerms(terms).slice(0, 50)))
}

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

function rememberSearchTerm(term) {
  const normalizedTerm = String(term || '').trim()
  if (normalizedTerm.length < 2) {
    return
  }

  const normalizedLower = normalizedTerm.toLowerCase()
  searchedTerms.value = [
    normalizedLower,
    ...searchedTerms.value.filter((item) => item !== normalizedLower),
  ].slice(0, 50)

  if (isLoggedIn.value && user.value?.id) {
    saveSearchHistory(normalizedTerm, user.value.id).catch(() => {
      // No bloquea la UX si falla la persistencia del historial.
    })
    return
  }

  setGuestSearchHistory(searchedTerms.value)
}

function wasTermSearched(term) {
  return searchedTerms.value.includes(String(term || '').trim().toLowerCase())
}

function onSearch() {
  const search = searchValue.value.trim()
  hideSuggestions()

  if (search) {
    rememberSearchTerm(search)
  }

  router.push({
    name: 'store',
    query: search ? { search } : {},
  })
}

function onSearchFocus() {
  if (searchSuggestions.value.length > 0 || searchTerms.value.length > 0 || (searchValue.value || '').trim().length >= 2) {
    showSuggestions.value = true
  }
}

function hideSuggestions() {
  showSuggestions.value = false
}

function toggleMobileMenu() {
  isMobileMenuOpen.value = !isMobileMenuOpen.value
}

function closeMobileMenu() {
  isMobileMenuOpen.value = false
}

function isNavActive(section) {
  const routeName = String(route.name || '')
  const gender = String(route.query?.gender || '').toLowerCase()
  const offers = String(route.query?.offers || '')

  if (section === 'inicio') {
    return route.path === '/'
  }

  if (section === 'collections') {
    return routeName === 'collections'
  }

  if (section === 'offers') {
    return routeName === 'store' && offers === '1'
  }

  if (section === 'nina' || section === 'nino' || section === 'bebe') {
    return routeName === 'store' && gender === section
  }

  return false
}

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

function onProductSuggestionClick(item) {
  rememberSearchTerm(item?.name || searchValue.value)
  hideSuggestions()
}

function resolveImageUrl(path) {
  return resolveMediaUrl(path, 'product')
}

function onDocumentClick(event) {
  const desktop = searchContainer.value
  const mobile = mobileSearchContainer.value
  const clickedInsideDesktop = desktop && desktop.contains(event.target)
  const clickedInsideMobile = mobile && mobile.contains(event.target)
  if (!clickedInsideDesktop && !clickedInsideMobile) {
    hideSuggestions()
  }
}

onBeforeUnmount(() => {
  if (searchDebounceTimer) {
    clearTimeout(searchDebounceTimer)
    searchDebounceTimer = null
  }
  document.removeEventListener('mousedown', onDocumentClick)
})

onMounted(() => {
  hydrateSearchHistory()
  document.addEventListener('mousedown', onDocumentClick)
})

watch(
  () => user.value?.id,
  () => {
    hydrateSearchHistory()
  },
)

watch(
  () => brandLogoPath.value,
  () => {
    logoLoaded.value = false
    logoFailed.value = false
  },
  { immediate: true },
)

function onLogoLoad() {
  logoLoaded.value = true
}

function onLogoError(event) {
  logoFailed.value = true
  handleMediaError(event, brandLogoPath.value, 'brand')
}
</script>
