<!-- 
  DashboardPage.vue
  Página principal del panel de usuario autenticado.
  Muestra un resumen de pedidos, direcciones, favoritos y recomendaciones.
-->
<template>
  <!-- Estado de carga: se muestra un shimmer (placeholder animado) mientras se obtienen los datos del servidor -->
  <AccountShimmer v-if="loading" variant="dashboard" />

  <!-- Contenido principal: se renderiza solo cuando loading es false -->
  <template v-else>
    <!-- Encabezado del dashboard: saludo y descripción de las funcionalidades disponibles -->
    <section class="dashboard-header">
      <h1>Bienvenido a tu cuenta</h1>
      <p>Aquí puedes gestionar tus pedidos, direcciones y preferencias.</p>
    </section>

    <!-- Sección de tarjetas resumen: muestra contadores de pedidos, direcciones y favoritos -->
    <section class="dashboard-summary">
      <!-- Tarjeta de resumen: pedidos del usuario -->
      <article class="summary-card">
        <div class="summary-icon">
          <i class="fas fa-shopping-bag" />
        </div>
        <div class="summary-content">
          <h3>Pedidos</h3>
          <!-- ordersCountLabel genera texto pluralizado según la cantidad de pedidos -->
          <p>{{ ordersCountLabel }}</p>
          <!-- Enlace a la página de historial de pedidos -->
          <RouterLink :to="{ name: 'account-orders' }">Ver historial</RouterLink>
        </div>
      </article>

      <!-- Tarjeta de resumen: direcciones guardadas del usuario -->
      <article class="summary-card">
        <div class="summary-icon">
          <i class="fas fa-map-marker-alt" />
        </div>
        <div class="summary-content">
          <h3>Direcciones</h3>
          <!-- addressesCountLabel genera texto pluralizado según la cantidad de direcciones -->
          <p>{{ addressesCountLabel }}</p>
          <!-- Enlace a la página de gestión de direcciones -->
          <RouterLink :to="{ name: 'account-addresses' }">Gestionar direcciones</RouterLink>
        </div>
      </article>

      <!-- Tarjeta de resumen: productos en lista de favoritos -->
      <article class="summary-card">
        <div class="summary-icon">
          <i class="fas fa-heart" />
        </div>
        <div class="summary-content">
          <h3>Favoritos</h3>
          <!-- favoritesCountLabel genera texto pluralizado según la cantidad de productos guardados -->
          <p>{{ favoritesCountLabel }}</p>
          <!-- Enlace a la página de lista de deseos -->
          <RouterLink :to="{ name: 'account-wishlist' }">Ver favoritos</RouterLink>
        </div>
      </article>
    </section>

    <!-- Sección de pedidos recientes: muestra los últimos 3 pedidos del usuario -->
    <section class="account-card">
      <header class="section-header">
        <h2>Pedidos recientes</h2>
        <!-- Enlace para ver todos los pedidos -->
        <RouterLink :to="{ name: 'account-orders' }" class="view-all">Ver todos</RouterLink>
      </header>

      <!-- Mensaje de error: se muestra si falló la carga de datos del servidor -->
      <p v-if="errorMessage" class="error-box">{{ errorMessage }}</p>

      <!-- Estado vacío: se muestra cuando no existen pedidos registrados -->
      <div v-else-if="recentOrders.length === 0" class="empty-state">
        <i class="fas fa-box-open" />
        <p>Aún no has realizado ningún pedido.</p>
        <!-- Enlace para redirigir al catálogo de la tienda -->
        <RouterLink :to="{ name: 'store' }" class="btn-primary-small">Ir a la tienda</RouterLink>
      </div>

      <!-- Lista de pedidos recientes: se renderiza solo si hay al menos un pedido -->
      <div v-else class="orders-list">
        <!-- Bucle que itera sobre los últimos 3 pedidos del usuario -->
        <article v-for="order in recentOrders" :key="order.id" class="order-card">
          <div class="order-header">
            <div class="order-title">
              <!-- Número de pedido formateado para mostrar al usuario -->
              <h3>Pedido #{{ order.order_number }}</h3>
              <!-- Fecha de creación del pedido formateada según locale colombiano -->
              <span class="order-date">{{ formatDate(order.created_at) }}</span>
            </div>
            <!-- Badge de estado con clase CSS dinámica según el estado del pedido -->
            <span class="status-badge" :class="statusClass(order.status)">{{ statusLabel(order.status) }}</span>
          </div>

          <div class="order-details">
            <div class="order-info">
              <i class="fas fa-box" />
              <!-- Cantidad de productos en el pedido, con fallback a 0 -->
              <span>{{ order.items_count || 0 }} producto(s)</span>
            </div>
            <!-- Total del pedido formateado como moneda colombiana (COP) -->
            <div class="order-total">{{ formatPrice(order.total) }}</div>
          </div>

          <div class="order-actions">
            <!-- Enlace a los detalles del pedido específico, pasando el ID como query parameter -->
            <RouterLink :to="{ name: 'account-orders', query: { order: order.id } }" class="btn-view-order">
              Ver detalles
            </RouterLink>
          </div>
        </article>
      </div>
    </section>

    <!-- Sección de productos favoritos: solo se muestra si el usuario tiene al menos 1 favorito -->
    <section v-if="favoriteProducts.length > 0" class="account-card">
      <header class="section-header">
        <h2>Tus favoritos</h2>
        <!-- Enlace para ver la lista completa de favoritos -->
        <RouterLink :to="{ name: 'account-wishlist' }" class="view-all">Ver favoritos</RouterLink>
      </header>

      <!-- Cuadrícula de productos: muestra los primeros 6 productos favoritos -->
      <div class="products-grid">
        <!-- Bucle que renderiza un ProductCard por cada producto favorito (máximo 6) -->
        <ProductCard
          v-for="product in favoriteProducts"
          :key="product.id"
          :product="product"
          @add-cart="openProduct"
          @wishlist-change="onWishlistChange"
        />
      </div>
    </section>

    <!-- Sección de recomendaciones: solo se muestra si hay productos recomendados que no están en favoritos -->
    <section v-if="recommendedShowcase.length > 0" class="account-card">
      <header class="section-header">
        <h2>Recomendaciones para ti</h2>
        <!-- Enlace para explorar el catálogo completo de la tienda -->
        <RouterLink :to="{ name: 'store' }" class="view-all">Ver tienda</RouterLink>
      </header>

      <!-- Cuadrícula de productos recomendados -->
      <div class="products-grid">
        <!-- Bucle que renderiza un ProductCard por cada producto recomendado (máximo 6, excluye favoritos) -->
        <ProductCard
          v-for="product in recommendedShowcase"
          :key="product.id"
          :product="product"
          @add-cart="openProduct"
          @wishlist-change="onWishlistChange"
        />
      </div>
    </section>
  </template>
