// Construye el payload con los nombres exactos que espera el backend de catálogo.
export function buildProductPayload({ form, validateCopPrice, validatePositiveInteger }) {
  return {
    nombre: form.name.trim(),
    slug: form.slug?.trim() || null,
    brand: form.brand?.trim() || null,
    gender: form.gender || 'unisex',
    category_id: Number(form.category_id),
    collection_id: form.collection_id ? Number(form.collection_id) : null,
    collection: form.collection?.trim() || null,
    precio: validateCopPrice(form.price).value,
    compare_price: form.compare_price !== '' ? validateCopPrice(form.compare_price).value : null,
    material: form.material?.trim() || null,
    descripcion: form.description?.trim() || null,
    care_instructions: form.care_instructions?.trim() || null,
    main_image_path: form.main_image_path?.trim() || null,
    activo: Boolean(form.is_active),
    is_featured: Boolean(form.is_featured),
    is_refundable: Boolean(form.is_refundable),
    refund_days: form.is_refundable ? Number(form.refund_days) : null,
    variants: buildVariantPayload({ form, validateCopPrice, validatePositiveInteger }),
  }
}

// Construye variantes con imágenes existentes y tallas validadas para persistencia.
// Construye variantes con imágenes existentes y tallas validadas para persistencia.
function buildVariantPayload({ form, validateCopPrice, validatePositiveInteger }) {
  return form.variants.map((variant) => ({
    id: variant.id,
    key: variant.key,
    color_id: Number(variant.color_id),
    is_default: Boolean(variant.is_default),
    images: variant.images
      .filter((img) => img.path && !img.file)
      .map((img, order) => ({
        id: img.id,
        path: img.path,
        is_primary: img.is_primary,
        order,
      })),
    sizes: variant.sizes.map((size) => ({
      id: size.id,
      key: size.key,
      size_id: Number(size.size_id),
      price: validateCopPrice(String(size.price ?? '').trim() ? size.price : form.price).value,
      compare_price: size.compare_price !== '' && size.compare_price !== null
        ? validateCopPrice(size.compare_price).value
        : (form.compare_price !== '' ? validateCopPrice(form.compare_price).value : null),
      quantity: validatePositiveInteger(size.quantity).value,
      sku: size.sku?.trim() || null,
      barcode: size.barcode?.trim() || null,
      is_active: Boolean(size.is_active),
    })),
  }))
}

// Mantiene FormData compatible con archivos nuevos de producto y variantes.
export function buildProductRequestBody({ form, mainImageFile, payload }) {
  const hasFiles = Boolean(mainImageFile) || form.variants.some((variant) => variant.images.some((img) => img.file))

  // Si no hay archivos nuevos, el backend puede recibir JSON normal.
  // Si no hay archivos nuevos, el backend puede recibir JSON normal.
  if (!hasFiles) {
    return { body: payload, config: undefined }
  }

  // Cuando hay archivos, variants viaja serializado y las imágenes viajan como partes multipart.
  // Cuando hay archivos, variants viaja serializado y las imágenes viajan como partes multipart.
  const formData = new FormData()
  Object.entries(payload).forEach(([key, value]) => {
    if (key === 'variants') {
      formData.append('variants', JSON.stringify(value))
      return
    }

    formData.append(key, value ?? '')
  })

  // La imagen principal nueva se envía en una clave dedicada para reemplazo seguro.
  // La imagen principal nueva se envía en una clave dedicada para reemplazo seguro.
  if (mainImageFile) {
    formData.append('main_image_file', mainImageFile)
  }

  // Cada imagen nueva de variante conserva la clave de variante y su orden visual.
  // Cada imagen nueva de variante conserva la clave de variante y su orden visual.
  form.variants.forEach((variant) => {
    variant.images.forEach((img, imgIndex) => {
      if (img.file) {
        formData.append(`variant_image_files[${variant.key}][${imgIndex}]`, img.file)
      }
    })
  })

  return {
    body: formData,
    config: {
      headers: {
        'Content-Type': 'multipart/form-data',
      },
    },
  }
}
