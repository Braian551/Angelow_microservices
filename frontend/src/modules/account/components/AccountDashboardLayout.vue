<!--
  AccountDashboardLayout.vue
  Componente de layout principal para el panel de cuenta del usuario.
  Proporciona la estructura de sidebar + contenido principal para todas
  las secciones del dashboard (pedidos, notificaciones, direcciones, etc.).
  Si el usuario no está autenticado, muestra un mensaje de acceso requerido.
-->
<template>
  <main class="account-page">
    <!--
      Sección de autenticación requerida:
      Se muestra cuando el usuario NO ha iniciado sesión.
      Incluye un enlace de redirección al login con la ruta actual como parámetro
      para que después del login pueda volver a donde estaba.
    -->
    <section v-if="!isLoggedIn" class="section-container account-auth-required">
      <h2>Inicia sesión para acceder a tu cuenta</h2>
      <p>Debes iniciar sesión para gestionar pedidos, notificaciones, direcciones y favoritos.</p>
      <RouterLink :to="{ name: 'login', query: { redirect: currentPath } }" class="btn-primary-small">
        Iniciar sesión
      </RouterLink>
    </section>

    <!--
      Contenedor principal del dashboard (solo visible si el usuario está autenticado).
      Contiene la barra lateral (sidebar) con perfil y navegación, más el área de contenido principal.
    -->
    <div v-else class="user-dashboard-container">
      <!-- Barra lateral izquierda con resumen del usuario y menú de navegación -->
      <aside class="user-sidebar">
        <!-- Resumen del perfil del usuario: avatar, nombre, email y fecha de registro -->
        <div class="user-profile-summary">
          <!-- Botón para navegar hacia atrás o al inicio si no hay historial -->
          <button type="button" class="back-button" aria-label="Volver" @click="goBackOrHome">
            <i class="fas fa-arrow-left" />
          </button>

          <!--
            Contenedor del avatar del usuario.
            Muestra un efecto shimmer mientras la imagen está cargando.
            La clase 'is-loading' se activa cuando la imagen aún no se ha cargado.
          -->
          <div class="user-avatar" :class="{ 'is-loading': showAvatarShimmer }">
            <!-- Efecto de carga (shimmer) visible mientras la imagen carga -->
            <span v-if="showAvatarShimmer" class="user-avatar__shimmer" aria-hidden="true"></span>
            <!--
              Imagen del avatar con manejo de eventos:
              - @load: se ejecuta cuando la imagen carga correctamente
              - @error: se ejecuta cuando hay error al cargar la imagen
              - La clase 'is-ready' se aplica cuando la imagen ya está cargada
            -->
            <img
              :src="avatarUrl"
              alt="Foto de perfil"
              :class="{ 'is-ready': !showAvatarShimmer }"
              @load="onAvatarLoad"
              @error="onAvatarError"
            />
          </div>

          <!-- Información del usuario: nombre, email y fecha de membresía -->
          <div class="user-info">
            <h3>{{ displayName }}</h3>
            <p>{{ displayEmail }}</p>
            <p>{{ memberSinceLabel }}</p>
          </div>
        </div>

        <!--
          Menú de navegación del panel de usuario.
          Cada enlace resalta visualmente (clase 'active') cuando la sección activa coincide.
          El badge de notificaciones solo se muestra si hay notificaciones sin leer.
        -->
        <nav class="user-menu" aria-label="Panel de usuario">
          <ul>
            <!-- Enlace al resumen principal del dashboard -->
            <li :class="{ active: activeSection === 'dashboard' }">
              <RouterLink :to="{ name: 'account-dashboard' }">
                <i class="fas fa-tachometer-alt" />
                Resumen
              </RouterLink>
            </li>
            <!-- Enlace a la sección de pedidos del usuario -->
            <li :class="{ active: activeSection === 'orders' }">
              <RouterLink :to="{ name: 'account-orders' }">
                <i class="fas fa-shopping-bag" />
                Mis pedidos
              </RouterLink>
            </li>
            <!--
              Enlace a notificaciones con badge condicional.
              El badge muestra el número de notificaciones sin leer (máximo "99+").
            -->
            <li :class="{ active: activeSection === 'notifications' }">
              <RouterLink :to="{ name: 'account-notifications' }">
                <i class="fas fa-bell" />
                Notificaciones
                <span v-if="unreadNotifications > 0" class="notification-badge">{{ unreadBadge }}</span>
              </RouterLink>
            </li>
            <!-- Enlace a la gestión de direcciones de envío -->
            <li :class="{ active: activeSection === 'addresses' }">
              <RouterLink :to="{ name: 'account-addresses' }">
                <i class="fas fa-map-marker-alt" />
                Direcciones
              </RouterLink>
            </li>
            <!-- Enlace a la lista de favoritos (wishlist) -->
            <li :class="{ active: activeSection === 'wishlist' }">
              <RouterLink :to="{ name: 'account-wishlist' }">
                <i class="fas fa-heart" />
                Favoritos
              </RouterLink>
            </li>
            <!-- Enlace a la configuración de la cuenta -->
            <li :class="{ active: activeSection === 'settings' }">
              <RouterLink :to="{ name: 'account-settings' }">
                <i class="fas fa-user-cog" />
                Configuración
              </RouterLink>
            </li>
            <!-- Botón de cerrar sesión con confirmación -->
            <li class="logout">
              <button type="button" @click="requestLogout">
                <i class="fas fa-sign-out-alt" />
                Cerrar sesión
              </button>
            </li>
          </ul>
        </nav>
      </aside>

      <!--
        Área de contenido principal donde se renderiza el contenido de cada sección.
        Utiliza un slot para permitir que las páginas hijas inyecten su contenido.
      -->
      <section class="user-main-content">
        <slot />
      </section>
    </div>
  </main>
