<template>
  <section class="dashboard-header notifications-header-panel">
    <div class="notifications-header-panel__copy">
      <h1>
        <i class="fas fa-bell"></i>
        Mis notificaciones
      </h1>
      <p>Mantente al día con tus pedidos, novedades y movimientos importantes de tu cuenta.</p>
    </div>

    <button
      v-if="unreadCount > 0 && !loading"
      type="button"
      class="btn-outline-small notifications-header-panel__action"
      :disabled="loadingAction"
      @click="markAllAsRead"
    >
      <i class="fas fa-check-double"></i>
      Marcar todas como leídas
    </button>
  </section>

  <section class="account-grid-2 notifications-summary-grid">
    <article class="summary-card notification-summary-card">
      <div class="summary-icon notification-summary-card__icon notification-summary-card__icon--total">
        <i class="fas fa-envelope"></i>
      </div>
      <div class="summary-content">
        <h3>Total</h3>
        <p>{{ notifications.length }} notificación{{ notifications.length === 1 ? '' : 'es' }}</p>
      </div>
    </article>

    <article class="summary-card notification-summary-card">
      <div class="summary-icon notification-summary-card__icon notification-summary-card__icon--unread">
        <i class="fas fa-envelope-open-text"></i>
      </div>
      <div class="summary-content">
        <h3>Sin leer</h3>
        <p>{{ unreadCount }} pendiente{{ unreadCount === 1 ? '' : 's' }}</p>
      </div>
    </article>

    <article class="summary-card notification-summary-card">
      <div class="summary-icon notification-summary-card__icon notification-summary-card__icon--read">
        <i class="fas fa-check-double"></i>
      </div>
      <div class="summary-content">
        <h3>Leídas</h3>
        <p>{{ readCount }} revisada{{ readCount === 1 ? '' : 's' }}</p>
      </div>
    </article>
  </section>

  <section class="account-card notifications-board">
    <header class="section-header notifications-board__header">
      <div class="notifications-board__heading">
        <h2>Bandeja de notificaciones</h2>
        <p class="notifications-board__meta">
          {{ filteredNotifications.length }} resultado{{ filteredNotifications.length === 1 ? '' : 's' }} visibles
        </p>
      </div>

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

    <div class="notifications-toolbar">
      <button
        v-for="opt in statusOptions"
        :key="opt.value"
        type="button"
        class="notifications-status-pill"
        :class="{ 'is-active': statusFilter === opt.value }"
        @click="statusFilter = opt.value"
      >
        {{ opt.label }}
        <span v-if="opt.value === 'unread' && unreadCount > 0" class="notifications-status-pill__badge">{{ unreadCount }}</span>
      </button>
    </div>

    <div v-if="loading" class="notifications-loading">
      <div v-for="n in 4" :key="n" class="notifications-skeleton">
        <div class="notifications-skeleton__icon"></div>
        <div class="notifications-skeleton__body">
          <div class="notifications-skeleton__line notifications-skeleton__line--title"></div>
          <div class="notifications-skeleton__line notifications-skeleton__line--text"></div>
        </div>
      </div>
    </div>

    <div v-else-if="errorMessage" class="empty-state notifications-empty-state">
      <i class="fas fa-exclamation-circle"></i>
      <p>{{ errorMessage }}</p>
    </div>

    <div v-else-if="filteredNotifications.length === 0" class="empty-state notifications-empty-state">
      <i class="fas fa-bell-slash"></i>
      <p>No tienes notificaciones para este filtro.</p>
      <span>Cuando haya novedades relevantes las verás aquí.</span>
    </div>

    <transition-group v-else name="notifications-list" tag="div" class="notifications-list">
      <article
        v-for="notification in filteredNotifications"
        :key="notification.id"
        class="notifications-item"
        :class="{ 'is-unread': !notification.is_read }"
        @click="handleOpenNotification(notification)"
      >
        <div class="notifications-item__leading">
          <span v-if="!notification.is_read" class="notifications-item__unread-dot" title="No leída"></span>
          <div class="notifications-item__icon" :class="`notifications-item__icon--${notificationType(notification)}`">
            <i :class="notificationIcon(notification)"></i>
          </div>
        </div>

        <div class="notifications-item__body">
          <div class="notifications-item__top">
            <div class="notifications-item__headline">
              <h3>{{ notification.title }}</h3>
              <span :class="`notifications-type-badge notifications-type-badge--${notificationType(notification)}`">
                {{ notificationTypeLabel(notification) }}
              </span>
            </div>

            <span class="notifications-item__time">
              <i class="far fa-clock"></i>
              {{ formatTimeAgo(notification.created_at) }}
            </span>
          </div>

          <p class="notifications-item__message">{{ notification.message }}</p>
        </div>

        <div class="notifications-item__actions" @click.stop>
          <button
            v-if="!notification.is_read"
            type="button"
            class="btn-outline-small notifications-item__btn notifications-item__btn--read"
            :disabled="loadingAction"
            @click="markAsRead(notification.id)"
          >
            <i class="fas fa-check"></i>
            <span>Marcar leída</span>
          </button>

          <button
            type="button"
            class="btn-outline-small notifications-item__btn notifications-item__btn--delete"
            :disabled="loadingAction"
            @click="deleteOne(notification.id)"
          >
            <i class="fas fa-trash-alt"></i>
            <span>Eliminar</span>
          </button>
        </div>
      </article>
    </transition-group>
  </section>
