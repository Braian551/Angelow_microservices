<template>
  <div class="admin-entity-page admin-administrators-page">
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
      <div class="admin-administrators-page admin-administrators-page--modal">
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
import { handleMediaError, resolveMediaUrl } from '../../../utils/media'
import AdminCard from '../components/AdminCard.vue'
import AdminEmptyState from '../components/AdminEmptyState.vue'
import AdminInfoTooltip from '../components/AdminInfoTooltip.vue'
import AdminModal from '../components/AdminModal.vue'
import AdminPagination from '../components/AdminPagination.vue'
import AdminPageHeader from '../components/AdminPageHeader.vue'
import AdminTableShimmer from '../components/AdminTableShimmer.vue'
import AdminToggleSwitch from '../components/AdminToggleSwitch.vue'
import { useAdminAdministrators } from '../composables/useAdminAdministrators'
import '../views/AdminAdministratorsPage.css'

// =====================================================
// Orquestación de la vista
// =====================================================
const {
  admins,
  closeModal,
  currentUserId,
  deleteAdmin,
  editing,
  errors,
  form,
  formatDateTime,
  isEditingCurrentAdmin,
  loading,
  onPhotoSelected,
  openModal,
  pagination,
  photoPreview,
  resolveLastAccess,
  saveAdmin,
  showModal,
  uploadingPhoto,
  validateField,
} = useAdminAdministrators()
</script>
