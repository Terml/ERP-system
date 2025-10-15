<template>
  <div class="statistics-page">
    <div class="page-header">
      <h1>Статистика системы</h1>
    </div>

    <div v-if="loading" class="loading">
      Загрузка статистики...
    </div>

    <div v-if="error" class="error">
      {{ error }}
    </div>

    <div v-if="!loading && !error" class="statistics-content">
      <div class="stats-section">
        <h2>Продукты по типам</h2>
        <div class="type-stats">
          <div v-for="(count, type) in productTypeStats" :key="type" class="type-item">
            <div class="type-info">
              <span class="type-label">{{ getProductTypeText(type) }}</span>
              <span class="type-count">{{ count }}</span>
            </div>
            <div class="type-bar">
              <div 
                class="type-fill" 
                :style="{ width: getProductTypePercentage(count) + '%' }"
                :class="'type-' + type"
              ></div>
            </div>
            <div class="type-percentage">{{ getProductTypePercentage(count) }}%</div>
          </div>
        </div>
      </div>
      <div class="stats-section">
        <h2>Заказы по статусам</h2>
        <div class="status-stats">
          <div v-for="(data, status) in orderStatusStats" :key="status" class="status-item">
            <div class="status-info">
              <span class="status-label">{{ getStatusText(status) }}</span>
              <span class="status-count">{{ data.count }}</span>
            </div>
            <div class="status-bar">
              <div 
                class="status-fill" 
                :style="{ width: data.percentage + '%' }"
                :class="'status-' + status"
              ></div>
            </div>
            <div class="status-percentage">{{ data.percentage }}%</div>
          </div>
        </div>
      </div>
      <div class="stats-section">
        <h2>Заказы по месяцам (последние 12 месяцев)</h2>
        <div class="monthly-stats">
          <div v-for="(count, month) in monthlyStats" :key="month" class="month-item">
            <div class="month-label">{{ formatMonth(month) }}</div>
            <div class="month-bar">
              <div 
                class="month-fill" 
                :style="{ width: getMonthPercentage(count) + '%' }"
              ></div>
            </div>
            <div class="month-count">{{ count }}</div>
          </div>
        </div>
      </div>
      <div class="stats-section">
        <h2>Топ компаний по количеству заказов</h2>
        <div class="company-stats">
          <div v-for="(count, company) in companyStats" :key="company" class="company-item">
            <div class="company-name">{{ company }}</div>
            <div class="company-bar">
              <div 
                class="company-fill" 
                :style="{ width: getCompanyPercentage(count) + '%' }"
              ></div>
            </div>
            <div class="company-count">{{ count }}</div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, onMounted } from 'vue'
import axios from 'axios'

export default {
  name: 'Statistics',
  setup() {
     const loading = ref(true)
     const error = ref(null)
     const orderStatusStats = ref({})
     const monthlyStats = ref({})
     const productTypeStats = ref({})
     const companyStats = ref({})

    const loadStatistics = async () => {
      try {
        loading.value = true
        error.value = null

         const [
           productsStats
         ] = await Promise.all([
           axios.get('/products/statistics').catch(() => ({ data: { total_products: 0 } }))
         ])

         let orderStatusData = { data: {} }
         let monthlyData = { data: {} }
         let companyData = { data: {} }

         try {
           orderStatusData = await axios.get('/orders/statistics/by-status')
         } catch (e) {
         }

         try {
           monthlyData = await axios.get('/orders/statistics/by-month')
         } catch (e) {
         }

         try {
           companyData = await axios.get('/orders/statistics/by-company')
         } catch (e) {
         }

         orderStatusStats.value = orderStatusData.data.data || orderStatusData.data || {}
         monthlyStats.value = monthlyData.data.data || monthlyData.data || {}
         const companyArray = companyData.data.data || companyData.data || []
         companyStats.value = Array.isArray(companyArray) ? companyArray.reduce((acc, company) => {
           acc[company.company_name] = company.orders_count
           return acc
         }, {}) : {}
         productTypeStats.value = productsStats.data.products_by_type || {}
      } catch (err) {
        error.value = 'Ошибка загрузки статистики: ' + (err.response?.data?.message || err.message)
      } finally {
        loading.value = false
      }
    }

    const getStatusText = (status) => {
      const statusMap = {
        'wait': 'Ожидание ',
        'in_process': 'В процессе ',
        'completed': 'Завершен ',
        'rejected': 'Отклонен '
      }
      return statusMap[status] || status
    }

    const formatMonth = (month) => {
      const [year, monthNum] = month.split('-')
      const date = new Date(year, monthNum - 1)
      return date.toLocaleDateString('ru-RU', { year: 'numeric', month: 'long' })
    }

    const getMonthPercentage = (count) => {
      const maxCount = Math.max(...Object.values(monthlyStats.value))
      return maxCount > 0 ? (count / maxCount) * 100 : 0
    }

    const getCompanyPercentage = (count) => {
      const maxCount = Math.max(...Object.values(companyStats.value))
      return maxCount > 0 ? (count / maxCount) * 100 : 0
    }

     const getProductTypeText = (type) => {
       const typeMap = {
         'material': 'Материалы ',
         'product': 'Продукты '
       }
       return typeMap[type] || type
     }

     const getProductTypePercentage = (count) => {
       const totalProducts = Object.values(productTypeStats.value).reduce((sum, c) => sum + c, 0)
       return totalProducts > 0 ? Math.round((count / totalProducts) * 100) : 0
     }

    onMounted(() => {
      loadStatistics()
    })

     return {
       loading,
       error,
       orderStatusStats,
       monthlyStats,
       productTypeStats,
       companyStats,
       getStatusText,
       formatMonth,
       getMonthPercentage,
       getCompanyPercentage,
       getProductTypeText,
       getProductTypePercentage
     }
  }
}
</script>

