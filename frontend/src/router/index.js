import { createRouter, createWebHistory } from 'vue-router'
import LoginPage from '../modules/auth/pages/LoginPage.vue'
import ForgotPasswordPage from '../modules/auth/pages/ForgotPasswordPage.vue'
import RegisterPage from '../modules/auth/pages/RegisterPage.vue'
import CartPage from '../modules/cart/pages/CartPage.vue'
import ProductDetailPage from '../modules/catalog/pages/ProductDetailPage.vue'
import StorePage from '../modules/catalog/pages/StorePage.vue'
import AccountLayoutPage from '../modules/account/pages/AccountLayoutPage.vue'
import DashboardPage from '../modules/account/pages/DashboardPage.vue'
import OrdersPage from '../modules/account/pages/OrdersPage.vue'
import OrderDetailPage from '../modules/account/pages/OrderDetailPage.vue'
import NotificationsPage from '../modules/account/pages/NotificationsPage.vue'
import AddressesPage from '../modules/account/pages/AddressesPage.vue'
import WishlistPage from '../modules/account/pages/WishlistPage.vue'
import SettingsPage from '../modules/account/pages/SettingsPage.vue'
import ShippingPage from '../modules/checkout/pages/ShippingPage.vue'
import PaymentPage from '../modules/checkout/pages/PaymentPage.vue'
import ConfirmationPage from '../modules/checkout/pages/ConfirmationPage.vue'
import CollectionsPage from '../modules/home/pages/CollectionsPage.vue'
import HomePage from '../modules/home/pages/HomePage.vue'
import TermsAndConditionsPage from '../modules/legal/pages/TermsAndConditionsPage.vue'
import ErrorPage from '../modules/errors/pages/ErrorPage.vue'
import { authHttp } from '../services/http'
import { useSession } from '../composables/useSession'
import { isAdminRole } from '../utils/authNavigation'
import { navigateToErrorPage } from '../utils/errorPage'

// Administración
import AdminLayout from '../modules/admin/layouts/AdminLayout.vue'
import AdminDashboardPage from '../modules/admin/pages/AdminDashboardPage.vue'
import AdminProductsPage from '../modules/admin/pages/AdminProductsPage.vue'
import AdminProductFormPage from '../modules/admin/pages/AdminProductFormPage.vue'
import AdminCategoriesPage from '../modules/admin/pages/AdminCategoriesPage.vue'
import AdminCollectionsPage from '../modules/admin/pages/AdminCollectionsPage.vue'
import AdminSizesPage from '../modules/admin/pages/AdminSizesPage.vue'
import AdminInventoryPage from '../modules/admin/pages/AdminInventoryPage.vue'
import AdminOrdersPage from '../modules/admin/pages/AdminOrdersPage.vue'
import AdminOrderDetailPage from '../modules/admin/pages/AdminOrderDetailPage.vue'
import AdminCustomersPage from '../modules/admin/pages/AdminCustomersPage.vue'
import AdminReviewsPage from '../modules/admin/pages/AdminReviewsPage.vue'
import AdminQuestionsPage from '../modules/admin/pages/AdminQuestionsPage.vue'
import AdminPaymentsPage from '../modules/admin/pages/AdminPaymentsPage.vue'
import AdminRefundsPage from '../modules/admin/pages/AdminRefundsPage.vue'
import AdminInvoicesPage from '../modules/admin/pages/AdminInvoicesPage.vue'
import AdminShippingRulesPage from '../modules/admin/pages/AdminShippingRulesPage.vue'
import AdminShippingMethodsPage from '../modules/admin/pages/AdminShippingMethodsPage.vue'
import AdminBulkDiscountsPage from '../modules/admin/pages/AdminBulkDiscountsPage.vue'
import AdminDiscountCodesPage from '../modules/admin/pages/AdminDiscountCodesPage.vue'
import AdminDiscountSpecificCampaignPage from '../modules/admin/pages/AdminDiscountSpecificCampaignPage.vue'
import AdminAnnouncementsPage from '../modules/admin/pages/AdminAnnouncementsPage.vue'
import AdminReportsPage from '../modules/admin/pages/AdminReportsPage.vue'
import AdminSlidersPage from '../modules/admin/pages/AdminSlidersPage.vue'
import AdminSettingsPage from '../modules/admin/pages/AdminSettingsPage.vue'
import AdminAdministratorsPage from '../modules/admin/pages/AdminAdministratorsPage.vue'
import AdminCouriersPage from '../modules/admin/pages/AdminCouriersPage.vue'
import AdminDeliveriesPage from '../modules/admin/pages/AdminDeliveriesPage.vue'
import AdminForgotPasswordPage from '../modules/admin/pages/AdminForgotPasswordPage.vue'

