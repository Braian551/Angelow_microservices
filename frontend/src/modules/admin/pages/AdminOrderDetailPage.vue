<template>
  <div class="admin-order-detail-page">
    <AdminPageHeader
      icon="fas fa-shopping-bag"
      :title="headerTitle"
      subtitle="Detalle completo del pedido con el mismo flujo operativo de administración."
      :breadcrumbs="[{ label: 'Dashboard', to: '/admin' }, { label: 'Órdenes', to: '/admin/ordenes' }, { label: breadcrumbOrderLabel }]"
    >
      <template #actions>
        <RouterLink to="/admin/ordenes" class="btn btn-secondary">
          <i class="fas fa-arrow-left"></i> Volver
        </RouterLink>
      </template>
    </AdminPageHeader>

    <div v-if="loading" class="order-detail-loading-grid">
      <AdminCard title="Resumen de la orden" icon="fas fa-clipboard-list">
        <AdminTableShimmer :rows="4" :columns="['line', 'line', 'line', 'line']" />
      </AdminCard>
      <AdminCard title="Productos" icon="fas fa-box" :flush="true">
        <AdminTableShimmer :rows="4" :columns="['line', 'line', 'line', 'line']" />
      </AdminCard>
    </div>

    <template v-else-if="order">
      <AdminCard title="Resumen de la Orden" icon="fas fa-file-invoice">
        <template #headerActions>
          <div class="summary-actions">
            <button type="button" class="order-toolbar-btn order-toolbar-btn--neutral" @click="openEditModal">
              <span class="order-toolbar-btn__icon"><i class="fas fa-edit"></i></span>
              <span>Editar</span>
            </button>
            <button type="button" class="order-toolbar-btn order-toolbar-btn--primary" @click="openStatusModal">
              <span class="order-toolbar-btn__icon"><i class="fas fa-sync-alt"></i></span>
              <span>Cambiar estado</span>
            </button>
            <button type="button" class="order-toolbar-btn order-toolbar-btn--accent" @click="openPaymentStatusModal">
              <span class="order-toolbar-btn__icon"><i class="fas fa-credit-card"></i></span>
              <span>Cambiar pago</span>
            </button>
          </div>
        </template>

        <div class="order-spotlight">
          <div class="order-spotlight__main">
            <span class="order-spotlight__eyebrow">Seguimiento del pedido</span>
            <h3>{{ order.order_number }}</h3>
            <p>{{ order.customer_name }}<span v-if="order.customer_email"> · {{ order.customer_email }}</span></p>
          </div>
          <div class="order-spotlight__chips">
            <span class="status-badge" :class="statusBadgeClass(order.status)">{{ statusLabel(order.status) }}</span>
            <span class="status-badge" :class="paymentBadgeClass(order.payment_status)">{{ paymentLabel(order.payment_status) }}</span>
          </div>
        </div>

        <div class="order-highlight-grid">
          <div class="order-highlight-card order-highlight-card--strong">
            <span>Total del pedido</span>
            <strong>{{ formatCurrency(order.total) }}</strong>
            <small>{{ items.length }} {{ items.length === 1 ? 'producto' : 'productos' }}</small>
          </div>
          <div class="order-highlight-card">
            <span>Método de pago</span>
            <strong>{{ paymentMethodLabel(order.payment_method) }}</strong>
            <small>{{ paymentLabel(order.payment_status) }}</small>
          </div>
          <div class="order-highlight-card">
            <span>Entrega</span>
            <strong>{{ order.shipping_city || 'Por definir' }}</strong>
            <small>{{ addressSourceLabel }}</small>
          </div>
        </div>

        <div class="order-info-grid">
          <div class="order-info-item">
            <label>Número de orden:</label>
            <strong>{{ order.order_number }}</strong>
          </div>
          <div class="order-info-item">
            <label>Fecha:</label>
            <strong>{{ formatDateTime(order.created_at) }}</strong>
          </div>
          <div class="order-info-item">
            <label>Cliente:</label>
            <strong>{{ order.customer_name }}</strong>
          </div>
          <div class="order-info-item">
            <label>Email:</label>
            <strong>{{ order.customer_email || 'No disponible' }}</strong>
          </div>
          <div class="order-info-item">
            <label>Teléfono:</label>
            <strong>{{ order.customer_phone || 'No disponible' }}</strong>
          </div>
          <div class="order-info-item">
            <label>Estado:</label>
            <strong><span class="status-badge" :class="statusBadgeClass(order.status)">{{ statusLabel(order.status) }}</span></strong>
          </div>
          <div class="order-info-item">
            <label>Método de pago:</label>
            <strong>{{ paymentMethodLabel(order.payment_method) }}</strong>
          </div>
          <div class="order-info-item">
            <label>Estado de pago:</label>
            <strong><span class="status-badge" :class="paymentBadgeClass(order.payment_status)">{{ paymentLabel(order.payment_status) }}</span></strong>
          </div>
          <div class="order-info-item">
            <label>Subtotal:</label>
            <strong>{{ formatCurrency(order.subtotal) }}</strong>
          </div>
          <div class="order-info-item">
            <label>Envío:</label>
            <strong>{{ formatCurrency(order.shipping_cost) }}</strong>
          </div>
        </div>

        <div class="order-total-strip">
          <span>Total:</span>
          <strong>{{ formatCurrency(order.total) }}</strong>
        </div>
      </AdminCard>

      <section class="order-detail-stack">
        <AdminCard title="Dirección de Envío" icon="fas fa-map-marker-alt">
          <div class="admin-detail-summary">
            <div class="admin-detail-summary__row admin-detail-summary__row--stack">
              <span>Dirección</span>
              <strong>{{ order.shipping_address || 'No se ha proporcionado dirección de envío' }}</strong>
            </div>
            <div class="admin-detail-summary__row">
              <span>Ciudad</span>
              <strong>{{ order.shipping_city || 'No disponible' }}</strong>
            </div>
            <div class="admin-detail-summary__row">
              <span>Origen</span>
              <strong>{{ addressSourceLabel }}</strong>
            </div>
            <div v-if="order.notes" class="admin-detail-summary__row admin-detail-summary__row--stack">
              <span>Notas</span>
              <strong>{{ order.notes }}</strong>
            </div>
          </div>
        </AdminCard>

        <AdminCard title="Comprobante de Pago" icon="fas fa-file-invoice-dollar">
          <template v-if="paymentRecord?.proof_url && paymentRecord.proof_exists !== false && !paymentProofUnavailable">
            <div class="payment-proof-card">
              <a :href="paymentRecord.proof_url" target="_blank" rel="noreferrer" class="payment-proof-card__preview" :class="{ 'payment-proof-card__preview--file': !paymentProofIsImage }">
                <img v-if="paymentProofIsImage" :src="paymentRecord.proof_url" alt="Comprobante de pago" class="payment-proof-card__image" @error="handlePaymentProofError">
                <div v-else class="payment-proof-card__file">
                  <i class="fas fa-file-pdf"></i>
                  <strong>{{ paymentRecord.proof_name || 'Abrir comprobante' }}</strong>
                  <span>Ver documento adjunto</span>
                </div>
              </a>

              <div class="payment-proof-card__meta">
                <div class="payment-proof-card__meta-item">
                  <span>Referencia</span>
                  <strong>{{ paymentRecord.reference_number || 'Sin referencia' }}</strong>
                </div>
                <div class="payment-proof-card__meta-item">
                  <span>Estado</span>
                  <strong>{{ paymentLabel(paymentRecord.status || order.payment_status) }}</strong>
                </div>
              </div>
            </div>
          </template>
          <div v-else-if="paymentRecord?.proof_url" class="detail-empty detail-empty--soft payment-proof-card__missing">
            <i class="fas fa-image-slash"></i>
            <div>
              <strong>Comprobante no disponible.</strong>
              <p>No pudimos mostrar el comprobante en este momento.</p>
            </div>
          </div>
          <div v-else class="detail-empty detail-empty--soft">No hay comprobante de pago adjunto para esta orden.</div>
        </AdminCard>

        <AdminCard title="Productos del Pedido" icon="fas fa-box" :flush="true">
          <div v-if="items.length === 0" class="detail-empty">Sin productos registrados en la orden.</div>
          <table v-else class="dashboard-table nested-table">
            <thead>
              <tr>
                <th>Producto</th>
                <th>Cantidad</th>
                <th>Precio</th>
                <th>Subtotal</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="item in items" :key="item.id || `${item.product_id}-${item.variant_name || ''}`">
                <td>{{ item.product_name || item.name || 'Producto' }}</td>
                <td>{{ Number(item.quantity || 0) }}</td>
                <td>{{ formatCurrency(item.unit_price || item.price || 0) }}</td>
                <td>{{ formatCurrency(item.total || (Number(item.unit_price || item.price || 0) * Number(item.quantity || 0))) }}</td>
              </tr>
            </tbody>
            <tfoot>
              <tr>
                <td colspan="3" class="totals-label">Subtotal</td>
                <td>{{ formatCurrency(order.subtotal) }}</td>
              </tr>
              <tr>
                <td colspan="3" class="totals-label">Envío</td>
                <td>{{ formatCurrency(order.shipping_cost) }}</td>
              </tr>
              <tr>
                <td colspan="3" class="totals-label"><strong>Total</strong></td>
                <td><strong>{{ formatCurrency(order.total) }}</strong></td>
              </tr>
            </tfoot>
          </table>
        </AdminCard>
      </section>

      <section class="order-history-section">
        <AdminCard title="Historial de Cambios" icon="fas fa-history">
          <template #headerActions>
            <button v-if="showHistoryToggle" type="button" class="history-toggle-btn" :class="{ 'history-toggle-btn--expanded': expandedHistory }" @click="toggleHistoryExpansion">
              <i class="fas" :class="expandedHistory ? 'fa-chevron-up' : 'fa-chevron-down'"></i>
              <span>{{ expandedHistory ? 'Ver menos' : `Ver todos (${hiddenHistoryCount} más)` }}</span>
            </button>
          </template>

          <div v-if="history.length === 0" class="detail-empty">Sin movimientos registrados.</div>
          <div v-else class="history-timeline">
            <article v-for="entry in visibleHistory" :key="entry.id" class="history-timeline__item" :style="{ '--timeline-accent': getHistoryTypeColor(entry) }">
              <div class="history-timeline__point">
                <i class="fas" :class="getHistoryTypeIcon(entry)"></i>
              </div>

              <div class="history-timeline__card">
                <div class="history-timeline__header">
                  <div>
                    <h4>{{ entry.description || `${historyFieldLabel(entry.field_changed)} actualizado` }}</h4>
                    <p>{{ historyFieldLabel(entry.field_changed) }}</p>
                  </div>
                  <span class="history-timeline__date">
                    <i class="fas fa-clock"></i>
                    {{ formatTimelineDate(entry.created_at) }}
                  </span>
                </div>

                <div class="history-timeline__user">
                  <div class="history-timeline__user-main">
                    <i class="fas fa-user"></i>
                    <span>{{ getHistoryActorName(entry) }}</span>
                  </div>
                  <span class="history-role-badge" :class="`history-role-badge--${getHistoryActorRole(entry).variant}`">{{ getHistoryActorRole(entry).label }}</span>
                </div>

                <div v-if="entry.old_value || entry.new_value" class="history-timeline__values">
                  <div class="history-change-box history-change-box--old">
                    <span class="history-change-box__label">Anterior</span>
                    <span class="history-change-box__value">{{ translateHistoryValue(entry.old_value, entry.field_changed) }}</span>
                  </div>
                  <i class="fas fa-arrow-right history-timeline__arrow"></i>
                  <div class="history-change-box history-change-box--new">
                    <span class="history-change-box__label">Nuevo</span>
                    <span class="history-change-box__value">{{ translateHistoryValue(entry.new_value, entry.field_changed) }}</span>
                  </div>
                </div>

                <div v-if="entry.ip_address" class="history-timeline__meta">
                  <i class="fas fa-network-wired"></i>
                  <span>IP: {{ entry.ip_address }}</span>
                </div>
              </div>
            </article>
          </div>
        </AdminCard>
      </section>
    </template>

    <AdminCard v-else title="Orden no disponible" icon="fas fa-exclamation-circle">
      <p>No fue posible cargar la orden solicitada.</p>
    </AdminCard>

    <AdminModal :show="showEditModal" title="Editar orden" max-width="760px" @close="closeEditModal">
      <div class="form-grid-2">
        <div class="form-group">
          <label for="edit-customer-name">Cliente</label>
          <input id="edit-customer-name" v-model="editForm.customer_name" type="text" class="form-control" :class="{ 'is-invalid': editErrors.customer_name }" @input="validateEditField('customer_name')">
          <p v-if="editErrors.customer_name" class="form-error">{{ editErrors.customer_name }}</p>
        </div>
        <div class="form-group">
          <label for="edit-customer-email">Email</label>
          <input id="edit-customer-email" v-model="editForm.customer_email" type="email" class="form-control" :class="{ 'is-invalid': editErrors.customer_email }" @input="validateEditField('customer_email')">
          <p v-if="editErrors.customer_email" class="form-error">{{ editErrors.customer_email }}</p>
        </div>
        <div class="form-group">
          <label for="edit-customer-phone">Telefono</label>
          <input id="edit-customer-phone" v-model="editForm.customer_phone" type="text" class="form-control" :class="{ 'is-invalid': editErrors.customer_phone }" @input="validateEditField('customer_phone')">
          <p v-if="editErrors.customer_phone" class="form-error">{{ editErrors.customer_phone }}</p>
        </div>
        <div class="form-group">
          <label for="edit-shipping-city">Ciudad</label>
          <input id="edit-shipping-city" v-model="editForm.shipping_city" type="text" class="form-control" :class="{ 'is-invalid': editErrors.shipping_city }" @input="validateEditField('shipping_city')">
          <p v-if="editErrors.shipping_city" class="form-error">{{ editErrors.shipping_city }}</p>
        </div>
        <div class="form-group form-group--full">
          <label for="edit-shipping-address">Direccion de envio</label>
          <textarea id="edit-shipping-address" v-model="editForm.shipping_address" rows="3" class="form-control" :class="{ 'is-invalid': editErrors.shipping_address }" @input="validateEditField('shipping_address')"></textarea>
          <p v-if="editErrors.shipping_address" class="form-error">{{ editErrors.shipping_address }}</p>
        </div>
        <div class="form-group form-group--full">
          <label for="edit-notes">Notas</label>
          <textarea id="edit-notes" v-model="editForm.notes" rows="3" class="form-control" :class="{ 'is-invalid': editErrors.notes }" @input="validateEditField('notes')"></textarea>
          <p v-if="editErrors.notes" class="form-error">{{ editErrors.notes }}</p>
        </div>
      </div>
      <template #footer>
        <button class="btn btn-secondary" type="button" @click="closeEditModal">Cancelar</button>
        <button class="btn btn-primary" type="button" :disabled="saving" @click="submitEditOrder">Guardar cambios</button>
      </template>
    </AdminModal>

    <AdminModal :show="showStatusModal" title="Cambiar estado de la orden" max-width="560px" @close="closeStatusModal">
      <div class="form-grid-1">
        <div class="form-group">
          <label for="status-value">Estado *</label>
          <select id="status-value" v-model="statusForm.status" class="form-control" :class="{ 'is-invalid': statusErrors.status }" @change="validateStatusField('status')">
            <option value="pending">Pendiente</option>
            <option value="processing">En proceso</option>
            <option value="shipped">Enviado</option>
            <option value="delivered">Entregado</option>
            <option value="cancelled">Cancelado</option>
            <option value="refunded">Reembolsado</option>
          </select>
          <p v-if="statusErrors.status" class="form-error">{{ statusErrors.status }}</p>
        </div>
        <div class="form-group">
          <label for="status-description">Descripcion del cambio *</label>
          <textarea id="status-description" v-model="statusForm.description" rows="4" class="form-control" :class="{ 'is-invalid': statusErrors.description }" @input="validateStatusField('description')"></textarea>
          <p v-if="statusErrors.description" class="form-error">{{ statusErrors.description }}</p>
        </div>
      </div>
      <template #footer>
        <button class="btn btn-secondary" type="button" @click="closeStatusModal">Cancelar</button>
        <button class="btn btn-primary" type="button" :disabled="saving" @click="submitStatusChange">Guardar estado</button>
      </template>
    </AdminModal>

    <AdminModal :show="showPaymentStatusModal" title="Cambiar estado de pago" max-width="560px" @close="closePaymentStatusModal">
      <div class="form-grid-1">
        <div class="form-group">
          <label for="payment-status-value">Estado de pago *</label>
          <select id="payment-status-value" v-model="paymentForm.payment_status" class="form-control" :class="{ 'is-invalid': paymentErrors.payment_status }" @change="validatePaymentField('payment_status')">
            <option value="pending">Pendiente</option>
            <option value="paid">Pagado</option>
            <option value="verified">Verificado</option>
            <option value="failed">Fallido</option>
            <option value="refunded">Reembolsado</option>
          </select>
          <p v-if="paymentErrors.payment_status" class="form-error">{{ paymentErrors.payment_status }}</p>
        </div>
        <div class="form-group">
          <label for="payment-description">Descripcion del cambio *</label>
          <textarea id="payment-description" v-model="paymentForm.description" rows="4" class="form-control" :class="{ 'is-invalid': paymentErrors.description }" @input="validatePaymentField('description')"></textarea>
          <p v-if="paymentErrors.description" class="form-error">{{ paymentErrors.description }}</p>
        </div>
      </div>
      <template #footer>
        <button class="btn btn-secondary" type="button" @click="closePaymentStatusModal">Cancelar</button>
        <button class="btn btn-primary" type="button" :disabled="saving" @click="submitPaymentStatusChange">Guardar estado de pago</button>
      </template>
    </AdminModal>
  </div>
