<template>
  <div class="admin-courier-page">
    <AdminPageHeader icon="fas fa-motorcycle" title="Repartidores" subtitle="Revisa identidad, vehículo, documentos y habilitación para operar." :breadcrumbs="[{ label: 'Repartidores' }]" />
    <AdminStatsGrid :loading="loading" :stats="stats" :count="4" />
    <AdminFilterCard v-model="filters.search" title="Buscar repartidores" placeholder="Correo o teléfono..." initially-expanded @search="load">
      <template #advanced>
        <div class="admin-filters__row">
          <div class="admin-filters__group">
            <label for="courier-status">Estado</label>
            <select id="courier-status" v-model="filters.status" @change="load">
              <option value="">Todos</option><option value="pending">Pendientes</option><option value="approved">Aprobados</option><option value="rejected">Rechazados</option><option value="inactive">Inactivos</option>
            </select>
          </div>
        </div>
      </template>
    </AdminFilterCard>
    <AdminResultsBar :text="`${pagination.totalItems} repartidores encontrados`" />
    <AdminCard :flush="true">
      <AdminTableShimmer v-if="loading" :rows="6" :columns="['line', 'line', 'line', 'line', 'pill', 'btn']" />
      <AdminEmptyState v-else-if="rows.length === 0" icon="fas fa-motorcycle" title="Sin repartidores" description="No hay solicitudes para los filtros actuales." />
      <div v-else class="table-responsive">
        <table class="dashboard-table">
          <thead><tr><th>Repartidor</th><th>Vehículo</th><th>Dirección</th><th>Documentos</th><th>Estado</th><th>Acciones</th></tr></thead>
          <tbody>
            <tr v-for="row in rows" :key="row.id">
              <td><div class="admin-entity-name"><strong>{{ row.email }}</strong><span>{{ row.phone }}</span></div></td>
              <td><div class="admin-entity-name"><strong>{{ vehicleLabel(row.vehicle?.type) }}</strong><span>{{ vehicleDescription(row.vehicle) }}</span></div></td>
              <td>{{ row.address }}</td>
              <td>{{ row.documents?.length || 0 }} adjuntos</td>
              <td><span class="status-badge" :class="statusClass(row.status)">{{ statusLabel(row.status) }}</span></td>
              <td><div class="admin-entity-actions"><button class="action-btn view" title="Revisar" @click="open(row)"><i class="fas fa-eye" /></button><button class="action-btn edit" title="Editar repartidor" :disabled="savingEditor" @click="openEditor(row)"><i class="fas fa-pen" /></button><button class="action-btn" :class="row.is_active ? 'delete' : 'edit'" :disabled="saving || savingEditor" :title="row.is_active ? 'Desactivar' : 'Activar'" @click="toggleActive(row)"><i :class="row.is_active ? 'fas fa-ban' : 'fas fa-check'" /></button></div></td>
            </tr>
          </tbody>
        </table>
      </div>
    </AdminCard>
    <AdminPagination v-model:page="pagination.currentPage" v-model:page-size="pagination.pageSize" :total-items="pagination.totalItems" @update:page="load" @update:page-size="load" />

    <AdminModal
      :show="showModal"
      :title="canReviewSelected ? 'Revisión de repartidor' : 'Detalle del repartidor'"
      :subtitle="modalSubtitle"
      icon="fas fa-user-check"
      max-width="1080px"
      @close="closeReview"
    >
      <div v-if="selected" class="courier-review">
        <section class="courier-review__summary">
          <div class="courier-review__avatar"><i class="fas fa-motorcycle" /></div>
          <div>
            <span class="courier-review__eyebrow">Solicitud #{{ selected.id }}</span>
            <h4>{{ selected.email }}</h4>
            <p>{{ selected.phone }} · {{ vehicleLabel(selected.vehicle?.type) }}</p>
          </div>
          <span class="status-badge" :class="statusClass(selected.status)">{{ statusLabel(selected.status) }}</span>
        </section>

        <section v-if="!canReviewSelected" class="courier-review-result" :class="`courier-review-result--${selected.status}`">
          <i :class="reviewResultIcon" />
          <div>
            <strong>{{ reviewResultTitle }}</strong>
            <p>{{ reviewResultMessage }}</p>
          </div>
        </section>

        <div class="courier-review-grid">
          <section class="courier-info-card">
            <div class="courier-section-title"><i class="fas fa-id-card" /><div><h4>Identidad y contacto</h4><p>Datos declarados por el solicitante</p></div></div>
            <dl class="courier-details"><div><dt>Documento</dt><dd>{{ selected.document_type?.toUpperCase() }} {{ selected.document_number }}</dd></div><div><dt>Nacimiento</dt><dd>{{ formatDate(selected.birth_date) }}</dd></div><div><dt>Celular</dt><dd>{{ selected.phone }}</dd></div><div><dt>Dirección</dt><dd>{{ selected.address }}</dd></div></dl>
          </section>
          <section class="courier-info-card">
            <div class="courier-section-title"><i class="fas fa-route" /><div><h4>Medio de transporte</h4><p>Información aplicable a las entregas</p></div></div>
            <dl class="courier-details"><div><dt>Tipo</dt><dd>{{ vehicleLabel(selected.vehicle?.type) }}</dd></div><div><dt>Detalle</dt><dd>{{ vehicleDescription(selected.vehicle) }}</dd></div><div><dt>Placa</dt><dd>{{ selected.vehicle?.plate || 'No aplica' }}</dd></div></dl>
          </section>
        </div>

        <section class="courier-documents">
          <div class="courier-section-title courier-section-title--documents"><i class="fas fa-folder-open" /><div><h4>Documentos adjuntos</h4><p>{{ selected.documents?.length || 0 }} archivos para comprobar</p></div></div>
          <div class="courier-document-list">
            <article v-for="document in selected.documents" :key="document.id" class="courier-document" :class="{ 'courier-document--change': document.needs_change }">
              <button type="button" class="courier-document__preview" @click="previewDocument(document)">
                <span class="courier-document__icon"><i :class="documentIcon(document.path)" /></span>
                <span class="courier-document__identity"><strong>{{ documentLabel(document.type) }}</strong><small><i class="fas fa-eye" /> Previsualizar archivo</small></span>
              </button>
              <div v-if="!requestingChanges" class="courier-document__status" :class="{ 'courier-document__status--rejected': document.status === 'rejected' }">
                <i :class="documentReviewIcon(document)" />
                <span>{{ documentReviewMessage(document) }}</span>
              </div>
              <div v-else class="courier-document__decision">
                <button type="button" class="courier-change-toggle" :class="{ active: document.needs_change }" @click="toggleDocumentChange(document)">
                  <i :class="document.needs_change ? 'fas fa-rotate-left' : 'far fa-square'" />
                  {{ document.needs_change ? 'Requiere cambio' : 'Marcar para cambio' }}
                </button>
                <textarea v-if="document.needs_change" v-model="document.review_note" rows="2" :aria-label="`Cambio requerido en ${documentLabel(document.type)}`" placeholder="Explica exactamente qué debe corregir o volver a adjuntar" />
              </div>
            </article>
          </div>
        </section>

        <section v-if="requestingChanges" class="courier-change-message">
          <div class="courier-section-title"><i class="fas fa-message" /><div><h4>Mensaje para el repartidor</h4><p>Este texto aparecerá destacado en la app.</p></div></div>
          <label for="courier-reason">Resumen de los cambios solicitados</label>
          <textarea id="courier-reason" v-model="selected.rejection_reason" rows="3" placeholder="Ejemplo: necesitamos imágenes más nítidas y que los datos sean completamente visibles." />
        </section>
        <section v-else-if="selected.status === 'rejected' && selected.rejection_reason" class="courier-change-message courier-change-message--readonly">
          <div class="courier-section-title"><i class="fas fa-message" /><div><h4>Mensaje enviado al repartidor</h4><p>Motivo registrado en la última revisión.</p></div></div>
          <p>{{ selected.rejection_reason }}</p>
        </section>
      </div>
      <template #footer>
        <button v-if="requestingChanges" type="button" class="btn btn-secondary" :disabled="saving" @click="cancelChangeRequest"><i class="fas fa-arrow-left" /> Volver a revisión</button>
        <button v-else type="button" class="btn btn-secondary" @click="closeReview">Cerrar</button>
        <button v-if="requestingChanges" type="button" class="btn btn-danger" :disabled="saving" @click="saveReview('rejected')"><i class="fas fa-paper-plane" /> {{ saving ? 'Enviando…' : 'Enviar solicitud de cambios' }}</button>
        <template v-else-if="canReviewSelected">
          <button type="button" class="btn btn-danger" :disabled="saving" @click="beginChangeRequest"><i class="fas fa-rotate-left" /> Solicitar cambios</button>
          <button type="button" class="btn btn-primary" :disabled="saving" @click="saveReview('approved')"><i class="fas fa-circle-check" /> {{ saving ? 'Aprobando…' : 'Aprobar repartidor' }}</button>
        </template>
      </template>
    </AdminModal>

    <AdminModal :show="showEditorModal" title="Editar repartidor" max-width="620px" @close="closeEditor">
      <div class="admin-entity-form">
        <div class="form-group admin-entity-form__full">
          <label for="courier-name">Nombre *</label>
          <input id="courier-name" v-model="courierForm.name" class="form-control" :class="{ 'is-invalid': courierErrors.name }" @input="validateCourierField('name')">
          <p v-if="courierErrors.name" class="form-error">{{ courierErrors.name }}</p>
        </div>
        <div class="form-group admin-entity-form__full">
          <label for="courier-email">Correo electrónico *</label>
          <input id="courier-email" v-model="courierForm.email" type="email" class="form-control" :class="{ 'is-invalid': courierErrors.email }" @input="validateCourierField('email')">
          <p v-if="courierErrors.email" class="form-error">{{ courierErrors.email }}</p>
        </div>
        <div class="form-group">
          <label for="courier-phone">Celular</label>
          <input id="courier-phone" v-model="courierForm.phone" type="tel" class="form-control" autocomplete="tel" @input="validateCourierField('phone')">
          <p v-if="courierErrors.phone" class="form-error">{{ courierErrors.phone }}</p>
        </div>
        <div class="form-group">
          <label for="courier-role">Rol</label>
          <select id="courier-role" v-model="courierForm.role" class="form-control">
            <option value="customer">Cliente</option>
            <option value="courier">Repartidor</option>
            <option value="admin">Administrador</option>
          </select>
        </div>
        <div class="form-group admin-entity-form__full">
          <label for="courier-address">Dirección</label>
          <input id="courier-address" v-model="courierForm.address" class="form-control" autocomplete="street-address">
        </div>
        <AdminToggleSwitch
          id="courier-account-active"
          class="admin-entity-form__full"
          v-model="courierForm.accountActive"
          title="Cuenta activa"
          description="Permite iniciar sesión con el rol seleccionado."
        />
        <AdminToggleSwitch
          id="courier-profile-active"
          class="admin-entity-form__full"
          v-model="courierForm.courierActive"
          :disabled="courierForm.role !== 'courier'"
          title="Disponible para entregas"
          :description="courierForm.role === 'courier' ? 'Habilita este perfil para recibir entregas.' : 'Solo aplica cuando la cuenta tiene rol de repartidor.'"
        />
      </div>
      <template #footer>
        <button class="btn btn-secondary" type="button" :disabled="savingEditor" @click="closeEditor">Cancelar</button>
        <button class="btn btn-primary" type="button" :disabled="savingEditor" @click="saveEditor">
          <i :class="savingEditor ? 'fas fa-circle-notch fa-spin' : 'fas fa-floppy-disk'" />
          {{ savingEditor ? 'Guardando…' : 'Guardar cambios' }}
        </button>
      </template>
    </AdminModal>

    <AdminPaymentProofModal
      :show="Boolean(previewedDocument)"
      :title="previewedDocument ? documentLabel(previewedDocument.type) : 'Documento del repartidor'"
      icon="fas fa-file-shield"
      image-alt="Documento adjunto por el repartidor"
      :attachment="previewAttachment"
      :meta="previewMeta"
      file-preview-text="El documento es un PDF. Ábrelo en una pestaña nueva para comprobar todo su contenido."
      empty-title="Documento no disponible"
      empty-text="No se encontró un archivo para este requisito."
      @close="previewedDocument = null"
    />
  </div>
