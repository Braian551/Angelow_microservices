import { createRouter, createWebHistory } from 'vue-router'
import HomePage from '../modules/home/pages/HomePage.vue'
import StorePage from '../modules/catalog/pages/StorePage.vue'
import ProductDetailPage from '../modules/catalog/pages/ProductDetailPage.vue'
import CartPage from '../modules/cart/pages/CartPage.vue'
import ShippingPage from '../modules/checkout/pages/ShippingPage.vue'
import PaymentPage from '../modules/checkout/pages/PaymentPage.vue'
import ConfirmationPage from '../modules/checkout/pages/ConfirmationPage.vue'
import LoginPage from '../modules/auth/pages/LoginPage.vue'
import RegisterPage from '../modules/auth/pages/RegisterPage.vue'
import OrdersPage from '../modules/account/pages/OrdersPage.vue'

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
    { path: '/login', name: 'login', component: LoginPage },
    { path: '/registro', name: 'register', component: RegisterPage },
    { path: '/mi-cuenta/pedidos', name: 'orders', component: OrdersPage },
  ],
  scrollBehavior() {
    return { top: 0 }
  },
})

export default router