</template>

<script setup>
import { computed, onMounted, reactive, ref } from 'vue'
import { RouterLink, useRoute } from 'vue-router'
import { orderHttp, paymentHttp } from '../../../services/http'
import { resolveUploadUrl } from '../../../utils/media'
import { useAlertSystem } from '../../../composables/useAlertSystem'
import { useSnackbarSystem } from '../../../composables/useSnackbarSystem'
import {
  getHistoryFieldLabel,
  getOrderStatusBadgeClass,
  getOrderStatusLabel,
  getPaymentMethodLabel,
  getPaymentStatusBadgeClass,
  getPaymentStatusLabel,
  translateHistoryValue,
} from '../utils/orderPresentation'
import AdminCard from '../components/AdminCard.vue'
import AdminModal from '../components/AdminModal.vue'
import AdminPageHeader from '../components/AdminPageHeader.vue'
import AdminTableShimmer from '../components/AdminTableShimmer.vue'

const route = useRoute()
const { showAlert } = useAlertSystem()
const { showSnackbar } = useSnackbarSystem()

const loading = ref(true)
const saving = ref(false)
const order = ref(null)
const items = ref([])
const history = ref([])
const paymentRecord = ref(null)
const paymentProofUnavailable = ref(false)
const expandedHistory = ref(false)

