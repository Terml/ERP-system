<template>
  <div class="dashboard-container">
    <div class="welcome-section">
      <h2 class="welcome-title">Добро пожаловать!</h2>
      <p class="welcome-subtitle">Система управления производством</p>
    </div>

    <div class="quick-actions">
      <h3>Быстрые действия</h3>
      <div class="actions-grid">
        <a v-if="canCreateOrder" href="#" class="action-btn" @click.prevent="createOrder">
          <span class="action-text">Создать заказ</span>
        </a>
        <a v-if="canCreateTask" href="#" class="action-btn" @click.prevent="createTask">
          <span class="action-text">Создать задание</span>
        </a>
        <a href="#" class="action-btn" @click.prevent="generateReport">
          <span class="action-text">Отчеты</span>
        </a>
        <a href="#" class="action-btn" @click.prevent="viewDocuments">
          <span class="action-text">Документы</span>
        </a>
        <a href="#" class="action-btn" @click.prevent="viewStatistics">
          <span class="action-text">Статистика</span>
        </a>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, onMounted, computed } from 'vue'
import { useRouter } from 'vue-router'
import axios from 'axios'

export default {
  name: 'Dashboard',
  setup() {
    const router = useRouter()
    const user = ref(null)
    const loading = ref(true)
    const stats = ref([
      { title: 'Заказы', value: '-' },
      { title: 'Задания', value: '-' },
      { title: 'Продукты', value: '-' },
      { title: 'Компании', value: '-' }
    ])

    const loadUserData = async () => {
      try {
        const response = await axios.get('/user')
        user.value = response.data
      } catch (error) {
      }
    }

    const loadStats = async () => {
      try {
        loading.value = true

        const [ordersStats, productsStats, companiesStats] = await Promise.all([
          axios.get('/orders/statistics').catch(() => ({ data: { total_orders: 0 } })),
          axios.get('/products/statistics').catch(() => ({ data: { total_products: 0 } })),
          axios.get('/companies/statistics').catch(() => ({ data: { total_companies: 0 } }))
        ])
        
        stats.value[0].value = ordersStats.data.total_orders || 0
        stats.value[2].value = productsStats.data.total_products || 0
        stats.value[3].value = companiesStats.data.total_companies || 0

        stats.value[1].value = 0
        
      } catch (error) {
      } finally {
        loading.value = false
      }
    }

    const createOrder = () => {
      router.push('/orders')
      setTimeout(() => {
        window.dispatchEvent(new CustomEvent('openCreateOrderModal'))
      }, 100)
    }
    const createTask = () => {
      router.push('/production-tasks')
      setTimeout(() => {
        window.dispatchEvent(new CustomEvent('openCreateTaskModal'))
      }, 100)
    }

    const generateReport = () => {
      window.open('/documents/task', '_blank')
    }

    const viewDocuments = () => {
      window.open('/documents/order', '_blank')
    }

    const viewStatistics = () => {
      router.push('/statistics')
    }

    const openSettings = () => {
      router.push('/admin')
    }

    const canCreateOrder = computed(() => {
      if (!user.value?.roles) return false
      return user.value.roles.some(role => ['admin', 'manager'].includes(role.role))
    })

    const canCreateTask = computed(() => {
      if (!user.value?.roles) return false
      return user.value.roles.some(role => ['admin', 'dispatcher'].includes(role.role))
    })

    onMounted(async () => {
      await Promise.all([
        loadUserData(),
        loadStats()
      ])
    })

    return {
      user,
      stats,
      loading,
      createOrder,
      createTask,
      generateReport,
      viewDocuments,
      viewStatistics,
      openSettings,
      canCreateOrder,
      canCreateTask
    }
  }
}
</script>