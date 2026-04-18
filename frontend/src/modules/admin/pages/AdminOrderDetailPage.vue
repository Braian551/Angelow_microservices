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
            </div>
        </AdminCard>

        <AdminCard class="order-detail-card order-detail-card--proof" title="Comprobante de Pago" icon="fas fa-file-invoice-dollar">
            <template v-if="paymentRecord?.proof_url && paymentRecord.proof_exists !== false && !paymentProofUnavailable">
              <div class="payment-proof-card">
                <!-- Botón de preview → abre modal con lupa -->
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
      <template #footer>
        <button class="btn btn-secondary" type="button" @click="closeEditModal">Cancelar</button>
        <button class="btn btn-primary" type="button" :disabled="saving" @click="submitEditOrder">Guardar cambios</button>
      </template>
    </AdminModal>

    <AdminModal :show="showStatusModal" title="Cambiar estado de la orden" max-width="560px" @close="closeStatusModal">
      <div class="form-grid-1">
        <div class="form-group">
          <label for="status-value">
            Estado *
            <AdminInfoTooltip text="Nuevo estado de la orden. Te ayuda a registrar en qué etapa va el pedido." />
          </label>
          <select id="status-value" v-model="statusForm.status" class="form-control" :class="{ 'is-invalid': statusErrors.status }" @change="validateStatusField('status')">
            <option value="pending">Pendiente</option>
            <option value="processing">En proceso</option>
            <option value="shipped">Enviado</option>
            <option value="delivered">Entregado</option>
            <option value="cancelled">Cancelado</option>
            <option value="refunded">Reembolsado</option>
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
      <template #footer>
        <button class="btn btn-secondary" type="button" @click="closeStatusModal">Cancelar</button>
        <button class="btn btn-primary" type="button" :disabled="saving" @click="submitStatusChange">Guardar estado</button>
      </template>
    </AdminModal>

    <AdminModal :show="showPaymentStatusModal" title="Cambiar estado de pago" max-width="560px" @close="closePaymentStatusModal">
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
      <template #footer>
        <button class="btn btn-secondary" type="button" @click="closePaymentStatusModal">Cancelar</button>
        <button class="btn btn-primary" type="button" :disabled="saving" @click="submitPaymentStatusChange">Guardar estado de pago</button>
      </template>
    </AdminModal>

    <AdminPaymentProofModal :show="showProofModal" :payment="paymentRecord" :fallback-status="order?.payment_status" @close="closeProofModal" />
  </div>
</template>

<script setup>
import { computed, onMounted, reactive, ref } from 'vue'
import { RouterLink, useRoute } from 'vue-router'
import { catalogHttp, orderHttp, paymentHttp, shippingHttp } from '../../../services/http'
import { resolveUploadUrl } from '../../../utils/media'
import { useAlertSystem } from '../../../composables/useAlertSystem'
import { useSnackbarSystem } from '../../../composables/useSnackbarSystem'
import {
  buildCheckoutAddressLine,
  labelCheckoutAddressType,
  normalizeCheckoutAddress,
} from '../../checkout/utils/checkoutHelpers'
import {
  getHistoryFieldLabel,
  getOrderStatusBadgeClass,
  getOrderStatusLabel,
  getPaymentMethodLabel,
  getPaymentStatusBadgeClass,
  getPaymentStatusLabel,
  translateHistoryValue,
} from '../utils/orderPresentation'
import AdminCard from '../components/AdminCard.vue'
import AdminInfoTooltip from '../components/AdminInfoTooltip.vue'
import AdminModal from '../components/AdminModal.vue'
import AdminPaymentProofModal from '../components/AdminPaymentProofModal.vue'
import AdminPageHeader from '../components/AdminPageHeader.vue'
import AdminTableImage from '../components/AdminTableImage.vue'
import AdminTableShimmer from '../components/AdminTableShimmer.vue'

const route = useRoute()
const { showAlert } = useAlertSystem()
const { showSnackbar } = useSnackbarSystem()

const loading = ref(true)
const saving = ref(false)
const order = ref(null)
const items = ref([])
const productImageById = ref({})
const history = ref([])
const paymentRecord = ref(null)
const shippingAddresses = ref([])
const paymentProofUnavailable = ref(false)
const showProofModal = ref(false)
const expandedHistory = ref(false)

const showEditModal = ref(false)
const showStatusModal = ref(false)
const showPaymentStatusModal = ref(false)

const statusForm = reactive({
  status: 'pending',
  description: '',
})

const statusErrors = reactive({
  status: '',
  description: '',
})

const paymentForm = reactive({
  payment_status: 'pending',
  description: '',
})

const paymentErrors = reactive({
  payment_status: '',
  description: '',
})

const editForm = reactive({
  customer_name: '',
  customer_email: '',
  customer_phone: '',
  shipping_address: '',
  notes: '',
})

const editErrors = reactive({
  customer_name: '',
  customer_email: '',
  customer_phone: '',
  shipping_address: '',
  notes: '',
})

const orderId = computed(() => Number(route.params.id))
const orderSource = computed(() => String(route.query.vista || '').toLowerCase() === 'archivo' ? 'legacy' : 'microservice')

const headerTitle = computed(() => {
  if (order.value?.order_number) {
    return `Detalle de Orden ${order.value.order_number}`
  }
  return `Detalle de Orden #${orderId.value}`
})

const breadcrumbOrderLabel = computed(() => {
  if (order.value?.order_number) {
    return order.value.order_number
  }
  return `#${orderId.value}`
})

const addressSourceLabel = computed(() => {
  if (!order.value) return 'N/A'
  if (Number(order.value.gps_used || 0) === 1) return 'GPS usado'
  if (order.value.gps_latitude && order.value.gps_longitude) return 'Con coordenadas'
  return 'Manual'
})

const selectedShippingAddress = computed(() => findBestShippingAddressMatch(shippingAddresses.value, order.value))

const deliveryLocationLabel = computed(() => {
  const address = selectedShippingAddress.value
  if (address) {
    const zone = normalizeText(address.neighborhood)
    if (zone) return zone

    const city = normalizeText(address.city)
    if (city) return city
  }

  const fallbackCity = normalizeText(order.value?.shipping_city)
  return fallbackCity || 'Por definir'
})

