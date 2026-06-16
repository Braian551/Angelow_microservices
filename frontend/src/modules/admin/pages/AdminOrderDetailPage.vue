<template>
  <div class="admin-order-detail-page">
    <AdminPageHeader
      icon="fas fa-shopping-bag"
      :title="headerTitle"
      subtitle="Detalle completo del pedido con la misma experiencia del panel administrativo."
      :breadcrumbs="[{ label: 'Dashboard', to: '/admin' }, { label: 'Órdenes', to: '/admin/ordenes' }, { label: breadcrumbOrderLabel }]"
    >
      <template #actions>
        <RouterLink to="/admin/ordenes" class="btn btn-secondary">
          <i class="fas fa-arrow-left"></i> Volver
        </RouterLink>
      </template>
    </AdminPageHeader>

    <div v-if="loading" class="order-detail-loading-grid">
      <AdminCard title="Resumen de la orden" icon="fas fa-clipboard-list">
        <AdminTableShimmer :rows="4" :columns="['line', 'line', 'line', 'line']" />
      </AdminCard>
      <AdminCard title="Productos" icon="fas fa-box" :flush="true">
        <AdminTableShimmer :rows="4" :columns="['line', 'line', 'line', 'line']" />
      </AdminCard>
    </div>

    <template v-else-if="order">
      <AdminCard title="Resumen de la Orden" icon="fas fa-file-invoice">
        <template #headerActions>
          <div class="summary-actions">
            <button type="button" class="order-toolbar-btn order-toolbar-btn--neutral" @click="openEditModal">
              <span class="order-toolbar-btn__icon"><i class="fas fa-edit"></i></span>
              <span>Editar</span>
            </button>
            <button type="button" class="order-toolbar-btn order-toolbar-btn--primary" @click="openStatusModal">
              <span class="order-toolbar-btn__icon"><i class="fas fa-sync-alt"></i></span>
              <span>Cambiar estado</span>
            </button>
            <button type="button" class="order-toolbar-btn order-toolbar-btn--accent" @click="openPaymentStatusModal">
              <span class="order-toolbar-btn__icon"><i class="fas fa-credit-card"></i></span>
              <span>Cambiar pago</span>
            </button>
          </div>
        </template>

        <div class="order-spotlight">
          <div class="order-spotlight__main">
            <span class="order-spotlight__eyebrow">Seguimiento del pedido</span>
            <h3>{{ order.order_number }}</h3>
            <p>{{ order.customer_name }}<span v-if="order.customer_email"> · {{ order.customer_email }}</span></p>
          </div>
          <div class="order-spotlight__chips">
            <span class="status-badge" :class="statusBadgeClass(order.status)">{{ statusLabel(order.status) }}</span>
            <span class="status-badge" :class="paymentBadgeClass(order.payment_status)">{{ paymentLabel(order.payment_status) }}</span>
          </div>
        </div>

        <div class="order-highlight-grid">
          <div class="order-highlight-card order-highlight-card--strong">
            <span>Total del pedido</span>
            <strong>{{ formatCurrency(order.total) }}</strong>
            <small>{{ items.length }} {{ items.length === 1 ? 'producto' : 'productos' }}</small>
          </div>
          <div class="order-highlight-card">
            <span>Método de pago</span>
            <strong>{{ paymentMethodLabel(order.payment_method) }}</strong>
            <small>{{ paymentLabel(order.payment_status) }}</small>
          </div>
          <div class="order-highlight-card">
            <span>Entrega</span>
            <strong>{{ deliveryLocationLabel }}</strong>
            <small>{{ shippingAddressOriginLabel }}</small>
          </div>
        </div>

        <div class="order-summary-panels">
          <section class="order-summary-panel">
            <div class="order-summary-panel__header">
              <span>Datos del pedido</span>
              <small>Información principal del registro</small>
            </div>
            <div class="admin-detail-summary order-summary-panel__body">
              <div class="admin-detail-summary__row">
                <span>Número de orden</span>
                <strong>{{ order.order_number }}</strong>
              </div>
              <div class="admin-detail-summary__row admin-detail-summary__row--stack">
                <span>Fecha</span>
                <strong>{{ formatDateTime(order.created_at) }}</strong>
              </div>
              <div class="admin-detail-summary__row">
                <span>Estado</span>
                <strong><span class="status-badge" :class="statusBadgeClass(order.status)">{{ statusLabel(order.status) }}</span></strong>
              </div>
              <div class="admin-detail-summary__row">
                <span>Estado de pago</span>
                <strong><span class="status-badge" :class="paymentBadgeClass(order.payment_status)">{{ paymentLabel(order.payment_status) }}</span></strong>
              </div>
            </div>
          </section>

          <section class="order-summary-panel">
            <div class="order-summary-panel__header">
              <span>Cliente</span>
              <small>Contacto y datos de referencia</small>
            </div>
            <div class="admin-detail-summary order-summary-panel__body">
              <div class="admin-detail-summary__row">
                <span>Nombre</span>
                <strong>{{ order.customer_name }}</strong>
              </div>
              <div class="admin-detail-summary__row admin-detail-summary__row--stack">
                <span>Email</span>
                <strong>{{ order.customer_email || 'No disponible' }}</strong>
              </div>
              <div class="admin-detail-summary__row">
                <span>Teléfono</span>
                <strong>{{ order.customer_phone || 'No disponible' }}</strong>
              </div>
            </div>
          </section>

          <section class="order-summary-panel">
            <div class="order-summary-panel__header">
              <span>Pago y totales</span>
              <small>Montos y método aplicado</small>
            </div>
            <div class="admin-detail-summary order-summary-panel__body">
              <div class="admin-detail-summary__row">
                <span>Método de pago</span>
                <strong>{{ paymentMethodLabel(order.payment_method) }}</strong>
              </div>
              <div class="admin-detail-summary__row">
                <span>Subtotal</span>
                <strong>{{ formatCurrency(order.subtotal) }}</strong>
              </div>
              <div class="admin-detail-summary__row">
                <span>Envío</span>
                <strong>{{ formatCurrency(order.shipping_cost) }}</strong>
              </div>
              <div class="admin-detail-summary__row order-summary-panel__row--total">
                <span>Total</span>
                <strong>{{ formatCurrency(order.total) }}</strong>
              </div>
            </div>
          </section>
        </div>

        <div class="order-total-strip">
          <span>Total:</span>
          <strong>{{ formatCurrency(order.total) }}</strong>
        </div>
      </AdminCard>

      <section class="order-detail-stack">
        <AdminCard class="order-detail-card order-detail-card--address" title="Dirección de Envío" icon="fas fa-map-marker-alt">
            <div class="addr-card">
              <!-- Encabezado: alias y badge de dirección principal -->
              <div v-if="selectedShippingAddress" class="addr-card__header">
                <div class="addr-card__title-group">
                  <span class="addr-card__alias">
                    <i class="fas fa-bookmark"></i>
                    {{ selectedShippingAddress.alias || 'Dirección guardada' }}
                  </span>
                  <span class="addr-card__type-badge">{{ labelCheckoutAddressType(selectedShippingAddress.address_type) }}</span>
                </div>
                <span v-if="selectedShippingAddress.is_default" class="addr-card__default-badge">
                  <i class="fas fa-star"></i> Principal
                </span>
              </div>

              <div class="admin-detail-summary addr-card__body">
                <div class="admin-detail-summary__row">
                  <span>Destinatario</span>
                  <strong>{{ shippingRecipientLabel }}</strong>
                </div>
                <div class="admin-detail-summary__row admin-detail-summary__row--stack">
                  <span>Dirección</span>
                  <strong>{{ shippingStreetLabel }}</strong>
                </div>
                <div v-if="shippingComplementLabel" class="admin-detail-summary__row admin-detail-summary__row--stack">
                  <span>Complemento</span>
                  <strong>{{ shippingComplementLabel }}</strong>
                </div>
                <div class="admin-detail-summary__row">
                  <span>Barrio / zona</span>
                  <strong>{{ shippingZoneLabel }}</strong>
                </div>
                <div v-if="selectedShippingAddress" class="admin-detail-summary__row">
                  <span>Tipo de domicilio</span>
                  <strong>{{ labelCheckoutAddressType(selectedShippingAddress.address_type) }}</strong>
                </div>
                <div v-if="shippingBuildingTypeLabel" class="admin-detail-summary__row">
                  <span>Tipo de edificación</span>
                  <strong>{{ shippingBuildingTypeLabel }}</strong>
                </div>
                <div v-if="shippingBuildingNameLabel" class="admin-detail-summary__row">
                  <span>Edificio / conjunto</span>
                  <strong>{{ shippingBuildingNameLabel }}</strong>
                </div>
                <div v-if="selectedShippingAddress?.apartment_number" class="admin-detail-summary__row">
                  <span>Apto / oficina</span>
                  <strong>{{ selectedShippingAddress.apartment_number }}</strong>
                </div>
                <div v-if="selectedShippingAddress?.delivery_instructions" class="admin-detail-summary__row admin-detail-summary__row--stack">
                  <span>Indicaciones de entrega</span>
                  <strong>{{ selectedShippingAddress.delivery_instructions }}</strong>
                </div>
                <div v-if="order.notes" class="admin-detail-summary__row admin-detail-summary__row--stack">
                  <span>Notas de la orden</span>
                  <strong>{{ order.notes }}</strong>
                </div>
              </div>

              <!-- Pie: origen de la dirección -->
              <div class="addr-card__footer">
                <span class="addr-card__origin" :class="selectedShippingAddress ? 'addr-card__origin--saved' : 'addr-card__origin--manual'">
                  <i :class="selectedShippingAddress ? 'fas fa-check-circle' : 'fas fa-pen-to-square'"></i>
                  {{ shippingAddressOriginLabel }}
                </span>
              </div>

              <!-- Mapa de ubicación de entrega -->
              <AddressMapViewer
                :latitude="shippingMapCoords?.lat ?? null"
                :longitude="shippingMapCoords?.lng ?? null"
                :address-text="shippingAddressLine"
                height="230px"
              />
            </div>
        </AdminCard>

        <AdminCard class="order-detail-card order-detail-card--proof" title="Comprobante de Pago" icon="fas fa-file-invoice-dollar">
            <template v-if="paymentRecord?.proof_url && paymentRecord.proof_exists !== false && !paymentProofUnavailable">
              <div class="payment-proof-card">
                <!-- Botón de preview -> abre modal con lupa -->
                <button type="button" class="payment-proof-card__preview" :class="{ 'payment-proof-card__preview--file': !paymentProofIsImage }" @click="openProofModal">
                  <img v-if="paymentProofIsImage" :src="paymentRecord.proof_url" alt="Comprobante de pago" class="payment-proof-card__image" @error="handlePaymentProofError">
                  <div v-else class="payment-proof-card__file">
                    <i class="fas fa-file-pdf"></i>
                    <strong>{{ paymentRecord.proof_name || 'Documento adjunto' }}</strong>
                    <span>Toca para ver el comprobante</span>
                  </div>
                  <div class="payment-proof-card__overlay">
                    <i class="fas fa-search-plus"></i>
                    <span>Ver comprobante</span>
                  </div>
                </button>

                <div class="payment-proof-card__meta">
                  <div class="payment-proof-card__meta-item">
                    <span>Referencia</span>
                    <strong>{{ paymentRecord.reference_number || 'Sin referencia' }}</strong>
                  </div>
                  <div class="payment-proof-card__meta-item">
                    <span>Estado</span>
                    <strong>{{ paymentLabel(paymentRecord.status || order.payment_status) }}</strong>
                  </div>
                </div>
              </div>
            </template>
            <div v-else-if="paymentRecord?.proof_url" class="detail-empty detail-empty--soft payment-proof-card__missing">
              <i class="fas fa-image-slash"></i>
              <div>
                <strong>Comprobante no disponible.</strong>
                <p>No pudimos mostrar el comprobante en este momento.</p>
              </div>
            </div>
            <div v-else class="detail-empty detail-empty--soft">No hay comprobante de pago adjunto para esta orden.</div>
        </AdminCard>

        <AdminCard class="order-detail-card order-detail-card--products" title="Productos del Pedido" icon="fas fa-box" :flush="true">
          <div v-if="items.length === 0" class="detail-empty">Sin productos registrados en la orden.</div>
          <table v-else class="dashboard-table nested-table">
            <thead>
              <tr>
                <th>Producto</th>
                <th>Cantidad</th>
                <th>Precio</th>
                <th>Subtotal</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="item in items" :key="item.id || `${item.product_id}-${item.variant_name || ''}`">
                <td>
                  <div class="order-item-product">
                    <AdminTableImage
                      :src="resolveOrderItemImage(item)"
                      :alt="item.product_name || item.name || 'Producto'"
                      :original-path="resolveOrderItemImagePath(item)"
                      fallback-type="product"
                      variant="square"
                    />
                    <div class="order-item-product__copy">
                      <strong>{{ item.product_name || item.name || 'Producto' }}</strong>
                      <span v-if="item.variant_name">{{ item.variant_name }}</span>
                    </div>
                  </div>
                </td>
                <td>{{ Number(item.quantity || 0) }}</td>
                <td>{{ formatCurrency(item.unit_price || item.price || 0) }}</td>
                <td>{{ formatCurrency(item.total || (Number(item.unit_price || item.price || 0) * Number(item.quantity || 0))) }}</td>
              </tr>
            </tbody>
            <tfoot>
              <tr>
                <td colspan="3" class="totals-label">Subtotal</td>
                <td>{{ formatCurrency(order.subtotal) }}</td>
              </tr>
              <tr>
                <td colspan="3" class="totals-label">Envío</td>
                <td>{{ formatCurrency(order.shipping_cost) }}</td>
              </tr>
              <tr>
                <td colspan="3" class="totals-label"><strong>Total</strong></td>
                <td><strong>{{ formatCurrency(order.total) }}</strong></td>
              </tr>
            </tfoot>
          </table>
        </AdminCard>
      </section>

      <section class="order-history-section">
        <AdminCard title="Historial de Cambios" icon="fas fa-history">
          <template #headerActions>
            <button v-if="showHistoryToggle" type="button" class="history-toggle-btn" :class="{ 'history-toggle-btn--expanded': expandedHistory }" @click="toggleHistoryExpansion">
              <i class="fas" :class="expandedHistory ? 'fa-chevron-up' : 'fa-chevron-down'"></i>
              <span>{{ expandedHistory ? 'Ver menos' : `Ver todos (${hiddenHistoryCount} más)` }}</span>
            </button>
          </template>

          <div v-if="history.length === 0" class="detail-empty">Sin movimientos registrados.</div>
          <div v-else class="history-timeline">
            <article v-for="entry in visibleHistory" :key="entry.id" class="history-timeline__item" :style="{ '--timeline-accent': getHistoryTypeColor(entry) }">
              <div class="history-timeline__point">
                <i class="fas" :class="getHistoryTypeIcon(entry)"></i>
              </div>

              <div class="history-timeline__card">
                <div class="history-timeline__header">
                  <div>
                    <h4>{{ entry.description || `${historyFieldLabel(entry.field_changed)} actualizado` }}</h4>
                    <p>{{ historyFieldLabel(entry.field_changed) }}</p>
                  </div>
                  <span class="history-timeline__date">
                    <i class="fas fa-clock"></i>
                    {{ formatTimelineDate(entry.created_at) }}
                  </span>
                </div>

                <div class="history-timeline__user">
                  <div class="history-timeline__user-main">
                    <i class="fas fa-user"></i>
                    <span>{{ getHistoryActorName(entry) }}</span>
                  </div>
                  <span class="history-role-badge" :class="`history-role-badge--${getHistoryActorRole(entry).variant}`">{{ getHistoryActorRole(entry).label }}</span>
                </div>

                <div v-if="entry.old_value || entry.new_value" class="history-timeline__values">
                  <div class="history-change-box history-change-box--old">
                    <span class="history-change-box__label">Anterior</span>
                    <span class="history-change-box__value">{{ translateHistoryValue(entry.old_value, entry.field_changed) }}</span>
                  </div>
                  <i class="fas fa-arrow-right history-timeline__arrow"></i>
                  <div class="history-change-box history-change-box--new">
                    <span class="history-change-box__label">Nuevo</span>
                    <span class="history-change-box__value">{{ translateHistoryValue(entry.new_value, entry.field_changed) }}</span>
                  </div>
                </div>

                <div v-if="entry.ip_address" class="history-timeline__meta">
                  <i class="fas fa-network-wired"></i>
                  <span>IP: {{ entry.ip_address }}</span>
                </div>
              </div>
            </article>
          </div>
        </AdminCard>
      </section>
    </template>

    <AdminCard v-else title="Orden no disponible" icon="fas fa-exclamation-circle">
      <p>No fue posible cargar la orden solicitada.</p>
    </AdminCard>

    <AdminModal :show="showEditModal" title="Editar orden" max-width="760px" @close="closeEditModal">
      <div class="admin-order-detail-page admin-order-detail-page--modal">
        <div class="form-grid-2">
        <div class="form-group">
          <label for="edit-customer-name">
            Cliente
            <AdminInfoTooltip text="Nombre del cliente asociado a esta orden." />
          </label>
          <input id="edit-customer-name" v-model="editForm.customer_name" type="text" class="form-control" :class="{ 'is-invalid': editErrors.customer_name }" @input="validateEditField('customer_name')">
          <p v-if="editErrors.customer_name" class="form-error">{{ editErrors.customer_name }}</p>
        </div>
        <div class="form-group">
          <label for="edit-customer-email">
            Email
            <AdminInfoTooltip text="Correo del cliente para notificaciones y contacto sobre la orden." />
          </label>
          <input id="edit-customer-email" v-model="editForm.customer_email" type="email" class="form-control" :class="{ 'is-invalid': editErrors.customer_email }" @input="validateEditField('customer_email')">
          <p v-if="editErrors.customer_email" class="form-error">{{ editErrors.customer_email }}</p>
        </div>
        <div class="form-group">
          <label for="edit-customer-phone">
            Teléfono
            <AdminInfoTooltip text="Número de contacto del cliente para coordinación del envío." />
          </label>
          <input id="edit-customer-phone" v-model="editForm.customer_phone" type="text" class="form-control" :class="{ 'is-invalid': editErrors.customer_phone }" @input="validateEditField('customer_phone')">
          <p v-if="editErrors.customer_phone" class="form-error">{{ editErrors.customer_phone }}</p>
        </div>
        <div class="form-group form-group--full">
          <div class="order-edit-address-panel">
            <div class="order-edit-address-panel__header">
              <div class="order-edit-address-panel__title">
                <h4>Dirección y detalles de entrega</h4>
                <p>{{ selectedShippingAddress?.alias || 'Dirección registrada manualmente en el pedido.' }}</p>
              </div>
              <div v-if="selectedShippingAddress" class="order-edit-address-panel__chips">
                <span class="addr-card__type-badge">{{ labelCheckoutAddressType(selectedShippingAddress.address_type) }}</span>
                <span v-if="selectedShippingAddress.is_default" class="addr-card__default-badge">
                  <i class="fas fa-star"></i> Principal
                </span>
              </div>
            </div>

            <div class="admin-detail-summary order-edit-address-panel__body">
              <div class="admin-detail-summary__row">
                <span>Destinatario</span>
                <strong>{{ shippingRecipientLabel }}</strong>
              </div>
              <div class="admin-detail-summary__row admin-detail-summary__row--stack">
                <span>Dirección</span>
                <strong>{{ shippingStreetLabel }}</strong>
              </div>
              <div v-if="shippingComplementLabel" class="admin-detail-summary__row admin-detail-summary__row--stack">
                <span>Complemento</span>
                <strong>{{ shippingComplementLabel }}</strong>
              </div>
              <div class="admin-detail-summary__row">
                <span>Barrio / zona</span>
                <strong>{{ shippingZoneLabel }}</strong>
              </div>
              <div v-if="shippingBuildingTypeLabel" class="admin-detail-summary__row">
                <span>Tipo de edificación</span>
                <strong>{{ shippingBuildingTypeLabel }}</strong>
              </div>
              <div v-if="shippingBuildingNameLabel" class="admin-detail-summary__row">
                <span>Edificio / conjunto</span>
                <strong>{{ shippingBuildingNameLabel }}</strong>
              </div>
              <div v-if="selectedShippingAddress?.apartment_number" class="admin-detail-summary__row">
                <span>Apto / oficina</span>
                <strong>{{ selectedShippingAddress.apartment_number }}</strong>
              </div>
              <div v-if="selectedShippingAddress?.delivery_instructions" class="admin-detail-summary__row admin-detail-summary__row--stack">
                <span>Indicaciones de entrega</span>
                <strong>{{ selectedShippingAddress.delivery_instructions }}</strong>
              </div>
            </div>
          </div>
        </div>
        <div class="form-group form-group--full">
          <label for="edit-shipping-address">
            Dirección del pedido
            <AdminInfoTooltip text="Dirección asociada a la orden. Se carga automáticamente con la dirección guardada cuando existe." />
          </label>
          <textarea id="edit-shipping-address" v-model="editForm.shipping_address" rows="3" class="form-control" :class="{ 'is-invalid': editErrors.shipping_address }" @input="validateEditField('shipping_address')"></textarea>
          <p v-if="editErrors.shipping_address" class="form-error">{{ editErrors.shipping_address }}</p>
        </div>
        <div class="form-group form-group--full">
          <label for="edit-notes">
            Notas
            <AdminInfoTooltip text="Comentarios internos o indicaciones especiales sobre la orden. No son visibles al cliente." />
          </label>
          <textarea id="edit-notes" v-model="editForm.notes" rows="3" class="form-control" :class="{ 'is-invalid': editErrors.notes }" @input="validateEditField('notes')"></textarea>
          <p v-if="editErrors.notes" class="form-error">{{ editErrors.notes }}</p>
        </div>
        </div>
      </div>
      <template #footer>
        <button class="btn btn-secondary" type="button" @click="closeEditModal">Cancelar</button>
        <button class="btn btn-primary" type="button" :class="{ 'is-loading': saving }" :disabled="saving" @click="submitEditOrder">
          <i :class="saving ? 'fas fa-spinner fa-spin' : 'fas fa-save'"></i>
          {{ saving ? 'Guardando...' : 'Guardar cambios' }}
        </button>
      </template>
    </AdminModal>

    <AdminModal :show="showStatusModal" title="Cambiar estado de la orden" max-width="560px" @close="closeStatusModal">
      <div class="admin-order-detail-page admin-order-detail-page--modal">
        <div class="form-grid-1">
        <div class="form-group">
          <label for="status-value">
            Estado *
            <AdminInfoTooltip text="Nuevo estado de la orden. Te ayuda a registrar en qué etapa va el pedido." />
          </label>
          <select id="status-value" v-model="statusForm.status" class="form-control" :class="{ 'is-invalid': statusErrors.status }" @change="validateStatusField('status')">
            <option v-for="option in ADMIN_EDITABLE_ORDER_STATUSES" :key="option.value" :value="option.value">{{ option.label }}</option>
          </select>
          <p v-if="statusErrors.status" class="form-error">{{ statusErrors.status }}</p>
        </div>
        <div class="form-group">
          <label for="status-description">
            Descripción del cambio
            <AdminInfoTooltip text="Razón interna del cambio de estado. Queda registrada en el historial de la orden. (opcional)" />
          </label>
          <textarea id="status-description" v-model="statusForm.description" rows="4" class="form-control" :class="{ 'is-invalid': statusErrors.description }" @input="validateStatusField('description')"></textarea>
          <p v-if="statusErrors.description" class="form-error">{{ statusErrors.description }}</p>
        </div>
        </div>
      </div>
      <template #footer>
        <button class="btn btn-secondary" type="button" :disabled="saving" @click="closeStatusModal">Cancelar</button>
        <button class="btn btn-primary" type="button" :class="{ 'is-loading': saving }" :disabled="saving" @click="submitStatusChange">
          <i :class="saving ? 'fas fa-spinner fa-spin' : 'fas fa-save'"></i>
          {{ saving ? 'Guardando...' : 'Guardar estado' }}
        </button>
      </template>
    </AdminModal>

    <AdminModal :show="showPaymentStatusModal" title="Cambiar estado de pago" max-width="560px" @close="closePaymentStatusModal">
      <div class="admin-order-detail-page admin-order-detail-page--modal">
        <div class="form-grid-1">
        <div class="form-group">
          <label for="payment-status-value">
            Estado de pago *
            <AdminInfoTooltip text="Estado actual del pago. Cambia a «Pagado» cuando el pago es confirmado, a «Verificado» una vez revisado el comprobante." />
          </label>
          <select id="payment-status-value" v-model="paymentForm.payment_status" class="form-control" :class="{ 'is-invalid': paymentErrors.payment_status }" @change="validatePaymentField('payment_status')">
            <option value="pending">Pendiente</option>
            <option value="paid">Pagado</option>
            <option value="verified">Verificado</option>
            <option value="failed">Fallido</option>
            <option value="refunded">Reembolsado</option>
          </select>
          <p v-if="paymentErrors.payment_status" class="form-error">{{ paymentErrors.payment_status }}</p>
        </div>
        <div class="form-group">
          <label for="payment-description">
            Descripción del cambio
            <AdminInfoTooltip text="Nota interna sobre el cambio de estado de pago. Queda registrada en el historial. (opcional)" />
          </label>
          <textarea id="payment-description" v-model="paymentForm.description" rows="4" class="form-control" :class="{ 'is-invalid': paymentErrors.description }" @input="validatePaymentField('description')"></textarea>
          <p v-if="paymentErrors.description" class="form-error">{{ paymentErrors.description }}</p>
        </div>
        </div>
      </div>
      <template #footer>
        <button class="btn btn-secondary" type="button" :disabled="saving" @click="closePaymentStatusModal">Cancelar</button>
        <button class="btn btn-primary" type="button" :class="{ 'is-loading': saving }" :disabled="saving" @click="submitPaymentStatusChange">
          <i :class="saving ? 'fas fa-spinner fa-spin' : 'fas fa-save'"></i>
          {{ saving ? 'Guardando...' : 'Guardar estado de pago' }}
        </button>
      </template>
    </AdminModal>

    <AdminPaymentProofModal :show="showProofModal" :payment="paymentRecord" :fallback-status="order?.payment_status" @close="closeProofModal" />
  </div>
