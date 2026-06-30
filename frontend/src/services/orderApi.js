import { orderHttp } from './http'

// Lista pedidos según los filtros recibidos por páginas de cuenta o admin.
export async function getOrders(params = {}) {
  const { data } = await orderHttp.get('/orders', { params })
  return data
}

// Consulta el detalle de un pedido específico.
export async function getOrderById(orderId) {
  const { data } = await orderHttp.get(`/orders/${orderId}`)
  return data
}

// Descarga la factura como blob para que la UI controle nombre y apertura del archivo.
export async function downloadOrderInvoice(orderId, params = {}) {
  return orderHttp.get(`/orders/${orderId}/invoice/download`, {
    params,
    responseType: 'blob',
  })
}

// Crea un pedido desde el resumen final del checkout.
export async function createOrder(payload) {
  const { data } = await orderHttp.post('/orders', payload)
  return data
}

// Actualiza estados operativos del pedido desde flujos administrativos.
export async function updateOrderStatus(orderId, payload) {
  const { data } = await orderHttp.patch(`/orders/${orderId}/status`, payload)
  return data
}

// Cancela un pedido con el motivo enviado por la UI.
export async function cancelOrder(orderId, payload) {
  const { data } = await orderHttp.patch(`/orders/${orderId}/cancel`, payload)
  return data
}

// Envía una solicitud de devolución con adjuntos usando multipart/form-data.
export async function requestOrderRefund(orderId, payload) {
  const { data } = await orderHttp.post(`/orders/${orderId}/refund-requests`, payload, {
    headers: {
      'Content-Type': 'multipart/form-data',
    },
  })
  return data
}