const shippingAddressOriginLabel = computed(() => {
  if (selectedShippingAddress.value) return 'Dirección guardada del cliente'
  return addressSourceLabel.value
})

const shippingRecipientLabel = computed(() => {
  const address = selectedShippingAddress.value
  const recipientName = normalizeText(address?.recipient_name || order.value?.customer_name)
  const recipientPhone = normalizeText(address?.recipient_phone || order.value?.customer_phone)

  if (recipientName && recipientPhone) return `${recipientName} (${recipientPhone})`
  return recipientName || recipientPhone || 'Sin destinatario'
})

const shippingAddressLine = computed(() => {
  const fromProfile = selectedShippingAddress.value ? normalizeText(buildCheckoutAddressLine(selectedShippingAddress.value)) : ''
  if (fromProfile) return fromProfile

  const fallbackAddress = normalizeText(order.value?.shipping_address)
  return fallbackAddress || 'No se ha proporcionado dirección de envío'
})

const shippingStreetLabel = computed(() => {
  const fromProfile = normalizeText(selectedShippingAddress.value?.address)
  if (fromProfile) return fromProfile

  const fallbackAddress = normalizeText(order.value?.shipping_address)
  return fallbackAddress || 'No se ha proporcionado dirección de envío'
})

const shippingComplementLabel = computed(() => normalizeText(selectedShippingAddress.value?.complement))

const shippingZoneLabel = computed(() => {
  const fromProfile = normalizeText(selectedShippingAddress.value?.neighborhood)
  if (fromProfile) return fromProfile

  const fallbackZone = normalizeText(order.value?.shipping_city)
  return fallbackZone || 'No disponible'
})

const shippingBuildingTypeLabel = computed(() => {
  if (!selectedShippingAddress.value) return ''

  return labelCheckoutAddressType(selectedShippingAddress.value.building_type || selectedShippingAddress.value.address_type)
})

const shippingBuildingNameLabel = computed(() => normalizeText(selectedShippingAddress.value?.building_name))

const paymentProofIsImage = computed(() => {
  return Boolean(paymentRecord.value?.proof_url && /\.(png|jpe?g|webp|gif|bmp|svg)(\?.*)?$/i.test(paymentRecord.value.proof_url))
})

const hiddenHistoryCount = computed(() => Math.max(history.value.length - 1, 0))
const showHistoryToggle = computed(() => hiddenHistoryCount.value > 0)
const visibleHistory = computed(() => expandedHistory.value ? history.value : history.value.slice(0, 1))

function resolvePaymentProofUrl(value) {
  // Evita romper toda la carga del comprobante si falla la normalizacion de URL.
  try {
    return resolveUploadUrl(value)
  } catch {
    const raw = String(value || '').trim()
    return raw ? (raw.startsWith('/') ? raw : `/${raw}`) : ''
  }
}

