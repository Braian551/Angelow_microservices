<template>
  <div class="admin-entity-page inventory-page">
    <AdminPageHeader
      icon="fas fa-warehouse"
      title="Inventario"
      subtitle="Monitorea stock por producto, revisa variantes y ejecuta ajustes o transferencias sin salir del flujo SPA."
      :breadcrumbs="[{ label: 'Dashboard', to: '/admin' }, { label: 'Inventario' }]"
    />

    <AdminStatsGrid :loading="loading" :count="4" :stats="inventoryStats" />

    <div class="filters-bar entity-filters">
      <div class="filter-group entity-filters__search">
        <label for="inventory-search">Buscar</label>
        <input id="inventory-search" v-model="search" type="text" placeholder="Producto, color, talla o SKU">
      </div>
      <div class="entity-filters__summary">
        <span><i class="fas fa-boxes"></i> {{ filteredProducts.length }} producto(s)</span>
      </div>
    </div>

    <div class="admin-tabs inventory-tabs">
      <button type="button" class="admin-tab" :class="{ active: activeTab === 'all' }" @click="activeTab = 'all'">Todo</button>
      <button type="button" class="admin-tab" :class="{ active: activeTab === 'low' }" @click="activeTab = 'low'">Bajo stock</button>
      <button type="button" class="admin-tab" :class="{ active: activeTab === 'out' }" @click="activeTab = 'out'">Sin stock</button>
    </div>

    <AdminCard :flush="true">
      <AdminTableShimmer v-if="loading" :rows="6" :columns="['thumb', 'line', 'line', 'line', 'pill', 'btn']" />
      <AdminEmptyState
        v-else-if="filteredProducts.length === 0"
        icon="fas fa-warehouse"
        title="Sin productos visibles"
        description="No hay coincidencias para los filtros actuales del inventario."
      />
      <div v-else class="table-responsive">
        <table class="dashboard-table">
          <thead>
            <tr>
              <th>Imagen</th>
              <th>Producto</th>
              <th>Resumen</th>
              <th>Stock total</th>
              <th>Estado</th>
              <th>Acciones</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="product in filteredProducts" :key="product.id">
              <td>
                <img
                  :src="product.image"
                  :alt="product.name"
                  class="inventory-thumb"
                  @error="onInventoryImageError($event, product.rawImage)"
                >
              </td>
              <td>
                <div class="entity-name-cell">
                  <strong>{{ product.name }}</strong>
                  <span>{{ product.variantCount }} variante(s) | {{ product.skuCount }} SKU(s)</span>
                </div>
              </td>
              <td>
                <div class="inventory-summary">
                  <span>{{ product.colorCount }} color(es)</span>
                  <span>{{ product.sizeCount }} talla(s)</span>
                  <span>{{ product.lowVariantCount }} variante(s) criticas</span>
                </div>
              </td>
              <td>
                <strong :class="stockTextClass(product.totalStock)">{{ product.totalStock }}</strong>
              </td>
              <td>
                <span class="status-badge" :class="statusClass(product.status)">{{ statusLabel(product.status) }}</span>
              </td>
              <td>
                <div class="entity-actions">
                  <button class="action-btn view" type="button" title="Ver detalle" @click="openDetail(product)">
                    <i class="fas fa-eye"></i>
                  </button>
                  <button class="action-btn edit" type="button" title="Ajuste rapido" @click="openQuickAdjust(product)">
                    <i class="fas fa-sliders-h"></i>
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </AdminCard>

    <AdminModal :show="showDetailModal" :title="detailModalTitle" max-width="1180px" @close="closeDetailModal">
      <div v-if="detailLoading" class="inventory-detail-loading">
        <AdminTableShimmer :rows="4" :columns="['line', 'line', 'line', 'line', 'btn']" />
      </div>
      <template v-else-if="selectedProductDetail">
        <div class="inventory-detail-header">
          <div>
            <h4>{{ selectedProductDetail.product.name }}</h4>
            <p>{{ selectedProductDetail.variantRows.length }} variante(s) operativas para este producto.</p>
          </div>
          <div class="inventory-detail-pills">
            <span class="inventory-pill"><p>Stock total</p><strong>{{ selectedProductDetail.totalStock }}</strong></span>
            <span class="inventory-pill"><p>Bajo stock</p><strong>{{ selectedProductDetail.lowStockCount }}</strong></span>
            <span class="inventory-pill"><p>Sin stock</p><strong>{{ selectedProductDetail.outOfStockCount }}</strong></span>
          </div>
        </div>

        <div class="table-responsive">
          <table class="dashboard-table">
            <thead>
              <tr>
                <th>Color</th>
                <th>Talla</th>
                <th>SKU</th>
                <th>Stock</th>
                <th>Estado</th>
                <th>Acciones</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="variant in selectedProductDetail.variantRows" :key="variant.id">
                <td>{{ variant.color_name || 'Sin color' }}</td>
                <td>{{ variant.size_name || variant.size_label || 'Sin talla' }}</td>
                <td>{{ variant.sku || '-' }}</td>
                <td><strong :class="stockTextClass(variant.quantity)">{{ variant.quantity }}</strong></td>
                <td><span class="status-badge" :class="statusClass(stockStatus(variant.quantity))">{{ statusLabel(stockStatus(variant.quantity)) }}</span></td>
                <td>
                  <div class="entity-actions">
                    <button class="action-btn edit" type="button" title="Ajustar" @click="openAdjustModal(variant)">
                      <i class="fas fa-pen"></i>
                    </button>
                    <button class="action-btn view" type="button" title="Transferir" @click="openTransferModal(variant)">
                      <i class="fas fa-right-left"></i>
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <div class="inventory-history-block">
          <div class="inventory-history-header">
            <div>
              <h4>Historial reciente</h4>
              <p>Ultimos movimientos registrados para este producto.</p>
            </div>
            <button class="btn btn-secondary btn-sm" type="button" @click="reloadDetailHistory">Actualizar historial</button>
          </div>

          <AdminEmptyState
            v-if="historyLoading"
            icon="fas fa-clock-rotate-left"
            title="Cargando historial"
            description="Consultando los ultimos movimientos registrados."
          />
          <AdminEmptyState
            v-else-if="selectedProductHistory.length === 0"
            icon="fas fa-clock-rotate-left"
            title="Sin movimientos recientes"
            description="Todavia no hay ajustes o transferencias registradas para este producto."
          />
          <div v-else class="table-responsive">
            <table class="dashboard-table">
              <thead>
                <tr>
                  <th>Fecha</th>
                  <th>Variante</th>
                  <th>Operacion</th>
                  <th>Cambio</th>
                  <th>Notas</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="entry in selectedProductHistory" :key="entry.id">
                  <td>{{ formatDateTime(entry.created_at) }}</td>
                  <td>{{ buildHistoryVariantLabel(entry) }}</td>
                  <td>{{ formatOperation(entry.operation) }}</td>
                  <td>{{ entry.previous_qty }} -> {{ entry.new_qty }}</td>
                  <td>{{ entry.notes || 'Sin notas' }}</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </template>
    </AdminModal>

    <AdminModal :show="showAdjustModal" title="Ajustar stock" max-width="520px" @close="closeAdjustModal">
      <div class="inventory-form-grid">
        <div class="form-group inventory-form-grid__full">
          <label>Variante seleccionada</label>
          <div class="inventory-variant-preview">{{ selectedVariantLabel }}</div>
        </div>

        <div class="form-group">
          <label for="adjust-action">Accion</label>
          <select id="adjust-action" v-model="adjustForm.action" class="form-control">
            <option value="add">Agregar</option>
            <option value="subtract">Restar</option>
            <option value="set">Establecer</option>
          </select>
        </div>

        <div class="form-group">
          <label for="adjust-quantity">Cantidad *</label>
          <input id="adjust-quantity" v-model="adjustForm.quantity" type="number" min="0" class="form-control" :class="{ 'is-invalid': adjustErrors.quantity }" @input="validateAdjustField('quantity')">
          <p v-if="adjustErrors.quantity" class="form-error">{{ adjustErrors.quantity }}</p>
        </div>

        <div class="form-group inventory-form-grid__full">
          <label for="adjust-reason">Motivo</label>
          <textarea id="adjust-reason" v-model="adjustForm.reason" class="form-control" rows="3"></textarea>
        </div>
      </div>

      <template #footer>
        <button class="btn btn-secondary" type="button" @click="closeAdjustModal">Cancelar</button>
        <button class="btn btn-primary" type="button" @click="submitAdjust">Aplicar ajuste</button>
      </template>
    </AdminModal>

    <AdminModal :show="showTransferModal" title="Transferir stock" max-width="560px" @close="closeTransferModal">
      <div class="inventory-form-grid">
        <div class="form-group inventory-form-grid__full">
          <label>Variante origen</label>
          <div class="inventory-variant-preview">{{ transferSourceLabel }}</div>
        </div>

        <div class="form-group inventory-form-grid__full">
          <label for="transfer-target">Variante destino *</label>
          <select id="transfer-target" v-model="transferForm.target_variant_id" class="form-control" :class="{ 'is-invalid': transferErrors.target_variant_id }" @change="validateTransferField('target_variant_id')">
            <option value="">Selecciona una variante</option>
            <option v-for="option in transferTargets" :key="option.id" :value="String(option.id)">
              {{ buildVariantLabel(option) }}
            </option>
          </select>
          <p v-if="transferErrors.target_variant_id" class="form-error">{{ transferErrors.target_variant_id }}</p>
        </div>

        <div class="form-group">
          <label for="transfer-quantity">Cantidad *</label>
          <input id="transfer-quantity" v-model="transferForm.quantity" type="number" min="1" class="form-control" :class="{ 'is-invalid': transferErrors.quantity }" @input="validateTransferField('quantity')">
          <p v-if="transferErrors.quantity" class="form-error">{{ transferErrors.quantity }}</p>
        </div>

        <div class="form-group inventory-form-grid__full">
          <label for="transfer-reason">Motivo</label>
          <textarea id="transfer-reason" v-model="transferForm.reason" class="form-control" rows="3"></textarea>
        </div>
      </div>

      <template #footer>
        <button class="btn btn-secondary" type="button" @click="closeTransferModal">Cancelar</button>
        <button class="btn btn-primary" type="button" @click="submitTransfer">Transferir stock</button>
      </template>
    </AdminModal>
  </div>
