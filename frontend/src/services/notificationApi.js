import { notificationHttp } from './http'

export async function getNotifications(userId, userEmail = '') {
  const { data } = await notificationHttp.get('/notifications', {
    params: {
      user_id: userId || undefined,
      user_email: userEmail || undefined,
    },
  })
  return data
}

export async function createNotification(payload) {
  const { data } = await notificationHttp.post('/notifications', payload)
  return data
}

export async function markNotificationRead(id) {
  const { data } = await notificationHttp.patch(`/notifications/${id}/read`)
  return data
}

export async function markAllNotificationsRead(userId, userEmail = '') {
  const { data } = await notificationHttp.patch('/notifications/read-all', {
    user_id: userId || undefined,
    user_email: userEmail || undefined,
  })
  return data
}

export async function deleteNotification(notificationId, userId, userEmail = '') {
  const { data } = await notificationHttp.delete(`/notifications/${notificationId}`, {
    params: {
      user_id: userId || undefined,
      user_email: userEmail || undefined,
    },
  })
  return data
}

export async function getNotificationPreferences(userId, userEmail = '') {
  const { data } = await notificationHttp.get('/notification-preferences', {
    params: {
      user_id: userId || undefined,
      user_email: userEmail || undefined,
    },
  })
  return data
}

export async function updateNotificationPreferences(payload, userId, userEmail = '') {
  const { data } = await notificationHttp.put('/notification-preferences', {
    ...payload,
    user_id: userId || undefined,
    user_email: userEmail || undefined,
  })
  return data
}
