const ORDER_STATUS_LABELS = Object.freeze({
  created: 'Creada',
  pending: 'Pendiente',
  pending_payment: 'Pendiente de pago',
  in_review: 'En revision',
  en_revision: 'En revision',
  processing: 'En proceso',
  shipped: 'Enviado',
  delivered: 'Entregado',
  completed: 'Completado',
  cancelled: 'Cancelado',
  canceled: 'Cancelado',
  refunded: 'Reembolsado',
})

const PAYMENT_STATUS_LABELS = Object.freeze({
  pending: 'Pendiente',
  pending_payment: 'Pendiente de pago',
  in_review: 'En revision',
  en_revision: 'En revision',
  pending_refund: 'Reembolso en proceso',
  paid: 'Pagado',
  verified: 'Verificado',
  approved: 'Aprobado',
  failed: 'Fallido',
  refunded: 'Reembolsado',
  rejected: 'Rechazado',
  cancelled: 'Cancelado',
  canceled: 'Cancelado',
})

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

const HISTORY_FIELD_LABELS = Object.freeze({
  status: 'Estado',
  payment_status: 'Estado de pago',
  payment_method: 'Método de pago',
  order_status: 'Estado de la orden',
})

const BULK_ACTION_LABELS = Object.freeze({
  change_status: 'cambio de estado',
  change_payment_status: 'cambio de estado de pago',
  deactivate: 'desactivación',
})

const GENERIC_REPLACEMENTS = [
  [/\bcreated\b/gi, 'Creada'],
  [/\bpending_payment\b/gi, 'Pendiente de pago'],
  [/\bin_review\b/gi, 'En revision'],
  [/\ben_revision\b/gi, 'En revision'],
  [/\bpending_refund\b/gi, 'Reembolso en proceso'],
  [/\bpending\b/gi, 'Pendiente'],
  [/\bprocessing\b/gi, 'En proceso'],
  [/\bshipped\b/gi, 'Enviado'],
  [/\bdelivered\b/gi, 'Entregado'],
  [/\bcompleted\b/gi, 'Completado'],
  [/\bcancelled\b/gi, 'Cancelado'],
  [/\bcanceled\b/gi, 'Cancelado'],
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

function normalizeToken(value) {
  return String(value ?? '')
    .trim()
    .toLowerCase()
    .replace(/\s+/g, '_')
    .replace(/-/g, '_')
}

function labelFromMap(value, map) {
  return map[normalizeToken(value)] || ''
}

function humanizeToken(value) {
  const normalized = String(value ?? '')
    .trim()
    .replace(/[\s_-]+/g, ' ')

  if (!normalized) {
    return ''
  }

  return normalized.charAt(0).toUpperCase() + normalized.slice(1).toLowerCase()
}

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

export function getOrderStatusLabel(status) {
  return translateDbText(status, 'order_status') || 'Pendiente'
}

export function getPaymentStatusLabel(status) {
  return translateDbText(status, 'payment_status') || 'Pendiente'
}

export function getPaymentMethodLabel(method) {
  return translateDbText(method, 'payment_method') || 'N/A'
}

export function getOrderStatusBadgeClass(status) {
  const normalized = normalizeToken(status)

  if (normalized === 'delivered') return 'delivered'
  if (normalized === 'completed') return 'active'
  if (['processing', 'in_review', 'en_revision'].includes(normalized)) return 'processing'
  if (normalized === 'shipped') return 'shipped'
  if (['cancelled', 'canceled', 'refunded'].includes(normalized)) return 'cancelled'
  return 'pending'
}

export function getPaymentStatusBadgeClass(status) {
  const normalized = normalizeToken(status)

  if (['paid', 'verified', 'approved'].includes(normalized)) return 'active'
  if (['failed', 'rejected', 'refunded', 'cancelled', 'canceled'].includes(normalized)) return 'cancelled'
  return 'pending'
}

export function getHistoryFieldLabel(field) {
  return HISTORY_FIELD_LABELS[normalizeToken(field)] || 'Cambio'
}

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

export function getBulkActionLabel(action) {
  return BULK_ACTION_LABELS[normalizeToken(action)] || 'acción masiva'
}