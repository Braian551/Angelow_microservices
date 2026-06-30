import { cartHttp } from './http'

// Consulta el carrito con los parámetros de identidad o contexto que envía la vista.
export async function getCart(params) {
  const { data } = await cartHttp.get('/cart', { params })
  return data
}

// Agrega un producto o variante al carrito del usuario.
export async function addToCart(payload) {
  const { data } = await cartHttp.post('/cart/add', payload)
  return data
}

// Actualiza cantidad de una línea del carrito manteniendo el endpoint dueño del dominio.
export async function updateCartItem(itemId, quantity) {
  const { data } = await cartHttp.put(`/cart/${itemId}`, { quantity })
  return data
}

// Elimina una línea específica del carrito.
export async function removeCartItem(itemId) {
  const { data } = await cartHttp.delete(`/cart/${itemId}`)
  return data
}
