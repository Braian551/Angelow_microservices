<template>
  <div class="admin-discount-codes-page">
    <AdminPageHeader
      icon="fas fa-tags"
      title="Códigos de descuento"
      subtitle="Gestiona promociones con detalles claros, control de vigencia y seguimiento del estado."
      :breadcrumbs="[{ label: 'Dashboard', to: '/admin' }, { label: 'Códigos de descuento' }]"
    >
      <template #actions>
        <button class="btn btn-secondary" type="button" @click="exportCodes">
          <i class="fas fa-file-export"></i>
          Exportar
        </button>
        <button class="btn btn-secondary" type="button" @click="openMassCampaignModal">
          <i class="fas fa-bullhorn"></i>
          Envío masivo
        </button>
        <button class="btn btn-secondary" type="button" @click="navigateToSpecificCampaignPage">
          <i class="fas fa-user-check"></i>
          Usuarios específicos
        </button>
        <button class="btn btn-primary" type="button" @click="openCreateModal">
          <i class="fas fa-plus"></i>
          Nuevo código
        </button>
      </template>
    </AdminPageHeader>

    <AdminStatsGrid :loading="loading" :count="4" :stats="discountStats" />

    <AdminFilterCard
      v-model="filters.search"
      icon="fas fa-filter"
      title="Filtros y control"
      placeholder="Buscar por código o tipo..."
      @search="() => {}"
    >
      <template #advanced>
        <div class="admin-filters__row">
          <div class="admin-filters__group">
            <label for="discount-code-status"><i class="fas fa-signal"></i> Estado</label>
            <select id="discount-code-status" v-model="filters.state">
              <option value="all">Todos</option>
              <option value="active">Activos</option>
              <option value="inactive">Inactivos</option>
              <option value="expired">Vencidos</option>
              <option value="single-use">Uso único</option>
            </select>
          </div>

          <div class="admin-filters__group">
            <label for="discount-code-type"><i class="fas fa-percent"></i> Tipo</label>
            <select id="discount-code-type" v-model="filters.type">
              <option value="all">Todos</option>
              <option value="percent">Porcentaje</option>
              <option value="fixed">Monto fijo</option>
            </select>
          </div>
        </div>

        <div class="admin-filters__actions">
          <div class="admin-filters__active">
            <i class="fas fa-sliders-h"></i>
            <span>{{ activeFilterCount }} {{ activeFilterCount === 1 ? 'filtro activo' : 'filtros activos' }}</span>
          </div>
          <div class="admin-filters__actions-buttons">
            <button type="button" class="admin-filters__clear" @click="clearFilters">
              <i class="fas fa-times-circle"></i> Limpiar filtros
            </button>
          </div>
        </div>
      </template>
    </AdminFilterCard>

    <AdminResultsBar :text="`Mostrando ${pagination.visibleCount} de ${pagination.totalItems} códigos`" />

    <AdminCard title="Bandeja de códigos" icon="fas fa-tags" :flush="true">
      <AdminTableShimmer v-if="loading" :rows="5" :columns="['line', 'pill', 'line', 'line', 'line', 'pill', 'btn']" />
      <AdminEmptyState
        v-else-if="filteredCodes.length === 0"
        icon="fas fa-tags"
        title="Sin códigos"
        description="Aún no hay códigos de descuento o ninguno coincide con los filtros activos."
      />
      <div v-else class="table-responsive">
        <table class="dashboard-table discount-codes-table">
          <thead>
            <tr>
              <th>Código</th>
              <th>Tipo</th>
              <th>Valor</th>
              <th>Usos</th>
              <th>Vigencia</th>
              <th>Estado</th>
              <th>Acciones</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="code in pagination.paginatedItems" :key="code.id">
              <td>
                <div class="admin-entity-name">
                  <strong class="discount-code-pill">{{ code.code }}</strong>
                  <span>{{ code.is_single_use ? 'Uso único' : 'Uso múltiple' }}</span>
                </div>
              </td>
              <td><span class="status-badge info">{{ code.type_label }}</span></td>
              <td><strong>{{ formatDiscountValue(code) }}</strong></td>
              <td>
                <div class="admin-entity-name">
                  <strong>{{ code.times_used }} / {{ code.max_uses || '∞' }}</strong>
                  <span>{{ code.max_uses ? remainingUsesLabel(code) : 'Sin límite de uso' }}</span>
                </div>
              </td>
              <td>
                <div class="admin-entity-name">
                  <strong>{{ code.start_date ? formatDateTime(code.start_date) : 'Inmediato' }}</strong>
                  <span>{{ code.expires_at ? `Vence ${formatDateTime(code.expires_at)}` : 'Sin expiración' }}</span>
                </div>
              </td>
              <td><span class="status-badge" :class="codeStatusClass(code)">{{ codeStatusLabel(code) }}</span></td>
              <td>
                <div class="admin-entity-actions">
                  <button class="action-btn view" type="button" title="Ver detalle" @click="openDetailModal(code)">
                    <i class="fas fa-eye"></i>
                  </button>
                  <button class="action-btn edit" type="button" title="Editar código" @click="openEditModal(code)">
                    <i class="fas fa-edit"></i>
                  </button>
                  <button class="action-btn delete" type="button" title="Eliminar código" @click="confirmDeleteCode(code)">
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

    <AdminModal :show="showDetailModal" :title="selectedCode ? `Código ${selectedCode.code}` : 'Detalle del código'" max-width="960px" @close="closeDetailModal">
      <template v-if="selectedCode">
        <div class="discount-detail-grid admin-detail-grid">
          <div>
            <AdminCard title="Resumen promocional" icon="fas fa-ticket-alt">
              <div class="discount-hero-card admin-surface-card">
                <p class="discount-hero-card__label admin-surface-card__label">Código</p>
                <h3>{{ selectedCode.code }}</h3>
                <p class="discount-hero-card__value admin-surface-card__value">{{ formatDiscountValue(selectedCode) }}</p>
                <span class="status-badge" :class="codeStatusClass(selectedCode)">{{ codeStatusLabel(selectedCode) }}</span>
              </div>
            </AdminCard>
          </div>

          <div>
            <AdminCard title="Configuración" icon="fas fa-cogs">
              <div class="admin-detail-summary">
                <div class="admin-detail-summary__row"><span>Tipo</span><strong>{{ selectedCode.type_label }}</strong></div>
                <div class="admin-detail-summary__row"><span>Usos máximos</span><strong>{{ selectedCode.max_uses || 'Ilimitados' }}</strong></div>
                <div class="admin-detail-summary__row"><span>Usos realizados</span><strong>{{ selectedCode.times_used }}</strong></div>
                <div class="admin-detail-summary__row"><span>Inicio</span><strong>{{ selectedCode.start_date ? formatDateTime(selectedCode.start_date) : 'Inmediato' }}</strong></div>
                <div class="admin-detail-summary__row"><span>Expira</span><strong>{{ selectedCode.expires_at ? formatDateTime(selectedCode.expires_at) : 'Sin fecha' }}</strong></div>
                <div class="admin-detail-summary__row"><span>Modo</span><strong>{{ selectedCode.is_single_use ? 'Uso único' : 'Uso repetible' }}</strong></div>
              </div>
            </AdminCard>
          </div>
        </div>
      </template>
      <template #footer>
        <button class="btn btn-secondary" type="button" @click="closeDetailModal">Cerrar</button>
        <button v-if="selectedCode" class="btn btn-primary" type="button" @click="openEditFromDetail">
          <i class="fas fa-edit"></i>
          Editar código
        </button>
      </template>
    </AdminModal>

    <AdminModal :show="showEditorModal" :title="editingCodeId ? 'Editar código' : 'Nuevo código'" max-width="760px" @close="closeEditorModal">
      <div class="editor-grid editor-grid--discounts admin-editor-grid">
        <div>
          <div class="form-group">
            <label for="discount-code-field">
              Código *
              <AdminInfoTooltip text="Palabra clave que el cliente escribe al finalizar la compra. Solo mayúsculas, números y guiones. Entre 4 y 20 caracteres." />
            </label>
            <input id="discount-code-field" v-model.trim="form.code" type="text" class="form-control" :class="{ 'is-invalid': formErrors.code }" @input="handleCodeInput">
            <p v-if="formErrors.code" class="form-error">{{ formErrors.code }}</p>
          </div>

          <div class="form-row">
            <div class="form-group" style="flex: 1;">
              <label for="discount-type-field">
                Tipo *
                <AdminInfoTooltip text="«Porcentaje» rebaja un % del subtotal. «Monto fijo» descuenta una cifra exacta en pesos." />
              </label>
              <select id="discount-type-field" v-model="form.type" class="form-control" @change="validateField('type')">
                <option value="percent">Porcentaje</option>
                <option value="fixed">Monto fijo</option>
              </select>
            </div>
            <div class="form-group" style="flex: 1;">
              <label for="discount-value-field">
                Valor *
                <AdminInfoTooltip text="Número que representa el descuento: porcentaje (Ej. 15 = 15%) o monto en pesos (Ej. 5000 = $5.000)." />
              </label>
              <input id="discount-value-field" v-model.number="form.value" type="number" min="1" class="form-control" :class="{ 'is-invalid': formErrors.value }" @input="validateField('value')">
              <p v-if="formErrors.value" class="form-error">{{ formErrors.value }}</p>
            </div>
          </div>

          <div class="form-row">
            <div class="form-group" style="flex: 1;">
              <label for="discount-max-uses">
                Usos máximos
                <AdminInfoTooltip text="Cantidad de veces que se puede usar este código en total. Dejar vacío para uso ilimitado." />
              </label>
              <input id="discount-max-uses" v-model.number="form.max_uses" type="number" min="1" class="form-control" :class="{ 'is-invalid': formErrors.max_uses }" @input="validateField('max_uses')">
              <p v-if="formErrors.max_uses" class="form-error">{{ formErrors.max_uses }}</p>
            </div>
            <div class="form-group" style="flex: 1; display: flex; align-items: flex-end;">
              <AdminToggleSwitch
                id="discount-single-use"
                v-model="form.is_single_use"
                layout="inline"
                label="Uso único por cliente"
              />
            </div>
          </div>
        </div>

        <div>
          <div class="form-group">
            <label for="discount-start-date">
              Fecha de inicio
              <AdminInfoTooltip text="Fecha y hora desde cuando el código es válido. Dejar vacío para que sea efectivo inmediatamente." />
            </label>
            <input id="discount-start-date" v-model="form.start_date" type="datetime-local" class="form-control" :class="{ 'is-invalid': formErrors.start_date }" @change="validateField('start_date')">
            <p v-if="formErrors.start_date" class="form-error">{{ formErrors.start_date }}</p>
          </div>

          <div class="form-group">
            <label for="discount-end-date">
              Fecha de expiración
              <AdminInfoTooltip text="Fecha y hora en que el código deja de ser válido. Dejar vacío para que no expire." />
            </label>
            <input id="discount-end-date" v-model="form.expires_at" type="datetime-local" class="form-control" :class="{ 'is-invalid': formErrors.expires_at }" @change="validateField('expires_at')">
            <p v-if="formErrors.expires_at" class="form-error">{{ formErrors.expires_at }}</p>
          </div>

          <div class="form-group">
            <AdminToggleSwitch
              id="discount-code-active"
              v-model="form.active"
              layout="inline"
              label="Código activo"
            />
          </div>

          <div class="discount-preview-card admin-surface-card">
            <p class="discount-preview-card__label admin-surface-card__label">Vista previa</p>
            <h3>{{ form.code || 'PROMO' }}</h3>
            <p>{{ form.type === 'percent' ? `${Number(form.value || 0)}% de descuento` : `${formatCurrency(form.value || 0)} de descuento` }}</p>
            <span class="status-badge" :class="form.active ? 'active' : 'rejected'">{{ form.active ? 'Activo' : 'Inactivo' }}</span>
          </div>
        </div>
      </div>

      <template #footer>
        <button class="btn btn-secondary" type="button" @click="closeEditorModal">Cancelar</button>
        <button class="btn btn-primary" type="button" @click="saveCode">
          <i class="fas fa-save"></i>
          {{ editingCodeId ? 'Guardar cambios' : 'Crear código' }}
        </button>
      </template>
    </AdminModal>

    <AdminModal :show="showMassCampaignModal" title="Envío masivo de descuentos" max-width="700px" @close="closeMassCampaignModal">
      <div class="campaign-modal">
        <p class="campaign-modal__intro">
          Envía un código de descuento a todos los clientes con notificación interna, correo o ambos canales.
        </p>

        <div class="form-group">
          <label for="mass-campaign-code">Código de descuento *</label>
          <select id="mass-campaign-code" v-model="massCampaignForm.discount_code_id" class="form-control" :class="{ 'is-invalid': massCampaignErrors.discount_code_id }" @change="validateMassCampaignField('discount_code_id')">
            <option value="">Selecciona un código</option>
            <option v-for="code in campaignCodeOptions" :key="`mass-${code.id}`" :value="String(code.id)">
              {{ code.code }} - {{ formatDiscountValue(code) }}
            </option>
          </select>
          <p v-if="massCampaignErrors.discount_code_id" class="form-error">{{ massCampaignErrors.discount_code_id }}</p>
        </div>

        <div class="campaign-channels">
          <label class="campaign-checkbox">
            <input v-model="massCampaignForm.send_notification" type="checkbox" @change="validateMassCampaignField('channels')">
            <span>Enviar notificación interna</span>
          </label>
          <label class="campaign-checkbox">
            <input v-model="massCampaignForm.send_email" type="checkbox" @change="validateMassCampaignField('channels')">
            <span>Enviar correo con PDF adjunto</span>
          </label>
          <p v-if="massCampaignErrors.channels" class="form-error">{{ massCampaignErrors.channels }}</p>
        </div>
      </div>

      <template #footer>
        <button class="btn btn-secondary" type="button" :disabled="campaignSubmitting" @click="closeMassCampaignModal">Cancelar</button>
        <button class="btn btn-primary" type="button" :disabled="campaignSubmitting" @click="submitMassCampaign">
          <i class="fas fa-paper-plane"></i>
          {{ campaignSubmitting ? 'Enviando...' : 'Enviar masivo' }}
        </button>
      </template>
    </AdminModal>

    <AdminModal :show="showSpecificCampaignModal" title="Descuento para usuarios específicos" max-width="1180px" @close="closeSpecificCampaignModal">
      <div class="specific-campaign-modal">

        <!-- Sección superior: selector de código y canales de envío -->
        <div class="specific-campaign-top">

          <!-- Columna izquierda: selector + vista previa del código -->
          <div class="specific-campaign-code-col">
            <div class="form-group">
              <label for="specific-campaign-code" class="specific-campaign-label">
                <i class="fas fa-tag"></i> Código de descuento *
              </label>
              <select
                id="specific-campaign-code"
                v-model="specificCampaignForm.discount_code_id"
                class="form-control"
                :class="{ 'is-invalid': specificCampaignErrors.discount_code_id }"
                @change="validateSpecificCampaignField('discount_code_id')"
              >
                <option value="">Selecciona un código</option>
                <option v-for="code in campaignCodeOptions" :key="`specific-${code.id}`" :value="String(code.id)">
                  {{ code.code }} — {{ formatDiscountValue(code) }}
                </option>
              </select>
              <p v-if="specificCampaignErrors.discount_code_id" class="form-error">{{ specificCampaignErrors.discount_code_id }}</p>
            </div>

            <!-- Vista previa del código seleccionado -->
            <transition name="campaign-preview-fade">
              <div v-if="selectedSpecificCode" class="campaign-code-preview">
                <div class="campaign-code-preview__icon" :class="selectedSpecificCode.type === 'percent' ? 'is-percent' : 'is-fixed'">
                  <i :class="selectedSpecificCode.type === 'percent' ? 'fas fa-percent' : 'fas fa-tag'"></i>
                </div>
                <div class="campaign-code-preview__body">
                  <strong class="campaign-code-preview__code">{{ selectedSpecificCode.code }}</strong>
                  <span class="campaign-code-preview__value">{{ formatDiscountValue(selectedSpecificCode) }} de descuento</span>
                  <span class="campaign-code-preview__meta">
                    <i class="fas fa-calendar-alt"></i>
                    {{ selectedSpecificCode.expires_at ? `Expira el ${formatShortDate(selectedSpecificCode.expires_at)}` : 'Sin fecha de expiración' }}
                  </span>
                  <span class="campaign-code-preview__meta">
                    <i class="fas fa-chart-bar"></i>
                    {{ selectedSpecificCode.times_used || 0 }} uso{{ selectedSpecificCode.times_used !== 1 ? 's' : '' }}
                    {{ selectedSpecificCode.max_uses ? `/ ${selectedSpecificCode.max_uses} máx.` : '(sin límite)' }}
                  </span>
                </div>
              </div>
            </transition>
          </div>

          <!-- Columna derecha: canales de envío como tarjetas interactivas -->
          <div class="specific-campaign-channels-col">
            <p class="specific-campaign-label">
              <i class="fas fa-paper-plane"></i>
              Canales de envío
              <AdminInfoTooltip text="Selecciona cómo se enviará el descuento. Puedes activar una o ambas opciones." />
            </p>
            <div
              class="campaign-channel-card"
              :class="{ 'is-active': specificCampaignForm.send_notification }"
              @click="specificCampaignForm.send_notification = !specificCampaignForm.send_notification; validateSpecificCampaignField('channels')"
            >
              <div class="campaign-channel-card__icon campaign-channel-card__icon--notif">
                <i class="fas fa-bell"></i>
              </div>
              <div class="campaign-channel-card__info">
                <strong>Notificación interna</strong>
                <span>Aparece en el panel del cliente</span>
              </div>
              <div class="campaign-channel-card__toggle">
                <input
                  v-model="specificCampaignForm.send_notification"
                  type="checkbox"
                  @change="validateSpecificCampaignField('channels')"
                  @click.stop
                >
              </div>
            </div>
            <div
              class="campaign-channel-card"
              :class="{ 'is-active': specificCampaignForm.send_email }"
              @click="specificCampaignForm.send_email = !specificCampaignForm.send_email; validateSpecificCampaignField('channels')"
            >
              <div class="campaign-channel-card__icon campaign-channel-card__icon--email">
                <i class="fas fa-envelope"></i>
              </div>
              <div class="campaign-channel-card__info">
                <strong>Correo electrónico</strong>
                <span>Con código PDF adjunto</span>
              </div>
              <div class="campaign-channel-card__toggle">
                <input
                  v-model="specificCampaignForm.send_email"
                  type="checkbox"
                  @change="validateSpecificCampaignField('channels')"
                  @click.stop
                >
              </div>
            </div>
            <p v-if="specificCampaignErrors.channels" class="form-error">{{ specificCampaignErrors.channels }}</p>
          </div>
        </div>

        <AdminFilterCard
          v-model="specificCampaignSearch"
          icon="fas fa-users"
          title="Destinatarios"
          placeholder="Buscar por nombre o correo..."
          :initially-expanded="true"
          :hide-toggle="true"
          @search="() => {}"
        >
          <div class="specific-campaign-users-panel">
            <div class="specific-campaign-users-header__title">
              <i class="fas fa-users"></i>
              <span>Clientes disponibles</span>
              <AdminInfoTooltip text="Solo se muestran clientes con cuenta activa. Filtra por nombre o correo para encontrar usuarios específicos." />
              <span
                class="specific-campaign-badge"
                :class="{ 'is-filled': specificCampaignForm.user_ids.length > 0 }"
              >
                {{ specificCampaignForm.user_ids.length }}
                {{ specificCampaignForm.user_ids.length === 1 ? 'seleccionado' : 'seleccionados' }}
              </span>
            </div>
            <div class="specific-campaign-users-header__actions">
              <button
                type="button"
                class="campaign-action-chip"
                title="Seleccionar todos los visibles"
                @click="selectAllFilteredCustomers"
              >
                <i class="fas fa-check-double"></i>
                <span>Todos</span>
              </button>
              <button
                type="button"
                class="campaign-action-chip campaign-action-chip--clear"
                title="Limpiar selección"
                :disabled="specificCampaignForm.user_ids.length === 0"
                @click="clearSpecificCustomerSelection"
              >
                <i class="fas fa-ban"></i>
                <span>Limpiar</span>
              </button>
            </div>
          </div>
        </AdminFilterCard>

        <!-- Lista de usuarios -->
        <div class="campaign-users-list campaign-users-list--specific">
          <div v-if="campaignCustomersLoading" class="campaign-users-list__state">
            <i class="fas fa-spinner fa-spin"></i> Cargando clientes…
          </div>
          <div v-else-if="filteredCampaignCustomers.length === 0" class="campaign-users-list__state">
            <i class="fas fa-user-slash"></i> No hay clientes para mostrar.
          </div>
          <label
            v-for="customer in filteredCampaignCustomers"
            v-else
            :key="customer.id"
            class="campaign-user-item"
            :class="{ 'is-selected': specificCampaignForm.user_ids.includes(String(customer.id)) }"
          >
            <input
              v-model="specificCampaignForm.user_ids"
              type="checkbox"
              :value="String(customer.id)"
              @change="validateSpecificCampaignField('user_ids')"
            >
            <div class="campaign-user-avatar">{{ userInitials(customer) }}</div>
            <div class="campaign-user-item__meta">
              <strong>{{ customer.name || 'Cliente' }}</strong>
              <span>{{ customer.email || 'Sin correo registrado' }}</span>
            </div>
            <i
              v-if="specificCampaignForm.user_ids.includes(String(customer.id))"
              class="fas fa-check campaign-user-item__checkmark"
            ></i>
          </label>
        </div>
        <p v-if="specificCampaignErrors.user_ids" class="form-error">{{ specificCampaignErrors.user_ids }}</p>
      </div>

      <template #footer>
        <button class="btn btn-secondary" type="button" :disabled="campaignSubmitting" @click="closeSpecificCampaignModal">Cancelar</button>
        <button
          class="btn btn-primary"
          type="button"
          :disabled="campaignSubmitting || specificCampaignForm.user_ids.length === 0"
          @click="submitSpecificCampaign"
        >
          <i :class="campaignSubmitting ? 'fas fa-spinner fa-spin' : 'fas fa-paper-plane'"></i>
          {{ campaignSubmitting
            ? 'Enviando…'
            : specificCampaignForm.user_ids.length > 0
              ? `Enviar a ${specificCampaignForm.user_ids.length} usuario${specificCampaignForm.user_ids.length !== 1 ? 's' : ''}`
              : 'Enviar a seleccionados'
          }}
        </button>
      </template>
    </AdminModal>
  </div>
