import { computed, onMounted, onUnmounted, reactive, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useStockRealtime } from '../../../composables/useStockRealtime'
import { catalogHttp } from '../../../services/http'
import { useSnackbarSystem } from '../../../composables/useSnackbarSystem'
import { resolveMediaUrl } from '../../../utils/media'
import { validatePositiveInteger } from '../../../utils/numericValidation'
import {
  buildInventoryVariantLabel as formatInventoryVariantLabel,
  normalizeInventoryStatus as resolveInventoryStatus,
  resolveInventoryThreshold,
} from '../utils/inventoryPresentation'
import { useAdminDataExport } from './useAdminDataExport'
import { useAdminPagination } from './useAdminPagination'

/**
 * Composable para la gestión de inventario de productos.
 * Administra visualización agrupada por producto, ajustes de stock, transferencias
 * entre variantes, historial de movimientos y sincronización en tiempo real vía WebSocket.
 * Reutiliza useAdminPagination, useAdminDataExport y useStockRealtime.
 */
export function useAdminInventory() {
  const route = useRoute()
  const router = useRouter()
  const { showSnackbar } = useSnackbarSystem()
  const { exportData, exportingFormat } = useAdminDataExport()

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
  const handledRouteTarget = ref('')
  const stockSubmitting = ref(false)
  const pendingRealtimeInventorySync = {
    reloadDetail: false,
    reloadHistory: false,
  }

  let realtimeInventorySyncTimerId = null
  let realtimeInventorySyncInFlight = false

  const adjustForm = reactive({
    action: 'set',
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
      const variants = product.variants.map((variant) => ({
        ...variant,
        inventory_status: resolveInventoryStatus(variant.stock, variant),
      }))
      const variantCount = variants.length
      const totalStock = variants.reduce((sum, variant) => sum + Number(variant.stock || 0), 0)
      const lowVariants = variants.filter((variant) => variant.inventory_status === 'low')
      const outVariants = variants.filter((variant) => variant.inventory_status === 'out')
      const lowVariantCount = lowVariants.length
      const outVariantCount = outVariants.length
      const skuCount = variants.filter((variant) => variant.sku).length
      const colorCount = new Set(variants.map((variant) => variant.color_name || '')).size
      const sizeCount = new Set(variants.map((variant) => variant.size_label || '')).size
      const status = outVariantCount > 0 ? 'out' : (lowVariantCount > 0 ? 'low' : 'active')

      return {
        ...product,
        variants,
        variantCount,
        totalStock,
        lowVariantCount,
        outVariantCount,
        skuCount,
        colorCount,
        sizeCount,
        status,
        lowStockThreshold: resolveInventoryThreshold(variants[0]),
        lowVariantLabels: lowVariants.map((variant) => formatInventoryVariantLabel(variant)),
        outVariantLabels: outVariants.map((variant) => formatInventoryVariantLabel(variant)),
      }
    })
  })

  const filteredProducts = computed(() => {
    const term = search.value.trim().toLowerCase()

    return groupedProducts.value.filter((product) => {
      const matchesSearch = !term || product.variants.some((variant) => [product.name, variant.color_name, variant.size_label, variant.sku]
        .some((value) => String(value || '').toLowerCase().includes(term)))

      const matchesTab = activeTab.value === 'all'
        || (activeTab.value === 'low' && product.lowVariantCount > 0)
        || (activeTab.value === 'out' && product.outVariantCount > 0)

      return matchesSearch && matchesTab
    })
  })

  const inventoryVariantIds = computed(() => inventoryRows.value
    .map((row) => Number(row.id || 0))
    .filter((variantId) => Number.isFinite(variantId) && variantId > 0))

  const selectedDetailVariantIds = computed(() => (selectedProductDetail.value?.variantRows || [])
    .map((row) => Number(row.id || 0))
    .filter((variantId) => Number.isFinite(variantId) && variantId > 0))

  const pagination = useAdminPagination(filteredProducts, {
    initialPageSize: 10,
    pageSizeOptions: [10, 20, 50],
  })

  const inventoryStats = computed(() => {
    const totalProducts = groupedProducts.value.length
    const totalUnits = inventoryRows.value.reduce((sum, variant) => sum + Number(variant.stock || 0), 0)
    const lowStockVariants = inventoryRows.value.filter((variant) => resolveInventoryStatus(variant.stock, variant) === 'low').length
    const outOfStockVariants = inventoryRows.value.filter((variant) => resolveInventoryStatus(variant.stock, variant) === 'out').length

    return [
      { key: 'products', label: 'Productos monitoreados', value: String(totalProducts), icon: 'fas fa-box', color: 'primary' },
      { key: 'units', label: 'Unidades disponibles', value: String(totalUnits), icon: 'fas fa-cubes', color: 'success' },
      { key: 'low', label: 'Variantes con alerta', value: String(lowStockVariants), icon: 'fas fa-triangle-exclamation', color: 'warning' },
      { key: 'out', label: 'Variantes sin stock', value: String(outOfStockVariants), icon: 'fas fa-circle-xmark', color: 'danger' },
    ]
  })

  const detailModalTitle = computed(() => selectedProductSummary.value ? `Detalle de inventario: ${selectedProductSummary.value.name}` : 'Detalle de inventario')
  const selectedVariantLabel = computed(() => selectedVariant.value ? buildVariantLabel(selectedVariant.value) : 'Sin variante seleccionada')
  const transferSourceLabel = computed(() => selectedVariant.value ? `${buildVariantLabel(selectedVariant.value)} | Stock actual: ${selectedVariant.value.quantity}` : 'Sin variante origen')
  const transferTargets = computed(() => {
    const rows = selectedProductDetail.value?.variantRows || []
    return rows.filter((row) => Number(row.id) !== Number(selectedVariant.value?.id || 0))
  })

  function stockStatus(stock, source = null) {
    return resolveInventoryStatus(stock, source)
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

  function productStatusLabel(product) {
    if (!product) return 'Sin stock'
    if (product.status === 'out' && Number(product.totalStock || 0) > 0) return 'Variantes agotadas'
    return statusLabel(product.status)
  }

  function inventorySummaryAlert(product) {
    if (!product) return 'Sin alertas'
    const outPreview = (product.outVariantLabels || []).slice(0, 2)
    const lowPreview = (product.lowVariantLabels || []).slice(0, 2)

    if (outPreview.length > 0 && lowPreview.length > 0) {
      return `Sin stock: ${outPreview.join(', ')}. Bajo stock: ${lowPreview.join(', ')}.`
    }

    if (outPreview.length > 0) {
      return `Sin stock: ${outPreview.join(', ')}${product.outVariantCount > outPreview.length ? '...' : ''}`
    }

    if (lowPreview.length > 0) {
      return `Bajo stock: ${lowPreview.join(', ')}${product.lowVariantCount > lowPreview.length ? '...' : ''}`
    }

    return 'Sin alertas'
  }

  // Reutiliza el exportador compartido del admin y deja fijo el mismo contrato visual de inventario.
  function buildInventoryExportColumns() {
    return [
      {
        header: 'Vista',
        includeInExcel: false,
        pdfImage: (product) => product.rawImage || product.image,
        fallbackType: 'product',
        pdfWidth: 18,
        pdfImageSize: 12,
      },
      { header: 'Producto', value: (product) => product.name },
      { header: 'Variantes', value: (product) => Number(product.variantCount || 0), excelType: 'number', align: 'center', width: 12 },
      { header: 'SKU', value: (product) => Number(product.skuCount || 0), excelType: 'number', align: 'center', width: 12 },
      { header: 'Resumen', value: (product) => inventorySummaryAlert(product), width: 32 },
      { header: 'Stock total', value: (product) => Number(product.totalStock || 0), excelType: 'number', align: 'center', width: 12 },
      { header: 'Estado', value: (product) => productStatusLabel(product), width: 16 },
    ]
  }

  function exportInventory(format) {
    return exportData({
      format,
      fileBaseName: 'inventario-admin',
      sheetName: 'Inventario',
      title: 'Inventario',
      subtitle: 'Resumen exportado desde el monitoreo administrativo de inventario.',
      columns: buildInventoryExportColumns(),
      rows: filteredProducts.value,
      landscape: true,
      emptyMessage: 'No hay productos de inventario para exportar.',
    })
  }

  function stockTextClass(stock, source = null) {
    return `inventory-stock inventory-stock--${stockStatus(stock, source)}`
  }

  function productStockTextClass(product) {
    if (!product) {
      return stockTextClass(0)
    }

    if (product.outVariantCount > 0) {
      return 'inventory-stock inventory-stock--out'
    }

    if (product.lowVariantCount > 0) {
      return 'inventory-stock inventory-stock--low'
    }

    return 'inventory-stock inventory-stock--active'
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
      low_stock_threshold: resolveInventoryThreshold(item),
      inventory_status: resolveInventoryStatus(item.stock || item.quantity || 0, item),
      sku: item.sku || '',
      rawImage: item.image || item.primary_image || item.product_image || item.imagen || null,
      image: resolveMediaUrl(item.image || item.primary_image || item.product_image || item.imagen || null, 'product'),
    }
  }

  function buildVariantLabel(variant) {
    return formatInventoryVariantLabel(variant)
  }

  function resolveHistoryVariant(entry) {
    const rows = selectedProductDetail.value?.variantRows || []
    if (!rows.length) return null

    const toValidId = (value) => {
      const parsed = Number(value)
      return Number.isFinite(parsed) && parsed > 0 ? parsed : null
    }

    const variantIds = [
      entry.size_variant_id,
      entry.variant_id,
      entry.source_variant_id,
      entry.target_variant_id,
      entry.size_variant?.id,
      entry.variant?.id,
    ]
      .map(toValidId)
      .filter(Boolean)

    if (variantIds.length) {
      const byVariantId = rows.find((row) => variantIds.includes(Number(row.id)))
      if (byVariantId) return byVariantId
    }

    const colorVariantIds = [
      entry.color_variant_id,
      entry.source_color_variant_id,
      entry.target_color_variant_id,
    ]
      .map(toValidId)
      .filter(Boolean)

    if (colorVariantIds.length) {
      const byColorVariantId = rows.find((row) => colorVariantIds.includes(Number(row.color_variant_id)))
      if (byColorVariantId) return byColorVariantId
    }

    const sku = String(entry.sku || entry.variant_sku || '').trim()
    if (sku) {
      const bySku = rows.find((row) => String(row.sku || '').trim() === sku)
      if (bySku) return bySku
    }

    return null
  }

  function buildHistoryVariantLabel(entry) {
    const relatedVariant = resolveHistoryVariant(entry)
    const color = String(entry.color_name || entry.color || relatedVariant?.color_name || '').trim() || 'Sin color'
    const size = String(entry.size_label || entry.size_name || relatedVariant?.size_name || relatedVariant?.size_label || '').trim() || 'Sin talla'
    return `${color} / ${size}`
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
    return Number.isNaN(date.getTime())
      ? 'Sin fecha'
      : date.toLocaleString('es-CO', {
        timeZone: 'America/Bogota',
        year: 'numeric',
        month: 'numeric',
        day: 'numeric',
        hour: 'numeric',
        minute: '2-digit',
        second: '2-digit',
        hour12: true,
      })
  }

  function normalizeProductDetail(productId, payload) {
    const product = payload.product || {}
    const variants = Array.isArray(payload.variants) ? payload.variants : []
    const variantRows = []

    variants.forEach((variant) => {
      const rows = Array.isArray(variant.size_variants) ? variant.size_variants : []
      rows.forEach((row) => {
        const quantity = Number(row.quantity || row.stock || 0)
        const lowStockThreshold = resolveInventoryThreshold(
          row.low_stock_threshold
          ?? row.lowStockThreshold
          ?? selectedProductSummary.value?.lowStockThreshold,
        )

        variantRows.push({
          ...row,
          id: Number(row.id),
          product_id: productId,
          color_variant_id: Number(row.color_variant_id || variant.id || 0),
          color_name: row.color_name || variant.color_name || 'Sin color',
          size_name: row.size_name || row.size_label || 'Sin talla',
          size_label: row.size_name || row.size_label || 'Sin talla',
          quantity,
          low_stock_threshold: lowStockThreshold,
          inventory_status: resolveInventoryStatus(quantity, { low_stock_threshold: lowStockThreshold }),
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
      lowStockCount: variantRows.filter((row) => resolveInventoryStatus(row.quantity, row) === 'low').length,
      outOfStockCount: variantRows.filter((row) => resolveInventoryStatus(row.quantity, row) === 'out').length,
    }
  }

  function sanitizeRouteNumber(value) {
    const parsed = Number(value)
    return Number.isFinite(parsed) && parsed > 0 ? parsed : null
  }

  async function clearInventoryTargetQuery() {
    const currentQuery = { ...route.query }
    delete currentQuery.productId
    delete currentQuery.variantId
    delete currentQuery.action

    await router.replace({
      path: route.path,
      query: currentQuery,
    })
  }

  // Mantiene el deep-link actual del inventario para abrir detalle/ajuste/transferencia desde query params.
  async function maybeHandleInventoryTargetFromRoute() {
    const productId = sanitizeRouteNumber(route.query.productId)
    const variantId = sanitizeRouteNumber(route.query.variantId)
    const action = String(route.query.action || '').trim().toLowerCase()

    if (!productId || !variantId) {
      handledRouteTarget.value = ''
      return
    }

    const targetToken = `${productId}:${variantId}:${action || 'detail'}`
    if (handledRouteTarget.value === targetToken) {
      return
    }

    const product = groupedProducts.value.find((item) => {
      if (Number(item.id) === productId) {
        return true
      }

      return item.variants.some((variant) => Number(variant.id) === variantId)
    })

    if (!product) {
      return
    }

    handledRouteTarget.value = targetToken
    showDetailModal.value = true
    await loadProductDetail(product)

    const targetVariant = selectedProductDetail.value?.variantRows?.find((variant) => Number(variant.id) === variantId) || null
    if (targetVariant) {
      if (action === 'transfer') {
        openTransferModal(targetVariant)
      } else {
        openAdjustModal(targetVariant)
      }
    }

    await clearInventoryTargetQuery()
  }

  function resetAdjustForm() {
    adjustForm.action = 'set'
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

  function validateStockAdjustmentQuantity() {
    if (adjustForm.action !== 'set') {
      return validatePositiveInteger(adjustForm.quantity)
    }

    const normalized = String(adjustForm.quantity ?? '').trim()
    if (!/^(0|[1-9]\d*)$/.test(normalized)) {
      return {
        valid: false,
        value: null,
        message: 'La cantidad debe ser un numero entero mayor o igual a 0.',
      }
    }

    return { valid: true, value: Number(normalized), message: '' }
  }

  function validateAdjustField(field) {
    if (field === 'quantity') {
      adjustErrors.quantity = validateStockAdjustmentQuantity().message
    }
  }

  function validateTransferField(field) {
    if (field === 'target_variant_id') {
      transferErrors.target_variant_id = transferForm.target_variant_id ? '' : 'Debes seleccionar una variante destino.'
    }

    if (field === 'quantity') {
      const result = validatePositiveInteger(transferForm.quantity)
      const quantity = result.value
      const available = Number(selectedVariant.value?.quantity || 0)
      transferErrors.quantity = result.valid && quantity <= available
        ? ''
        : (result.message || 'La cantidad no puede exceder el stock disponible.')
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

  function closeDetailModal() {
    showDetailModal.value = false
    showAdjustModal.value = false
    showTransferModal.value = false
    selectedProductSummary.value = null
    selectedProductDetail.value = null
    selectedProductHistory.value = []
    selectedVariant.value = null
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

  async function flushRealtimeInventorySync() {
    const reloadDetail = pendingRealtimeInventorySync.reloadDetail
    const reloadHistory = pendingRealtimeInventorySync.reloadHistory

    pendingRealtimeInventorySync.reloadDetail = false
    pendingRealtimeInventorySync.reloadHistory = false

    await loadInventory()

    if (selectedProductSummary.value) {
      const updatedSummary = groupedProducts.value.find((product) => Number(product.id) === Number(selectedProductSummary.value?.id || 0))
      if (updatedSummary) {
        selectedProductSummary.value = updatedSummary
      }
    }

    if (reloadDetail && selectedProductSummary.value) {
      await loadProductDetail(selectedProductSummary.value)
      return
    }

    if (reloadHistory && showDetailModal.value) {
      await reloadDetailHistory()
    }
  }

  function scheduleRealtimeInventorySync(options = {}) {
    pendingRealtimeInventorySync.reloadDetail = pendingRealtimeInventorySync.reloadDetail || Boolean(options.reloadDetail)
    pendingRealtimeInventorySync.reloadHistory = pendingRealtimeInventorySync.reloadHistory || Boolean(options.reloadHistory)

    if (realtimeInventorySyncTimerId) {
      window.clearTimeout(realtimeInventorySyncTimerId)
    }

    realtimeInventorySyncTimerId = window.setTimeout(async () => {
      realtimeInventorySyncTimerId = null

      if (realtimeInventorySyncInFlight) {
        scheduleRealtimeInventorySync(options)
        return
      }

      realtimeInventorySyncInFlight = true

      try {
        await flushRealtimeInventorySync()
      } catch {
        showSnackbar({ type: 'error', message: 'No se pudo sincronizar el inventario en tiempo real' })
      } finally {
        realtimeInventorySyncInFlight = false
      }
    }, 280)
  }

  async function submitAdjust() {
    if (stockSubmitting.value) return
    validateAdjustField('quantity')
    if (adjustErrors.quantity || !selectedVariant.value?.id) return
    const quantity = validateStockAdjustmentQuantity().value

    stockSubmitting.value = true

    try {
      await catalogHttp.patch(`/admin/inventory/${selectedVariant.value.id}/stock`, {
        action: adjustForm.action,
        quantity,
        reason: adjustForm.reason?.trim() || null,
      })

      showSnackbar({ type: 'success', message: 'Stock actualizado' })
      closeAdjustModal()
      await refreshAfterStockChange()
    } catch (error) {
      showSnackbar({ type: 'error', message: error?.response?.data?.message || 'Error ajustando stock' })
    } finally {
      stockSubmitting.value = false
    }
  }

  async function submitTransfer() {
    if (stockSubmitting.value) return
    validateTransferField('target_variant_id')
    validateTransferField('quantity')
    if (transferErrors.target_variant_id || transferErrors.quantity || !selectedVariant.value?.id) return

    stockSubmitting.value = true

    try {
      await catalogHttp.post('/admin/inventory/transfer', {
        source_variant_id: Number(transferForm.source_variant_id),
        target_variant_id: Number(transferForm.target_variant_id),
        quantity: validatePositiveInteger(transferForm.quantity).value,
        reason: transferForm.reason?.trim() || null,
      })

      showSnackbar({ type: 'success', message: 'Transferencia aplicada' })
      closeTransferModal()
      await refreshAfterStockChange()
    } catch (error) {
      showSnackbar({ type: 'error', message: error?.response?.data?.message || 'Error transfiriendo stock' })
    } finally {
      stockSubmitting.value = false
    }
  }

  watch(
    () => route.fullPath,
    async () => {
      if (!route.query.productId || !route.query.variantId) {
        handledRouteTarget.value = ''
        return
      }

      await maybeHandleInventoryTargetFromRoute()
    },
  )

  onMounted(async () => {
    await loadInventory()
    await maybeHandleInventoryTargetFromRoute()
  })

  // Reutiliza la suscripción global de stock en tiempo real para refrescar solo cuando las variantes abiertas cambian.
  useStockRealtime((message) => {
    const hasRelevantVariant = message.variantIds.some((variantId) => inventoryVariantIds.value.includes(variantId))
    if (!hasRelevantVariant) {
      return
    }

    const affectsOpenDetail = message.variantIds.some((variantId) => selectedDetailVariantIds.value.includes(variantId))
    scheduleRealtimeInventorySync({
      reloadDetail: affectsOpenDetail,
      reloadHistory: message.historyUpdated && affectsOpenDetail,
    })
  })

  onUnmounted(() => {
    if (realtimeInventorySyncTimerId) {
      window.clearTimeout(realtimeInventorySyncTimerId)
    }
  })

  return {
    activeTab,
    adjustErrors,
    adjustForm,
    detailLoading,
    detailModalTitle,
    exportInventory,
    exportingFormat,
    filteredProducts,
    formatDateTime,
    formatOperation,
    groupedProducts,
    historyLoading,
    inventoryStats,
    inventorySummaryAlert,
    loading,
    openAdjustModal,
    openDetail,
    openTransferModal,
    pagination,
    productStatusLabel,
    productStockTextClass,
    reloadDetailHistory,
    search,
    selectedProductDetail,
    selectedProductHistory,
    selectedVariantLabel,
    showAdjustModal,
    showDetailModal,
    showTransferModal,
    statusClass,
    statusLabel,
    stockStatus,
    stockSubmitting,
    stockTextClass,
    submitAdjust,
    submitTransfer,
    transferErrors,
    transferForm,
    transferSourceLabel,
    transferTargets,
    buildHistoryVariantLabel,
    buildVariantLabel,
    closeAdjustModal,
    closeDetailModal,
    closeTransferModal,
    validateAdjustField,
    validateTransferField,
  }
}