function normalizeProofPath(value) {
  const proofPath = String(value || '').trim()
  if (!proofPath) return ''

  const normalized = proofPath.replace(/\\/g, '/')

  if (/^https?:\/\//i.test(normalized)) return normalized
  if (normalized.startsWith('/uploads/') || normalized.startsWith('uploads/')) return normalized
  if (normalized.startsWith('/payment_proofs/') || normalized.startsWith('payment_proofs/')) {
    return normalized.replace(/^\/?payment_proofs\/?/, 'uploads/payment_proofs/')
  }

  // Compatibilidad con registros antiguos que guardaban solo el nombre del archivo.
  return `uploads/payment_proofs/${normalized.replace(/^\/+/, '')}`
}

function normalizePaymentRecord(rawPayment = {}) {
  const proofPath = normalizeProofPath(rawPayment.proof_url || rawPayment.payment_proof || '')
  const rawStatus = String(rawPayment.status || rawPayment.payment_status || '').toLowerCase().trim()

  return {
    ...rawPayment,
    status: rawStatus === 'approved' ? 'verified' : (rawStatus === 'rejected' ? 'failed' : (rawStatus || 'pending')),
    proof_url: resolvePaymentProofUrl(proofPath),
    proof_name: rawPayment.proof_name || (proofPath ? proofPath.split('/').pop() : ''),
    proof_exists: rawPayment.proof_exists !== false,
  }
}

function extractPaymentRows(payload) {
  if (Array.isArray(payload?.data)) return payload.data
  if (Array.isArray(payload?.data?.data)) return payload.data.data
  if (Array.isArray(payload)) return payload
  return []
}

function extractAddressRows(payload) {
  if (Array.isArray(payload?.data)) return payload.data
  if (Array.isArray(payload?.data?.data)) return payload.data.data
  if (Array.isArray(payload)) return payload
  return []
}

function normalizeText(value) {
  return String(value || '').trim()
}

function textMatches(leftValue, rightValue) {
  const left = normalizeText(leftValue).toLowerCase()
  const right = normalizeText(rightValue).toLowerCase()

  if (!left || !right) return false
  return left === right || left.includes(right) || right.includes(left)
}

function findBestShippingAddressMatch(rows = [], currentOrder = null) {
  if (!Array.isArray(rows) || rows.length === 0 || !currentOrder) return null

  const shippingAddressId = Number(currentOrder.shipping_address_id || currentOrder.billing_address_id || 0)
  if (shippingAddressId > 0) {
    const byId = rows.find((row) => Number(row?.id || 0) === shippingAddressId)
    if (byId) return byId
  }

  const orderAddress = normalizeText(currentOrder.shipping_address)
  const orderZone = normalizeText(currentOrder.shipping_city)

  if (orderAddress || orderZone) {
    // Fallback por contenido cuando el ID historico no esta disponible o no coincide.
    const byContent = rows.find((row) => {
      const addressLine = normalizeText(buildCheckoutAddressLine(row))
      const addressValue = normalizeText(row?.address)
      const zoneValue = normalizeText(row?.neighborhood)
      const cityValue = normalizeText(row?.city)

      const addressMatches = orderAddress
        ? textMatches(addressLine, orderAddress) || textMatches(addressValue, orderAddress)
        : true

      const zoneMatches = orderZone
        ? textMatches(zoneValue, orderZone) || textMatches(cityValue, orderZone)
        : true

      return addressMatches && zoneMatches
    })

    if (byContent) return byContent
  }

  return rows.find((row) => Boolean(row?.is_default)) || rows[0] || null
}

function pickPaymentForCurrentOrder(rows = []) {
  if (!Array.isArray(rows) || rows.length === 0) return null

  return rows.find((row) => Number(row?.order_id || 0) === orderId.value) || rows[0] || null
}

function handlePaymentProofError() {
  paymentProofUnavailable.value = true
}

function openProofModal() {
  showProofModal.value = true
}

function closeProofModal() {
  showProofModal.value = false
}

function toggleHistoryExpansion() {
  expandedHistory.value = !expandedHistory.value
}

function normalizeOrder(rawOrder = {}) {
  return {
    ...rawOrder,
    id: Number(rawOrder.id || orderId.value || 0),
    user_id: rawOrder.user_id ? String(rawOrder.user_id) : '',
    order_source: rawOrder.order_source || orderSource.value,
    order_number: rawOrder.order_number || `#${rawOrder.id || orderId.value}`,
    created_at: rawOrder.created_at || null,
    status: rawOrder.status || rawOrder.order_status || 'pending',
    payment_status: rawOrder.payment_status || 'pending',
    payment_method: rawOrder.payment_method || '',
    customer_name: rawOrder.user_name || rawOrder.customer_name || rawOrder.billing_name || 'Cliente no registrado',
    customer_email: rawOrder.user_email || rawOrder.customer_email || rawOrder.billing_email || '',
    customer_phone: rawOrder.user_phone || rawOrder.customer_phone || rawOrder.billing_phone || '',
    subtotal: Number(rawOrder.subtotal || 0),
    shipping_cost: Number(rawOrder.shipping_cost || rawOrder.shipping || 0),
    discount_amount: Number(rawOrder.discount_amount || 0),
    total: Number(rawOrder.total || 0),
    shipping_address: rawOrder.shipping_address || rawOrder.address_current || rawOrder.billing_address || '',
    shipping_address_id: Number(rawOrder.shipping_address_id || 0) || null,
    shipping_city: rawOrder.shipping_city || rawOrder.billing_city || '',
    billing_address_id: Number(rawOrder.billing_address_id || 0) || null,
    notes: rawOrder.notes || '',
    gps_used: rawOrder.gps_used || 0,
    gps_latitude: rawOrder.gps_latitude || null,
    gps_longitude: rawOrder.gps_longitude || null,
  }
}

function resolveOrderItemImagePath(item = {}) {
  const directPath = normalizeText(
    item.product_image
      || item.image
      || item.image_path
      || item.variant_image
      || item.thumbnail,
  )

  if (directPath) return directPath

  const productId = Number(item.product_id || 0)
  if (productId > 0) {
    return normalizeText(productImageById.value[productId])
  }

  return ''
}

function resolveOrderItemImage(item = {}) {
  const imagePath = resolveOrderItemImagePath(item)
  return imagePath ? resolveUploadUrl(imagePath) : ''
}

function pickCatalogImage(payload = {}) {
  const images = Array.isArray(payload.images) ? payload.images : []
  const variantImages = Array.isArray(payload.variant_images) ? payload.variant_images : []
  const product = payload.product || {}

  return [
    ...images.map((row) => row?.image_path || row?.url || row?.image || ''),
    ...variantImages.map((row) => row?.image_path || row?.url || row?.image || ''),
    product?.main_image_path,
    product?.image,
    product?.imagen,
    product?.image_url,
  ].map((value) => normalizeText(value)).find(Boolean) || ''
}

async function loadProductImages(rows = []) {
  const productIds = [...new Set((Array.isArray(rows) ? rows : [])
    .map((row) => Number(row?.product_id || 0))
    .filter((id) => id > 0))]

  if (productIds.length === 0) {
    productImageById.value = {}
    return
  }

  const entries = await Promise.all(productIds.map(async (productId) => {
    try {
      const response = await catalogHttp.get(`/admin/products/${productId}`)
      const payload = response?.data?.data || {}
      return [productId, pickCatalogImage(payload)]
    } catch {
      return [productId, '']
    }
  }))

  const nextMap = {}
  entries.forEach(([productId, imagePath]) => {
    if (imagePath) {
      nextMap[productId] = imagePath
    }
  })

  productImageById.value = nextMap
}

async function loadShippingAddresses() {
  shippingAddresses.value = []

  const userId = normalizeText(order.value?.user_id)
  const userEmail = normalizeText(order.value?.customer_email)
  if (!userId && !userEmail) return

  try {
    const response = await shippingHttp.get('/shipping/addresses', {
      params: {
        user_id: userId || undefined,
        user_email: userEmail || undefined,
      },
    })

    shippingAddresses.value = extractAddressRows(response.data)
      .map((row) => normalizeCheckoutAddress(row))
      .filter((row) => Number(row?.id || 0) > 0)
  } catch {
    shippingAddresses.value = []
  }
}

function hydrateEditForm() {
  if (!order.value) return
  editForm.customer_name = order.value.customer_name || ''
  editForm.customer_email = order.value.customer_email || ''
  editForm.customer_phone = order.value.customer_phone || ''
  editForm.shipping_address = shippingAddressLine.value || order.value.shipping_address || ''
  editForm.notes = order.value.notes || ''
}

function resetStatusForm() {
  statusForm.status = order.value?.status || 'pending'
  statusForm.description = ''
  statusErrors.status = ''
  statusErrors.description = ''
}

function resetPaymentForm() {
  paymentForm.payment_status = order.value?.payment_status || 'pending'
  paymentForm.description = ''
  paymentErrors.payment_status = ''
  paymentErrors.description = ''
}

function resetEditErrors() {
  editErrors.customer_name = ''
  editErrors.customer_email = ''
  editErrors.customer_phone = ''
  editErrors.shipping_address = ''
  editErrors.notes = ''
}

async function loadOrder() {
  loading.value = true
  try {
    const res = await orderHttp.get(`/orders/${orderId.value}`, { params: { source: orderSource.value } })
    const payload = res.data || {}
    const rawOrder = payload.order || payload.data?.order || payload.data || {}
    const rawItems = payload.items || payload.data?.items || []
    const rawHistory = payload.history || payload.data?.history || []

    order.value = normalizeOrder(rawOrder)
    items.value = Array.isArray(rawItems) ? rawItems : []
    loadProductImages(items.value)
    history.value = Array.isArray(rawHistory) ? rawHistory : []
    expandedHistory.value = false
    await loadShippingAddresses()
    await loadPaymentRecord()
    hydrateEditForm()
    resetStatusForm()
    resetPaymentForm()
  } catch {
    showSnackbar({ type: 'error', message: 'Error cargando el detalle de la orden.' })
    order.value = null
    items.value = []
    productImageById.value = {}
    history.value = []
    shippingAddresses.value = []
    paymentRecord.value = null
  } finally {
    loading.value = false
  }
}

async function loadPaymentRecord() {
  paymentProofUnavailable.value = false

  try {
    const response = await paymentHttp.get('/admin/payments', { params: { order_id: orderId.value, per_page: 1 } })
    let rows = extractPaymentRows(response.data)

    // Fallback de migracion: si admin/payments no devuelve filas, intenta endpoint publico.
    if (rows.length === 0) {
      const fallbackResponse = await paymentHttp.get('/payments', { params: { order_id: orderId.value } })
      rows = extractPaymentRows(fallbackResponse.data)
        .filter((row) => Number(row?.order_id || 0) === orderId.value)
    }

    const selectedPayment = pickPaymentForCurrentOrder(rows)
    paymentRecord.value = selectedPayment ? normalizePaymentRecord(selectedPayment) : null
  } catch {
    try {
      const fallbackResponse = await paymentHttp.get('/payments', { params: { order_id: orderId.value } })
      const rows = extractPaymentRows(fallbackResponse.data)
        .filter((row) => Number(row?.order_id || 0) === orderId.value)

      const selectedPayment = pickPaymentForCurrentOrder(rows)
      paymentRecord.value = selectedPayment ? normalizePaymentRecord(selectedPayment) : null
    } catch {
      paymentRecord.value = null
    }
  }
}

function openEditModal() {
  hydrateEditForm()
  resetEditErrors()
  showEditModal.value = true
}

function closeEditModal() {
  showEditModal.value = false
}

function validateEditField(field) {
  const value = String(editForm[field] || '').trim()

  if (field === 'customer_name') {
    editErrors.customer_name = value.length >= 3 ? '' : 'El nombre debe tener al menos 3 caracteres.'
  }

  if (field === 'customer_email') {
    if (!value) {
      editErrors.customer_email = ''
      return
    }
    editErrors.customer_email = /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value)
      ? ''
      : 'Ingresa un email valido.'
  }

  if (field === 'customer_phone') {
    if (!value) {
      editErrors.customer_phone = ''
      return
    }
    editErrors.customer_phone = value.length >= 7 ? '' : 'El teléfono debe tener al menos 7 caracteres.'
  }

  if (field === 'shipping_address') {
    editErrors.shipping_address = value.length >= 5 ? '' : 'La dirección debe tener al menos 5 caracteres.'
  }

  if (field === 'notes') {
    if (!value) {
      editErrors.notes = ''
      return
    }
    editErrors.notes = value.length >= 4 ? '' : 'La nota debe tener al menos 4 caracteres.'
  }
}