const showEditModal = ref(false)
const showStatusModal = ref(false)
const showPaymentStatusModal = ref(false)

const statusForm = reactive({
  status: 'pending',
  description: '',
})

const statusErrors = reactive({
  status: '',
  description: '',
})

const paymentForm = reactive({
  payment_status: 'pending',
  description: '',
})

const paymentErrors = reactive({
  payment_status: '',
  description: '',
})

const editForm = reactive({
  customer_name: '',
  customer_email: '',
  customer_phone: '',
  shipping_address: '',
  shipping_city: '',
  notes: '',
})

const editErrors = reactive({
  customer_name: '',
  customer_email: '',
  customer_phone: '',
  shipping_address: '',
  shipping_city: '',
  notes: '',
})

const orderId = computed(() => Number(route.params.id))
const orderSource = computed(() => String(route.query.vista || '').toLowerCase() === 'archivo' ? 'legacy' : 'microservice')

const headerTitle = computed(() => {
  if (order.value?.order_number) {
    return `Detalle de Orden ${order.value.order_number}`
  }
  return `Detalle de Orden #${orderId.value}`
})

const breadcrumbOrderLabel = computed(() => {
  if (order.value?.order_number) {
    return order.value.order_number
  }
  return `#${orderId.value}`
})

const addressSourceLabel = computed(() => {
  if (!order.value) return 'N/A'
  if (Number(order.value.gps_used || 0) === 1) return 'GPS usado'
  if (order.value.gps_latitude && order.value.gps_longitude) return 'Con coordenadas'
  return 'Manual'
})

