import { computed, onBeforeUnmount, onMounted, ref } from 'vue'
import { catalogHttp } from '../../../services/http'
import { useAlertSystem } from '../../../composables/useAlertSystem'
import { useSnackbarSystem } from '../../../composables/useSnackbarSystem'
import { getFallbackMediaUrl, handleMediaError, resolveMediaUrl } from '../../../utils/media'
import { useAdminDataExport } from './useAdminDataExport'
import { useAdminPagination } from './useAdminPagination'

// =====================================================
// Constantes internas
// =====================================================
const PRODUCT_FALLBACK_IMAGE = getFallbackMediaUrl('product')

// =====================================================
// Helpers internos
// =====================================================
function normalizeGender(value) {
  return String(value || '')
    .normalize('NFD')
    .replace(/[\u0300-\u036f]/g, '')
    .toLowerCase()
}

function extractColorName(raw) {
  if (!raw) return ''
  if (typeof raw === 'string') return raw.trim()

  if (typeof raw === 'object') {
    return String(raw.name || raw.nombre || raw.color_name || raw.label || '').trim()
  }

  return ''
}

function formatCurrency(value) {
  return `$${Number(value || 0).toLocaleString('es-CO')}`
}

function genderLabel(value) {
  return {
    nino: 'Niño',
    nina: 'Niña',
    bebe: 'Bebé',
    unisex: 'Unisex',
  }[normalizeGender(value)] || 'Sin género'
}

function resolveProductImage(product) {
  return resolveMediaUrl(
    product.primary_image || product.image || product.product_image || product.imagen || product.image_url,
    'product',
  )
}

function normalizeVariant(rawVariant) {
  const colorName = extractColorName(rawVariant.color_name || rawVariant.color || rawVariant.color_label || rawVariant.variant_color)

  return {
    ...rawVariant,
    id: rawVariant.id || `${rawVariant.color_name || rawVariant.color || 'general'}-${rawVariant.size_name || rawVariant.size || 'unica'}-${rawVariant.price || rawVariant.precio || 0}`,
    color_name: colorName,
    color_variant_id: Number(rawVariant.color_variant_id || rawVariant.variant_id || rawVariant.product_color_variant_id || 0) || null,
    hex_code: rawVariant.hex_code || rawVariant.color_hex || rawVariant.hex || null,
    size_name: rawVariant.size_name || rawVariant.size || '',
    price: Number(rawVariant.price ?? rawVariant.precio ?? 0),
    quantity: Number(rawVariant.quantity ?? rawVariant.stock ?? rawVariant.cantidad ?? 0),
    is_active: typeof rawVariant.is_active === 'boolean' ? rawVariant.is_active : Boolean(Number(rawVariant.is_active ?? rawVariant.activo ?? 1)),
  }
}

function buildQuickImages(product, imageRows) {
  const normalizedImages = imageRows
    .map((image) => ({
      ...image,
      color_name: extractColorName(image.color_name || image.color || image.color_label || image.variant_color) || 'General',
      hex_code: image.hex_code || image.color_hex || image.hex || null,
      resolvedUrl: resolveMediaUrl(image.url || image.image_path || image.image, 'product'),
      is_primary: Boolean(Number(image.is_primary ?? 0)),
    }))
    .filter((image) => image.resolvedUrl && image.resolvedUrl !== PRODUCT_FALLBACK_IMAGE)

  const dedupedByUrl = []
  const byUrl = new Map()

  normalizedImages.forEach((image) => {
    const key = image.resolvedUrl
    const existing = byUrl.get(key)

    if (!existing) {
      byUrl.set(key, image)
      dedupedByUrl.push(image)
      return
    }

    if (!existing.is_primary && image.is_primary) existing.is_primary = true

    if ((existing.color_name === 'General' || !existing.color_name) && image.color_name && image.color_name !== 'General') {
      existing.color_name = image.color_name
    }

    if (!existing.hex_code && image.hex_code) existing.hex_code = image.hex_code
    if (!existing.alt_text && image.alt_text) existing.alt_text = image.alt_text
  })

  const productImageUrl = product.rawImage ? resolveMediaUrl(product.rawImage, 'product') : ''

  if (productImageUrl && !dedupedByUrl.some((image) => image.resolvedUrl === productImageUrl)) {
    dedupedByUrl.unshift({
      id: 'primary-image',
      color_name: 'General',
      resolvedUrl: productImageUrl,
      is_primary: true,
      alt_text: product.name,
    })
  }

  return dedupedByUrl
}