async function submitEditOrder() {
  validateEditField('customer_name')
  validateEditField('customer_email')
  validateEditField('customer_phone')
  validateEditField('shipping_address')
  validateEditField('notes')

  const hasErrors = Object.values(editErrors).some(Boolean)
  if (hasErrors) return

  showAlert({
    type: 'warning',
    title: 'Confirmar cambios',
    message: 'Se actualizaran los datos principales de la orden.',
    actions: [
      { text: 'Cancelar', style: 'secondary' },
      {
        text: 'Guardar',
        style: 'primary',
        callback: async () => {
          saving.value = true
          try {
            await orderHttp.patch(`/orders/${orderId.value}`, {
              source: orderSource.value,
              customer_name: editForm.customer_name.trim(),
              customer_email: editForm.customer_email.trim(),
              customer_phone: editForm.customer_phone.trim(),
              shipping_address: editForm.shipping_address.trim(),
              notes: editForm.notes.trim(),
            })
            showSnackbar({ type: 'success', message: 'Orden actualizada correctamente.' })
            closeEditModal()
            await loadOrder()
          } catch {
            showSnackbar({ type: 'error', message: 'No fue posible actualizar la orden.' })
          } finally {
            saving.value = false
          }
        },
      },
    ],
  })
}

function openStatusModal() {
  resetStatusForm()
  showStatusModal.value = true
}

function closeStatusModal() {
  showStatusModal.value = false
}

function validateStatusField(field) {
  if (field === 'status') {
    statusErrors.status = statusForm.status ? '' : 'Selecciona un estado.'
  }
  if (field === 'description') {
    statusErrors.description = ''
  }
}

async function submitStatusChange() {
  validateStatusField('status')
  if (statusErrors.status) return

  saving.value = true
  try {
    await orderHttp.patch(`/orders/${orderId.value}/status`, {
      source: orderSource.value,
      status: statusForm.status,
      description: statusForm.description.trim(),
    })
    showSnackbar({ type: 'success', message: 'Estado de la orden actualizado.' })
    closeStatusModal()
    await loadOrder()
  } catch {
    showSnackbar({ type: 'error', message: 'No se pudo actualizar el estado de la orden.' })
  } finally {
    saving.value = false
  }
}

function openPaymentStatusModal() {
  resetPaymentForm()
  showPaymentStatusModal.value = true
}

function closePaymentStatusModal() {
  showPaymentStatusModal.value = false
}

function validatePaymentField(field) {
  if (field === 'payment_status') {
    paymentErrors.payment_status = paymentForm.payment_status ? '' : 'Selecciona un estado de pago.'
  }
  if (field === 'description') {
    paymentErrors.description = ''
  }
}

async function submitPaymentStatusChange() {
  validatePaymentField('payment_status')
  if (paymentErrors.payment_status) return

  saving.value = true
  try {
    await orderHttp.patch(`/orders/${orderId.value}/payment-status`, {
      source: orderSource.value,
      payment_status: paymentForm.payment_status,
      description: paymentForm.description.trim(),
    })
    showSnackbar({ type: 'success', message: 'Estado de pago actualizado.' })
    closePaymentStatusModal()
    await loadOrder()
  } catch {
    showSnackbar({ type: 'error', message: 'No se pudo actualizar el estado de pago.' })
  } finally {
    saving.value = false
  }
}

function formatCurrency(value) {
  return `$ ${Number(value || 0).toLocaleString('es-CO')}`
}

