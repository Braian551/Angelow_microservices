<template>
  <!-- ============================================
       Vista principal de gestión de códigos de descuento
       Panel administrativo para crear, editar, eliminar
       y gestionar promociones con envío masivo y campañas
  ============================================ -->
  <div class="admin-discount-codes-page">
    <AdminPageHeader
      icon="fas fa-tags"
      title="Códigos de descuento"
      subtitle="Gestiona promociones con detalles claros, control de vigencia y seguimiento del estado."
      :breadcrumbs="[{ label: 'Dashboard', to: '/admin' }, { label: 'Códigos de descuento' }]"
    >
      <template #actions>
        <AdminExportActions
          tone="header"
          :disabled="filteredCodes.length === 0"
          :excel-loading="exportingFormat === 'excel'"
          :pdf-loading="exportingFormat === 'pdf'"
          @excel="exportCodes('excel')"
          @pdf="exportCodes('pdf')"
        />
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
        <div class="admin-discount-codes-page admin-discount-codes-page--modal">
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
      <div class="admin-discount-codes-page admin-discount-codes-page--modal">
        <div class="editor-grid editor-grid--discounts admin-editor-grid">
        <div>
          <div class="form-group">
            <div class="discount-code-field__header">
              <label for="discount-code-field">
                Código *
                <AdminInfoTooltip text="Palabra clave que el cliente escribe al finalizar la compra. Puedes escribirla manualmente o generar una propuesta aleatoria antes de guardar." />
              </label>
              <button
                v-if="autoGenerateCode"
                type="button"
                class="discount-code-field__generate"
                @click="regenerateAutomaticCode"
              >
                <i class="fas fa-sync-alt"></i>
                Generar otro
              </button>
            </div>
            <input
              id="discount-code-field"
              v-model.trim="form.code"
              type="text"
              class="form-control"
              :class="{ 'is-invalid': formErrors.code }"
              :readonly="autoGenerateCode"
              placeholder="Ej. PROMO-AB12CD"
              @input="handleCodeInput"
            >
            <p class="discount-code-field__hint">
              {{ autoGenerateCode
                ? 'Modo automático activo. Se propone un código aleatorio en mayúsculas que puedes regenerar antes de guardar.'
                : 'Escribe entre 4 y 20 caracteres en mayúsculas, números o guiones.' }}
            </p>
            <p v-if="formErrors.code" class="form-error">{{ formErrors.code }}</p>
            <div class="discount-code-field__mode">
              <AdminToggleSwitch
                id="discount-code-auto-generate"
                :model-value="autoGenerateCode"
                layout="inline"
                label="Generar código automáticamente"
                @update:modelValue="handleCodeGenerationToggle"
              />
            </div>
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
      <div class="admin-discount-codes-page admin-discount-codes-page--modal">
        <div class="campaign-modal">
        <p class="campaign-modal__intro">
          Envía un código de descuento a todos los clientes con notificación interna, correo o ambos canales.
        </p>

        <div class="campaign-modal__availability" :class="{ 'campaign-modal__availability--empty': !campaignCustomersLoading && !massCampaignHasRecipients }">
          <i :class="campaignCustomersLoading ? 'fas fa-spinner fa-spin' : massCampaignHasRecipients ? 'fas fa-users' : 'fas fa-user-slash'"></i>
          <div>
            <strong>{{ massCampaignAvailabilityTitle }}</strong>
            <p>{{ massCampaignAvailabilityMessage }}</p>
          </div>
        </div>

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
      </div>

      <template #footer>
        <button class="btn btn-secondary" type="button" :disabled="campaignSubmitting" @click="closeMassCampaignModal">Cancelar</button>
        <button class="btn btn-primary" type="button" :disabled="campaignSubmitting || campaignCustomersLoading || !massCampaignHasRecipients" @click="submitMassCampaign">
          <i class="fas fa-paper-plane"></i>
          {{ campaignSubmitting ? 'Enviando...' : 'Enviar masivo' }}
        </button>
      </template>
    </AdminModal>

    <AdminModal :show="showSpecificCampaignModal" title="Descuento para usuarios específicos" max-width="1180px" @close="closeSpecificCampaignModal">
      <div class="admin-discount-codes-page admin-discount-codes-page--modal">
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
// ============================================
// Módulo de gestión de códigos de descuento
// CRUD, envío masivo y campañas a usuarios
// ============================================

// =====================================================
// Imports de la vista y componentes compartidos
// =====================================================
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
import { useAdminDiscountCodes } from '../composables/useAdminDiscountCodes'
import '../views/AdminDiscountCodesPage.css'

