import { computed, onMounted, ref } from 'vue'
import { authHttp } from '../../../services/http'
import { updateProfile } from '../../../services/authApi'
import { useSnackbarSystem } from '../../../composables/useSnackbarSystem'
import { useAlertSystem } from '../../../composables/useAlertSystem'
import { useSession } from '../../../composables/useSession'
import { useAdminPagination } from './useAdminPagination'

/**
 * Composable para la gestión de administradores del panel.
 * Encapsula CRUD de administradores, validación de formularios,
 * carga de foto de perfil y paginación reutilizando useAdminPagination.
 * Reutiliza useSnackbarSystem y useAlertSystem para feedback visual.
 */
export function useAdminAdministrators() {
  // =====================================================
  // Dependencias y composables reutilizados
  // =====================================================
  const { showSnackbar } = useSnackbarSystem()
  const { showAlert } = useAlertSystem()
  const { user, token, saveSession } = useSession()

  // =====================================================
  // Estado principal
  // =====================================================
  const currentUserId = ref(user.value?.id)
  const admins = ref([])
  const loading = ref(true)
  const saving = ref(false)
  const showModal = ref(false)
  const editing = ref(null)
  const errors = ref({})
  const photoPreview = ref(null)
  const uploadingPhoto = ref(false)

  // =====================================================
  // Filtros y paginación
  // =====================================================
  const pagination = useAdminPagination(admins, {
    initialPageSize: 10,
    pageSizeOptions: [10, 20, 50],
  })

  // =====================================================
  // Formulario
  // =====================================================
  const emptyForm = {
    name: '',
    email: '',
    password: '',
    active: true,
    image: '',
  }

  const form = ref({ ...emptyForm })

  // =====================================================
  // Contraseña y administrador actual
  // =====================================================
  const isEditingCurrentAdmin = computed(() => {
    if (editing.value === null || editing.value === undefined) return false
    return String(editing.value) === String(currentUserId.value)
  })

  /** Valida un campo específico del formulario y actualiza errors.value. */
  function validateField(field) {
    errors.value[field] = ''

    if (field === 'name' && !form.value.name?.trim()) {
      errors.value.name = 'El nombre es requerido'
    }

    if (field === 'email') {
      if (!form.value.email?.trim()) {
        errors.value.email = 'El email es requerido'
      } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(form.value.email)) {
        errors.value.email = 'Email inválido'
      }
    }

    if (field === 'password' && !editing.value && form.value.password.length < 6) {
      errors.value.password = 'Mínimo 6 caracteres'
    }
  }

  // =====================================================
  // Validaciones
  // =====================================================
  /** Valida todos los campos del formulario. Retorna true si no hay errores. */
  function validateForm() {
    ;['name', 'email'].forEach(validateField)

    if (!editing.value) {
      validateField('password')
    }

    return !Object.values(errors.value).some(Boolean)
  }

  // =====================================================
  // Helpers internos
  // =====================================================
  /** Extrae la fecha del último acceso del administrador intentando varios campos. */
  function resolveLastAccess(admin) {
    return admin?.last_access || admin?.last_login || admin?.last_access_at || null
  }

  /** Formatea una fecha ISO a cadena legible en español (locale es-CO). */
  function formatDateTime(value) {
    if (!value) return 'Sin registro'
    const date = new Date(value)
    if (Number.isNaN(date.getTime())) return 'Sin registro'
    return date.toLocaleString('es-CO')
  }

  /** Limpia o carga los datos de un administrador en el formulario. */
  function resetForm(admin = null) {
    form.value = admin
      ? {
          name: admin.name || '',
          email: admin.email || '',
          password: '',
          active: admin.active !== false,
          image: admin.image || '',
        }
      : { ...emptyForm }
  }

  // =====================================================
  // Carga
  // =====================================================
  /** Obtiene la lista completa de administradores desde el backend. */
  async function loadAdmins() {
    loading.value = true
    try {
      const { data } = await authHttp.get('/admin/administrators')
      const rows = Array.isArray(data?.data) ? data.data : (Array.isArray(data) ? data : [])

      admins.value = rows.map((admin) => ({
        ...admin,
        active: admin.active !== undefined ? Boolean(admin.active) : !Boolean(admin.is_blocked),
      }))
    } catch {
      admins.value = []
    } finally {
      loading.value = false
    }
  }

  // =====================================================
  // Gestión del formulario y modal
  // =====================================================
  /** Abre el modal en modo creación o edición con los datos del admin indicado. */
  function openModal(admin = null) {
    editing.value = admin ? admin.id : null
    resetForm(admin)
    errors.value = {}
    photoPreview.value = null
    showModal.value = true
  }

  /** Cierra el modal y limpia el estado de edición. */
  function closeModal() {
    showModal.value = false
    editing.value = null
  }

  // =====================================================
  // Acciones CRUD
  // =====================================================
  /** Crea o actualiza un administrador según editing.value. */
  async function saveAdmin() {
    if (saving.value) return

    if (!validateForm()) return

    saving.value = true
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
    } catch {
      showSnackbar({ type: 'error', message: 'Error al guardar administrador' })
    } finally {
      saving.value = false
    }
  }

  /** Muestra confirmación y elimina un administrador por su ID. */
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

  // =====================================================
  // Estado y bloqueo de foto de perfil
  // =====================================================
  /** Maneja la selección de archivo de foto: valida tipo/tamaño, previsualiza y sube vía updateProfile. */
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

    // Previsualiza la imagen local antes de reutilizar updateProfile de authApi.js.
    const reader = new FileReader()
    reader.onload = (loadEvent) => {
      photoPreview.value = loadEvent.target.result
    }
    reader.readAsDataURL(file)

    // Reutiliza el flujo actual de /auth/profile para no cambiar el contrato del backend.
    uploadingPhoto.value = true
    try {
      const fd = new FormData()
      fd.append('name', form.value.name || user.value?.name || '')
      fd.append('image', file)

      const result = await updateProfile(fd)
      const newImage = result?.data?.image || result?.image || ''

      if (newImage) {
        form.value.image = newImage
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

  // =====================================================
  // Ciclo de vida
  // =====================================================
  onMounted(loadAdmins)

  // =====================================================
  // API pública del composable
  // =====================================================
  return {
    admins,
    closeModal,
    currentUserId,
    deleteAdmin,
    editing,
    errors,
    form,
    formatDateTime,
    isEditingCurrentAdmin,
    loadAdmins,
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
  }
}