</template>

<script setup>
// ─── Importaciones de Vue ───────────────────────────────────────────────────────
// computed: crea propiedades calculadas reactivas que se actualizan automáticamente
// onMounted: hook del ciclo de vida que se ejecuta cuando el componente se monta en el DOM
// ref: crea una referencia reactiva que puede contener un valor escalar u objeto
import { computed, onMounted, ref } from 'vue'

// RouterLink: componente para generar enlaces de navegación declarativos
// useRouter: composposable que permite acceder a la instancia del router para navegación programática
import { RouterLink, useRouter } from 'vue-router'

// ─── Importaciones de componentes ───────────────────────────────────────────────
// ProductCard: componente reutilizable que renderiza la información de un producto en tarjeta
import ProductCard from '../../catalog/components/ProductCard.vue'

// AccountShimmer: componente de efecto shimmer (placeholder animado) para el estado de carga
import AccountShimmer from '../components/AccountShimmer.vue'

// ─── Importaciones de servicios API ─────────────────────────────────────────────
// Servicio para obtener productos del catálogo (se usa para mostrar recomendaciones)
import { getProducts } from '../../../services/catalogApi'

// Servicio para obtener el historial de pedidos del usuario autenticado
import { getOrders } from '../../../services/orderApi'

// Servicio para obtener las direcciones de envío registradas del usuario
import { getUserAddresses } from '../../../services/shippingApi'

