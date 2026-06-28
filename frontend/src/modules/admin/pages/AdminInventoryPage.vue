<!--
  AdminInventoryPage.vue
  Componente principal del módulo de inventario del panel administrativo.
  Muestra el resumen de stock general, listado de productos con su nivel de inventario,
  permite buscar y filtrar por estado (todo, bajo stock, sin stock), exportar datos a Excel/PDF,
  y gestionar el stock de cada variante mediante ajustes y transferencias desde un modal de detalle.
-->
<template>
  <div class="admin-entity-page inventory-page admin-inventory-page">
    <AdminPageHeader
      icon="fas fa-warehouse"
      title="Inventario"
      subtitle="Monitorea stock por producto, revisa variantes y ejecuta ajustes o transferencias sin salir del flujo SPA."
      :breadcrumbs="[{ label: 'Dashboard', to: '/admin' }, { label: 'Inventario' }]"
    />

    <AdminStatsGrid :loading="loading" :count="4" :stats="inventoryStats" />

    <AdminFilterCard
      v-model="search"
      icon="fas fa-filter"
      title="Búsqueda de inventario"
      placeholder="Producto, color, talla o SKU"
      @search="search = search.trim()"
    />

    <div class="admin-tabs inventory-tabs">
      <button type="button" class="admin-tab" :class="{ active: activeTab === 'all' }" @click="activeTab = 'all'">Todo</button>
      <button type="button" class="admin-tab" :class="{ active: activeTab === 'low' }" @click="activeTab = 'low'">Bajo stock</button>
      <button type="button" class="admin-tab" :class="{ active: activeTab === 'out' }" @click="activeTab = 'out'">Sin stock</button>
    </div>

    <AdminResultsBar :text="`Mostrando ${pagination.visibleCount} de ${pagination.totalItems} productos`">
      <template #actions>
        <AdminExportActions
          tone="results"
          :disabled="filteredProducts.length === 0"
          :excel-loading="exportingFormat === 'excel'"
          :pdf-loading="exportingFormat === 'pdf'"
          @excel="exportInventory('excel')"
          @pdf="exportInventory('pdf')"
        />
      </template>
    </AdminResultsBar>

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
            <tr v-for="product in pagination.paginatedItems" :key="product.id">
              <td>
                <AdminTableImage :src="product.image" :alt="product.name" :original-path="product.rawImage" fallback-type="product" variant="square" />
              </td>
              <td>
                <div class="admin-entity-name">
                  <strong>{{ product.name }}</strong>
                  <span>{{ product.variantCount }} variante(s) | {{ product.skuCount }} SKU(s)</span>
                </div>
              </td>
              <td>
                <div class="inventory-summary">
                  <span>{{ product.colorCount }} color(es)</span>
                  <span>{{ product.sizeCount }} talla(s)</span>
                  <span>{{ inventorySummaryAlert(product) }}</span>
                </div>
              </td>
              <td>
                <strong :class="productStockTextClass(product)">{{ product.totalStock }}</strong>
              </td>
              <td>
                <span class="status-badge" :class="statusClass(product.status)">{{ productStatusLabel(product) }}</span>
              </td>
              <td>
                <div class="admin-entity-actions">
                  <button class="action-btn edit" type="button" title="Ajustar inventario" @click="openDetail(product)">
                    <i class="fas fa-sliders-h"></i>
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </AdminCard>

    <AdminPagination
      v-model:page="pagination.currentPage"
      v-model:page-size="pagination.pageSize"
      :total-items="pagination.totalItems"
      :page-size-options="pagination.pageSizeOptions"
    />

    <AdminModal :show="showDetailModal" :title="detailModalTitle" max-width="1180px" @close="closeDetailModal">
      <div class="admin-inventory-page admin-inventory-page--modal">
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
            <div class="inventory-pill inventory-pill--total">
              <p>Stock total</p><strong>{{ selectedProductDetail.totalStock }}</strong>
            </div>
            <div class="inventory-pill inventory-pill--low">
              <p>Bajo stock</p><strong>{{ selectedProductDetail.lowStockCount }}</strong>
            </div>
            <div class="inventory-pill inventory-pill--out">
              <p>Sin stock</p><strong>{{ selectedProductDetail.outOfStockCount }}</strong>
            </div>
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
                <td><strong :class="stockTextClass(variant.quantity, variant)">{{ variant.quantity }}</strong></td>
                <td><span class="status-badge" :class="statusClass(stockStatus(variant.quantity, variant))">{{ statusLabel(stockStatus(variant.quantity, variant)) }}</span></td>
                <td>
                  <div class="admin-entity-actions">
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
              <p>Últimos movimientos registrados para este producto.</p>
            </div>
            <button class="btn btn-secondary btn-sm" type="button" title="Actualizar historial" @click="reloadDetailHistory">
              <i class="fas fa-sync-alt"></i>
            </button>
          </div>

          <AdminEmptyState
            v-if="historyLoading"
            icon="fas fa-clock-rotate-left"
            title="Cargando historial"
            description="Consultando los últimos movimientos registrados."
          />
          <AdminEmptyState
            v-else-if="selectedProductHistory.length === 0"
            icon="fas fa-clock-rotate-left"
            title="Sin movimientos recientes"
            description="Todavía no hay ajustes o transferencias registradas para este producto."
          />
          <div v-else class="table-responsive">
            <table class="dashboard-table">
              <thead>
                <tr>
                  <th>Fecha</th>
                  <th>Variante</th>
                  <th>Operación</th>
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
      </div>
    </AdminModal>

    <AdminModal :show="showAdjustModal" title="Ajustar stock" max-width="520px" @close="closeAdjustModal">
      <div class="admin-inventory-page admin-inventory-page--modal">
      <div class="inventory-form-grid">
        <div class="form-group inventory-form-grid__full">
          <label>Variante seleccionada</label>
          <div class="inventory-variant-preview">{{ selectedVariantLabel }}</div>
        </div>

        <div class="form-group">
          <label for="adjust-action">
            Acción
            <AdminInfoTooltip text="«Agregar» suma unidades al stock actual. «Restar» las descuenta. «Establecer» fija el valor exacto independientemente del stock actual." />
          </label>
          <select id="adjust-action" v-model="adjustForm.action" class="form-control" @change="validateAdjustField('quantity')">
            <option value="set">Establecer cantidad</option>
            <option value="add">Sumar unidades</option>
            <option value="subtract">Restar unidades</option>
          </select>
        </div>

        <div class="form-group">
          <label for="adjust-quantity">
            Cantidad *
            <AdminInfoTooltip text="Número de unidades a agregar, restar o establecer según la acción seleccionada." />
          </label>
          <input id="adjust-quantity" v-model="adjustForm.quantity" type="text" inputmode="numeric" class="form-control" :class="{ 'is-invalid': adjustErrors.quantity }" @input="validateAdjustField('quantity')">
          <p v-if="adjustErrors.quantity" class="form-error">{{ adjustErrors.quantity }}</p>
        </div>

        <div class="form-group inventory-form-grid__full">
          <label for="adjust-reason">
            Motivo
            <AdminInfoTooltip text="Descripción interna del ajuste para trazabilidad del historial. No es visible al cliente." />
          </label>
          <textarea id="adjust-reason" v-model="adjustForm.reason" class="form-control" rows="3"></textarea>
        </div>
      </div>
      </div>

      <template #footer>
        <button class="btn btn-secondary" type="button" @click="closeAdjustModal">Cancelar</button>
        <button class="btn btn-primary" type="button" :disabled="stockSubmitting || Boolean(adjustErrors.quantity)" @click="submitAdjust">
          {{ stockSubmitting ? 'Guardando...' : 'Aplicar ajuste' }}
        </button>
      </template>
    </AdminModal>

    <AdminModal :show="showTransferModal" title="Transferir stock" max-width="560px" @close="closeTransferModal">
      <div class="admin-inventory-page admin-inventory-page--modal">
      <div class="inventory-form-grid">
        <div class="form-group inventory-form-grid__full">
          <label>Variante origen</label>
          <div class="inventory-variant-preview">{{ transferSourceLabel }}</div>
        </div>

        <div class="form-group inventory-form-grid__target">
          <label for="transfer-target">
            Variante destino *
            <AdminInfoTooltip text="Variante a la que se enviarán las unidades de stock desde la variante origen." />
          </label>
          <select id="transfer-target" v-model="transferForm.target_variant_id" class="form-control" :class="{ 'is-invalid': transferErrors.target_variant_id }" @change="validateTransferField('target_variant_id')">
            <option value="">Selecciona una variante</option>
            <option v-for="option in transferTargets" :key="option.id" :value="String(option.id)">
              {{ buildVariantLabel(option) }}
            </option>
          </select>
          <p v-if="transferErrors.target_variant_id" class="form-error">{{ transferErrors.target_variant_id }}</p>
        </div>

        <div class="form-group inventory-form-grid__quantity">
          <label for="transfer-quantity">
            Cantidad *
            <AdminInfoTooltip text="Número de unidades a transferir. No puede superar el stock disponible en la variante origen." />
          </label>
          <input id="transfer-quantity" v-model="transferForm.quantity" type="text" inputmode="numeric" class="form-control" :class="{ 'is-invalid': transferErrors.quantity }" @input="validateTransferField('quantity')">
          <p v-if="transferErrors.quantity" class="form-error">{{ transferErrors.quantity }}</p>
        </div>

        <div class="form-group inventory-form-grid__full">
          <label for="transfer-reason">
            Motivo
            <AdminInfoTooltip text="Descripción del motivo del traslado para trazabilidad del historial. No visible al cliente." />
          </label>
          <textarea id="transfer-reason" v-model="transferForm.reason" class="form-control" rows="3"></textarea>
        </div>
      </div>
      </div>

      <template #footer>
        <button class="btn btn-secondary" type="button" @click="closeTransferModal">Cancelar</button>
        <button class="btn btn-primary" type="button" :disabled="stockSubmitting || Boolean(transferErrors.target_variant_id) || Boolean(transferErrors.quantity)" @click="submitTransfer">
          {{ stockSubmitting ? 'Guardando...' : 'Transferir stock' }}
        </button>
      </template>
    </AdminModal>
  </div>
