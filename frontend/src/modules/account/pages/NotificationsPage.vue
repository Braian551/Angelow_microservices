<template>
  <!-- Muestra el efecto shimmer (esqueleto de carga) mientras se obtienen las notificaciones del API -->
  <AccountShimmer v-if="loading" variant="notifications" />

  <!-- Contenido principal: se renderiza solo cuando loading es false -->
  <template v-else>
    <!-- Encabezado de la página: título, descripción y botón de marcado masivo -->
    <section class="dashboard-header notifications-header-panel">
      <div class="notifications-header-panel__copy">
        <h1>
          <i class="fas fa-bell"></i>
          Mis notificaciones
        </h1>
        <p>Mantente al día con tus pedidos, novedades y movimientos importantes de tu cuenta.</p>
      </div>

      <!-- Botón para marcar todas las notificaciones como leídas; solo se muestra si hay al menos una sin leer -->
      <button
        v-if="unreadCount > 0"
        type="button"
        class="btn-outline-small notifications-header-panel__action"
        :disabled="loadingAction"
        @click="markAllAsRead"
      >
        <!-- Muestra spinner de carga cuando se está procesando; de lo contrario muestra el icono de doble check -->
        <i :class="loadingAction ? 'fas fa-spinner fa-spin' : 'fas fa-check-double'"></i>
        {{ loadingAction ? 'Actualizando...' : 'Marcar todas como leídas' }}
      </button>
    </section>

    <!-- Tarjetas de resumen: total, sin leer y leídas -->
    <section class="account-grid-2 notifications-summary-grid">
      <article class="summary-card notification-summary-card">
        <div class="summary-icon notification-summary-card__icon notification-summary-card__icon--total">
          <i class="fas fa-envelope"></i>
        </div>
        <div class="summary-content">
          <h3>Total</h3>
          <!-- Pluralización condicional: muestra "notificación" o "notificaciones" según la cantidad -->
          <p>{{ notifications.length }} notificación{{ notifications.length === 1 ? '' : 'es' }}</p>
        </div>
      </article>

      <article class="summary-card notification-summary-card">
        <div class="summary-icon notification-summary-card__icon notification-summary-card__icon--unread">
          <i class="fas fa-envelope-open-text"></i>
        </div>
        <div class="summary-content">
          <h3>Sin leer</h3>
          <!-- Pluralización condicional para "pendiente" / "pendientes" -->
          <p>{{ unreadCount }} pendiente{{ unreadCount === 1 ? '' : 's' }}</p>
        </div>
      </article>

      <article class="summary-card notification-summary-card">
        <div class="summary-icon notification-summary-card__icon notification-summary-card__icon--read">
          <i class="fas fa-check-double"></i>
        </div>
        <div class="summary-content">
          <h3>Leídas</h3>
          <!-- Pluralización condicional para "revisada" / "revisadas" -->
          <p>{{ readCount }} revisada{{ readCount === 1 ? '' : 's' }}</p>
        </div>
      </article>
    </section>

    <!-- Tablero principal de notificaciones: filtros, lista y estados vacíos -->
    <section class="account-card notifications-board">
      <!-- Encabezado del tablero con título y selector de tipo -->
      <header class="section-header notifications-board__header">
        <div class="notifications-board__heading">
          <h2>Bandeja de notificaciones</h2>
          <!-- Muestra cuántas notificaciones son visibles según los filtros activos -->
          <p class="notifications-board__meta">
            {{ filteredNotifications.length }} resultado{{ filteredNotifications.length === 1 ? '' : 's' }} visibles
          </p>
        </div>

        <!-- Selector desplegable para filtrar por tipo de notificación (órdenes, productos, etc.) -->
        <div class="notif-type-select-wrap">
          <i class="fas fa-filter notif-type-select-icon"></i>
          <select v-model="typeFilter" class="notif-type-select">
            <option value="all">Todos los tipos</option>
            <option value="order">Órdenes</option>
            <option value="product">Productos</option>
            <option value="promotion">Promociones</option>
            <option value="system">Sistema</option>
            <option value="account">Cuenta</option>
          </select>
        </div>
      </header>

      <!-- Barra de herramientas con botones "pill" para filtrar por estado de lectura -->
      <div class="notifications-toolbar">
        <!-- Genera un botón pill por cada opción de estado definida en statusOptions -->
        <button
          v-for="opt in statusOptions"
          :key="opt.value"
          type="button"
          class="notifications-status-pill"
          :class="{ 'is-active': statusFilter === opt.value }"
          @click="statusFilter = opt.value"
        >
          {{ opt.label }}
          <!-- Muestra un badge con el conteo de no leídas solo en el botón "Sin leer" -->
          <span v-if="opt.value === 'unread' && unreadCount > 0" class="notifications-status-pill__badge">{{ unreadCount }}</span>
        </button>
      </div>

      <!-- Estado de error: se muestra cuando falla la carga de notificaciones -->
      <div v-if="errorMessage" class="empty-state notifications-empty-state">
        <i class="fas fa-exclamation-circle"></i>
        <p>{{ errorMessage }}</p>
      </div>

      <!-- Estado vacío: se muestra cuando no hay notificaciones para los filtros seleccionados -->
      <div v-else-if="filteredNotifications.length === 0" class="empty-state notifications-empty-state">
        <i class="fas fa-bell-slash"></i>
        <p>No tienes notificaciones para este filtro.</p>
        <span>Cuando haya novedades relevantes las verás aquí.</span>
      </div>

      <!-- Lista de notificaciones con animación de entrada/salida al cambiar los filtros -->
      <transition-group v-else name="notifications-list" tag="div" class="notifications-list">
        <!-- Cada notificación se renderiza como un article clickeable -->
        <article
          v-for="notification in filteredNotifications"
          :key="notification.id"
          class="notifications-item"
          :class="{ 'is-unread': !notification.is_read }"
          @click="handleOpenNotification(notification)"
        >
          <!-- Zona izquierda: indicador de no leída + icono del tipo de notificación -->
          <div class="notifications-item__leading">
            <!-- Punto azul que indica que la notificación no ha sido leída -->
            <span v-if="!notification.is_read" class="notifications-item__unread-dot" title="No leída"></span>
            <!-- Icono con fondo de color según el tipo de notificación (orden, producto, etc.) -->
            <div class="notifications-item__icon" :class="`notifications-item__icon--${notificationType(notification)}`">
              <i :class="notificationIcon(notification)"></i>
            </div>
          </div>

          <!-- Cuerpo de la notificación: título, badge de tipo, tiempo transcurrido y mensaje -->
          <div class="notifications-item__body">
            <div class="notifications-item__top">
              <div class="notifications-item__headline">
                <h3>{{ notification.title }}</h3>
                <!-- Badge de color que indica el tipo de notificación en texto legible -->
                <span :class="`notifications-type-badge notifications-type-badge--${notificationType(notification)}`">
                  {{ notificationTypeLabel(notification) }}
                </span>
              </div>

              <!-- Tiempo transcurrido desde la creación (se actualiza cada segundo gracias a relativeNow) -->
              <span class="notifications-item__time">
                <i class="far fa-clock"></i>
                {{ formatTimeAgo(notification.created_at) }}
              </span>
            </div>

            <!-- Mensaje descriptivo de la notificación -->
            <p class="notifications-item__message">{{ notification.message }}</p>
          </div>

          <!-- Botones de acción: marcar leída y eliminar. @click.stop evita que el clic se propague al article padre -->
          <div class="notifications-item__actions" @click.stop>
            <!-- Botón "Marcar leída": solo se muestra si la notificación no fue leída aún -->
            <button
              v-if="!notification.is_read"
              type="button"
              class="btn-outline-small notifications-item__btn notifications-item__btn--read"
              :disabled="loadingAction"
              @click="markAsRead(notification.id)"
            >
              <i :class="loadingAction ? 'fas fa-spinner fa-spin' : 'fas fa-check'"></i>
              <span>{{ loadingAction ? 'Marcando...' : 'Marcar leída' }}</span>
            </button>

            <!-- Botón "Eliminar": siempre visible para poder borrar cualquier notificación -->
            <button
              type="button"
              class="btn-outline-small notifications-item__btn notifications-item__btn--delete"
              :disabled="loadingAction"
              @click="deleteOne(notification.id)"
            >
              <i :class="loadingAction ? 'fas fa-spinner fa-spin' : 'fas fa-trash-alt'"></i>
              <span>{{ loadingAction ? 'Eliminando...' : 'Eliminar' }}</span>
            </button>
          </div>
        </article>
      </transition-group>
    </section>
  </template>
