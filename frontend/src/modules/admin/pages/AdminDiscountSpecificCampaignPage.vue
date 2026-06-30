<!--
  AdminDiscountSpecificCampaignPage.vue
  Página de administración para enviar campañas de descuento
  a usuarios específicos mediante códigos de descuento.
  Permite seleccionar un código, definir canales de envío
  y elegir destinatarios de entre los clientes disponibles.
-->
<template>
  <div class="admin-discount-specific-campaign-page">
    <AdminPageHeader
      icon="fas fa-user-check"
      title="Campaña a usuarios específicos"
      subtitle="Selecciona un código, define canales y envía la campaña a clientes concretos sin usar modal."
      :breadcrumbs="[
        { label: 'Dashboard', to: '/admin' },
        { label: 'Códigos de descuento', to: '/admin/descuentos/codigos' },
        { label: 'Usuarios específicos' },
      ]"
    >
      <template #actions>
        <button class="btn btn-secondary" type="button" @click="goBackToDiscountCodes">
          <i class="fas fa-arrow-left"></i>
          Volver a códigos
        </button>
        <button
          class="btn btn-primary"
          type="button"
          :disabled="campaignSubmitting || specificCampaignForm.user_ids.length === 0"
          @click="submitSpecificCampaign"
        >
          <i :class="campaignSubmitting ? 'fas fa-spinner fa-spin' : 'fas fa-paper-plane'"></i>
          {{ campaignSubmitting
            ? 'Enviando...'
            : specificCampaignForm.user_ids.length > 0
              ? `Enviar a ${specificCampaignForm.user_ids.length} usuario${specificCampaignForm.user_ids.length !== 1 ? 's' : ''}`
              : 'Enviar campaña'
          }}
        </button>
      </template>
    </AdminPageHeader>

    <div class="specific-campaign-page-grid">
      <AdminCard class="specific-campaign-config-card" title="Configuración de campaña" icon="fas fa-paper-plane">
        <div v-if="loadingCodes" class="specific-campaign-loading">
          <div class="specific-campaign-loading__item">
            <AdminShimmer type="line" width="36%" height="0.95rem" />
            <AdminShimmer type="rect" width="100%" height="2.8rem" radius="12px" />
          </div>
          <div class="specific-campaign-loading__item">
            <AdminShimmer type="line" width="42%" height="0.95rem" />
            <AdminShimmer type="rect" width="100%" height="5.4rem" radius="12px" />
          </div>
        </div>

        <AdminEmptyState
          v-else-if="campaignCodeOptions.length === 0"
          icon="fas fa-tags"
          title="Sin códigos disponibles"
          description="Crea al menos un código de descuento para usar esta campaña."
        />

        <div v-else class="specific-campaign-config">
          <!-- Paso 1: Código de descuento -->
          <div class="specific-campaign-step">
            <div class="specific-campaign-step__header">
              <span class="specific-campaign-step__num">1</span>
              <span class="specific-campaign-step__title">
                <i class="fas fa-tag"></i>
                Código de descuento
                <AdminInfoTooltip text="Selecciona el código que se enviará a los clientes elegidos." />
              </span>
            </div>
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

            <transition name="campaign-preview-fade">
              <div v-if="selectedSpecificCode" class="campaign-code-summary">
                <span class="campaign-code-summary__pill" :class="selectedSpecificCode.type === 'percent' ? 'is-percent' : 'is-fixed'">
                  <i :class="selectedSpecificCode.type === 'percent' ? 'fas fa-percent' : 'fas fa-tag'"></i>
                  {{ formatDiscountValue(selectedSpecificCode) }}
                </span>
                <span class="campaign-code-summary__meta">
                  <i class="fas fa-calendar-alt"></i>
                  {{ selectedSpecificCode.expires_at ? formatShortDate(selectedSpecificCode.expires_at) : 'Sin expiración' }}
                </span>
                <span class="campaign-code-summary__meta">
                  <i class="fas fa-chart-bar"></i>
                  {{ selectedSpecificCode.times_used || 0 }}{{ selectedSpecificCode.max_uses ? `/${selectedSpecificCode.max_uses}` : '' }}
                  uso{{ (selectedSpecificCode.times_used || 0) !== 1 ? 's' : '' }}
                  {{ !selectedSpecificCode.max_uses ? '(sin límite)' : '' }}
                </span>
              </div>
            </transition>
          </div>

          <!-- Paso 2: Canales de envío -->
          <div class="specific-campaign-step">
            <div class="specific-campaign-step__header">
              <span class="specific-campaign-step__num">2</span>
              <span class="specific-campaign-step__title">
                <i class="fas fa-share-square"></i>
                Canales de envío
                <AdminInfoTooltip text="Activa al menos un canal para enviar la campaña. Puedes usar ambos al mismo tiempo." />
              </span>
            </div>
            <div class="campaign-channels-grid">
              <AdminToggleSwitch
                id="specific-campaign-send-notification"
                v-model="specificCampaignForm.send_notification"
                class="campaign-channel-toggle"
                :class="{ 'is-active': specificCampaignForm.send_notification }"
                title="Notificación interna"
                description="Panel del cliente"
                @change="validateSpecificCampaignField('channels')"
              />
              <AdminToggleSwitch
                id="specific-campaign-send-email"
                v-model="specificCampaignForm.send_email"
                class="campaign-channel-toggle"
                :class="{ 'is-active': specificCampaignForm.send_email }"
                title="Correo electrónico"
                description="Código con detalle"
                @change="validateSpecificCampaignField('channels')"
              />
            </div>
            <p v-if="specificCampaignErrors.channels" class="form-error">{{ specificCampaignErrors.channels }}</p>
          </div>
        </div>
      </AdminCard>

      <div class="specific-campaign-customers-panel">
        <AdminCard class="specific-campaign-customers-card" title="Clientes disponibles" icon="fas fa-users" :flush="true">
          <AdminFilterCard
            v-model="specificCampaignSearch"
            icon="fas fa-search"
            title="Buscar destinatarios"
            placeholder="Buscar por nombre o correo..."
            :initially-expanded="true"
            :hide-toggle="true"
            @search="() => {}"
          />

          <AdminResultsBar :text="customerResultsText">
            <template #actions>
              <div class="campaign-results-actions">
                <span
                  class="specific-campaign-badge"
                  :class="{ 'is-filled': specificCampaignForm.user_ids.length > 0 }"
                >
                  {{ specificCampaignForm.user_ids.length }}
                  {{ specificCampaignForm.user_ids.length === 1 ? 'seleccionado' : 'seleccionados' }}
                </span>
                <button
                  type="button"
                  class="results-action-btn results-action-btn--neutral"
                  :disabled="filteredCampaignCustomers.length === 0"
                  @click="selectAllFilteredCustomers"
                >
                  <span class="results-action-btn__icon"><i class="fas fa-check-double"></i></span>
                  Todos los visibles
                </button>
                <button
                  type="button"
                  class="results-action-btn results-action-btn--neutral campaign-results-btn--clear"
                  :disabled="specificCampaignForm.user_ids.length === 0"
                  @click="clearSpecificCustomerSelection"
                >
                  <span class="results-action-btn__icon"><i class="fas fa-ban"></i></span>
                  Limpiar
                </button>
              </div>
            </template>
          </AdminResultsBar>

          <AdminTableShimmer
            v-if="campaignCustomersLoading"
            :rows="6"
            :columns="customerTableShimmerColumns"
          />

          <AdminEmptyState
            v-else-if="filteredCampaignCustomers.length === 0"
            icon="fas fa-user-slash"
            title="Sin resultados"
            description="No hay clientes para mostrar con el filtro actual."
          />

          <div v-else class="table-responsive">
            <table class="dashboard-table campaign-customers-table">
              <thead>
                <tr>
                  <th class="selection-cell">
                    <input
                      type="checkbox"
                      :checked="allFilteredCustomersSelected"
                      @change="toggleFilteredCustomersSelection($event.target.checked)"
                    >
                  </th>
                  <th>Cliente</th>
                  <th>Contacto</th>
                  <th>Estado</th>
                </tr>
              </thead>
              <tbody>
                <tr
                  v-for="customer in campaignCustomersPagination.paginatedItems"
                  :key="customer.id"
                  :class="{ 'campaign-customer-row--selected': isCustomerSelected(customer) }"
                >
                  <td class="selection-cell">
                    <input
                      v-model="specificCampaignForm.user_ids"
                      type="checkbox"
                      :value="String(customer.id)"
                      @change="validateSpecificCampaignField('user_ids')"
                    >
                  </td>
                  <td>
                    <div class="campaign-customer-cell">
                      <div class="campaign-user-avatar" :class="{ 'is-selected': isCustomerSelected(customer) }">{{ userInitials(customer) }}</div>
                      <div class="admin-entity-name">
                        <strong>{{ customer.name || 'Cliente' }}</strong>
                        <span>ID {{ customer.id }}</span>
                      </div>
                    </div>
                  </td>
                  <td>
                    <div class="admin-entity-name">
                      <strong>{{ customer.email || 'Sin correo registrado' }}</strong>
                      <span>{{ isCustomerSelected(customer) ? 'Listo para recibir la campaña' : 'Disponible para selección' }}</span>
                    </div>
                  </td>
                  <td>
                    <span class="campaign-selection-badge" :class="{ 'is-selected': isCustomerSelected(customer) }">
                      {{ isCustomerSelected(customer) ? 'Seleccionado' : 'Disponible' }}
                    </span>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
          <p v-if="specificCampaignErrors.user_ids" class="form-error specific-campaign-error">{{ specificCampaignErrors.user_ids }}</p>
        </AdminCard>

        <AdminPagination
          v-model:page="campaignCustomersPagination.currentPage"
          v-model:page-size="campaignCustomersPagination.pageSize"
          :total-items="campaignCustomersPagination.totalItems"
          :page-size-options="campaignCustomersPagination.pageSizeOptions"
        />
      </div>
    </div>
  </div>