function formatDateTime(value) {
  if (!value) return 'Sin fecha'
  const date = new Date(value)
  return Number.isNaN(date.getTime()) ? 'Sin fecha' : date.toLocaleString('es-CO')
}

function formatTimelineDate(value) {
  if (!value) return 'Sin fecha'
  const date = new Date(value)
  return Number.isNaN(date.getTime())
    ? 'Sin fecha'
    : date.toLocaleString('es-CO', {
      year: 'numeric',
      month: '2-digit',
      day: '2-digit',
      hour: '2-digit',
      minute: '2-digit',
    })
}

function statusLabel(status) {
  return getOrderStatusLabel(status)
}

function paymentLabel(status) {
  return getPaymentStatusLabel(status)
}

function paymentMethodLabel(method) {
  return getPaymentMethodLabel(method)
}

function statusBadgeClass(status) {
  return getOrderStatusBadgeClass(status)
}

function paymentBadgeClass(status) {
  return getPaymentStatusBadgeClass(status)
}

function historyFieldLabel(field) {
  return getHistoryFieldLabel(field)
}

function normalizeHistoryToken(value) {
  return String(value || '')
    .trim()
    .toLowerCase()
    .replace(/\s+/g, '_')
    .replace(/-/g, '_')
}

function getHistoryType(entry) {
  const field = normalizeHistoryToken(entry?.field_changed)

  if (field === 'status' || field === 'order_status') return 'status'
  if (field === 'payment_status') return 'payment_status'
  if (field.includes('shipping') || field.includes('address') || field.includes('city')) return 'shipping'
  if (field.includes('note')) return 'notes'
  if (field.includes('item') || field.includes('product')) return 'items'
  return 'other'
}

function getHistoryTypeColor(entry) {
  return {
    status: '#0077b6',
    payment_status: '#10b981',
    shipping: '#f59e0b',
    notes: '#6366f1',
    items: '#ec4899',
    other: '#64748b',
  }[getHistoryType(entry)] || '#64748b'
}

function getHistoryTypeIcon(entry) {
  return {
    status: 'fa-rotate',
    payment_status: 'fa-credit-card',
    shipping: 'fa-truck',
    notes: 'fa-note-sticky',
    items: 'fa-box-open',
    other: 'fa-pen'
  }[getHistoryType(entry)] || 'fa-pen'
}

function getHistoryActorName(entry) {
  return entry?.changed_by_name || entry?.changed_by || 'Sistema'
}

function getHistoryActorRole(entry) {
  const role = normalizeHistoryToken(entry?.changed_by_role || '')
  const actorName = normalizeHistoryToken(getHistoryActorName(entry))

  if (role === 'admin' || role === 'administrator') return { label: 'Administrador', variant: 'admin' }
  if (role === 'customer' || role === 'cliente') return { label: 'Cliente', variant: 'customer' }
  if (role === 'delivery' || role === 'repartidor') return { label: 'Repartidor', variant: 'delivery' }
  if (actorName && actorName !== 'sistema' && actorName !== 'system') return { label: 'Administrador', variant: 'admin' }
  return { label: 'Sistema', variant: 'system' }
}

onMounted(loadOrder)
</script>

<style scoped>
.summary-actions {
  display: flex;
  flex-wrap: wrap;
  gap: 0.8rem;
}

.order-toolbar-btn {
  display: inline-flex;
  align-items: center;
  gap: 0.72rem;
  min-height: 4.2rem;
  padding: 0.78rem 1.2rem;
  border-radius: 1.3rem;
  border: 1px solid transparent;
  background: rgba(255, 255, 255, 0.9);
  color: var(--admin-text-heading);
  font-weight: 700;
  cursor: pointer;
  box-shadow: 0 12px 24px rgba(15, 55, 96, 0.08);
  transition: transform 0.2s ease, box-shadow 0.2s ease, border-color 0.2s ease;
}

.order-toolbar-btn:hover {
  transform: translateY(-1px);
  box-shadow: 0 16px 28px rgba(15, 55, 96, 0.1);
}

.order-toolbar-btn__icon {
  width: 3rem;
  height: 3rem;
  border-radius: 1rem;
  display: inline-flex;
  align-items: center;
  justify-content: center;
}

.order-toolbar-btn--neutral {
  border-color: rgba(148, 184, 216, 0.24);
  color: var(--admin-primary);
}

.order-toolbar-btn--neutral .order-toolbar-btn__icon {
  background: rgba(0, 119, 182, 0.1);
}

.order-toolbar-btn--primary {
  background: rgba(86, 191, 116, 0.14);
  border-color: rgba(86, 191, 116, 0.22);
  color: #1f6e33;
}

.order-toolbar-btn--primary .order-toolbar-btn__icon {
  background: rgba(86, 191, 116, 0.18);
}

.order-toolbar-btn--accent {
  background: rgba(0, 119, 182, 0.12);
  border-color: rgba(0, 119, 182, 0.16);
  color: var(--admin-primary-dark);
}

.order-toolbar-btn--accent .order-toolbar-btn__icon {
  background: rgba(0, 119, 182, 0.18);
}

.order-spotlight {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 1rem;
  padding: 1.15rem 1.2rem;
  margin-bottom: 1.1rem;
  border: 1px solid rgba(148, 184, 216, 0.22);
  border-radius: 1.7rem;
  background: rgba(241, 248, 255, 0.9);
}

.order-spotlight__main {
  display: grid;
  gap: 0.35rem;
}

.order-spotlight__eyebrow {
  font-size: 1.05rem;
  text-transform: uppercase;
  letter-spacing: 0.08em;
  color: var(--admin-text-soft);
  font-weight: 800;
}

.order-spotlight__main h3 {
  margin: 0;
  font-size: 2.05rem;
  color: var(--admin-text-heading);
}

.order-spotlight__main p {
  margin: 0;
  color: var(--admin-text-soft);
  font-size: 1.22rem;
}

.order-spotlight__chips {
  display: flex;
  flex-wrap: wrap;
  justify-content: flex-end;
  gap: 0.65rem;
}

.order-highlight-grid {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 1rem;
  margin-bottom: 1.1rem;
}

