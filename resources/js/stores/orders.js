import { defineStore } from 'pinia'
import axios from 'axios'

export const useOrdersStore = defineStore('orders', {
  state: () => ({
    orders: [],
    companies: [],
    products: [],
    materials: [],
    loading: false,
    error: null,
    pagination: null,
    filters: {
      status: '',
      search: ''
    }
  }),

  getters: {
    getOrdersByStatus: (state) => (status) => {
      if (!status) return state.orders
      return state.orders.filter(order => order.status === status)
    },

    getOrdersWithoutTasks: (state) => {
      return state.orders.filter(order => order.status === 'wait')
    },

    getOrderById: (state) => (id) => {
      return state.orders.find(order => order.id === id)
    },

    getFilteredOrders: (state) => {
      let filtered = state.orders

      if (state.filters.status) {
        filtered = filtered.filter(order => order.status === state.filters.status)
      }

      if (state.filters.search) {
        const search = state.filters.search.toLowerCase()
        filtered = filtered.filter(order => 
          order.company?.name?.toLowerCase().includes(search) ||
          order.id.toString().includes(search)
        )
      }

      return filtered
    }
  },

  actions: {
    async fetchOrders(page = 1) {
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

        const response = await axios.get(`/orders?${params}`)
        this.orders = response.data.data
        this.pagination = {
          current_page: response.data.current_page,
          last_page: response.data.last_page,
          total: response.data.total
        }
      } catch (error) {
        this.error = 'Ошибка загрузки заказов: ' + (error.response?.data?.message || error.message)
        throw error
      } finally {
        this.loading = false
      }
    },

    async fetchCompanies() {
      try {
        const response = await axios.get('/companies')
        this.companies = response.data.data || []
      } catch (error) {
        console.error('Ошибка загрузки компаний:', error)
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

    async createOrder(orderData) {
      try {
        this.loading = true
        this.error = null

        const response = await axios.post('/orders', orderData)
    
        this.orders.unshift(response.data.data)
        
        return response.data.data
      } catch (error) {
        this.error = 'Ошибка создания заказа: ' + (error.response?.data?.message || error.message)
        throw error
      } finally {
        this.loading = false
      }
    },

    async updateOrder(id, orderData) {
      try {
        this.loading = true
        this.error = null

        const response = await axios.put(`/orders/${id}`, orderData)
        
        const index = this.orders.findIndex(order => order.id === id)
        if (index !== -1) {
          this.orders[index] = response.data.data
        }
        
        return response.data.data
      } catch (error) {
        this.error = 'Ошибка обновления заказа: ' + (error.response?.data?.message || error.message)
        throw error
      } finally {
        this.loading = false
      }
    },

    async deleteOrder(id) {
      try {
        this.loading = true
        this.error = null

        await axios.delete(`/orders/${id}`)
        
        this.orders = this.orders.filter(order => order.id !== id)
      } catch (error) {
        this.error = 'Ошибка удаления заказа: ' + (error.response?.data?.message || error.message)
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

    clearError() {
      this.error = null
    },

    async initialize() {
      await Promise.all([
        this.fetchOrders(),
        this.fetchCompanies(),
        this.fetchProducts(),
        this.fetchMaterials()
      ])
    }
  }
})
