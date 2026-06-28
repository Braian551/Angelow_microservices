<template>
  <AccountDashboardLayout
    :active-section="activeSection"
    :unread-notifications="unreadNotifications"
  >
    <RouterView />
  </AccountDashboardLayout>
</template>

<script setup>
/* ============================================================
 * IMPORTACIONES
 * ============================================================ */
// Funciones reactivas y de ciclo de vida de Vue 3 Composition API
import { computed, onMounted, onUnmounted, provide, readonly, ref } from 'vue'
// RouterView renderiza la ruta hija; useRoute da acceso a la ruta actual
import { RouterView, useRoute } from 'vue-router'
// Composable que gestiona la sesión del usuario (datos, token, persistencia)
import { useSession } from '../../../composables/useSession'
// Servicio para obtener el perfil del usuario autenticado desde la API
import { getProfile } from '../../../services/authApi'
// Servicio para obtener las notificaciones del usuario desde la API
import { getNotifications } from '../../../services/notificationApi'
// Componente de layout que estructura el dashboard de la cuenta
import AccountDashboardLayout from '../components/AccountDashboardLayout.vue'

/* ============================================================
 * ESTADO REACTIVO Y CONFIGURACIÓN
 * ============================================================ */

// Referencia a la ruta actual, permite leer metadatos como la sección activa
const route = useRoute()
// Datos de sesión del usuario: objeto user, token JWT y función para guardar la sesión
const { user, token, saveSession } = useSession()

// Contador reactivo de notificaciones no leídas que se muestra en la UI
const unreadNotifications = ref(0)
// Intervalo en milisegundos para consultar notificaciones periódicamente (20 segundos)
const NOTIFICATIONS_POLLING_MS = 20000
// Identificador del temporizador de polling; null cuando no está activo
let unreadNotificationsTimer = null

/* ============================================================
 * COMPUTED (PROPIEDADES CALCULADAS)
 * ============================================================ */

// Determina la sección activa del dashboard a partir de los metadatos de la ruta.
// Si la ruta no define accountSection, usa 'dashboard' como valor por defecto.
// Se convierte a String por seguridad en caso de que el meta sea undefined.
const activeSection = computed(() => String(route.meta?.accountSection || 'dashboard'))

/* ============================================================
 * PROVEEDORES (provide/inject)
 * ============================================================ */

// Provee el contador de notificaciones no leídas como valor de solo lectura
// a todos los componentes hijos, evitando modificaciones accidentales desde fuera.
provide('accountUnreadNotifications', readonly(unreadNotifications))
// Provee una función para establecer el contador de notificaciones de forma programática
provide('setAccountUnreadNotifications', setAccountUnreadNotifications)
// Provee una función para forzar una recarga inmediata del contador de notificaciones
provide('refreshAccountUnreadNotifications', refreshUnreadNotifications)

/* ============================================================
 * CICLOS DE VIDA
 * ============================================================ */

// Al montar el componente: sincroniza el perfil del usuario con el servidor,
// carga las notificaciones no leídas iniciales e inicia el polling periódico.
onMounted(async () => {
  await syncProfile()
  await refreshUnreadNotifications()
  startUnreadNotificationsPolling()
})

// Al desmontar el componente: detiene el polling para evitar fugas de memoria
// y llamadas innecesarias a la API cuando el usuario navega fuera del dashboard.
onUnmounted(() => {
  stopUnreadNotificationsPolling()
})

/* ============================================================
 * FUNCIONES
 * ============================================================ */

/**
 * Establece el valor del contador de notificaciones no leídas.
 * Utiliza normalizeCount para validar y normalizar el valor recibido.
 * Expuesta vía provide para que componentes hijos puedan actualizar el contador.
 */
function setAccountUnreadNotifications(value) {
  unreadNotifications.value = normalizeCount(value)
}

/**
 * Sincroniza el perfil del usuario con el servidor.
 * Obtiene los datos más recientes del perfil y los combina con los existentes en la sesión.
 * Esto asegura que la UI muestre información actualizada (nombre, avatar, etc.).
 *
 * Flujo:
 * 1. Verifica que exista un usuario con ID y un token válidos.
 * 2. Llama a la API para obtener el perfil actualizado.
 * 3. Extrae el perfil de la respuesta (admite diferentes estructuras de respuesta).
 * 4. Si el perfil es válido, fusiona los datos nuevos con los existentes y guarda la sesión.
 * 5. Si falla, simplemente silencia el error para no romper la experiencia del usuario.
 */