</template>

<script setup>
import { computed, onMounted, reactive, ref } from 'vue'
import { useRouter } from 'vue-router'
import { discountHttp } from '../../../services/http'
import { useAlertSystem } from '../../../composables/useAlertSystem'
import { useSnackbarSystem } from '../../../composables/useSnackbarSystem'
import { useAdminPagination } from '../composables/useAdminPagination'
import AdminCard from '../components/AdminCard.vue'
import AdminEmptyState from '../components/AdminEmptyState.vue'
import AdminFilterCard from '../components/AdminFilterCard.vue'
import AdminInfoTooltip from '../components/AdminInfoTooltip.vue'
import AdminModal from '../components/AdminModal.vue'
import AdminPagination from '../components/AdminPagination.vue'
import AdminPageHeader from '../components/AdminPageHeader.vue'
import AdminResultsBar from '../components/AdminResultsBar.vue'
import AdminStatsGrid from '../components/AdminStatsGrid.vue'
import AdminTableShimmer from '../components/AdminTableShimmer.vue'
import AdminToggleSwitch from '../components/AdminToggleSwitch.vue'

const { showAlert } = useAlertSystem()
const { showSnackbar } = useSnackbarSystem()
const router = useRouter()

const loading = ref(true)
const codes = ref([])
const selectedCode = ref(null)
const showDetailModal = ref(false)
const showEditorModal = ref(false)
const editingCodeId = ref(null)
const showMassCampaignModal = ref(false)
const showSpecificCampaignModal = ref(false)
const campaignSubmitting = ref(false)
const campaignCustomersLoading = ref(false)
const campaignCustomers = ref([])
const specificCampaignSearch = ref('')