</template>

<script setup>
import { RouterLink } from 'vue-router'
import { useAdminOrderDetail } from '../composables/useAdminOrderDetail'
import AdminCard from '../components/AdminCard.vue'
import AdminInfoTooltip from '../components/AdminInfoTooltip.vue'
import AdminModal from '../components/AdminModal.vue'
import AdminPaymentProofModal from '../components/AdminPaymentProofModal.vue'
import AdminPageHeader from '../components/AdminPageHeader.vue'
import AdminTableImage from '../components/AdminTableImage.vue'
import AdminTableShimmer from '../components/AdminTableShimmer.vue'
import AddressMapViewer from '../../../components/common/AddressMapViewer.vue'
import '../views/AdminOrderDetailPage.css'

// =====================================================
// Lógica principal del detalle de orden
// =====================================================
const {
  ADMIN_EDITABLE_ORDER_STATUSES,
  breadcrumbOrderLabel,
  closeEditModal,
  closePaymentStatusModal,
  closeProofModal,
  closeStatusModal,
  deliveryLocationLabel,
  editErrors,
  editForm,
  formatCurrency,
  formatDateTime,
  formatTimelineDate,
  getHistoryActorName,
  getHistoryActorRole,
  getHistoryTypeColor,
  getHistoryTypeIcon,
  handlePaymentProofError,
  headerTitle,
  hiddenHistoryCount,
  history,
  historyFieldLabel,
  items,
  labelCheckoutAddressType,
  loading,
  openEditModal,
  openPaymentStatusModal,
  openProofModal,
  openStatusModal,
  order,
  paymentBadgeClass,
  paymentErrors,
  paymentForm,
  paymentLabel,
  paymentMethodLabel,
  paymentProofIsImage,
  paymentProofUnavailable,
  paymentRecord,
  resolveOrderItemImage,
  resolveOrderItemImagePath,
  saving,
  selectedShippingAddress,
  shippingAddressLine,
  shippingAddressOriginLabel,
  shippingBuildingNameLabel,
  shippingBuildingTypeLabel,
  shippingComplementLabel,
  shippingMapCoords,
  shippingRecipientLabel,
  shippingStreetLabel,
  shippingZoneLabel,
  showEditModal,
  showHistoryToggle,
  showPaymentStatusModal,
  showProofModal,
  showStatusModal,
  statusBadgeClass,
  statusErrors,
  statusForm,
  statusLabel,
  submitEditOrder,
  submitPaymentStatusChange,
  submitStatusChange,
  toggleHistoryExpansion,
  translateHistoryValue,
  validateEditField,
  validatePaymentField,
  validateStatusField,
  visibleHistory,
} = useAdminOrderDetail()
</script>