</template>

<script setup>
import { computed, onMounted, reactive, ref } from 'vue'
import { catalogHttp } from '../../../services/http'
import { useSnackbarSystem } from '../../../composables/useSnackbarSystem'
import { handleMediaError, resolveMediaUrl } from '../../../utils/media'
import AdminCard from '../components/AdminCard.vue'
import AdminEmptyState from '../components/AdminEmptyState.vue'
import AdminModal from '../components/AdminModal.vue'
import AdminPageHeader from '../components/AdminPageHeader.vue'
import AdminStatsGrid from '../components/AdminStatsGrid.vue'
import AdminTableShimmer from '../components/AdminTableShimmer.vue'

const { showSnackbar } = useSnackbarSystem()

const loading = ref(true)
const detailLoading = ref(false)
const historyLoading = ref(false)
const search = ref('')
const activeTab = ref('all')
const inventoryRows = ref([])
const showDetailModal = ref(false)
const showAdjustModal = ref(false)
const showTransferModal = ref(false)
const selectedProductSummary = ref(null)
const selectedProductDetail = ref(null)
const selectedProductHistory = ref([])
const selectedVariant = ref(null)

const adjustForm = reactive({
  action: 'add',
  quantity: '',
  reason: '',
})

const adjustErrors = reactive({ quantity: '' })