</template>

<script setup>
import { computed, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAdminCouriers } from '../composables/useAdminCouriers'
import AdminCard from '../components/AdminCard.vue'
import AdminEmptyState from '../components/AdminEmptyState.vue'
import AdminFilterCard from '../components/AdminFilterCard.vue'
import AdminModal from '../components/AdminModal.vue'
import AdminPagination from '../components/AdminPagination.vue'
import AdminPaymentProofModal from '../components/AdminPaymentProofModal.vue'
import AdminPageHeader from '../components/AdminPageHeader.vue'
import AdminResultsBar from '../components/AdminResultsBar.vue'
import AdminStatsGrid from '../components/AdminStatsGrid.vue'
import AdminTableShimmer from '../components/AdminTableShimmer.vue'
import AdminToggleSwitch from '../components/AdminToggleSwitch.vue'
import '../views/AdminCouriersPage.css'

const route = useRoute()
const router = useRouter()
const previewedDocument = ref(null)
const {
  beginChangeRequest,
  canReviewSelected,
  cancelChangeRequest,
  close,
  closeEditor,
  courierErrors,
  courierForm,
  filters,
  load,
  loading,
  open,
  openEditor,
  pagination,
  requestingChanges,
  rows,
  saveEditor,
  saveReview,
  saving,
  savingEditor,
  selected,
  showEditorModal,
  showModal,
  stats,
  statusClass,
  statusLabel,
  toggleActive,
  toggleDocumentChange,
  validateCourierField,
} = useAdminCouriers()
const vehicleLabels = { foot: 'A pie', bicycle: 'Bicicleta', motorcycle: 'Moto', car: 'Auto', truck: 'Camión' }
const documentLabels = { identity_front: 'Documento (frente)', identity_back: 'Documento (reverso)', profile_photo: 'Foto de perfil', driving_license: 'Licencia de conducir', vehicle_registration: 'Tarjeta de propiedad', soat: 'SOAT', technical_inspection: 'Revisión técnico-mecánica' }
function vehicleLabel(type) { return vehicleLabels[type] || 'Sin definir' }
function documentLabel(type) { return documentLabels[type] || type }
function vehicleDescription(vehicle) { return [vehicle?.make_name, vehicle?.model_name, vehicle?.color_name, vehicle?.year].filter(Boolean).join(' · ') || 'No aplica' }
function formatDate(value) { return value ? new Intl.DateTimeFormat('es-CO', { dateStyle: 'medium', timeZone: 'UTC' }).format(new Date(value)) : 'Sin registrar' }
function documentUrl(path) { return `${import.meta.env.VITE_SHIPPING_PUBLIC_URL || 'http://localhost:8007'}/${String(path || '').replace(/^\//, '')}` }
function documentIcon(path) { return /\.pdf(?:\?|$)/i.test(String(path || '')) ? 'fas fa-file-pdf' : 'fas fa-file-image' }
function documentReviewIcon(document) {
  if (!canReviewSelected.value && document?.status === 'rejected') return 'fas fa-circle-exclamation'
  return 'fas fa-circle-check'
}
function documentReviewMessage(document) {
  if (canReviewSelected.value) return 'Se aprobará al aceptar la solicitud'
  if (document?.status === 'approved') return 'Documento aprobado'
  if (document?.status === 'rejected') return document.review_note || 'Documento marcado para corrección'
  return 'Documento pendiente de revisión'
}
function previewDocument(document) { previewedDocument.value = document }
function closeReview() {
  previewedDocument.value = null
  close()
  if (route.query.courier) router.replace({ path: route.path, query: { ...route.query, courier: undefined } })
}

