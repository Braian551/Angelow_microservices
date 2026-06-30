<template>
  <article class="product-card" :data-product-id="product.id">
    <!-- Badge superior para productos destacados por catálogo. -->
    <div v-if="product.is_featured" class="product-badge">Destacado</div>

    <!-- Acción rápida de favoritos con bloqueo mientras se sincroniza con catálogo. -->
    <button
      type="button"
      class="wishlist-btn"
      :class="{ active: isFavorite }"
      aria-label="Añadir a favoritos"
      :disabled="wishlistBusy"
      @click="onToggleWishlist"
    >
      <i :class="isFavorite ? 'fas fa-heart' : 'far fa-heart'" />
    </button>

    <!-- Imagen navegable del producto con fallback visual y descuento calculado. -->
    <RouterLink :to="{ name: 'product', params: { slug: product.slug } }" :class="['product-image', { loading: !imageLoaded }]">
      <img :src="imageUrl" :alt="product.name" :class="{ loaded: imageLoaded }" @load="onImageLoad" @error="onImageError" />
      <div v-if="showComparePrice" class="product-badge sale">{{ discountPercentage }}% OFF</div>
    </RouterLink>

    <!-- Información comercial: categoría, nombre, reseñas, precio y navegación al detalle. -->
    <div class="product-info">
      <span class="product-category">{{ categoryName }}</span>
      <h3 class="product-title">
        <RouterLink :to="{ name: 'product', params: { slug: product.slug } }">
          {{ product.name }}
        </RouterLink>
      </h3>

      <div class="product-rating">
        <div class="stars">
          <i v-for="(starClass, index) in starClasses" :key="index" :class="starClass" />
        </div>
        <span class="rating-count">({{ reviewCount }})</span>
      </div>

      <div class="product-price">
        <span class="current-price">{{ formatPrice(product.price) }}</span>
        <span v-if="showComparePrice" class="original-price">{{ formatPrice(product.compare_price) }}</span>
      </div>

      <RouterLink :to="{ name: 'product', params: { slug: product.slug } }" class="view-product-btn" @click="emit('add-cart', product)">
        <i class="fas fa-eye" /> Ver producto
      </RouterLink>
    </div>
  </article>
</template>

<script setup>
import { computed, ref, watch } from 'vue'
import { RouterLink, useRouter } from 'vue-router'
import { useSession } from '../../../composables/useSession'
import { useSnackbarSystem } from '../../../composables/useSnackbarSystem'
import { toggleWishlist } from '../../../services/wishlistApi'
import { handleMediaError, resolveMediaUrl } from '../../../utils/media'

// La tarjeta recibe el producto normalizado desde tienda, home o listados relacionados.
const props = defineProps({
  product: {
    type: Object,
    required: true,
  },
})

const emit = defineEmits(['add-cart', 'wishlist-change'])

// Servicios de navegación, sesión y feedback usados por las acciones de la tarjeta.
const router = useRouter()
const { user, isLoggedIn } = useSession()
const { showSnackbar } = useSnackbarSystem()

// Estado local de carga de imagen y sincronización de favorito.
const imageLoaded = ref(false)
const isFavorite = ref(false)
const wishlistBusy = ref(false)

// Reinicia el estado de imagen cuando cambia la imagen principal del producto.
watch(
  () => props.product.primary_image,
  () => {
    imageLoaded.value = false
  },
  { immediate: true },
)

// Mantiene el corazón local alineado con el estado favorito recibido por props.
watch(
  () => props.product.is_favorite,
  (value) => {
    isFavorite.value = Boolean(Number(value || 0))
  },
  { immediate: true },
)

// Valores derivados para resolver imagen, categoría y conteo de reseñas.
const imageUrl = computed(() => resolveMediaUrl(props.product.primary_image, 'product'))
const categoryName = computed(() => props.product.category_name || 'Sin categoría')
const reviewCount = computed(() => Number(props.product.review_count || 0))

// Determina si existe precio comparativo válido para mostrar descuento.
const showComparePrice = computed(() => {
  const current = Number(props.product.price || 0)
  const compare = Number(props.product.compare_price || 0)
  return compare > 0 && compare > current
})

// Calcula el porcentaje de descuento mostrado sobre la imagen.
const discountPercentage = computed(() => {
  if (!showComparePrice.value) return 0

  const current = Number(props.product.price || 0)
  const compare = Number(props.product.compare_price || 0)
  if (compare <= 0) return 0

  return Math.max(1, Math.round(((compare - current) / compare) * 100))
})

// Convierte el promedio de reseñas en clases Font Awesome para estrellas completas, medias y vacías.
const starClasses = computed(() => {
  const avg = Number(props.product.avg_rating || 0)
  const fullStars = Math.floor(avg)
  const hasHalfStar = avg - fullStars >= 0.5
  const stars = []

  for (let index = 0; index < 5; index += 1) {
    if (index < fullStars) {
      stars.push('fas fa-star')
      continue
    }

    if (index === fullStars && hasHalfStar) {
      stars.push('fas fa-star-half-alt')
      continue
    }

    stars.push('far fa-star')
  }

  return stars
})

// Marca la imagen como cargada para retirar el estado visual de loading.
function onImageLoad() {
  imageLoaded.value = true
}

// Aplica fallback de imagen y evita que la tarjeta quede en estado de carga permanente.
function onImageError(event) {
  handleMediaError(event, props.product.primary_image, 'product')
  imageLoaded.value = true
}

// Alterna favorito; si no hay sesión, redirige a login conservando la ruta actual.
async function onToggleWishlist() {
  if (wishlistBusy.value) return

  if (!isLoggedIn.value || !user.value?.id) {
    router.push({
      name: 'login',
      query: { redirect: router.currentRoute.value.fullPath },
    })
    return
  }

  wishlistBusy.value = true

  try {
    const response = await toggleWishlist({
      user_id: String(user.value.id),
      user_email: String(user.value?.email || '').trim() || undefined,
      product_id: Number(props.product.id),
    })

    isFavorite.value = response?.action === 'added'
    showSnackbar({
      type: 'success',
      title: isFavorite.value ? 'Agregado a favoritos' : 'Eliminado de favoritos',
      message: isFavorite.value ? 'El producto se guardo en tu lista.' : 'El producto se quito de tu lista.',
      durationMs: 1800,
    })

    emit('wishlist-change', {
      productId: Number(props.product.id),
      isFavorite: isFavorite.value,
    })
  } catch {
    showSnackbar({
      type: 'error',
      title: 'No se pudo actualizar favorito',
      message: 'Intenta nuevamente en unos segundos.',
    })
  } finally {
    wishlistBusy.value = false
  }
}

// Formatea precios en COP sin decimales para mantener consistencia comercial.
function formatPrice(value) {
  return new Intl.NumberFormat('es-CO', {
    style: 'currency',
    currency: 'COP',
    maximumFractionDigits: 0,
  }).format(Number(value || 0))
}
</script>
