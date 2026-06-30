import { computed, onMounted, reactive, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { catalogHttp } from '../../../services/http'
import { useSnackbarSystem } from '../../../composables/useSnackbarSystem'
import { handleMediaError, resolveMediaUrl } from '../../../utils/media'
import {
  formatCopPrice,
  normalizeCopInput,
  numericValidationMessages,
  validateCopPrice,
  validatePositiveInteger,
} from '../../../utils/numericValidation'
import { buildProductPayload, buildProductRequestBody } from '../utils/productFormPayload'
import {
  buildGeneratedProductSlug,
  shouldTreatExistingSlugAsManual,
  slugifyText,
} from '../utils/productSlug'
import {
  buildGeneratedSku,
  normalizeSkuValue,
  shouldTreatExistingSkuAsManual,
} from '../utils/productSku'

/**
 * Composable para el formulario de creación/edición de productos.
 * Centraliza estado del formulario, carga de catálogos (categorías, colecciones,
 * colores, tallas, imágenes), validaciones, generación de slugs/SKUs,
 * construcción de payload y gestión de imágenes.
 */
export function useAdminProductForm() {
  const route = useRoute()
  const router = useRouter()
  const { showSnackbar } = useSnackbarSystem()

  const isEditing = computed(() => Boolean(route.params.id))
  const saving = ref(false)
  const initialLoading = ref(false)
  const activeTab = ref('general')

  const categories = ref([])
  const collections = ref([])
  const colors = ref([])
  const sizes = ref([])

  const mainImageInput = ref(null)
  const mainImageFile = ref(null)
  const mainImagePreview = ref('')
  const variantImageInputs = ref({})

  const variantModalOpen = ref(false)
  const refundPolicyModalOpen = ref(false)
  const activeVariantKey = ref('')
  const selectedSizeId = ref('')
  const slugManuallyEdited = ref(false)

  let variantSeed = 0
  let sizeSeed = 0

  const form = reactive({
    name: '',
    slug: '',
    brand: '',
    gender: 'unisex',
    category_id: '',
    collection_id: '',
    collection: '',
    price: '',
    compare_price: '',
    material: '',
    description: '',
    care_instructions: '',
    main_image_path: '',
    is_featured: false,
    is_active: true,
    is_refundable: false,
    refund_days: '',
    variants: [],
  })

  const errors = reactive({
    name: '',
    price: '',
    compare_price: '',
    category_id: '',
    refund_days: '',
    variants: '',
  })

  const activeVariant = computed(() => form.variants.find((variant) => variant.key === activeVariantKey.value) || null)

  const availableSizesForActiveVariant = computed(() => {
    if (!activeVariant.value) return sizes.value
    const selectedIds = new Set(activeVariant.value.sizes.map((size) => Number(size.size_id)).filter(Boolean))
    return sizes.value.filter((size) => !selectedIds.has(Number(size.id)))
  })

  const totalSizeConfigurations = computed(() => form.variants.reduce((total, variant) => total + variant.sizes.length, 0))
  const totalStock = computed(() => form.variants.reduce(
    (total, variant) => total + variant.sizes.reduce((variantTotal, size) => variantTotal + Number(size.quantity || 0), 0),
    0,
  ))

  const canSaveProduct = computed(() => hasValidProductState())

  const generatedSlug = computed(() => buildGeneratedProductSlug({
    categorySource: categorySource(form.category_id),
    gender: form.gender,
    name: form.name,
  }))

  const skuGenerationSignature = computed(() => JSON.stringify({
    name: form.name,
    brand: form.brand,
    category_id: form.category_id,
    category_source: categorySource(form.category_id),
    gender: form.gender,
    colors: colors.value.map((color) => `${color.id}:${color.name}`),
    sizes: sizes.value.map((size) => `${size.id}:${size.name}`),
    variants: form.variants.map((variant) => ({
      key: variant.key,
      color_id: variant.color_id,
      sizes: variant.sizes.map((size) => ({
        key: size.key,
        size_id: size.size_id,
        manual: Boolean(size.sku_manually_edited),
      })),
    })),
  }))

  function nextVariantKey() {
    variantSeed += 1
    return `variant-${variantSeed}`
  }

  function nextSizeKey() {
    sizeSeed += 1
    return `size-${sizeSeed}`
  }

  function categoryOption(categoryId) {
    return categories.value.find((item) => Number(item.id) === Number(categoryId)) || null
  }

  function categorySource(categoryId) {
    const category = categoryOption(categoryId)
    return category?.slug || category?.name || ''
  }

  function normalizeBoolean(value, fallback = false) {
    if (typeof value === 'boolean') return value
    if (value === null || value === undefined || value === '') return fallback
    return Boolean(Number(value)) || value === 'true'
  }

  function createSizeRow(partial = {}) {
    return {
      id: partial.id || null,
      key: partial.key || nextSizeKey(),
      size_id: partial.size_id ? Number(partial.size_id) : '',
      price: partial.price ?? form.price ?? '',
      compare_price: partial.compare_price ?? form.compare_price ?? '',
      quantity: partial.quantity ?? '',
      sku: partial.sku || '',
      sku_manually_edited: partial.sku_manually_edited ?? shouldTreatExistingSkuAsManual(partial.sku),
      barcode: partial.barcode || '',
      is_active: normalizeBoolean(partial.is_active, true),
      errors: {
        price: '',
        compare_price: '',
        quantity: '',
      },
    }
  }

  function createVariant(partial = {}) {
    const images = []

    if (Array.isArray(partial.images) && partial.images.length > 0) {
      for (const img of partial.images) {
        const path = img.image_path || img.url || img.path || ''
        images.push({
          id: img.id || null,
          path,
          preview: path ? resolveMediaUrl(path, 'product') : '',
          file: null,
          is_primary: normalizeBoolean(img.is_primary, images.length === 0),
        })
      }
    } else if (partial.image_path) {
      const path = partial.image_path
      images.push({ id: null, path, preview: resolveMediaUrl(path, 'product'), file: null, is_primary: true })
    }

    return {
      id: partial.id || null,
      key: partial.key || nextVariantKey(),
      color_id: partial.color_id ? Number(partial.color_id) : '',
      is_default: normalizeBoolean(partial.is_default, form.variants.length === 0),
      images,
      sizes: (partial.size_variants || partial.sizes || []).map((size) => createSizeRow(size)),
    }
  }

  function setMainImageInputRef(element) {
    mainImageInput.value = element || null
  }

  function setVariantImageInputRef(key, element) {
    if (element) {
      variantImageInputs.value[key] = element
      return
    }

    delete variantImageInputs.value[key]
  }

  function triggerMainImagePicker() {
    mainImageInput.value?.click()
  }

  function triggerVariantImagePicker(key) {
    variantImageInputs.value[key]?.click()
  }

  function revokePreview(url) {
    if (typeof url === 'string' && url.startsWith('blob:')) {
      URL.revokeObjectURL(url)
    }
  }

  function handleMainImageUpload(event) {
    const file = event.target.files?.[0]
    if (!file) return

    revokePreview(mainImagePreview.value)
    mainImageFile.value = file
    mainImagePreview.value = URL.createObjectURL(file)
  }

  function removeMainImage() {
    revokePreview(mainImagePreview.value)
    form.main_image_path = ''
    mainImageFile.value = null
    mainImagePreview.value = ''
    if (mainImageInput.value) {
      mainImageInput.value.value = ''
    }
  }

  function handleVariantImageUpload(key, event) {
    const files = Array.from(event.target.files || [])
    if (!files.length) return

    const variant = form.variants.find((item) => item.key === key)
    if (!variant) return

    for (const file of files) {
      variant.images.push({
        id: null,
        path: '',
        preview: URL.createObjectURL(file),
        file,
        is_primary: variant.images.length === 0,
      })
    }

    if (variantImageInputs.value[key]) {
      variantImageInputs.value[key].value = ''
    }
  }

  function removeVariantImageItem(variantKey, imgIndex) {
    const variant = form.variants.find((item) => item.key === variantKey)
    if (!variant) return

    const [removed] = variant.images.splice(imgIndex, 1)
    revokePreview(removed?.preview)

    if (removed?.is_primary && variant.images.length > 0) {
      variant.images[0].is_primary = true
    }
  }

  function setVariantImagePrimary(variantKey, imgIndex) {
    const variant = form.variants.find((item) => item.key === variantKey)
    if (!variant) return

    variant.images.forEach((img, idx) => {
      img.is_primary = idx === imgIndex
    })
  }

  function colorName(colorId) {
    const color = colors.value.find((item) => Number(item.id) === Number(colorId))
    return color?.name || ''
  }

  function colorHex(colorId) {
    const color = colors.value.find((item) => Number(item.id) === Number(colorId))
    return color?.hex_code || ''
  }

  function sizeName(sizeId) {
    const size = sizes.value.find((item) => Number(item.id) === Number(sizeId))
    return size?.name || ''
  }

  function currencyLabel(value) {
    return formatCopPrice(value)
  }

  function setDefaultVariant(key) {
    form.variants.forEach((variant) => {
      variant.is_default = variant.key === key
    })
  }

  function ensureDefaultVariant() {
    if (!form.variants.length) return
    if (form.variants.some((variant) => variant.is_default)) return
    form.variants[0].is_default = true
  }

  function addVariant() {
    errors.variants = ''
    form.variants.push(createVariant({
      sizes: [],
      is_default: form.variants.length === 0,
    }))
    ensureDefaultVariant()
    activeTab.value = 'variants'
  }

  function removeVariant(key) {
    const index = form.variants.findIndex((variant) => variant.key === key)
    if (index === -1) return

    form.variants[index].images.forEach((image) => revokePreview(image.preview))
    form.variants.splice(index, 1)
    ensureDefaultVariant()

    if (activeVariantKey.value === key) {
      closeVariantModal()
    }
  }

  function openVariantModal(key) {
    activeVariantKey.value = key
    selectedSizeId.value = ''
    variantModalOpen.value = true
  }

  function closeVariantModal() {
    variantModalOpen.value = false
    activeVariantKey.value = ''
    selectedSizeId.value = ''
  }

  function addSizeRowToActiveVariant() {
    if (!activeVariant.value) return

    const sizeId = Number(selectedSizeId.value)
    if (!sizeId) {
      showSnackbar({ type: 'error', message: 'Selecciona una talla para agregarla a la variante.' })
      return
    }

    activeVariant.value.sizes.push(createSizeRow({ size_id: sizeId }))
    syncVariantSkus()
    selectedSizeId.value = ''
  }

  function removeSizeRow(variantKey, sizeKey) {
    const variant = form.variants.find((item) => item.key === variantKey)
    if (!variant) return

    const index = variant.sizes.findIndex((size) => size.key === sizeKey)
    if (index !== -1) {
      variant.sizes.splice(index, 1)
    }
  }

  function validateField(field) {
    if (field === 'name') {
      errors.name = form.name.trim().length >= 2 ? '' : 'El nombre es obligatorio y debe tener al menos 2 caracteres.'
    }

    if (field === 'price') {
      errors.price = validateCopPrice(form.price).message
    }

    if (field === 'category_id') {
      errors.category_id = form.category_id ? '' : 'Selecciona una categoría.'
    }

    if (field === 'refund_days') {
      if (!form.is_refundable) {
        errors.refund_days = ''
        return
      }

      const days = Number(form.refund_days)
      errors.refund_days = Number.isInteger(days) && days >= 1 && days <= 365
        ? ''
        : 'Indica un plazo de reembolso entre 1 y 365 días.'
    }

    if (field === 'compare_price') {
      const compareText = String(form.compare_price ?? '').trim()
      if (!compareText) {
        errors.compare_price = ''
        return
      }

      const compare = validateCopPrice(compareText)
      const price = validateCopPrice(form.price)
      errors.compare_price = !compare.valid
        ? numericValidationMessages.copPrice
        : price.valid && compare.value <= price.value
          ? 'El precio comparativo debe ser mayor al precio base.'
          : ''
    }
  }

  function handleCopInput(field) {
    form[field] = normalizeCopInput(form[field])
    validateField(field)
    if (field === 'price') {
      validateField('compare_price')
    }
    validateAllSizeRows()
  }

  function handleSizeCopInput(sizeRow, field) {
    sizeRow[field] = normalizeCopInput(sizeRow[field])
    validateSizeRow(sizeRow)
  }

  function validateSizeRow(sizeRow) {
    if (!sizeRow.errors) {
      sizeRow.errors = { price: '', compare_price: '', quantity: '' }
    }

    const priceSource = String(sizeRow.price ?? '').trim() ? sizeRow.price : form.price
    const price = validateCopPrice(priceSource)
    sizeRow.errors.price = price.valid ? '' : numericValidationMessages.copPrice

    const compareSource = String(sizeRow.compare_price ?? '').trim()
    if (!compareSource) {
      sizeRow.errors.compare_price = ''
    } else {
      const compare = validateCopPrice(compareSource)
      sizeRow.errors.compare_price = !compare.valid
        ? numericValidationMessages.copPrice
        : price.valid && compare.value <= price.value
          ? 'El precio comparativo debe ser mayor al precio de venta.'
          : ''
    }

    const quantity = validatePositiveInteger(sizeRow.quantity)
    sizeRow.errors.quantity = quantity.message

    return !sizeRow.errors.price && !sizeRow.errors.compare_price && !sizeRow.errors.quantity
  }

  function validateAllSizeRows() {
    form.variants.forEach((variant) => {
      variant.sizes.forEach((sizeRow) => validateSizeRow(sizeRow))
    })
  }

  function hasValidProductState() {
    const nameValid = form.name.trim().length >= 2
    const categoryValid = Boolean(form.category_id)
    const price = validateCopPrice(form.price)
    const compareText = String(form.compare_price ?? '').trim()
    const compare = compareText ? validateCopPrice(compareText) : { valid: true, value: null }
    const compareValid = compare.valid && (!compare.value || !price.valid || compare.value > price.value)
    const refundDays = Number(form.refund_days)
    const refundPolicyValid = !form.is_refundable || (Number.isInteger(refundDays) && refundDays >= 1 && refundDays <= 365)

    if (!nameValid || !categoryValid || !price.valid || !compareValid || !refundPolicyValid || !form.variants.length) {
      return false
    }

    return form.variants.every((variant) => {
      if (!variant.color_id || !variant.sizes.length) return false
      const seenSizes = new Set()

      return variant.sizes.every((sizeRow) => {
        const sizeId = Number(sizeRow.size_id)
        if (!sizeId || seenSizes.has(sizeId)) return false
        seenSizes.add(sizeId)

        const priceSource = String(sizeRow.price ?? '').trim() ? sizeRow.price : form.price
        const rowPrice = validateCopPrice(priceSource)
        const rowCompareText = String(sizeRow.compare_price ?? '').trim()
        const rowCompare = rowCompareText ? validateCopPrice(rowCompareText) : { valid: true, value: null }
        const rowCompareValid = rowCompare.valid && (!rowCompare.value || !rowPrice.valid || rowCompare.value > rowPrice.value)
        const rowQuantity = validatePositiveInteger(sizeRow.quantity)

        return rowPrice.valid && rowCompareValid && rowQuantity.valid
      })
    })
  }

  function syncSlugValue() {
    if (!slugManuallyEdited.value || !String(form.slug || '').trim()) {
      form.slug = generatedSlug.value
    }
  }

  function syncVariantSkus() {
    form.variants.forEach((variant) => {
      variant.sizes.forEach((sizeRow) => {
        if (sizeRow.sku_manually_edited && String(sizeRow.sku || '').trim()) {
          return
        }

        sizeRow.sku = buildGeneratedSku({
          brand: form.brand,
          categorySource: categorySource(form.category_id),
          colorName: colorName(variant.color_id),
          gender: form.gender,
          name: form.name,
          sizeName: sizeName(sizeRow.size_id),
        })
      })
    })
  }

  function handleSlugInput() {
    const normalizedSlug = slugifyText(form.slug)

    if (!normalizedSlug) {
      slugManuallyEdited.value = false
      form.slug = generatedSlug.value
      return
    }

    slugManuallyEdited.value = normalizedSlug !== generatedSlug.value
    form.slug = normalizedSlug
  }

  function handleSizeSkuInput(variant, sizeRow) {
    const normalizedSku = normalizeSkuValue(sizeRow.sku)

    if (!normalizedSku) {
      sizeRow.sku_manually_edited = false
      sizeRow.sku = buildGeneratedSku({
        brand: form.brand,
        categorySource: categorySource(form.category_id),
        colorName: colorName(variant.color_id),
        gender: form.gender,
        name: form.name,
        sizeName: sizeName(sizeRow.size_id),
      })
      return
    }

    const generatedSku = buildGeneratedSku({
      brand: form.brand,
      categorySource: categorySource(form.category_id),
      colorName: colorName(variant.color_id),
      gender: form.gender,
      name: form.name,
      sizeName: sizeName(sizeRow.size_id),
    })
    sizeRow.sku_manually_edited = normalizedSku !== generatedSku
    sizeRow.sku = normalizedSku
  }

  function validateVariants() {
    if (!form.variants.length) {
      errors.variants = 'Debes agregar al menos una variante.'
      return false
    }

    for (const variant of form.variants) {
      if (!variant.color_id) {
        errors.variants = 'Todas las variantes deben tener un color.'
        return false
      }

      if (!variant.sizes.length) {
        errors.variants = 'Cada variante debe tener al menos una talla configurada.'
        return false
      }

      const seenSizes = new Set()
      for (const size of variant.sizes) {
        const sizeId = Number(size.size_id)
        if (!sizeId) {
          errors.variants = 'Cada fila de tallas debe apuntar a una talla válida.'
          return false
        }

        if (seenSizes.has(sizeId)) {
          errors.variants = 'No repitas la misma talla dentro de una variante.'
          return false
        }

        seenSizes.add(sizeId)

        const priceResult = validateCopPrice(String(size.price ?? '').trim() ? size.price : form.price)
        const compareResult = String(size.compare_price ?? '').trim()
          ? validateCopPrice(size.compare_price)
          : { valid: true, value: null }
        const quantityResult = validatePositiveInteger(size.quantity)
        validateSizeRow(size)

        if (!priceResult.valid) {
          errors.variants = 'Cada talla debe tener un precio mayor a cero.'
          return false
        }

        if (!compareResult.valid || (compareResult.value && compareResult.value <= priceResult.value)) {
          errors.variants = 'El precio comparativo por talla debe ser mayor al precio de venta.'
          return false
        }

        if (!quantityResult.valid) {
          errors.variants = numericValidationMessages.positiveInteger
          return false
        }
      }
    }

    errors.variants = ''
    return true
  }

  function requestRefundPolicyToggle() {
    if (form.is_refundable) {
      form.is_refundable = false
      form.refund_days = ''
      errors.refund_days = ''
      return
    }

    refundPolicyModalOpen.value = true
  }

  function closeRefundPolicyModal() {
    refundPolicyModalOpen.value = false
    validateField('refund_days')
  }

  function confirmRefundPolicy() {
    form.is_refundable = true
    validateField('refund_days')
    if (errors.refund_days) {
      return
    }

    refundPolicyModalOpen.value = false
  }

  function validateForm() {
    validateField('name')
    validateField('price')
    validateField('compare_price')
    validateField('category_id')
    validateField('refund_days')

    const generalValid = !errors.name && !errors.price && !errors.compare_price && !errors.category_id && !errors.refund_days
    const variantsValid = validateVariants()

    if (!generalValid) {
      activeTab.value = 'general'
    } else if (!variantsValid) {
      activeTab.value = 'variants'
    }

    return generalValid && variantsValid
  }

  function extractErrorMessage(error) {
    return error?.response?.data?.message || error?.response?.data?.error || 'No se pudo guardar el producto.'
  }

  async function saveProduct() {
    if (!validateForm()) {
      showSnackbar({ type: 'error', message: 'Revisa la información del producto antes de guardar.' })
      return
    }

    saving.value = true
    try {
      const payload = buildProductPayload({ form, validateCopPrice, validatePositiveInteger })
      const { body, config } = buildProductRequestBody({ form, mainImageFile: mainImageFile.value, payload })
      const endpoint = isEditing.value ? `/admin/products/${route.params.id}` : '/admin/products'

      if (isEditing.value) {
        await catalogHttp.put(endpoint, body, config)
      } else {
        await catalogHttp.post(endpoint, body, config)
      }

      showSnackbar({
        type: 'success',
        message: isEditing.value ? 'Producto actualizado correctamente.' : 'Producto creado correctamente.',
      })
      router.push('/admin/productos')
    } catch (error) {
      showSnackbar({ type: 'error', message: extractErrorMessage(error) })
    } finally {
      saving.value = false
    }
  }

  function normalizeRows(response) {
    const data = response.data?.data || response.data || []
    return Array.isArray(data) ? data : (data.data || [])
  }

  async function loadCatalogOptions() {
    const [categoriesResponse, collectionsResponse, colorsResponse, sizesResponse] = await Promise.all([
      catalogHttp.get('/admin/categories'),
      catalogHttp.get('/admin/collections'),
      catalogHttp.get('/admin/colors'),
      catalogHttp.get('/admin/sizes'),
    ])

    categories.value = normalizeRows(categoriesResponse).map((row) => ({
      ...row,
      id: Number(row.id),
      name: row.name || row.nombre || 'Sin nombre',
    }))

    collections.value = normalizeRows(collectionsResponse).map((row) => ({
      ...row,
      id: Number(row.id),
      name: row.name || row.nombre || 'Sin nombre',
    }))

    colors.value = normalizeRows(colorsResponse).map((row) => ({
      ...row,
      id: Number(row.id),
      name: row.name || row.nombre || 'Sin color',
    }))

    sizes.value = normalizeRows(sizesResponse).map((row) => ({
      ...row,
      id: Number(row.id),
      name: row.name || row.nombre || row.size_label || 'Sin talla',
    }))
  }

  async function loadProduct() {
    if (!isEditing.value) {
      if (!form.variants.length) {
        addVariant()
      }
      return
    }

    const response = await catalogHttp.get(`/admin/products/${route.params.id}`)
    const data = response.data?.data || {}
    const product = data.product || {}
    const variants = Array.isArray(data.variants) ? data.variants : []
    const productImages = Array.isArray(data.images) ? data.images : []

    slugManuallyEdited.value = true
    form.name = product.name || product.nombre || ''
    form.slug = product.slug || ''
    form.brand = product.brand || product.marca || ''
    form.gender = product.gender || product.genero || 'unisex'
    form.category_id = product.category_id ? Number(product.category_id) : ''
    form.collection_id = product.collection_id ? Number(product.collection_id) : ''
    form.collection = product.collection || product.coleccion || ''
    form.price = Number(product.price ?? product.precio ?? 0) || ''
    form.compare_price = product.compare_price !== null && product.compare_price !== undefined
      ? Number(product.compare_price)
      : ''
    form.material = product.material || ''
    form.description = product.description || product.descripcion || ''
    form.care_instructions = product.care_instructions || product.instrucciones_cuidado || ''
    form.is_featured = normalizeBoolean(product.is_featured ?? product.destacado, false)
    form.is_active = normalizeBoolean(product.is_active ?? product.activo, true)
    form.is_refundable = normalizeBoolean(product.is_refundable, false)
    form.refund_days = form.is_refundable ? Number(product.refund_days ?? 0) || '' : ''

    const mainImage = productImages.find((image) => !image.color_variant_id && normalizeBoolean(image.is_primary, true))
      || productImages.find((image) => !image.color_variant_id)

    form.main_image_path = mainImage?.image_path || mainImage?.url || product.image || product.imagen || product.image_url || ''
    if (form.main_image_path) {
      mainImagePreview.value = resolveMediaUrl(form.main_image_path, 'product')
    }

    form.variants = variants.map((variant) => {
      const variantImgList = (variant.images || []).length > 0
        ? variant.images
        : productImages.filter((img) => Number(img.color_variant_id) === Number(variant.id))

      return createVariant({
        id: variant.id,
        key: `variant-${variant.id || nextVariantKey()}`,
        color_id: variant.color_id,
        is_default: normalizeBoolean(variant.is_default, false),
        images: variantImgList.map((img) => ({
          id: img.id || null,
          image_path: img.image_path || img.url || '',
          is_primary: normalizeBoolean(img.is_primary, false),
        })),
        size_variants: (variant.size_variants || []).map((size) => ({
          id: size.id,
          size_id: size.size_id,
          price: Number(size.price ?? 0),
          compare_price: size.compare_price !== null && size.compare_price !== undefined ? Number(size.compare_price) : '',
          quantity: Number(size.quantity ?? 0),
          sku: size.sku || '',
          barcode: size.barcode || '',
          is_active: normalizeBoolean(size.is_active, true),
        })),
      })
    })

    if (!form.variants.length) {
      addVariant()
    }

    slugManuallyEdited.value = shouldTreatExistingSlugAsManual({
      generatedSlug: generatedSlug.value,
      name: form.name,
      value: form.slug,
    })
    syncSlugValue()
    ensureDefaultVariant()
    syncVariantSkus()
  }

  function onProductImageError(event, path) {
    handleMediaError(event, path, 'product')
  }

  watch(generatedSlug, () => {
    syncSlugValue()
  }, { immediate: true })

  watch(skuGenerationSignature, () => {
    syncVariantSkus()
  }, { immediate: true })

  onMounted(async () => {
    initialLoading.value = true
    try {
      await loadCatalogOptions()
      await loadProduct()
      activeTab.value = 'general'
    } catch (error) {
      showSnackbar({ type: 'error', message: extractErrorMessage(error) || 'No se pudo cargar el formulario.' })
    } finally {
      initialLoading.value = false
    }
  })

  return {
    activeTab,
    activeVariant,
    addSizeRowToActiveVariant,
    addVariant,
    availableSizesForActiveVariant,
    canSaveProduct,
    categories,
    closeRefundPolicyModal,
    closeVariantModal,
    collections,
    colorHex,
    colorName,
    colors,
    currencyLabel,
    errors,
    form,
    handleCopInput,
    handleMainImageUpload,
    handleSizeCopInput,
    handleSizeSkuInput,
    handleSlugInput,
    handleVariantImageUpload,
    initialLoading,
    isEditing,
    mainImagePreview,
    onProductImageError,
    openVariantModal,
    removeMainImage,
    refundPolicyModalOpen,
    removeSizeRow,
    removeVariant,
    removeVariantImageItem,
    requestRefundPolicyToggle,
    saveProduct,
    saving,
    selectedSizeId,
    setDefaultVariant,
    confirmRefundPolicy,
    setMainImageInputRef,
    setVariantImageInputRef,
    setVariantImagePrimary,
    sizeName,
    sizes,
    totalSizeConfigurations,
    totalStock,
    triggerMainImagePicker,
    triggerVariantImagePicker,
    validateField,
    validateSizeRow,
    variantModalOpen,
  }
}