const paymentProofIsImage = computed(() => {
  return Boolean(paymentRecord.value?.proof_url && /\.(png|jpe?g|webp|gif|bmp|svg)(\?.*)?$/i.test(paymentRecord.value.proof_url))
})

const hiddenHistoryCount = computed(() => Math.max(history.value.length - 1, 0))
const showHistoryToggle = computed(() => hiddenHistoryCount.value > 0)
const visibleHistory = computed(() => expandedHistory.value ? history.value : history.value.slice(0, 1))

function resolvePaymentProofUrl(value) {
  // Evita romper toda la carga del comprobante si falla la normalizacion de URL.
  try {
    return resolveUploadUrl(value)
  } catch {
    const raw = String(value || '').trim()
    return raw ? (raw.startsWith('/') ? raw : `/${raw}`) : ''
  }
}

function normalizeProofPath(value) {
  const proofPath = String(value || '').trim()
  if (!proofPath) return ''

  const normalized = proofPath.replace(/\\/g, '/')

  if (/^https?:\/\//i.test(normalized)) return normalized
  if (normalized.startsWith('/uploads/') || normalized.startsWith('uploads/')) return normalized
  if (normalized.startsWith('/payment_proofs/') || normalized.startsWith('payment_proofs/')) {
    return normalized.replace(/^\/?payment_proofs\/?/, 'uploads/payment_proofs/')
  }

  // Compatibilidad con registros antiguos que guardaban solo el nombre del archivo.
  return `uploads/payment_proofs/${normalized.replace(/^\/+/, '')}`
}

function normalizePaymentRecord(rawPayment = {}) {
  const proofPath = normalizeProofPath(rawPayment.proof_url || rawPayment.payment_proof || '')
  return {
    ...rawPayment,
    proof_url: resolvePaymentProofUrl(proofPath),
    proof_name: rawPayment.proof_name || (proofPath ? proofPath.split('/').pop() : ''),
    proof_exists: rawPayment.proof_exists !== false,
  }
}

function extractPaymentRows(payload) {
  if (Array.isArray(payload?.data)) return payload.data
  if (Array.isArray(payload?.data?.data)) return payload.data.data
  if (Array.isArray(payload)) return payload
  return []
}

function pickPaymentForCurrentOrder(rows = []) {
  if (!Array.isArray(rows) || rows.length === 0) return null

  return rows.find((row) => Number(row?.order_id || 0) === orderId.value) || rows[0] || null
}

function handlePaymentProofError() {
  paymentProofUnavailable.value = true
}

function toggleHistoryExpansion() {
  expandedHistory.value = !expandedHistory.value
}

function normalizeOrder(rawOrder = {}) {
  return {
    ...rawOrder,
    id: Number(rawOrder.id || orderId.value || 0),
    order_source: rawOrder.order_source || orderSource.value,
    order_number: rawOrder.order_number || `#${rawOrder.id || orderId.value}`,
    created_at: rawOrder.created_at || null,
    status: rawOrder.status || rawOrder.order_status || 'pending',
    payment_status: rawOrder.payment_status || 'pending',
    payment_method: rawOrder.payment_method || '',
    customer_name: rawOrder.user_name || rawOrder.customer_name || rawOrder.billing_name || 'Cliente no registrado',
    customer_email: rawOrder.user_email || rawOrder.customer_email || rawOrder.billing_email || '',
    customer_phone: rawOrder.user_phone || rawOrder.customer_phone || rawOrder.billing_phone || '',
    subtotal: Number(rawOrder.subtotal || 0),
    shipping_cost: Number(rawOrder.shipping_cost || rawOrder.shipping || 0),
    discount_amount: Number(rawOrder.discount_amount || 0),
    total: Number(rawOrder.total || 0),
    shipping_address: rawOrder.shipping_address || rawOrder.address_current || rawOrder.billing_address || '',
    shipping_city: rawOrder.shipping_city || rawOrder.billing_city || '',
    notes: rawOrder.notes || '',
    gps_used: rawOrder.gps_used || 0,
    gps_latitude: rawOrder.gps_latitude || null,
    gps_longitude: rawOrder.gps_longitude || null,
  }
}

function hydrateEditForm() {
  if (!order.value) return
  editForm.customer_name = order.value.customer_name || ''
  editForm.customer_email = order.value.customer_email || ''
  editForm.customer_phone = order.value.customer_phone || ''
  editForm.shipping_address = order.value.shipping_address || ''
  editForm.shipping_city = order.value.shipping_city || ''
  editForm.notes = order.value.notes || ''
}

function resetStatusForm() {
  statusForm.status = order.value?.status || 'pending'
  statusForm.description = ''
  statusErrors.status = ''
  statusErrors.description = ''
}

function resetPaymentForm() {
  paymentForm.payment_status = order.value?.payment_status || 'pending'
  paymentForm.description = ''
  paymentErrors.payment_status = ''
  paymentErrors.description = ''
}

function resetEditErrors() {
  editErrors.customer_name = ''
  editErrors.customer_email = ''
  editErrors.customer_phone = ''
  editErrors.shipping_address = ''
  editErrors.shipping_city = ''
  editErrors.notes = ''
}

async function loadOrder() {
  loading.value = true
  try {
    const res = await orderHttp.get(`/orders/${orderId.value}`, { params: { source: orderSource.value } })
    const payload = res.data || {}
    const rawOrder = payload.order || payload.data?.order || payload.data || {}
    const rawItems = payload.items || payload.data?.items || []
    const rawHistory = payload.history || payload.data?.history || []

    order.value = normalizeOrder(rawOrder)
    items.value = Array.isArray(rawItems) ? rawItems : []
    history.value = Array.isArray(rawHistory) ? rawHistory : []
    expandedHistory.value = false
    await loadPaymentRecord()
    hydrateEditForm()
    resetStatusForm()
    resetPaymentForm()
  } catch {
    showSnackbar({ type: 'error', message: 'Error cargando el detalle de la orden.' })
    order.value = null
    items.value = []
    history.value = []
    paymentRecord.value = null
  } finally {
    loading.value = false
  }
}

async function loadPaymentRecord() {
  paymentProofUnavailable.value = false

  try {
    const response = await paymentHttp.get('/admin/payments', { params: { order_id: orderId.value, per_page: 1 } })
    let rows = extractPaymentRows(response.data)

    // Fallback de migracion: si admin/payments no devuelve filas, intenta endpoint publico.
    if (rows.length === 0) {
      const fallbackResponse = await paymentHttp.get('/payments', { params: { order_id: orderId.value } })
      rows = extractPaymentRows(fallbackResponse.data)
        .filter((row) => Number(row?.order_id || 0) === orderId.value)
    }

    const selectedPayment = pickPaymentForCurrentOrder(rows)
    paymentRecord.value = selectedPayment ? normalizePaymentRecord(selectedPayment) : null
  } catch {
    try {
      const fallbackResponse = await paymentHttp.get('/payments', { params: { order_id: orderId.value } })
      const rows = extractPaymentRows(fallbackResponse.data)
        .filter((row) => Number(row?.order_id || 0) === orderId.value)

      const selectedPayment = pickPaymentForCurrentOrder(rows)
      paymentRecord.value = selectedPayment ? normalizePaymentRecord(selectedPayment) : null
    } catch {
      paymentRecord.value = null
    }
  }
}

function openEditModal() {
  hydrateEditForm()
  resetEditErrors()
  showEditModal.value = true
}

function closeEditModal() {
  showEditModal.value = false
}

function validateEditField(field) {
  const value = String(editForm[field] || '').trim()

  if (field === 'customer_name') {
    editErrors.customer_name = value.length >= 3 ? '' : 'El nombre debe tener al menos 3 caracteres.'
  }

  if (field === 'customer_email') {
    if (!value) {
      editErrors.customer_email = ''
      return
    }
    editErrors.customer_email = /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value)
      ? ''
      : 'Ingresa un email valido.'
  }

  if (field === 'customer_phone') {
    if (!value) {
      editErrors.customer_phone = ''
      return
    }
    editErrors.customer_phone = value.length >= 7 ? '' : 'El telefono debe tener al menos 7 caracteres.'
  }

  if (field === 'shipping_address') {
    editErrors.shipping_address = value.length >= 5 ? '' : 'La direccion debe tener al menos 5 caracteres.'
  }

  if (field === 'shipping_city') {
    editErrors.shipping_city = value.length >= 2 ? '' : 'La ciudad debe tener al menos 2 caracteres.'
  }

  if (field === 'notes') {
    if (!value) {
      editErrors.notes = ''
      return
    }
    editErrors.notes = value.length >= 4 ? '' : 'La nota debe tener al menos 4 caracteres.'
  }
}

