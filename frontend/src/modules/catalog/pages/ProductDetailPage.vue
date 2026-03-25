<template>
  <SiteHeader :settings="settings" :cart-count="cartCount" />

  <main class="section-container product-page">
    <p v-if="loading" class="loading-box">Cargando producto...</p>
    <p v-else-if="errorMessage" class="error-box">{{ errorMessage }}</p>

    <section v-else class="product-layout">
      <div class="product-gallery">
        <img :src="mainImage" :alt="product.name" />
      </div>

      <div class="product-panel">
        <h1>{{ product.name }}</h1>
        <p class="product-category-line">{{ product.category_name }}</p>
        <p class="product-price-line">{{ formatPrice(activePrice) }}</p>
        <p class="product-description">{{ product.description || 'Sin descripcion disponible.' }}</p>

        <div v-if="colorOptions.length > 0" class="variant-group">
          <label>Color</label>
          <select v-model="selectedColorId">
            <option v-for="color in colorOptions" :key="color.color_variant_id" :value="color.color_variant_id">
              {{ color.color_name || 'Color' }}
            </option>
          </select>
        </div>

        <div v-if="sizeOptions.length > 0" class="variant-group">
          <label>Talla</label>
          <select v-model="selectedSizeVariantId">
            <option v-for="size in sizeOptions" :key="size.variant_id" :value="size.variant_id">
              {{ size.size_name }} (stock: {{ size.quantity }})
            </option>
          </select>
        </div>

        <div class="variant-group">
          <label>Cantidad</label>
          <input v-model.number="quantity" type="number" min="1" />
        </div>

        <p v-if="infoMessage" class="status-message">{{ infoMessage }}</p>

        <div class="product-actions">
          <button type="button" class="btn" @click="addItemToCart">Agregar al carrito</button>
          <RouterLink :to="{ name: 'cart' }" class="btn">Ver carrito</RouterLink>
        </div>
      </div>
    </section>
  </main>

  <SiteFooter :settings="settings" />
</template>

<script setup>
import { computed, onMounted, ref, watch } from 'vue'
import { RouterLink, useRoute } from 'vue-router'
import SiteHeader from '../../../components/layout/SiteHeader.vue'
import SiteFooter from '../../../components/layout/SiteFooter.vue'
import { getHomeData, getProductBySlug } from '../../../services/catalogApi'
import { addToCart, getCart } from '../../../services/cartApi'
import { useSession } from '../../../composables/useSession'
import { resolveMediaUrl } from '../../../utils/media'

const route = useRoute()
const { sessionId, user } = useSession()

const loading = ref(true)
const errorMessage = ref('')
const infoMessage = ref('')
const settings = ref({})
const product = ref({})
const variants = ref({})
const cartCount = ref(0)
const selectedColorId = ref(null)
const selectedSizeVariantId = ref(null)
const quantity = ref(1)

const colorOptions = computed(() => Object.values(variants.value || {}))

const activeColor = computed(() => {
  if (!selectedColorId.value) return colorOptions.value[0] || null
  return colorOptions.value.find((item) => item.color_variant_id === selectedColorId.value) || null
})

const sizeOptions = computed(() => {
  const sizesMap = activeColor.value?.sizes || {}
  return Object.values(sizesMap)
})

const activeSize = computed(() => {
  if (!selectedSizeVariantId.value) return sizeOptions.value[0] || null
  return sizeOptions.value.find((item) => item.variant_id === selectedSizeVariantId.value) || null
})

const mainImage = computed(() => {
  const colorImage = activeColor.value?.images?.[0]?.image_path
  return resolveMediaUrl(colorImage || product.value.primary_image, '/logo_principal.png')
})

const activePrice = computed(() => Number(activeSize.value?.price || product.value.price || 0))

watch(colorOptions, (items) => {
  if (!items.length) return
  selectedColorId.value = items[0].color_variant_id
}, { immediate: true })

watch(sizeOptions, (items) => {
  if (!items.length) return
  selectedSizeVariantId.value = items[0].variant_id
}, { immediate: true })

async function loadData() {
  loading.value = true
  errorMessage.value = ''

  try {
    const [homeRes, productRes, cartRes] = await Promise.all([
      getHomeData(),
      getProductBySlug(route.params.slug),
      getCart({
        user_id: user.value?.id || undefined,
        session_id: user.value?.id ? undefined : sessionId.value,
      }),
    ])

    settings.value = homeRes?.data?.settings || {}
    product.value = productRes?.data?.product || {}
    variants.value = productRes?.data?.variants || {}
    cartCount.value = Number(cartRes?.data?.item_count || 0)
  } catch {
    errorMessage.value = 'No se pudo cargar el producto.'
  } finally {
    loading.value = false
  }
}

async function addItemToCart() {
  infoMessage.value = ''
  if (!selectedSizeVariantId.value) {
    infoMessage.value = 'Selecciona una talla valida.'
    return
  }

  try {
    const payload = {
      product_id: Number(product.value.id),
      color_variant_id: selectedColorId.value ? Number(selectedColorId.value) : null,
      size_variant_id: Number(selectedSizeVariantId.value),
      quantity: Number(quantity.value || 1),
      user_id: user.value?.id || null,
      session_id: user.value?.id ? null : sessionId.value,
    }

    await addToCart(payload)
    const cartRes = await getCart({
      user_id: user.value?.id || undefined,
      session_id: user.value?.id ? undefined : sessionId.value,
    })
    cartCount.value = Number(cartRes?.data?.item_count || cartCount.value)
    infoMessage.value = 'Producto agregado al carrito.'
  } catch {
    infoMessage.value = 'No se pudo agregar al carrito.'
  }
}

function formatPrice(value) {
  return new Intl.NumberFormat('es-CO', {
    style: 'currency',
    currency: 'COP',
    maximumFractionDigits: 0,
  }).format(Number(value || 0))
}

onMounted(loadData)
</script>

<style scoped>
.product-layout {
  display: grid;
  grid-template-columns: minmax(0, 1fr) minmax(0, 1fr);
  gap: 2rem;
  margin-top: 2rem;
}

.product-gallery img {
  width: 100%;
  border-radius: 12px;
  object-fit: cover;
}

.product-panel {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.variant-group {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.variant-group input,
.variant-group select {
  padding: 0.75rem;
  border: 1px solid #d1d5db;
  border-radius: 8px;
}

.product-actions {
  display: flex;
  gap: 1rem;
  margin-top: 0.5rem;
}

.status-message {
  color: #1a8fc4;
}

@media (max-width: 900px) {
  .product-layout {
    grid-template-columns: 1fr;
  }
}
</style>