.order-highlight-card {
  display: grid;
  gap: 0.42rem;
  padding: 1.2rem 1.25rem;
  border-radius: 1.55rem;
  border: 1px solid rgba(148, 184, 216, 0.22);
  background: rgba(247, 251, 255, 0.88);
  box-shadow: 0 12px 24px rgba(15, 55, 96, 0.06);
}

.order-highlight-card--strong {
  background: rgba(0, 119, 182, 0.08);
  border-color: rgba(0, 119, 182, 0.18);
}

.order-highlight-card span {
  font-size: 1.04rem;
  text-transform: uppercase;
  letter-spacing: 0.06em;
  color: var(--admin-text-soft);
  font-weight: 800;
}

.order-highlight-card strong {
  font-size: 1.9rem;
  color: var(--admin-text-heading);
}

.order-highlight-card small {
  color: var(--admin-text-light);
  font-size: 1.08rem;
}

.order-detail-loading-grid {
  display: grid;
  gap: 1.2rem;
}

.order-summary-panels {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 1rem;
  margin-top: 1.1rem;
  padding-top: 1rem;
  border-top: 1px solid rgba(148, 184, 216, 0.16);
}

.order-summary-panel {
  display: grid;
  gap: 0.8rem;
  padding: 1.2rem 1.25rem;
  border-radius: 1.55rem;
  border: 1px solid rgba(148, 184, 216, 0.22);
  background: rgba(247, 251, 255, 0.82);
  box-shadow: 0 10px 24px rgba(15, 55, 96, 0.05);
}

.order-summary-panel__header {
  display: grid;
  gap: 0.22rem;
}

.order-summary-panel__header span {
  font-size: 1.08rem;
  text-transform: uppercase;
  letter-spacing: 0.06em;
  font-weight: 800;
  color: var(--admin-text-soft);
}

.order-summary-panel__header small {
  color: var(--admin-text-light);
  font-size: 1.05rem;
}

.order-summary-panel__body {
  gap: 0;
}

.order-summary-panel__body .admin-detail-summary__row {
  padding: 0.78rem 0;
  border-bottom: 1px solid rgba(148, 184, 216, 0.14);
}

.order-summary-panel__body .admin-detail-summary__row:last-child {
  border-bottom: none;
  padding-bottom: 0;
}

.order-summary-panel__body .admin-detail-summary__row:first-child {
  padding-top: 0;
}

.order-summary-panel__body .admin-detail-summary__row > strong {
  max-width: 65%;
  word-break: break-word;
}

.order-summary-panel__row--total {
  align-items: center;
}

.order-summary-panel__row--total > span,
.order-summary-panel__row--total > strong {
  color: var(--admin-primary-dark);
  font-weight: 800;
}

.order-total-strip {
  margin-top: 1rem;
  display: flex;
  justify-content: space-between;
  align-items: center;
  border: 1px solid rgba(0, 119, 182, 0.22);
  background: rgba(247, 251, 255, 0.88);
  border-radius: 1.6rem;
  padding: 1.1rem 1.45rem;
  font-size: 1.8rem;
  color: var(--admin-primary);
}

.payment-proof-card {
  display: grid;
  gap: 1rem;
}

.payment-proof-card__preview {
  position: relative;
  display: block;
  width: 100%;
  border: 1px solid rgba(148, 184, 216, 0.24);
  border-radius: 1.6rem;
  background: rgba(247, 251, 255, 0.82);
  padding: 1rem;
  cursor: pointer;
  overflow: hidden;
  transition: transform 0.2s ease, box-shadow 0.2s ease, border-color 0.2s ease;
}

.payment-proof-card__preview:hover {
  transform: translateY(-1px);
  border-color: rgba(0, 119, 182, 0.22);
  box-shadow: 0 16px 28px rgba(15, 55, 96, 0.1);
}

.payment-proof-card__preview--file {
  min-height: 18rem;
  display: grid;
  place-items: center;
}

.payment-proof-card__overlay {
  position: absolute;
  inset: 0;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 0.55rem;
  border-radius: 1.5rem;
  background: rgba(15, 55, 96, 0.54);
  color: white;
  font-weight: 700;
  font-size: 1.18rem;
  opacity: 0;
  transition: opacity 0.22s ease;
  pointer-events: none;
}

.payment-proof-card__overlay i {
  font-size: 2.2rem;
}

.payment-proof-card__preview:hover .payment-proof-card__overlay {
  opacity: 1;
}

.payment-proof-card__image {
  width: 100%;
  max-height: 34rem;
  object-fit: contain;
  border-radius: 1.2rem;
}

.payment-proof-card__file {
  display: grid;
  gap: 0.7rem;
  justify-items: center;
  text-align: center;
  color: var(--admin-primary-dark);
}

.payment-proof-card__file i {
  font-size: 3rem;
}

.payment-proof-card__meta {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 0.9rem;
}

.payment-proof-card__meta-item {
  padding: 1rem 1.1rem;
  border-radius: 1.3rem;
  border: 1px solid rgba(148, 184, 216, 0.2);
  background: rgba(255, 255, 255, 0.84);
  display: grid;
  gap: 0.35rem;
}

.payment-proof-card__meta-item span {
  font-size: 1.05rem;
  color: var(--admin-text-light);
  text-transform: uppercase;
  letter-spacing: 0.04em;
  font-weight: 700;
}

.payment-proof-card__meta-item strong {
  font-size: 1.35rem;
  color: var(--admin-text-dark);
}

.payment-proof-card__missing {
  display: flex;
  align-items: flex-start;
  gap: 1rem;
}

.payment-proof-card__missing i {
  font-size: 2.2rem;
  color: var(--admin-primary);
}

.payment-proof-card__missing p {
  margin: 0.3rem 0 0;
  color: var(--admin-text-light);
}

.detail-empty--soft {
  border: 1px dashed rgba(148, 184, 216, 0.24);
  border-radius: 1.4rem;
  background: rgba(247, 251, 255, 0.62);
}

.order-detail-stack {
  margin-top: 1.2rem;
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  grid-template-areas:
    'address proof'
    'products products';
  gap: 1.2rem;
  align-items: start;
}

.order-detail-card {
  min-width: 0;
}

.order-detail-card--address {
  grid-area: address;
}

.order-detail-card--proof {
  grid-area: proof;
}

.order-detail-card--products {
  grid-area: products;
}

.order-detail-card--proof .payment-proof-card {
  display: grid;
  gap: 1rem;
}

