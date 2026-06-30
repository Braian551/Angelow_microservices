import { notificationHttp } from './http'

// Lista notificaciones del usuario usando id y correo como llaves de identidad.
export async function getNotifications(userId, userEmail = '') {
  const { data } = await notificationHttp.get('/notifications', {
    params: {
      user_id: userId || undefined,
      user_email: userEmail || undefined,
    },
  })
  return data
}

// Crea una notificación desde flujos internos que necesitan informar al cliente.
export async function createNotification(payload) {
  const { data } = await notificationHttp.post('/notifications', payload)
  return data
}

// Marca una notificación puntual como leída.
export async function markNotificationRead(id) {
  const { data } = await notificationHttp.patch(`/notifications/${id}/read`)
  return data
}

// Marca todas las notificaciones del usuario como leídas en una sola acción.
export async function markAllNotificationsRead(userId, userEmail = '') {
  const { data } = await notificationHttp.patch('/notifications/read-all', {
    user_id: userId || undefined,
    user_email: userEmail || undefined,
  })
  return data
}

// Elimina una notificación validando que pertenezca al usuario actual.
export async function deleteNotification(notificationId, userId, userEmail = '') {
  const { data } = await notificationHttp.delete(`/notifications/${notificationId}`, {
    params: {
      user_id: userId || undefined,
      user_email: userEmail || undefined,
    },
  })
  return data
}

// Recupera preferencias de canales y tipos de notificación del usuario.
export async function getNotificationPreferences(userId, userEmail = '') {
  const { data } = await notificationHttp.get('/notification-preferences', {
    params: {
      user_id: userId || undefined,
      user_email: userEmail || undefined,
    },
  })
  return data
}

// Guarda preferencias y adjunta identidad para mantener consistencia entre servicios.
export async function updateNotificationPreferences(payload, userId, userEmail = '') {
  const { data } = await notificationHttp.put('/notification-preferences', {
    ...payload,
    user_id: userId || undefined,
    user_email: userEmail || undefined,
  })
  return data
}
