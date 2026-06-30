// Obtiene el origen del payment-service para resolver archivos fuera del host Vite.
function paymentServiceBaseUrl() {
  const configuredUrl = String(import.meta.env.VITE_PAYMENT_API_URL || 'http://localhost:8005/api').trim()
  if (!configuredUrl) return ''

  return configuredUrl.replace(/\/api\/?$/i, '').replace(/\/+$/, '')
}

// Une base y ruta evitando dobles slash o rutas vacías inconsistentes.
function joinUrl(baseUrl, path) {
  const cleanBaseUrl = String(baseUrl || '').replace(/\/+$/, '')
  const cleanPath = String(path || '').replace(/^\/+/, '')

  return cleanBaseUrl ? `${cleanBaseUrl}/${cleanPath}` : `/${cleanPath}`
}

// Resuelve comprobantes de pago desde rutas completas, /uploads o nombres sueltos.
export function resolvePaymentProofUrl(value) {
  const proofPath = String(value || '').trim().replace(/\\/g, '/')
  if (!proofPath) return ''

  if (/^(https?:|blob:|data:)/i.test(proofPath)) {
    return proofPath
  }

  // Reutiliza el origen del payment-service para que /uploads no apunte al servidor Vite.
  const baseUrl = paymentServiceBaseUrl()

  if (proofPath.startsWith('/uploads/') || proofPath.startsWith('uploads/')) {
    return joinUrl(baseUrl, proofPath)
  }

  if (proofPath.startsWith('/payment_proofs/') || proofPath.startsWith('payment_proofs/')) {
    return joinUrl(baseUrl, `uploads/${proofPath.replace(/^\/+/, '')}`)
  }

  return joinUrl(baseUrl, `uploads/payment_proofs/${proofPath.replace(/^\/+/, '')}`)
}