.order-detail-card--proof .payment-proof-card__preview {
  min-height: 24rem;
  display: grid;
  place-items: center;
}

.order-detail-card--proof .payment-proof-card__image {
  max-height: 24rem;
}

.order-detail-card--products .nested-table tbody td {
  vertical-align: top;
}

.order-item-product {
  display: flex;
  align-items: center;
  gap: 0.9rem;
  min-width: 0;
}

.order-item-product__copy {
  min-width: 0;
  display: grid;
  gap: 0.28rem;
}

.order-item-product__copy strong {
  color: var(--admin-text-heading);
  font-size: 1.35rem;
}

.order-item-product__copy span {
  color: var(--admin-text-light);
  font-size: 1.12rem;
  line-height: 1.4;
}

.order-item-product :deep(.admin-table-media) {
  width: 4.4rem;
  height: 4.4rem;
  border-radius: 0.9rem;
  flex-shrink: 0;
}

.order-history-section {
  margin-top: 1.2rem;
}

.detail-empty {
  padding: 1.6rem;
  color: var(--admin-text-light);
}

.history-toggle-btn {
  display: inline-flex;
  align-items: center;
  gap: 0.55rem;
  min-height: 3.7rem;
  padding: 0.68rem 1rem;
  border: 1px solid rgba(0, 119, 182, 0.16);
  border-radius: 1.15rem;
  background: rgba(0, 119, 182, 0.08);
  color: var(--admin-primary-dark);
  font-weight: 700;
  cursor: pointer;
  transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.history-toggle-btn:hover {
  transform: translateY(-1px);
  box-shadow: 0 12px 22px rgba(15, 55, 96, 0.08);
}

.history-toggle-btn--expanded {
  background: rgba(0, 119, 182, 0.12);
}

.history-timeline {
  position: relative;
  display: grid;
  gap: 1.4rem;
  padding-left: 1.7rem;
}

.history-timeline::before {
  content: '';
  position: absolute;
  top: 0.3rem;
  bottom: 0.3rem;
  left: 0.95rem;
  width: 2px;
  background: rgba(0, 119, 182, 0.16);
}

.history-timeline__item {
  position: relative;
  display: grid;
  grid-template-columns: auto 1fr;
  gap: 1rem;
  align-items: flex-start;
}

.history-timeline__point {
  position: relative;
  z-index: 1;
  width: 4rem;
  height: 4rem;
  border-radius: 50%;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  color: white;
  background: var(--timeline-accent);
  box-shadow: 0 12px 22px color-mix(in srgb, var(--timeline-accent) 30%, transparent);
}

.history-timeline__card {
  display: grid;
  gap: 1rem;
  padding: 1.35rem 1.4rem;
  border-radius: 1.7rem;
  border: 1px solid rgba(148, 184, 216, 0.22);
  background: rgba(248, 251, 255, 0.96);
  box-shadow: 0 16px 34px rgba(15, 55, 96, 0.08);
}

.history-timeline__header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  gap: 1rem;
}

.history-timeline__header h4 {
  margin: 0;
  font-size: 1.7rem;
  color: var(--admin-text-heading);
}

.history-timeline__header p {
  margin: 0.25rem 0 0;
  color: var(--admin-text-soft);
  font-size: 1.08rem;
}

.history-timeline__date {
  display: inline-flex;
  align-items: center;
  gap: 0.45rem;
  padding: 0.72rem 0.95rem;
  border-radius: 999px;
  background: rgba(0, 119, 182, 0.08);
  color: var(--admin-primary-dark);
  font-size: 1.08rem;
  font-weight: 700;
  white-space: nowrap;
}

.history-timeline__user {
  display: flex;
  align-items: center;
  gap: 0.8rem;
  flex-wrap: wrap;
}

.history-timeline__user-main {
  display: inline-flex;
  align-items: center;
  gap: 0.55rem;
  color: var(--admin-text-heading);
  font-weight: 700;
}

.history-role-badge {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  min-height: 3rem;
  padding: 0.42rem 0.92rem;
  border-radius: 999px;
  font-size: 0.98rem;
  font-weight: 800;
  text-transform: uppercase;
  letter-spacing: 0.05em;
}

.history-role-badge--admin {
  background: rgba(255, 116, 116, 0.14);
  color: #db5252;
}

.history-role-badge--customer {
  background: rgba(0, 119, 182, 0.12);
  color: var(--admin-primary-dark);
}

.history-role-badge--delivery {
  background: rgba(16, 185, 129, 0.12);
  color: #12805b;
}

.history-role-badge--system {
  background: rgba(138, 160, 184, 0.14);
  color: #516173;
}

.history-timeline__values {
  display: grid;
  grid-template-columns: 1fr auto 1fr;
  align-items: center;
  gap: 0.9rem;
}

.history-change-box {
  display: grid;
  gap: 0.45rem;
  padding: 1rem 1.05rem;
  border-radius: 1.25rem;
  border: 1px solid rgba(148, 184, 216, 0.2);
  background: white;
}

.history-change-box--old {
  box-shadow: inset 0 0 0 1px rgba(239, 68, 68, 0.08);
}

.history-change-box--new {
  box-shadow: inset 0 0 0 1px rgba(16, 185, 129, 0.08);
}

.history-change-box__label {
  font-size: 1rem;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  font-weight: 800;
}

.history-change-box--old .history-change-box__label {
  color: #ef4444;
}

.history-change-box--new .history-change-box__label {
  color: #10b981;
}

.history-change-box__value {
  font-size: 1.38rem;
  font-weight: 700;
  color: var(--admin-text-heading);
}

.history-timeline__arrow {
  color: var(--admin-primary);
  font-size: 1.9rem;
}

.history-timeline__meta {
  display: inline-flex;
  align-items: center;
  gap: 0.55rem;
  padding: 0.82rem 0.95rem;
  border-radius: 1rem;
  background: rgba(0, 119, 182, 0.05);
  color: var(--admin-text-soft);
  font-size: 1.05rem;
}

/* =====================================================
   Tarjeta de dirección de envío con íconos
   ===================================================== */
.addr-card {
  display: grid;
  gap: 0;
}

.addr-card__header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 0.8rem;
  padding: 0.95rem 1.2rem;
  margin-bottom: 0.5rem;
  border-radius: 1.4rem;
  background: rgba(0, 119, 182, 0.07);
  border: 1px solid rgba(0, 119, 182, 0.14);
}

