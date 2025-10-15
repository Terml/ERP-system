import { defineStore } from 'pinia'
import axios from 'axios'

export const useAuthStore = defineStore('auth', {
  state: () => ({
    user: null,
    isAuthenticated: false,
    loading: false,
    error: null
  }),

  getters: {
    isAdmin: (state) => {
      return state.user?.roles?.some(role => role.name === 'admin')
    },

    isManager: (state) => {
      return state.user?.roles?.some(role => role.name === 'manager')
    },

    isDispatcher: (state) => {
      return state.user?.roles?.some(role => role.name === 'dispatcher')
    },

    isMaster: (state) => {
      return state.user?.roles?.some(role => role.name === 'master')
    },

    isOTK: (state) => {
      return state.user?.roles?.some(role => role.name === 'otk')
    },

    userRoles: (state) => {
      return state.user?.roles?.map(role => role.name) || []
    },

    hasRole: (state) => (roleName) => {
      return state.user?.roles?.some(role => role.name === roleName)
    },

    hasAnyRole: (state) => (roleNames) => {
      return state.user?.roles?.some(role => roleNames.includes(role.name))
    }
  },

  actions: {
    async loadUser() {
      try {
        this.loading = true
        this.error = null

        const response = await axios.get('/user')
        this.user = response.data
        this.isAuthenticated = true
      } catch (error) {
        this.user = null
        this.isAuthenticated = false
        console.error('Ошибка загрузки пользователя:', error)
      } finally {
        this.loading = false
      }
    },

    async login(credentials) {
      try {
        this.loading = true
        this.error = null

        const response = await axios.post('/login', credentials)
        
        if (response.data.success) {
          await this.loadUser()
          return response.data
        }
      } catch (error) {
        this.error = 'Ошибка входа: ' + (error.response?.data?.message || error.message)
        throw error
      } finally {
        this.loading = false
      }
    },

    async logout() {
      try {
        this.loading = true
        this.error = null

        await axios.post('/logout')
        
        this.user = null
        this.isAuthenticated = false
      } catch (error) {
        console.error('Ошибка выхода:', error)
        this.user = null
        this.isAuthenticated = false
      } finally {
        this.loading = false
      }
    },

    clearError() {
      this.error = null
    },

    async initialize() {
      await this.loadUser()
    }
  }
})
