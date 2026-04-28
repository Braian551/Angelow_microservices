<template>
  <div class="admin-entity-page">
    <AdminPageHeader icon="fas fa-user-shield" title="Administradores" subtitle="Gestiona los usuarios administradores del panel." :breadcrumbs="[{ label: 'Dashboard', to: '/admin' }, { label: 'Administradores' }]">
      <template #actions>
        <button class="btn btn-primary" type="button" @click="openModal()"><i class="fas fa-user-plus"></i> Nuevo administrador</button>
      </template>
    </AdminPageHeader>

    <AdminCard title="Administradores del sistema" icon="fas fa-user-shield" :flush="true">
      <AdminTableShimmer v-if="loading" :rows="5" :columns="['circle','line','line','line','pill','btn']" />
      <AdminEmptyState v-else-if="admins.length === 0" icon="fas fa-user-shield" title="Sin administradores" description="Agrega administradores para gestionar la tienda." />
      <div v-else class="table-responsive">
        <table class="dashboard-table">
          <thead>
            <tr>
              <th>Avatar</th>
              <th>Nombre</th>
              <th>Email</th>
              <th>Último acceso</th>
              <th>Estado</th>
              <th>Acciones</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="a in pagination.paginatedItems" :key="a.id">
              <td>
                <div class="admin-avatar">
                  <img :src="resolveMediaUrl(a.image, 'avatar')" :alt="a.name" @error="(e) => handleMediaError(e, a.image, 'avatar')">
                </div>
              </td>
              <td><strong>{{ a.name }}</strong></td>
              <td>{{ a.email }}</td>
              <td>{{ formatDateTime(resolveLastAccess(a)) }}</td>
              <td>
                <span class="status-badge" :class="a.active !== false ? 'approved' : 'rejected'">
                  {{ a.active !== false ? 'Activo' : 'Inactivo' }}
                </span>
              </td>
              <td>
                <div class="admin-entity-actions">
                  <button class="action-btn edit" type="button" title="Editar" @click="openModal(a)">
                    <i class="fas fa-edit"></i>
                  </button>
                  <button class="action-btn delete" type="button" title="Eliminar" :disabled="a.id === currentUserId" @click="deleteAdmin(a.id)">
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

    <!-- Modal de administrador -->
    <AdminModal :show="showModal" :title="editing ? 'Editar administrador' : 'Nuevo administrador'" max-width="560px" @close="closeModal">
      <div class="admin-entity-filters__form">
        <!-- Foto de perfil: solo para el administrador actual -->
        <div v-if="isEditingCurrentAdmin" class="form-group admin-entity-filters__form--full admin-photo-upload-section">
          <label>Foto de perfil</label>
          <div class="admin-photo-upload">
            <div class="admin-photo-preview-wrap">
              <img
                :src="photoPreview || resolveMediaUrl(form.image, 'avatar')"
                class="admin-photo-preview"
                alt="Foto de perfil"
                @error="(e) => handleMediaError(e, form.image, 'avatar')"
              >
              <span class="admin-photo-state-icon" aria-hidden="true">
                <i class="fas fa-user-shield"></i>
              </span>
            </div>
            <div class="admin-photo-controls">
              <label for="admin-photo-input" class="admin-photo-trigger" :class="{ 'is-disabled': uploadingPhoto }">
                <i :class="uploadingPhoto ? 'fas fa-circle-notch fa-spin' : 'fas fa-camera-retro'"></i>
                {{ uploadingPhoto ? 'Subiendo foto...' : 'Cambiar foto de perfil' }}
              </label>
              <input
                id="admin-photo-input"
                type="file"
                accept="image/jpeg,image/png,image/webp"
                class="sr-only"
                :disabled="uploadingPhoto"
                @change="onPhotoSelected"
              >
              <p class="admin-photo-hint">JPG, PNG o WebP. Máx. 2 MB.</p>
            </div>
          </div>
        </div>
        <div class="form-group admin-entity-filters__form--full">
          <label for="admin-name">
            Nombre *
            <AdminInfoTooltip text="Nombre completo del administrador. Se muestra en el perfil del panel." />
          </label>
          <input id="admin-name" v-model="form.name" class="form-control" :class="{ 'is-invalid': errors.name }" @input="validateField('name')">
          <p v-if="errors.name" class="form-error">{{ errors.name }}</p>
        </div>
        <div class="form-group admin-entity-filters__form--full">
          <label for="admin-email">
            Email *
            <AdminInfoTooltip text="Correo de acceso al panel. Debe ser único por administrador." />
          </label>
          <input id="admin-email" v-model="form.email" type="email" class="form-control" :class="{ 'is-invalid': errors.email }" @input="validateField('email')">
          <p v-if="errors.email" class="form-error">{{ errors.email }}</p>
        </div>
        <div v-if="!editing" class="form-group admin-entity-filters__form--full">
          <label for="admin-password">
            Contraseña *
            <AdminInfoTooltip text="Contraseña de acceso. Mínimo 8 caracteres. No se puede recuperar desde aquí si se pierde." />
          </label>
          <input id="admin-password" v-model="form.password" type="password" class="form-control" :class="{ 'is-invalid': errors.password }" @input="validateField('password')">
          <p v-if="errors.password" class="form-error">{{ errors.password }}</p>
        </div>
        <AdminToggleSwitch
          id="admin-active"
          class="form-group admin-entity-filters__toggle"
          v-model="form.active"
          title="Activo"
          description="Permite que este administrador acceda al panel."
        />
      </div>

      <template #footer>
        <button class="btn btn-secondary" type="button" @click="closeModal">Cancelar</button>
        <button class="btn btn-primary" type="button" @click="saveAdmin">
          {{ editing ? 'Guardar cambios' : 'Crear administrador' }}
        </button>
      </template>
    </AdminModal>
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import { authHttp } from '../../../services/http'
import { updateProfile } from '../../../services/authApi'
import { handleMediaError, resolveMediaUrl } from '../../../utils/media'
import { useSnackbarSystem } from '../../../composables/useSnackbarSystem'
import { useAlertSystem } from '../../../composables/useAlertSystem'
import { useSession } from '../../../composables/useSession'
import { useAdminPagination } from '../composables/useAdminPagination'
import AdminCard from '../components/AdminCard.vue'
import AdminEmptyState from '../components/AdminEmptyState.vue'
import AdminInfoTooltip from '../components/AdminInfoTooltip.vue'
import AdminModal from '../components/AdminModal.vue'
import AdminPagination from '../components/AdminPagination.vue'
import AdminPageHeader from '../components/AdminPageHeader.vue'
import AdminTableShimmer from '../components/AdminTableShimmer.vue'
import AdminToggleSwitch from '../components/AdminToggleSwitch.vue'

