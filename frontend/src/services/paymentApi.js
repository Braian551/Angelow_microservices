import { paymentHttp } from './http'

// Lista bancos disponibles para el formulario de comprobante.
export async function getBanks() {
  const { data } = await paymentHttp.get('/banks')
  return data
}

// Obtiene la cuenta de pago visible para el checkout.
export async function getPaymentAccount() {
  const { data } = await paymentHttp.get('/payment-account')
  return data
}

// Lista pagos con filtros para vistas de administración o seguimiento.
export async function getPayments(params = {}) {
  const { data } = await paymentHttp.get('/payments', { params })
  return data
}

// Registra un pago o comprobante enviado por el cliente.
export async function createPayment(payload) {
  const { data } = await paymentHttp.post('/payments', payload)
  return data
}

// Verifica un pago desde administración con el resultado de revisión.
export async function verifyPayment(paymentId, payload) {
  const { data } = await paymentHttp.patch(`/payments/${paymentId}/verify`, payload)
  return data
}

// Obtiene configuración administrativa de la cuenta receptora de pagos.
export async function getAdminPaymentAccountConfig() {
  const { data } = await paymentHttp.get('/admin/payment-account')
  return data
}

// Guarda la configuración administrativa de cuenta de pago.
export async function saveAdminPaymentAccountConfig(payload) {
  const { data } = await paymentHttp.put('/admin/payment-account', payload)
  return data
}