</template>

<script setup>
// ── Imports de Vue ──────────────────────────────────────────────────────────
// computed: propiedades reactivas derivadas (se recalculan automáticamente).
// inject: accede a valores inyectados desde un componente ancestro (provide/inject).
// onMounted / onUnmounted: hooks del ciclo de vida para inicializar y limpiar recursos.
// ref: crea una variable reactiva que Vue observa para actualizar el DOM.
import { computed, inject, onMounted, onUnmounted, ref } from 'vue'
// useRouter: composable para navegar programáticamente entre rutas de la aplicación.
import { useRouter } from 'vue-router'
// Componente de efecto shimmer (esqueleto de carga) que se muestra mientras se cargan los datos.
import AccountShimmer from '../components/AccountShimmer.vue'
// Funciones del API para gestionar notificaciones: obtener, marcar como leídas y eliminar.
import {
  deleteNotification,
  getNotifications,
  markAllNotificationsRead,
  markNotificationRead,
} from '../../../services/notificationApi'
// useSession: composable que expone el usuario actual y su estado de sesión (isLoggedIn).
import { useSession } from '../../../composables/useSession'
// useAlertSystem: composable para mostrar alertas modales (éxito, error, confirmación).
import { useAlertSystem } from '../../../composables/useAlertSystem'
// useAppShell: composable para comunicar datos al shell de la aplicación (barra superior, etc.).
import { useAppShell } from '../../../composables/useAppShell'