async function syncProfile() {
  // Guard: si no hay usuario autenticado o no hay token, no realiza la petición
  if (!user.value?.id || !token.value) return

  try {
    // Solicita el perfil actualizado al servidor de autenticación
    const response = await getProfile()
    // El perfil puede venir en response.data o response.user según el formato de la API
    const profile = response?.data || response?.user || null

    // Si la respuesta no contiene un perfil válido, aborta la sincronización
    if (!profile || typeof profile !== 'object') return

    // Fusiona los datos del perfil nuevo sobre los existentes y guarda la sesión actualizada
    saveSession(token.value, {
      ...user.value,
      ...profile,
    })
  } catch {
    // El layout mantiene el dashboard funcional aunque falle el sync.
    // No se muestra error al usuario para no interrumpir su navegación.
  }
}

/**
 * Actualiza el contador de notificaciones no leídas consultando la API.
 *
 * Flujo:
 * 1. Verifica que el usuario tenga identificador o email para poder consultar.
 * 2. Llama a la API de notificaciones con el ID y email del usuario.
 * 3. Si la respuesta contiene un array de datos, filtra las no leídas y cuenta.
 * 4. Si la estructura de respuesta no es la esperada o falla, resetea el contador a 0.
 *
 * El contador se usa para mostrar un badge o indicador visual en la UI.
 */
async function refreshUnreadNotifications() {
  // Guard: si el usuario no tiene ID ni email, no hay notificaciones que consultar
  if (!user.value?.id && !user.value?.email) {
    unreadNotifications.value = 0
    return
  }

  try {
    // Solicita las notificaciones del usuario a la API
    // Se pasan ID y email como strings vacíos si no existen, para cumplir la firma
    const notificationsResponse = await getNotifications(
      String(user.value?.id || '').trim(),
      String(user.value?.email || '').trim(),
    )

    // Si la respuesta contiene un array, filtra las notificaciones no leídas
    // y actualiza el contador con la cantidad resultante
    if (Array.isArray(notificationsResponse?.data)) {
      unreadNotifications.value = notificationsResponse.data.filter((item) => !item?.is_read).length
      return
    }
  } catch {
    // Si falla la carga, no se rompe la navegación del dashboard.
    // El contador se mantiene en su último valor conocido o se resetea a 0.
  }

  // Si la respuesta no fue un array válido o hubo error, resetea el contador
  unreadNotifications.value = 0
}

/**
 * Inicia el polling (consulta periódica) de notificaciones no leídas.
 * Detiene cualquier polling existente antes de iniciar uno nuevo para evitar duplicados.
 * Solo consulta la API cuando la pestaña del navegador está visible (document.visibilityState),
 * para evitar llamadas innecesarias cuando el usuario no está viendo la aplicación.
 */
function startUnreadNotificationsPolling() {
  // Detiene el polling previo si existe, garantizando un único intervalo activo
  stopUnreadNotificationsPolling()

  // Crea un intervalo que ejecuta refreshUnreadNotifications cada 20 segundos
  unreadNotificationsTimer = window.setInterval(() => {
    // Si la pestaña no está visible, omite la consulta para ahorrar recursos
    if (document.visibilityState === 'hidden') {
      return
    }

    // Actualiza el contador de notificaciones no leídas
    refreshUnreadNotifications()
  }, NOTIFICATIONS_POLLING_MS)
}

/**
 * Detiene el polling de notificaciones no leídas.
 * Limpia el temporizador y establece la referencia a null para indicar que no hay polling activo.
 * Esto previene fugas de memoria y llamadas innecesarias a la API.
 */
function stopUnreadNotificationsPolling() {
  // Solo intenta limpiar si hay un timer activo (no es null)
  if (unreadNotificationsTimer !== null) {
    window.clearInterval(unreadNotificationsTimer)
    unreadNotificationsTimer = null
  }
}

/**
 * Normaliza un valor numérico para usarlo como contador de notificaciones.
 * Convierte el valor a número, verifica que sea finito y no negativo,
 * y redondea hacia abajo para obtener un entero válido.
 *
 * Si el valor no es válido (NaN, Infinity, negativo), retorna 0.
 * Esto protege la UI de mostrar valores incorrectos o decimales.
 */
function normalizeCount(value) {
  // Intenta convertir el valor a número
  const parsed = Number(value)
  // Si no es un número finito o es negativo, retorna 0 como valor seguro
  if (!Number.isFinite(parsed) || parsed < 0) return 0
  // Redondea hacia abajo para obtener un entero (no decimales de notificaciones)
  return Math.floor(parsed)
}
</script>
