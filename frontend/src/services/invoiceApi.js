import { orderHttp } from './http'

export async function getAdminInvoices(params = {}) {
  const { data } = await orderHttp.get('/admin/invoices', { params })
  return data
}

export async function resendAdminInvoice(orderId, payload = {}) {
  const { data } = await orderHttp.post(`/admin/invoices/${orderId}/resend`, payload)
  return data
}

export async function downloadAdminInvoice(orderId, params = {}) {
  return orderHttp.get(`/admin/invoices/${orderId}/download`, {
    params,
    responseType: 'blob',
  })
}
