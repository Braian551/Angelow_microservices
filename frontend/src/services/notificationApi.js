import { notificationHttp } from './http'

export async function getNotifications(userId) {
  const { data } = await notificationHttp.get('/notifications', { params: { user_id: userId } })
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