// Servicio para obtener la lista de productos marcados como favoritos (wishlist)
import { getWishlist } from '../../../services/wishlistApi'

// ─── Importaciones de composables y utilidades ──────────────────────────────────
// useSession: composable que expone el estado de la sesión del usuario (datos del usuario y si está autenticado)
import { useSession } from '../../../composables/useSession'

// Funciones utilitarias para presentación de estados de pedido:
// getOrderStatusLabel: convierte el código de estado a un texto legible en español
// normalizeOrderStatus: normaliza el estado del pedido a un formato estándar (snake_case)
import { getOrderStatusLabel, normalizeOrderStatus } from '../../../utils/orderPresentation'

// Instancia del router para navegación programática (se usa en openProduct para redirigir)
const router = useRouter()

// Datos de sesión del usuario autenticado:
// user: objeto con los datos del usuario (id, email, etc.)
// isLoggedIn: booleano reactivo que indica si el usuario tiene sesión activa
const { user, isLoggedIn } = useSession()

// ─── Estado reactivo del componente ─────────────────────────────────────────────

// loading: controla la visualización del shimmer durante la carga inicial de datos
const loading = ref(true)

// errorMessage: almacena el mensaje de error si falla alguna petición API
const errorMessage = ref('')

// orders: array que almacena los pedidos del usuario obtenidos del servidor
const orders = ref([])

// addresses: array que almacena las direcciones de envío del usuario
const addresses = ref([])

// favorites: array que almacena los productos marcados como favoritos (wishlist)
const favorites = ref([])

// recommendedProducts: array que almacena productos recomendados del catálogo
const recommendedProducts = ref([])

// ─── Propiedades computadas ─────────────────────────────────────────────────────

// favoriteProducts: extrae los primeros 6 productos favoritos para mostrar en la cuadrícula
// Se usa slice(0, 6) para limitar la cantidad de elementos visibles en el dashboard
const favoriteProducts = computed(() => favorites.value.slice(0, 6))

// recommendedShowcase: genera la lista de productos recomendados excluyendo los que ya son favoritos
// Esto evita mostrar duplicados en las secciones de favoritos y recomendaciones
const recommendedShowcase = computed(() => {
  // Se crea un Set con los IDs de los favoritos para búsquedas eficientes O(1)
  const favoriteIds = new Set(favorites.value.map((item) => Number(item.id || 0)))

  return recommendedProducts.value
    // Se filtra para excluir productos que ya están en la lista de favoritos
    .filter((item) => !favoriteIds.has(Number(item.id || 0)))
    // Se limita a un máximo de 6 productos recomendados
    .slice(0, 6)
})

// recentOrders: extrae los últimos 3 pedidos para mostrar en la sección de pedidos recientes
const recentOrders = computed(() => orders.value.slice(0, 3))

// ordersCountLabel: genera el texto del contador de pedidos con pluralización correcta en español
// Ejemplo: "1 pedido realizado" o "5 pedidos realizados"
const ordersCountLabel = computed(() => {
  const total = orders.value.length
  return `${total} pedido${total === 1 ? '' : 's'} realizado${total === 1 ? '' : 's'}`
})

// addressesCountLabel: genera el texto del contador de direcciones con pluralización correcta
// Ejemplo: "1 dirección guardada" o "3 direcciones guardadas"
const addressesCountLabel = computed(() => {
  const total = addresses.value.length
  return `${total} dirección${total === 1 ? '' : 'es'} guardada${total === 1 ? '' : 's'}`
})

// favoritesCountLabel: genera el texto del contador de productos favoritos con pluralización correcta
// Ejemplo: "2 productos guardados" o "1 producto guardado"
const favoritesCountLabel = computed(() => {
  const total = favorites.value.length
  return `${total} producto${total === 1 ? '' : 's'} guardado${total === 1 ? '' : 's'}`
})