const filters = reactive({ search: '', state: 'all', type: 'all' })

const form = reactive({
  code: '',
  type: 'percent',
  value: 10,
  max_uses: null,
  start_date: '',
  expires_at: '',
  active: true,
  is_single_use: false,
})

const formErrors = reactive({ code: '', type: '', value: '', max_uses: '', start_date: '', expires_at: '' })

const massCampaignForm = reactive({
  discount_code_id: '',
  send_notification: true,
  send_email: true,
})

const massCampaignErrors = reactive({
  discount_code_id: '',
  channels: '',
})

const specificCampaignForm = reactive({
  discount_code_id: '',
  send_notification: true,
  send_email: true,
  user_ids: [],
})

const specificCampaignErrors = reactive({
  discount_code_id: '',
  channels: '',
  user_ids: '',
})

const filteredCodes = computed(() => {
  const term = filters.search.trim().toLowerCase()

  return codes.value.filter((code) => {
    if (filters.type !== 'all' && code.type !== filters.type) return false
    if (filters.state !== 'all' && codeStatusKey(code) !== filters.state) return false
    if (!term) return true

    return [code.code, code.type_label, code.discount_type_name].join(' ').toLowerCase().includes(term)
  })
})

const pagination = useAdminPagination(filteredCodes, {
  initialPageSize: 10,
  pageSizeOptions: [10, 20, 50],
})

