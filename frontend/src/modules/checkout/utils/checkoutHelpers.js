// Formatea montos del checkout en pesos colombianos sin decimales visibles.
export function formatCheckoutPrice(value) {
  return new Intl.NumberFormat('es-CO', {
    style: 'currency',
    currency: 'COP',
    maximumFractionDigits: 0,
  }).format(Number(value || 0))
}

// Normaliza un método de envío para que la UI no dependa de nombres crudos de la API.
export function normalizeCheckoutMethod(item = {}) {
  const freeShippingMinimum = item?.free_shipping_minimum
  const hasFreeShippingMinimum = freeShippingMinimum !== null && freeShippingMinimum !== undefined && freeShippingMinimum !== ''
  const appliedCost = item?.applied_cost
  const methodCost = item?.method_cost
  const rangeRuleAdditionalCost = item?.range_rule_additional_cost

  const hasRangeRuleMinPrice = item?.range_rule_min_price !== null
    && item?.range_rule_min_price !== undefined
    && item?.range_rule_min_price !== ''
  const hasRangeRuleMaxPrice = item?.range_rule_max_price !== null
    && item?.range_rule_max_price !== undefined
    && item?.range_rule_max_price !== ''

  return {
    id: Number(item?.id || 0),
    name: normalizeText(item?.name || 'Envío'),
    description: normalizeText(item?.description || ''),
    delivery_time: normalizeText(item?.delivery_time || ''),
    base_cost: Number(item?.base_cost || 0),
    free_shipping_minimum: hasFreeShippingMinimum ? Number(freeShippingMinimum) : null,
    method_cost: methodCost !== null && methodCost !== undefined && methodCost !== '' ? Number(methodCost) : null,
    range_rule_additional_cost: rangeRuleAdditionalCost !== null && rangeRuleAdditionalCost !== undefined && rangeRuleAdditionalCost !== ''
      ? Number(rangeRuleAdditionalCost)
      : null,
    range_rule_applied: Boolean(item?.range_rule_applied ?? item?.rule_id),
    range_rule_label: normalizeText(item?.range_rule_label || ''),
    range_rule_min_price: hasRangeRuleMinPrice ? Number(item.range_rule_min_price) : null,
    range_rule_max_price: hasRangeRuleMaxPrice ? Number(item.range_rule_max_price) : null,
    rule_id: item?.rule_id ? Number(item.rule_id) : null,
    rule_shipping_cost: item?.rule_shipping_cost !== null && item?.rule_shipping_cost !== undefined && item?.rule_shipping_cost !== ''
      ? Number(item.rule_shipping_cost)
      : null,
    applied_cost: appliedCost !== null && appliedCost !== undefined && appliedCost !== '' ? Number(appliedCost) : null,
    pricing_source: normalizeText(item?.pricing_source || ''),
  }
}

// Normaliza una dirección del cliente con valores seguros para renderizar el selector de envío.
export function normalizeCheckoutAddress(item = {}) {
  const addressType = normalizeText(item?.address_type || 'casa').toLowerCase()

  return {
    id: Number(item?.id || 0),
    alias: normalizeText(item?.alias || item?.address_type || 'Dirección'),
    address_type: addressType || 'casa',
    recipient_name: normalizeText(item?.recipient_name || 'Sin destinatario'),
    recipient_phone: normalizeText(item?.recipient_phone || item?.phone || 'Sin teléfono'),
    address: normalizeText(item?.address || item?.address_line_1 || 'Sin dirección'),
    complement: normalizeText(item?.complement || item?.address_line_2 || ''),
    neighborhood: normalizeText(item?.neighborhood || item?.city || ''),
    city: normalizeText(item?.city || ''),
    building_type: normalizeText(item?.building_type || addressType || 'casa').toLowerCase(),
    building_name: normalizeText(item?.building_name || ''),
    apartment_number: normalizeText(item?.apartment_number || ''),
    delivery_instructions: normalizeText(item?.delivery_instructions || item?.notes || ''),
    is_default: Boolean(item?.is_default),
  }
}