const router = createRouter({
  history: createWebHistory(),
  routes: [
    { path: '/', name: 'home', component: HomePage },
    { path: '/tienda', name: 'store', component: StorePage },
    { path: '/producto/:slug', name: 'product', component: ProductDetailPage, props: true },
    { path: '/carrito', name: 'cart', component: CartPage },
    { path: '/checkout/envio', name: 'shipping', component: ShippingPage, meta: { requiresCheckoutAuth: true } },
    { path: '/checkout/pago', name: 'payment', component: PaymentPage, meta: { requiresCheckoutAuth: true } },
    { path: '/checkout/confirmacion', name: 'confirmation', component: ConfirmationPage, meta: { requiresCheckoutAuth: true } },
    { path: '/terminos-y-condiciones', name: 'terms-and-conditions', component: TermsAndConditionsPage },
    {
      path: '/error/:status(400|401|403|408|429|500|502|503|504)',
      name: 'error-status',
      component: ErrorPage,
      props: true,
      meta: { layout: 'error' },
    },
    { path: '/login', name: 'login', component: LoginPage, meta: { layout: 'auth' } },
    { path: '/recuperar', name: 'forgot-password', component: ForgotPasswordPage, meta: { layout: 'auth' } },
    { path: '/admin/recuperar', name: 'admin-forgot-password', component: AdminForgotPasswordPage, meta: { layout: 'auth' } },
    { path: '/registro', name: 'register', component: RegisterPage, meta: { layout: 'auth' } },
    {
      path: '/mi-cuenta',
      component: AccountLayoutPage,
      children: [
        { path: '', redirect: { name: 'account-dashboard' } },
        {
          path: 'resumen',
          name: 'account-dashboard',
          component: DashboardPage,
          meta: { accountSection: 'dashboard' },
        },
        {
          path: 'pedidos',
          name: 'account-orders',
          component: OrdersPage,
          meta: { accountSection: 'orders' },
        },
        {
          path: 'pedidos/:id',
          name: 'account-order-detail',
          component: OrderDetailPage,
          props: true,
          meta: { accountSection: 'orders' },
        },
        {
          path: 'notificaciones',
          name: 'account-notifications',
          component: NotificationsPage,
          meta: { accountSection: 'notifications' },
        },
        {
          path: 'direcciones',
          name: 'account-addresses',
          component: AddressesPage,
          meta: { accountSection: 'addresses' },
        },
        {
          path: 'favoritos',
          name: 'account-wishlist',
          component: WishlistPage,
          meta: { accountSection: 'wishlist' },
        },
        {
          path: 'configuracion',
          name: 'account-settings',
          component: SettingsPage,
          meta: { accountSection: 'settings' },
        },
      ],
    },
    // Panel administrativo
    {
      path: '/admin',
      component: AdminLayout,
      meta: { layout: 'admin', requiresAdmin: true },
      children: [
        { path: '', name: 'admin-dashboard', component: AdminDashboardPage },
        // Productos
        { path: 'productos', name: 'admin-products', component: AdminProductsPage },
        { path: 'productos/nuevo', name: 'admin-product-create', component: AdminProductFormPage },
        { path: 'productos/:id/editar', name: 'admin-product-edit', component: AdminProductFormPage, props: true },
        { path: 'categorias', name: 'admin-categories', component: AdminCategoriesPage },
        { path: 'colecciones', name: 'admin-collections', component: AdminCollectionsPage },
        { path: 'tallas', name: 'admin-sizes', component: AdminSizesPage },
        { path: 'inventario', name: 'admin-inventory', component: AdminInventoryPage },
        // Órdenes
        { path: 'ordenes', name: 'admin-orders', component: AdminOrdersPage },
        { path: 'ordenes/:id', name: 'admin-order-detail', component: AdminOrderDetailPage, props: true },
        // Clientes
        { path: 'clientes', name: 'admin-customers', component: AdminCustomersPage },
        // Reseñas
        { path: 'resenas', name: 'admin-reviews', component: AdminReviewsPage },
        { path: 'preguntas', name: 'admin-questions', component: AdminQuestionsPage },
        // Pagos
        { path: 'pagos', name: 'admin-payments', component: AdminPaymentsPage },
        { path: 'reembolsos', name: 'admin-refunds', component: AdminRefundsPage },
        { path: 'facturas', name: 'admin-invoices', component: AdminInvoicesPage },
        // Envíos
        { path: 'envios/reglas', name: 'admin-shipping-rules', component: AdminShippingRulesPage },
        { path: 'envios/metodos', name: 'admin-shipping-methods', component: AdminShippingMethodsPage },
        { path: 'repartidores', name: 'admin-couriers', component: AdminCouriersPage },
        { path: 'repartidores/envios', name: 'admin-deliveries', component: AdminDeliveriesPage },
        // Descuentos
        { path: 'descuentos/cantidad', name: 'admin-bulk-discounts', component: AdminBulkDiscountsPage },
        { path: 'descuentos/codigos', name: 'admin-discount-codes', component: AdminDiscountCodesPage },
        { path: 'descuentos/codigos/usuarios-especificos', name: 'admin-discount-codes-specific-campaign', component: AdminDiscountSpecificCampaignPage },
        // Anuncios
        { path: 'anuncios', name: 'admin-announcements', component: AdminAnnouncementsPage },
        // Informes
        { path: 'informes', name: 'admin-reports', component: AdminReportsPage },
        { path: 'informes/ventas', name: 'admin-reports-sales', component: AdminReportsPage },
        { path: 'informes/productos', name: 'admin-reports-products', component: AdminReportsPage },
        { path: 'informes/clientes', name: 'admin-reports-customers', component: AdminReportsPage },
        // Configuración
        { path: 'sliders', name: 'admin-sliders', component: AdminSlidersPage },
        { path: 'configuracion', name: 'admin-settings', component: AdminSettingsPage },
        { path: 'configuracion/general', name: 'admin-settings-general', component: AdminSettingsPage },
        // Administradores
        { path: 'administradores', name: 'admin-administrators', component: AdminAdministratorsPage },
      ],
    },
    { path: '/dashboard', redirect: { name: 'account-dashboard' } },
    { path: '/mis-pedidos', redirect: { name: 'account-orders' } },
    { path: '/notificaciones', redirect: { name: 'account-notifications' } },
    { path: '/mis-direcciones', redirect: { name: 'account-addresses' } },
    { path: '/mis-favoritos', redirect: { name: 'account-wishlist' } },
    { path: '/configuracion-cuenta', redirect: { name: 'account-settings' } },
    { path: '/favoritos', redirect: { name: 'account-wishlist' } },
    { path: '/colecciones', name: 'collections', component: CollectionsPage },
    { path: '/:pathMatch(.*)*', name: 'not-found', component: ErrorPage, meta: { layout: 'error' } },
  ],
  scrollBehavior() {
    return { top: 0 }
  },
})

