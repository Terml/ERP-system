<template>
  <div class="user-management-page">
     <div class="page-header">
       <h1>Панель администратора</h1>
       <button @click="showCreateForm = true" class="btn btn-primary">
         Создать пользователя
       </button>
     </div>

    <div class="filters">
      <div class="filter-group">
        <select v-model="filters.role" @change="fetchUsers" class="form-select">
          <option value="">Все роли</option>
          <option v-for="role in roles" :key="role.id" :value="role.role">
            {{ role.description }}
          </option>
        </select>
      </div>
      <div class="filter-group">
        <input
          v-model="filters.search"
          @input="debouncedSearch"
          type="text"
          placeholder="Поиск по логину..."
          class="form-input"
        />
      </div>
    </div>

    <div v-if="error" class="error-message">
      {{ error }}
    </div>

    <div v-if="loading" class="loading">
      Загрузка пользователей...
    </div>

    <div v-else class="users-table">
      <table>
        <thead>
          <tr>
            <th>ID</th>
            <th>Логин</th>
            <th>Роли</th>
            <th>Создан</th>
            <th>Действия</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="user in users" :key="user.id">
            <td>{{ user.id }}</td>
            <td>{{ user.login }}</td>
            <td>
              <span 
                v-for="role in user.roles" 
                :key="role.id" 
                class="role-badge"
                :class="`role-${role.role}`"
              >
                {{ role.description }}
              </span>
            </td>
            <td>{{ formatDate(user.created_at) }}</td>
            <td>
              <button @click="editUser(user)" class="btn btn-sm btn-secondary">
                Редактировать
              </button>
              <button @click="deleteUser(user)" class="btn btn-sm btn-danger">
                Удалить
              </button>
            </td>
          </tr>
        </tbody>
      </table>

      <div v-if="pagination" class="pagination">
        <button
          @click="fetchUsers(pagination.current_page - 1)"
          :disabled="pagination.current_page <= 1"
          class="btn btn-sm"
        >
          Предыдущая
        </button>
        <span class="pagination-info">
          Страница {{ pagination.current_page }} из {{ pagination.last_page }}
        </span>
        <button
          @click="fetchUsers(pagination.current_page + 1)"
          :disabled="pagination.current_page >= pagination.last_page"
          class="btn btn-sm"
        >
          Следующая
        </button>
      </div>
    </div>

    <div v-if="showCreateForm || editingUser" class="modal-overlay" @click="closeModal">
      <div class="modal-content" @click.stop>
        <div class="modal-header">
          <h2>{{ editingUser ? 'Редактировать пользователя' : 'Создать пользователя' }}</h2>
          <button @click="closeModal" class="btn-close">&times;</button>
        </div>

        <form @submit.prevent="saveUser" class="modal-body">
          <div class="form-group">
            <label class="form-label">Логин *</label>
            <input
              v-model="userForm.login"
              type="text"
              required
              class="form-input"
            />
          </div>


          <div v-if="!editingUser" class="form-group">
            <label class="form-label">Пароль *</label>
            <input
              v-model="userForm.password"
              type="password"
              required
              class="form-input"
            />
          </div>

          <div v-if="editingUser" class="form-group">
            <label class="form-label">Новый пароль (оставьте пустым, чтобы не менять)</label>
            <input
              v-model="userForm.password"
              type="password"
              class="form-input"
            />
          </div>

          <div class="form-group">
            <label class="form-label">Роли *</label>
            <div class="roles-selection">
              <label v-for="role in roles" :key="role.id" class="role-checkbox">
                <input
                  v-model="userForm.role_ids"
                  :value="role.id"
                  type="checkbox"
                />
                <span class="role-name">{{ role.description }}</span>
              </label>
            </div>
          </div>

          <div class="form-actions">
            <button type="button" @click="closeModal" class="btn btn-secondary">
              Отмена
            </button>
            <button type="submit" :disabled="saving" class="btn btn-primary">
              {{ saving ? 'Сохранение...' : 'Сохранить' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios'

export default {
  name: 'AdminPanel',
  data() {
    return {
      users: [],
      roles: [],
      pagination: null,
      loading: false,
      error: null,
      saving: false,
      showCreateForm: false,
      editingUser: null,
      filters: {
        role: '',
        search: ''
      },
      userForm: {
        login: '',
        password: '',
        role_ids: []
      },
      searchTimeout: null
    }
  },
  mounted() {
    this.fetchUsers()
    this.fetchRoles()
  },
  methods: {
    async fetchUsers(page = 1) {
      try {
        this.loading = true
        this.error = null

        const params = new URLSearchParams({
          page: page
        })

        if (this.filters.role) {
          params.append('role', this.filters.role)
        }

        if (this.filters.search) {
          params.append('search', this.filters.search)
        }

        const response = await axios.get(`/users?${params}`)
        this.users = response.data.data
        this.pagination = {
          current_page: response.data.current_page,
          last_page: response.data.last_page,
          total: response.data.total
        }
      } catch (err) {
        this.error = 'Ошибка загрузки пользователей: ' + (err.response?.data?.message || err.message)
      } finally {
        this.loading = false
      }
    },

    async fetchRoles() {
      try {
        const response = await axios.get('/roles')
        this.roles = response.data.data || response.data
      } catch (err) {
        this.error = 'Ошибка загрузки ролей: ' + (err.response?.data?.message || err.message)
      }
    },

    debouncedSearch() {
      clearTimeout(this.searchTimeout)
      this.searchTimeout = setTimeout(() => {
        this.fetchUsers()
      }, 500)
    },

    editUser(user) {
      this.editingUser = user
      this.userForm = {
        login: user.login,
        password: '',
        role_ids: user.roles.map(role => role.id)
      }
    },

    async saveUser() {
      try {
        this.saving = true
        this.error = null

        const url = this.editingUser
          ? `/users/${this.editingUser.id}`
          : '/users'
        const method = this.editingUser ? 'put' : 'post'

        const data = { ...this.userForm }
        if (!data.password) {
          delete data.password
        }

        await axios[method](url, data)

        this.closeModal()
        this.fetchUsers()
      } catch (err) {
        if (err.response?.data?.errors) {
          const errors = err.response.data.errors
          const errorMessages = []
          for (const field in errors) {
            errorMessages.push(`${field}: ${errors[field].join(', ')}`)
          }
          this.error = 'Ошибка валидации: ' + errorMessages.join('; ')
        } else {
          this.error = 'Ошибка сохранения пользователя: ' + (err.response?.data?.message || err.message)
        }
      } finally {
        this.saving = false
      }
    },

    async deleteUser(user) {
      if (!confirm(`Удалить пользователя "${user.login}"?`)) {
        return
      }

      try {
        await axios.delete(`/users/${user.id}`)
        this.fetchUsers()
      } catch (err) {
        this.error = 'Ошибка удаления пользователя: ' + (err.response?.data?.message || err.message)
      }
    },

    closeModal() {
      this.showCreateForm = false
      this.editingUser = null
      this.userForm = {
        login: '',
        password: '',
        role_ids: []
      }
    },

    formatDate(dateString) {
      return new Date(dateString).toLocaleDateString('ru-RU')
    }
  }
}
</script>

<style scoped>
.user-management-page {
  padding: 20px;
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

.filter-group {
  display: flex;
  flex-direction: column;
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
  min-width: 200px;
}

.error-message {
  background: #fee;
  color: #c33;
  padding: 10px;
  border-radius: 4px;
  margin-bottom: 20px;
}

.loading {
  text-align: center;
  padding: 40px;
  color: #666;
}

.users-table {
  background: white;
  border-radius: 8px;
  overflow: hidden;
  box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.users-table table {
  width: 100%;
  border-collapse: collapse;
}

.users-table th,
.users-table td {
  padding: 12px;
  text-align: left;
  border-bottom: 1px solid #eee;
}

.users-table th {
  background: #f8f9fa;
  font-weight: 600;
  color: #333;
}

.role-badge {
  display: inline-block;
  padding: 2px 8px;
  margin: 2px;
  border-radius: 12px;
  font-size: 12px;
  font-weight: 500;
}

.role-admin { background: #dc3545; color: white; }
.role-manager { background: #007bff; color: white; }
.role-dispatcher { background: #28a745; color: white; }
.role-master { background: #ffc107; color: #333; }
.role-otk { background: #6f42c1; color: white; }

.pagination {
  display: flex;
  justify-content: center;
  align-items: center;
  gap: 15px;
  padding: 20px;
}

.pagination-info {
  color: #666;
  font-size: 14px;
}

.btn {
  padding: 8px 16px;
  border-radius: 6px;
  cursor: pointer;
  font-size: 14px;
  text-align: center;
  transition: background-color 0.2s;
  border: none;
}

.btn-primary { background: #3b82f6; color: white; }
.btn-primary:hover { background: #2563eb; }

.btn-secondary { background: #6b7280; color: white; }
.btn-secondary:hover { background: #4b5563; }

.btn-danger { background: #ef4444; color: white; }
.btn-danger:hover { background: #dc2626; }

.btn-sm { padding: 6px 12px; font-size: 12px; }

.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0,0,0,0.5);
  display: flex;
  justify-content: center;
  align-items: center;
  z-index: 1000;
}

.modal-content {
  background: white;
  border-radius: 8px;
  max-width: 500px;
  width: 90%;
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

.modal-header h2 {
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

.roles-selection {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.role-checkbox {
  display: flex;
  align-items: center;
  gap: 8px;
  cursor: pointer;
}

.role-checkbox input[type="checkbox"] {
  margin: 0;
}

.role-name {
  font-size: 14px;
}

.form-actions {
  display: flex;
  gap: 10px;
  justify-content: flex-end;
  margin-top: 20px;
}
</style>