const transferForm = reactive({
  source_variant_id: '',
  target_variant_id: '',
  quantity: '',
  reason: '',
})

const transferErrors = reactive({
  target_variant_id: '',
  quantity: '',
})

const groupedProducts = computed(() => {
  const grouped = new Map()

  inventoryRows.value.forEach((row) => {
    const existing = grouped.get(row.product_id) || {
      id: row.product_id,
      name: row.product_name,
      image: row.image,
      rawImage: row.rawImage,
      variants: [],
    }

    existing.variants.push(row)
    grouped.set(row.product_id, existing)
  })

  return Array.from(grouped.values()).map((product) => {
    const variantCount = product.variants.length
    const totalStock = product.variants.reduce((sum, variant) => sum + Number(variant.stock || 0), 0)
    const lowVariantCount = product.variants.filter((variant) => Number(variant.stock || 0) > 0 && Number(variant.stock || 0) <= 5).length
    const outVariantCount = product.variants.filter((variant) => Number(variant.stock || 0) <= 0).length
    const skuCount = product.variants.filter((variant) => variant.sku).length
    const colorCount = new Set(product.variants.map((variant) => variant.color_name || '')).size
    const sizeCount = new Set(product.variants.map((variant) => variant.size_label || '')).size
    const status = totalStock <= 0 ? 'out' : lowVariantCount > 0 ? 'low' : 'active'

    return {
      ...product,
      variantCount,
      totalStock,
      lowVariantCount,
      outVariantCount,
      skuCount,
      colorCount,
      sizeCount,
      status,
    }
  })
})

