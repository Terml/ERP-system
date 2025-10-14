<template>
  <div class="products-page">
    <div class="page-header">
      <button @click="showCreateForm = true" class="btn btn-primary">
        Создать продукт
      </button>
    </div>
    <div class="filters">
      <div class="filter-group">
         <select v-model="filters.type" @change="fetchProducts" class="form-select">
           <option value="">Все типы</option>
           <option value="product">Продукт</option>
           <option value="material">Материал</option>
         </select>
      </div>
      <div class="filter-group">
        <input
          v-model="filters.search"
          @input="debouncedSearch"
          type="text"
          placeholder="Поиск по названию..."
          class="form-input"
        />
      </div>
    </div>
    <div v-if="error" class="error-message">
      {{ error }}
    </div>
    <div v-if="loading" class="loading">
      Загрузка продуктов...
    </div>
    <div v-else class="products-table">
      <table>
        <thead>
          <tr>
            <th>ID</th>
            <th>Название</th>
            <th>Тип</th>
            <th>Единица измерения</th>
            <th>Действия</th>
          </tr>
        </thead>
        <tbody>
           <tr v-for="product in products" :key="product.id">
             <td>{{ product.id }}</td>
             <td>{{ product.name }}</td>
             <td>{{ getTypeLabel(product.type) }}</td>
             <td>{{ product.unit }}</td>
             <td>
              <button @click="editProduct(product)" class="btn btn-sm btn-secondary">
                Редактировать
              </button>
              <button @click="deleteProduct(product)" class="btn btn-sm btn-danger">
                Удалить
              </button>
            </td>
          </tr>
        </tbody>
      </table>
      <div v-if="pagination" class="pagination">
        <button 
          @click="fetchProducts(pagination.current_page - 1)"
          :disabled="pagination.current_page <= 1"
          class="btn btn-sm"
        >
          Предыдущая
        </button>
        <span class="pagination-info">
          Страница {{ pagination.current_page }} из {{ pagination.last_page }}
        </span>
        <button 
          @click="fetchProducts(pagination.current_page + 1)"
          :disabled="pagination.current_page >= pagination.last_page"
          class="btn btn-sm"
        >
          Следующая
        </button>
      </div>
    </div>
    <div v-if="showCreateForm || editingProduct" class="modal-overlay" @click="closeModal">
      <div class="modal-content" @click.stop>
        <h2>{{ editingProduct ? 'Редактировать продукт' : 'Создать продукт' }}</h2>
        
        <form @submit.prevent="saveProduct">
          <div class="form-group">
            <label>Название *</label>
            <input 
              v-model="productForm.name" 
              type="text" 
              required 
              class="form-input"
            />
          </div>
          
          <div class="form-group">
            <label>Тип *</label>
            <select v-model="productForm.type" required class="form-select">
              <option value="product">Продукт</option>
              <option value="material">Материал</option>
            </select>
          </div>
          
          <div class="form-group">
            <label>Единица измерения *</label>
            <input 
              v-model="productForm.unit" 
              type="text" 
              required 
              class="form-input"
              placeholder="шт, кг, м, л и т.д."
            />
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
  name: 'Products',
  data() {
    return {
      products: [],
      pagination: null,
      loading: false,
      error: null,
      saving: false,
      showCreateForm: false,
      editingProduct: null,
      filters: {
        type: '',
        search: ''
      },
       productForm: {
         name: '',
         type: 'product',
         unit: ''
       },
      searchTimeout: null
    }
  },
  mounted() {
    this.fetchProducts()
  },
  methods: {
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
        this.products = response.data.data
        this.pagination = {
          current_page: response.data.current_page,
          last_page: response.data.last_page,
          total: response.data.total
        }
      } catch (err) {
        this.error = 'Ошибка загрузки продуктов: ' + (err.response?.data?.message || err.message)
      } finally {
        this.loading = false
      }
    },
    
     debouncedSearch() {
       clearTimeout(this.searchTimeout)
       this.searchTimeout = setTimeout(() => {
         this.fetchProducts()
       }, 500)
     },
     
     getTypeLabel(type) {
       const labels = {
         'product': 'Продукт',
         'material': 'Материал'
       }
       return labels[type] || type
     },
    
    editProduct(product) {
      this.editingProduct = product
      this.productForm = {
        name: product.name,
        type: product.type,
        unit: product.unit
      }
    },
    
    async saveProduct() {
      try {
        this.saving = true
        
        const url = this.editingProduct 
          ? `/products/${this.editingProduct.id}`
          : '/products'
        const method = this.editingProduct ? 'put' : 'post'
        
        await axios[method](url, this.productForm)
        
        this.closeModal()
        this.fetchProducts()
      } catch (err) {
        this.error = 'Ошибка сохранения продукта: ' + (err.response?.data?.message || err.message)
      } finally {
        this.saving = false
      }
    },
    
    async deleteProduct(product) {
      if (!confirm(`Удалить продукт "${product.name}"?`)) {
        return
      }
      
      try {
        await axios.delete(`/products/${product.id}`)
        this.fetchProducts()
      } catch (err) {
        this.error = 'Ошибка удаления продукта: ' + (err.response?.data?.message || err.message)
      }
    },
    
     closeModal() {
       this.showCreateForm = false
       this.editingProduct = null
       this.productForm = {
         name: '',
         type: 'product',
         unit: ''
       }
     }
  }
}
</script>

