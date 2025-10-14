<template>
  <div class="dashboard-container">
    <div class="welcome-section">
      <h2 class="welcome-title">Добро пожаловать!</h2>
      <p class="welcome-subtitle">Система управления производством</p>
    </div>

    <div class="quick-actions">
      <h3>Быстрые действия</h3>
      <div class="actions-grid">
        <a href="#" class="action-btn" @click.prevent="createOrder">
          <span class="action-text">Создать заказ</span>
        </a>
        <a href="#" class="action-btn" @click.prevent="createTask">
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
      router.push('/products')
    }

    const openSettings = () => {
      router.push('/admin')
    }

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
      openSettings
    }
  }
}
</script>

<style scoped>
.dashboard-container {
  min-height: 100vh;
  background: #f5f5f5;
}

.welcome-section {
  background: white;
  padding: 30px;
  border-radius: 12px;
  box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
  margin-bottom: 30px;
}

.welcome-title {
  color: #333;
  margin: 0 0 10px 0;
  font-size: 28px;
  font-weight: 600;
}

.welcome-subtitle {
  color: #666;
  margin: 0 0 20px 0;
  font-size: 16px;
}

.stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: 20px;
  margin-bottom: 30px;
}

.stat-card {
  background: white;
  padding: 25px;
  border-radius: 12px;
  box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
  text-align: center;
}

.stat-title {
  color: #333;
  margin: 0 0 5px 0;
  font-size: 18px;
  font-weight: 600;
}

.stat-value {
  color: #667eea;
  margin: 0;
  font-size: 32px;
  font-weight: bold;
}

.quick-actions {
  background: white;
  padding: 30px;
  border-radius: 12px;
  box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
}

.quick-actions h3 {
  color: #333;
  margin: 0 0 20px 0;
  font-size: 20px;
  font-weight: 600;
}

.actions-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 15px;
}

.action-btn {
  background: #f8f9fa;
  border: 2px solid #e1e5e9;
  padding: 15px 20px;
  border-radius: 8px;
  text-decoration: none;
  color: #333;
  text-align: center;
  transition: all 0.3s ease;
  display: block;
  cursor: pointer;
}

.action-btn:hover {
  background: #e9ecef;
  border-color: #667eea;
  color: #667eea;
}

.action-text {
  font-weight: 500;
  font-size: 14px;
}

@media (max-width: 768px) {
  .stats-grid {
    grid-template-columns: 1fr;
  }

  .actions-grid {
    grid-template-columns: 1fr;
  }
}
</style>