const filteredProducts = computed(() => {
  const term = search.value.trim().toLowerCase()

  return groupedProducts.value.filter((product) => {
    const matchesSearch = !term || product.variants.some((variant) => [product.name, variant.color_name, variant.size_label, variant.sku]
      .some((value) => String(value || '').toLowerCase().includes(term)))

    const matchesTab = activeTab.value === 'all'
      || (activeTab.value === 'low' && product.status === 'low')
      || (activeTab.value === 'out' && product.status === 'out')

    return matchesSearch && matchesTab
  })
})

const inventoryStats = computed(() => {
  const totalProducts = groupedProducts.value.length
  const totalUnits = groupedProducts.value.reduce((sum, product) => sum + product.totalStock, 0)
  const lowStockProducts = groupedProducts.value.filter((product) => product.status === 'low').length
  const outOfStockProducts = groupedProducts.value.filter((product) => product.status === 'out').length

  return [
    { key: 'products', label: 'Productos monitoreados', value: String(totalProducts), icon: 'fas fa-box', color: 'primary' },
    { key: 'units', label: 'Unidades disponibles', value: String(totalUnits), icon: 'fas fa-cubes', color: 'success' },
    { key: 'low', label: 'Productos con alerta', value: String(lowStockProducts), icon: 'fas fa-triangle-exclamation', color: 'warning' },
    { key: 'out', label: 'Productos sin stock', value: String(outOfStockProducts), icon: 'fas fa-circle-xmark', color: 'danger' },
  ]
})

const detailModalTitle = computed(() => selectedProductSummary.value ? `Detalle de inventario: ${selectedProductSummary.value.name}` : 'Detalle de inventario')
const selectedVariantLabel = computed(() => selectedVariant.value ? buildVariantLabel(selectedVariant.value) : 'Sin variante seleccionada')
const transferSourceLabel = computed(() => selectedVariant.value ? `${buildVariantLabel(selectedVariant.value)} | Stock actual: ${selectedVariant.value.quantity}` : 'Sin variante origen')
const transferTargets = computed(() => {
  const rows = selectedProductDetail.value?.variantRows || []
  return rows.filter((row) => Number(row.id) !== Number(selectedVariant.value?.id || 0))
})