</template>

<script setup>
import { computed, inject, onMounted, onUnmounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import {
  deleteNotification,
  getNotifications,
  markAllNotificationsRead,
  markNotificationRead,
} from '../../../services/notificationApi'
import { useSession } from '../../../composables/useSession'
import { useAlertSystem } from '../../../composables/useAlertSystem'

const router = useRouter()
const { user, isLoggedIn } = useSession()
const { showAlert } = useAlertSystem()
const setAccountUnreadNotifications = inject('setAccountUnreadNotifications', () => {})

const loading = ref(true)
const loadingAction = ref(false)
const errorMessage = ref('')
const notifications = ref([])
const statusFilter = ref('all')
const typeFilter = ref('all')
const relativeNow = ref(Date.now())
const NOTIFICATIONS_POLLING_MS = 20000
let relativeClockTimer = null
let notificationsRefreshTimer = null

const statusOptions = [
  { value: 'all', label: 'Todas' },
  { value: 'unread', label: 'Sin leer' },
  { value: 'read', label: 'Leídas' },
]

const unreadCount = computed(() => notifications.value.filter((item) => !item?.is_read).length)
const readCount = computed(() => notifications.value.filter((item) => !!item?.is_read).length)

const filteredNotifications = computed(() => {
  return notifications.value.filter((item) => {
    const read = !!item?.is_read
    const entityType = String(item?.related_entity_type || 'system').toLowerCase()

    if (statusFilter.value === 'read' && !read) return false
    if (statusFilter.value === 'unread' && read) return false
    if (typeFilter.value !== 'all' && typeFilter.value !== entityType) return false

    return true
  })
})

onMounted(async () => {
  relativeClockTimer = window.setInterval(() => {
    relativeNow.value = Date.now()
  }, 1000)

  await refreshNotifications({ showLoader: true })
  startNotificationsPolling()
})

onUnmounted(() => {
  if (relativeClockTimer !== null) {
    window.clearInterval(relativeClockTimer)
    relativeClockTimer = null
  }

  stopNotificationsPolling()
})

async function refreshNotifications({ showLoader = false } = {}) {
  if (showLoader) {
    loading.value = true
    errorMessage.value = ''
  }

  try {
    if (!isLoggedIn.value) {
      notifications.value = []
      setAccountUnreadNotifications(0)
      return
    }

    const userId = String(user.value?.id || '').trim()
    const userEmail = String(user.value?.email || '').trim()
    const response = await getNotifications(userId, userEmail)
    notifications.value = Array.isArray(response?.data) ? response.data : []
    setAccountUnreadNotifications(unreadCount.value)
  } catch {
    if (showLoader) {
      errorMessage.value = 'No se pudieron cargar las notificaciones.'
    }
  } finally {
    if (showLoader) {
      loading.value = false
    }
  }
}

function startNotificationsPolling() {
  stopNotificationsPolling()

  notificationsRefreshTimer = window.setInterval(() => {
    if (document.visibilityState === 'hidden') {
      return
    }

    refreshNotifications()
  }, NOTIFICATIONS_POLLING_MS)
}

function stopNotificationsPolling() {
  if (notificationsRefreshTimer !== null) {
    window.clearInterval(notificationsRefreshTimer)
    notificationsRefreshTimer = null
  }
}

async function markAsRead(notificationId) {
  try {
    loadingAction.value = true
    await markNotificationRead(notificationId)

    notifications.value = notifications.value.map((item) => (
      Number(item.id) === Number(notificationId)
        ? { ...item, is_read: true }
        : item
    ))

    setAccountUnreadNotifications(unreadCount.value)
  } catch {
    showAlert({
      type: 'error',
      title: 'No fue posible actualizar',
      message: 'No pudimos marcar la notificación como leída.',
    })
  } finally {
    loadingAction.value = false
  }
}

function deleteOne(notificationId) {
  showAlert({
    type: 'question',
    title: 'Eliminar notificación',
    message: '¿Deseas eliminar esta notificación?',
    actions: [
      { text: 'Cancelar', style: 'secondary' },
      {
        text: 'Eliminar',
        style: 'danger',
        callback: async () => {
          try {
            loadingAction.value = true
            await deleteNotification(
              notificationId,
              String(user.value?.id || '').trim(),
              String(user.value?.email || '').trim(),
            )
            notifications.value = notifications.value.filter((item) => Number(item.id) !== Number(notificationId))
            setAccountUnreadNotifications(unreadCount.value)
          } catch {
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

async function markAllAsRead() {
  if (unreadCount.value === 0) return

  try {
    loadingAction.value = true
    await markAllNotificationsRead(
      String(user.value?.id || '').trim(),
      String(user.value?.email || '').trim(),
    )

    notifications.value = notifications.value.map((item) => ({
      ...item,
      is_read: true,
    }))

    setAccountUnreadNotifications(0)

    showAlert({
      type: 'success',
      title: 'Listo',
      message: 'Todas las notificaciones fueron marcadas como leídas.',
      autoCloseSeconds: 4,
    })
  } catch {
    showAlert({
      type: 'error',
      title: 'No fue posible actualizar',
      message: 'No pudimos marcar todas las notificaciones como leídas.',
    })
  } finally {
    loadingAction.value = false
  }
}

function handleOpenNotification(notification) {
  if (!notification?.is_read) {
    markAsRead(notification.id)
  }

  if (String(notification?.related_entity_type || '') === 'order' && notification?.related_entity_id) {
    router.push({ name: 'account-orders', query: { order: notification.related_entity_id } })
  }
}

function notificationType(notification) {
  const type = String(notification?.related_entity_type || 'system').toLowerCase()
  if (['order', 'product', 'promotion', 'account', 'system'].includes(type)) return type
  return 'system'
}

function notificationTypeLabel(notification) {
  const type = notificationType(notification)
  if (type === 'order') return 'Orden'
  if (type === 'product') return 'Producto'
  if (type === 'promotion') return 'Promoción'
  if (type === 'account') return 'Cuenta'
  return 'Sistema'
}

function notificationIcon(notification) {
  const type = notificationType(notification)
  if (type === 'order') return 'fas fa-shopping-bag'
  if (type === 'product') return 'fas fa-tag'
  if (type === 'promotion') return 'fas fa-gift'
  if (type === 'account') return 'fas fa-user'
  return 'fas fa-info-circle'
}

function formatTimeAgo(value) {
  if (!value) return 'Ahora'

  // Fuerza lectura UTC cuando llega timestamp sin zona horaria (paridad con backend UTC).
  const createdAt = parseNotificationDate(value)
  if (!createdAt) return 'Ahora'

  const seconds = Math.max(0, Math.floor((relativeNow.value - createdAt.getTime()) / 1000))
  if (seconds < 5) return 'Hace unos segundos'

  if (seconds < 60) return `Hace ${seconds} segundo${seconds === 1 ? '' : 's'}`

  const minutes = Math.floor(seconds / 60)
  if (minutes < 60) return `Hace ${minutes} minuto${minutes === 1 ? '' : 's'}`

  const hours = Math.floor(minutes / 60)
  if (hours < 24) return `Hace ${hours} hora${hours === 1 ? '' : 's'}`

  const days = Math.floor(hours / 24)
  if (days < 7) return `Hace ${days} día${days === 1 ? '' : 's'}`

  return createdAt.toLocaleDateString('es-CO', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
  })
}

function parseNotificationDate(value) {
  const raw = String(value || '').trim()
  if (!raw) return null

  const iso = raw.includes('T') ? raw : raw.replace(' ', 'T')
  const hasTimezone = /(Z|[+-]\d{2}:?\d{2})$/i.test(iso)
  const normalized = hasTimezone ? iso : `${iso}Z`

  const parsed = new Date(normalized)
  if (!Number.isNaN(parsed.getTime())) {
    return parsed
  }

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
