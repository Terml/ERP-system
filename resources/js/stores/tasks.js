import { defineStore } from 'pinia'
import axios from 'axios'

export const useTasksStore = defineStore('tasks', {
  state: () => ({
    tasks: [],
    availableOrders: [],
    products: [],
    materials: [],
    masters: [],
    user: null,
    loading: false,
    loadingOrders: false,
    error: null,
    pagination: null,
    filters: {
      status: '',
      search: ''
    },
    activeTab: 'tasks'
  }),

  getters: {
    getTasksByStatus: (state) => (status) => {
      if (!status) return state.tasks
      return state.tasks.filter(task => task.status === status)
    },

    getTaskById: (state) => (id) => {
      return state.tasks.find(task => task.id === id)
    },

    getFilteredTasks: (state) => {
      let filtered = state.tasks

      if (state.filters.status) {
        filtered = filtered.filter(task => task.status === state.filters.status)
      }

      if (state.filters.search) {
        const search = state.filters.search.toLowerCase()
        filtered = filtered.filter(task => 
          task.order_id.toString().includes(search) ||
          task.order?.company?.name?.toLowerCase().includes(search) ||
          task.user?.login?.toLowerCase().includes(search)
        )
      }

      return filtered
    },

    canCreateTask: (state) => {
      return state.user?.roles?.some(role => ['admin', 'dispatcher'].includes(role.name))
    },

    getMyTasks: (state) => {
      if (!state.user) return []
      return state.tasks.filter(task => 
        task.user_id === state.user.id && 
        ['in_process', 'checking'].includes(task.status)
      )
    }
  },

  actions: {
    async loadUser() {
      try {
        const response = await axios.get('/user')
        this.user = response.data
      } catch (error) {
        console.error('Ошибка загрузки пользователя:', error)
      }
    },

    async fetchTasks(page = 1) {
      try {
        this.loading = true
        this.error = null

        const params = new URLSearchParams({
          page: page
        })

        if (this.filters.status) {
          params.append('status', this.filters.status)
        }

        if (this.filters.search) {
          params.append('search', this.filters.search)
        }

        const response = await axios.get(`/production-tasks?${params}`)
        this.tasks = response.data.data
        this.pagination = {
          current_page: response.data.current_page,
          last_page: response.data.last_page,
          total: response.data.total
        }
      } catch (error) {
        this.error = 'Ошибка загрузки заданий: ' + (error.response?.data?.message || error.message)
        throw error
      } finally {
        this.loading = false
      }
    },

    async fetchOrdersWithoutTasks() {
      try {
        this.loadingOrders = true
        const response = await axios.get('/orders?status=wait')
        this.availableOrders = response.data.data || []
      } catch (error) {
        this.error = 'Ошибка загрузки заказов: ' + (error.response?.data?.message || error.message)
        throw error
      } finally {
        this.loadingOrders = false
      }
    },

    async fetchProducts() {
      try {
        const response = await axios.get('/products?type=product')
        this.products = response.data.data || []
      } catch (error) {
        console.error('Ошибка загрузки продуктов:', error)
      }
    },

    async fetchMaterials() {
      try {
        const response = await axios.get('/products?type=material')
        this.materials = response.data.data || []
      } catch (error) {
        console.error('Ошибка загрузки материалов:', error)
      }
    },

    async fetchMasters() {
      try {
        const response = await axios.get('/users?role=master')
        this.masters = response.data.data || []
      } catch (error) {
        console.error('Ошибка загрузки мастеров:', error)
      }
    },

    async createTask(taskData) {
      try {
        this.loading = true
        this.error = null

        const response = await axios.post('/production-tasks/with-components', taskData)
        
        this.tasks.unshift(response.data.data)
        
        return response.data.data
      } catch (error) {
        this.error = 'Ошибка создания задания: ' + (error.response?.data?.message || error.message)
        throw error
      } finally {
        this.loading = false
      }
    },

    async takeTask(taskId) {
      try {
        this.loading = true
        this.error = null

        await axios.post(`/production-tasks/${taskId}/take`)
        
        const task = this.tasks.find(t => t.id === taskId)
        if (task) {
          task.status = 'in_process'
          task.user_id = this.user.id
        }
        
        await this.fetchTasks()
      } catch (error) {
        this.error = 'Ошибка взятия задания: ' + (error.response?.data?.message || error.message)
        throw error
      } finally {
        this.loading = false
      }
    },

    async sendForInspection(taskId) {
      try {
        this.loading = true
        this.error = null

        await axios.post(`/production-tasks/${taskId}/send-for-inspection`)
        
        const task = this.tasks.find(t => t.id === taskId)
        if (task) {
          task.status = 'checking'
        }
        
        await this.fetchTasks()
      } catch (error) {
        this.error = 'Ошибка отправки на проверку: ' + (error.response?.data?.message || error.message)
        throw error
      } finally {
        this.loading = false
      }
    },

    async acceptTask(taskId, decision, otkUserId) {
      try {
        this.loading = true
        this.error = null

        await axios.post(`/production-tasks/${taskId}/accept-with-completion`, {
          decision: decision,
          otk_user_id: otkUserId
        })
        
        const task = this.tasks.find(t => t.id === taskId)
        if (task) {
          task.status = 'completed'
        }
        
        await this.fetchTasks()
      } catch (error) {
        this.error = 'Ошибка принятия задания: ' + (error.response?.data?.message || error.message)
        throw error
      } finally {
        this.loading = false
      }
    },

    async rejectTask(taskId, otkUserId) {
      try {
        this.loading = true
        this.error = null

        await axios.post(`/production-tasks/${taskId}/reject`, {
          otk_user_id: otkUserId
        })
        
        const task = this.tasks.find(t => t.id === taskId)
        if (task) {
          task.status = 'rejected'
        }
        
        await this.fetchTasks()
      } catch (error) {
        this.error = 'Ошибка отклонения задания: ' + (error.response?.data?.message || error.message)
        throw error
      } finally {
        this.loading = false
      }
    },

    async updateTaskComponents(taskId, componentsData) {
      try {
        this.loading = true
        this.error = null

        await axios.put(`/production-tasks/${taskId}/components`, {
          components: componentsData
        })
        
        await this.fetchTasks()
      } catch (error) {
        this.error = 'Ошибка обновления компонентов: ' + (error.response?.data?.message || error.message)
        throw error
      } finally {
        this.loading = false
      }
    },

    setFilters(filters) {
      this.filters = { ...this.filters, ...filters }
    },

    clearFilters() {
      this.filters = {
        status: '',
        search: ''
      }
    },

    setActiveTab(tab) {
      this.activeTab = tab
      if (tab === 'orders') {
        this.fetchOrdersWithoutTasks()
      }
    },

    clearError() {
      this.error = null
    },

    async initialize() {
      await Promise.all([
        this.loadUser(),
        this.fetchTasks(),
        this.fetchOrdersWithoutTasks(),
        this.fetchProducts(),
        this.fetchMaterials(),
        this.fetchMasters()
      ])
    }
  }
})