function stockStatus(stock) {
  const value = Number(stock || 0)
  if (value <= 0) return 'out'
  if (value <= 5) return 'low'
  return 'active'
}

function statusClass(status) {
  if (status === 'active') return 'active'
  if (status === 'low') return 'pending'
  return 'cancelled'
}

function statusLabel(status) {
  if (status === 'active') return 'En stock'
  if (status === 'low') return 'Bajo stock'
  return 'Sin stock'
}

function stockTextClass(stock) {
  return `inventory-stock inventory-stock--${stockStatus(stock)}`
}

function normalizeInventoryRow(item) {
  return {
    ...item,
    id: Number(item.id),
    product_id: Number(item.product_id),
    color_variant_id: Number(item.color_variant_id || 0),
    product_name: item.product_name || item.name || 'Sin nombre',
    color_name: item.color_name || 'Sin color',
    size_label: item.size_label || item.size_name || 'Sin talla',
    stock: Number(item.stock || item.quantity || 0),
    sku: item.sku || '',
    rawImage: item.image || item.primary_image || item.product_image || item.imagen || null,
    image: resolveMediaUrl(item.image || item.primary_image || item.product_image || item.imagen || null, 'product'),
  }
}

function buildVariantLabel(variant) {
  const color = variant.color_name || 'Sin color'
  const size = variant.size_name || variant.size_label || 'Sin talla'
  const sku = variant.sku ? ` | SKU ${variant.sku}` : ''
  return `${color} / ${size}${sku}`
}

function buildHistoryVariantLabel(entry) {
  return `${entry.color_name || 'Sin color'} / ${entry.size_label || 'Sin talla'}`
}

function formatOperation(operation) {
  if (operation === 'add') return 'Ingreso'
  if (operation === 'subtract') return 'Salida'
  if (operation === 'set') return 'Ajuste directo'
  if (operation === 'transfer') return 'Transferencia'
  return operation || 'Movimiento'
}

function formatDateTime(value) {
  if (!value) return 'Sin fecha'
  const date = new Date(value)
  return Number.isNaN(date.getTime()) ? 'Sin fecha' : date.toLocaleString('es-CO')
}

function onInventoryImageError(event, imagePath) {
  handleMediaError(event, imagePath, 'product')
}

function normalizeProductDetail(productId, payload) {
  const product = payload.product || {}
  const variants = Array.isArray(payload.variants) ? payload.variants : []
  const variantRows = []

  variants.forEach((variant) => {
    const rows = Array.isArray(variant.size_variants) ? variant.size_variants : []
    rows.forEach((row) => {
      variantRows.push({
        ...row,
        id: Number(row.id),
        product_id: productId,
        color_variant_id: Number(row.color_variant_id || variant.id || 0),
        color_name: row.color_name || variant.color_name || 'Sin color',
        size_name: row.size_name || row.size_label || 'Sin talla',
        size_label: row.size_name || row.size_label || 'Sin talla',
        quantity: Number(row.quantity || row.stock || 0),
        sku: row.sku || '',
      })
    })
  })

  return {
    product: {
      id: productId,
      name: product.name || product.nombre || selectedProductSummary.value?.name || 'Producto',
    },
    variantRows,
    totalStock: variantRows.reduce((sum, row) => sum + row.quantity, 0),
    lowStockCount: variantRows.filter((row) => row.quantity > 0 && row.quantity <= 5).length,
    outOfStockCount: variantRows.filter((row) => row.quantity <= 0).length,
  }
}

function resetAdjustForm() {
  adjustForm.action = 'add'
  adjustForm.quantity = ''
  adjustForm.reason = ''
  adjustErrors.quantity = ''
}

function resetTransferForm() {
  transferForm.source_variant_id = ''
  transferForm.target_variant_id = ''
  transferForm.quantity = ''
  transferForm.reason = ''
  transferErrors.target_variant_id = ''
  transferErrors.quantity = ''
}

