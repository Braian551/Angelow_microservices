<template>
  <div class="account-shimmer" :class="`account-shimmer--${variant}`" aria-busy="true" aria-label="Cargando contenido">
    <template v-if="variant === 'dashboard'">
      <section class="dashboard-header account-shimmer__surface account-shimmer__header">
        <span class="account-shimmer__line account-shimmer__line--title" />
        <span class="account-shimmer__line account-shimmer__line--subtitle" />
      </section>

      <section class="dashboard-summary account-shimmer__summary">
        <article
          v-for="card in summaryCards"
          :key="`dashboard-summary-${card}`"
          class="summary-card account-shimmer__summary-card"
        >
          <span class="account-shimmer__circle account-shimmer__circle--icon" />
          <div class="account-shimmer__stack">
            <span class="account-shimmer__line account-shimmer__line--heading" />
            <span class="account-shimmer__line account-shimmer__line--text" />
            <span class="account-shimmer__line account-shimmer__line--link" />
          </div>
        </article>
      </section>

      <section class="account-card account-shimmer__surface">
        <header class="section-header">
          <span class="account-shimmer__line account-shimmer__line--heading" />
          <span class="account-shimmer__line account-shimmer__line--link" />
        </header>

        <div class="account-shimmer__list">
          <article v-for="order in primaryRows" :key="`dashboard-order-${order}`" class="account-shimmer__order-card">
            <div class="account-shimmer__row account-shimmer__row--between">
              <div class="account-shimmer__stack">
                <span class="account-shimmer__line account-shimmer__line--heading" />
                <span class="account-shimmer__line account-shimmer__line--small" />
              </div>
              <span class="account-shimmer__pill account-shimmer__pill--status" />
            </div>

            <div class="account-shimmer__row account-shimmer__row--between">
              <span class="account-shimmer__line account-shimmer__line--small" />
              <span class="account-shimmer__line account-shimmer__line--price" />
            </div>

            <span class="account-shimmer__btn" />
          </article>
        </div>
      </section>

      <section class="account-card account-shimmer__surface">
        <header class="section-header">
          <span class="account-shimmer__line account-shimmer__line--heading" />
          <span class="account-shimmer__line account-shimmer__line--link" />
        </header>

        <div class="account-shimmer__product-grid">
          <article v-for="product in productCards" :key="`dashboard-product-${product}`" class="account-shimmer__product-card">
            <span class="account-shimmer__rect account-shimmer__rect--product" />
            <span class="account-shimmer__line account-shimmer__line--text" />
            <span class="account-shimmer__line account-shimmer__line--heading account-shimmer__line--wide" />
            <span class="account-shimmer__line account-shimmer__line--price" />
            <span class="account-shimmer__btn account-shimmer__btn--full" />
          </article>
        </div>
      </section>
    </template>

    <template v-else-if="variant === 'orders'">
      <section class="dashboard-header account-shimmer__surface account-shimmer__header">
        <span class="account-shimmer__line account-shimmer__line--title" />
        <span class="account-shimmer__line account-shimmer__line--subtitle" />
      </section>

      <section class="account-card account-shimmer__surface">
        <header class="section-header">
          <span class="account-shimmer__line account-shimmer__line--heading" />
        </header>

        <div class="account-shimmer__list">
          <article v-for="order in orderRows" :key="`orders-${order}`" class="account-shimmer__order-card account-shimmer__order-card--detailed">
            <div class="account-shimmer__row account-shimmer__row--between">
              <div class="account-shimmer__stack">
                <span class="account-shimmer__line account-shimmer__line--heading" />
                <span class="account-shimmer__line account-shimmer__line--small" />
              </div>
              <span class="account-shimmer__pill account-shimmer__pill--status" />
            </div>

            <div class="account-shimmer__metric-row">
              <span class="account-shimmer__line account-shimmer__line--small" />
              <span class="account-shimmer__line account-shimmer__line--price" />
              <span class="account-shimmer__line account-shimmer__line--small" />
            </div>

            <span class="account-shimmer__line account-shimmer__line--wide" />

            <div class="account-shimmer__action-row">
              <span class="account-shimmer__btn" />
              <span class="account-shimmer__btn account-shimmer__btn--ghost" />
            </div>

            <div class="account-shimmer__progress-row">
              <span v-for="step in progressSteps" :key="`orders-progress-${order}-${step}`" class="account-shimmer__progress-dot" />
            </div>
          </article>
        </div>
      </section>
    </template>

    <template v-else-if="variant === 'addresses'">
      <section class="dashboard-header account-shimmer__surface account-shimmer__header">
        <span class="account-shimmer__line account-shimmer__line--title" />
        <span class="account-shimmer__line account-shimmer__line--subtitle" />
      </section>

      <section class="account-card account-shimmer__surface">
        <header class="section-header account-shimmer__row account-shimmer__row--between">
          <span class="account-shimmer__line account-shimmer__line--heading" />
          <span class="account-shimmer__btn" />
        </header>

        <div class="account-shimmer__address-grid">
          <article v-for="address in primaryRows" :key="`address-${address}`" class="account-shimmer__address-card">
            <div class="account-shimmer__row account-shimmer__row--between">
              <div class="account-shimmer__row">
                <span class="account-shimmer__circle account-shimmer__circle--small" />
                <div class="account-shimmer__stack">
                  <span class="account-shimmer__line account-shimmer__line--heading" />
                  <span class="account-shimmer__line account-shimmer__line--small" />
                </div>
              </div>
              <span class="account-shimmer__pill account-shimmer__pill--small" />
            </div>

            <div class="account-shimmer__stack account-shimmer__stack--spaced">
              <span class="account-shimmer__line account-shimmer__line--wide" />
              <span class="account-shimmer__line account-shimmer__line--wide" />
              <span class="account-shimmer__line account-shimmer__line--text" />
              <span class="account-shimmer__line account-shimmer__line--text" />
            </div>

            <div class="account-shimmer__action-row">
              <span class="account-shimmer__btn" />
              <span class="account-shimmer__btn account-shimmer__btn--ghost" />
            </div>
          </article>
        </div>
      </section>
    </template>

    <template v-else-if="variant === 'wishlist'">
      <section class="dashboard-header account-shimmer__surface account-shimmer__header">
        <span class="account-shimmer__line account-shimmer__line--title" />
        <span class="account-shimmer__line account-shimmer__line--subtitle" />
      </section>

      <section class="account-card account-shimmer__surface">
        <div class="account-shimmer__row account-shimmer__row--between account-shimmer__row--wrap">
          <div class="account-shimmer__stack">
            <span class="account-shimmer__line account-shimmer__line--heading" />
            <span class="account-shimmer__line account-shimmer__line--text" />
          </div>
          <span class="account-shimmer__btn" />
        </div>

        <div class="account-shimmer__summary-card account-shimmer__summary-card--single">
          <span class="account-shimmer__circle account-shimmer__circle--icon" />
          <div class="account-shimmer__stack">
            <span class="account-shimmer__line account-shimmer__line--price" />
            <span class="account-shimmer__line account-shimmer__line--text" />
          </div>
        </div>
      </section>

      <section class="account-shimmer__product-grid">
        <article v-for="product in productCards" :key="`wishlist-product-${product}`" class="account-shimmer__product-card account-shimmer__surface">
          <span class="account-shimmer__rect account-shimmer__rect--product" />
          <span class="account-shimmer__line account-shimmer__line--text" />
          <span class="account-shimmer__line account-shimmer__line--wide" />
          <span class="account-shimmer__line account-shimmer__line--price" />
          <span class="account-shimmer__btn account-shimmer__btn--full" />
        </article>
      </section>
    </template>

    <template v-else-if="variant === 'notifications'">
      <section class="dashboard-header account-shimmer__surface account-shimmer__header">
        <span class="account-shimmer__line account-shimmer__line--title" />
        <span class="account-shimmer__line account-shimmer__line--subtitle" />
      </section>

      <section class="account-grid-2 notifications-summary-grid account-shimmer__summary">
        <article v-for="card in summaryCards" :key="`notifications-summary-${card}`" class="summary-card account-shimmer__summary-card">
          <span class="account-shimmer__circle account-shimmer__circle--icon" />
          <div class="account-shimmer__stack">
            <span class="account-shimmer__line account-shimmer__line--heading" />
            <span class="account-shimmer__line account-shimmer__line--text" />
          </div>
        </article>
      </section>

      <section class="account-card account-shimmer__surface">
        <header class="section-header account-shimmer__row account-shimmer__row--between">
          <div class="account-shimmer__stack">
            <span class="account-shimmer__line account-shimmer__line--heading" />
            <span class="account-shimmer__line account-shimmer__line--small" />
          </div>
          <span class="account-shimmer__line account-shimmer__line--filter" />
        </header>

        <div class="account-shimmer__toolbar">
          <span v-for="pill in summaryCards" :key="`notifications-pill-${pill}`" class="account-shimmer__pill account-shimmer__pill--small" />
        </div>

        <div class="account-shimmer__list">
          <article v-for="notification in orderRows" :key="`notification-${notification}`" class="account-shimmer__notification-row">
            <div class="account-shimmer__row">
              <span class="account-shimmer__circle account-shimmer__circle--small" />
              <div class="account-shimmer__stack account-shimmer__stack--grow">
                <span class="account-shimmer__line account-shimmer__line--heading" />
                <span class="account-shimmer__line account-shimmer__line--wide" />
              </div>
            </div>
            <div class="account-shimmer__action-row">
              <span class="account-shimmer__btn account-shimmer__btn--ghost" />
              <span class="account-shimmer__btn account-shimmer__btn--ghost" />
            </div>
          </article>
        </div>
      </section>
    </template>

    <template v-else-if="variant === 'order-detail'">
      <section class="dashboard-header account-shimmer__surface account-shimmer__header">
        <span class="account-shimmer__line account-shimmer__line--title" />
        <span class="account-shimmer__line account-shimmer__line--subtitle" />
      </section>

      <section class="account-card account-shimmer__surface">
        <div class="account-shimmer__stack">
          <span class="account-shimmer__line account-shimmer__line--title" />
          <span class="account-shimmer__line account-shimmer__line--small" />
        </div>

        <div class="account-shimmer__row account-shimmer__row--wrap">
          <span class="account-shimmer__pill account-shimmer__pill--status" />
          <span class="account-shimmer__pill account-shimmer__pill--status" />
          <span class="account-shimmer__btn account-shimmer__btn--ghost" />
        </div>
      </section>

      <section class="account-card account-shimmer__surface">
        <header class="section-header">
          <span class="account-shimmer__line account-shimmer__line--heading" />
        </header>

        <div class="account-shimmer__list">
          <article v-for="item in primaryRows" :key="`order-detail-item-${item}`" class="account-shimmer__detail-row">
            <span class="account-shimmer__rect account-shimmer__rect--thumb" />
            <div class="account-shimmer__stack account-shimmer__stack--grow">
              <span class="account-shimmer__line account-shimmer__line--heading" />
              <span class="account-shimmer__line account-shimmer__line--text" />
              <span class="account-shimmer__line account-shimmer__line--small" />
            </div>
            <span class="account-shimmer__line account-shimmer__line--price" />
          </article>
        </div>
      </section>

      <section class="account-card account-shimmer__surface">
        <header class="section-header">
          <span class="account-shimmer__line account-shimmer__line--heading" />
        </header>

        <div class="account-shimmer__progress-row account-shimmer__progress-row--wide">
          <span v-for="step in progressSteps" :key="`order-detail-progress-${step}`" class="account-shimmer__progress-dot" />
        </div>
      </section>
    </template>

    <template v-else-if="variant === 'settings'">
      <section class="dashboard-header account-shimmer__surface account-shimmer__header">
        <span class="account-shimmer__line account-shimmer__line--title" />
        <span class="account-shimmer__line account-shimmer__line--subtitle" />
      </section>

      <section class="account-card account-shimmer__surface account-shimmer__settings-shell">
        <div class="account-shimmer__settings-layout">
          <aside class="account-shimmer__settings-sidebar">
            <span v-for="tab in settingsTabs" :key="`settings-tab-${tab}`" class="account-shimmer__line account-shimmer__line--nav" />
          </aside>

          <div class="account-shimmer__settings-content">
            <div class="account-shimmer__row account-shimmer__row--wrap">
              <span class="account-shimmer__circle account-shimmer__circle--avatar" />
              <div class="account-shimmer__stack account-shimmer__stack--grow">
                <span class="account-shimmer__line account-shimmer__line--heading" />
                <span class="account-shimmer__line account-shimmer__line--text" />
                <span class="account-shimmer__line account-shimmer__line--link" />
              </div>
            </div>

            <div class="account-shimmer__settings-fields">
              <div v-for="field in settingsFields" :key="`settings-field-${field}`" class="account-shimmer__field-block">
                <span class="account-shimmer__line account-shimmer__line--small" />
                <span class="account-shimmer__rect account-shimmer__rect--input" />
              </div>
            </div>

            <span class="account-shimmer__btn" />
          </div>
        </div>
      </section>
    </template>
  </div>
</template>

<script setup>
defineProps({
  variant: {
    type: String,
    default: 'dashboard',
  },
})

const summaryCards = [1, 2, 3]
const primaryRows = [1, 2, 3]
const orderRows = [1, 2, 3, 4]
const productCards = [1, 2, 3, 4]
const progressSteps = [1, 2, 3, 4]
const settingsTabs = [1, 2, 3]
const settingsFields = [1, 2, 3, 4, 5]
</script>