async function submitEditOrder() {
  validateEditField('customer_name')
  validateEditField('customer_email')
  validateEditField('customer_phone')
  validateEditField('shipping_address')
  validateEditField('shipping_city')
  validateEditField('notes')

  const hasErrors = Object.values(editErrors).some(Boolean)
  if (hasErrors) return

  showAlert({
    type: 'warning',
    title: 'Confirmar cambios',
    message: 'Se actualizaran los datos principales de la orden.',
    actions: [
      { text: 'Cancelar', style: 'secondary' },
      {
        text: 'Guardar',
        style: 'primary',
        callback: async () => {
          saving.value = true
          try {
            await orderHttp.patch(`/orders/${orderId.value}`, {
              source: orderSource.value,
              customer_name: editForm.customer_name.trim(),
              customer_email: editForm.customer_email.trim(),
              customer_phone: editForm.customer_phone.trim(),
              shipping_address: editForm.shipping_address.trim(),
              shipping_city: editForm.shipping_city.trim(),
              notes: editForm.notes.trim(),
            })
            showSnackbar({ type: 'success', message: 'Orden actualizada correctamente.' })
            closeEditModal()
            await loadOrder()
          } catch {
            showSnackbar({ type: 'error', message: 'No fue posible actualizar la orden.' })
          } finally {
            saving.value = false
          }
        },
      },
    ],
  })
}

function openStatusModal() {
  resetStatusForm()
  showStatusModal.value = true
}

function closeStatusModal() {
  showStatusModal.value = false
}

function validateStatusField(field) {
  if (field === 'status') {
    statusErrors.status = statusForm.status ? '' : 'Selecciona un estado.'
  }
  if (field === 'description') {
    statusErrors.description = statusForm.description.trim().length >= 5
      ? ''
      : 'La descripcion debe tener al menos 5 caracteres.'
  }
}

