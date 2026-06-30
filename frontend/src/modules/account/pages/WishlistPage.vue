<template>
  <!-- Contenedor principal de la página de lista de deseos -->
  <section class="wishlist-page-container">
    <!-- Componente de carga (shimmer) que se muestra mientras se obtienen los datos -->
    <AccountShimmer v-if="loading" variant="wishlist" />

    <!-- Plantilla que se muestra una vez que la carga ha terminado -->
    <template v-else>
      <!-- Encabezado de la página con título e instrucción para el usuario -->
      <div class="wishlist-header">
        <h1><i class="fas fa-heart" /> Mi Lista de Deseos</h1>
        <p>Guarda tus productos favoritos aquí</p>
      </div>

      <!-- Sección que muestra un mensaje de error si ocurrió alguno durante la carga -->
      <template v-if="errorMessage">
        <section class="account-card">
          <p class="error-box">{{ errorMessage }}</p>
        </section>
      </template>

      <!-- Sección principal que se muestra cuando hay productos en la lista de deseos -->
      <template v-else-if="products.length > 0">
        <!-- Controles superiores: acciones y estadísticas de la lista -->
        <section class="wishlist-controls">
          <div class="wishlist-actions">
            <!-- Contador de productos con pluralización dinámica -->
            <div class="total-products">
              <p>{{ products.length }} producto{{ products.length === 1 ? '' : 's' }} en tu lista</p>
            </div>

            <!-- Botón para limpiar toda la lista de deseos -->
            <button type="button" class="clear-all-btn" :disabled="busy" @click="confirmClearAll">
              <i class="fas fa-trash" />
              Limpiar lista
            </button>
          </div>

          <!-- Tarjeta de estadísticas que muestra el total de productos guardados -->
          <div class="wishlist-stats">
            <article class="stat-card">
              <div class="stat-icon">
                <i class="fas fa-heart" />
              </div>
              <div class="stat-content">
                <span class="stat-value">{{ products.length }}</span>
                <span class="stat-label">Producto{{ products.length === 1 ? '' : 's' }} guardado{{ products.length === 1 ? '' : 's' }}</span>
              </div>
            </article>
          </div>
        </section>

        <!-- Cuadrícula de productos: renderiza un ProductCard por cada producto en la lista -->
        <section class="products-grid">
          <ProductCard
            v-for="product in products"
            :key="product.id"
            :product="product"
            @add-cart="openProduct"
            @wishlist-change="onWishlistChange"
          />
        </section>
      </template>

      <!-- Sección que se muestra cuando la lista de deseos está vacía -->
      <div v-else class="empty-wishlist">
        <div class="empty-wishlist-content">
          <i class="fas fa-heart-broken" />
          <h2>Tu lista de deseos está vacía</h2>
          <p>Agrega productos a tu lista de deseos para guardarlos aquí</p>
          <!-- Enlace para navegar a la tienda y explorar productos -->
          <RouterLink :to="{ name: 'store' }" class="btn">
            <i class="fas fa-shopping-bag" />
            Explorar productos
          </RouterLink>
        </div>
      </div>
    </template>
  </section>
</template>

<script setup>
// Importaciones de Vue: onMounted para el ciclo de vida, ref para reactividad
import { onMounted, ref } from 'vue'
// RouterLink y useRouter para navegación entre páginas
import { RouterLink, useRouter } from 'vue-router'
// Componente de tarjeta de producto del catálogo
import ProductCard from '../../catalog/components/ProductCard.vue'
// Componente de efecto shimmer para estados de carga
import AccountShimmer from '../components/AccountShimmer.vue'
// Composable que proporciona información de sesión del usuario (autenticación)
import { useSession } from '../../../composables/useSession'
// Composable del sistema de alertas para mostrar notificaciones al usuario
import { useAlertSystem } from '../../../composables/useAlertSystem'
// Funciones de la API para obtener y modificar la lista de deseos
import { getWishlist, toggleWishlist } from '../../../services/wishlistApi'
// Estilos CSS específicos de la vista de lista de deseos
import '../views/WishlistView.css'

// Router instance para navegar programáticamente
const router = useRouter()
// Datos de sesión del usuario: objeto user y booleano isLoggedIn
const { user, isLoggedIn } = useSession()
// Función showAlert del sistema de alertas para mostrar mensajes al usuario
const { showAlert } = useAlertSystem()

// Estado reactivo: indica si la página está cargando datos iniciales
const loading = ref(true)
// Estado reactivo: indica si se está realizando una operación (para deshabilitar botones)
const busy = ref(false)
// Estado reactivo: almacena mensaje de error si la carga falla
const errorMessage = ref('')
// Estado reactivo: array que contiene todos los productos de la lista de deseos
const products = ref([])

// Ciclo de vida: se ejecuta cuando el componente se monta en el DOM
// Llama a loadWishlist para cargar los productos favoritos del usuario
onMounted(async () => {
  await loadWishlist()
})

