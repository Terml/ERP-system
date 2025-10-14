import { createApp } from 'vue'
import { createRouter, createWebHistory } from 'vue-router'
import App from './App.vue'
import axios from 'axios'

axios.defaults.baseURL = '/api'
axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest'
axios.defaults.withCredentials = true

const token = document.head.querySelector('meta[name="csrf-token"]')
if (token) {
    axios.defaults.headers.common['X-CSRF-TOKEN'] = token.content
}
import Dashboard from './components/Dashboard.vue'
import Orders from './components/Orders.vue'
import Products from './components/Products.vue'
import ProductionTasks from './components/ProductionTasks.vue'
import AdminPanel from './components/AdminPanel.vue'

const routes = [
    { path: '/', component: Dashboard, name: 'dashboard' },
    { path: '/orders', component: Orders, name: 'orders' },
    { path: '/products', component: Products, name: 'products' },
    { path: '/production-tasks', component: ProductionTasks, name: 'production-tasks' },
    { path: '/admin', component: AdminPanel, name: 'admin' },
]

const router = createRouter({
    history: createWebHistory(),
    routes,
})

const app = createApp(App)
app.use(router)
app.mount('#app')