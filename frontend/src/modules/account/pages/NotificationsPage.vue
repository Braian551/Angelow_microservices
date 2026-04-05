<template>
  <section class="dashboard-header notifications-header-panel">
    <h1><i class="fas fa-bell" /> Mis notificaciones</h1>
    <p>Mantente al día con tus pedidos y actualizaciones.</p>
  </section>

  <section class="account-grid-2">
    <article class="account-card">
      <div class="summary-card">
        <div class="summary-icon"><i class="fas fa-envelope" /></div>
        <div class="summary-content">
          <h3>Total</h3>
          <p>{{ notifications.length }}</p>
        </div>
      </div>
    </article>

    <article class="account-card">
      <div class="summary-card">
        <div class="summary-icon"><i class="fas fa-envelope-open" /></div>
        <div class="summary-content">
          <h3>Sin leer</h3>
          <p>{{ unreadCount }}</p>
        </div>
      </div>
    </article>

    <article class="account-card">
      <div class="summary-card">
        <div class="summary-icon"><i class="fas fa-check-double" /></div>
        <div class="summary-content">
          <h3>Leídas</h3>
          <p>{{ readCount }}</p>
        </div>
      </div>
    </article>
  </section>

  <section class="account-card">
    <div class="notifications-toolbar">
      <div class="filters">
        <select v-model="statusFilter" class="filter-select">
          <option value="all">Todas</option>
          <option value="unread">No leídas</option>
          <option value="read">Leídas</option>
        </select>

        <select v-model="typeFilter" class="filter-select">
          <option value="all">Todos los tipos</option>
          <option value="order">Órdenes</option>
          <option value="product">Productos</option>
          <option value="promotion">Promociones</option>
          <option value="system">Sistema</option>
          <option value="account">Cuenta</option>
        </select>
      </div>

      <button type="button" class="btn-primary-small" @click="markAllAsRead" :disabled="unreadCount === 0 || loadingAction">
        Marcar todas como leídas
      </button>
    </div>

    <p v-if="loading" class="loading-box">Cargando notificaciones...</p>
    <p v-else-if="errorMessage" class="error-box">{{ errorMessage }}</p>

    <div v-else-if="filteredNotifications.length === 0" class="empty-state">
      <i class="fas fa-bell-slash" />
      <p>No tienes notificaciones para este filtro.</p>
    </div>

    <div v-else class="notifications-list">
      <article
        v-for="notification in filteredNotifications"
        :key="notification.id"
        class="notification-item"
        :class="{ unread: !notification.is_read, read: !!notification.is_read }"
      >
        <div class="notification-icon" :class="notificationType(notification)">
          <i :class="notificationIcon(notification)" />
        </div>

        <div class="notification-content" @click="handleOpenNotification(notification)">
          <div class="notification-header">
            <h3>
              {{ notification.title }}
              <span class="notification-type">{{ notificationTypeLabel(notification) }}</span>
            </h3>
            <span class="notification-time">
              <i class="far fa-clock" />
              {{ formatTimeAgo(notification.created_at) }}
            </span>
          </div>
          <p class="notification-message">{{ notification.message }}</p>
        </div>

        <div class="notification-actions">
          <button
            v-if="!notification.is_read"
            type="button"
            class="btn-outline-small"
            @click.stop="markAsRead(notification.id)"
          >
            Marcar leída
          </button>
          <button type="button" class="btn-outline-small" @click.stop="deleteOne(notification.id)">
            Eliminar
          </button>
        </div>
      </article>
    </div>
  </section>
</template>

<script setup>
import { computed, inject, onMounted, ref } from 'vue'
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
  loading.value = true
  errorMessage.value = ''

  try {
    if (!isLoggedIn.value) {
      setAccountUnreadNotifications(0)
      return
    }

    const userId = String(user.value?.id || '').trim()
    const userEmail = String(user.value?.email || '').trim()

    const response = await getNotifications(userId, userEmail)
    notifications.value = Array.isArray(response?.data) ? response.data : []
    setAccountUnreadNotifications(unreadCount.value)
  } catch {
    errorMessage.value = 'No se pudieron cargar las notificaciones.'
  } finally {
    loading.value = false
  }
})

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

  const createdAt = new Date(value)
  if (Number.isNaN(createdAt.getTime())) return 'Ahora'

  const seconds = Math.max(0, Math.floor((Date.now() - createdAt.getTime()) / 1000))

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
</script>

<style scoped>
.notifications-header-panel h1 {
  display: inline-flex;
  align-items: center;
  gap: 0.8rem;
}

.notifications-toolbar {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 1rem;
  flex-wrap: wrap;
  margin-bottom: 1.6rem;
}

.filters {
  display: flex;
  gap: 0.8rem;
  flex-wrap: wrap;
}

.filter-select {
  padding: 0.8rem 1.2rem;
  border: 1px solid #d1d5db;
  border-radius: 8px;
  font-size: 1.6rem;
  background: #fff;
}

.notifications-list {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.notification-item {
  display: flex;
  gap: 1rem;
  align-items: flex-start;
  padding: 1.4rem;
  border: 1px solid #e5e7eb;
  border-radius: 12px;
  background: #fff;
}

.notification-item.unread {
  border-left: 4px solid #0077b6;
  background: #f8fbff;
}

.notification-icon {
  width: 52px;
  height: 52px;
  border-radius: 50%;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  font-size: 2rem;
}

.notification-icon.order {
  background: rgba(0, 119, 182, 0.12);
  color: #0077b6;
}

.notification-icon.product {
  background: rgba(72, 202, 228, 0.15);
  color: #0284c7;
}

.notification-icon.promotion {
  background: rgba(236, 72, 153, 0.12);
  color: #db2777;
}

.notification-icon.account {
  background: rgba(147, 51, 234, 0.12);
  color: #9333ea;
}

.notification-icon.system {
  background: rgba(100, 116, 139, 0.12);
  color: #475569;
}

.notification-content {
  flex: 1;
  cursor: pointer;
}

.notification-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  gap: 1rem;
}

.notification-header h3 {
  font-size: 2.6rem;
  margin-bottom: 0.5rem;
}

.notification-type {
  margin-left: 0.6rem;
  padding: 0.2rem 0.7rem;
  background: #e2e8f0;
  border-radius: 999px;
  font-size: 1.4rem;
  color: #475569;
}

.notification-time {
  color: #64748b;
  font-size: 1.5rem;
  white-space: nowrap;
}

.notification-message {
  font-size: 2.2rem;
  color: #475569;
}

.notification-actions {
  display: flex;
  flex-direction: column;
  gap: 0.6rem;
}

@media (max-width: 980px) {
  .notification-item {
    flex-direction: column;
  }

  .notification-actions {
    width: 100%;
    flex-direction: row;
  }
}
</style>