// ── Inicialización de composables y dependencias ──────────────────────────────
const router = useRouter()
const { user, isLoggedIn } = useSession()
const { showAlert } = useAlertSystem()
const { setNotificationCount } = useAppShell()
// Inyecta la función del componente padre para actualizar el contador de notificaciones sin leer en la barra de navegación.
// Si no existe provide en el padre, usa una función vacía como respaldo.
const setAccountUnreadNotifications = inject('setAccountUnreadNotifications', () => {})

// ── Estado reactivo ──────────────────────────────────────────────────────────
// loading: controla la visibilidad del shimmer de carga durante la carga inicial.
const loading = ref(true)
// loadingAction: bloquea botones mientras se ejecuta una acción (marcar leída, eliminar, etc.) para evitar clics duplicados.
const loadingAction = ref(false)
// errorMessage: almacena el mensaje de error cuando falla la carga de notificaciones.
const errorMessage = ref('')
// notifications: array completo de notificaciones obtenidas del API.
const notifications = ref([])
// statusFilter: filtro activo por estado de lectura ('all', 'unread', 'read').
const statusFilter = ref('all')
// typeFilter: filtro activo por tipo de notificación ('all', 'order', 'product', etc.).
const typeFilter = ref('all')
// relativeNow: marca de tiempo actual que se actualiza cada segundo para calcular "hace X tiempo" en tiempo real.
const relativeNow = ref(Date.now())
// Intervalo en milisegundos para el polling de notificaciones (20 segundos).
const NOTIFICATIONS_POLLING_MS = 20000
// Identificador del intervalo del reloj relativo (para limpiarlo al desmontar).
let relativeClockTimer = null
// Identificador del intervalo de polling de notificaciones (para limpiarlo al desmontar).
let notificationsRefreshTimer = null

// ── Opciones de filtro por estado ────────────────────────────────────────────
// Cada objeto define el valor del filtro y la etiqueta visible en los botones tipo "pill".
const statusOptions = [
  { value: 'all', label: 'Todas' },
  { value: 'unread', label: 'Sin leer' },
  { value: 'read', label: 'Leídas' },
]

// ── Propiedades computadas ──────────────────────────────────────────────────
// unreadCount: calcula la cantidad de notificaciones no leídas filtrando por is_read === false.
const unreadCount = computed(() => notifications.value.filter((item) => !item?.is_read).length)
// readCount: calcula la cantidad de notificaciones ya leídas filtrando por is_read === true.
const readCount = computed(() => notifications.value.filter((item) => !!item?.is_read).length)

// filteredNotifications: devuelve solo las notificaciones que cumplen con ambos filtros activos
// (estado de lectura y tipo de entidad). Es la lista que realmente se renderiza en el DOM.
const filteredNotifications = computed(() => {
  return notifications.value.filter((item) => {
    // Determina si la notificación fue leída (convierte a booleano por seguridad).
    const read = !!item?.is_read
    // Extrae el tipo de entidad relacionada y lo normaliza a minúsculas; por defecto es 'system'.
    const entityType = String(item?.related_entity_type || 'system').toLowerCase()

    // Si el filtro es "leídas" pero la notificación no fue leída, la excluye.
    if (statusFilter.value === 'read' && !read) return false
    // Si el filtro es "sin leer" pero la notificación ya fue leída, la excluye.
    if (statusFilter.value === 'unread' && read) return false
    // Si se eligió un tipo específico y no coincide, la excluye.
    if (typeFilter.value !== 'all' && typeFilter.value !== entityType) return false

    return true
  })
})