const activeFilterCount = computed(() => [filters.search, filters.state !== 'all', filters.type !== 'all'].filter(Boolean).length)

const discountStats = computed(() => [
  { key: 'total', label: 'Total códigos', value: codes.value.length, icon: 'fas fa-tags', color: 'primary' },
  { key: 'active', label: 'Activos', value: codes.value.filter((code) => codeStatusKey(code) === 'active').length, icon: 'fas fa-check-circle', color: 'success' },
  { key: 'expired', label: 'Vencidos', value: codes.value.filter((code) => codeStatusKey(code) === 'expired').length, icon: 'fas fa-calendar-times', color: 'warning' },
  { key: 'single', label: 'Uso único', value: codes.value.filter((code) => code.is_single_use).length, icon: 'fas fa-user-shield', color: 'info' },
])

const campaignCodeOptions = computed(() => codes.value.map((code) => ({
  id: code.id,
  code: code.code,
  type: code.type,
  value: code.value,
})))

// Código completo seleccionado en campaña específica (para vista previa)
const selectedSpecificCode = computed(() =>
  codes.value.find((c) => String(c.id) === String(specificCampaignForm.discount_code_id)) || null
)

const filteredCampaignCustomers = computed(() => {
  const term = specificCampaignSearch.value.trim().toLowerCase()

  if (!term) return campaignCustomers.value

  return campaignCustomers.value.filter((customer) => [customer.name, customer.email].join(' ').toLowerCase().includes(term))
})

