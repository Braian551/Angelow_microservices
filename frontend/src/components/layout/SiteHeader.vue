<template>
  <header class="main-header">
    <div class="header-container">
      <div class="content-logo2">
        <RouterLink to="/">
          <img :src="brandLogo" :alt="storeName" width="100" />
        </RouterLink>
      </div>

      <div class="search-bar">
        <form class="search-form" @submit.prevent="onSearch">
          <input
            id="header-search"
            v-model="searchValue"
            type="text"
            placeholder="Buscar productos..."
            autocomplete="off"
          />
          <button type="submit" aria-label="Buscar">
            <i class="fas fa-search" />
          </button>
        </form>
      </div>

      <div class="header-icons">
        <RouterLink to="/mi-cuenta/pedidos" aria-label="Mi cuenta">
          <i class="fas fa-user" />
        </RouterLink>
        <RouterLink to="/carrito" aria-label="Carrito" class="cart-link">
          <i class="fas fa-shopping-cart" />
          <span v-if="cartCount > 0" class="cart-count">{{ cartCount }}</span>
        </RouterLink>
      </div>
    </div>

    <nav class="main-nav">
      <ul>
        <li><RouterLink to="/">Inicio</RouterLink></li>
        <li><RouterLink :to="{ name: 'store', query: { gender: 'nina' } }">Ninas</RouterLink></li>
        <li><RouterLink :to="{ name: 'store', query: { gender: 'nino' } }">Ninos</RouterLink></li>
        <li><RouterLink :to="{ name: 'store', query: { gender: 'bebe' } }">Bebes</RouterLink></li>
        <li><RouterLink :to="{ name: 'store', query: { offers: '1' } }">Ofertas</RouterLink></li>
      </ul>
    </nav>
  </header>
</template>

<script setup>
import { computed, ref, watch } from 'vue'
import { RouterLink, useRouter } from 'vue-router'

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
const searchValue = ref(props.initialSearch)

watch(
  () => props.initialSearch,
  (value) => {
    searchValue.value = value || ''
  },
)

const brandLogo = computed(() => {
  if (props.settings?.brand_logo) {
    if (props.settings.brand_logo.startsWith('http')) return props.settings.brand_logo
    if (props.settings.brand_logo.startsWith('/')) return props.settings.brand_logo
    return `/${props.settings.brand_logo}`
  }
  return '/logo.png'
})

const storeName = computed(() => props.settings?.store_name || 'Angelow')

function onSearch() {
  const search = searchValue.value.trim()
  router.push({
    name: 'store',
    query: search ? { search } : {},
  })
}
</script>