const { saveSession, clearSession } = useSession()
let sessionSyncToken = ''
let sessionSyncAt = 0
let sessionSyncPromise = null

function readSessionUser() {
  try {
    return JSON.parse(localStorage.getItem('angelow_user') || '{}')
  } catch {
    return {}
  }
}

function isAdminSession(userData) {
  return isAdminRole(userData)
}

async function synchronizeSession(token) {
  if (!token) {
    return true
  }

  const isFresh = sessionSyncToken === token && Date.now() - sessionSyncAt < 60_000
  if (isFresh) {
    return true
  }

  if (sessionSyncPromise) {
    return sessionSyncPromise
  }

  sessionSyncPromise = authHttp.get('/auth/me')
    .then((response) => {
      const authUser = response.data?.data
      if (authUser && typeof authUser === 'object') {
        saveSession(token, authUser)
      }
      sessionSyncToken = token
      sessionSyncAt = Date.now()
      return true
    })
    .catch((error) => {
      if (error?.response?.status === 401) {
        clearSession()
        sessionSyncToken = ''
        sessionSyncAt = 0
        return false
      }

      // Una caída temporal no debe expulsar una sesión todavía válida.
      return true
    })
    .finally(() => {
      sessionSyncPromise = null
    })

  return sessionSyncPromise
}

