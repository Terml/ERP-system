<template>
  <div id="app">
    <nav class="navbar">
      <div class="nav-brand">
        <h1>ERP System</h1>
      </div>
             <div class="nav-links">
               <router-link to="/" class="nav-link">Дашборд</router-link>
               <router-link v-if="canViewOrders()" to="/orders" class="nav-link">Заказы</router-link>
               <router-link v-if="canViewProducts()" to="/products" class="nav-link">Продукты</router-link>
               <router-link v-if="canViewTasks()" to="/production-tasks" class="nav-link">Задания</router-link>
               <router-link v-if="canViewAdmin()" to="/admin" class="nav-link">Админ-панель</router-link>
             </div>
      <div class="user-info">
        <div class="user-details">
          <p class="user-name">{{ user?.login }}</p>
        </div>
        <button @click="logout" class="logout-btn">Выйти</button>
      </div>
    </nav>
    
    <main class="main-content">
      <router-view />
    </main>
  </div>
</template>

<script>
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import axios from 'axios'

export default {
  name: 'App',
  components: {
  },
  setup() {
    const router = useRouter()
    const user = ref(null)

    const userInitials = computed(() => {
      if (user.value?.login) {
        return user.value.login.charAt(0).toUpperCase()
      }
      return 'U'
    })

    const loadUserData = () => {
      const appElement = document.getElementById('app')
      const userData = appElement?.getAttribute('data-user')
      
      if (userData) {
        try {
          const parsedUser = JSON.parse(userData)
          user.value = parsedUser
        } catch (error) {
          window.location.href = '/login'
        }
      } else {
        window.location.href = '/login'
      }
    }

    const logout = async () => {
      try {
        await axios.post('/logout')
        window.location.href = '/login'
      } catch (error) {
        window.location.href = '/login'
      }
    }
    const hasRole = (roleName) => {
      return user.value?.roles?.some(role => role.role === roleName)
    }

    const hasAnyRole = (roleNames) => {
      return user.value?.roles?.some(role => roleNames.includes(role.role))
    }
    const canViewOrders = () => {
      return hasAnyRole(['admin', 'manager', 'dispatcher'])
    }

    const canViewProducts = () => {
      return hasAnyRole(['admin', 'dispatcher', 'master'])
    }

    const canViewTasks = () => {
      return hasAnyRole(['admin', 'dispatcher', 'master', 'otk'])
    }

    const canViewAdmin = () => {
      return hasRole('admin')
    }

    onMounted(() => {
      loadUserData()
    })

    return {
      user,
      userInitials,
      logout,
      canViewOrders,
      canViewProducts,
      canViewTasks,
      canViewAdmin
    }
  }
}
</script>