function normalizeProduct(rawProduct) {
  return {
    ...rawProduct,
    id: Number(rawProduct.id),
    name: rawProduct.name || rawProduct.nombre || 'Sin nombre',
    price: Number(rawProduct.price ?? rawProduct.precio ?? 0),
    min_price: Number(rawProduct.min_price ?? rawProduct.price ?? rawProduct.precio ?? 0),
    max_price: Number(rawProduct.max_price ?? rawProduct.price ?? rawProduct.precio ?? 0),
    variant_count: Number(rawProduct.variant_count ?? rawProduct.variants_count ?? 0),
    category_name: rawProduct.category_name || rawProduct.categoria_nombre || 'Sin categoría',
    stock: Number(rawProduct.stock ?? rawProduct.total_stock ?? 0),
    description: rawProduct.description || rawProduct.descripcion || '',
    gender: rawProduct.gender || rawProduct.genero || '',
    rawImage: rawProduct.primary_image || rawProduct.image || rawProduct.product_image || rawProduct.imagen || rawProduct.image_url || null,
    image: resolveProductImage(rawProduct),
    is_active: typeof rawProduct.is_active === 'boolean' ? rawProduct.is_active : Boolean(Number(rawProduct.activo ?? 1)),
  }
}

export function useAdminProducts() {
  const { showAlert } = useAlertSystem()
  const { showSnackbar } = useSnackbarSystem()
  const { exportData, exportingFormat } = useAdminDataExport()

  // =====================================================
  // Estado principal
  // =====================================================
  const products = ref([])
  const categories = ref([])
  const loading = ref(true)
  const search = ref('')
  const selectedProducts = ref([])

  // =====================================================
  // Filtros y paginación
  // =====================================================
  const categoryFilter = ref('')
  const statusFilter = ref('')
  const genderFilter = ref('')
  const sortOrder = ref('newest')

  // =====================================================
  // Gestión de detalle rápido
  // =====================================================
  const showQuickView = ref(false)
  const quickViewLoading = ref(false)
  const quickProduct = ref(null)
  const quickImages = ref([])
  const mainQuickImage = ref('')
  const quickSizeVariants = ref([])
  const quickTotalStock = ref(0)
  const quickMinPrice = ref(0)
  const quickMaxPrice = ref(0)
  const activeColorFilter = ref('General')
  const thumbGalleryRef = ref(null)

  // =====================================================
  // Gestión de zoom
  // =====================================================
  const showZoom = ref(false)
  const zoomImage = ref('')
  const zoomTitle = ref('')

  // =====================================================
  // Datos derivados
  // =====================================================
  const colorFilters = computed(() => {
    const filters = [{ color: 'General', label: 'Principal', hex: null }]
    const seen = new Set(['General'])

    quickImages.value.forEach((image) => {
      if (image.color_name && image.color_name !== 'General' && !seen.has(image.color_name)) {
        seen.add(image.color_name)
        filters.push({ color: image.color_name, label: image.color_name, hex: image.hex_code || '#CCC' })
      }
    })

    quickSizeVariants.value.forEach((variant) => {
      const color = variant.color_name || 'General'
      if (color !== 'General' && !seen.has(color)) {
        seen.add(color)
        filters.push({ color, label: color, hex: '#D0D7E2' })
      }
    })

    return filters
  })

  const visibleThumbs = computed(() => {
    if (activeColorFilter.value === 'General') {
      const generalImages = quickImages.value.filter((image) => !image.color_name || image.color_name === 'General' || image.is_primary)
      return generalImages.length > 0 ? generalImages : quickImages.value
    }

    const imagesByColor = quickImages.value.filter((image) => image.color_name === activeColorFilter.value)
    return imagesByColor.length > 0 ? imagesByColor : quickImages.value
  })

  const showThumbArrows = computed(() => visibleThumbs.value.length > 4)
  const quickColors = computed(() => {
    const fromVariants = quickSizeVariants.value.map((variant) => variant.color_name).filter(Boolean)
    const fromFilters = colorFilters.value
      .map((filter) => filter.color)
      .filter((color) => color && color !== 'General')

    return [...new Set([...fromVariants, ...fromFilters])]
  })
  const quickSizes = computed(() => [...new Set(quickSizeVariants.value.map((variant) => variant.size_name).filter(Boolean))])
  const quickVariantCount = computed(() => quickSizeVariants.value.length || Number(quickProduct.value?.variant_count || 0))
  const showVariantTable = computed(() => quickSizeVariants.value.length > 1 || quickColors.value.length > 1 || quickSizes.value.length > 1)

  const activeFilterCount = computed(() => {
    let count = 0
    if (categoryFilter.value) count += 1
    if (statusFilter.value) count += 1
    if (genderFilter.value) count += 1
    if (sortOrder.value !== 'newest') count += 1
    return count
  })

  const filteredProducts = computed(() => {
    let rows = products.value

    if (statusFilter.value) {
      rows = rows.filter((product) => (statusFilter.value === 'active' ? product.is_active : !product.is_active))
    }

    if (genderFilter.value) {
      rows = rows.filter((product) => normalizeGender(product.gender) === genderFilter.value)
    }

    const sortedRows = [...rows]

    switch (sortOrder.value) {
      case 'name_asc':
        sortedRows.sort((a, b) => (a.name || '').localeCompare(b.name || ''))
        break
      case 'name_desc':
        sortedRows.sort((a, b) => (b.name || '').localeCompare(a.name || ''))
        break
      case 'price_asc':
        sortedRows.sort((a, b) => (a.min_price || a.price || 0) - (b.min_price || b.price || 0))
        break
      case 'price_desc':
        sortedRows.sort((a, b) => (b.max_price || b.price || 0) - (a.max_price || a.price || 0))
        break
      case 'stock_asc':
        sortedRows.sort((a, b) => a.stock - b.stock)
        break
      case 'stock_desc':
        sortedRows.sort((a, b) => b.stock - a.stock)
        break
      default:
        break
    }

    return sortedRows
  })

  const pagination = useAdminPagination(filteredProducts, {
    initialPageSize: 12,
    pageSizeOptions: [12, 24, 48],
  })

  // =====================================================
  // Selección
  // =====================================================
  function toggleSelection({ id, checked }) {
    if (checked) {
      selectedProducts.value = [...new Set([...selectedProducts.value, id])]
      return
    }

    selectedProducts.value = selectedProducts.value.filter((selectedId) => selectedId !== id)
  }

  // =====================================================
  // Carga y actualización de datos
  // =====================================================
  async function loadProducts() {
    loading.value = true

    try {
      const adminParams = { limit: 200 }
      if (search.value) adminParams.search = search.value
      if (categoryFilter.value) adminParams.category = categoryFilter.value
      if (statusFilter.value) adminParams.status = statusFilter.value

      const response = await catalogHttp.get('/admin/products', { params: adminParams })
      const payload = response.data?.data || response.data || []
      const rows = Array.isArray(payload) ? payload : (payload.data || [])

      products.value = rows.map(normalizeProduct)
      selectedProducts.value = selectedProducts.value.filter((id) => products.value.some((product) => product.id === id))
    } catch {
      showSnackbar({ type: 'error', message: 'Error cargando productos' })
    } finally {
      loading.value = false
    }
  }

  async function loadCategories() {
    try {
      const response = await catalogHttp.get('/admin/categories')
      const payload = response.data?.data || response.data || []
      categories.value = Array.isArray(payload) ? payload : (payload.data || [])
    } catch {
      try {
        const fallbackResponse = await catalogHttp.get('/categories')
        categories.value = fallbackResponse.data?.data || fallbackResponse.data || []
      } catch {
        categories.value = []
      }
    }
  }

  // =====================================================
  // Filtros y refresco
  // =====================================================
  function applyFilters() {
    pagination.currentPage = 1
    loadProducts()
  }

  function clearAllFilters() {
    search.value = ''
    categoryFilter.value = ''
    statusFilter.value = ''
    genderFilter.value = ''
    sortOrder.value = 'newest'
    selectedProducts.value = []
    pagination.currentPage = 1
    loadProducts()
  }

  let debounceTimer = null

  function debouncedLoad() {
    clearTimeout(debounceTimer)
    debounceTimer = setTimeout(() => {
      pagination.currentPage = 1
      loadProducts()
    }, 400)
  }

  // =====================================================
  // Gestión de detalle rápido
  // =====================================================
  function setColorFilter(color) {
    activeColorFilter.value = color
    const filtered = visibleThumbs.value

    if (filtered.length > 0) {
      mainQuickImage.value = filtered[0].resolvedUrl
    }
  }

  function scrollThumbs(direction) {
    if (!thumbGalleryRef.value) return

    thumbGalleryRef.value.scrollBy({ left: direction * 150, behavior: 'smooth' })
  }

  async function openQuickView(product) {
    showQuickView.value = true
    quickViewLoading.value = true
    quickProduct.value = product
    activeColorFilter.value = 'General'

    try {
      const response = await catalogHttp.get(`/admin/products/${product.id}`)
      const payload = response.data?.data || {}
      const productData = payload.product || product
      const variantsPayload = Array.isArray(payload.variants) ? payload.variants : []
      const variantColorMap = new Map()
      const variantHexMap = new Map()

      variantsPayload.forEach((variant) => {
        const variantId = Number(variant?.id || variant?.color_variant_id || 0)
        const colorName = extractColorName(variant?.color_name || variant?.color || variant?.name)
        const hexCode = variant?.hex_code || variant?.color_hex || null

        if (variantId > 0) {
          if (colorName) variantColorMap.set(variantId, colorName)
          if (hexCode) variantHexMap.set(variantId, hexCode)
        }
      })

      const sizeVariantsRaw = []

      if (Array.isArray(payload.size_variants) && payload.size_variants.length > 0) {
        sizeVariantsRaw.push(...payload.size_variants)
      } else {
        variantsPayload.forEach((variant) => {
          if (!Array.isArray(variant?.size_variants)) return

          variant.size_variants.forEach((sizeVariant) => {
            sizeVariantsRaw.push({
              ...sizeVariant,
              color_variant_id: sizeVariant.color_variant_id || variant.id || variant.color_variant_id,
              color_name: sizeVariant.color_name || variant.color_name,
              hex_code: sizeVariant.hex_code || variant.hex_code || variant.color_hex,
            })
          })
        })
      }

      const sizeVariants = sizeVariantsRaw.map((variant) => {
        const normalized = normalizeVariant(variant)
        const variantId = Number(normalized.color_variant_id || 0)

        if (!normalized.color_name && variantId > 0 && variantColorMap.has(variantId)) {
          normalized.color_name = variantColorMap.get(variantId)
        }

        if (!normalized.hex_code && variantId > 0 && variantHexMap.has(variantId)) {
          normalized.hex_code = variantHexMap.get(variantId)
        }

        return normalized
      })

      const imageRows = []
      const hasVariantImages = Array.isArray(payload.variant_images) && payload.variant_images.length > 0
      const hasProductImages = Array.isArray(payload.images) && payload.images.length > 0

      if (hasVariantImages) {
        imageRows.push(...payload.variant_images)
      } else if (hasProductImages) {
        imageRows.push(...payload.images)
      } else {
        variantsPayload.forEach((variant) => {
          if (!Array.isArray(variant?.images)) return

          variant.images.forEach((image) => {
            imageRows.push({
              ...image,
              color_variant_id: image.color_variant_id || variant.id || variant.color_variant_id,
              color_name: image.color_name || variant.color_name,
              hex_code: image.hex_code || variant.hex_code || variant.color_hex,
            })
          })
        })
      }

      const enrichedImageRows = imageRows.map((image) => {
        const variantId = Number(image.color_variant_id || 0)
        const colorName = extractColorName(image.color_name || image.color || image.color_label)

        return {
          ...image,
          color_name: colorName || (variantId > 0 ? (variantColorMap.get(variantId) || '') : ''),
          hex_code: image.hex_code || image.color_hex || (variantId > 0 ? (variantHexMap.get(variantId) || null) : null),
        }
      })

      quickProduct.value = normalizeProduct(productData)
      quickSizeVariants.value = sizeVariants
      quickTotalStock.value = Number(payload.total_stock ?? quickProduct.value.stock ?? sizeVariants.reduce((total, variant) => total + variant.quantity, 0))
      quickMinPrice.value = Number(payload.min_price ?? quickProduct.value.min_price ?? quickProduct.value.price ?? 0)
      quickMaxPrice.value = Number(payload.max_price ?? quickProduct.value.max_price ?? quickProduct.value.price ?? 0)
      quickImages.value = buildQuickImages(quickProduct.value, enrichedImageRows)

      if (quickImages.value.length === 0) {
        const fallbackSafeImage = quickProduct.value.rawImage
          ? resolveMediaUrl(quickProduct.value.rawImage, 'product')
          : quickProduct.value.image

        quickImages.value = [{ id: 0, resolvedUrl: fallbackSafeImage, color_name: 'General', is_primary: true }]
      }

      const preferredImage = quickImages.value.find((image) => image.is_primary && image.resolvedUrl !== PRODUCT_FALLBACK_IMAGE)
        || quickImages.value.find((image) => image.resolvedUrl !== PRODUCT_FALLBACK_IMAGE)
        || quickImages.value[0]

      mainQuickImage.value = preferredImage?.resolvedUrl || quickProduct.value.image
    } catch {
      quickProduct.value = normalizeProduct(product)
      quickImages.value = [{ id: 0, resolvedUrl: quickProduct.value.image, color_name: 'General', is_primary: true }]
      mainQuickImage.value = quickProduct.value.image
      quickSizeVariants.value = []
      quickTotalStock.value = Number(quickProduct.value.stock || 0)
      quickMinPrice.value = Number(quickProduct.value.min_price || quickProduct.value.price || 0)
      quickMaxPrice.value = Number(quickProduct.value.max_price || quickProduct.value.price || 0)
      showSnackbar({ type: 'warning', message: 'No fue posible cargar todos los detalles del producto.' })
    } finally {
      quickViewLoading.value = false
    }
  }

  function closeQuickView() {
    showQuickView.value = false
    quickViewLoading.value = false
    quickProduct.value = null
    quickImages.value = []
    quickSizeVariants.value = []
    quickTotalStock.value = 0
    quickMinPrice.value = 0
    quickMaxPrice.value = 0
    mainQuickImage.value = ''
    activeColorFilter.value = 'General'
  }

  // =====================================================
  // Gestión de zoom
  // =====================================================
  function openZoom(image, title) {
    zoomImage.value = image
    zoomTitle.value = title
    showZoom.value = true
  }

  function closeZoom() {
    showZoom.value = false
    zoomImage.value = ''
    zoomTitle.value = ''
  }

  // =====================================================
  // Acciones administrativas
  // =====================================================
  function confirmToggleStatus(product) {
    const action = product.is_active ? 'desactivar' : 'activar'
    const actionLabel = product.is_active ? 'Desactivar' : 'Activar'

    showAlert({
      type: product.is_active ? 'warning' : 'success',
      title: `${actionLabel} producto`,
      message: `¿Estás seguro de ${action} "${product.name}"?`,
      actions: [
        { text: 'Cancelar', style: 'secondary' },
        {
          text: actionLabel,
          style: product.is_active ? 'warning' : 'success',
          callback: () => toggleProductStatus(product),
        },
      ],
    })
  }

  async function toggleProductStatus(product) {
    try {
      const newStatus = !product.is_active
      await catalogHttp.patch(`/admin/products/${product.id}/status`, { is_active: newStatus })
      showSnackbar({ type: 'success', message: `Producto ${newStatus ? 'activado' : 'desactivado'}` })
      await loadProducts()
    } catch {
      showSnackbar({ type: 'error', message: 'Error al cambiar el estado del producto' })
    }
  }

  // =====================================================
  // Exportación
  // =====================================================
  function buildProductsExportColumns() {
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
      { header: 'Categoría', value: (product) => product.category_name || 'Sin categoría' },
      { header: 'Estado', value: (product) => (product.is_active ? 'Activo' : 'Inactivo') },
      { header: 'Variantes', value: (product) => Number(product.variant_count || 0), excelType: 'number', align: 'center' },
      { header: 'Stock total', value: (product) => Number(product.stock || 0), excelType: 'number', align: 'center' },
      {
        header: 'Precio desde',
        value: (product) => formatCurrency(product.min_price || product.price || 0),
        excelValue: (product) => Number(product.min_price || product.price || 0),
        excelType: 'currency',
        align: 'right',
        width: 16,
      },
      {
        header: 'Precio hasta',
        value: (product) => formatCurrency(product.max_price || product.price || 0),
        excelValue: (product) => Number(product.max_price || product.price || 0),
        excelType: 'currency',
        align: 'right',
        width: 16,
      },
      { header: 'Género', value: (product) => genderLabel(product.gender), width: 14 },
    ]
  }

  function exportProducts(format) {
    return exportData({
      format,
      fileBaseName: 'productos-admin',
      sheetName: 'Productos',
      title: 'Catálogo de productos',
      subtitle: 'Resumen exportado desde la gestión administrativa de productos.',
      columns: buildProductsExportColumns(),
      rows: filteredProducts.value,
      landscape: true,
      emptyMessage: 'No hay productos para exportar.',
    })
  }

  // =====================================================
  // Helpers visuales
  // =====================================================
  function onProductCardImageError({ event, imagePath }) {
    handleMediaError(event, imagePath, 'product')
  }

  function onZoomImageError(event, imagePath) {
    handleMediaError(event, imagePath, 'product')
  }

  // =====================================================
  // Ciclo de vida
  // =====================================================
  onMounted(() => {
    loadProducts()
    loadCategories()
  })

  onBeforeUnmount(() => {
    if (debounceTimer) {
      clearTimeout(debounceTimer)
    }
  })

  // =====================================================
  // API pública del composable
  // =====================================================
  return {
    activeColorFilter,
    activeFilterCount,
    applyFilters,
    categories,
    categoryFilter,
    clearAllFilters,
    closeQuickView,
    closeZoom,
    colorFilters,
    confirmToggleStatus,
    debouncedLoad,
    exportProducts,
    exportingFormat,
    filteredProducts,
    formatCurrency,
    genderFilter,
    loading,
    mainQuickImage,
    onProductCardImageError,
    onZoomImageError,
    openQuickView,
    openZoom,
    pagination,
    products,
    quickColors,
    quickMaxPrice,
    quickMinPrice,
    quickProduct,
    quickSizes,
    quickSizeVariants,
    quickTotalStock,
    quickVariantCount,
    quickViewLoading,
    scrollThumbs,
    search,
    selectedProducts,
    setColorFilter,
    showQuickView,
    showThumbArrows,
    showVariantTable,
    showZoom,
    sortOrder,
    statusFilter,
    thumbGalleryRef,
    toggleSelection,
    visibleThumbs,
    zoomImage,
    zoomTitle,
  }
}