.addr-card__title-group {
  display: flex;
  align-items: center;
  gap: 0.7rem;
  flex-wrap: wrap;
}

.addr-card__alias {
  display: inline-flex;
  align-items: center;
  gap: 0.55rem;
  font-size: 1.4rem;
  font-weight: 800;
  color: var(--admin-primary-dark);
}

.addr-card__alias i {
  color: var(--admin-primary);
  font-size: 1.15rem;
}

.addr-card__type-badge {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  min-height: 2.8rem;
  padding: 0.34rem 0.82rem;
  border-radius: 999px;
  background: rgba(0, 119, 182, 0.1);
  color: var(--admin-primary-dark);
  font-size: 1rem;
  font-weight: 700;
}

.addr-card__default-badge {
  display: inline-flex;
  align-items: center;
  gap: 0.4rem;
  padding: 0.38rem 0.85rem;
  border-radius: 999px;
  background: rgba(251, 191, 36, 0.15);
  border: 1px solid rgba(251, 191, 36, 0.3);
  color: #92610a;
  font-size: 1.05rem;
  font-weight: 800;
}

.addr-card__default-badge i {
  color: #f59e0b;
  font-size: 1rem;
}

.addr-card__body {
  padding-top: 0.5rem;
}

.addr-card__body .admin-detail-summary__row {
  padding: 0.78rem 0;
  border-bottom: 1px solid rgba(148, 184, 216, 0.14);
}

.addr-card__body .admin-detail-summary__row:last-child {
  border-bottom: none;
  padding-bottom: 0;
}

.addr-card__body .admin-detail-summary__row:first-child {
  padding-top: 0;
}

.addr-card__body .admin-detail-summary__row > strong {
  max-width: 65%;
  word-break: break-word;
}

.addr-card__footer {
  display: flex;
  justify-content: flex-end;
  padding: 0.75rem 0 0;
}

.order-edit-address-panel {
  display: grid;
  gap: 0.9rem;
  padding: 1.05rem 1.15rem;
  border-radius: 1.45rem;
  border: 1px solid rgba(148, 184, 216, 0.22);
  background: rgba(247, 251, 255, 0.8);
}

.order-edit-address-panel__header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 1rem;
  padding-bottom: 0.25rem;
  border-bottom: 1px solid rgba(148, 184, 216, 0.14);
}

.order-edit-address-panel__title {
  display: grid;
  gap: 0.2rem;
}

.order-edit-address-panel__title h4 {
  margin: 0;
  font-size: 1.42rem;
  color: var(--admin-text-heading);
}

.order-edit-address-panel__title p {
  margin: 0;
  color: var(--admin-text-light);
  font-size: 1.06rem;
}

.order-edit-address-panel__chips {
  display: flex;
  flex-wrap: wrap;
  justify-content: flex-end;
  gap: 0.55rem;
}

.order-edit-address-panel__body {
  gap: 0;
}

.order-edit-address-panel__body .admin-detail-summary__row {
  padding: 0.75rem 0;
  border-bottom: 1px solid rgba(148, 184, 216, 0.14);
}

.order-edit-address-panel__body .admin-detail-summary__row:last-child {
  border-bottom: none;
  padding-bottom: 0;
}

.order-edit-address-panel__body .admin-detail-summary__row:first-child {
  padding-top: 0;
}

.order-edit-address-panel__body .admin-detail-summary__row > strong {
  max-width: 65%;
  word-break: break-word;
}

.addr-card__origin {
  display: inline-flex;
  align-items: center;
  gap: 0.45rem;
  padding: 0.42rem 0.9rem;
  border-radius: 999px;
  font-size: 1.05rem;
  font-weight: 700;
}

.addr-card__origin--saved {
  background: rgba(16, 185, 129, 0.12);
  color: #065f46;
  border: 1px solid rgba(16, 185, 129, 0.2);
}

.addr-card__origin--manual {
  background: rgba(100, 116, 139, 0.1);
  color: #475569;
  border: 1px solid rgba(100, 116, 139, 0.18);
}

.totals-label {
  text-align: right;
}

.form-grid-2 {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 1rem;
}

.form-grid-1 {
  display: grid;
  gap: 1rem;
}

.form-group--full {
  grid-column: 1 / -1;
}

.form-error {
  margin-top: 0.4rem;
  color: var(--admin-danger);
  font-size: 1.2rem;
}

@media (max-width: 1200px) {
  .order-highlight-grid {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }

  .order-summary-panels {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }
}

@media (max-width: 1080px) {
  .order-detail-stack {
    grid-template-columns: 1fr;
    grid-template-areas:
      'address'
      'proof'
      'products';
  }
}

@media (max-width: 768px) {
  .order-highlight-grid,
  .order-summary-panels {
    grid-template-columns: 1fr;
  }

  .order-summary-panel__body .admin-detail-summary__row {
    gap: 0.45rem;
  }

  .order-summary-panel__body .admin-detail-summary__row > strong {
    max-width: 100%;
  }

  .addr-card__body .admin-detail-summary__row > strong,
  .order-edit-address-panel__body .admin-detail-summary__row > strong {
    max-width: 100%;
  }

  .order-edit-address-panel__header {
    flex-direction: column;
  }

  .order-edit-address-panel__chips {
    justify-content: flex-start;
  }

  .history-timeline {
    padding-left: 0;
  }

  .history-timeline::before {
    display: none;
  }

  .history-timeline__item {
    grid-template-columns: 1fr;
  }

  .history-timeline__point {
    width: 3.6rem;
    height: 3.6rem;
  }

  .history-timeline__header,
  .history-timeline__values {
    grid-template-columns: 1fr;
    display: grid;
  }

  .history-timeline__arrow {
    justify-self: center;
    transform: rotate(90deg);
  }

  .order-spotlight {
    flex-direction: column;
    align-items: flex-start;
  }

  .order-spotlight__chips {
    justify-content: flex-start;
  }

  .summary-actions {
    width: 100%;
  }

  .summary-actions .order-toolbar-btn {
    width: 100%;
    justify-content: center;
  }

  .form-grid-2 {
    grid-template-columns: 1fr;
  }

  .payment-proof-card__meta {
    grid-template-columns: 1fr;
  }
}
</style>
