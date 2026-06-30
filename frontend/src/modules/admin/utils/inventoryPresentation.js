const DEFAULT_LOW_STOCK_THRESHOLD = 5

// Normaliza identificadores positivos usados en rutas de inventario.
function normalizePositiveInteger(value) {
  const parsed = Number(value)
  return Number.isFinite(parsed) && parsed > 0 ? parsed : null
}

// Resuelve el umbral de bajo stock aceptando objetos, números directos o fallback seguro.
export function resolveInventoryThreshold(source, fallback = DEFAULT_LOW_STOCK_THRESHOLD) {
  const normalizedFallback = Math.max(1, Number(fallback) || DEFAULT_LOW_STOCK_THRESHOLD)

  if (source && typeof source === 'object') {
    return resolveInventoryThreshold(
      source.low_stock_threshold
      ?? source.lowStockThreshold
      ?? source.threshold,
      normalizedFallback,
    )
  }

  const parsed = Number(source)
  return Number.isFinite(parsed) && parsed > 0 ? parsed : normalizedFallback
}

// Traduce el stock numérico a un estado visual de inventario.
export function normalizeInventoryStatus(stock, source = null) {
  const numericStock = Math.max(0, Number(stock || 0))
  const lowStockThreshold = resolveInventoryThreshold(source)

  if (numericStock <= 0) {
    return 'out'
  }

  if (numericStock <= lowStockThreshold) {
    return 'low'
  }

  return 'active'
}

// Construye la etiqueta de variante con color, talla y SKU cuando existe.
export function buildInventoryVariantLabel(row = {}) {
  const color = String(row.color_name || row.color || '').trim() || 'Sin color'
  const size = String(row.size_label || row.size_name || row.size || '').trim() || 'Sin talla'
  const sku = String(row.sku || row.variant_sku || '').trim()
  const baseLabel = `${color} / ${size}`

  return sku ? `${baseLabel} | SKU ${sku}` : baseLabel
}

// Genera la ruta admin que abre inventario sobre una variante y acción concreta.
export function buildInventoryTargetRoute(row = {}, action = 'adjust') {
  const productId = normalizePositiveInteger(row.product_id ?? row.productId)
  const variantId = normalizePositiveInteger(
    row.id
    ?? row.variant_id
    ?? row.variantId
    ?? row.size_variant_id
    ?? row.sizeVariantId,
  )

  if (!productId || !variantId) {
    return '/admin/inventario'
  }

  const normalizedAction = String(action || '').trim().toLowerCase() === 'transfer'
    ? 'transfer'
    : 'adjust'

  return {
    path: '/admin/inventario',
    query: {
      productId: String(productId),
      variantId: String(variantId),
      action: normalizedAction,
    },
  }
}