function validateAdjustField(field) {
  if (field === 'quantity') {
    const quantity = Number(adjustForm.quantity)
    adjustErrors.quantity = Number.isFinite(quantity) && quantity >= 0 ? '' : 'La cantidad debe ser un numero valido.'
  }
}

function validateTransferField(field) {
  if (field === 'target_variant_id') {
    transferErrors.target_variant_id = transferForm.target_variant_id ? '' : 'Debes seleccionar una variante destino.'
  }

  if (field === 'quantity') {
    const quantity = Number(transferForm.quantity)
    const available = Number(selectedVariant.value?.quantity || 0)
    transferErrors.quantity = Number.isFinite(quantity) && quantity > 0 && quantity <= available
      ? ''
      : 'La cantidad debe ser mayor que cero y no exceder el stock disponible.'
  }
}

async function loadInventory() {
  loading.value = true
  try {
    const response = await catalogHttp.get('/admin/inventory')
    const data = response.data?.data || response.data || []
    const rows = Array.isArray(data) ? data : (data.data || [])
    inventoryRows.value = rows.map(normalizeInventoryRow)
  } catch {
    showSnackbar({ type: 'error', message: 'Error cargando inventario' })
  } finally {
    loading.value = false
  }
}

async function loadProductDetail(product) {
  detailLoading.value = true
  historyLoading.value = true
  selectedProductSummary.value = product

  try {
    const [detailResponse, historyResponse] = await Promise.all([
      catalogHttp.get(`/admin/products/${product.id}`),
      catalogHttp.get('/admin/inventory/history', { params: { product_id: product.id } }),
    ])

    const detailPayload = detailResponse.data?.data || {}
    const historyPayload = historyResponse.data?.data || historyResponse.data || []
    const historyRows = Array.isArray(historyPayload) ? historyPayload : (historyPayload.data || [])

    selectedProductDetail.value = normalizeProductDetail(product.id, detailPayload)
    selectedProductHistory.value = historyRows
  } catch {
    showSnackbar({ type: 'error', message: 'Error cargando detalle de inventario' })
  } finally {
    detailLoading.value = false
    historyLoading.value = false
  }
}

async function openDetail(product) {
  showDetailModal.value = true
  await loadProductDetail(product)
}

async function openQuickAdjust(product) {
  await openDetail(product)
  const firstVariant = selectedProductDetail.value?.variantRows?.[0] || null
  if (firstVariant) {
    openAdjustModal(firstVariant)
  }
}

function closeDetailModal() {
  showDetailModal.value = false
  selectedProductSummary.value = null
  selectedProductDetail.value = null
  selectedProductHistory.value = []
}

function openAdjustModal(variant) {
  selectedVariant.value = variant
  resetAdjustForm()
  showAdjustModal.value = true
}

function closeAdjustModal() {
  showAdjustModal.value = false
  selectedVariant.value = null
}

function openTransferModal(variant) {
  selectedVariant.value = variant
  resetTransferForm()
  transferForm.source_variant_id = String(variant.id)
  showTransferModal.value = true
}

function closeTransferModal() {
  showTransferModal.value = false
  selectedVariant.value = null
}

async function reloadDetailHistory() {
  if (!selectedProductSummary.value) return
  historyLoading.value = true
  try {
    const response = await catalogHttp.get('/admin/inventory/history', { params: { product_id: selectedProductSummary.value.id } })
    const payload = response.data?.data || response.data || []
    selectedProductHistory.value = Array.isArray(payload) ? payload : (payload.data || [])
  } catch {
    showSnackbar({ type: 'error', message: 'Error actualizando historial' })
  } finally {
    historyLoading.value = false
  }
}

async function refreshAfterStockChange() {
  await loadInventory()
  if (selectedProductSummary.value) {
    await loadProductDetail(selectedProductSummary.value)
  }
}