// ── Ciclo de vida ───────────────────────────────────────────────────────────
// onMounted: se ejecuta cuando el componente se inserta en el DOM.
// Inicia el reloj relativo y carga las notificaciones por primera vez.
onMounted(async () => {
  // Actualiza relativeNow cada segundo para que los textos "hace X minutos" se refresquen en tiempo real.
  relativeClockTimer = window.setInterval(() => {
    relativeNow.value = Date.now()
  }, 1000)

  // Carga inicial de notificaciones con el shimmer de loading visible.
  await refreshNotifications({ showLoader: true })
  // Inicia el polling para refrescar notificaciones periódicamente en segundo plano.
  startNotificationsPolling()
})

// onUnmounted: se ejecuta cuando el componente se destruye (navegación a otra ruta).
// Limpia todos los intervalos para evitar fugas de memoria.
onUnmounted(() => {
  // Detiene el reloj relativo que actualiza "hace X tiempo".
  if (relativeClockTimer !== null) {
    window.clearInterval(relativeClockTimer)
    relativeClockTimer = null
  }

  // Detiene el polling de notificaciones.
  stopNotificationsPolling()
})

// ── Funciones de carga de datos ─────────────────────────────────────────────
// refreshNotifications: obtiene la lista completa de notificaciones del API.
// Parámetro showLoader: cuando es true, muestra el shimmer de carga y errores;
// cuando es false (polling silencioso), actualiza los datos sin mostrar indicadores.
async function refreshNotifications({ showLoader = false } = {}) {
  // Solo muestra el loader y limpia errores en la carga inicial (no en el polling).
  if (showLoader) {
    loading.value = true
    errorMessage.value = ''
  }

  try {
    // Si el usuario no tiene sesión activa, limpia las notificaciones y el contador global.
    if (!isLoggedIn.value) {
      notifications.value = []
      setAccountUnreadNotifications(0)
      setNotificationCount(0)
      return
    }

    // Extrae el ID y email del usuario para la consulta al API.
    const userId = String(user.value?.id || '').trim()
    const userEmail = String(user.value?.email || '').trim()
    // Llama al servicio API que retorna las notificaciones del usuario.
    const response = await getNotifications(userId, userEmail)
    // Asigna las notificaciones; valida que response.data sea un array para evitar errores.
    notifications.value = Array.isArray(response?.data) ? response.data : []
    // Actualiza el contador de notificaciones sin leer en el shell y en la barra de navegación del padre.
    setAccountUnreadNotifications(unreadCount.value)
    setNotificationCount(unreadCount.value)
  } catch {
    // En caso de error, solo muestra el mensaje si es la carga inicial (no en polling silencioso).
    if (showLoader) {
      errorMessage.value = 'No se pudieron cargar las notificaciones.'
    }
  } finally {
    // Oculta el loader solo si se mostró al inicio.
    if (showLoader) {
      loading.value = false
    }
  }
}

// ── Polling de notificaciones ────────────────────────────────────────────────
// startNotificationsPolling: inicia un intervalo que refresca las notificaciones
// cada 20 segundos. Si la pestaña del navegador está oculta, omite la petición
// para ahorrar recursos de red y CPU.
function startNotificationsPolling() {
  // Detiene cualquier polling existente antes de iniciar uno nuevo (evita duplicados).
  stopNotificationsPolling()

  notificationsRefreshTimer = window.setInterval(() => {
    // Si la pestaña está en segundo plano (hidden), no realiza la consulta.
    if (document.visibilityState === 'hidden') {
      return
    }

    // Refresca las notificaciones sin mostrar el shimmer de carga.
    refreshNotifications()
  }, NOTIFICATIONS_POLLING_MS)
}

// stopNotificationsPolling: limpia el intervalo de polling para evitar llamadas innecesarias al API
// y fugas de memoria cuando el componente se desmonta.
function stopNotificationsPolling() {
  if (notificationsRefreshTimer !== null) {
    window.clearInterval(notificationsRefreshTimer)
    notificationsRefreshTimer = null
  }
}

