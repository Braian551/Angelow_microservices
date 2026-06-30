import { orderHttp } from './http'

// Lista facturas administrativas desde order-service con filtros opcionales.
export async function getAdminInvoices(params = {}) {
  const { data } = await orderHttp.get('/admin/invoices', { params })
  return data
}

// Reenvía una factura de pedido al contacto definido por la operación.
export async function resendAdminInvoice(orderId, payload = {}) {
  const { data } = await orderHttp.post(`/admin/invoices/${orderId}/resend`, payload)
  return data
}

// Descarga la factura administrativa como blob para que la UI gestione el archivo.
export async function downloadAdminInvoice(orderId, params = {}) {
  return orderHttp.get(`/admin/invoices/${orderId}/download`, {
    params,
    responseType: 'blob',
  })
}
