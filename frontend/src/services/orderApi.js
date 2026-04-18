import { orderHttp } from './http'

export async function getOrders(params = {}) {
  const { data } = await orderHttp.get('/orders', { params })
  return data
}

export async function getOrderById(orderId) {
  const { data } = await orderHttp.get(`/orders/${orderId}`)
  return data
}

export async function createOrder(payload) {
  const { data } = await orderHttp.post('/orders', payload)
  return data
}

export async function updateOrderStatus(orderId, payload) {
  const { data } = await orderHttp.patch(`/orders/${orderId}/status`, payload)
  return data
}

export async function cancelOrder(orderId, payload) {
  const { data } = await orderHttp.patch(`/orders/${orderId}/cancel`, payload)
  return data
}