const previewAttachment = computed(() => previewedDocument.value ? {
  url: documentUrl(previewedDocument.value.path),
  name: documentLabel(previewedDocument.value.type),
  exists: true,
} : null)
const previewMeta = computed(() => previewedDocument.value ? [
  { label: 'Tipo', value: documentLabel(previewedDocument.value.type) },
  { label: 'Estado actual', value: statusLabel(previewedDocument.value.status) },
] : [])
const modalSubtitle = computed(() => {
  if (requestingChanges.value) return 'Selecciona únicamente los documentos que deben corregirse.'
  if (canReviewSelected.value) return 'Comprueba la información y previsualiza cada adjunto antes de decidir.'
  return 'Consulta el resultado de la revisión y los documentos registrados.'
})
const reviewResultTitle = computed(() => ({
  approved: 'Revisión finalizada y aprobada',
  rejected: 'Se solicitaron correcciones',
  inactive: 'Perfil desactivado',
}[selected.value?.status] || 'Revisión finalizada'))
const reviewResultMessage = computed(() => ({
  approved: 'El repartidor ya está habilitado. No es necesario volver a aprobar esta solicitud.',
  rejected: 'La solicitud quedará disponible para una nueva decisión cuando el repartidor envíe las correcciones.',
  inactive: 'El perfil no puede aceptar entregas mientras permanezca desactivado.',
}[selected.value?.status] || 'Esta solicitud ya no tiene acciones de revisión pendientes.'))
const reviewResultIcon = computed(() => ({
  approved: 'fas fa-circle-check',
  rejected: 'fas fa-rotate-left',
  inactive: 'fas fa-ban',
}[selected.value?.status] || 'fas fa-circle-info'))

watch(
  [() => route.query.courier, rows],
  ([courierId]) => {
    const targetId = Number(courierId || 0)
    if (!targetId || showModal.value) return
    const target = rows.value.find((row) => Number(row.id) === targetId)
    if (target) open(target)
  },
  { immediate: true },
)
</script>
