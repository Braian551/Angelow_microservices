import ExcelJS from 'exceljs'
import { jsPDF } from 'jspdf'
import autoTable from 'jspdf-autotable'
import { ref } from 'vue'
import { useAppShell } from '../../../composables/useAppShell'
import { useSnackbarSystem } from '../../../composables/useSnackbarSystem'
import { getMediaCandidates, getUploadCandidates, resolveMediaUrl } from '../../../utils/media'

const DEFAULT_PRIMARY_HEX = '0F7ABF'
const DEFAULT_SECONDARY_HEX = 'DCEAF6'
const DEFAULT_TEXT_HEX = '24364B'
const PDF_MARGIN_X = 14
const PDF_MARGIN_TOP = 14
const PDF_MARGIN_BOTTOM = 12
const PDF_IMAGE_SIZE = 11
const PDF_BRAND_CARD_MIN_HEIGHT = 27
const PDF_REPORT_CARD_GAP = 5
const PDF_CONTACT_PANEL_MIN_WIDTH = 50
const PDF_CONTACT_PANEL_MAX_WIDTH = 72

// Normaliza texto exportable y evita celdas vacías poco legibles en Excel/PDF.
function normalizeExportValue(value, fallback = 'Sin dato') {
  if (Array.isArray(value)) {
    const joined = value
      .map((item) => String(item ?? '').trim())
      .filter(Boolean)
      .join(', ')

    return joined || fallback
  }

  if (typeof value === 'boolean') {
    return value ? 'Sí' : 'No'
  }

  const text = String(value ?? '').trim()
  return text || fallback
}

// Limpia nombres de archivo para que funcionen igual en Windows y navegador.
function sanitizeFileBaseName(value, fallback = 'exportacion-admin') {
  const cleaned = String(value || '')
    .trim()
    .normalize('NFD')
    .replace(/[\u0300-\u036f]/g, '')
    .replace(/[^a-zA-Z0-9-_]+/g, '-')
    .replace(/-+/g, '-')
    .replace(/^-|-$/g, '')

  return cleaned || fallback
}