// ── Funciones de marcado de lectura ─────────────────────────────────────────
// markAsRead: marca una notificación individual como leída en el API y actualiza el estado local.
async function markAsRead(notificationId) {
  try {
    loadingAction.value = true
    // Llama al API para registrar que el usuario leyó la notificación.
    await markNotificationRead(notificationId)

    // Actualiza el array local: reemplaza la notificación modificada con is_read = true
    // usando map para no mutar el array original (inmutabilidad reactiva de Vue).
    notifications.value = notifications.value.map((item) => (
      Number(item.id) === Number(notificationId)
        ? { ...item, is_read: true }
        : item
    ))

    // Sincroniza el contador de no leídas con los componentes padre y el shell.
    setAccountUnreadNotifications(unreadCount.value)
    setNotificationCount(unreadCount.value)
  } catch {
    // Muestra una alerta de error si la petición al API falla.
    showAlert({
      type: 'error',
      title: 'No fue posible actualizar',
      message: 'No pudimos marcar la notificación como leída.',
    })
  } finally {
    // Desbloquea los botones de acción independientemente del resultado.
    loadingAction.value = false
  }
}

// ── Función de eliminación ──────────────────────────────────────────────────
// deleteOne: muestra un modal de confirmación antes de eliminar una notificación.
// Utiliza el sistema de alertas con un callback asíncrono que se ejecuta solo si
// el usuario confirma la acción.
function deleteOne(notificationId) {
  // Muestra un diálogo de confirmación con dos opciones: cancelar y eliminar.
  showAlert({
    type: 'question',
    title: 'Eliminar notificación',
    message: '¿Deseas eliminar esta notificación?',
    actions: [
      // Botón de cancelar: cierra el modal sin hacer nada.
      { text: 'Cancelar', style: 'secondary' },
      {
        text: 'Eliminar',
        style: 'danger',
        // Callback que se ejecuta al confirmar la eliminación.
        callback: async () => {
          try {
            loadingAction.value = true
            // Llama al API para eliminar la notificación del servidor.
            await deleteNotification(
              notificationId,
              String(user.value?.id || '').trim(),
              String(user.value?.email || '').trim(),
            )
            // Elimina la notificación del array local usando filter (inmutabilidad reactiva).
            notifications.value = notifications.value.filter((item) => Number(item.id) !== Number(notificationId))
            // Actualiza los contadores globales de notificaciones sin leer.
            setAccountUnreadNotifications(unreadCount.value)
            setNotificationCount(unreadCount.value)
          } catch {
            // Muestra alerta de error si la eliminación falla.
            showAlert({
              type: 'error',
              title: 'No fue posible eliminar',
              message: 'No pudimos eliminar la notificación. Intenta nuevamente.',
            })
          } finally {
            loadingAction.value = false
          }
        },
      },
    ],
  })
}

// ── Marcado masivo de lectura ───────────────────────────────────────────────
// markAllAsRead: marca todas las notificaciones pendientes como leídas de una sola vez.
async function markAllAsRead() {
  // Si no hay notificaciones sin leer, no hace nada (evita una llamada innecesaria al API).
  if (unreadCount.value === 0) return

  try {
    loadingAction.value = true
    // Llama al API para marcar todas las notificaciones del usuario como leídas.
    await markAllNotificationsRead(
      String(user.value?.id || '').trim(),
      String(user.value?.email || '').trim(),
    )

    // Actualiza el array local: todas las notificaciones ahora tienen is_read = true.
    notifications.value = notifications.value.map((item) => ({
      ...item,
      is_read: true,
    }))

    // Resetea los contadores de notificaciones sin leer a cero.
    setAccountUnreadNotifications(0)
    setNotificationCount(0)

    // Muestra una alerta de éxito que se cierra automáticamente después de 4 segundos.
    showAlert({
      type: 'success',
      title: 'Listo',
      message: 'Todas las notificaciones fueron marcadas como leídas.',
      autoCloseSeconds: 4,
    })
  } catch {
    // Muestra alerta de error si la operación falla.
    showAlert({
      type: 'error',
      title: 'No fue posible actualizar',
      message: 'No pudimos marcar todas las notificaciones como leídas.',
    })
  } finally {
    loadingAction.value = false
  }
}

// ── Manejo de apertura de notificación ──────────────────────────────────────
// handleOpenNotification: se ejecuta al hacer clic en una notificación de la lista.
// Si la notificación no fue leída, la marca como leída automáticamente.
// Si es una notificación de tipo "order", navega a la página de órdenes con el ID de la orden.
function handleOpenNotification(notification) {
  // Marca como leída si aún no lo está (la función es idempotente en el API).
  if (!notification?.is_read) {
    markAsRead(notification.id)
  }

  // Si la notificación está relacionada con una orden, navega a la vista de órdenes
  // pasando el ID de la orden como parámetro de consulta para resaltarla.
  if (String(notification?.related_entity_type || '') === 'order' && notification?.related_entity_id) {
    router.push({ name: 'account-orders', query: { order: notification.related_entity_id } })
  }
}

