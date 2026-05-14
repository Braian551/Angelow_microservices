import { catalogHttp } from '../../../services/http'

export const CUSTOM_STORE_LINK_VALUE = '__custom__'

function normalizeBoolean(value, fallback = true) {
  if (typeof value === 'boolean') return value
  if (value === null || value === undefined || value === '') return fallback
  return Boolean(Number(value))
}

function normalizeCategory(item) {
  return {
    id: Number(item?.id || 0),
    name: item?.name || item?.nombre || 'Sin nombre',
    is_active: normalizeBoolean(item?.is_active ?? item?.activo, true),
  }
}

function normalizeCollection(item) {
  return {
    id: Number(item?.id || 0),
    name: item?.name || item?.nombre || 'Sin nombre',
    is_active: normalizeBoolean(item?.is_active ?? item?.activo, true),
  }
}

function sortByName(rows) {
  return [...rows].sort((left, right) => String(left.name || '').localeCompare(String(right.name || ''), 'es'))
}

function normalizeResponseRows(response) {
  const payload = response?.data?.data || response?.data || []
  if (Array.isArray(payload)) return payload
  if (Array.isArray(payload?.data)) return payload.data
  return []
}

async function fetchCatalogRows(adminPath, fallbackPath) {
  try {
    const response = await catalogHttp.get(adminPath)
    return normalizeResponseRows(response)
  } catch {
    try {
      const response = await catalogHttp.get(fallbackPath)
      return normalizeResponseRows(response)
    } catch {
      return []
    }
  }
}

export async function loadStoreLinkCatalogs() {
  const [categoryRows, collectionRows] = await Promise.all([
    fetchCatalogRows('/admin/categories', '/categories'),
    fetchCatalogRows('/admin/collections', '/collections'),
  ])

  return {
    categories: categoryRows.map(normalizeCategory).filter((item) => item.id > 0),
    collections: collectionRows.map(normalizeCollection).filter((item) => item.id > 0),
  }
}

export function buildStoreLinkGroups({ categories = [], collections = [], includeEmptyOption = false } = {}) {
  const groups = []

  if (includeEmptyOption) {
    groups.push({
      label: 'Acción opcional',
      options: [
        { label: '— Sin destino (botón decorativo) —', value: '' },
      ],
    })
  }

  groups.push(
    {
      label: 'Destinos frecuentes',
      options: [
        { label: 'Inicio', value: '/' },
        { label: 'Tienda — Todos los productos', value: '/tienda' },
        { label: 'Ofertas y descuentos', value: '/tienda?offers=1' },
        { label: 'Colecciones', value: '/colecciones' },
        { label: 'Mi carrito', value: '/carrito' },
        { label: 'Mi cuenta', value: '/mi-cuenta/resumen' },
      ],
    },
    {
      label: 'Tienda por público',
      options: [
        { label: 'Niñas', value: '/tienda?gender=nina' },
        { label: 'Niños', value: '/tienda?gender=nino' },
        { label: 'Bebés', value: '/tienda?gender=bebe' },
      ],
    },
  )

  const activeCategories = sortByName(categories.filter((item) => item.is_active !== false))
  if (activeCategories.length > 0) {
    groups.push({
      label: 'Categorías de la tienda',
      options: activeCategories.map((item) => ({
        label: item.name,
        value: `/tienda?category=${item.id}`,
      })),
    })
  }

  const activeCollections = sortByName(collections.filter((item) => item.is_active !== false))
  if (activeCollections.length > 0) {
    groups.push({
      label: 'Colecciones de la tienda',
      options: activeCollections.map((item) => ({
        label: item.name,
        value: `/tienda?collection=${item.id}`,
      })),
    })
  }

  groups.push({
    label: 'Destino personalizado',
    options: [
      { label: 'Otro enlace personalizado...', value: CUSTOM_STORE_LINK_VALUE },
    ],
  })

  return groups
}

export function detectStoreLinkOption(link, groups, fallbackValue = '') {
  const clean = String(link || '').trim()
  if (!clean) return fallbackValue

  const options = groups.flatMap((group) => group.options || [])
  const preset = options.find((option) => option.value !== CUSTOM_STORE_LINK_VALUE && option.value === clean)
  return preset ? preset.value : CUSTOM_STORE_LINK_VALUE
}