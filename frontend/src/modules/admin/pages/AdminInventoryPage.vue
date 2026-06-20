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
import { useAdminInventory } from '../composables/useAdminInventory'
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
import '../views/AdminInventoryPage.css'

const {
  activeTab,
  adjustErrors,
  adjustForm,
  buildHistoryVariantLabel,
  buildVariantLabel,
  closeAdjustModal,
  closeDetailModal,
  closeTransferModal,
  detailLoading,
  detailModalTitle,
  exportInventory,
  exportingFormat,
  filteredProducts,
  formatDateTime,
  formatOperation,
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
  validateAdjustField,
  validateTransferField,
} = useAdminInventory()
</script>