<style scoped>
.products-page {
  padding: 20px;
}

.page-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 20px;
}

.page-header h1 {
  margin: 0;
  color: #333;
}

.filters {
  display: flex;
  gap: 15px;
  margin-bottom: 20px;
  align-items: center;
}

.filter-group {
  display: flex;
  flex-direction: column;
}

.form-select, .form-input {
  padding: 8px 12px;
  border: 1px solid #ddd;
  border-radius: 4px;
  font-size: 14px;
}

.form-select:focus, .form-input:focus {
  outline: none;
  border-color: #007bff;
}

.error-message {
  background-color: #f8d7da;
  color: #721c24;
  padding: 12px;
  border-radius: 4px;
  margin-bottom: 20px;
}

.loading {
  text-align: center;
  padding: 40px;
  color: #666;
}

.products-table {
  background: white;
  border-radius: 8px;
  overflow: hidden;
  box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.products-table table {
  width: 100%;
  border-collapse: collapse;
}

.products-table th,
.products-table td {
  padding: 12px;
  text-align: left;
  border-bottom: 1px solid #eee;
}

.products-table th {
  background-color: #f8f9fa;
  font-weight: 600;
  color: #333;
}

.products-table tr:hover {
  background-color: #f8f9fa;
}

.pagination {
  display: flex;
  justify-content: center;
  align-items: center;
  gap: 15px;
  padding: 20px;
  background-color: #f8f9fa;
}

.pagination-info {
  color: #666;
  font-size: 14px;
}

.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background-color: rgba(0, 0, 0, 0.5);
  display: flex;
  justify-content: center;
  align-items: center;
  z-index: 1000;
}

.modal-content {
  background: white;
  padding: 30px;
  border-radius: 8px;
  width: 90%;
  max-width: 500px;
  max-height: 90vh;
  overflow-y: auto;
}

.modal-content h2 {
  margin: 0 0 20px 0;
  color: #333;
}

.form-group {
  margin-bottom: 20px;
}

.form-group label {
  display: block;
  margin-bottom: 5px;
  font-weight: 500;
  color: #333;
}

.form-actions {
  display: flex;
  gap: 10px;
  justify-content: flex-end;
  margin-top: 30px;
}

.btn {
  padding: 8px 16px;
  border: none;
  border-radius: 4px;
  cursor: pointer;
  font-size: 14px;
  text-decoration: none;
  display: inline-block;
  transition: background-color 0.2s;
}

.btn:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.btn-primary {
  background-color: #007bff;
  color: white;
}

.btn-primary:hover:not(:disabled) {
  background-color: #0056b3;
}

.btn-secondary {
  background-color: #6c757d;
  color: white;
}

.btn-secondary:hover:not(:disabled) {
  background-color: #545b62;
}

.btn-danger {
  background-color: #dc3545;
  color: white;
}

.btn-danger:hover:not(:disabled) {
  background-color: #c82333;
}

.btn-sm {
  padding: 4px 8px;
  font-size: 12px;
}
</style>
