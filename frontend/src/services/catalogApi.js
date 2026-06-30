import { catalogHttp } from './http'

// Carga datos agregados de portada: sliders, barra superior y banner.
export async function getHomeData() {
  const { data } = await catalogHttp.get('/home')
  return data
}

// Lista productos públicos con filtros y datos de favorito cuando aplica identidad.
export async function getProducts(params = {}) {
  const { data } = await catalogHttp.get('/products', { params })
  return data
}

// Sugerencias de búsqueda: producto con imagen y términos relevantes.
export async function getSearchSuggestions(term, userId) {
  const params = { term }
  if (userId) params.user_id = userId
  const { data } = await catalogHttp.get('/search/suggestions', { params })
  return data
}

// Recupera historial de búsqueda del usuario; sin sesión devuelve lista vacía controlada.
export async function getSearchHistory(userId) {
  if (!userId) {
    return { success: true, data: { terms: [] } }
  }

  const { data } = await catalogHttp.get('/search/history', {
    params: { user_id: userId },
  })
  return data
}

// Persiste un término buscado solo cuando existe usuario y texto válido.
export async function saveSearchHistory(term, userId) {
  if (!userId || !term) {
    return { success: true }
  }

  const { data } = await catalogHttp.post('/search/history', {
    term,
    user_id: userId,
  })
  return data
}

// Obtiene el detalle público de producto por slug.
export async function getProductBySlug(slug, params = {}) {
  const { data } = await catalogHttp.get(`/products/${slug}`, { params })
  return data
}

// Consulta una variante puntual para refrescar stock en tiempo real.
export async function getProductVariantById(variantId) {
  const { data } = await catalogHttp.get(`/internal/variants/${variantId}`)
  return data
}

// Lista categorías públicas de catálogo.
export async function getCategories() {
  const { data } = await catalogHttp.get('/categories')
  return data
}

// Lista colecciones públicas de catálogo.
export async function getCollections() {
  const { data } = await catalogHttp.get('/collections')
  return data
}