</template>

<script setup>
/**
 * Script del componente AdminInventoryPage.
 * Orquesta la vista de inventario: carga de datos, filtrado, paginación,
 * exportación, y modales de detalle, ajuste de stock y transferencia entre variantes.
 * Toda la lógica de negocio está delegada en el composable useAdminInventory.
 */

/* Composable principal de inventario — encapsula toda la lógica del módulo */
import { useAdminInventory } from '../composables/useAdminInventory'

/* Componentes compartidos del panel administrativo */
import AdminCard from '../components/AdminCard.vue'
import AdminEmptyState from '../components/AdminEmptyState.vue'
import AdminExportActions from '../components/AdminExportActions.vue'
import AdminFilterCard from '../components/AdminFilterCard.vue'
import AdminInfoTooltip from '../components/AdminInfoTooltip.vue'
import AdminModal from '../components/AdminModal.vue'
import AdminPagination from '../components/AdminPagination.vue'
import AdminPageHeader from '../components/AdminPageHeader.vue'
import AdminResultsBar from '../components/AdminResultsBar.vue'
import AdminStatsGrid from '../components/AdminStatsGrid.vue'
import AdminTableImage from '../components/AdminTableImage.vue'
import AdminTableShimmer from '../components/AdminTableShimmer.vue'