// ── Funciones utilitarias de tipo de notificación ───────────────────────────
// notificationType: normaliza el tipo de entidad relacionada de la notificación.
// Si el tipo no está en la lista permitida, devuelve 'system' como valor por defecto.
function notificationType(notification) {
  const type = String(notification?.related_entity_type || 'system').toLowerCase()
  // Lista blanca de tipos válidos; cualquier otro valor se tratará como 'system'.
  if (['order', 'product', 'promotion', 'account', 'system'].includes(type)) return type
  return 'system'
}

// notificationTypeLabel: retorna la etiqueta en español legible para el usuario
// según el tipo de notificación (se usa en el badge de color al lado del título).
function notificationTypeLabel(notification) {
  const type = notificationType(notification)
  if (type === 'order') return 'Orden'
  if (type === 'product') return 'Producto'
  if (type === 'promotion') return 'Promoción'
  if (type === 'account') return 'Cuenta'
  return 'Sistema'
}

// notificationIcon: retorna la clase de icono de Font Awesome según el tipo de notificación.
// Cada tipo tiene un icono visualmente representativo de su naturaleza.
function notificationIcon(notification) {
  const type = notificationType(notification)
  if (type === 'order') return 'fas fa-shopping-bag'
  if (type === 'product') return 'fas fa-tag'
  if (type === 'promotion') return 'fas fa-gift'
  if (type === 'account') return 'fas fa-user'
  return 'fas fa-info-circle'
}

// ── Formateo de tiempo relativo ─────────────────────────────────────────────
// formatTimeAgo: convierte una marca de tiempo en un texto legible como "hace 5 minutos".
// Usa relativeNow (que se actualiza cada segundo) para que el texto se refresque en tiempo real.
// Para marcas de tiempo antiguas (más de 7 días), muestra la fecha absoluta en formato local.
function formatTimeAgo(value) {
  if (!value) return 'Ahora'

  // Fuerza lectura UTC cuando llega timestamp sin zona horaria (paridad con backend UTC).
  const createdAt = parseNotificationDate(value)
  if (!createdAt) return 'Ahora'

  // Calcula la diferencia en segundos entre ahora y la fecha de creación.
  const seconds = Math.max(0, Math.floor((relativeNow.value - createdAt.getTime()) / 1000))
  if (seconds < 5) return 'Hace unos segundos'

  if (seconds < 60) return `Hace ${seconds} segundo${seconds === 1 ? '' : 's'}`

  const minutes = Math.floor(seconds / 60)
  if (minutes < 60) return `Hace ${minutes} minuto${minutes === 1 ? '' : 's'}`

  const hours = Math.floor(minutes / 60)
  if (hours < 24) return `Hace ${hours} hora${hours === 1 ? '' : 's'}`

  const days = Math.floor(hours / 24)
  if (days < 7) return `Hace ${days} día${days === 1 ? '' : 's'}`

  // Para notificaciones antiguas (más de 7 días), muestra la fecha en formato dd/mm/aaaa.
  return createdAt.toLocaleDateString('es-CO', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
  })
}

// ── Parseo de fechas de notificaciones ──────────────────────────────────────
// parseNotificationDate: convierte un string de fecha (ISO o formato "YYYY-MM-DD HH:mm:ss")
// en un objeto Date válido. Maneja timestamps sin zona horaria añadiendo 'Z' (UTC)
// para mantener consistencia con el backend que almacena en UTC.
function parseNotificationDate(value) {
  const raw = String(value || '').trim()
  if (!raw) return null

  // Normaliza el separador: reemplaza espacio por 'T' para formar ISO 8601 válido.
  const iso = raw.includes('T') ? raw : raw.replace(' ', 'T')
  // Verifica si ya tiene zona horaria (Z o +/-HH:MM).
  const hasTimezone = /(Z|[+-]\d{2}:?\d{2})$/i.test(iso)
  // Si no tiene zona horaria, asume UTC (Z) para paridad con el backend.
  const normalized = hasTimezone ? iso : `${iso}Z`

  // Intenta parsear la fecha normalizada.
  const parsed = new Date(normalized)
  if (!Number.isNaN(parsed.getTime())) {
    return parsed
  }

  // Fallback: intenta parsear el string original sin normalización.
  const fallback = new Date(raw)
  return Number.isNaN(fallback.getTime()) ? null : fallback
}
</script>

