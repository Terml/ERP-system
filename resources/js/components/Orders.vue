<template>
  <div class="orders-page">
    <div class="page-header">
      <button @click="showCreateForm = true" class="btn btn-primary">
        Создать заказ
      </button>
    </div>

    <div class="filters">
      <select v-model="filters.status" @change="fetchOrders" class="form-select">
        <option value="">Все статусы</option>
        <option value="wait">Ожидание</option>
        <option value="in_process">В процессе</option>
        <option value="completed">Завершен</option>
        <option value="rejected">Отклонен</option>
      </select>
      
      <input 
        v-model="filters.search" 
        @input="debouncedSearch" 
        type="text" 
        placeholder="Поиск по компании или продукту..." 
        class="form-input"
      >
    </div>

    <div v-if="loading" class="loading">
      Загрузка заказов...
    </div>

    <div v-if="error" class="error">
      {{ error }}
    </div>

    <div v-if="!loading && !error" class="orders-list">
      <div v-if="orders.length === 0" class="empty-state">
        Заказы не найдены
      </div>

      <div v-else class="orders-grid">
        <div v-for="order in orders" :key="order.id" class="order-card">
          <div class="order-header">
            <h3>Заказ #{{ order.id }}</h3>
            <span :class="['status-badge', `status-${order.status}`]">
              {{ getStatusText(order.status) }}
            </span>
          </div>

          <div class="order-content">
                 <div class="order-info">
                   <p><strong>Компания:</strong> {{ order.company?.name || 'Не указана' }}</p>
                   <p><strong>Продукт:</strong> {{ order.product?.name || 'Не указан' }} ({{ order.quantity || 0 }} {{ order.product?.unit || '' }})</p>
                   <p><strong>Срок:</strong> {{ formatDate(order.deadline) }}</p>
                   <p><strong>Создан:</strong> {{ formatDate(order.created_at) }}</p>
                 </div>
            
            <div class="order-actions">
              <button 
                v-if="canEditOrder(order)" 
                @click="editOrder(order)" 
                class="btn btn-sm btn-outline"
              >
                Редактировать
              </button>
              <button 
                v-if="canCompleteOrder(order)" 
                @click="completeOrder(order)" 
                class="btn btn-sm btn-success"
              >
                Завершить
              </button>
              <button 
                v-if="canRejectOrder(order)" 
                @click="rejectOrder(order)" 
                class="btn btn-sm btn-danger"
              >
                Отклонить
              </button>
              <button 
                v-if="canAddProduction(order)" 
                @click="addProduction(order)" 
                class="btn btn-sm btn-primary"
              >
                Добавить производство
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div v-if="pagination && pagination.last_page > 1" class="pagination">
      <button 
        @click="changePage(pagination.current_page - 1)" 
        :disabled="pagination.current_page <= 1"
        class="btn btn-outline"
      >
        Назад
      </button>

      <span class="page-info">
        Страница {{ pagination.current_page }} из {{ pagination.last_page }}
      </span>
      
      <button 
        @click="changePage(pagination.current_page + 1)" 
        :disabled="pagination.current_page >= pagination.last_page"
        class="btn btn-outline"
      >
        Вперед
      </button>
    </div>

    <div v-if="showCreateForm || editingOrder" class="modal-overlay" @click="closeModal">
      <div class="modal" @click.stop>
        <div class="modal-header">
          <h3>{{ editingOrder ? 'Редактировать заказ' : 'Создать заказ' }}</h3>
          <button @click="closeModal" class="btn-close">&times;</button>
        </div>
        
        <form @submit.prevent="saveOrder" class="modal-body">
                 <div class="form-group">
                   <label class="form-label">Компания *</label>
                   <select v-model="orderForm.company_id" class="form-select" required>
                     <option value="">Выберите компанию</option>
                     <option v-for="company in companies" :key="company.value" :value="company.value">
                       {{ company.label }}
                     </option>
                   </select>
                 </div>

                 <div class="form-group">
                   <label class="form-label">Продукт *</label>
                   <select v-model="orderForm.product_id" class="form-select" required>
                     <option value="">Выберите продукт</option>
                     <option v-for="product in products" :key="product.id" :value="product.id">
                       {{ product.name }} ({{ product.unit }})
                     </option>
                   </select>
                 </div>

                 <div class="form-group">
                   <label class="form-label">Количество *</label>
                   <input 
                     v-model="orderForm.quantity" 
                     type="number" 
                     min="1" 
                     step="1"
                     class="form-input" 
                     required
                   >
                 </div>
                 
                 <div class="form-group">
                   <label class="form-label">Срок выполнения</label>
                   <input 
                     v-model="orderForm.deadline" 
                     type="date" 
                     class="form-input" 
                     required
                   >
                 </div>
          
          <div class="form-actions">
            <button type="button" @click="closeModal" class="btn btn-outline">
              Отмена
            </button>
            <button type="submit" class="btn btn-primary" :disabled="saving">
              {{ saving ? 'Сохранение...' : 'Сохранить' }}
            </button>
          </div>
        </form>
      </div>
    </div>
    <div v-if="showProductionForm" class="modal-overlay" @click="closeProductionModal">
      <div class="modal" @click.stop>
        <div class="modal-header">
          <h3>Добавить производство к заказу #{{ selectedOrder?.id }}</h3>
          <button @click="closeProductionModal" class="btn-close">&times;</button>
        </div>
        
        <form @submit.prevent="saveProduction" class="modal-body">
          <div class="form-group">
            <label class="form-label">Что производить *</label>
            <div class="components-section">
              <div v-for="(component, index) in productionForm.components" :key="index" class="component-row">
                <select v-model="component.product_id" class="form-select" required>
                  <option value="">Выберите продукт/материал</option>
                  <optgroup label="Продукты">
                    <option v-for="product in products" :key="product.id" :value="product.id">
                      {{ product.name }} ({{ product.unit }})
                    </option>
                  </optgroup>
                  <optgroup label="Материалы">
                    <option v-for="material in materials" :key="material.id" :value="material.id">
                      {{ material.name }} ({{ material.unit }})
                    </option>
                  </optgroup>
                </select>
                <input v-model.number="component.quantity" type="number" min="0.01" step="0.01"
                    placeholder="Количество" class="form-input" required />
                <button @click="removeComponent(index)" type="button" class="btn btn-sm btn-danger">
                  Удалить
                </button>
              </div>
              <button @click="addComponent" type="button" class="btn btn-sm btn-secondary">
                Добавить продукт/материал
              </button>
            </div>
          </div>

          <div class="form-actions">
            <button type="button" @click="closeProductionModal" class="btn btn-secondary">
              Отмена
            </button>
            <button type="submit" :disabled="saving" class="btn btn-primary">
              {{ saving ? 'Создание...' : 'Создать задание' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, reactive, onMounted } from 'vue'
import axios from 'axios'

export default {
  name: 'Orders',
  setup() {
    const orders = ref([])
    const companies = ref([])
    const products = ref([])
    const materials = ref([])
    const loading = ref(true)
    const saving = ref(false)
    const error = ref(null)
    const showCreateForm = ref(false)
    const showProductionForm = ref(false)
    const editingOrder = ref(null)
    const selectedOrder = ref(null)
    const pagination = ref(null)
    
    const filters = reactive({
      status: '',
      search: ''
    })
    
           const orderForm = reactive({
             company_id: '',
             product_id: '',
             quantity: '',
             deadline: ''
           })

    const productionForm = reactive({
      components: []
    })

    const fetchOrders = async (page = 1) => {
      try {
        loading.value = true
        const params = new URLSearchParams({
          page: page.toString(),
          ...(filters.status && { status: filters.status }),
          ...(filters.search && { search: filters.search })
        })
        
        const response = await axios.get(`/orders?${params}`)
        orders.value = response.data.data
        pagination.value = response.data.meta
      } catch (err) {
        error.value = 'Ошибка загрузки заказов: ' + (err.response?.data?.message || err.message)
      } finally {
        loading.value = false
      }
    }

    const fetchCompanies = async () => {
      try {
        const response = await axios.get('/companies/select')
        companies.value = response.data.data || response.data
      } catch (err) {
      }
    }

    const fetchProducts = async () => {
      try {
        const response = await axios.get('/products?type=product')
        products.value = response.data.data || []
      } catch (err) {
      }
    }

    const fetchMaterials = async () => {
      try {
        const response = await axios.get('/products?type=material')
        materials.value = response.data.data || []
      } catch (err) {
      }
    }

    const debouncedSearch = debounce(() => {
      fetchOrders()
    }, 500)

    const changePage = (page) => {
      if (page >= 1 && page <= pagination.value.last_page) {
        fetchOrders(page)
      }
    }


           const editOrder = (order) => {
             editingOrder.value = order
             orderForm.company_id = order.company.id
             orderForm.product_id = order.product?.id || ''
             orderForm.quantity = order.quantity || ''
             orderForm.deadline = order.deadline.split('T')[0]
           }

    const saveOrder = async () => {
      try {
        saving.value = true
        error.value = null
        const url = editingOrder.value 
          ? `/orders/${editingOrder.value.id}`
          : '/orders'
        
        const method = editingOrder.value ? 'put' : 'post'
        
        await axios[method](url, orderForm)
        closeModal()
        fetchOrders()
      } catch (err) {
        error.value = 'Ошибка сохранения заказа: ' + (err.response?.data?.message || err.message)
      } finally {
        saving.value = false
      }
    }

    const completeOrder = async (order) => {
      if (confirm(`Завершить заказ #${order.id}?`)) {
        try {
          error.value = null
          await axios.post(`/orders/${order.id}/complete`)
          fetchOrders()
        } catch (err) {
          error.value = 'Ошибка завершения заказа: ' + (err.response?.data?.message || err.message)
        }
      }
    }

    const rejectOrder = async (order) => {
      if (confirm(`Отклонить заказ #${order.id}?`)) {
        try {
          error.value = null
          await axios.post(`/orders/${order.id}/reject`)
          fetchOrders()
        } catch (err) {
          error.value = 'Ошибка отклонения заказа: ' + (err.response?.data?.message || err.message)
        }
      }
    }

           const closeModal = () => {
             showCreateForm.value = false
             editingOrder.value = null
             Object.assign(orderForm, {
               company_id: '',
               product_id: '',
               quantity: '',
               deadline: ''
             })
           }

    const addProduction = (order) => {
      selectedOrder.value = order
      productionForm.components = []
      addComponent()
      showProductionForm.value = true
    }

    const addComponent = () => {
      productionForm.components.push({
        product_id: '',
        quantity: 0
      })
    }

    const removeComponent = (index) => {
      productionForm.components.splice(index, 1)
    }

    const saveProduction = async () => {
      try {
        saving.value = true
        error.value = null

        if (productionForm.components.length === 0) {
          error.value = 'Необходимо добавить хотя бы один продукт или материал'
          return
        }

        const taskData = {
          order_id: selectedOrder.value.id,
          components: productionForm.components.filter(c => c.product_id && c.quantity > 0)
        }

        if (taskData.components.length === 0) {
          error.value = 'Необходимо указать количество для всех продуктов/материалов'
          return
        }

        await axios.post('/production-tasks/with-components', taskData)
        closeProductionModal()
        fetchOrders()
      } catch (err) {
        error.value = 'Ошибка создания задания: ' + (err.response?.data?.message || err.message)
      } finally {
        saving.value = false
      }
    }

    const closeProductionModal = () => {
      showProductionForm.value = false
      selectedOrder.value = null
      productionForm.components = []
    }

    const getStatusText = (status) => {
      const statuses = {
        wait: 'Ожидание',
        in_process: 'В процессе',
        completed: 'Завершен',
        rejected: 'Отклонен'
      }
      return statuses[status] || status
    }

    const formatDate = (dateString) => {
      return new Date(dateString).toLocaleDateString('ru-RU')
    }

    const canEditOrder = (order) => {
      return order.status === 'wait' || order.status === 'in_process'
    }

    const canCompleteOrder = (order) => {
      return order.status === 'in_process'
    }

    const canRejectOrder = (order) => {
      return order.status === 'wait' || order.status === 'in_process'
    }

    const canAddProduction = (order) => {
      return order.status === 'wait'
    }

    onMounted(() => {
      fetchOrders()
      fetchCompanies()
      fetchProducts()
      fetchMaterials()
      window.addEventListener('openCreateOrderModal', () => {
        showCreateForm.value = true
      })
    })

    return {
      orders,
      companies,
      products,
      materials,
      loading,
      saving,
      error,
      showCreateForm,
      showProductionForm,
      editingOrder,
      selectedOrder,
      pagination,
      filters,
      orderForm,
      productionForm,
      fetchOrders,
      changePage,
      editOrder,
      saveOrder,
      completeOrder,
      rejectOrder,
      addProduction,
      addComponent,
      removeComponent,
      saveProduction,
      closeModal,
      closeProductionModal,
      getStatusText,
      formatDate,
      canEditOrder,
      canCompleteOrder,
      canRejectOrder,
      canAddProduction,
      debouncedSearch
    }
  }
}

function debounce(func, wait) {
  let timeout
  return function executedFunction(...args) {
    const later = () => {
      clearTimeout(timeout)
      func(...args)
    }
    clearTimeout(timeout)
    timeout = setTimeout(later, wait)
  }
}
</script>