// ─── Hook onMounted: carga inicial de datos ────────────────────────────────────
// Se ejecuta una sola vez cuando el componente se monta en el DOM.
// Realiza 4 peticiones API en paralelo para obtener todos los datos necesarios del dashboard.
onMounted(async () => {
  // Se activa el estado de carga para mostrar el shimmer
  loading.value = true
  // Se limpia cualquier mensaje de error previo
  errorMessage.value = ''

  try {
    // Verificación de autenticación: si el usuario no tiene sesión, se aborta la carga
    // y se redirige implícitamente al login (no se maneja aquí, se asume en route guards)
    if (!isLoggedIn.value) {
      return
    }

    // Se obtienen el ID y email del usuario desde la sesión, con trimming por seguridad
    const userId = String(user.value?.id || '').trim()
    const userEmail = String(user.value?.email || '').trim()

    // ─── Peticiones API en paralelo ───────────────────────────────────────────────
    // Se utiliza Promise.allSettled en lugar de Promise.all para que todas las peticiones
    // se completen independientemente de si alguna falla. Esto permite mostrar parcialmente
    // los datos disponibles en caso de que solo algumas APIs fallen.
    const [
      ordersResult,       // Resultado de la petición de pedidos
      addressesResult,    // Resultado de la petición de direcciones
      wishlistResult,     // Resultado de la petición de lista de deseos
      productsResult,     // Resultado de la petición de productos del catálogo
    ] = await Promise.allSettled([
      // Petición de pedidos: se envía user_id o user_email como parámetros de filtro
      getOrders({
        user_id: userId || undefined,
        user_email: userEmail || undefined,
      }),
      // Petición de direcciones del usuario
      getUserAddresses(userId, userEmail),
      // Petición de lista de deseos (wishlist)
      getWishlist(userId || undefined, userEmail || undefined),
      // Petición de productos del catálogo con límite de 6 elementos (para recomendaciones)
      getProducts({
        per_page: 6,
        user_id: userId || undefined,
        user_email: userEmail || undefined,
      }),
    ])

    // ─── Procesamiento de resultados de pedidos ───────────────────────────────────
    // Solo se asignan datos si la promesa fue resuelta y el campo data es un array válido
    orders.value = ordersResult.status === 'fulfilled' && Array.isArray(ordersResult.value?.data)
      ? ordersResult.value.data
      : []

    // ─── Procesamiento de resultados de direcciones ───────────────────────────────
    addresses.value = addressesResult.status === 'fulfilled' && Array.isArray(addressesResult.value?.data)
      ? addressesResult.value.data
      : []

    // ─── Procesamiento de resultados de wishlist ──────────────────────────────────
    // Se agrega la propiedad is_favorite: 1 a cada item para que ProductCard lo reconozca como favorito
    favorites.value = wishlistResult.status === 'fulfilled' && Array.isArray(wishlistResult.value?.data)
      ? wishlistResult.value.data.map((item) => ({
        ...item,
        is_favorite: 1,
      }))
      : []

    // ─── Procesamiento de resultados de productos del catálogo ────────────────────
    recommendedProducts.value = productsResult.status === 'fulfilled'
      && Array.isArray(productsResult.value?.data?.products)
      ? productsResult.value.data.products
      : []

    // ─── Manejo de errores parciales ─────────────────────────────────────────────
    // Si las 3 peticiones principales (pedidos, direcciones, wishlist) fallaron,
    // se muestra un mensaje de error genérico al usuario
    if (
      ordersResult.status === 'rejected'
      && addressesResult.status === 'rejected'
      && wishlistResult.status === 'rejected'
    ) {
      errorMessage.value = 'No se pudieron cargar los datos del dashboard.'
    }
  } catch {
    // Error inesperado: se captura cualquier excepción no controlada y se notifica al usuario
    errorMessage.value = 'No se pudieron cargar los datos principales de tu cuenta.'
  } finally {
    // Siempre se desactiva el loading al finalizar, ya sea éxito o error
    loading.value = false
  }
})

// ─── Funciones de navegación ────────────────────────────────────────────────────

// openProduct: navega a la página de detalle de un producto específico
// Se invoca cuando el usuario hace clic en el botón "Agregar al carrito" del ProductCard
// En lugar de agregar al carrito, redirige a la página del producto para que el usuario
// pueda ver los detalles y elegir variantes antes de comprar
function openProduct(product) {
  router.push({ name: 'product', params: { slug: product.slug } })
}

// ─── Funciones de actualización de estado ───────────────────────────────────────