// Excel limita el nombre de hoja y prohíbe ciertos caracteres reservados.
function sanitizeSheetName(value, fallback = 'Reporte') {
  const cleaned = String(value || fallback)
    .replace(/[\\/*?:\[\]]/g, ' ')
    .trim()

  return (cleaned || fallback).slice(0, 31)
}

// Convierte colores configurables del sitio a un HEX válido reutilizable en PDF y Excel.
function toHexColor(value, fallback = DEFAULT_PRIMARY_HEX) {
  const normalized = String(value || '').trim().replace('#', '')

  if (/^[\da-f]{3}$/i.test(normalized)) {
    return normalized
      .split('')
      .map((char) => `${char}${char}`)
      .join('')
      .toUpperCase()
  }

  if (/^[\da-f]{6}$/i.test(normalized)) {
    return normalized.toUpperCase()
  }

  return fallback
}

// ExcelJS usa ARGB; se agrega alpha completo para conservar consistencia visual.
function toExcelArgb(value, fallback = DEFAULT_PRIMARY_HEX) {
  return `FF${toHexColor(value, fallback)}`
}

// Permite reutilizar colores calculados en RGB dentro de superficies suaves de Excel.
function rgbToExcelArgb(rgb = [255, 255, 255]) {
  return `FF${rgb
    .map((channel) => Number(channel || 0).toString(16).padStart(2, '0').toUpperCase())
    .join('')}`
}

// jsPDF trabaja mejor con RGB numérico cuando se dibujan cabeceras y líneas.
function hexToRgb(value, fallback = DEFAULT_PRIMARY_HEX) {
  const hex = toHexColor(value, fallback)

  return [
    parseInt(hex.slice(0, 2), 16),
    parseInt(hex.slice(2, 4), 16),
    parseInt(hex.slice(4, 6), 16),
  ]
}

// Aclara un color hacia blanco para construir superficies sobrias sin recurrir a degradados.
function tintRgb(rgb, ratio = 0.82) {
  return rgb.map((channel) => Math.round(channel + ((255 - channel) * ratio)))
}

// Filtra columnas por formato para que imágenes o campos auxiliares aparezcan solo donde corresponda.
function isColumnEnabled(column, format) {
  if (format === 'excel' && column.includeInExcel === false) return false
  if (format === 'pdf' && column.includeInPdf === false) return false
  return true
}

// Resuelve el valor fuente de una columna respetando overrides por formato.
function resolveColumnRawValue(column, row, format) {
  const customValue = format === 'excel'
    ? (column.excelValue ?? column.value)
    : (column.pdfValue ?? column.value)

  if (typeof customValue === 'function') {
    return customValue(row)
  }

  if (customValue !== undefined) {
    return customValue
  }

  return row?.[column.key]
}

// Convierte a texto final lo que verá el usuario en PDF o en celdas textuales de Excel.
function resolveColumnTextValue(column, row, format) {
  return normalizeExportValue(resolveColumnRawValue(column, row, format), column.emptyText || 'Sin dato')
}

// Excel mantiene números reales cuando la columna lo declara para ordenar y filtrar mejor.
function resolveExcelCellValue(column, row) {
  const rawValue = resolveColumnRawValue(column, row, 'excel')

  if (column.excelType === 'number' || column.excelType === 'currency') {
    const numericValue = Number(rawValue)
    return Number.isFinite(numericValue) ? numericValue : 0
  }

  return normalizeExportValue(rawValue, column.emptyText || 'Sin dato')
}

// Dispara la descarga binaria tanto para buffers de Excel como para blobs futuros.
function downloadBinaryFile(content, mimeType, fileName) {
  const blob = content instanceof Blob ? content : new Blob([content], { type: mimeType })
  const url = URL.createObjectURL(blob)
  const link = document.createElement('a')

  link.href = url
  link.download = fileName
  document.body.appendChild(link)
  link.click()
  document.body.removeChild(link)
  URL.revokeObjectURL(url)
}

// Reúne la información viva de branding y soporte para que toda exportación use la misma plantilla.
function resolveBrandLogoUrl(path) {
  const source = String(path || '').trim()
  if (!source) return resolveMediaUrl(source, 'brand')

  try {
    const parsed = new URL(source, window.location.origin)
    if (parsed.pathname.startsWith('/uploads/')) {
      return `${parsed.pathname}${parsed.search}`
    }
  } catch {
    // Mantiene el fallback existente cuando el valor no se puede parsear como URL.
  }

  const uploadCandidates = getUploadCandidates(source)
  const localUploadCandidate = uploadCandidates.find((candidate) => String(candidate || '').startsWith('/uploads/'))

  return localUploadCandidate || resolveMediaUrl(source, 'brand')
}

// Reúne la información viva de branding y soporte para que toda exportación use la misma plantilla.
function buildBrandingSnapshot(settings) {
  return {
    storeName: normalizeExportValue(settings?.store_name, 'Angelow'),
    tagline: String(settings?.store_tagline || '').trim(),
    supportAddress: String(settings?.support_address || '').trim(),
    supportPhone: String(settings?.support_phone || '').trim(),
    supportEmail: String(settings?.support_email || '').trim(),
    primaryColor: toHexColor(settings?.primary_color, DEFAULT_PRIMARY_HEX),
    secondaryColor: toHexColor(settings?.secondary_color, DEFAULT_SECONDARY_HEX),
    textColor: toHexColor(settings?.text_color, DEFAULT_TEXT_HEX),
    logoUrl: resolveBrandLogoUrl(settings?.brand_logo_secondary || settings?.brand_logo),
  }
}

// Concatena la información operativa que debe verse en las plantillas exportadas.
function buildBrandingContactLine(branding) {
  return [branding.supportAddress, branding.supportPhone, branding.supportEmail].filter(Boolean).join(' · ')
}

// Mantiene la misma marca temporal entre Excel y PDF para que ambos encabezados salgan alineados.
function buildGeneratedAtLabel() {
  return `Generado el ${new Date().toLocaleString('es-CO')}`
}

// Normaliza metadatos de exportación para reusar el mismo bloque en PDF y Excel.
function normalizeMetaEntries(meta = []) {
  return meta
    .map((entry) => {
      if (!entry) return null

      if (typeof entry === 'string') {
        return { label: 'Detalle', value: entry }
      }

      const label = normalizeExportValue(entry.label, 'Detalle')
      const value = normalizeExportValue(entry.value, 'Sin dato')
      return { label, value }
    })
    .filter(Boolean)
}

// Convierte blobs en data URL para poder incrustar logos e imágenes dentro de PDF/Excel.
function blobToDataUrl(blob) {
  return new Promise((resolve, reject) => {
    const reader = new FileReader()

    reader.onload = () => resolve(reader.result)
    reader.onerror = () => reject(new Error('No fue posible leer la imagen para la exportación.'))
    reader.readAsDataURL(blob)
  })
}

// Mide la proporción real del asset para que el logo no se deforme al insertarlo en PDF o Excel.
function measureImageDimensions(dataUrl) {
  return new Promise((resolve, reject) => {
    const image = new Image()

    image.onload = () => {
      resolve({
        width: image.naturalWidth || image.width || 1,
        height: image.naturalHeight || image.height || 1,
      })
    }

    image.onerror = () => reject(new Error('No fue posible medir la imagen para la exportación.'))
    image.src = dataUrl
  })
}

// Detecta el tipo de imagen admitido por los generadores del documento.
function normalizeImageDescriptor(blob, sourceUrl) {
  const mimeType = String(blob?.type || '').toLowerCase()
  const lowerSource = String(sourceUrl || '').toLowerCase()

  if (mimeType.includes('png') || lowerSource.includes('.png')) {
    return { extension: 'png', format: 'PNG' }
  }

  if (mimeType.includes('jpg') || mimeType.includes('jpeg') || lowerSource.includes('.jpg') || lowerSource.includes('.jpeg')) {
    return { extension: 'jpeg', format: 'JPEG' }
  }

  return null
}

// Ajusta cualquier imagen al espacio disponible conservando su proporción original.
function fitImageToBox(asset, maxWidth, maxHeight) {
  const sourceWidth = Number(asset?.width) || maxWidth || 1
  const sourceHeight = Number(asset?.height) || maxHeight || 1
  const aspectRatio = sourceWidth / sourceHeight
  let width = maxWidth
  let height = width / aspectRatio

  if (height > maxHeight) {
    height = maxHeight
    width = height * aspectRatio
  }

  return {
    width,
    height,
    offsetX: (maxWidth - width) / 2,
    offsetY: (maxHeight - height) / 2,
  }
}

// Convierte formatos modernos como WEBP o AVIF a PNG cuando el motor de exportación no los soporta directo.
function rasterizeImageBlob(blob) {
  return new Promise((resolve, reject) => {
    const objectUrl = URL.createObjectURL(blob)
    const image = new Image()

    image.onload = () => {
      try {
        const width = image.naturalWidth || image.width
        const height = image.naturalHeight || image.height
        const canvas = document.createElement('canvas')
        const context = canvas.getContext('2d')

        if (!context || !width || !height) {
          throw new Error('No fue posible rasterizar la imagen para la exportación.')
        }

        canvas.width = width
        canvas.height = height
        context.drawImage(image, 0, 0, width, height)

        resolve({
          extension: 'png',
          format: 'PNG',
          dataUrl: canvas.toDataURL('image/png'),
          width,
          height,
        })
      } catch (error) {
        reject(error)
      } finally {
        URL.revokeObjectURL(objectUrl)
      }
    }

    image.onerror = () => {
      URL.revokeObjectURL(objectUrl)
      reject(new Error('No fue posible convertir la imagen para la exportación.'))
    }

    image.src = objectUrl
  })
}

// Reúne candidatas locales y remotas para que la exportación funcione tanto en desarrollo como detrás de servidor.
function buildImageFetchCandidates(url, fallbackType = 'product') {
  const source = String(url || '').trim()
  if (!source) return []

  if (fallbackType === 'brand') {
    return getUploadCandidates(source)
  }

  return getMediaCandidates(source, fallbackType)
}

// Descarga la imagen una sola vez y la deja lista para insertarse en PDF o Excel.
async function loadImageAsset(url, fallbackType = 'product') {
  const sources = buildImageFetchCandidates(url, fallbackType)
  if (!sources.length) return null

  let lastError = null

  for (const source of sources) {
    try {
      const response = await fetch(source)
      if (!response.ok) {
        throw new Error('No fue posible descargar una imagen para la exportación.')
      }

      const blob = await response.blob()
      const descriptor = normalizeImageDescriptor(blob, source)
      const normalizedAsset = descriptor
        ? {
            ...descriptor,
            dataUrl: await blobToDataUrl(blob),
          }
        : await rasterizeImageBlob(blob)

      if (!normalizedAsset.width || !normalizedAsset.height) {
        const dimensions = await measureImageDimensions(normalizedAsset.dataUrl)
        normalizedAsset.width = dimensions.width
        normalizedAsset.height = dimensions.height
      }

      return normalizedAsset
    } catch (error) {
      lastError = error
    }
  }

  throw lastError || new Error('No fue posible descargar una imagen para la exportación.')
}

// Prepara caché por fila/columna para incrustar miniaturas solo en columnas PDF con imagen.
async function preloadPdfImages(columns, rows) {
  const cellImages = new Map()
  const sourceCache = new Map()

  for (const [rowIndex, row] of rows.entries()) {
    for (const [columnIndex, column] of columns.entries()) {
      if (typeof column.pdfImage !== 'function') continue

      const source = String(column.pdfImage(row) || '').trim()
      if (!source) continue

      const cacheKey = `${column.fallbackType || 'product'}:${source}`

      if (!sourceCache.has(cacheKey)) {
        try {
          sourceCache.set(cacheKey, await loadImageAsset(source, column.fallbackType || 'product'))
        } catch {
          sourceCache.set(cacheKey, null)
        }
      }

      const asset = sourceCache.get(cacheKey)
      if (asset) {
        cellImages.set(`${rowIndex}:${columnIndex}`, asset)
      }
    }
  }

  return cellImages
}

// Determina un ancho razonable para columnas Excel cuando la vista no lo especifica.
function resolveExcelColumnWidth(column) {
  return Number(column.width) || Math.max(16, Math.min(34, String(column.header || '').length + 8))
}

// Aplica el mismo acabado visual a las superficies superiores del Excel compartido.
function styleExcelHeaderPanel(cell, fillArgb, borderArgb) {
  cell.fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: fillArgb } }
  cell.border = {
    top: { style: 'thin', color: { argb: borderArgb } },
    left: { style: 'thin', color: { argb: borderArgb } },
    bottom: { style: 'thin', color: { argb: borderArgb } },
    right: { style: 'thin', color: { argb: borderArgb } },
  }
  cell.alignment = { vertical: 'middle', horizontal: 'left' }
}

// Aproxima el ancho real de una columna Excel para poder centrar imágenes dentro de su celda.
function estimateExcelColumnPixels(width = 8.43) {
  return Math.round((Number(width || 8.43) * 7) + 5)
}

// Convierte la altura de fila a píxeles para alinear el logo dentro del bloque combinado.
function estimateExcelRowPixels(height = 15) {
  return Math.round(Number(height || 15) * (96 / 72))
}

// Centra el logo flotante de Excel dentro de una sola columna para no dejar una franja vacía innecesaria.
function placeExcelLogo({ workbook, worksheet, logoAsset }) {
  const logoId = workbook.addImage({ base64: logoAsset.dataUrl, extension: logoAsset.extension })
  const logoPlacement = fitImageToBox(logoAsset, 42, 30)
  // Usa la métrica del rango A2:A4 para que el logo quede centrado dentro de la celda combinada.
  const columnWidthPixels = estimateExcelColumnPixels(worksheet.getColumn(1).width)
  const mergedHeightPixels = [2, 3, 4].reduce(
    (total, rowNumber) => total + estimateExcelRowPixels(worksheet.getRow(rowNumber).height),
    0,
  )
  const horizontalOffset = Math.max(0, (columnWidthPixels - logoPlacement.width) / 2)
  const verticalOffset = Math.max(0, (mergedHeightPixels - logoPlacement.height) / 2)

  worksheet.addImage(logoId, {
    tl: {
      col: horizontalOffset / columnWidthPixels,
      row: 1 + ((verticalOffset / mergedHeightPixels) * 3),
    },
    ext: {
      width: logoPlacement.width,
      height: logoPlacement.height,
    },
  })
}

// Ordena el encabezado de Excel en un bloque compacto con logo proporcionado y jerarquía de marca.
function drawExcelHeader({ workbook, worksheet, branding, logoAsset, title, subtitle, metaEntries, lastColumnIndex }) {
  const primaryArgb = toExcelArgb(branding.primaryColor)
  const secondaryArgb = toExcelArgb(branding.secondaryColor)
  const textArgb = toExcelArgb(branding.textColor)
  const brandPanelArgb = 'FFFFFFFF'
  const reportPanelArgb = rgbToExcelArgb(tintRgb(hexToRgb(branding.primaryColor, DEFAULT_PRIMARY_HEX), 0.92))
  const titleColumnStart = logoAsset ? 2 : 1
  const metadataStartColumn = Math.max(titleColumnStart, lastColumnIndex - 1)
  const contactEndColumn = Math.max(titleColumnStart, metadataStartColumn - 1)
  const contactLine = buildBrandingContactLine(branding)
  const generatedLabel = buildGeneratedAtLabel()

  worksheet.getRow(1).height = 5
  worksheet.mergeCells(1, 1, 1, lastColumnIndex)
  worksheet.getCell(1, 1).fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: primaryArgb } }

  worksheet.getRow(2).height = 20
  worksheet.getRow(3).height = 14
  worksheet.getRow(4).height = 14
  worksheet.getRow(5).height = 5

  if (logoAsset) {
    worksheet.mergeCells(2, 1, 4, 1)
    styleExcelHeaderPanel(worksheet.getCell(2, 1), 'FFFFFFFF', secondaryArgb)
    placeExcelLogo({ workbook, worksheet, logoAsset })
  }

  worksheet.mergeCells(2, titleColumnStart, 2, lastColumnIndex)
  styleExcelHeaderPanel(worksheet.getCell(2, titleColumnStart), brandPanelArgb, secondaryArgb)
  worksheet.getCell(2, titleColumnStart).value = branding.storeName
  worksheet.getCell(2, titleColumnStart).font = {
    size: 16,
    bold: true,
    color: { argb: primaryArgb },
  }

  if (branding.tagline) {
    worksheet.mergeCells(3, titleColumnStart, 3, lastColumnIndex)
    styleExcelHeaderPanel(worksheet.getCell(3, titleColumnStart), brandPanelArgb, secondaryArgb)
    worksheet.getCell(3, titleColumnStart).value = branding.tagline
    worksheet.getCell(3, titleColumnStart).font = { size: 10, color: { argb: textArgb } }
  }

  if (contactEndColumn >= titleColumnStart) {
    worksheet.mergeCells(4, titleColumnStart, 4, contactEndColumn)
    styleExcelHeaderPanel(worksheet.getCell(4, titleColumnStart), brandPanelArgb, secondaryArgb)
    worksheet.getCell(4, titleColumnStart).value = contactLine || 'Sin datos de contacto configurados'
    worksheet.getCell(4, titleColumnStart).font = { size: 9, color: { argb: textArgb } }

    worksheet.mergeCells(4, metadataStartColumn, 4, lastColumnIndex)
    styleExcelHeaderPanel(worksheet.getCell(4, metadataStartColumn), reportPanelArgb, secondaryArgb)
    worksheet.getCell(4, metadataStartColumn).value = generatedLabel
    worksheet.getCell(4, metadataStartColumn).font = { size: 8.5, bold: true, color: { argb: primaryArgb } }
    worksheet.getCell(4, metadataStartColumn).alignment = { vertical: 'middle', horizontal: 'right' }
  }

  worksheet.mergeCells(6, 1, 6, lastColumnIndex)
  worksheet.getRow(6).height = 24
  styleExcelHeaderPanel(worksheet.getCell(6, 1), reportPanelArgb, secondaryArgb)
  worksheet.getCell(6, 1).value = normalizeExportValue(title, 'Exportación administrativa')
  worksheet.getCell(6, 1).font = {
    size: 13,
    bold: true,
    color: { argb: primaryArgb },
  }

  let currentRow = 7

  if (subtitle) {
    worksheet.mergeCells(currentRow, 1, currentRow, lastColumnIndex)
    worksheet.getCell(currentRow, 1).value = subtitle
    worksheet.getCell(currentRow, 1).font = { size: 10, color: { argb: textArgb } }
    currentRow += 1
  }

  for (const entry of metaEntries) {
    worksheet.mergeCells(currentRow, 1, currentRow, lastColumnIndex)
    worksheet.getCell(currentRow, 1).value = `${entry.label}: ${entry.value}`
    worksheet.getCell(currentRow, 1).font = { size: 9, color: { argb: textArgb } }
    currentRow += 1
  }

  return currentRow + 1
}

// Construye el encabezado visual compartido dentro del Excel con identidad y datos operativos.
async function exportExcelFile({ branding, fileBaseName, title, subtitle, sheetName, columns, rows, meta }) {
  const workbook = new ExcelJS.Workbook()
  const worksheet = workbook.addWorksheet(sanitizeSheetName(sheetName || title || 'Reporte'))
  const excelColumns = columns.filter((column) => isColumnEnabled(column, 'excel'))
  const metaEntries = normalizeMetaEntries(meta)
  const logoAsset = await loadImageAsset(branding.logoUrl, 'brand').catch(() => null)
  const lastColumnIndex = Math.max(excelColumns.length, 6)
  let currentRow = 1

  workbook.creator = branding.storeName
  workbook.company = branding.storeName
  worksheet.properties.defaultRowHeight = 22
  worksheet.columns = excelColumns.map((column, index) => ({
    key: `column-${index}`,
    width: resolveExcelColumnWidth(column),
  }))

  currentRow = drawExcelHeader({
    workbook,
    worksheet,
    branding,
    logoAsset,
    title,
    subtitle,
    metaEntries,
    lastColumnIndex,
  })

  const headerRow = worksheet.getRow(currentRow)
  excelColumns.forEach((column, index) => {
    headerRow.getCell(index + 1).value = column.header
  })

  headerRow.eachCell((cell) => {
    cell.font = { bold: true, color: { argb: 'FFFFFFFF' } }
    cell.fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: toExcelArgb(branding.primaryColor) } }
    cell.alignment = { vertical: 'middle', horizontal: 'left' }
    cell.border = {
      top: { style: 'thin', color: { argb: toExcelArgb(branding.secondaryColor) } },
      left: { style: 'thin', color: { argb: toExcelArgb(branding.secondaryColor) } },
      bottom: { style: 'thin', color: { argb: toExcelArgb(branding.secondaryColor) } },
      right: { style: 'thin', color: { argb: toExcelArgb(branding.secondaryColor) } },
    }
  })

  currentRow += 1

  rows.forEach((row, rowIndex) => {
    const worksheetRow = worksheet.getRow(currentRow + rowIndex)

    excelColumns.forEach((column, columnIndex) => {
      const cell = worksheetRow.getCell(columnIndex + 1)
      cell.value = resolveExcelCellValue(column, row)
      cell.alignment = {
        vertical: 'middle',
        horizontal: column.align || 'left',
        wrapText: true,
      }
      cell.border = {
        top: { style: 'thin', color: { argb: 'FFE7EEF6' } },
        left: { style: 'thin', color: { argb: 'FFE7EEF6' } },
        bottom: { style: 'thin', color: { argb: 'FFE7EEF6' } },
        right: { style: 'thin', color: { argb: 'FFE7EEF6' } },
      }

      if (column.excelType === 'currency') {
        cell.numFmt = '$ #,##0'
      }

      if (rowIndex % 2 === 1) {
        cell.fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: 'FFF7FBFF' } }
      }
    })
  })

  worksheet.autoFilter = {
    from: { row: headerRow.number, column: 1 },
    to: { row: headerRow.number, column: excelColumns.length },
  }
  worksheet.views = [{ state: 'frozen', ySplit: headerRow.number }]

  const buffer = await workbook.xlsx.writeBuffer()
  downloadBinaryFile(
    buffer,
    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    `${sanitizeFileBaseName(fileBaseName)}.xlsx`,
  )
}

// Compacta los metadatos del reporte para que el encabezado PDF conserve jerarquía sin volverse pesado.
function buildPdfMetaText(metaEntries) {
  return metaEntries
    .map((entry) => `${entry.label}: ${entry.value}`)
    .join(' · ')
}

// Reutiliza una sola métrica para logo, tarjetas y textos del PDF y así evita solapes con la tabla.
function buildPdfHeaderLayout(doc, branding, title, subtitle, metaEntries) {
  const contentWidth = doc.internal.pageSize.getWidth() - (PDF_MARGIN_X * 2)
  const contactLine = buildBrandingContactLine(branding)
  const metaText = buildPdfMetaText(metaEntries)
  const contactPanelWidth = contactLine
    ? Math.min(PDF_CONTACT_PANEL_MAX_WIDTH, Math.max(PDF_CONTACT_PANEL_MIN_WIDTH, contentWidth * 0.32))
    : 0
  const logoFrameWidth = 24
  const logoFrameHeight = 18
  const brandTextWidth = contentWidth - 12 - (logoFrameWidth + 4) - (contactPanelWidth ? contactPanelWidth + 5 : 0)
  const brandNameLines = doc.splitTextToSize(branding.storeName, brandTextWidth)
  const taglineLines = branding.tagline ? doc.splitTextToSize(branding.tagline, brandTextWidth) : []
  const titleLines = doc.splitTextToSize(normalizeExportValue(title, 'Exportación administrativa'), contentWidth - 12)
  const subtitleLines = subtitle ? doc.splitTextToSize(subtitle, contentWidth - 14) : []
  const metaLines = metaText ? doc.splitTextToSize(metaText, contentWidth - 22) : []
  const contactLines = contactLine ? doc.splitTextToSize(contactLine, contactPanelWidth - 8) : []
  // Reserva un renglón adicional para la fecha de generación y evita que choque con el contacto.
  const contactCardHeight = contactPanelWidth
    ? Math.max(18, 11.6 + (contactLines.length * 3.4))
    : 0
  const brandCardHeight = Math.max(
    PDF_BRAND_CARD_MIN_HEIGHT,
    12 + (brandNameLines.length * 4.6) + (taglineLines.length * 3.4),
    contactPanelWidth ? contactCardHeight + 5 : 0,
  )
  const metaPanelHeight = metaLines.length ? Math.max(7, 4 + (metaLines.length * 3.5)) : 0
  const reportCardHeight = Math.max(
    22,
    12 + (titleLines.length * 5.1) + (subtitleLines.length * 3.8) + (metaPanelHeight ? metaPanelHeight + 4 : 0),
  )

  return {
    contentWidth,
    brandCardY: PDF_MARGIN_TOP,
    brandCardHeight,
    reportCardY: PDF_MARGIN_TOP + brandCardHeight + PDF_REPORT_CARD_GAP,
    reportCardHeight,
    contactPanelWidth,
    logoFrameWidth,
    logoFrameHeight,
    brandNameLines,
    taglineLines,
    titleLines,
    subtitleLines,
    metaLines,
    metaPanelHeight,
    contactCardHeight,
    contactLines,
    generatedLabel: buildGeneratedAtLabel(),
  }
}

// Calcula la altura inicial del bloque superior para que la tabla PDF no pise logo, título ni metadatos.
function measurePdfStartY(doc, branding, title, subtitle, metaEntries) {
  const layout = buildPdfHeaderLayout(doc, branding, title, subtitle, metaEntries)
  return layout.reportCardY + layout.reportCardHeight + 6
}

// Dibuja la cabecera repetible del PDF usando la marca vigente del sitio.
function drawPdfHeader(doc, branding, title, subtitle, metaEntries, logoAsset) {
  const primaryColor = hexToRgb(branding.primaryColor, DEFAULT_PRIMARY_HEX)
  const textColor = hexToRgb(branding.textColor, DEFAULT_TEXT_HEX)
  const brandSurfaceColor = [255, 255, 255]
  const reportSurfaceColor = tintRgb(primaryColor, 0.93)
  const layout = buildPdfHeaderLayout(doc, branding, title, subtitle, metaEntries)
  const logoFrameX = PDF_MARGIN_X + 4
  const logoFrameY = layout.brandCardY + ((layout.brandCardHeight - layout.logoFrameHeight) / 2)
  const titleX = logoAsset ? logoFrameX + layout.logoFrameWidth + 5 : PDF_MARGIN_X + 5
  const contactCardHeight = layout.contactCardHeight
  const contactCardX = layout.contactPanelWidth
    ? PDF_MARGIN_X + layout.contentWidth - layout.contactPanelWidth - 4
    : 0
  const contactCardY = layout.brandCardY + ((layout.brandCardHeight - contactCardHeight) / 2)

  doc.setFillColor(...brandSurfaceColor)
  doc.setDrawColor(...tintRgb(primaryColor, 0.78))
  doc.roundedRect(PDF_MARGIN_X, layout.brandCardY, layout.contentWidth, layout.brandCardHeight, 3.2, 3.2, 'FD')

  if (logoAsset) {
    const logoPlacement = fitImageToBox(logoAsset, layout.logoFrameWidth - 4, layout.logoFrameHeight - 4)

    doc.setFillColor(255, 255, 255)
    doc.setDrawColor(...tintRgb(primaryColor, 0.6))
    doc.roundedRect(logoFrameX, logoFrameY, layout.logoFrameWidth, layout.logoFrameHeight, 2.6, 2.6, 'FD')
    doc.addImage(
      logoAsset.dataUrl,
      logoAsset.format,
      logoFrameX + 2 + logoPlacement.offsetX,
      logoFrameY + 2 + logoPlacement.offsetY,
      logoPlacement.width,
      logoPlacement.height,
    )
  }

  doc.setFont('helvetica', 'bold')
  doc.setFontSize(16.5)
  doc.setTextColor(...textColor)
  doc.text(layout.brandNameLines, titleX, layout.brandCardY + 11)

  if (layout.taglineLines.length) {
    doc.setFont('helvetica', 'normal')
    doc.setFontSize(9.1)
    doc.text(layout.taglineLines, titleX, layout.brandCardY + 16.2)
  }

  if (layout.contactPanelWidth && layout.contactLines.length) {
    doc.setFillColor(255, 255, 255)
    doc.setDrawColor(...tintRgb(primaryColor, 0.72))
    doc.roundedRect(contactCardX, contactCardY, layout.contactPanelWidth, contactCardHeight, 2.5, 2.5, 'FD')

    doc.setFont('helvetica', 'bold')
    doc.setFontSize(7.2)
    doc.setTextColor(...primaryColor)
    doc.text('Contacto', contactCardX + 3, contactCardY + 4.6)

    doc.setFont('helvetica', 'normal')
    doc.setFontSize(7.5)
    doc.setTextColor(...textColor)
    doc.text(layout.contactLines, contactCardX + 3, contactCardY + 8.5)

    doc.setFontSize(6.9)
    doc.setTextColor(...hexToRgb(branding.primaryColor, DEFAULT_PRIMARY_HEX))
    doc.text(layout.generatedLabel, contactCardX + 3, contactCardY + contactCardHeight - 2.6)
  }

  doc.setFillColor(...reportSurfaceColor)
  doc.roundedRect(PDF_MARGIN_X, layout.reportCardY, layout.contentWidth, layout.reportCardHeight, 3.2, 3.2, 'F')
  doc.setFillColor(...primaryColor)
  doc.rect(PDF_MARGIN_X, layout.reportCardY, 3, layout.reportCardHeight, 'F')

  doc.setFont('helvetica', 'bold')
  doc.setFontSize(7.1)
  doc.setTextColor(...textColor)
  doc.text('REPORTE ADMINISTRATIVO', PDF_MARGIN_X + 6, layout.reportCardY + 5.2)

  doc.setFont('helvetica', 'bold')
  doc.setFontSize(15.2)
  doc.setTextColor(...primaryColor)
  doc.text(layout.titleLines, PDF_MARGIN_X + 6, layout.reportCardY + 11.8)

  let currentY = layout.reportCardY + 11.8 + (layout.titleLines.length * 5.1)

  if (layout.subtitleLines.length) {
    doc.setFont('helvetica', 'normal')
    doc.setFontSize(9)
    doc.setTextColor(...textColor)
    doc.text(layout.subtitleLines, PDF_MARGIN_X + 6, currentY)
    currentY += (layout.subtitleLines.length * 3.8) + 1.2
  }

  if (layout.metaLines.length) {
    const metaPanelY = currentY + 1.2

    doc.setFillColor(255, 255, 255)
    doc.setDrawColor(...tintRgb(primaryColor, 0.75))
    doc.roundedRect(
      PDF_MARGIN_X + 5.5,
      metaPanelY - 3.6,
      layout.contentWidth - 11,
      layout.metaPanelHeight + 1.8,
      2.3,
      2.3,
      'FD',
    )

    doc.setFont('helvetica', 'normal')
    doc.setFontSize(8)
    doc.setTextColor(...textColor)
    doc.text(layout.metaLines, PDF_MARGIN_X + 8, metaPanelY)
  }
}

// Agrega pie de página uniforme para todas las exportaciones PDF del admin.
function drawPdfFooter(doc, branding) {
  const pageCount = doc.getNumberOfPages()
  const pageWidth = doc.internal.pageSize.getWidth()
  const pageHeight = doc.internal.pageSize.getHeight()

  for (let page = 1; page <= pageCount; page += 1) {
    doc.setPage(page)
    doc.setFont('helvetica', 'normal')
    doc.setFontSize(8)
    doc.setTextColor(...hexToRgb(branding.textColor, DEFAULT_TEXT_HEX))
    doc.text(`${branding.storeName} · Página ${page} de ${pageCount}`, pageWidth / 2, pageHeight - 6, { align: 'center' })
  }
}

// Ajusta estilos por columna para que la tabla PDF mantenga legibilidad e imágenes centradas.
function buildPdfColumnStyles(columns) {
  const styles = {}

  columns.forEach((column, index) => {
    styles[index] = {
      halign: column.align || 'left',
    }

    if (column.pdfWidth) {
      styles[index].cellWidth = column.pdfWidth
    }

    if (typeof column.pdfImage === 'function') {
      styles[index].cellWidth = column.pdfWidth || 18
      styles[index].minCellHeight = column.pdfImageSize || 16
      styles[index].halign = 'center'
    }
  })

  return styles
}

// Genera el PDF con la misma plantilla para todas las vistas y agrega miniaturas cuando la columna lo declara.
async function exportPdfFile({ branding, fileBaseName, title, subtitle, columns, rows, meta, landscape = false }) {
  const pdfColumns = columns.filter((column) => isColumnEnabled(column, 'pdf'))
  const metaEntries = normalizeMetaEntries(meta)
  const logoAsset = await loadImageAsset(branding.logoUrl, 'brand').catch(() => null)
  const cellImages = await preloadPdfImages(pdfColumns, rows)
  const doc = new jsPDF({
    orientation: landscape || pdfColumns.length >= 7 ? 'landscape' : 'portrait',
    unit: 'mm',
    format: 'a4',
  })
  const startY = measurePdfStartY(doc, branding, title, subtitle, metaEntries)

  autoTable(doc, {
    startY,
    margin: {
      top: startY,
      left: PDF_MARGIN_X,
      right: PDF_MARGIN_X,
      bottom: PDF_MARGIN_BOTTOM,
    },
    head: [pdfColumns.map((column) => column.header)],
    body: rows.map((row) => pdfColumns.map((column) => (
      typeof column.pdfImage === 'function'
        ? ''
        : resolveColumnTextValue(column, row, 'pdf')
    ))),
    styles: {
      font: 'helvetica',
      fontSize: 8.7,
      textColor: hexToRgb(branding.textColor, DEFAULT_TEXT_HEX),
      lineColor: [228, 236, 244],
      lineWidth: 0.15,
      cellPadding: 2.2,
      valign: 'middle',
    },
    headStyles: {
      fillColor: hexToRgb(branding.primaryColor, DEFAULT_PRIMARY_HEX),
      textColor: [255, 255, 255],
      fontStyle: 'bold',
      lineColor: hexToRgb(branding.primaryColor, DEFAULT_PRIMARY_HEX),
    },
    alternateRowStyles: {
      fillColor: [247, 251, 255],
    },
    columnStyles: buildPdfColumnStyles(pdfColumns),
    didDrawPage: () => {
      drawPdfHeader(doc, branding, title, subtitle, metaEntries, logoAsset)
    },
    didDrawCell: (data) => {
      if (data.section !== 'body') return

      const column = pdfColumns[data.column.index]
      if (typeof column?.pdfImage !== 'function') return

      const asset = cellImages.get(`${data.row.index}:${data.column.index}`)
      if (!asset) return

      const boxSize = Math.min(column.pdfImageSize || PDF_IMAGE_SIZE, data.cell.height - 3)
      const placement = fitImageToBox(asset, boxSize, boxSize)
      const x = data.cell.x + ((data.cell.width - boxSize) / 2) + placement.offsetX
      const y = data.cell.y + ((data.cell.height - boxSize) / 2) + placement.offsetY

      doc.addImage(asset.dataUrl, asset.format, x, y, placement.width, placement.height)
    },
  })

  drawPdfFooter(doc, branding)
  doc.save(`${sanitizeFileBaseName(fileBaseName)}.pdf`)
}

export function useAdminDataExport() {
  const { settings, refreshShellSettings } = useAppShell()
  const { showSnackbar } = useSnackbarSystem()
  const exportingFormat = ref('')

  // Garantiza que las exportaciones salgan con la configuración viva de marca aunque la vista se abra primero.
  async function ensureBranding() {
    const currentSettings = settings.value || {}

    if (!currentSettings.store_name && !currentSettings.brand_logo && !currentSettings.brand_logo_secondary) {
      await refreshShellSettings()
    }

    return buildBrandingSnapshot(settings.value || {})
  }

  // Orquesta exportación, feedback y bloqueo temporal del botón para cualquier vista admin.
  async function exportData({
    format,
    fileBaseName,
    title,
    subtitle,
    sheetName,
    columns,
    rows,
    meta,
    emptyMessage,
    successMessage,
    errorMessage,
    landscape,
  }) {
    if (!Array.isArray(rows) || rows.length === 0) {
      showSnackbar({ type: 'info', message: emptyMessage || 'No hay datos para exportar.' })
      return false
    }

    exportingFormat.value = format

    try {
      const branding = await ensureBranding()

      if (format === 'excel') {
        await exportExcelFile({ branding, fileBaseName, title, subtitle, sheetName, columns, rows, meta })
      } else {
        await exportPdfFile({ branding, fileBaseName, title, subtitle, columns, rows, meta, landscape })
      }

      showSnackbar({
        type: 'success',
        message: successMessage || `Exportación ${format === 'excel' ? 'Excel' : 'PDF'} generada correctamente.`,
      })

      return true
    } catch (error) {
      console.error(error)
      showSnackbar({
        type: 'error',
        message: errorMessage || `No fue posible exportar en ${format === 'excel' ? 'Excel' : 'PDF'}.`,
      })

      return false
    } finally {
      exportingFormat.value = ''
    }
  }

  return {
    exportingFormat,
    exportData,
  }
}