const { showSnackbar } = useSnackbarSystem()
const { showAlert } = useAlertSystem()
const { user, token, saveSession } = useSession()
const currentUserId = ref(user.value?.id)
const admins = ref([])
const loading = ref(true)
const showModal = ref(false)
const editing = ref(null)
const errors = ref({})
const emptyForm = { name: '', email: '', password: '', active: true, image: '' }
const form = ref({ ...emptyForm })
const photoPreview = ref(null)
const uploadingPhoto = ref(false)
const isEditingCurrentAdmin = computed(() => {
  if (editing.value === null || editing.value === undefined) return false
  return String(editing.value) === String(currentUserId.value)
})

const pagination = useAdminPagination(admins, {
  initialPageSize: 10,
  pageSizeOptions: [10, 20, 50],
})

function validateField(field) {
  errors.value[field] = ''
  if (field === 'name' && !form.value.name?.trim()) errors.value.name = 'El nombre es requerido'
  if (field === 'email') {
    if (!form.value.email?.trim()) errors.value.email = 'El email es requerido'
    else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(form.value.email)) errors.value.email = 'Email inválido'
  }
  if (field === 'password' && !editing.value && form.value.password.length < 6) errors.value.password = 'Mínimo 6 caracteres'
}

function resolveLastAccess(admin) {
  return admin?.last_access || admin?.last_login || admin?.last_access_at || null
}

function formatDateTime(value) {
  if (!value) return 'Sin registro'
  const date = new Date(value)
  if (Number.isNaN(date.getTime())) return 'Sin registro'
  return date.toLocaleString('es-CO')
}

function openModal(admin = null) {
  editing.value = admin ? admin.id : null
  form.value = admin
    ? {
        name: admin.name || '',
        email: admin.email || '',
        password: '',
        active: admin.active !== false,
        image: admin.image || '',
      }
    : { ...emptyForm }
  errors.value = {}
  photoPreview.value = null
  showModal.value = true
}

async function onPhotoSelected(event) {
  const file = event.target.files?.[0]
  if (!file) return

  const isValidType = ['image/jpeg', 'image/png', 'image/webp'].includes(file.type)
  if (!isValidType) {
    showSnackbar({ type: 'warning', message: 'Formato no permitido. Usa JPG, PNG o WebP.' })
    event.target.value = ''
    return
  }

  const maxSizeBytes = 2 * 1024 * 1024
  if (file.size > maxSizeBytes) {
    showSnackbar({ type: 'warning', message: 'La imagen supera el tamaño máximo de 2 MB.' })
    event.target.value = ''
    return
  }

  // Previsualización local
  const reader = new FileReader()
  reader.onload = (e) => { photoPreview.value = e.target.result }
  reader.readAsDataURL(file)

  // Subir al servidor via /auth/profile
  uploadingPhoto.value = true
  try {
    const fd = new FormData()
    fd.append('name', form.value.name || user.value?.name || '')
    fd.append('image', file)
    const result = await updateProfile(fd)
    const newImage = result?.data?.image || result?.image || ''
    if (newImage) {
      form.value.image = newImage
      // Actualizar imagen en la sesión del admin actual
      saveSession(token.value, { ...user.value, image: newImage })
    }
    showSnackbar({ type: 'success', message: 'Foto de perfil actualizada' })
    await loadAdmins()
  } catch {
    photoPreview.value = null
    showSnackbar({ type: 'error', message: 'Error al subir la foto' })
  } finally {
    uploadingPhoto.value = false
    event.target.value = ''
  }
}

