import { catalogHttp } from './http'

// Recupera favoritos usando id y correo para mantener compatibilidad de identidad.
export async function getWishlist(userId, userEmail = '') {
  const { data } = await catalogHttp.get('/wishlist', {
    params: {
      user_id: userId || undefined,
      user_email: userEmail || undefined,
    },
  })
  return data
}

// Agrega o quita un producto de favoritos según el estado devuelto por catálogo.
export async function toggleWishlist(payload) {
  const { data } = await catalogHttp.post('/wishlist/toggle', payload)
  return data
}
