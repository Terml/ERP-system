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

<style>
.navbar {
  background: white;
  padding: 20px 0;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
  margin-bottom: 30px;
  display: flex;
  justify-content: space-between;
  align-items: center;
  max-width: 1200px;
  margin: 0 auto 30px;
  padding: 20px;
}

.nav-brand h1 {
  color: #333;
  margin: 0;
  font-size: 24px;
  font-weight: 600;
}

.nav-links {
  display: flex;
  gap: 20px;
}

.nav-link {
  color: #333;
  text-decoration: none;
  padding: 8px 16px;
  border-radius: 6px;
  transition: background-color 0.3s ease;
}

.nav-link:hover {
  background-color: #f8f9fa;
  color: #667eea;
}

.user-info {
  display: flex;
  align-items: center;
  gap: 15px;
}

.user-details {
  display: flex;
  align-items: center;
  gap: 10px;
}

.user-name {
  margin: 0;
  font-weight: 500;
  color: #333;
}

.logout-btn {
  background: #dc3545;
  color: white;
  border: none;
  padding: 8px 16px;
  border-radius: 6px;
  cursor: pointer;
  font-size: 14px;
  transition: background-color 0.3s ease;
}

.logout-btn:hover {
  background: #c82333;
}

.main-content {
  max-width: 1200px;
  margin: 0 auto;
  padding: 0 20px;
}
</style>