async function submitStatusChange() {
  validateStatusField('status')
  validateStatusField('description')
  if (statusErrors.status || statusErrors.description) return

  saving.value = true
  try {
    await orderHttp.patch(`/orders/${orderId.value}/status`, {
      source: orderSource.value,
      status: statusForm.status,
      description: statusForm.description.trim(),
    })
    showSnackbar({ type: 'success', message: 'Estado de la orden actualizado.' })
    closeStatusModal()
    await loadOrder()
  } catch {
    showSnackbar({ type: 'error', message: 'No se pudo actualizar el estado de la orden.' })
  } finally {
    saving.value = false
  }
}

function openPaymentStatusModal() {
  resetPaymentForm()
  showPaymentStatusModal.value = true
}

function closePaymentStatusModal() {
  showPaymentStatusModal.value = false
}

function validatePaymentField(field) {
  if (field === 'payment_status') {
    paymentErrors.payment_status = paymentForm.payment_status ? '' : 'Selecciona un estado de pago.'
  }
  if (field === 'description') {
    paymentErrors.description = paymentForm.description.trim().length >= 5
      ? ''
      : 'La descripcion debe tener al menos 5 caracteres.'
  }
}

async function submitPaymentStatusChange() {
  validatePaymentField('payment_status')
  validatePaymentField('description')
  if (paymentErrors.payment_status || paymentErrors.description) return

  saving.value = true
  try {
    await orderHttp.patch(`/orders/${orderId.value}/payment-status`, {
      source: orderSource.value,
      payment_status: paymentForm.payment_status,
      description: paymentForm.description.trim(),
    })
    showSnackbar({ type: 'success', message: 'Estado de pago actualizado.' })
    closePaymentStatusModal()
    await loadOrder()
  } catch {
    showSnackbar({ type: 'error', message: 'No se pudo actualizar el estado de pago.' })
  } finally {
    saving.value = false
  }
}

function formatCurrency(value) {
  return `$ ${Number(value || 0).toLocaleString('es-CO')}`
}

function formatDateTime(value) {
  if (!value) return 'Sin fecha'
  const date = new Date(value)
  return Number.isNaN(date.getTime()) ? 'Sin fecha' : date.toLocaleString('es-CO')
}

function formatTimelineDate(value) {
  if (!value) return 'Sin fecha'
  const date = new Date(value)
  return Number.isNaN(date.getTime())
    ? 'Sin fecha'
    : date.toLocaleString('es-CO', {
      year: 'numeric',
      month: '2-digit',
      day: '2-digit',
      hour: '2-digit',
      minute: '2-digit',
    })
}

function statusLabel(status) {
  return getOrderStatusLabel(status)
}

function paymentLabel(status) {
  return getPaymentStatusLabel(status)
}

function paymentMethodLabel(method) {
  return getPaymentMethodLabel(method)
}

function statusBadgeClass(status) {
  return getOrderStatusBadgeClass(status)
}

function paymentBadgeClass(status) {
  return getPaymentStatusBadgeClass(status)
}

function historyFieldLabel(field) {
  return getHistoryFieldLabel(field)
}

function normalizeHistoryToken(value) {
  return String(value || '')
    .trim()
    .toLowerCase()
    .replace(/\s+/g, '_')
    .replace(/-/g, '_')
}

function getHistoryType(entry) {
  const field = normalizeHistoryToken(entry?.field_changed)

  if (field === 'status' || field === 'order_status') return 'status'
  if (field === 'payment_status') return 'payment_status'
  if (field.includes('shipping') || field.includes('address') || field.includes('city')) return 'shipping'
  if (field.includes('note')) return 'notes'
  if (field.includes('item') || field.includes('product')) return 'items'
  return 'other'
}

function getHistoryTypeColor(entry) {
  return {
    status: '#0077b6',
    payment_status: '#10b981',
    shipping: '#f59e0b',
    notes: '#6366f1',
    items: '#ec4899',
    other: '#64748b',
  }[getHistoryType(entry)] || '#64748b'
}

function getHistoryTypeIcon(entry) {
  return {
    status: 'fa-rotate',
    payment_status: 'fa-credit-card',
    shipping: 'fa-truck',
    notes: 'fa-note-sticky',
    items: 'fa-box-open',
    other: 'fa-pen'
  }[getHistoryType(entry)] || 'fa-pen'
}

function getHistoryActorName(entry) {
  return entry?.changed_by_name || entry?.changed_by || 'Sistema'
}

function getHistoryActorRole(entry) {
  const role = normalizeHistoryToken(entry?.changed_by_role || '')
  const actorName = normalizeHistoryToken(getHistoryActorName(entry))

  if (role === 'admin' || role === 'administrator') return { label: 'Administrador', variant: 'admin' }
  if (role === 'customer' || role === 'cliente') return { label: 'Cliente', variant: 'customer' }
  if (role === 'delivery' || role === 'repartidor') return { label: 'Repartidor', variant: 'delivery' }
  if (actorName && actorName !== 'sistema' && actorName !== 'system') return { label: 'Administrador', variant: 'admin' }
  return { label: 'Sistema', variant: 'system' }
}

onMounted(loadOrder)
</script>

<style scoped>
.summary-actions {
  display: flex;
  flex-wrap: wrap;
  gap: 0.8rem;
}

.order-toolbar-btn {
  display: inline-flex;
  align-items: center;
  gap: 0.72rem;
  min-height: 4.2rem;
  padding: 0.78rem 1.2rem;
  border-radius: 1.3rem;
  border: 1px solid transparent;
  background: rgba(255, 255, 255, 0.9);
  color: var(--admin-text-heading);
  font-weight: 700;
  cursor: pointer;
  box-shadow: 0 12px 24px rgba(15, 55, 96, 0.08);
  transition: transform 0.2s ease, box-shadow 0.2s ease, border-color 0.2s ease;
}

.order-toolbar-btn:hover {
  transform: translateY(-1px);
  box-shadow: 0 16px 28px rgba(15, 55, 96, 0.1);
}

.order-toolbar-btn__icon {
  width: 3rem;
  height: 3rem;
  border-radius: 1rem;
  display: inline-flex;
  align-items: center;
  justify-content: center;
}

.order-toolbar-btn--neutral {
  border-color: rgba(148, 184, 216, 0.24);
  color: var(--admin-primary);
}

.order-toolbar-btn--neutral .order-toolbar-btn__icon {
  background: rgba(0, 119, 182, 0.1);
}

.order-toolbar-btn--primary {
  background: rgba(86, 191, 116, 0.14);
  border-color: rgba(86, 191, 116, 0.22);
  color: #1f6e33;
}