function closeModal() {
  showModal.value = false
  editing.value = null
}

async function loadAdmins() {
  loading.value = true
  try {
    const { data } = await authHttp.get('/admin/administrators')
    const rows = Array.isArray(data?.data) ? data.data : (Array.isArray(data) ? data : [])
    admins.value = rows.map((admin) => ({
      ...admin,
      active: admin.active !== undefined ? Boolean(admin.active) : !Boolean(admin.is_blocked),
    }))
  } catch { admins.value = [] } finally { loading.value = false }
}

async function saveAdmin() {
  ;['name', 'email'].forEach(validateField)
  if (!editing.value) validateField('password')
  if (Object.values(errors.value).some(Boolean)) return

  try {
    const payload = {
      name: form.value.name,
      email: form.value.email,
      active: Boolean(form.value.active),
    }
    if (form.value.image) payload.image = form.value.image
    if (!editing.value) payload.password = form.value.password
    if (editing.value && form.value.password) payload.password = form.value.password
    if (editing.value) {
      await authHttp.put(`/admin/administrators/${editing.value}`, payload)
      showSnackbar({ type: 'success', message: 'Administrador actualizado' })
    } else {
      await authHttp.post('/admin/administrators', payload)
      showSnackbar({ type: 'success', message: 'Administrador creado' })
    }
    showModal.value = false
    await loadAdmins()
  } catch { showSnackbar({ type: 'error', message: 'Error al guardar administrador' }) }
}

function deleteAdmin(id) {
  if (id === currentUserId.value) {
    showSnackbar({ type: 'warning', message: 'No puedes eliminarte a ti mismo' })
    return
  }

  showAlert({
    type: 'warning',
    title: 'Eliminar administrador',
    message: '¿Deseas eliminar este administrador? Esta acción no se puede deshacer.',
    actions: [
      { text: 'Cancelar', style: 'secondary' },
      {
        text: 'Eliminar',
        style: 'danger',
        callback: async () => {
          try {
            await authHttp.delete(`/admin/administrators/${id}`)
            showSnackbar({ type: 'success', message: 'Administrador eliminado' })
            await loadAdmins()
          } catch {
            showSnackbar({ type: 'error', message: 'Error al eliminar' })
          }
        },
      },
    ],
  })
}

onMounted(loadAdmins)
</script>

<style scoped>
.admin-photo-upload-section {
  border: 1px solid var(--admin-border);
  border-radius: 1rem;
  padding: 1.4rem;
  background: var(--admin-bg-soft);
}

.admin-photo-upload {
  display: flex;
  align-items: center;
  gap: 1.6rem;
  flex-wrap: wrap;
}

.admin-photo-preview-wrap {
  position: relative;
  width: fit-content;
}

.admin-photo-preview {
  width: 7.2rem;
  height: 7.2rem;
  border-radius: 50%;
  object-fit: cover;
  border: 2px solid var(--admin-border);
  flex-shrink: 0;
}

.admin-photo-state-icon {
  position: absolute;
  right: -0.2rem;
  bottom: -0.2rem;
  width: 2.4rem;
  height: 2.4rem;
  border-radius: 999px;
  background: var(--admin-primary);
  color: #fff;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  box-shadow: 0 4px 10px rgba(0, 119, 182, 0.28);
}

.admin-photo-controls {
  display: flex;
  flex-direction: column;
  gap: 0.6rem;
}

.admin-photo-trigger {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 0.7rem;
  padding: 0.8rem 1.25rem;
  border-radius: 999px;
  border: 1px solid var(--admin-border);
  background: #fff;
  color: var(--admin-primary);
  font-size: 1.3rem;
  font-weight: 700;
  line-height: 1;
  cursor: pointer;
  white-space: nowrap;
  transition: transform 0.2s ease, box-shadow 0.2s ease, border-color 0.2s ease;
}

.admin-photo-trigger:hover {
  border-color: rgba(0, 119, 182, 0.32);
  box-shadow: 0 6px 16px rgba(15, 55, 96, 0.12);
  transform: translateY(-1px);
}

.admin-photo-trigger.is-disabled {
  opacity: 0.65;
  pointer-events: none;
}

.admin-photo-hint {
  font-size: 1.2rem;
  color: var(--admin-text-light);
  margin: 0;
}

.sr-only {
  position: absolute;
  width: 1px;
  height: 1px;
  padding: 0;
  margin: -1px;
  overflow: hidden;
  clip: rect(0, 0, 0, 0);
  white-space: nowrap;
  border: 0;
}

@media (max-width: 640px) {
  .admin-photo-upload {
    align-items: flex-start;
    gap: 1.2rem;
  }

  .admin-photo-trigger {
    width: 100%;
  }
}
</style>
