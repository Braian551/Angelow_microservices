import axios from 'axios'
import { normalizeUtf8Data } from '../utils/text'

// Configuración base compartida por todos los clientes HTTP de microservicios.
const baseConfig = {
  timeout: 15000,
  headers: {
    Accept: 'application/json',
  },
}

const productionApiOrigin = 'https://angelow.online'

function uniqueUrls(urls) {
  return [...new Set(urls.map((url) => String(url || '').trim()).filter(Boolean))]
}

function apiUrls(service, localPort) {
  const configuredUrl = String(import.meta.env[`VITE_${service.toUpperCase()}_API_URL`] || '').trim()

  return uniqueUrls([
    configuredUrl,
    `${productionApiOrigin}/api/${service}-service`,
    `http://localhost:${localPort}/api`,
  ])
}

// Detecta respuestas binarias para no intentar normalizarlas como JSON.
function isBinaryResponseData(data) {
  if (!data) return false
  if (typeof Blob !== 'undefined' && data instanceof Blob) return true
  if (typeof ArrayBuffer !== 'undefined' && data instanceof ArrayBuffer) return true
  return false
}

// Informa al shell de errores del servidor o de una conexión agotada después de probar los fallbacks.
function notifyServerError(error) {
  if (typeof window === 'undefined' || error?.code === 'ERR_CANCELED') return

  const responseStatus = Number(error?.response?.status)
  const status = responseStatus >= 500 && responseStatus < 600 ? responseStatus : 503
  window.dispatchEvent(new CustomEvent('angelow:server-error', { detail: { status } }))
}

// Crea un cliente Axios con token, soporte FormData y normalización UTF-8 de respuestas.
function createClient(baseURLs) {
  const urls = uniqueUrls(baseURLs)
  const client = axios.create({
    ...baseConfig,
    baseURL: urls[0],
  })

  client.interceptors.request.use((config) => {
    const urlIndex = Number(config._angelowBaseUrlIndex || 0)
    config.baseURL = urls[Math.min(urlIndex, urls.length - 1)]

    // Cuando el payload es FormData, el navegador debe inyectar el boundary.
    if (typeof FormData !== 'undefined' && config.data instanceof FormData) {
      if (config.headers) {
        if (typeof config.headers.setContentType === 'function') {
          // Evita que Axios transforme el FormData en JSON (los File terminan como objetos).
          // El valor false omite el header y deja que el navegador añada el boundary multipart.
          config.headers.setContentType(false)
        } else if (typeof config.headers.delete === 'function') {
          config.headers.delete('Content-Type')
          config.headers.delete('content-type')
        } else {
          delete config.headers['Content-Type']
          delete config.headers['content-type']
        }
      }
    }

    const token = localStorage.getItem('angelow_token')
    if (token) {
      config.headers.Authorization = `Bearer ${token}`
    }
    return config
  })

  client.interceptors.response.use(
    (response) => {
      if (!isBinaryResponseData(response.data)) {
        response.data = normalizeUtf8Data(response.data)
      }
      return response
    },
    (error) => {
      if (error?.response?.data && !isBinaryResponseData(error.response.data)) {
        error.response.data = normalizeUtf8Data(error.response.data)
      }

      const currentUrlIndex = Number(error?.config?._angelowBaseUrlIndex || 0)
      const hasNextUrl = currentUrlIndex < urls.length - 1
      if (!error?.response && error?.config && hasNextUrl) {
        error.config._angelowBaseUrlIndex = currentUrlIndex + 1
        return client.request(error.config)
      }

      if (!error?.response || Number(error.response.status) >= 500) {
        notifyServerError(error)
      }

      const requestUrl = String(error?.config?.url || '')
      const isLoginRequest = /\/auth\/(login|google|register|registration-verification)/.test(requestUrl)
      if (error?.response?.status === 401 && !isLoginRequest && typeof window !== 'undefined') {
        window.dispatchEvent(new CustomEvent('angelow:auth-expired'))
      }

      return Promise.reject(error)
    },
  )

  return client
}

// Clientes por dominio: cada módulo consume únicamente el microservicio dueño de sus datos.
export const authHttp = createClient(apiUrls('auth', 8001))
export const catalogHttp = createClient(apiUrls('catalog', 8002))
export const cartHttp = createClient(apiUrls('cart', 8003))
export const orderHttp = createClient(apiUrls('order', 8004))
export const paymentHttp = createClient(apiUrls('payment', 8005))
export const discountHttp = createClient(apiUrls('discount', 8006))
export const shippingHttp = createClient(apiUrls('shipping', 8007))
export const notificationHttp = createClient(apiUrls('notification', 8008))