function resetForm() {
  form.code = ''
  form.type = 'percent'
  form.value = 10
  form.max_uses = null
  form.start_date = ''
  form.expires_at = ''
  form.active = true
  form.is_single_use = false
  clearErrors()
}

function clearErrors() {
  Object.keys(formErrors).forEach((key) => {
    formErrors[key] = ''
  })
}

function clearFilters() {
  filters.search = ''
  filters.state = 'all'
  filters.type = 'all'
}

function openCreateModal() {
  editingCodeId.value = null
  resetForm()
  showEditorModal.value = true
}

function navigateToSpecificCampaignPage() {
  router.push({ name: 'admin-discount-codes-specific-campaign' })
}

function openEditModal(code) {
  editingCodeId.value = code.id
  clearErrors()
  form.code = code.code || ''
  form.type = code.type || 'percent'
  form.value = Number(code.value || 0)
  form.max_uses = code.max_uses ?? null
  form.start_date = normalizeDateTimeInput(code.start_date)
  form.expires_at = normalizeDateTimeInput(code.expires_at)
  form.active = Boolean(code.active)
  form.is_single_use = Boolean(code.is_single_use)
  showEditorModal.value = true
}

function closeEditorModal() {
  showEditorModal.value = false
  editingCodeId.value = null
  resetForm()
}

function openDetailModal(code) {
  selectedCode.value = code
  showDetailModal.value = true
}

function closeDetailModal() {
  selectedCode.value = null
  showDetailModal.value = false
}

function openEditFromDetail() {
  if (!selectedCode.value) return
  const current = selectedCode.value
  closeDetailModal()
  openEditModal(current)
}

function defaultCampaignCodeId() {
  const firstCode = campaignCodeOptions.value[0]
  return firstCode ? String(firstCode.id) : ''
}

function resetMassCampaignForm() {
  massCampaignForm.discount_code_id = defaultCampaignCodeId()
  massCampaignForm.send_notification = true
  massCampaignForm.send_email = true
  massCampaignErrors.discount_code_id = ''
  massCampaignErrors.channels = ''
}

function resetSpecificCampaignForm() {
  specificCampaignForm.discount_code_id = defaultCampaignCodeId()
  specificCampaignForm.send_notification = true
  specificCampaignForm.send_email = true
  specificCampaignForm.user_ids = []
  specificCampaignErrors.discount_code_id = ''
  specificCampaignErrors.channels = ''
  specificCampaignErrors.user_ids = ''
  specificCampaignSearch.value = ''
}

function openMassCampaignModal() {
  resetMassCampaignForm()
  showMassCampaignModal.value = true
}

function closeMassCampaignModal() {
  showMassCampaignModal.value = false
  resetMassCampaignForm()
}

async function openSpecificCampaignModal() {
  resetSpecificCampaignForm()
  showSpecificCampaignModal.value = true
  await loadCampaignCustomers()
}

function closeSpecificCampaignModal() {
  showSpecificCampaignModal.value = false
  resetSpecificCampaignForm()
}

async function loadCampaignCustomers() {
  campaignCustomersLoading.value = true
  try {
    const { data } = await discountHttp.get('/admin/discount-codes/campaign/customers')
    campaignCustomers.value = Array.isArray(data?.data) ? data.data : []
  } catch (error) {
    campaignCustomers.value = []
    showSnackbar({ type: 'error', message: extractErrorMessage(error, 'No se pudieron cargar los clientes para la campaña.') })
  } finally {
    campaignCustomersLoading.value = false
  }
}

function validateMassCampaignField(field) {
  if (field === 'discount_code_id') {
    massCampaignErrors.discount_code_id = massCampaignForm.discount_code_id ? '' : 'Selecciona un código para el envío masivo.'
    return
  }

  if (field === 'channels') {
    massCampaignErrors.channels = massCampaignForm.send_notification || massCampaignForm.send_email
      ? ''
      : 'Activa al menos un canal de envío (notificación o correo).'
  }
}

function validateSpecificCampaignField(field) {
  if (field === 'discount_code_id') {
    specificCampaignErrors.discount_code_id = specificCampaignForm.discount_code_id ? '' : 'Selecciona un código para el envío.'
    return
  }

  if (field === 'channels') {
    specificCampaignErrors.channels = specificCampaignForm.send_notification || specificCampaignForm.send_email
      ? ''
      : 'Activa al menos un canal de envío (notificación o correo).'
    return
  }

  if (field === 'user_ids') {
    specificCampaignErrors.user_ids = specificCampaignForm.user_ids.length > 0
      ? ''
      : 'Selecciona al menos un usuario para continuar.'
  }
}

function validateMassCampaignForm() {
  validateMassCampaignField('discount_code_id')
  validateMassCampaignField('channels')

  return !massCampaignErrors.discount_code_id && !massCampaignErrors.channels
}

function validateSpecificCampaignForm() {
  validateSpecificCampaignField('discount_code_id')
  validateSpecificCampaignField('channels')
  validateSpecificCampaignField('user_ids')

  return !specificCampaignErrors.discount_code_id && !specificCampaignErrors.channels && !specificCampaignErrors.user_ids
}

function clearSpecificCustomerSelection() {
  specificCampaignForm.user_ids = []
  validateSpecificCampaignField('user_ids')
}

function selectAllFilteredCustomers() {
  const visibleIds = filteredCampaignCustomers.value.map((customer) => String(customer.id))
  specificCampaignForm.user_ids = Array.from(new Set([...specificCampaignForm.user_ids, ...visibleIds]))
  validateSpecificCampaignField('user_ids')
}

