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

// Admin
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
import AdminForgotPasswordPage from '../modules/admin/pages/AdminForgotPasswordPage.vue'

const router = createRouter({
  history: createWebHistory(),
  routes: [
    { path: '/', name: 'home', component: HomePage },
    { path: '/tienda', name: 'store', component: StorePage },
    { path: '/producto/:slug', name: 'product', component: ProductDetailPage, props: true },
    { path: '/carrito', name: 'cart', component: CartPage },
    { path: '/checkout/envio', name: 'shipping', component: ShippingPage },
    { path: '/checkout/pago', name: 'payment', component: PaymentPage },
    { path: '/checkout/confirmacion', name: 'confirmation', component: ConfirmationPage },
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
    // Admin panel
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
        // Ordenes
        { path: 'ordenes', name: 'admin-orders', component: AdminOrdersPage },
        { path: 'ordenes/:id', name: 'admin-order-detail', component: AdminOrderDetailPage, props: true },
        // Clientes
        { path: 'clientes', name: 'admin-customers', component: AdminCustomersPage },
        // Resenas
        { path: 'resenas', name: 'admin-reviews', component: AdminReviewsPage },
        { path: 'preguntas', name: 'admin-questions', component: AdminQuestionsPage },
        // Pagos
        { path: 'pagos', name: 'admin-payments', component: AdminPaymentsPage },
        { path: 'facturas', name: 'admin-invoices', component: AdminInvoicesPage },
        // Envios
        { path: 'envios/reglas', name: 'admin-shipping-rules', component: AdminShippingRulesPage },
        { path: 'envios/metodos', name: 'admin-shipping-methods', component: AdminShippingMethodsPage },
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
        // Configuracion
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
  ],
  scrollBehavior() {
    return { top: 0 }
  },
})

const ADMIN_ROLES = new Set(['admin', 'super_admin', 'superadmin', 'administrator'])

function readSessionUser() {
  try {
    return JSON.parse(localStorage.getItem('angelow_user') || '{}')
  } catch {
    return {}
  }
}

function normalizeRole(value) {
  return String(value || '')
    .trim()
    .toLowerCase()
    .replace(/\s+/g, '_')
}

function resolveUserRole(userData) {
  const directRole = normalizeRole(userData?.role || userData?.rol || userData?.user_role || userData?.tipo_usuario)
  if (directRole) {
    return directRole
  }

  const firstRole = Array.isArray(userData?.roles) ? normalizeRole(userData.roles[0]) : ''
  return firstRole
}

function isAdminSession(userData) {
  return ADMIN_ROLES.has(resolveUserRole(userData))
}

router.beforeEach((to) => {
  const token = localStorage.getItem('angelow_token')
  const isAuthenticated = Boolean(token)
  const userData = readSessionUser()
  const sessionIsAdmin = isAdminSession(userData)
  const routePath = String(to.path || '')

  // Si existe sesion admin y entra a la raiz publica, redirige directo al panel.
  if (routePath === '/' && isAuthenticated && sessionIsAdmin) {
    return { name: 'admin-dashboard' }
  }

  // Proteger rutas admin: requiere autenticacion + rol admin
  if (to.meta?.requiresAdmin || routePath.startsWith('/admin')) {
    if (!isAuthenticated) {
      return { name: 'login', query: { redirect: to.fullPath } }
    }

    if (!sessionIsAdmin) {
      return { name: 'account-dashboard' }
    }
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

export default router