async function submitAdjust() {
  validateAdjustField('quantity')
  if (adjustErrors.quantity || !selectedVariant.value?.id) return

  try {
    await catalogHttp.patch(`/admin/inventory/${selectedVariant.value.id}/stock`, {
      action: adjustForm.action,
      quantity: Number(adjustForm.quantity),
      reason: adjustForm.reason?.trim() || null,
    })

    showSnackbar({ type: 'success', message: 'Stock actualizado' })
    closeAdjustModal()
    await refreshAfterStockChange()
  } catch (error) {
    showSnackbar({ type: 'error', message: error?.response?.data?.message || 'Error ajustando stock' })
  }
}

async function submitTransfer() {
  validateTransferField('target_variant_id')
  validateTransferField('quantity')
  if (transferErrors.target_variant_id || transferErrors.quantity || !selectedVariant.value?.id) return

  try {
    await catalogHttp.post('/admin/inventory/transfer', {
      source_variant_id: Number(transferForm.source_variant_id),
      target_variant_id: Number(transferForm.target_variant_id),
      quantity: Number(transferForm.quantity),
      reason: transferForm.reason?.trim() || null,
    })

    showSnackbar({ type: 'success', message: 'Transferencia aplicada' })
    closeTransferModal()
    await refreshAfterStockChange()
  } catch (error) {
    showSnackbar({ type: 'error', message: error?.response?.data?.message || 'Error transfiriendo stock' })
  }
}

onMounted(loadInventory)
</script>

<style scoped>
.entity-filters {
  justify-content: space-between;
}

.entity-filters__search {
  flex: 1 1 380px;
}

.entity-filters__search input {
  width: 100%;
}

.entity-filters__summary {
  margin-left: auto;
  color: var(--admin-text-light);
  font-size: 1.3rem;
}

.inventory-tabs {
  margin-bottom: 1.6rem;
}

.inventory-thumb {
  width: 4.6rem;
  height: 4.6rem;
  border-radius: 10px;
  object-fit: cover;
  background: var(--admin-bg-dark);
}

.entity-name-cell,
.entity-actions,
.inventory-summary,
.inventory-detail-pills {
  display: flex;
  gap: 0.7rem;
}

.entity-name-cell {
  flex-direction: column;
  align-items: flex-start;
}

.entity-name-cell span,
.inventory-summary,
.inventory-history-header p,
.inventory-detail-header p {
  color: var(--admin-text-light);
  font-size: 1.25rem;
}

.inventory-summary {
  flex-direction: column;
}

.inventory-stock {
  font-weight: 700;
}

.inventory-stock--active {
  color: var(--admin-success);
}

.inventory-stock--low {
  color: #8a6d00;
}

.inventory-stock--out {
  color: var(--admin-error);
}

.inventory-detail-header,
.inventory-history-header,
.inventory-form-grid__full {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 1rem;
}

.inventory-detail-header,
.inventory-history-block {
  margin-bottom: 1.6rem;
}

.inventory-detail-header h4,
.inventory-history-header h4 {
  margin: 0 0 0.4rem;
}

.inventory-detail-pills {
  flex-wrap: wrap;
}

.inventory-form-grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 1.2rem;
}

.inventory-form-grid__full {
  grid-column: 1 / -1;
}

.inventory-variant-preview {
  width: 100%;
  padding: 1rem 1.2rem;
  border: 1px solid var(--admin-border);
  border-radius: 12px;
  background: var(--admin-bg-dark);
  color: var(--admin-text);
}

.inventory-detail-loading {
  min-height: 20rem;
}

@media (max-width: 900px) {
  .inventory-detail-header,
  .inventory-history-header,
  .inventory-form-grid__full {
    flex-direction: column;
    align-items: flex-start;
  }
}

@media (max-width: 768px) {
  .inventory-form-grid {
    grid-template-columns: 1fr;
  }
}
</style>