// =====================================================
// Orquestación de la lógica administrativa
// =====================================================
const {
  activeFilterCount,                    // Conteo de filtros activos aplicados
  autoGenerateCode,                     // Indica si el código se genera automáticamente
  campaignCodeOptions,                  // Códigos disponibles para usar en campañas
  campaignCustomers,                    // Lista completa de clientes para campañas
  campaignCustomersLoading,             // Estado de carga de clientes en campaña
  campaignSubmitting,                   // Indica si una campaña está en proceso de envío
  clearFilters,                         // Limpia todos los filtros de búsqueda activos
  clearSpecificCustomerSelection,       // Deselecciona todos los clientes elegidos
  closeDetailModal,                     // Cierra el modal de detalle del código
  closeEditorModal,                     // Cierra el modal de edición/creación de código
  closeMassCampaignModal,               // Cierra el modal de envío masivo
  closeSpecificCampaignModal,           // Cierra el modal de campaña a usuarios específicos
  codeStatusClass,                      // Retorna la clase CSS según el estado del código
  codeStatusLabel,                      // Retorna la etiqueta de texto del estado
  confirmDeleteCode,                    // Muestra confirmación y elimina un código
  discountStats,                        // Estadísticas resumidas de los códigos
  editingCodeId,                        // ID del código que se está editando
  exportCodes,                          // Exporta códigos filtrados en Excel o PDF
  exportingFormat,                      // Formato de exportación en curso (excel/pdf)
  filteredCampaignCustomers,            // Clientes filtrados por búsqueda en campaña
  filteredCodes,                        // Códigos filtrados según filtros activos
  filters,                              // Objeto reactivo con filtros de búsqueda
  form,                                 // Datos del formulario de creación/edición
  formErrors,                           // Errores de validación del formulario
  formatCurrency,                       // Formatea un número como moneda local
  formatDateTime,                       // Formatea una fecha con hora legible
  formatDiscountValue,                  // Formatea el valor del descuento según tipo
  formatShortDate,                      // Formatea una fecha en formato corto
  handleCodeGenerationToggle,           // Alterna entre generación manual/automática
  handleCodeInput,                      // Procesa la entrada manual del código
  loading,                              // Estado de carga general de la página
  massCampaignAvailabilityMessage,      // Mensaje de disponibilidad en campaña masiva
  massCampaignAvailabilityTitle,        // Título de disponibilidad en campaña masiva
  massCampaignErrors,                   // Errores de validación de campaña masiva
  massCampaignForm,                     // Datos del formulario de campaña masiva
  massCampaignHasRecipients,            // Indica si hay destinatarios disponibles
  navigateToSpecificCampaignPage,       // Navega a la página de campaña específica
  openCreateModal,                      // Abre el modal para crear un nuevo código
  openDetailModal,                      // Abre el modal de detalle de un código
  openEditFromDetail,                   // Abre edición desde el modal de detalle
  openEditModal,                        // Abre el modal para editar un código
  openMassCampaignModal,                // Abre el modal de envío masivo
  openSpecificCampaignModal,            // Abre el modal de campaña específica
  pagination,                           // Estado y configuración de paginación
  regenerateAutomaticCode,              // Genera un nuevo código automático aleatorio
  remainingUsesLabel,                   // Texto descriptivo de usos restantes
  saveCode,                             // Guarda (crea o actualiza) el código
  selectedCode,                         // Código seleccionado para ver detalle
  selectedSpecificCode,                 // Código elegido en campaña específica
  selectAllFilteredCustomers,           // Selecciona todos los clientes visibles
  showDetailModal,                      // Visibilidad del modal de detalle
  showEditorModal,                      // Visibilidad del modal de edición
  showMassCampaignModal,                // Visibilidad del modal de campaña masiva
  showSpecificCampaignModal,            // Visibilidad del modal de campaña específica
  specificCampaignErrors,               // Errores de validación de campaña específica
  specificCampaignForm,                 // Datos del formulario de campaña específica
  specificCampaignSearch,               // Texto de búsqueda para clientes en campaña
  submitMassCampaign,                   // Envía la campaña masiva de descuentos
  submitSpecificCampaign,               // Envía descuentos a usuarios específicos
  userInitials,                         // Obtiene las iniciales de un usuario
  validateField,                        // Valida un campo individual del formulario
  validateMassCampaignField,            // Valida un campo de campaña masiva
  validateSpecificCampaignField,        // Valida un campo de campaña específica
} = useAdminDiscountCodes()
</script>


