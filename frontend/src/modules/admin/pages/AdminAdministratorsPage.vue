<template>
  <div class="admin-entity-page">
    <AdminPageHeader icon="fas fa-user-shield" title="Administradores" subtitle="Gestiona los usuarios con rol de administrador." :breadcrumbs="[{ label: 'Dashboard', to: '/admin' }, { label: 'Administradores' }]">
      <template #actions>
        <button class="btn btn-primary" type="button" @click="openModal()"><i class="fas fa-user-plus"></i> Nuevo administrador</button>
      </template>
    </AdminPageHeader>

    <AdminCard title="Administradores del sistema" icon="fas fa-user-shield" :flush="true">
      <AdminTableShimmer v-if="loading" :rows="5" :columns="['circle','line','line','pill','line','pill','btn']" />
      <AdminEmptyState v-else-if="admins.length === 0" icon="fas fa-user-shield" title="Sin administradores" description="Agrega administradores para gestionar la tienda." />
      <div v-else class="table-responsive">
        <table class="dashboard-table">
          <thead>
            <tr>
              <th>Avatar</th>
              <th>Nombre</th>
              <th>Email</th>
              <th>Rol</th>
              <th>Último acceso</th>
              <th>Estado</th>
              <th>Acciones</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="a in pagination.paginatedItems" :key="a.id">
              <td>
                <div class="admin-avatar">
                  <img :src="a.avatar_url || '/assets/foundnotimages/user-default.png'" :alt="a.name">
                </div>
              </td>
              <td><strong>{{ a.name }}</strong></td>
              <td>{{ a.email }}</td>
              <td><span class="status-badge approved">{{ a.role || 'admin' }}</span></td>
              <td>{{ a.last_login || '—' }}</td>
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
        <div class="form-group">
          <label for="admin-role">
            Rol
            <AdminInfoTooltip text="Nivel de permisos. Administrador tiene acceso general; Super Admin tiene acceso total incluyendo ajustes críticos del sistema." />
          </label>
          <select id="admin-role" v-model="form.role" class="form-control">
            <option value="admin">Administrador</option>
            <option value="super_admin">Super Admin</option>
          </select>
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
import { onMounted, ref } from 'vue'
import { authHttp } from '../../../services/http'
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
const { user } = useSession()
const currentUserId = ref(user.value?.id)
const admins = ref([])
const loading = ref(true)
const showModal = ref(false)
const editing = ref(null)
const errors = ref({})
const emptyForm = { name: '', email: '', password: '', role: 'admin', active: true }
const form = ref({ ...emptyForm })

const pagination = useAdminPagination(admins, {
  initialPageSize: 10,
  pageSizeOptions: [10, 20, 50],
})

function validateField(field) {
  errors.value[field] = ''
  if (field === 'name' && !form.value.name?.trim()) errors.value.name = 'El nombre es requerido'
  if (field === 'email') {
    if (!form.value.email?.trim()) errors.value.email = 'El email es requerido'
    else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(form.value.email)) errors.value.email = 'Email invalido'
  }
  if (field === 'password' && !editing.value && form.value.password.length < 6) errors.value.password = 'Minimo 6 caracteres'
}

function openModal(admin = null) {
  editing.value = admin ? admin.id : null
  form.value = admin ? { ...admin, password: '' } : { ...emptyForm }
  errors.value = {}
  showModal.value = true
}

function closeModal() {
  showModal.value = false
  editing.value = null
}

async function loadAdmins() {
  loading.value = true
  try {
    const { data } = await authHttp.get('/admin/administrators')
    admins.value = data.data || data || []
  } catch { admins.value = [] } finally { loading.value = false }
}

async function saveAdmin() {
  ;['name', 'email'].forEach(validateField)
  if (!editing.value) validateField('password')
  if (Object.values(errors.value).some(Boolean)) return

  try {
    const payload = { ...form.value }
    if (editing.value && !payload.password) delete payload.password
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
    message: '¿Deseas eliminar este administrador? Esta accion no se puede deshacer.',
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
/* Sin estilos locales — usa estilos globales del dashboard */
</style>