<style scoped>
.notifications-header-panel {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 1.2rem;
}

.notifications-header-panel__copy h1 {
  display: inline-flex;
  align-items: center;
  gap: 0.85rem;
}

.notifications-header-panel__copy h1 i {
  width: 4.2rem;
  height: 4.2rem;
  border-radius: 50%;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  background: rgba(0, 119, 182, 0.1);
  color: #0077b6;
  font-size: 1.9rem;
}

.notifications-header-panel__action {
  flex-shrink: 0;
}

.notification-summary-card {
  min-height: 9.2rem;
}

.notification-summary-card__icon--total {
  background: #dff4fb;
  color: #0077b6;
}

.notification-summary-card__icon--unread {
  background: #fff3df;
  color: #d97706;
}

.notification-summary-card__icon--read {
  background: #e3f8e8;
  color: #16a34a;
}

.notifications-board__header {
  align-items: flex-end;
}

.notifications-board__heading {
  display: flex;
  flex-direction: column;
  gap: 0.3rem;
}

.notifications-board__meta {
  margin: 0;
  font-size: 1.35rem;
  color: #6b7280;
}

.notifications-toolbar {
  display: flex;
  flex-wrap: wrap;
  gap: 0.8rem;
  margin-bottom: 1.6rem;
}

.notifications-status-pill {
  display: inline-flex;
  align-items: center;
  gap: 0.45rem;
  padding: 0.65rem 1.25rem;
  border-radius: 999px;
  border: 1px solid #d5dbe3;
  background: #fff;
  color: #475569;
  font-size: 1.35rem;
  font-weight: 700;
  cursor: pointer;
  transition: all 0.18s ease;
}

.notifications-status-pill:hover {
  border-color: #90e0ef;
  color: #0077b6;
}

.notifications-status-pill.is-active {
  background: #0077b6;
  border-color: #0077b6;
  color: #fff;
}

.notifications-status-pill__badge {
  min-width: 2rem;
  height: 2rem;
  padding: 0 0.45rem;
  border-radius: 999px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  font-size: 1.15rem;
  font-weight: 700;
  background: rgba(0, 119, 182, 0.08);
}

.notifications-status-pill.is-active .notifications-status-pill__badge {
  background: rgba(255, 255, 255, 0.2);
}

.notif-type-select-wrap {
  position: relative;
}

.notif-type-select-icon {
  position: absolute;
  left: 1rem;
  top: 50%;
  transform: translateY(-50%);
  color: #94a3b8;
  font-size: 1.2rem;
  pointer-events: none;
}

.notif-type-select {
  min-width: 18rem;
  padding: 0.75rem 1rem 0.75rem 2.7rem;
  border: 1px solid #d5dbe3;
  border-radius: 10px;
  background: #fff;
  font-size: 1.4rem;
  color: #475569;
  appearance: none;
}

.notifications-loading {
  display: grid;
  gap: 1rem;
}

.notifications-skeleton {
  display: flex;
  align-items: center;
  gap: 1.1rem;
  padding: 1.4rem;
  border: 1px solid #e5edf5;
  border-radius: 12px;
  background: #f9fbfd;
  animation: notifications-pulse 1.35s ease-in-out infinite;
}

.notifications-skeleton__icon {
  width: 4.4rem;
  height: 4.4rem;
  border-radius: 50%;
  background: #e2e8f0;
  flex-shrink: 0;
}

.notifications-skeleton__body {
  flex: 1;
  display: grid;
  gap: 0.55rem;
}

.notifications-skeleton__line {
  height: 1.1rem;
  border-radius: 999px;
  background: #e2e8f0;
}

.notifications-skeleton__line--title {
  width: 36%;
}

.notifications-skeleton__line--text {
  width: 72%;
}

@keyframes notifications-pulse {
  0%, 100% { opacity: 1; }
  50% { opacity: 0.58; }
}

.notifications-empty-state span {
  display: block;
  color: #94a3b8;
  font-size: 1.35rem;
}

.notifications-list {
  display: grid;
  gap: 1rem;
}

.notifications-list-enter-active,
.notifications-list-leave-active {
  transition: opacity 0.24s ease, transform 0.24s ease;
}

.notifications-list-enter-from,
.notifications-list-leave-to {
  opacity: 0;
  transform: translateY(-6px);
}

