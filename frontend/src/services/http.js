import axios from 'axios'
import { normalizeUtf8Data } from '../utils/text'

const baseConfig = {
  timeout: 15000,
  headers: {
    Accept: 'application/json',
    'Content-Type': 'application/json',
  },
}

function isBinaryResponseData(data) {
  if (!data) return false
  if (typeof Blob !== 'undefined' && data instanceof Blob) return true
  if (typeof ArrayBuffer !== 'undefined' && data instanceof ArrayBuffer) return true
  return false
}

function createClient(baseURL) {
  const client = axios.create({
    ...baseConfig,
    baseURL,
  })

  client.interceptors.request.use((config) => {
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
      return Promise.reject(error)
    },
  )

  return client
}

export const authHttp = createClient(import.meta.env.VITE_AUTH_API_URL || 'http://localhost:8001/api')
export const catalogHttp = createClient(import.meta.env.VITE_CATALOG_API_URL || 'http://localhost:8002/api')
export const cartHttp = createClient(import.meta.env.VITE_CART_API_URL || 'http://localhost:8003/api')
export const orderHttp = createClient(import.meta.env.VITE_ORDER_API_URL || 'http://localhost:8004/api')
export const paymentHttp = createClient(import.meta.env.VITE_PAYMENT_API_URL || 'http://localhost:8005/api')
export const discountHttp = createClient(import.meta.env.VITE_DISCOUNT_API_URL || 'http://localhost:8006/api')
export const shippingHttp = createClient(import.meta.env.VITE_SHIPPING_API_URL || 'http://localhost:8007/api')
export const notificationHttp = createClient(import.meta.env.VITE_NOTIFICATION_API_URL || 'http://localhost:8008/api')
