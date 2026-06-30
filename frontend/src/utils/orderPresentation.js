// Etiquetas públicas para estados de pedido recibidos desde base de datos o API.
const ORDER_STATUS_LABELS = Object.freeze({
  created: 'Creada',
  pending: 'Pendiente',
  pending_payment: 'Pendiente de pago',
  in_review: 'En proceso',
  en_revision: 'En proceso',
  processing: 'En proceso',
  shipped: 'Enviado',
  delivered: 'Entregado',
  completed: 'Completado',
  cancelled: 'Cancelado',
  canceled: 'Cancelado',
  expired: 'Cancelado',
  refunded: 'Reembolsado',
})

// Etiquetas públicas para estados de pago, incluyendo equivalencias de migración.
const PAYMENT_STATUS_LABELS = Object.freeze({
  pending: 'Pendiente',
  pending_payment: 'Pendiente de pago',
  in_review: 'En revisión',
  en_revision: 'En revisión',
  pending_refund: 'Reembolso en proceso',
  refund_requested: 'Reembolso solicitado',
  paid: 'Pagado',
  verified: 'Verificado',
  approved: 'Aprobado',
  expired: 'Cancelado',
  failed: 'Fallido',
  refunded: 'Reembolsado',
  rejected: 'Rechazado',
  cancelled: 'Cancelado',
  canceled: 'Cancelado',
})

// Etiquetas visibles para métodos de pago técnicos.
const PAYMENT_METHOD_LABELS = Object.freeze({
  transfer: 'Transferencia',
  transferencia: 'Transferencia',
  bank_transfer: 'Transferencia bancaria',
  cash: 'Efectivo',
  card: 'Tarjeta',
  credit_card: 'Tarjeta crédito',
  debit_card: 'Tarjeta débito',
  nequi: 'Nequi',
  pse: 'PSE',
  consignacion: 'Consignación',
})

// Campos de historial traducidos para auditoría administrativa.
const HISTORY_FIELD_LABELS = Object.freeze({
  status: 'Estado',
  payment_status: 'Estado de pago',
  payment_method: 'Método de pago',
  order_status: 'Estado de la orden',
})

// Acciones masivas mostradas en mensajes y registros de admin.
const BULK_ACTION_LABELS = Object.freeze({
  change_status: 'cambio de estado',
  change_payment_status: 'cambio de estado de pago',
  deactivate: 'desactivación',
})

// Reemplazos de respaldo para textos compuestos que mezclan valores técnicos.
const GENERIC_REPLACEMENTS = [
  [/\bcreated\b/gi, 'Creada'],
  [/\bpending_payment\b/gi, 'Pendiente de pago'],
  [/\bin_review\b/gi, 'En proceso'],
  [/\ben_revision\b/gi, 'En proceso'],
  [/\bpending_refund\b/gi, 'Reembolso en proceso'],
  [/\brefund_requested\b/gi, 'Reembolso solicitado'],
  [/\bpending\b/gi, 'Pendiente'],
  [/\bprocessing\b/gi, 'En proceso'],
  [/\bshipped\b/gi, 'Enviado'],
  [/\bdelivered\b/gi, 'Entregado'],
  [/\bcompleted\b/gi, 'Completado'],
  [/\bcancelled\b/gi, 'Cancelado'],
  [/\bcanceled\b/gi, 'Cancelado'],
  [/\bexpired\b/gi, 'Cancelado'],
  [/\brefunded\b/gi, 'Reembolsado'],
  [/\bpaid\b/gi, 'Pagado'],
  [/\bverified\b/gi, 'Verificado'],
  [/\bapproved\b/gi, 'Aprobado'],
  [/\brejected\b/gi, 'Rechazado'],
  [/\bfailed\b/gi, 'Fallido'],
  [/\btransfer\b/gi, 'Transferencia'],
  [/\bcash\b/gi, 'Efectivo'],
  [/\bcard\b/gi, 'Tarjeta'],
]

// Normaliza tokens de BD para poder compararlos sin depender de espacios o guiones.
function normalizeToken(value) {
  return String(value ?? '')
    .trim()
    .toLowerCase()
    .replace(/\s+/g, '_')
    .replace(/-/g, '_')
}

// Agrupa estados equivalentes del pedido en las rutas de negocio actuales.
export function normalizeOrderStatus(status) {
  const normalized = normalizeToken(status)

  if (!normalized || ['created', 'pending_payment'].includes(normalized)) {
    return 'pending'
  }

  if (normalized === 'en_revision' || normalized === 'in_review') {
    return 'processing'
  }

  // Los pedidos con datos antiguos `expired` se tratan como cancelados para conservar una sola ruta final de negocio.
  if (['canceled', 'expired', 'refunded'].includes(normalized)) {
    return 'cancelled'
  }

  return normalized
}

export const normalizeAdminOrderStatus = normalizeOrderStatus

// Normaliza estados de pago sin colapsarlos para conservar semántica de cobro.
export function normalizePaymentStatus(status) {
  return normalizeToken(status)
}