/* Estilos específicos de la vista de inventario */
import '../views/AdminInventoryPage.css'

const {
  activeTab,                // Pestaña activa del filtro: 'all' | 'low' | 'out'
  adjustErrors,             // Errores de validación del formulario de ajuste de stock
  adjustForm,               // Datos del formulario de ajuste (acción, cantidad, motivo)
  buildHistoryVariantLabel, // Función que construye la etiqueta descriptiva de una variante en el historial
  buildVariantLabel,        // Función que construye la etiqueta descriptiva de una variante (color, talla, SKU)
  closeAdjustModal,         // Cierra el modal de ajuste de stock y resetea su estado
  closeDetailModal,         // Cierra el modal de detalle del producto seleccionado
  closeTransferModal,       // Cierra el modal de transferencia de stock
  detailLoading,            // Indica si el detalle del producto se está cargando
  detailModalTitle,         // Título dinámico del modal de detalle del producto
  exportInventory,          // Dispara la exportación de inventario a Excel o PDF
  exportingFormat,          // Formato de exportación en curso ('excel' | 'pdf' | null)
  filteredProducts,         // Lista de productos filtrados según la pestaña y búsqueda activa
  formatDateTime,           // Formatea una fecha ISO a string legible en español
  formatOperation,          // Convierte el código de operación (add/subtract/transfer) a su etiqueta en español
  historyLoading,           // Indica si el historial de movimientos del producto se está cargando
  inventoryStats,           // Arreglo de estadísticas resumen mostradas en el grid superior
  inventorySummaryAlert,    // Función que genera un texto de alerta según el estado de stock del producto
  loading,                  // Estado general de carga de la página (listado y estadísticas)
  openAdjustModal,          // Abre el modal de ajuste de stock para una variante específica
  openDetail,               // Abre el modal de detalle de inventario para un producto seleccionado
  openTransferModal,        // Abre el modal de transferencia de stock para una variante específica
  pagination,               // Objeto de paginación con página actual, ítems por página, total y opciones
  productStatusLabel,       // Función que retorna la etiqueta de estado del producto (activo, inactivo, etc.)
  productStockTextClass,    // Función que retorna la clase CSS del texto de stock según su nivel
  reloadDetailHistory,      // Recarga el historial de movimientos del producto en el modal de detalle
  search,                   // Texto de búsqueda del filtro de inventario (v-model)
  selectedProductDetail,    // Detalle completo del producto seleccionado (variantes, stock, resumen)
  selectedProductHistory,   // Historial de movimientos del producto actualmente seleccionado
  selectedVariantLabel,     // Etiqueta formateada de la variante seleccionada para ajuste o transferencia
  showAdjustModal,          // Controla la visibilidad del modal de ajuste de stock
  showDetailModal,          // Controla la visibilidad del modal de detalle de inventario
  showTransferModal,        // Controla la visibilidad del modal de transferencia de stock
  statusClass,              // Función que retorna la clase CSS de un badge de estado dado
  statusLabel,              // Función que retorna la etiqueta legible de un estado dado
  stockStatus,              // Función que determina el estado de stock ('normal' | 'low' | 'out') según cantidad
  stockSubmitting,          // Indica si un envío de ajuste o transferencia está en curso (doble-envío)
  stockTextClass,           // Función que retorna la clase CSS del texto de stock de una variante
  submitAdjust,             // Envía el formulario de ajuste de stock al servidor
  submitTransfer,           // Envía el formulario de transferencia de stock al servidor
  transferErrors,           // Errores de validación del formulario de transferencia de stock
  transferForm,             // Datos del formulario de transferencia (variante destino, cantidad, motivo)
  transferSourceLabel,      // Etiqueta formateada de la variante origen para la transferencia
  transferTargets,          // Lista de variantes destino candidatas para la transferencia
  validateAdjustField,      // Valida un campo específico del formulario de ajuste en tiempo real
  validateTransferField,    // Valida un campo específico del formulario de transferencia en tiempo real
} = useAdminInventory()
</script>