.order-toolbar-btn--primary .order-toolbar-btn__icon {
  background: rgba(86, 191, 116, 0.18);
}

.order-toolbar-btn--accent {
  background: rgba(0, 119, 182, 0.12);
  border-color: rgba(0, 119, 182, 0.16);
  color: var(--admin-primary-dark);
}

.order-toolbar-btn--accent .order-toolbar-btn__icon {
  background: rgba(0, 119, 182, 0.18);
}

.order-spotlight {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 1rem;
  padding: 1.15rem 1.2rem;
  margin-bottom: 1.1rem;
  border: 1px solid rgba(148, 184, 216, 0.22);
  border-radius: 1.7rem;
  background: rgba(241, 248, 255, 0.9);
}

.order-spotlight__main {
  display: grid;
  gap: 0.35rem;
}

.order-spotlight__eyebrow {
  font-size: 1.05rem;
  text-transform: uppercase;
  letter-spacing: 0.08em;
  color: var(--admin-text-soft);
  font-weight: 800;
}

.order-spotlight__main h3 {
  margin: 0;
  font-size: 2.05rem;
  color: var(--admin-text-heading);
}

.order-spotlight__main p {
  margin: 0;
  color: var(--admin-text-soft);
  font-size: 1.22rem;
}

.order-spotlight__chips {
  display: flex;
  flex-wrap: wrap;
  justify-content: flex-end;
  gap: 0.65rem;
}

.order-highlight-grid {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 1rem;
  margin-bottom: 1.1rem;
}

.order-highlight-card {
  display: grid;
  gap: 0.42rem;
  padding: 1.2rem 1.25rem;
  border-radius: 1.55rem;
  border: 1px solid rgba(148, 184, 216, 0.22);
  background: rgba(247, 251, 255, 0.88);
  box-shadow: 0 12px 24px rgba(15, 55, 96, 0.06);
}

.order-highlight-card--strong {
  background: rgba(0, 119, 182, 0.08);
  border-color: rgba(0, 119, 182, 0.18);
}

.order-highlight-card span {
  font-size: 1.04rem;
  text-transform: uppercase;
  letter-spacing: 0.06em;
  color: var(--admin-text-soft);
  font-weight: 800;
}

.order-highlight-card strong {
  font-size: 1.9rem;
  color: var(--admin-text-heading);
}

.order-highlight-card small {
  color: var(--admin-text-light);
  font-size: 1.08rem;
}

.order-detail-loading-grid {
  display: grid;
  gap: 1.2rem;
}

.order-info-grid {
  display: grid;
  grid-template-columns: repeat(4, minmax(0, 1fr));
  gap: 1rem;
}

.order-info-item {
  border: 1px solid rgba(148, 184, 216, 0.22);
  background: rgba(247, 251, 255, 0.86);
  border-radius: 1.6rem;
  padding: 1.15rem 1.25rem;
  display: flex;
  flex-direction: column;
  gap: 0.45rem;
  box-shadow: 0 10px 24px rgba(15, 55, 96, 0.06);
}

.order-info-item label {
  font-size: 1.1rem;
  color: var(--admin-text-light);
  text-transform: uppercase;
  letter-spacing: 0.03em;
  font-weight: 700;
}

.order-info-item strong {
  font-size: 1.55rem;
  color: var(--admin-text-dark);
}

.order-total-strip {
  margin-top: 1rem;
  display: flex;
  justify-content: space-between;
  align-items: center;
  border: 1px solid rgba(0, 119, 182, 0.22);
  background: rgba(247, 251, 255, 0.88);
  border-radius: 1.6rem;
  padding: 1.1rem 1.45rem;
  font-size: 1.8rem;
  color: var(--admin-primary);
}

.payment-proof-card {
  display: grid;
  gap: 1rem;
}

.payment-proof-card__preview {
  display: block;
  border: 1px solid rgba(148, 184, 216, 0.24);
  border-radius: 1.6rem;
  background: rgba(247, 251, 255, 0.82);
  padding: 1rem;
  text-decoration: none;
  transition: transform 0.2s ease, box-shadow 0.2s ease, border-color 0.2s ease;
}

.payment-proof-card__preview:hover {
  transform: translateY(-1px);
  border-color: rgba(0, 119, 182, 0.22);
  box-shadow: 0 16px 28px rgba(15, 55, 96, 0.1);
}

.payment-proof-card__preview--file {
  min-height: 18rem;
  display: grid;
  place-items: center;
}

.payment-proof-card__image {
  width: 100%;
  max-height: 34rem;
  object-fit: contain;
  border-radius: 1.2rem;
}

.payment-proof-card__file {
  display: grid;
  gap: 0.7rem;
  justify-items: center;
  text-align: center;
  color: var(--admin-primary-dark);
}

.payment-proof-card__file i {
  font-size: 3rem;
}

.payment-proof-card__meta {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 0.9rem;
}

.payment-proof-card__meta-item {
  padding: 1rem 1.1rem;
  border-radius: 1.3rem;
  border: 1px solid rgba(148, 184, 216, 0.2);
  background: rgba(255, 255, 255, 0.84);
  display: grid;
  gap: 0.35rem;
}

.payment-proof-card__meta-item span {
  font-size: 1.05rem;
  color: var(--admin-text-light);
  text-transform: uppercase;
  letter-spacing: 0.04em;
  font-weight: 700;
}

.payment-proof-card__meta-item strong {
  font-size: 1.35rem;
  color: var(--admin-text-dark);
}

.payment-proof-card__missing {
  display: flex;
  align-items: flex-start;
  gap: 1rem;
}

.payment-proof-card__missing i {
  font-size: 2.2rem;
  color: var(--admin-primary);
}

.payment-proof-card__missing p {
  margin: 0.3rem 0 0;
  color: var(--admin-text-light);
}

.detail-empty--soft {
  border: 1px dashed rgba(148, 184, 216, 0.24);
  border-radius: 1.4rem;
  background: rgba(247, 251, 255, 0.62);
}

.order-detail-stack {
  margin-top: 1.2rem;
  display: grid;
  gap: 1.2rem;
}

.order-history-section {
  margin-top: 1.2rem;
}

.detail-empty {
  padding: 1.6rem;
  color: var(--admin-text-light);
}