function campaignSummaryMessage(summary) {
  if (!summary) {
    return 'Campaña enviada correctamente.'
  }

  const notifications = summary.notifications || { sent: 0, failed: 0 }
  const emails = summary.emails || { sent: 0, failed: 0 }

  return `Notificaciones: ${notifications.sent} enviadas / ${notifications.failed} fallidas. Correos: ${emails.sent} enviados / ${emails.failed} fallidos.`
}

async function submitMassCampaign() {
  if (!validateMassCampaignForm()) {
    showSnackbar({ type: 'warning', message: 'Completa correctamente los campos del envío masivo.' })
    return
  }

  campaignSubmitting.value = true
  try {
    const payload = {
      discount_code_id: Number(massCampaignForm.discount_code_id),
      send_notification: Boolean(massCampaignForm.send_notification),
      send_email: Boolean(massCampaignForm.send_email),
    }

    const { data } = await discountHttp.post('/admin/discount-codes/campaign/mass', payload)
    showSnackbar({ type: 'success', message: campaignSummaryMessage(data?.data?.summary) })
    closeMassCampaignModal()
  } catch (error) {
    showSnackbar({ type: 'error', message: extractErrorMessage(error, 'No se pudo ejecutar el envío masivo.') })
  } finally {
    campaignSubmitting.value = false
  }
}

async function submitSpecificCampaign() {
  if (!validateSpecificCampaignForm()) {
    showSnackbar({ type: 'warning', message: 'Completa correctamente la selección de usuarios y canales.' })
    return
  }

  campaignSubmitting.value = true
  try {
    const payload = {
      discount_code_id: Number(specificCampaignForm.discount_code_id),
      user_ids: specificCampaignForm.user_ids,
      send_notification: Boolean(specificCampaignForm.send_notification),
      send_email: Boolean(specificCampaignForm.send_email),
    }

    const { data } = await discountHttp.post('/admin/discount-codes/campaign/specific', payload)
    showSnackbar({ type: 'success', message: campaignSummaryMessage(data?.data?.summary) })
    closeSpecificCampaignModal()
  } catch (error) {
    showSnackbar({ type: 'error', message: extractErrorMessage(error, 'No se pudo ejecutar el envío a usuarios específicos.') })
  } finally {
    campaignSubmitting.value = false
  }
}

function handleCodeInput() {
  form.code = form.code.toUpperCase().replace(/\s+/g, '')
  validateField('code')
}

function validateField(field) {
  switch (field) {
    case 'code':
      formErrors.code = /^[A-Z0-9_-]{4,20}$/.test(form.code) ? '' : 'Usa entre 4 y 20 caracteres en mayúsculas, números o guiones.'
      break
    case 'type':
      formErrors.type = form.type ? '' : 'Selecciona el tipo de descuento.'
      break
    case 'value':
      formErrors.value = Number(form.value) > 0 ? '' : 'El valor del descuento debe ser mayor que cero.'
      if (!formErrors.value && form.type === 'percent' && Number(form.value) > 100) formErrors.value = 'El porcentaje no puede superar el 100%.'
      break
    case 'max_uses':
      formErrors.max_uses = form.max_uses === null || form.max_uses === '' || Number(form.max_uses) > 0 ? '' : 'El máximo de usos debe ser mayor que cero.'
      break
    case 'start_date':
    case 'expires_at':
      formErrors.start_date = ''
      formErrors.expires_at = ''
      if (form.start_date && form.expires_at && new Date(form.expires_at) <= new Date(form.start_date)) {
        formErrors.expires_at = 'La expiración debe ser posterior al inicio.'
      }
      break
    default:
      break
  }
}

function validateForm() {
  validateField('code')
  validateField('type')
  validateField('value')
  validateField('max_uses')
  validateField('start_date')
  return Object.values(formErrors).every((value) => !value)
}

async function loadCodes() {
  loading.value = true
  try {
    const { data } = await discountHttp.get('/admin/discount-codes')
    codes.value = Array.isArray(data?.data) ? data.data : []
  } catch (error) {
    codes.value = []
    showSnackbar({ type: 'error', message: extractErrorMessage(error, 'No se pudieron cargar los códigos.') })
  } finally {
    loading.value = false
  }
}

async function saveCode() {
  if (!validateForm()) {
    showSnackbar({ type: 'warning', message: 'Corrige los errores del formulario antes de guardar.' })
    return
  }

  const payload = {
    code: form.code,
    type: form.type,
    value: Number(form.value),
    max_uses: form.max_uses || null,
    active: form.active,
    is_single_use: form.is_single_use,
    start_date: form.start_date || null,
    expires_at: form.expires_at || null,
  }

  try {
    if (editingCodeId.value) {
      await discountHttp.put(`/admin/discount-codes/${editingCodeId.value}`, payload)
      showSnackbar({ type: 'success', message: 'Código actualizado correctamente.' })
    } else {
      await discountHttp.post('/admin/discount-codes', payload)
      showSnackbar({ type: 'success', message: 'Código creado correctamente.' })
    }

    closeEditorModal()
    await loadCodes()
  } catch (error) {
    showSnackbar({ type: 'error', message: extractErrorMessage(error, 'No se pudo guardar el código.') })
  }
}

function confirmDeleteCode(code) {
  showAlert({
    type: 'warning',
    title: 'Eliminar código',
    message: `Vas a eliminar el código ${code.code}. Esta acción no se puede deshacer.`,
    actions: [
      { text: 'Cancelar', style: 'secondary' },
      {
        text: 'Eliminar',
        style: 'danger',
        callback: async () => {
          try {
            await discountHttp.delete(`/admin/discount-codes/${code.id}`)
            showSnackbar({ type: 'success', message: 'Código eliminado correctamente.' })
            if (selectedCode.value?.id === code.id) closeDetailModal()
            await loadCodes()
          } catch (error) {
            showSnackbar({ type: 'error', message: extractErrorMessage(error, 'No se pudo eliminar el código.') })
          }
        },
      },
    ],
  })
}

function exportCodes() {
  const rows = filteredCodes.value.map((code) => [code.code, code.type_label, formatDiscountValue(code), code.times_used, code.max_uses || '∞', codeStatusLabel(code), code.start_date || '', code.expires_at || ''])
  const csv = [['Código', 'Tipo', 'Valor', 'Usados', 'Máximo', 'Estado', 'Inicio', 'Expira'].join(','), ...rows.map((row) => row.map(csvSafe).join(','))].join('\n')
  downloadCsv('codigos-descuento.csv', csv)
}