// Estados disponibles para filtros administrativos de pedidos.
export const ADMIN_ORDER_FILTER_STATUSES = Object.freeze([
  { value: 'pending', label: 'Pendiente' },
  { value: 'processing', label: 'En proceso' },
  { value: 'shipped', label: 'Enviado' },
  { value: 'delivered', label: 'Entregado' },
  { value: 'completed', label: 'Completado' },
  { value: 'cancelled', label: 'Cancelado' },
])

// Estados que el administrador puede seleccionar al editar un pedido.
export const ADMIN_EDITABLE_ORDER_STATUSES = Object.freeze([
  { value: 'pending', label: 'Pendiente' },
  { value: 'processing', label: 'En proceso' },
  { value: 'shipped', label: 'Enviado' },
  { value: 'delivered', label: 'Entregado' },
  { value: 'completed', label: 'Completado' },
  { value: 'cancelled', label: 'Cancelado' },
])

// Busca una etiqueta normalizada dentro del mapa correspondiente.
function labelFromMap(value, map) {
  return map[normalizeToken(value)] || ''
}

// Convierte slugs o tokens desconocidos en texto legible como último recurso.
function humanizeToken(value) {
  const normalized = String(value ?? '')
    .trim()
    .replace(/[\s_-]+/g, ' ')

  if (!normalized) {
    return ''
  }

  return normalized.charAt(0).toUpperCase() + normalized.slice(1).toLowerCase()
}

// Traduce valores de BD a texto natural según el contexto de presentación.
export function translateDbText(value, context = 'generic') {
  if (value == null) return ''

  const raw = String(value).trim()
  if (!raw) return ''

  if (raw.includes('->')) {
    return raw
      .split('->')
      .map((segment) => translateDbText(segment.trim(), context) || '-')
      .join(' -> ')
  }

  const contextualLabel = (() => {
    if (context === 'order_status') return labelFromMap(raw, ORDER_STATUS_LABELS)
    if (context === 'payment_status') return labelFromMap(raw, PAYMENT_STATUS_LABELS)
    if (context === 'payment_method') return labelFromMap(raw, PAYMENT_METHOD_LABELS)

    return labelFromMap(raw, ORDER_STATUS_LABELS)
      || labelFromMap(raw, PAYMENT_STATUS_LABELS)
      || labelFromMap(raw, PAYMENT_METHOD_LABELS)
  })()

  if (contextualLabel) {
    return contextualLabel
  }

  const translated = GENERIC_REPLACEMENTS.reduce((accumulated, [pattern, replacement]) => {
    return accumulated.replace(pattern, replacement)
  }, raw)

  if (translated !== raw) {
    return translated
  }

  if (/[_-]/.test(raw)) {
    return humanizeToken(raw)
  }

  return translated
}

// Devuelve la etiqueta final de estado de pedido con fallback seguro.
export function getOrderStatusLabel(status) {
  return translateDbText(normalizeOrderStatus(status), 'order_status') || 'Pendiente'
}

// Devuelve la etiqueta final de estado de pago con fallback seguro.
export function getPaymentStatusLabel(status) {
  return translateDbText(status, 'payment_status') || 'Pendiente'
}

// Devuelve la etiqueta final del método de pago.
export function getPaymentMethodLabel(method) {
  return translateDbText(method, 'payment_method') || 'N/A'
}

// Asocia estados de pedido con clases visuales de badge.
export function getOrderStatusBadgeClass(status) {
  const normalized = normalizeOrderStatus(status)

  if (normalized === 'delivered') return 'delivered'
  if (normalized === 'completed') return 'active'
  if (['processing', 'in_review', 'en_revision'].includes(normalized)) return 'processing'
  if (normalized === 'shipped') return 'shipped'
  if (['cancelled', 'canceled', 'refunded'].includes(normalized)) return 'cancelled'
  return 'pending'
}

// Asocia estados de pago con clases visuales de badge.
export function getPaymentStatusBadgeClass(status) {
  const normalized = normalizePaymentStatus(status)

  if (['paid', 'verified', 'approved'].includes(normalized)) return 'active'
  if (['failed', 'rejected', 'refunded', 'cancelled', 'canceled'].includes(normalized)) return 'cancelled'
  return 'pending'
}

// Traduce el nombre de campo mostrado en el historial.
export function getHistoryFieldLabel(field) {
  return HISTORY_FIELD_LABELS[normalizeToken(field)] || 'Cambio'
}

// Traduce valores antiguos y nuevos del historial según el campo afectado.
export function translateHistoryValue(value, field) {
  const normalizedField = normalizeToken(field)
  if (normalizedField === 'payment_status') {
    return translateDbText(value, 'payment_status') || '-'
  }

  if (normalizedField === 'payment_method') {
    return translateDbText(value, 'payment_method') || '-'
  }

  return translateDbText(value, 'order_status') || '-'
}

// Traduce acciones masivas para mensajes de confirmación o auditoría.
export function getBulkActionLabel(action) {
  return BULK_ACTION_LABELS[normalizeToken(action)] || 'acción masiva'
}