// Función asíncrona que carga la lista de deseos desde la API
// Obtiene los productos y los normaliza con propiedades adicionales
async function loadWishlist() {
  // Activa el estado de carga
  loading.value = true
  // Limpia cualquier error previo
  errorMessage.value = ''

  try {
    // Verifica si el usuario está autenticado antes de hacer la petición
    if (!isLoggedIn.value || !user.value?.id) {
      products.value = []
      return
    }

    // Realiza la petición a la API para obtener la lista de deseos del usuario actual
    const wishlistResponse = await getWishlist(currentUserId(), currentUserEmail())

    // Normaliza la respuesta: convierte el array de productos y agrega propiedades por defecto
    // is_favorite se establece en 1 porque estos productos ya están en la lista de deseos
    // avg_rating y review_count se convierten a números para evitar problemas de renderizado
    products.value = (Array.isArray(wishlistResponse?.data) ? wishlistResponse.data : []).map((item) => ({
      ...item,
      is_favorite: 1,
      avg_rating: Number(item.avg_rating || 0),
      review_count: Number(item.review_count || 0),
    }))
  } catch {
    // En caso de error, establece un mensaje de error genérico
    errorMessage.value = 'No se pudieron cargar los favoritos.'
  } finally {
    // Siempre desactiva el estado de carga al finalizar, independientemente del resultado
    loading.value = false
  }
}

// Función que navega a la página de detalle de un producto específico
// Se llama cuando el usuario hace clic en "Agregar al carrito" desde un ProductCard
function openProduct(product) {
  router.push({ name: 'product', params: { slug: product.slug } })
}

// Función que maneja el cambio de estado de favorito en un producto
// Cuando se elimina un producto de la lista de deseos, lo quita del array local
function onWishlistChange(event) {
  // Si el evento indica que se agregó a favoritos (isFavorite=true), no hace nada
  // Solo procesa la eliminación de favoritos
  if (event?.isFavorite) return
  // Filtra el array para eliminar el producto cuyo id coincida con el del evento
  products.value = products.value.filter((item) => Number(item.id) !== Number(event.productId))
}

// Función que muestra un diálogo de confirmación antes de limpiar toda la lista
// Previene accidentes mostrando una alerta de confirmación al usuario
function confirmClearAll() {
  // No procede si la lista está vacía o si ya hay una operación en curso
  if (!products.value.length || busy.value) return

  // Muestra alerta de tipo "question" con opciones de cancelar o confirmar
  showAlert({
    type: 'question',
    title: 'Limpiar lista de deseos',
    message: '¿Deseas eliminar todos los productos de tus favoritos?',
    actions: [
      // Botón de cancelar: cierra la alerta sin hacer nada
      { text: 'Cancelar', style: 'secondary' },
      // Botón de confirmar: ejecuta la función para limpiar la lista
      {
        text: 'Limpiar lista',
        style: 'danger',
        // Callback asíncrono que se ejecuta cuando el usuario confirma la acción
        callback: async () => {
          await clearAllWishlist()
        },
      },
    ],
  })
}

// Función asíncrona que elimina todos los productos de la lista de deseos
// Recorre cada producto y lo elimina individualmente de la API
async function clearAllWishlist() {
  // No procede si la lista está vacía o si ya hay una operación en curso
  if (!products.value.length || busy.value) return

  // Marca como ocupado para deshabilitar botones y evitar múltiples clics
  busy.value = true

  try {
    // Crea una copia del array para iterar sin problemas durante la eliminación
    const currentProducts = [...products.value]

    // Itera sobre cada producto y lo elimina de la lista de deseos en la API
    // Se usa un bucle for...of para poder usar await dentro del ciclo
    for (const product of currentProducts) {
      await toggleWishlist({
        user_id: currentUserId(),
        user_email: currentUserEmail(),
        product_id: Number(product.id),
      })
    }

    // Limpia el array local de productos después de eliminar todos exitosamente
    products.value = []

    // Muestra alerta de éxito indicando que la lista fue limpiada
    showAlert({
      type: 'success',
      title: 'Lista actualizada',
      message: 'Se eliminaron todos los productos de tus favoritos.',
      autoCloseSeconds: 3, // La alerta se cierra automáticamente después de 3 segundos
    })
  } catch {
    // En caso de error, muestra alerta indicando que no se pudo completar la operación
    showAlert({
      type: 'error',
      title: 'No se pudo completar',
      message: 'No fue posible limpiar toda la lista. Intenta de nuevo.',
    })

    // Recarga la lista desde la API para mostrar el estado actual real
    await loadWishlist()
  } finally {
    // Desactiva el estado ocupado para volver a habilitar los botones
    busy.value = false
  }
}

// Función auxiliar que obtiene el ID del usuario actual como string
// Retorna undefined si no hay usuario autenticado
function currentUserId() {
  return String(user.value?.id || '').trim() || undefined
}

// Función auxiliar que obtiene el email del usuario actual como string
// Retorna undefined si no hay usuario autenticado
function currentUserEmail() {
  return String(user.value?.email || '').trim() || undefined
}
</script>