function codeStatusKey(code) {
  if (!code.active) return 'inactive'
  if (code.expires_at && new Date(code.expires_at) < new Date()) return 'expired'
  if (code.is_single_use) return 'single-use'
  return 'active'
}

function codeStatusLabel(code) {
  return { active: 'Activo', inactive: 'Inactivo', expired: 'Vencido', 'single-use': 'Uso único' }[codeStatusKey(code)]
}

function codeStatusClass(code) {
  return { active: 'active', inactive: 'rejected', expired: 'cancelled', 'single-use': 'info' }[codeStatusKey(code)]
}

function formatDiscountValue(code) {
  return code.type === 'percent' ? `${Number(code.value || 0)}%` : formatCurrency(code.value || 0)
}

function remainingUsesLabel(code) {
  const remaining = Number(code.max_uses || 0) - Number(code.times_used || 0)
  return remaining > 0 ? `${remaining} usos disponibles` : 'Sin cupos disponibles'
}

function formatCurrency(value) {
  return new Intl.NumberFormat('es-CO', { style: 'currency', currency: 'COP', maximumFractionDigits: 0 }).format(Number(value || 0))
}

// Devuelve las iniciales del cliente para el avatar
function userInitials(customer) {
  const name = String(customer?.name || customer?.email || '?').trim()
  return name.split(/\s+/).slice(0, 2).map((w) => w[0]?.toUpperCase() || '').join('')
}

// Formatea una fecha ISO a formato legible en español
function formatShortDate(dateStr) {
  if (!dateStr) return ''
  const d = new Date(dateStr)
  return isNaN(d.getTime()) ? dateStr : d.toLocaleDateString('es-CO', { day: 'numeric', month: 'short', year: 'numeric' })
}

