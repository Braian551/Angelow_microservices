<template>
  <div class="admin-shipping-methods-page">
    <AdminPageHeader
      icon="fas fa-truck"
      title="Definir envíos"
      subtitle="Gestiona el costo base de cada método. Los recargos por rango se administran por separado y se suman como valor adicional."
      :breadcrumbs="[{ label: 'Dashboard', to: '/admin' }, { label: 'Definir envíos' }]"
    >
      <template #actions>
        <AdminExportActions
          tone="header"
          :disabled="filteredMethods.length === 0"
          :excel-loading="exportingFormat === 'excel'"
          :pdf-loading="exportingFormat === 'pdf'"
          @excel="exportMethods('excel')"
          @pdf="exportMethods('pdf')"
        />
        <button class="btn btn-primary" type="button" @click="openCreateModal">
          <i class="fas fa-plus"></i>
          Nuevo método
        </button>
      </template>
    </AdminPageHeader>

    <AdminStatsGrid :loading="loading" :count="4" :stats="shippingStats" />

    <!-- Filtros de métodos de envío -->
    <AdminFilterCard
      icon="fas fa-filter"
      title="Búsqueda y cobertura"
      placeholder="Buscar por nombre, ciudad o descripción..."
      :modelValue="filters.search"
      @update:modelValue="filters.search = $event"
      @search="loadMethods"
    >
      <template #advanced>
        <div class="filters-row filters-row--shipping-methods">
          <div class="filter-group">
            <label for="shipping-method-status"><i class="fas fa-signal"></i> Estado</label>
            <select id="shipping-method-status" v-model="filters.state" class="form-control">
              <option value="all">Todos</option>
              <option value="active">Activos</option>
              <option value="inactive">Inactivos</option>
              <option value="free-threshold">Con envío gratis</option>
            </select>
          </div>

          <div class="filter-group">
            <label for="shipping-method-city"><i class="fas fa-map-marker-alt"></i> Cobertura</label>
            <select id="shipping-method-city" v-model="filters.city" class="form-control">
              <option value="all">Todas</option>
              <option value="national">Sin ciudad fija</option>
              <option v-for="city in availableCities" :key="city" :value="city">{{ city }}</option>
            </select>
          </div>
        </div>

        <div class="admin-filters__actions">
          <div class="admin-filters__count">
            <i class="fas fa-sliders-h"></i>
            <span>{{ activeFilterCount }} {{ activeFilterCount === 1 ? 'filtro activo' : 'filtros activos' }}</span>
          </div>
          <button type="button" class="admin-filters__clear" @click="clearFilters">
            <i class="fas fa-times-circle"></i>
            Limpiar filtros
          </button>
        </div>
      </template>
    </AdminFilterCard>

    <!-- Barra de resultados -->
    <AdminResultsBar :text="`Mostrando ${pagination.visibleCount} de ${pagination.totalItems} métodos`" />

    <AdminCard title="Bandeja de métodos" icon="fas fa-truck" :flush="true">
      <AdminTableShimmer v-if="loading" :rows="5" :columns="['line', 'line', 'line', 'line', 'pill', 'btn']" />
      <AdminEmptyState
        v-else-if="filteredMethods.length === 0"
        icon="fas fa-truck"
        title="Sin métodos de envío"
        description="No hay métodos configurados o ninguno coincide con los filtros activos."
      />
      <div v-else class="table-responsive">
        <table class="dashboard-table shipping-methods-table">
          <thead>
            <tr>
              <th>Método</th>
              <th>Cobertura</th>
              <th>Tiempo</th>
              <th>Costo base del método</th>
              <th>Estado</th>
              <th>Acciones</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="method in pagination.paginatedItems" :key="method.id">
              <td>
                <div class="admin-entity-name">
                  <strong>{{ method.name }}</strong>
                  <span>{{ method.description || 'Sin descripción operativa' }}</span>
                </div>
              </td>
              <td>
                <div class="admin-entity-name">
                  <strong>{{ method.city || 'Cobertura general' }}</strong>
                  <span>{{ method.free_shipping_minimum ? `Gratis desde ${formatCurrency(method.free_shipping_minimum)}` : 'Sin umbral gratis' }}</span>
                </div>
              </td>
              <td>
                <div class="admin-entity-name">
                  <strong>{{ deliveryWindowLabel(method) }}</strong>
                  <span>{{ method.delivery_time || 'Sin promesa adicional' }}</span>
                </div>
              </td>
              <td><strong>{{ formatCurrency(method.base_cost) }}</strong></td>
              <td>
                <span class="status-badge" :class="method.active ? 'active' : 'rejected'">
                  {{ method.active ? 'Activo' : 'Inactivo' }}
                </span>
              </td>
              <td>
                <div class="admin-entity-actions">
                  <button class="action-btn view" type="button" title="Ver detalle" @click="openDetailModal(method)">
                    <i class="fas fa-eye"></i>
                  </button>
                  <button class="action-btn edit" type="button" title="Editar método" @click="openEditModal(method)">
                    <i class="fas fa-edit"></i>
                  </button>
                  <button class="action-btn delete" type="button" title="Eliminar método" @click="confirmDeleteMethod(method)">
                    <i class="fas fa-trash"></i>
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

    <AdminModal :show="showDetailModal" :title="selectedMethod ? selectedMethod.name : 'Detalle del método'" max-width="940px" @close="closeDetailModal">
      <div class="admin-shipping-methods-page admin-shipping-methods-page--modal">
        <template v-if="selectedMethod">
          <div class="shipping-detail-grid admin-detail-grid">
            <AdminCard title="Resumen del método" icon="fas fa-shipping-fast">
              <div class="admin-detail-summary">
                <div class="admin-detail-summary__row"><span>Descripción</span><strong>{{ selectedMethod.description || 'Sin descripción' }}</strong></div>
                <div class="admin-detail-summary__row"><span>Costo base</span><strong>{{ formatCurrency(selectedMethod.base_cost) }}</strong></div>
                <div class="admin-detail-summary__row"><span>Recargos por rango</span><strong>Se suman como cargo adicional</strong></div>
                <div class="admin-detail-summary__row"><span>Promesa</span><strong>{{ deliveryWindowLabel(selectedMethod) }}</strong></div>
                <div class="admin-detail-summary__row"><span>Tiempo libre</span><strong>{{ selectedMethod.delivery_time || 'No definido' }}</strong></div>
                <div class="admin-detail-summary__row"><span>Ciudad</span><strong>{{ selectedMethod.city || 'Cobertura general' }}</strong></div>
                <div class="admin-detail-summary__row"><span>Envío gratis desde</span><strong>{{ selectedMethod.free_shipping_minimum ? formatCurrency(selectedMethod.free_shipping_minimum) : 'No aplica' }}</strong></div>
              </div>
            </AdminCard>

            <AdminCard title="Estado comercial" icon="fas fa-clipboard-check">
              <div class="shipping-method-hero admin-surface-card">
                <p class="shipping-method-hero__label admin-surface-card__label">Método</p>
                <h3>{{ selectedMethod.name }}</h3>
                <p>{{ selectedMethod.city || 'Disponible sin restricción de ciudad' }}</p>
                <span class="status-badge" :class="selectedMethod.active ? 'active' : 'rejected'">{{ selectedMethod.active ? 'Activo' : 'Inactivo' }}</span>
              </div>
            </AdminCard>
          </div>
        </template>
      </div>
      <template #footer>
        <button class="btn btn-secondary" type="button" @click="closeDetailModal">Cerrar</button>
        <button v-if="selectedMethod" class="btn btn-primary" type="button" @click="openEditFromDetail">
          <i class="fas fa-edit"></i>
          Editar método
        </button>
      </template>
    </AdminModal>

    <AdminModal :show="showEditorModal" :title="editingMethodId ? 'Editar método de envío' : 'Nuevo método de envío'" max-width="860px" @close="closeEditorModal">
      <div class="admin-shipping-methods-page admin-shipping-methods-page--modal">
        <div class="editor-grid editor-grid--shipping admin-editor-grid">
          <div>
            <div class="form-group">
              <label for="shipping-method-name">
                Nombre *
                <AdminInfoTooltip text="Nombre visible del método de envío tal como aparece al cliente al finalizar la compra." />
              </label>
              <input id="shipping-method-name" v-model.trim="form.name" type="text" class="form-control" :class="{ 'is-invalid': formErrors.name }" @input="validateField('name')">
              <p v-if="formErrors.name" class="form-error">{{ formErrors.name }}</p>
            </div>

            <div class="form-group">
              <label for="shipping-method-description">
                Descripción
                <AdminInfoTooltip text="Texto interno descriptivo del método. No visible al cliente durante la compra." />
              </label>
              <textarea id="shipping-method-description" v-model.trim="form.description" rows="3" class="form-control" @input="validateField('description')"></textarea>
            </div>

            <div class="form-row">
              <div class="form-group" style="flex: 1;">
                <label for="shipping-base-cost">
                  Costo base *
                  <AdminInfoTooltip text="Tarifa base del método. Los recargos por rango se suman aparte como un valor adicional." />
                </label>
                <input id="shipping-base-cost" v-model.number="form.base_cost" type="number" min="0" class="form-control" :class="{ 'is-invalid': formErrors.base_cost }" @input="validateField('base_cost')">
                <p v-if="formErrors.base_cost" class="form-error">{{ formErrors.base_cost }}</p>
              </div>
              <div class="form-group" style="flex: 1;">
                <label for="shipping-free-threshold">
                  Envío gratis desde
                  <AdminInfoTooltip text="Monto mínimo del pedido a partir del cual el envío es gratuito con este método." />
                </label>
                <input id="shipping-free-threshold" v-model.number="form.free_shipping_minimum" type="number" min="0" class="form-control" :class="{ 'is-invalid': formErrors.free_shipping_minimum }" @input="validateField('free_shipping_minimum')">
                <p v-if="formErrors.free_shipping_minimum" class="form-error">{{ formErrors.free_shipping_minimum }}</p>
              </div>
            </div>

            <p class="shipping-editor-hint">
              Los valores de <strong>Recargos por rango</strong> se aplican como adicional sobre este costo base en checkout.
            </p>
          </div>

          <div>
            <div class="form-row">
              <div class="form-group" style="flex: 1;">
                <label for="shipping-days-min">
                  Días mínimos
                  <AdminInfoTooltip text="Número mínimo de días hábiles estimados para la entrega." />
                </label>
                <input id="shipping-days-min" v-model.number="form.estimated_days_min" type="number" min="1" class="form-control" :class="{ 'is-invalid': formErrors.estimated_days }" @input="validateField('estimated_days')">
              </div>
              <div class="form-group" style="flex: 1;">
                <label for="shipping-days-max">
                  Días máximos
                  <AdminInfoTooltip text="Número máximo de días hábiles estimados para la entrega." />
                </label>
                <input id="shipping-days-max" v-model.number="form.estimated_days_max" type="number" min="1" class="form-control" :class="{ 'is-invalid': formErrors.estimated_days }" @input="validateField('estimated_days')">
              </div>
            </div>
            <p v-if="formErrors.estimated_days" class="form-error">{{ formErrors.estimated_days }}</p>

            <div class="form-group">
              <label for="shipping-delivery-time">
                Texto visible de entrega
                <AdminInfoTooltip text="Mensaje que verá el cliente sobre el tiempo estimado. Ejemplo: «Entrega entre 24 y 48 horas»." />
              </label>
              <input id="shipping-delivery-time" v-model.trim="form.delivery_time" type="text" class="form-control" placeholder="Ej: Entrega entre 24 y 48 horas" @input="validateField('delivery_time')">
              <p v-if="formErrors.delivery_time" class="form-error">{{ formErrors.delivery_time }}</p>
            </div>

            <div class="form-row">
              <div class="form-group" style="flex: 1;">
                <label for="shipping-city">
                  Ciudad
                  <AdminInfoTooltip text="Ciudad o zona de cobertura del método. Dejar vacío si aplica a nivel nacional." />
                </label>
                <input id="shipping-city" v-model.trim="form.city" type="text" class="form-control" placeholder="Ej: Medellín" @input="validateField('city')">
              </div>
              <div class="form-group" style="flex: 1;">
                <label for="shipping-icon">
                  Icono
                  <AdminInfoTooltip text="Ícono representativo del método que se muestra durante la compra." />
                </label>
                <select id="shipping-icon" v-model="form.icon" class="form-control">
                  <option value="fa-truck">Camión</option>
                  <option value="fa-shipping-fast">Rápido</option>
                  <option value="fa-box-open">Paquetería</option>
                  <option value="fa-store">Recogida</option>
                </select>
              </div>
            </div>

            <AdminToggleSwitch
              id="shipping-method-active"
              v-model="form.active"
              title="Método activo"
              description="Si está activo, el método se puede seleccionar durante el checkout."
            />

            <div class="shipping-preview-card admin-surface-card">
              <p class="shipping-preview-card__label admin-surface-card__label">Vista previa del cliente</p>
              <div class="shipping-method-mock-card" :class="{ 'shipping-method-mock-card--active': form.active }">
                <div class="shipping-method-mock-icon">
                  <i :class="`fas ${form.icon || 'fa-truck'}`"></i>
                </div>
                <div class="shipping-method-mock-copy">
                  <h4>{{ form.name || 'Nombre del método' }}</h4>
                  <p>{{ form.description || 'Entrega segura con seguimiento de tu pedido.' }}</p>
                  <span v-if="form.delivery_time" class="shipping-method-mock-time">
                    <i class="fas fa-clock"></i>
                    {{ form.delivery_time }}
                  </span>
                  <div class="shipping-method-mock-breakdown">
                    <span>Costo base: {{ form.base_cost > 0 ? formatCurrency(form.base_cost) : 'Gratis' }}</span>
                    <span class="shipping-method-mock-breakdown__hint">+ recargo adicional según rango del pedido</span>
                  </div>
                </div>
                <div class="shipping-method-mock-cost">
                  <small>Total envío</small>
                  <strong>{{ form.base_cost > 0 ? formatCurrency(form.base_cost) : 'Gratis' }}</strong>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <template #footer>
        <button class="btn btn-secondary" type="button" @click="closeEditorModal">Cancelar</button>
        <button class="btn btn-primary" type="button" @click="saveMethod">
          <i class="fas fa-save"></i>
          {{ editingMethodId ? 'Guardar cambios' : 'Crear método' }}
        </button>
      </template>
    </AdminModal>
  </div>
</template>

<script setup>
// =====================================================
// Imports de la vista y componentes compartidos
// =====================================================
import '../views/AdminShippingMethodsPage.css'
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
import AdminTableShimmer from '../components/AdminTableShimmer.vue'
import AdminToggleSwitch from '../components/AdminToggleSwitch.vue'
import { useAdminShippingMethods } from '../composables/useAdminShippingMethods'

// =====================================================
// Orquestación de la lógica de métodos de envío
// =====================================================
const {
  activeFilterCount,
  availableCities,
  clearFilters,
  closeDetailModal,
  closeEditorModal,
  confirmDeleteMethod,
  deliveryWindowLabel,
  editingMethodId,
  exportMethods,
  exportingFormat,
  filteredMethods,
  filters,
  form,
  formErrors,
  formatCurrency,
  loadMethods,
  loading,
  openCreateModal,
  openDetailModal,
  openEditFromDetail,
  openEditModal,
  pagination,
  saveMethod,
  selectedMethod,
  shippingStats,
  showDetailModal,
  showEditorModal,
  validateField,
} = useAdminShippingMethods()
</script>
