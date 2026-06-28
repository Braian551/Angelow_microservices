<!--
  AdminPaymentsPage.vue
  Página administrativa para la gestión de pagos.
  Permite visualizar el listado de pagos, filtrar por estado y método,
  verificar o rechazar comprobantes de transferencia, y configurar la
  cuenta bancaria que se muestra al cliente al momento de registrar un pago.
-->
<template>
  <div class="admin-entity-page admin-payments-page">
    <AdminPageHeader
      icon="fas fa-credit-card"
      title="Pagos"
      subtitle="Gestiona pagos, verifica comprobantes y revisa la cuenta activa para transferencias."
      :breadcrumbs="[{ label: 'Dashboard', to: '/admin' }, { label: 'Pagos' }]"
    >
      <template #actions>
        <button class="btn btn-secondary" type="button" @click="openAccountModal">
          <i class="fas fa-cog"></i>
          Cuenta visible al cliente
        </button>
      </template>
    </AdminPageHeader>

    <AdminStatsGrid :loading="loading" :count="4" :stats="paymentStats" />

    <AdminFilterCard
      v-model="search"
      icon="fas fa-filter"
      title="Búsqueda y control de pagos"
      placeholder="Buscar por orden, cliente o referencia..."
      @search="search = search.trim()"
    >
      <template #advanced>
        <div class="admin-filters__row admin-filters__row--2">
          <div class="admin-filters__group">
            <label for="payment-status"><i class="fas fa-signal"></i> Estado</label>
            <select id="payment-status" v-model="statusFilter">
              <option value="">Todos</option>
              <option value="pending">Pendiente</option>
              <option value="approved">Verificado</option>
              <option value="rejected">Rechazado</option>
            </select>
          </div>
          <div class="admin-filters__group">
            <label for="payment-method"><i class="fas fa-credit-card"></i> Método</label>
            <select id="payment-method" v-model="methodFilter">
              <option value="">Todos</option>
              <option value="transfer">Transferencia</option>
              <option value="cash">Efectivo</option>
              <option value="card">Tarjeta</option>
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
              <i class="fas fa-times-circle"></i>
              Limpiar filtros
            </button>
          </div>
        </div>
      </template>
    </AdminFilterCard>

    <AdminResultsBar :text="`Mostrando ${pagination.visibleCount} de ${pagination.totalItems} pagos`" />

    <AdminCard title="Listado de pagos" icon="fas fa-list" :flush="true">
      <AdminTableShimmer v-if="loading" :rows="5" :columns="['line', 'line', 'line', 'line', 'line', 'pill', 'btn', 'btn']" />
      <AdminEmptyState v-else-if="filtered.length === 0" icon="fas fa-credit-card" title="Sin pagos registrados" description="No se encontraron pagos con los filtros actuales." />
      <div v-else class="table-responsive">
        <table class="dashboard-table">
          <thead>
            <tr>
              <th>#</th>
              <th>Orden</th>
              <th>Cliente</th>
              <th>Monto</th>
              <th>Método</th>
              <th>Estado</th>
              <th>Comprobante</th>
              <th>Acciones</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="payment in pagination.paginatedItems" :key="payment.id">
              <td>{{ payment.id }}</td>
              <td>
                <RouterLink v-if="payment.order_id" :to="`/admin/ordenes/${payment.order_id}`">#{{ payment.order_id }}</RouterLink>
                <span v-else>—</span>
              </td>
              <td>
                <div class="admin-entity-name">
                  <strong>{{ payment.customer_name || 'Cliente' }}</strong>
                  <span>{{ payment.customer_email || 'Sin correo' }}</span>
                </div>
              </td>
              <td><strong>{{ formatCurrency(payment.amount) }}</strong></td>
              <td>{{ methodLabel(payment.method) }}</td>
              <td>
                <span class="status-badge" :class="statusBadgeClass(payment.status)">
                  {{ statusLabel(payment.status) }}
                </span>
              </td>
              <td>
                <button
                  v-if="payment.proof_url"
                  type="button"
                  class="btn btn-sm btn-secondary"
                  title="Ver comprobante"
                  @click="openProofModal(payment)"
                >
                  <i class="fas fa-search-plus"></i>
                </button>
                <span v-else>—</span>
              </td>
              <td>
                <div class="admin-entity-actions">
                  <RouterLink
                    v-if="payment.order_id"
                    :to="`/admin/ordenes/${payment.order_id}`"
                    class="action-btn view"
                    title="Ir a la orden"
                  >
                    <i class="fas fa-external-link-alt"></i>
                  </RouterLink>

                  <button
                    v-if="payment.status === 'pending'"
                    class="action-btn edit"
                    type="button"
                    title="Verificar pago"
                    :class="{ 'is-loading': isPaymentActionLoading(payment, 'approved') }"
                    :disabled="syncingPaymentId !== null"
                    @click="updatePayment(payment, 'approved')"
                  >
                    <i :class="isPaymentActionLoading(payment, 'approved') ? 'fas fa-spinner fa-spin' : 'fas fa-check'"></i>
                  </button>
                  <button
                    v-if="payment.status === 'pending'"
                    class="action-btn delete"
                    type="button"
                    title="Rechazar pago"
                    :class="{ 'is-loading': isPaymentActionLoading(payment, 'rejected') }"
                    :disabled="syncingPaymentId !== null"
                    @click="updatePayment(payment, 'rejected')"
                  >
                    <i :class="isPaymentActionLoading(payment, 'rejected') ? 'fas fa-spinner fa-spin' : 'fas fa-times'"></i>
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

    <AdminModal :show="showAccountModal" title="Cuenta visible al cliente" max-width="1040px" @close="closeAccountModal">
      <div class="admin-payments-page admin-payments-page--modal payment-account-modal">
        <p class="payment-account-modal__intro">Esta es la cuenta activa que se muestra al cliente cuando va a registrar una transferencia en el paso de pagos.</p>

        <div class="payment-account-modal__grid">
          <div class="payment-account-modal__summary">
            <PaymentAccountSummaryCard
                :account="accountPreview"
              description="Información activa para recibir transferencias de clientes."
            />
          </div>

          <div class="payment-account-modal__editor">
            <div class="payment-account-modal__editor-header">
              <h4>Configurar datos de cuenta</h4>
              <p>Actualiza aquí la cuenta que verá el cliente al momento de reportar su transferencia.</p>
            </div>

            <p v-if="loadingAccountConfig" class="loading-box">Cargando configuración...</p>

            <div v-else class="payment-account-form-grid">
              <div class="form-group">
                <label for="payment-account-bank">Banco *</label>
                <select
                  id="payment-account-bank"
                  v-model="accountForm.bank_code"
                  :disabled="savingAccountConfig"
                  @change="validateAccountField('bank_code')"
                >
                  <option value="">Selecciona un banco</option>
                  <option v-for="bank in accountBanks" :key="bank.bank_code || bank.id" :value="bank.bank_code">
                    {{ bank.bank_name }}
                  </option>
                </select>
                <small v-if="accountErrors.bank_code" class="form-error">{{ accountErrors.bank_code }}</small>
              </div>

              <div class="form-group">
                <label for="payment-account-type">Tipo de cuenta *</label>
                <select
                  id="payment-account-type"
                  v-model="accountForm.account_type"
                  :disabled="savingAccountConfig"
                  @change="validateAccountField('account_type')"
                >
                  <option value="ahorros">Cuenta de ahorros</option>
                  <option value="corriente">Cuenta corriente</option>
                </select>
                <small v-if="accountErrors.account_type" class="form-error">{{ accountErrors.account_type }}</small>
              </div>

              <div class="form-group">
                <label for="payment-account-number">Número de cuenta *</label>
                <input
                  id="payment-account-number"
                  v-model.trim="accountForm.account_number"
                  type="text"
                  placeholder="Ejemplo: 1234567890"
                  :disabled="savingAccountConfig"
                  @input="validateAccountField('account_number')"
                >
                <small v-if="accountErrors.account_number" class="form-error">{{ accountErrors.account_number }}</small>
              </div>

              <div class="form-group">
                <label for="payment-account-holder">Titular *</label>
                <input
                  id="payment-account-holder"
                  v-model.trim="accountForm.account_holder"
                  type="text"
                  placeholder="Nombre del titular"
                  :disabled="savingAccountConfig"
                  @input="validateAccountField('account_holder')"
                >
                <small v-if="accountErrors.account_holder" class="form-error">{{ accountErrors.account_holder }}</small>
              </div>

              <div class="form-group">
                <label for="payment-account-id-type">Tipo de documento *</label>
                <select
                  id="payment-account-id-type"
                  v-model="accountForm.identification_type"
                  :disabled="savingAccountConfig"
                  @change="validateAccountField('identification_type')"
                >
                  <option value="cc">Cédula</option>
                  <option value="ce">Cédula de extranjería</option>
                  <option value="nit">NIT</option>
                </select>
                <small v-if="accountErrors.identification_type" class="form-error">{{ accountErrors.identification_type }}</small>
              </div>

              <div class="form-group">
                <label for="payment-account-id-number">Número de documento *</label>
                <input
                  id="payment-account-id-number"
                  v-model.trim="accountForm.identification_number"
                  type="text"
                  placeholder="Ejemplo: 900123456"
                  :disabled="savingAccountConfig"
                  @input="validateAccountField('identification_number')"
                >
                <small v-if="accountErrors.identification_number" class="form-error">{{ accountErrors.identification_number }}</small>
              </div>

              <div class="form-group">
                <label for="payment-account-email">Email de contacto</label>
                <input
                  id="payment-account-email"
                  v-model.trim="accountForm.email"
                  type="email"
                  placeholder="pagos@tuempresa.com"
                  :disabled="savingAccountConfig"
                  @input="validateAccountField('email')"
                >
                <small v-if="accountErrors.email" class="form-error">{{ accountErrors.email }}</small>
              </div>

              <div class="form-group">
                <label for="payment-account-phone">Teléfono de contacto</label>
                <input
                  id="payment-account-phone"
                  v-model.trim="accountForm.phone"
                  type="text"
                  placeholder="3001234567"
                  :disabled="savingAccountConfig"
                  @input="validateAccountField('phone')"
                >
                <small v-if="accountErrors.phone" class="form-error">{{ accountErrors.phone }}</small>
              </div>

              <AdminToggleSwitch
                id="payment-account-active"
                class="payment-account-form-grid__full payment-account-form-grid__toggle"
                v-model="accountForm.is_active"
                :disabled="savingAccountConfig"
                title="Cuenta activa"
                description="Activa esta cuenta para que los clientes la vean al registrar sus pagos."
              />
            </div>
          </div>
        </div>
      </div>

      <template #footer>
        <button type="button" class="btn btn-primary" :class="{ 'is-loading': savingAccountConfig }" :disabled="savingAccountConfig || loadingAccountConfig" @click="submitAccountConfig">
          <i :class="savingAccountConfig ? 'fas fa-spinner fa-spin' : 'fas fa-save'"></i>
          {{ savingAccountConfig ? 'Guardando...' : 'Guardar configuración' }}
        </button>
        <button type="button" class="btn btn-secondary" :disabled="savingAccountConfig" @click="closeAccountModal">Cerrar</button>
      </template>
    </AdminModal>

    <AdminPaymentProofModal :show="showProofModal" :payment="selectedProofPayment" @close="closeProofModal" />
  </div>