function formatDateTime(value) {
  if (!value) return 'Sin fecha'
  return new Date(value).toLocaleString('es-CO', { year: 'numeric', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' })
}

function normalizeDateTimeInput(value) {
  if (!value) return ''
  const date = new Date(value)
  if (Number.isNaN(date.getTime())) return ''
  const year = date.getFullYear()
  const month = `${date.getMonth() + 1}`.padStart(2, '0')
  const day = `${date.getDate()}`.padStart(2, '0')
  const hours = `${date.getHours()}`.padStart(2, '0')
  const minutes = `${date.getMinutes()}`.padStart(2, '0')
  return `${year}-${month}-${day}T${hours}:${minutes}`
}

function extractErrorMessage(error, fallback) {
  return error?.response?.data?.message || fallback
}

function csvSafe(value) {
  return `"${String(value ?? '').replaceAll('"', '""')}"`
}

function downloadCsv(filename, content) {
  const blob = new Blob([content], { type: 'text/csv;charset=utf-8;' })
  const url = URL.createObjectURL(blob)
  const link = document.createElement('a')
  link.href = url
  link.download = filename
  document.body.appendChild(link)
  link.click()
  document.body.removeChild(link)
  URL.revokeObjectURL(url)
}

onMounted(loadCodes)
</script>

<style scoped>
.discount-code-pill {
  display: inline-flex;
  padding: 0.4rem 0.9rem;
  border-radius: 999px;
  background: rgba(15, 122, 191, 0.08);
  color: var(--admin-primary);
}

.discount-preview-card {
  margin-top: 1rem;
  border: 1px solid rgba(15, 122, 191, 0.12);
}

.discount-hero-card h3,
.discount-preview-card h3 {
  font-size: 2.6rem;
  letter-spacing: 0.08em;
}

.campaign-modal {
  display: grid;
  gap: 1rem;
}

.campaign-modal__intro {
  margin: 0;
  color: var(--admin-text-muted);
}

.campaign-form-grid {
  display: grid;
  gap: 1rem;
  grid-template-columns: 1fr;
}

.campaign-channels {
  display: grid;
  gap: 0.6rem;
  padding: 0.85rem;
  border-radius: 12px;
  border: 1px solid rgba(15, 122, 191, 0.14);
  background: rgba(15, 122, 191, 0.04);
}

.campaign-checkbox {
  display: flex;
  align-items: center;
  gap: 0.6rem;
  font-weight: 600;
  color: var(--admin-text);
}

.campaign-user-toolbar {
  display: flex;
  flex-wrap: wrap;
  gap: 0.8rem;
  justify-content: space-between;
  align-items: flex-end;
}

.campaign-user-toolbar__search {
  flex: 1;
  min-width: 240px;
}

.campaign-user-toolbar__actions {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  flex-wrap: wrap;
}

/* ── Modal campaña específica mejorada ─────────────────────── */
.specific-campaign-modal {
  display: grid;
  gap: 1.6rem;
}

.specific-campaign-top {
  display: grid;
  gap: 1.35rem;
  grid-template-columns: 1fr;
}

.specific-campaign-label {
  display: flex;
  align-items: center;
  gap: 0.45rem;
  font-weight: 700;
  font-size: 0.93rem;
  color: var(--admin-text-muted);
  text-transform: uppercase;
  letter-spacing: 0.04em;
  margin-bottom: 0.6rem;
}

/* Vista previa del código seleccionado */
.campaign-code-preview {
  display: flex;
  align-items: flex-start;
  gap: 0.9rem;
  padding: 0.9rem 1rem;
  border-radius: 12px;
  border: 1px solid rgba(15, 122, 191, 0.2);
  background: rgba(15, 122, 191, 0.05);
  margin-top: 0.6rem;
}

.campaign-code-preview__icon {
  width: 2.8rem;
  height: 2.8rem;
  flex-shrink: 0;
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.1rem;
  background: rgba(15, 122, 191, 0.12);
  color: var(--admin-primary, #0077b6);
}

.campaign-code-preview__icon.is-fixed {
  background: rgba(34, 197, 94, 0.12);
  color: #16a34a;
}

.campaign-code-preview__body {
  display: flex;
  flex-direction: column;
  gap: 0.18rem;
  min-width: 0;
}

.campaign-code-preview__code {
  font-size: 1.18rem;
  color: var(--admin-text);
  word-break: break-all;
}

.campaign-code-preview__value {
  font-size: 1.04rem;
  font-weight: 700;
  color: var(--admin-primary, #0077b6);
}

.campaign-code-preview__meta {
  display: flex;
  align-items: center;
  gap: 0.35rem;
  font-size: 0.9rem;
  color: var(--admin-text-muted);
}

/* Animación de entrada del preview */
.campaign-preview-fade-enter-active,
.campaign-preview-fade-leave-active {
  transition: opacity 0.22s ease, transform 0.22s ease;
}
.campaign-preview-fade-enter-from,
.campaign-preview-fade-leave-to {
  opacity: 0;
  transform: translateY(-6px);
}

/* Tarjetas de canal */
.specific-campaign-channels-col {
  display: flex;
  flex-direction: column;
  gap: 0.65rem;
}

.campaign-channel-card {
  display: flex;
  align-items: center;
  gap: 0.85rem;
  padding: 0.85rem 1rem;
  border-radius: 12px;
  border: 1.5px solid rgba(15, 122, 191, 0.14);
  background: var(--admin-surface, #fff);
  cursor: pointer;
  transition: border-color 0.18s, background 0.18s, box-shadow 0.18s;
  user-select: none;
}

.campaign-channel-card:hover {
  border-color: rgba(15, 122, 191, 0.32);
  background: rgba(15, 122, 191, 0.04);
}

.campaign-channel-card.is-active {
  border-color: var(--admin-primary, #0077b6);
  background: rgba(15, 122, 191, 0.07);
  box-shadow: 0 0 0 3px rgba(15, 122, 191, 0.08);
}

.campaign-channel-card__icon {
  width: 2.4rem;
  height: 2.4rem;
  flex-shrink: 0;
  border-radius: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1rem;
  background: rgba(15, 122, 191, 0.1);
  color: var(--admin-primary, #0077b6);
}

.campaign-channel-card__icon--email {
  background: rgba(34, 197, 94, 0.1);
  color: #16a34a;
}

.campaign-channel-card__info {
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 0.08rem;
}

.campaign-channel-card__info strong {
  font-size: 1.04rem;
  color: var(--admin-text);
}

.campaign-channel-card__info span {
  font-size: 0.9rem;
  color: var(--admin-text-muted);
}

.campaign-channel-card__toggle {
  flex-shrink: 0;
}

.campaign-channel-card__toggle input[type="checkbox"] {
  width: 1.1rem;
  height: 1.1rem;
  cursor: pointer;
}

/* Cabecera de destinatarios */
.specific-campaign-users-panel {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 0.85rem;
  flex-wrap: wrap;
}

.specific-campaign-users-header__title {
  display: flex;
  align-items: center;
  gap: 0.55rem;
  font-weight: 700;
  color: var(--admin-text);
}

.specific-campaign-users-header__actions {
  display: flex;
  align-items: center;
  gap: 0.4rem;
  flex-wrap: wrap;
}

/* Botones de acción tipo chip para el panel de destinatarios */
.campaign-action-chip {
  display: inline-flex;
  align-items: center;
  gap: 0.32rem;
  padding: 0.35rem 0.85rem;
  border-radius: 999px;
  font-size: 0.84rem;
  font-weight: 700;
  background: rgba(15, 122, 191, 0.08);
  color: var(--admin-primary, #0077b6);
  border: 1.5px solid rgba(15, 122, 191, 0.2);
  cursor: pointer;
  transition: background 0.16s, border-color 0.16s, color 0.16s;
  white-space: nowrap;
  line-height: 1;
}

.campaign-action-chip:hover {
  background: rgba(15, 122, 191, 0.15);
  border-color: rgba(15, 122, 191, 0.38);
}

.campaign-action-chip--clear {
  background: rgba(220, 38, 38, 0.06);
  color: #dc2626;
  border-color: rgba(220, 38, 38, 0.18);
}

.campaign-action-chip--clear:hover:not(:disabled) {
  background: rgba(220, 38, 38, 0.12);
  border-color: rgba(220, 38, 38, 0.35);
}

.campaign-action-chip:disabled {
  opacity: 0.4;
  cursor: not-allowed;
}


.specific-campaign-badge {
  display: inline-flex;
  align-items: center;
  padding: 0.2rem 0.7rem;
  border-radius: 999px;
  font-size: 0.83rem;
  font-weight: 700;
  background: rgba(15, 122, 191, 0.1);
  color: var(--admin-text-muted);
  transition: background 0.2s, color 0.2s;
}

.specific-campaign-badge.is-filled {
  background: var(--admin-primary, #0077b6);
  color: #fff;
}

/* Lista de usuarios */
.campaign-users-list {
  max-height: 320px;
  overflow-y: auto;
  border: 1px solid rgba(15, 122, 191, 0.14);
  border-radius: 12px;
  padding: 0.45rem;
  background: rgba(255, 255, 255, 0.78);
}

.campaign-users-list--specific {
  max-height: min(52vh, 430px);
}

.campaign-users-list__state {
  padding: 1.4rem;
  text-align: center;
  color: var(--admin-text-muted);
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
}

.campaign-user-item {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 0.6rem 0.75rem;
  border-radius: 10px;
  cursor: pointer;
  transition: background 0.15s;
}

.campaign-user-item:hover {
  background: rgba(15, 122, 191, 0.06);
}

.campaign-user-item.is-selected {
  background: rgba(15, 122, 191, 0.09);
}

.campaign-user-item input[type="checkbox"] {
  flex-shrink: 0;
}

.campaign-user-avatar {
  width: 2.5rem;
  height: 2.5rem;
  flex-shrink: 0;
  border-radius: 50%;
  background: rgba(15, 122, 191, 0.14);
  color: var(--admin-primary, #0077b6);
  font-size: 0.86rem;
  font-weight: 800;
  display: flex;
  align-items: center;
  justify-content: center;
  text-transform: uppercase;
}

.campaign-user-item__meta {
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 0.1rem;
  min-width: 0;
}

.campaign-user-item__meta strong {
  font-size: 1rem;
  color: var(--admin-text);
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.campaign-user-item__meta span {
  color: var(--admin-text-muted);
  font-size: 0.9rem;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.campaign-user-item__checkmark {
  flex-shrink: 0;
  color: var(--admin-primary, #0077b6);
  font-size: 0.85rem;
}

@media (min-width: 760px) {
  .specific-campaign-top {
    grid-template-columns: minmax(0, 1.08fr) minmax(0, 1fr);
    align-items: start;
  }

  .campaign-form-grid {
    grid-template-columns: 1fr 1fr;
  }
}

@media (max-width: 640px) {
  .specific-campaign-users-panel {
    flex-direction: column;
    align-items: stretch;
  }

  .specific-campaign-users-header__actions {
    width: 100%;
  }

  .specific-campaign-users-header__actions .btn {
    flex: 1;
    justify-content: center;
  }

  .campaign-user-toolbar__actions {
    width: 100%;
  }

  .campaign-user-toolbar__actions .btn {
    flex: 1;
  }
}
</style>