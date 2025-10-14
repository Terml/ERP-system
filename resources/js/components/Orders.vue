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

<style scoped>
.orders-page {
  padding: 20px;
  background: #fff;
  border-radius: 8px;
  box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.page-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 20px;
}

.filters {
  display: flex;
  gap: 15px;
  margin-bottom: 20px;
}

.form-select,
.form-input {
  padding: 8px 12px;
  border: 1px solid #ddd;
  border-radius: 4px;
  font-size: 14px;
}

.form-select {
  min-width: 150px;
}

.form-input {
  flex: 1;
  min-width: 200px;
}

.loading,
.error {
  text-align: center;
  padding: 20px;
  margin: 20px 0;
}

.error {
  color: #e74c3c;
  background: #fdf2f2;
  border: 1px solid #fecaca;
  border-radius: 4px;
}

.empty-state {
  text-align: center;
  padding: 40px;
  color: #666;
}

.orders-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(400px, 1fr));
  gap: 20px;
}

.order-card {
  border: 1px solid #e1e5e9;
  border-radius: 8px;
  padding: 20px;
  background: #fff;
  transition: box-shadow 0.2s;
}

.order-card:hover {
  box-shadow: 0 4px 8px rgba(0,0,0,0.1);
}

.order-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 15px;
}

.order-header h3 {
  margin: 0;
  color: #333;
}

.status-badge {
  padding: 4px 8px;
  border-radius: 12px;
  font-size: 12px;
  font-weight: 500;
  text-transform: uppercase;
}

.status-wait { background: #fef3c7; color: #92400e; }
.status-in_process { background: #dbeafe; color: #1e40af; }
.status-completed { background: #d1fae5; color: #065f46; }
.status-rejected { background: #fee2e2; color: #991b1b; }

.order-info p {
  margin: 8px 0;
  color: #666;
}

.order-actions {
  display: flex;
  gap: 8px;
  margin-top: 15px;
  flex-wrap: wrap;
}

.btn {
  padding: 8px 16px;
  border: none;
  border-radius: 4px;
  cursor: pointer;
  font-size: 14px;
  text-decoration: none;
  display: inline-block;
  text-align: center;
  transition: background-color 0.2s;
}

.btn-primary { background: #3b82f6; color: white; }
.btn-primary:hover { background: #2563eb; }

.btn-outline { background: transparent; color: #6b7280; border: 1px solid #d1d5db; }
.btn-outline:hover { background: #f9fafb; }

.btn-success { background: #10b981; color: white; }
.btn-success:hover { background: #059669; }

.btn-danger { background: #ef4444; color: white; }
.btn-danger:hover { background: #dc2626; }

.btn-sm { padding: 6px 12px; font-size: 12px; }

.pagination {
  display: flex;
  justify-content: center;
  align-items: center;
  gap: 15px;
  margin-top: 20px;
}

.page-info {
  color: #666;
  font-size: 14px;
}

.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0,0,0,0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
}

.modal {
  background: white;
  border-radius: 8px;
  width: 90%;
  max-width: 500px;
  max-height: 90vh;
  overflow-y: auto;
}

.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 20px;
  border-bottom: 1px solid #e1e5e9;
}

.modal-header h3 {
  margin: 0;
}

.btn-close {
  background: none;
  border: none;
  font-size: 24px;
  cursor: pointer;
  color: #666;
}

.modal-body {
  padding: 20px;
}

.form-group {
  margin-bottom: 15px;
}

.form-label {
  display: block;
  margin-bottom: 5px;
  font-weight: 500;
  color: #333;
}

.form-actions {
  display: flex;
  gap: 10px;
  justify-content: flex-end;
  margin-top: 20px;
}

.components-section {
  border: 1px solid #ddd;
  border-radius: 4px;
  padding: 15px;
}

.component-row {
  display: flex;
  gap: 10px;
  align-items: center;
  margin-bottom: 10px;
}

.component-row select,
.component-row input {
  flex: 1;
}

.note-section {
  margin-top: 20px;
  padding: 15px;
  background: #f8f9fa;
  border-radius: 4px;
  border-left: 4px solid #007bff;
}

.note-section h3 {
  margin: 0 0 10px 0;
  color: #333;
}

.note-section p {
  margin: 0;
  color: #666;
}
</style>
