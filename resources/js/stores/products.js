import { defineStore } from 'pinia'
import axios from 'axios'

export const useProductsStore = defineStore('products', {
  state: () => ({
    products: [],
    materials: [],
    loading: false,
    error: null,
    pagination: null,
    filters: {
      type: '',
      search: ''
    }
  }),

  getters: {
    getAllItems: (state) => {
      return [...state.products, ...state.materials]
    },

    getItemsByType: (state) => (type) => {
      if (type === 'product') return state.products
      if (type === 'material') return state.materials
      return state.getAllItems
    },

    getFilteredItems: (state) => {
      let filtered = state.getAllItems

      if (state.filters.type) {
        filtered = filtered.filter(item => item.type === state.filters.type)
      }

      if (state.filters.search) {
        const search = state.filters.search.toLowerCase()
        filtered = filtered.filter(item => 
          item.name.toLowerCase().includes(search) ||
          item.unit.toLowerCase().includes(search)
        )
      }

      return filtered
    },

    getItemById: (state) => (id) => {
      return state.getAllItems.find(item => item.id === id)
    }
  },

  actions: {
    async fetchProducts(page = 1) {
      try {
        this.loading = true
        this.error = null

        const params = new URLSearchParams({
          page: page
        })

        if (this.filters.type) {
          params.append('type', this.filters.type)
        }

        if (this.filters.search) {
          params.append('search', this.filters.search)
        }

        const response = await axios.get(`/products?${params}`)
        
        const items = response.data.data || []
        this.products = items.filter(item => item.type === 'product')
        this.materials = items.filter(item => item.type === 'material')
        
        this.pagination = {
          current_page: response.data.current_page,
          last_page: response.data.last_page,
          total: response.data.total
        }
      } catch (error) {
        this.error = 'Ошибка загрузки продуктов: ' + (error.response?.data?.message || error.message)
        throw error
      } finally {
        this.loading = false
      }
    },

    async createProduct(productData) {
      try {
        this.loading = true
        this.error = null

        const response = await axios.post('/products', productData)
        
        const newItem = response.data.data
        if (newItem.type === 'product') {
          this.products.unshift(newItem)
        } else {
          this.materials.unshift(newItem)
        }
        
        return newItem
      } catch (error) {
        this.error = 'Ошибка создания продукта: ' + (error.response?.data?.message || error.message)
        throw error
      } finally {
        this.loading = false
      }
    },

    async updateProduct(id, productData) {
      try {
        this.loading = true
        this.error = null

        const response = await axios.put(`/products/${id}`, productData)
        const updatedItem = response.data.data
        
        if (updatedItem.type === 'product') {
          const index = this.products.findIndex(item => item.id === id)
          if (index !== -1) {
            this.products[index] = updatedItem
          }
        } else {
          const index = this.materials.findIndex(item => item.id === id)
          if (index !== -1) {
            this.materials[index] = updatedItem
          }
        }
        
        return updatedItem
      } catch (error) {
        this.error = 'Ошибка обновления продукта: ' + (error.response?.data?.message || error.message)
        throw error
      } finally {
        this.loading = false
      }
    },

    async deleteProduct(id) {
      try {
        this.loading = true
        this.error = null

        await axios.delete(`/products/${id}`)
        
        this.products = this.products.filter(item => item.id !== id)
        this.materials = this.materials.filter(item => item.id !== id)
      } catch (error) {
        this.error = 'Ошибка удаления продукта: ' + (error.response?.data?.message || error.message)
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
        type: '',
        search: ''
      }
    },

    clearError() {
      this.error = null
    },

    async initialize() {
      await this.fetchProducts()
    }
  }
})
