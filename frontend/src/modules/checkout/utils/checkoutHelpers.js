export function formatCheckoutPrice(value) {
  return new Intl.NumberFormat('es-CO', {
    style: 'currency',
    currency: 'COP',
    maximumFractionDigits: 0,
  }).format(Number(value || 0))
}

export function normalizeCheckoutMethod(item = {}) {
  return {
    id: Number(item?.id || 0),
    name: normalizeText(item?.name || 'Envío'),
    description: normalizeText(item?.description || ''),
    delivery_time: normalizeText(item?.delivery_time || ''),
    base_cost: Number(item?.base_cost || 0),
  }
}

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

export function normalizeCheckoutCartItem(item = {}) {
  return {
    item_id: Number(item?.item_id || 0),
    product_id: Number(item?.product_id || 0),
    product_name: normalizeText(item?.product_name || 'Producto'),
    product_slug: normalizeText(item?.product_slug || ''),
    product_image: normalizeText(item?.product_image || item?.variant_image || ''),
    color_variant_id: item?.color_variant_id ? Number(item.color_variant_id) : null,
    size_variant_id: item?.size_variant_id ? Number(item.size_variant_id) : null,
    color_name: normalizeText(item?.color_name || ''),
    size_name: normalizeText(item?.size_name || ''),
    quantity: Math.max(1, Number(item?.quantity || 1)),
    price: Number(item?.price || 0),
    total: Number(item?.line_total ?? item?.item_total ?? item?.total ?? 0),
  }
}

export function labelCheckoutAddressType(type) {
  const value = normalizeText(type).toLowerCase()
  if (value === 'apartamento') return 'Apartamento'
  if (value === 'oficina') return 'Oficina'
  if (value === 'otro') return 'Otro'
  return 'Casa'
}

export function iconCheckoutAddressType(type) {
  const value = normalizeText(type).toLowerCase()
  if (value === 'apartamento') return 'fas fa-building'
  if (value === 'oficina') return 'fas fa-briefcase'
  if (value === 'otro') return 'fas fa-map-marker-alt'
  return 'fas fa-home'
}

export function labelCheckoutBuilding(address = {}) {
  const buildingType = labelCheckoutAddressType(address?.building_type || address?.address_type)
  const buildingName = normalizeText(address?.building_name || '')

  if (buildingName) {
    return `${buildingType} (${buildingName})`
  }

  return buildingType
}

export function buildCheckoutAddressLine(address = {}) {
  return [normalizeText(address?.address || ''), normalizeText(address?.complement || '')]
    .filter(Boolean)
    .join(', ')
}

export function buildCheckoutZoneLine(address = {}) {
  const zone = normalizeText(address?.neighborhood || address?.city || '')
  const apartment = normalizeText(address?.apartment_number || '')

  if (zone && apartment) {
    return `${zone} · ${apartment}`
  }

  return zone || apartment
}

export function buildCheckoutVariantName(item = {}) {
  return [
    normalizeText(item?.color_name) ? `Color: ${normalizeText(item?.color_name)}` : '',
    normalizeText(item?.size_name) ? `Talla: ${normalizeText(item?.size_name)}` : '',
  ].filter(Boolean).join(' · ')
}

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

function normalizeText(value) {
  return String(value || '').trim()
}