</template>

<!--
  Script del componente AdminPaymentsPage.
  Responsabilidades:
  - Importar componentes de interfaz y el composable de pagos del administrador.
  - Desestructurar las propiedades y métodos proporcionados por useAdminPayments
    para gestionar el estado, filtros, paginación, modales y acciones sobre pagos.
-->
<script setup>
// Importaciones del módulo de pagos; se mantienen como comentarios JS válidos dentro de script.
import { RouterLink } from 'vue-router'

import PaymentAccountSummaryCard from '../../../components/payments/PaymentAccountSummaryCard.vue'

import AdminCard from '../components/AdminCard.vue'
import AdminEmptyState from '../components/AdminEmptyState.vue'
import AdminFilterCard from '../components/AdminFilterCard.vue'
import AdminModal from '../components/AdminModal.vue'
import AdminPageHeader from '../components/AdminPageHeader.vue'
import AdminPagination from '../components/AdminPagination.vue'
import AdminPaymentProofModal from '../components/AdminPaymentProofModal.vue'
import AdminResultsBar from '../components/AdminResultsBar.vue'
import AdminStatsGrid from '../components/AdminStatsGrid.vue'
import AdminTableShimmer from '../components/AdminTableShimmer.vue'
import AdminToggleSwitch from '../components/AdminToggleSwitch.vue'