.notifications-item {
  display: grid;
  grid-template-columns: auto minmax(0, 1fr) auto;
  gap: 1.2rem;
  align-items: flex-start;
  padding: 1.45rem;
  border: 1px solid #d5dbe3;
  border-radius: 12px;
  background: #fff;
  cursor: pointer;
  transition: border-color 0.18s ease, box-shadow 0.18s ease, background 0.18s ease;
}

.notifications-item:hover {
  border-color: #90e0ef;
  box-shadow: 0 3px 10px rgba(0, 119, 182, 0.08);
}

.notifications-item.is-unread {
  background: #f8fbff;
  border-left: 4px solid #0077b6;
}

.notifications-item__leading {
  position: relative;
}

.notifications-item__unread-dot {
  position: absolute;
  top: -0.15rem;
  left: -0.1rem;
  width: 0.8rem;
  height: 0.8rem;
  border-radius: 50%;
  background: #0077b6;
}

.notifications-item__icon {
  width: 4.6rem;
  height: 4.6rem;
  border-radius: 50%;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  font-size: 1.85rem;
}

.notifications-item__icon--order {
  background: #dff4fb;
  color: #0077b6;
}

.notifications-item__icon--product {
  background: #e8f7fb;
  color: #0284c7;
}

.notifications-item__icon--promotion {
  background: #fce7f3;
  color: #db2777;
}

.notifications-item__icon--account {
  background: #f3e8ff;
  color: #9333ea;
}

.notifications-item__icon--system {
  background: #eef2f7;
  color: #475569;
}

.notifications-item__body {
  min-width: 0;
}

.notifications-item__top {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 1rem;
  margin-bottom: 0.55rem;
}

.notifications-item__headline {
  display: flex;
  align-items: center;
  gap: 0.55rem;
  flex-wrap: wrap;
}

.notifications-item__headline h3 {
  margin: 0;
  font-size: 1.9rem;
  color: #1f2937;
  line-height: 1.2;
}

.notifications-type-badge {
  display: inline-flex;
  align-items: center;
  border-radius: 999px;
  padding: 0.3rem 0.8rem;
  font-size: 1.2rem;
  font-weight: 700;
}

.notifications-type-badge--order {
  background: #dbeafe;
  color: #2563eb;
}

.notifications-type-badge--product {
  background: #e0f2fe;
  color: #0284c7;
}

.notifications-type-badge--promotion {
  background: #fce7f3;
  color: #db2777;
}

.notifications-type-badge--account {
  background: #f3e8ff;
  color: #9333ea;
}

.notifications-type-badge--system {
  background: #e2e8f0;
  color: #475569;
}

.notifications-item__time {
  display: inline-flex;
  align-items: center;
  gap: 0.4rem;
  color: #94a3b8;
  font-size: 1.28rem;
  white-space: nowrap;
}

.notifications-item__message {
  margin: 0;
  font-size: 1.55rem;
  line-height: 1.5;
  color: #475569;
}

.notifications-item__actions {
  display: flex;
  flex-direction: column;
  gap: 0.6rem;
}

.notifications-item__btn {
  min-width: 17rem;
  justify-content: center;
  padding: 0.8rem 1.2rem;
}

.notifications-item__btn--delete {
  border-color: #fecaca;
  color: #dc2626;
}

.notifications-item__btn--delete:hover:not(:disabled) {
  background: rgba(220, 38, 38, 0.06);
}

@media (max-width: 980px) {
  .notifications-header-panel,
  .notifications-board__header,
  .notifications-item,
  .notifications-item__top {
    grid-template-columns: unset;
    flex-direction: column;
  }

  .notifications-header-panel,
  .notifications-board__header,
  .notifications-item__top {
    display: flex;
    align-items: stretch;
  }

  .notifications-item {
    display: flex;
  }

  .notifications-item__actions {
    width: 100%;
    flex-direction: row;
  }

  .notifications-item__btn {
    flex: 1;
    min-width: 0;
  }
}

@media (max-width: 640px) {
  .notifications-toolbar {
    gap: 0.6rem;
  }

  .notifications-status-pill {
    flex: 1 1 auto;
    justify-content: center;
  }

  .notif-type-select {
    width: 100%;
    min-width: 0;
  }

  .notifications-item__headline h3 {
    font-size: 1.65rem;
  }

  .notifications-item__message {
    font-size: 1.45rem;
  }

  .notifications-item__actions {
    flex-direction: column;
  }
}
</style>
