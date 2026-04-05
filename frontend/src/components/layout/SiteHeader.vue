<template>
  <header class="main-header">
    <div class="header-container">
      <button class="mobile-menu-toggle" @click="toggleMobileMenu" aria-label="Menu">
        <i class="fas" :class="isMobileMenuOpen ? 'fa-times' : 'fa-bars'"></i>
      </button>

      <div class="content-logo2">
        <RouterLink to="/">
          <img :src="brandLogo" :alt="storeName" width="100" @error="onLogoError" />
        </RouterLink>
      </div>

      <div class="search-bar">
        <form ref="searchContainer" class="search-form" @submit.prevent="onSearch" autocomplete="off">
          <input
            ref="searchInput"
            id="angelow-site-search"
            name="angelow_site_search_query"
            v-model="searchValue"
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

          <div
            v-if="showSuggestions"
            class="header-search-results"
          >
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

    <nav class="main-nav" :class="{ 'is-open': isMobileMenuOpen }">
      <ul>
        <li><RouterLink to="/">Inicio</RouterLink></li>
        <li><RouterLink :to="{ name: 'store', query: { gender: 'nina' } }">Niñas</RouterLink></li>
        <li><RouterLink :to="{ name: 'store', query: { gender: 'nino' } }">Niños</RouterLink></li>
        <li><RouterLink :to="{ name: 'store', query: { gender: 'bebe' } }">Bebés</RouterLink></li>
        <li><RouterLink :to="{ name: 'store', query: { offers: '1' } }">Ofertas</RouterLink></li>
        <li><RouterLink :to="{ name: 'collections' }">Colecciones</RouterLink></li>
      </ul>
    </nav>
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
const searchInput = ref(null)
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
      searchTerms.value = response?.data?.terms || []
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

const brandLogo = computed(() => {
  return resolveMediaUrl(props.settings?.brand_logo, 'brand')
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

onBeforeUnmount(() => {
  if (searchDebounceTimer) {
    clearTimeout(searchDebounceTimer)
    searchDebounceTimer = null
  }
})

onMounted(() => {
  hydrateSearchHistory()
})

watch(
  () => user.value?.id,
  () => {
    hydrateSearchHistory()
  },
)

function onLogoError(event) {
  handleMediaError(event, props.settings?.brand_logo, 'brand')
}
</script>