import { useAdminPayments } from '../composables/useAdminPayments'

import '../views/AdminPaymentsPage.css'

const {
  accountBanks,        // Lista de bancos disponibles para la configuración de cuenta
  accountErrors,       // Objeto con los errores de validación de los campos de cuenta
  accountForm,         // Formulario reactivo con los datos de la cuenta bancaria
  accountPreview,      // Vista previa de la cuenta configurada para mostrar al cliente
  activeFilterCount,   // Cantidad de filtros actualmente activos
  clearFilters,        // Función que restablece todos los filtros de búsqueda a su valor por defecto
  closeAccountModal,   // Función que cierra el modal de configuración de cuenta bancaria
  closeProofModal,     // Función que cierra el modal de visualización de comprobante
  filtered,            // Lista de pagos filtrados según los criterios de búsqueda y filtros aplicados
  formatCurrency,      // Función de utilidad que formatea un valor numérico como moneda local
  isPaymentActionLoading, // Función que verifica si una acción específica está en curso para un pago dado
  loading,             // Indicador booleano que muestra si los datos se están cargando
  loadingAccountConfig, // Indicador booleano que muestra si la configuración de cuenta se está cargando
  methodFilter,        // Filtro reactivo para el método de pago (transferencia, efectivo, tarjeta)
  methodLabel,         // Función que convierte el código del método de pago en su etiqueta legible
  openAccountModal,    // Función que abre el modal de configuración de cuenta bancaria
  openProofModal,      // Función que abre el modal de visualización del comprobante de un pago
  pagination,          // Objeto de paginación con página actual, tamaño de página, elementos totales y opciones
  paymentStats,        // Arreglo de estadísticas resumidas de pagos para mostrar en la cuadrícula de métricas
  savingAccountConfig, // Indicador booleano que muestra si la configuración de cuenta se está guardando
  search,              // Término de búsqueda reactivo para filtrar pagos por orden, cliente o referencia
  selectedProofPayment, // Pago seleccionado cuyo comprobante se está visualizando en el modal
  showAccountModal,    // Estado reactivo que controla la visibilidad del modal de cuenta bancaria
  showProofModal,      // Estado reactivo que controla la visibilidad del modal de comprobante
  statusBadgeClass,    // Función que retorna la clase CSS correspondiente al estado del pago para el badge
  statusFilter,        // Filtro reactivo para el estado del pago (pendiente, verificado, rechazado)
  statusLabel,         // Función que convierte el código del estado del pago en su etiqueta legible
  submitAccountConfig, // Función que envía y guarda la configuración de cuenta bancaria en el servidor
  syncingPaymentId,    // ID del pago que se está sincronizando actualmente, o null si no hay ninguna
  updatePayment,       // Función que cambia el estado de un pago (aprobar o rechazar)
  validateAccountField, // Función que valida un campo individual del formulario de cuenta
} = useAdminPayments()
</script>

