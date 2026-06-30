const CART_SELECTION_STORAGE_KEY = 'angelow_cart_selection'
const LOW_STOCK_THRESHOLD = 6

// Evalúa si una línea del carrito puede comprarse según stock disponible y cantidad solicitada.
export function resolveCartItemAvailability(item = {}, options = {}) {
  const lowStockThreshold = Math.max(1, Number(options?.lowStockThreshold || LOW_STOCK_THRESHOLD))
  const availableStock = Math.max(0, Math.floor(Number(item?.available_stock ?? item?.availableStock ?? 0)))
  const quantity = Math.max(1, Math.floor(Number(item?.quantity || 1)))
  const soldOut = availableStock <= 0
  const exceedsStock = !soldOut && quantity > availableStock
  const lowStock = !soldOut && availableStock <= lowStockThreshold

  return {
    availableStock,
    quantity,
    soldOut,
    exceedsStock,
    lowStock,
    isSelectable: !soldOut && !exceedsStock,
    canIncrease: !soldOut && quantity < availableStock,
    maxQuantity: soldOut ? quantity : Math.max(1, availableStock),
    shortfall: soldOut ? quantity : Math.max(0, quantity - availableStock),
    status: soldOut
      ? 'sold-out'
      : (exceedsStock ? 'quantity-exceeded' : (lowStock ? 'low-stock' : 'available')),
  }
}

// Lee la selección persistida del carrito y la normaliza para evitar valores corruptos en localStorage.
export function readStoredCartSelectionMap() {
  if (typeof window === 'undefined') {
    return {}
  }

  try {
    const rawValue = window.localStorage.getItem(CART_SELECTION_STORAGE_KEY)
    if (!rawValue) {
      return {}
    }

    return normalizeSelectionMap(JSON.parse(rawValue))
  } catch {
    return {}
  }
}

// Guarda la selección actual del carrito y elimina la clave cuando no hay productos seleccionables.
export function writeStoredCartSelectionMap(selectionMap = {}) {
  const normalizedSelectionMap = normalizeSelectionMap(selectionMap)

  if (typeof window === 'undefined') {
    return normalizedSelectionMap
  }

  if (Object.keys(normalizedSelectionMap).length === 0) {
    window.localStorage.removeItem(CART_SELECTION_STORAGE_KEY)
    return normalizedSelectionMap
  }

  window.localStorage.setItem(CART_SELECTION_STORAGE_KEY, JSON.stringify(normalizedSelectionMap))
  return normalizedSelectionMap
}

// Sincroniza la selección guardada con los ítems vigentes para conservar preferencias válidas.
export function synchronizeCartSelectionMap(items = [], baseSelectionMap = readStoredCartSelectionMap()) {
  const storedSelectionMap = normalizeSelectionMap(baseSelectionMap)
  const nextSelectionMap = {}

  for (const item of Array.isArray(items) ? items : []) {
    const itemId = normalizeCartItemId(item)
    if (!itemId) {
      continue
    }

    nextSelectionMap[itemId] = Object.prototype.hasOwnProperty.call(storedSelectionMap, itemId)
      ? Boolean(storedSelectionMap[itemId])
      : true
  }

  return writeStoredCartSelectionMap(nextSelectionMap)
}

// Cambia la selección de una sola línea sin perder el resto del mapa persistido.
export function setStoredCartItemSelection(itemId, selected, baseSelectionMap = readStoredCartSelectionMap()) {
  const normalizedItemId = normalizeCartItemId({ item_id: itemId })
  if (!normalizedItemId) {
    return normalizeSelectionMap(baseSelectionMap)
  }

  const nextSelectionMap = {
    ...normalizeSelectionMap(baseSelectionMap),
    [normalizedItemId]: Boolean(selected),
  }

  return writeStoredCartSelectionMap(nextSelectionMap)
}

// Aplica selección masiva únicamente sobre productos que todavía se pueden comprar.
export function setAllStoredCartSelections(items = [], selected, baseSelectionMap = readStoredCartSelectionMap()) {
  const nextSelectionMap = {
    ...normalizeSelectionMap(baseSelectionMap),
  }

  for (const item of Array.isArray(items) ? items : []) {
    const itemId = normalizeCartItemId(item)
    if (!itemId) {
      continue
    }

    const availability = resolveCartItemAvailability(item)
    if (!availability.isSelectable) {
      continue
    }

    nextSelectionMap[itemId] = Boolean(selected)
  }

  return writeStoredCartSelectionMap(nextSelectionMap)
}

// Construye el resumen que usa la vista del carrito para totales, conteos y estados bloqueados.
export function buildCartSelectionSummary(items = [], baseSelectionMap = readStoredCartSelectionMap()) {
  const selectionMap = normalizeSelectionMap(baseSelectionMap)
  const entries = []

  for (const item of Array.isArray(items) ? items : []) {
    const itemId = normalizeCartItemId(item)
    if (!itemId) {
      continue
    }

    const availability = resolveCartItemAvailability(item)
    const preferred = Object.prototype.hasOwnProperty.call(selectionMap, itemId)
      ? Boolean(selectionMap[itemId])
      : true
    const selected = preferred && availability.isSelectable
    const lineTotal = resolveCartLineTotal(item)

    entries.push({
      item,
      itemId,
      availability,
      preferred,
      selected,
      lineTotal,
    })
  }

  const selectedEntries = entries.filter((entry) => entry.selected)
  const selectableEntries = entries.filter((entry) => entry.availability.isSelectable)

  return {
    entries,
    selectedEntries,
    selectedItems: selectedEntries.map((entry) => entry.item),
    selectedSubtotal: selectedEntries.reduce((sum, entry) => sum + entry.lineTotal, 0),
    selectedUnits: selectedEntries.reduce((sum, entry) => sum + Math.max(1, Number(entry.item?.quantity || 1)), 0),
    selectedProductCount: selectedEntries.length,
    selectableProductCount: selectableEntries.length,
    blockedProductCount: entries.length - selectableEntries.length,
    allSelectableSelected: selectableEntries.length > 0 && selectableEntries.every((entry) => entry.selected),
    hasSelectableItems: selectableEntries.length > 0,
  }
}

// Resuelve el total de línea usando primero totales precalculados y luego precio por cantidad.
export function resolveCartLineTotal(item = {}) {
  const explicitTotal = Number(item?.line_total ?? item?.item_total ?? item?.total ?? 0)
  if (Number.isFinite(explicitTotal) && explicitTotal > 0) {
    return explicitTotal
  }

  return Number(item?.price || 0) * Math.max(1, Number(item?.quantity || 1))
}

// Limpia el mapa recibido para aceptar solo identificadores numéricos con valores booleanos.
function normalizeSelectionMap(value) {
  if (!value || typeof value !== 'object' || Array.isArray(value)) {
    return {}
  }

  return Object.entries(value).reduce((accumulator, [key, selected]) => {
    const itemId = Number(key)
    if (!Number.isFinite(itemId) || itemId <= 0 || typeof selected !== 'boolean') {
      return accumulator
    }

    accumulator[itemId] = selected
    return accumulator
  }, {})
}

// Normaliza el identificador del ítem para usarlo como clave estable de selección.
function normalizeCartItemId(item = {}) {
  const itemId = Number(item?.item_id || item?.itemId || 0)
  if (!Number.isFinite(itemId) || itemId <= 0) {
    return null
  }

  return itemId
}