// onWishlistChange: handler que se ejecuta cuando el usuario cambia el estado de favorito de un producto
// Actualiza tanto la lista de favoritos como la de recomendaciones para mantener la coherencia visual
function onWishlistChange(payload) {
  // Se extraen y tipifican los datos del evento emitido por ProductCard
  const productId = Number(payload?.productId)
  const isFavorite = Boolean(payload?.isFavorite)

  // ─── Actualización de la lista de favoritos ──────────────────────────────────
  favorites.value = favorites.value
    .map((item) => {
      // Si el item no coincide con el ID del producto, se devuelve sin cambios
      if (Number(item.id) !== productId) {
        return item
      }

      // Se actualiza el estado is_favorite del producto coincidente
      return {
        ...item,
        is_favorite: isFavorite ? 1 : 0,
      }
    })
    // Se filtran los items: solo permanecen los que tienen is_favorite === 1
    // Esto elimina de la vista los productos que el usuario desmarcó como favoritos
    .filter((item) => Number(item.is_favorite) === 1)

  // ─── Actualización de la lista de recomendaciones ────────────────────────────
  // Se actualiza el estado del producto recomendado para que el ProductCard
  // refleje correctamente si es favorito o no (corazón lleno/vacío)
  recommendedProducts.value = recommendedProducts.value.map((item) => {
    if (Number(item.id) !== productId) {
      return item
    }

    return {
      ...item,
      is_favorite: isFavorite ? 1 : 0,
    }
  })
}

// ─── Funciones de formateo ──────────────────────────────────────────────────────

// formatPrice: convierte un valor numérico a formato de moneda colombiana (COP)
// Utiliza Intl.NumberFormat para localización correcta con separadores de miles
// maximumFractionDigits: 0 porque los precios en COP no manejan decimales
function formatPrice(value) {
  return new Intl.NumberFormat('es-CO', {
    style: 'currency',
    currency: 'COP',
    maximumFractionDigits: 0,
  }).format(Number(value || 0))
}

// formatDate: convierte una cadena de fecha ISO a formato legible según locale colombiano
// Si el valor es nulo o indefinido, retorna un guión como fallback visual
function formatDate(value) {
  if (!value) return '-'
  return new Date(value).toLocaleDateString('es-CO')
}

// ─── Funciones de presentación de estados de pedido ─────────────────────────────

// statusLabel: retorna el texto legible de un estado de pedido
// Utiliza la función utilitaria getOrderStatusLabel que mapea códigos internos a textos en español
// Si no hay coincidencia, retorna el estado original o "Sin estado" como fallback
function statusLabel(status) {
  return getOrderStatusLabel(status) || status || 'Sin estado'
}

// statusClass: retorna la clase CSS correspondiente al estado del pedido
// Se usa para aplicar estilos visuales diferenciados (colores) a cada badge de estado
// Normaliza el estado y luego evalúa contra valores conocidos para asignar la clase correcta
function statusClass(status) {
  // Se normaliza el estado a un formato estándar (snake_case minúsculas)
  const normalizedStatus = normalizeOrderStatus(status)
  // Se genera una versión raw del estado para comparaciones adicionales
  const rawStatus = String(status || '').trim().toLowerCase().replace(/\s+/g, '_').replace(/-/g, '_')

  // Mapeo de estados a clases CSS:
  // Pendiente: amarillo/naranja suave
  if (normalizedStatus === 'pending') return 'status-pending'
  // En revisión o procesando: azul
  if (['in_review', 'processing'].includes(normalizedStatus)) return 'status-processing'
  // Pagado, confirmado o enviado: colores específicos por estado
  if (['paid', 'confirmed', 'shipped'].includes(rawStatus)) return `status-${rawStatus}`
  // Entregado o completado: verde
  if (['delivered', 'completed'].includes(normalizedStatus)) return `status-${normalizedStatus}`
  // Cancelado, fallido o reembolsado: rojo
  if (['cancelled', 'canceled', 'failed', 'refunded'].includes(rawStatus)) return 'status-cancelled'

  // Fallback: si el estado no coincide con ninguno conocido, se usa procesando (azul)
  return 'status-processing'
}
</script>