</template>

<!-- Script principal del componente (Composition API con <script setup>) -->
<script setup>
// Importaciones de Vue: composición reactiva y utilidades
import { computed, ref, watch } from 'vue'
// Importaciones de Vue Router: componentes y composables para navegación
import { RouterLink, useRoute, useRouter } from 'vue-router'
// Composable personalizado para gestionar alertas modales (confirmaciones, errores, etc.)
import { useAlertSystem } from '../../../composables/useAlertSystem'
// Composable personalizado para manejar la sesión del usuario (datos, autenticación, limpieza)
import { useSession } from '../../../composables/useSession'
// Utilidades para manejar URLs de medios y errores de carga de imágenes
import { handleMediaError, resolveMediaUrl } from '../../../utils/media'
// Estilos CSS específicos de la vista del dashboard de cuenta
import '../views/AccountDashboardView.css'

// Props del componente: se reciben desde el componente padre
const props = defineProps({
  // Identificador de la sección activa del dashboard (determina qué enlace del menú se resalta)
  activeSection: {
    type: String,
    default: 'dashboard', // Valor por defecto: sección de resumen
  },
  // Número de notificaciones sin leer del usuario (para mostrar el badge)
  unreadNotifications: {
    type: Number,
    default: 0, // Sin notificaciones por defecto
  },
})

// Instancia del router para navegación programática
const router = useRouter()
// Ruta actual para preservar la ubicación del usuario tras login
const route = useRoute()
// Datos de sesión del usuario: objeto reactivo 'user', booleano 'isLoggedIn' y función 'clearSession'
const { user, isLoggedIn, clearSession } = useSession()
// Función para mostrar alertas modales de confirmación
const { showAlert } = useAlertSystem()

// Ruta actual completa del navegador (se usa para redirigir después del login)
const currentPath = computed(() => route.fullPath || '/mi-cuenta/resumen')
// Estado reactivo para controlar si la imagen del avatar ya terminó de cargar
const avatarLoaded = ref(false)

// Nombre del usuario para mostrar en el perfil (con valor por defecto si no existe)
const displayName = computed(() => String(user.value?.name || 'Usuario'))
// Email del usuario para mostrar en el perfil (con valor por defecto si no existe)
const displayEmail = computed(() => String(user.value?.email || 'Sin correo'))
// Badge de notificaciones: muestra el número exacto o "99+" si supera 99
const unreadBadge = computed(() => (props.unreadNotifications > 99 ? '99+' : String(props.unreadNotifications)))

// Extrae la ruta de la imagen del avatar desde el objeto usuario (probando varios campos posibles)
const avatarPath = computed(() => user.value?.image || user.value?.avatar || user.value?.profile_image || '')
// Resuelve la URL completa de la imagen del avatar usando la utilidad de medios
const avatarUrl = computed(() => resolveMediaUrl(avatarPath.value, 'avatar'))
// Controla la visualización del efecto shimmer: se muestra si hay URL de avatar pero aún no ha cargado
const showAvatarShimmer = computed(() => Boolean(avatarUrl.value) && !avatarLoaded.value)

// Genera la etiqueta "Miembro desde" con el mes y año de registro del usuario
const memberSinceLabel = computed(() => {
  const raw = user.value?.created_at
  // Si no hay fecha de creación, retorna un valor por defecto
  if (!raw) return 'Miembro de Angelow'

  const date = new Date(raw)
  // Si la fecha no es válida, retorna un valor por defecto
  if (Number.isNaN(date.getTime())) return 'Miembro de Angelow'

  // Formatea la fecha en español colombiano: mes abreviado y año numérico
  return `Miembro desde ${date.toLocaleDateString('es-CO', { month: 'short', year: 'numeric' })}`
})

// Manejador de error al cargar la imagen del avatar
// Marca la imagen como cargada (para ocultar el shimmer) y registra el error
function onAvatarError(event) {
  avatarLoaded.value = true
  handleMediaError(event, avatarPath.value, 'avatar')
}

// Manejador de carga exitosa del avatar
// Marca la imagen como cargada para ocultar el efecto shimmer
function onAvatarLoad() {
  avatarLoaded.value = true
}

// Watcher que observa cambios en la URL del avatar
// Cuando la URL cambia (ej: el usuario actualiza su foto), resetea el estado de carga
// para mostrar el efecto shimmer mientras carga la nueva imagen
watch(avatarUrl, () => {
  avatarLoaded.value = false
}, { immediate: true }) // immediate: true ejecuta el watcher inmediatamente al montar el componente

// Navega hacia atrás en el historial del navegador, o al inicio si no hay historial previo
function goBackOrHome() {
  if (window.history.length > 1) {
    router.back() // Si hay historial, retrocede una página
    return
  }

  // Si no hay historial (el usuario abrió directamente esta página), va al inicio
  router.push({ name: 'home' })
}

// Muestra un modal de confirmación antes de cerrar la sesión
function requestLogout() {
  showAlert({
    type: 'question', // Tipo de alerta: pregunta de confirmación
    title: 'Cerrar sesión',
    message: '¿Deseas cerrar tu sesión en Angelow?',
    actions: [
      {
        text: 'Cancelar', // Botón secundario: cierra el modal sin hacer nada
        style: 'secondary',
      },
      {
        text: 'Cerrar sesión', // Botón de acción principal: ejecuta el cierre de sesión
        style: 'danger', // Estilo rojo para indicar acción destructiva
        callback: () => {
          clearSession() // Limpia los datos de sesión del almacenamiento local/cookies
          router.push({ name: 'home' }) // Redirige al usuario a la página principal
        },
      },
    ],
  })
}
</script>