// Normaliza cada línea del carrito antes de enviarla al flujo de pago y pedido.
export function normalizeCheckoutCartItem(item = {}) {
  const colorVariantId = normalizeOptionalNumericId(
    item?.color_variant_id
    ?? item?.colorVariantId
    ?? item?.color_variant?.id
    ?? item?.color?.id,
  )

  const sizeVariantId = normalizeOptionalNumericId(
    item?.size_variant_id
    ?? item?.sizeVariantId
    ?? item?.variant_id
    ?? item?.variantId
    ?? item?.size_variant?.id
    ?? item?.variant?.id,
  )

  return {
    item_id: Number(item?.item_id || 0),
    product_id: Number(item?.product_id || 0),
    product_name: normalizeText(item?.product_name || 'Producto'),
    product_slug: normalizeText(item?.product_slug || ''),
    product_image: normalizeText(item?.product_image || item?.variant_image || ''),
    color_variant_id: colorVariantId,
    size_variant_id: sizeVariantId,
    color_name: normalizeText(item?.color_name || ''),
    size_name: normalizeText(item?.size_name || ''),
    quantity: Math.max(1, Number(item?.quantity || 1)),
    price: Number(item?.price || 0),
    total: Number(item?.line_total ?? item?.item_total ?? item?.total ?? 0),
  }
}

// Traduce el tipo técnico de dirección a la etiqueta visible del checkout.
export function labelCheckoutAddressType(type) {
  const value = normalizeText(type).toLowerCase()
  if (value === 'apartamento') return 'Apartamento'
  if (value === 'oficina') return 'Oficina'
  if (value === 'otro') return 'Otro'
  return 'Casa'
}

// Asocia cada tipo de dirección con el ícono usado por las tarjetas del checkout.
export function iconCheckoutAddressType(type) {
  const value = normalizeText(type).toLowerCase()
  if (value === 'apartamento') return 'fas fa-building'
  if (value === 'oficina') return 'fas fa-briefcase'
  if (value === 'otro') return 'fas fa-map-marker-alt'
  return 'fas fa-home'
}

// Compone el nombre del tipo de inmueble, agregando el nombre del edificio cuando existe.
export function labelCheckoutBuilding(address = {}) {
  const buildingType = labelCheckoutAddressType(address?.building_type || address?.address_type)
  const buildingName = normalizeText(address?.building_name || '')

  if (buildingName) {
    return `${buildingType} (${buildingName})`
  }

  return buildingType
}

// Une dirección y complemento evitando comas vacías cuando falta alguno de los campos.
export function buildCheckoutAddressLine(address = {}) {
  return [normalizeText(address?.address || ''), normalizeText(address?.complement || '')]
    .filter(Boolean)
    .join(', ')
}

// Construye la línea secundaria de zona o apartamento para resumir la entrega.
export function buildCheckoutZoneLine(address = {}) {
  const zone = normalizeText(address?.neighborhood || address?.city || '')
  const apartment = normalizeText(address?.apartment_number || '')

  if (zone && apartment) {
    return `${zone} · ${apartment}`
  }

  return zone || apartment
}

// Resume color y talla de la variante seleccionada en una sola línea legible.
export function buildCheckoutVariantName(item = {}) {
  return [
    normalizeText(item?.color_name) ? `Color: ${normalizeText(item?.color_name)}` : '',
    normalizeText(item?.size_name) ? `Talla: ${normalizeText(item?.size_name)}` : '',
  ].filter(Boolean).join(' · ')
}

// Formatea fechas de confirmación para mostrar trazabilidad clara al cliente.
export function formatCheckoutDateTime(value) {
  if (!value) return ''

  const parsed = new Date(value)
  if (Number.isNaN(parsed.getTime())) {
    return ''
  }

  return new Intl.DateTimeFormat('es-CO', {
    dateStyle: 'medium',
    timeStyle: 'short',
  }).format(parsed)
}

// Convierte cualquier valor opcional en texto limpio para evitar espacios o nulos visibles.
function normalizeText(value) {
  return String(value || '').trim()
}

// Devuelve identificadores numéricos válidos o null cuando el dato no aplica.
function normalizeOptionalNumericId(value) {
  const parsed = Number(value)
  if (!Number.isFinite(parsed) || parsed <= 0) {
    return null
  }

  return parsed
}