function hasSessionUser(userData) {
  // La sesión pública se considera válida solo si hay token y datos mínimos del usuario.
  return Boolean(userData && typeof userData === 'object' && (userData.id || String(userData.email || '').trim()))
}

router.beforeEach(async (to) => {
  const token = localStorage.getItem('angelow_token')
  const sessionIsValid = await synchronizeSession(token)
  if (!sessionIsValid) {
    return { name: 'login', query: { redirect: to.fullPath } }
  }

  const userData = readSessionUser()
  const isAuthenticated = Boolean(token && hasSessionUser(userData))
  const sessionIsAdmin = isAdminSession(userData)
  const routePath = String(to.path || '')

  // Si existe sesión admin y entra a la raíz pública, redirige directo al panel.
  if (routePath === '/' && isAuthenticated && sessionIsAdmin) {
    return { name: 'admin-dashboard' }
  }

  // Proteger rutas admin: requiere autenticación y rol admin.
  if (to.meta?.requiresAdmin || routePath.startsWith('/admin')) {
    if (!isAuthenticated) {
      return { name: 'login', query: { redirect: to.fullPath } }
    }

    if (!sessionIsAdmin) {
      return { name: 'account-dashboard' }
    }
  }

  // Proteger checkout: desde envío en adelante solo avanza una sesión de cliente.
  const requiresCheckoutAuth = Boolean(to.meta?.requiresCheckoutAuth) || routePath.startsWith('/checkout')
  if (requiresCheckoutAuth && !isAuthenticated) {
    return {
      name: 'login',
      query: { redirect: to.fullPath || '/checkout/envio' },
    }
  }

  // Evita que una sesión administrativa confirme compras desde el flujo cliente.
  if (requiresCheckoutAuth && sessionIsAdmin) {
    return { name: 'admin-dashboard' }
  }

  // Proteger rutas de cuenta de cliente
  const requiresAccount = routePath.startsWith('/mi-cuenta')
  if (requiresAccount && !isAuthenticated) {
    return {
      name: 'login',
      query: { redirect: to.fullPath || '/mi-cuenta/resumen' },
    }
  }

  // Evita mezclar sesiones admin con rutas de cuenta cliente.
  if (requiresAccount && sessionIsAdmin) {
    return { name: 'admin-dashboard' }
  }

  return true
})

// Una excepción al resolver una ruta o un componente no debe dejar la aplicación en blanco.
router.onError((error, to) => {
  console.error('[Angelow] Error al resolver la navegación:', error)
  void navigateToErrorPage(router, 500, { from: to?.fullPath }).catch(() => {})
})

export default router