</template>

<!--
  Lógica de la página de campaña de descuento específica.
  Gestiona la selección de código de descuento, canales de envío
  y la elección de clientes destinatarios para la campaña.
-->
<script setup>
// Importaciones de componentes compartidos y composable de campaña específica.
import AdminCard from '../components/AdminCard.vue'
import AdminEmptyState from '../components/AdminEmptyState.vue'
import AdminFilterCard from '../components/AdminFilterCard.vue'
import AdminInfoTooltip from '../components/AdminInfoTooltip.vue'
import AdminPagination from '../components/AdminPagination.vue'
import AdminPageHeader from '../components/AdminPageHeader.vue'
import AdminResultsBar from '../components/AdminResultsBar.vue'
import AdminShimmer from '../components/AdminShimmer.vue'
import AdminTableShimmer from '../components/AdminTableShimmer.vue'
import AdminToggleSwitch from '../components/AdminToggleSwitch.vue'
import { useAdminDiscountSpecificCampaign } from '../composables/useAdminDiscountSpecificCampaign'
import '../views/AdminDiscountSpecificCampaignPage.css'

// =====================================================
// Orquestación de la vista
// =====================================================
const {
  allFilteredCustomersSelected,
  campaignCodeOptions,
  campaignCustomersLoading,
  campaignCustomersPagination,
  campaignSubmitting,
  clearSpecificCustomerSelection,
  customerResultsText,
  customerTableShimmerColumns,
  filteredCampaignCustomers,
  formatDiscountValue,
  formatShortDate,
  goBackToDiscountCodes,
  isCustomerSelected,
  loadingCodes,
  selectAllFilteredCustomers,
  selectedSpecificCode,
  specificCampaignErrors,
  specificCampaignForm,
  specificCampaignSearch,
  submitSpecificCampaign,
  toggleFilteredCustomersSelection,
  userInitials,
  validateSpecificCampaignField,
} = useAdminDiscountSpecificCampaign()
</script>