.history-toggle-btn {
  display: inline-flex;
  align-items: center;
  gap: 0.55rem;
  min-height: 3.7rem;
  padding: 0.68rem 1rem;
  border: 1px solid rgba(0, 119, 182, 0.16);
  border-radius: 1.15rem;
  background: rgba(0, 119, 182, 0.08);
  color: var(--admin-primary-dark);
  font-weight: 700;
  cursor: pointer;
  transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.history-toggle-btn:hover {
  transform: translateY(-1px);
  box-shadow: 0 12px 22px rgba(15, 55, 96, 0.08);
}

.history-toggle-btn--expanded {
  background: rgba(0, 119, 182, 0.12);
}

.history-timeline {
  position: relative;
  display: grid;
  gap: 1.4rem;
  padding-left: 1.7rem;
}

.history-timeline::before {
  content: '';
  position: absolute;
  top: 0.3rem;
  bottom: 0.3rem;
  left: 0.95rem;
  width: 2px;
  background: rgba(0, 119, 182, 0.16);
}

.history-timeline__item {
  position: relative;
  display: grid;
  grid-template-columns: auto 1fr;
  gap: 1rem;
  align-items: flex-start;
}

.history-timeline__point {
  position: relative;
  z-index: 1;
  width: 4rem;
  height: 4rem;
  border-radius: 50%;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  color: white;
  background: var(--timeline-accent);
  box-shadow: 0 12px 22px color-mix(in srgb, var(--timeline-accent) 30%, transparent);
}

.history-timeline__card {
  display: grid;
  gap: 1rem;
  padding: 1.35rem 1.4rem;
  border-radius: 1.7rem;
  border: 1px solid rgba(148, 184, 216, 0.22);
  background: rgba(248, 251, 255, 0.96);
  box-shadow: 0 16px 34px rgba(15, 55, 96, 0.08);
}

.history-timeline__header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  gap: 1rem;
}

.history-timeline__header h4 {
  margin: 0;
  font-size: 1.7rem;
  color: var(--admin-text-heading);
}

.history-timeline__header p {
  margin: 0.25rem 0 0;
  color: var(--admin-text-soft);
  font-size: 1.08rem;
}

.history-timeline__date {
  display: inline-flex;
  align-items: center;
  gap: 0.45rem;
  padding: 0.72rem 0.95rem;
  border-radius: 999px;
  background: rgba(0, 119, 182, 0.08);
  color: var(--admin-primary-dark);
  font-size: 1.08rem;
  font-weight: 700;
  white-space: nowrap;
}

.history-timeline__user {
  display: flex;
  align-items: center;
  gap: 0.8rem;
  flex-wrap: wrap;
}

.history-timeline__user-main {
  display: inline-flex;
  align-items: center;
  gap: 0.55rem;
  color: var(--admin-text-heading);
  font-weight: 700;
}

.history-role-badge {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  min-height: 3rem;
  padding: 0.42rem 0.92rem;
  border-radius: 999px;
  font-size: 0.98rem;
  font-weight: 800;
  text-transform: uppercase;
  letter-spacing: 0.05em;
}

.history-role-badge--admin {
  background: rgba(255, 116, 116, 0.14);
  color: #db5252;
}

.history-role-badge--customer {
  background: rgba(0, 119, 182, 0.12);
  color: var(--admin-primary-dark);
}

.history-role-badge--delivery {
  background: rgba(16, 185, 129, 0.12);
  color: #12805b;
}

.history-role-badge--system {
  background: rgba(138, 160, 184, 0.14);
  color: #516173;
}

.history-timeline__values {
  display: grid;
  grid-template-columns: 1fr auto 1fr;
  align-items: center;
  gap: 0.9rem;
}

.history-change-box {
  display: grid;
  gap: 0.45rem;
  padding: 1rem 1.05rem;
  border-radius: 1.25rem;
  border: 1px solid rgba(148, 184, 216, 0.2);
  background: white;
}

.history-change-box--old {
  box-shadow: inset 0 0 0 1px rgba(239, 68, 68, 0.08);
}

.history-change-box--new {
  box-shadow: inset 0 0 0 1px rgba(16, 185, 129, 0.08);
}

.history-change-box__label {
  font-size: 1rem;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  font-weight: 800;
}

.history-change-box--old .history-change-box__label {
  color: #ef4444;
}

.history-change-box--new .history-change-box__label {
  color: #10b981;
}

.history-change-box__value {
  font-size: 1.38rem;
  font-weight: 700;
  color: var(--admin-text-heading);
}

.history-timeline__arrow {
  color: var(--admin-primary);
  font-size: 1.9rem;
}

.history-timeline__meta {
  display: inline-flex;
  align-items: center;
  gap: 0.55rem;
  padding: 0.82rem 0.95rem;
  border-radius: 1rem;
  background: rgba(0, 119, 182, 0.05);
  color: var(--admin-text-soft);
  font-size: 1.05rem;
}

.totals-label {
  text-align: right;
}

.form-grid-2 {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 1rem;
}

.form-grid-1 {
  display: grid;
  gap: 1rem;
}

.form-group--full {
  grid-column: 1 / -1;
}

.form-error {
  margin-top: 0.4rem;
  color: var(--admin-danger);
  font-size: 1.2rem;
}

@media (max-width: 1200px) {
  .order-highlight-grid,
  .order-info-grid {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }
}

@media (max-width: 768px) {
  .order-highlight-grid,
  .order-info-grid {
    grid-template-columns: 1fr;
  }

  .history-timeline {
    padding-left: 0;
  }

  .history-timeline::before {
    display: none;
  }

  .history-timeline__item {
    grid-template-columns: 1fr;
  }

  .history-timeline__point {
    width: 3.6rem;
    height: 3.6rem;
  }

  .history-timeline__header,
  .history-timeline__values {
    grid-template-columns: 1fr;
    display: grid;
  }

  .history-timeline__arrow {
    justify-self: center;
    transform: rotate(90deg);
  }

  .order-spotlight {
    flex-direction: column;
    align-items: flex-start;
  }

  .order-spotlight__chips {
    justify-content: flex-start;
  }

  .summary-actions {
    width: 100%;
  }

  .summary-actions .order-toolbar-btn {
    width: 100%;
    justify-content: center;
  }

  .form-grid-2 {
    grid-template-columns: 1fr;
  }

  .payment-proof-card__meta {
    grid-template-columns: 1fr;
  }
}
</style>
