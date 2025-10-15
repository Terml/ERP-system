<template>
    <div class="production-tasks-page">
        <div class="page-header">
            <button v-if="canCreateTask" @click="showCreateForm = true" class="btn btn-primary">
                Создать задание
            </button>
        </div>

        <div class="tabs">
            <button 
                @click="activeTab = 'tasks'" 
                :class="['tab-button', { active: activeTab === 'tasks' }]"
            >
                Задания ({{ tasks.length }})
            </button>
            <button 
                @click="activeTab = 'orders'" 
                :class="['tab-button', { active: activeTab === 'orders' }]"
            >
                Заказы без заданий ({{ availableOrders.length }})
            </button>
        </div>
        <div v-if="activeTab === 'tasks'" class="filters">
            <div class="filter-group">
                <select v-model="filters.status" @change="fetchTasks" class="form-select">
                    <option value="">Все статусы</option>
                    <option value="wait">Ожидает</option>
                    <option value="in_process">В работе</option>
                    <option value="completed">Завершено</option>
                    <option value="rejected">Отклонено</option>
                </select>
            </div>
            <div class="filter-group">
                <input v-model="filters.search" @input="debouncedSearch" type="text"
                    placeholder="Поиск по заказу или продукту..." class="form-input" />
            </div>
        </div>

        <div v-if="error" class="error-message">
            {{ error }}
        </div>
        <div v-if="activeTab === 'tasks'">
            <div v-if="loading" class="loading">
                Загрузка заданий...
            </div>

            <div v-else class="tasks-table">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Заказ</th>
                        <th>Компания</th>
                        <th>Статус</th>
                        <th>Мастер</th>
                        <th>Создано</th>
                        <th>Действия</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="task in tasks" :key="task.id">
                        <td>{{ task.id }}</td>
                        <td>#{{ task.order_id }}</td>
                        <td>{{ task.order?.company?.name }}</td>
                        <td>
                            <span :class="`status status-${task.status}`">
                                {{ getStatusLabel(task.status) }}
                            </span>
                        </td>
                        <td>{{ task.user?.login || 'Не назначен' }}</td>
                        <td>{{ formatDate(task.created_at) }}</td>
                        <td>
                            <button @click="viewTask(task)" class="btn btn-sm btn-secondary">
                                Просмотр
                            </button>
                        </td>
                    </tr>
                </tbody>
            </table>

            <div v-if="pagination" class="pagination">
                <button @click="fetchTasks(pagination.current_page - 1)" :disabled="pagination.current_page <= 1"
                    class="btn btn-sm">
                    Предыдущая
                </button>
                <span class="pagination-info">
                    Страница {{ pagination.current_page }} из {{ pagination.last_page }}
                </span>
                <button @click="fetchTasks(pagination.current_page + 1)"
                    :disabled="pagination.current_page >= pagination.last_page" class="btn btn-sm">
                    Следующая
                </button>
            </div>
        </div>
        </div>

        <div v-if="activeTab === 'orders'">
            <div v-if="loadingOrders" class="loading">
                Загрузка заказов...
            </div>

            <div v-else-if="availableOrders.length === 0" class="empty-state">
                <p>Нет заказов без заданий.</p>
            </div>

            <div v-else class="orders-grid">
                <div v-for="order in availableOrders" :key="order.id" class="order-card">
                    <div class="order-header">
                        <h3>Заказ #{{ order.id }}</h3>
                        <span class="status-badge status-wait">
                            {{ getStatusText(order.status) }}
                        </span>
                    </div>
                    
                    <div class="order-content">
                        <div class="order-info">
                            <p><strong>Компания:</strong> {{ order.company?.name || 'Не указана' }}</p>
                            <p><strong>Срок:</strong> {{ formatDate(order.deadline) }}</p>
                            <p><strong>Создан:</strong> {{ formatDate(order.created_at) }}</p>
                        </div>
                        
                        <div class="order-actions">
                            <button @click="addTaskToOrder(order)" class="btn btn-primary">
                                Добавить задание
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div v-if="showCreateForm" class="modal-overlay" @click="closeModal">
            <div class="modal-content" @click.stop>
                <div class="modal-header">
                    <h2>Создать производственное задание</h2>
                    <button @click="closeModal" class="btn-close">&times;</button>
                </div>

                <form @submit.prevent="saveTask" class="modal-body">
                    <div class="form-group">
                        <label class="form-label">Заказ *</label>
                        <select v-model="taskForm.order_id" class="form-select" required>
                            <option value="">Выберите заказ</option>
                            <option v-for="order in availableOrders" :key="order.id" :value="order.id">
                                #{{ order.id }} - {{ order.company?.name }}
                            </option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Мастер</label>
                        <select v-model="taskForm.user_id" class="form-select">
                            <option value="">Выберите мастера</option>
                            <option v-for="master in masters" :key="master.id" :value="master.id">
                                {{ master.login }}
                            </option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Что производить *</label>
                        <div class="components-section">
                            <div v-for="(component, index) in taskForm.components" :key="index" class="component-row">
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
                                <input v-model="component.quantity" type="number" min="1" step="1"
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
                        <button type="button" @click="closeModal" class="btn btn-secondary">
                            Отмена
                        </button>
                        <button type="submit" :disabled="saving" class="btn btn-primary">
                            {{ saving ? 'Создание...' : 'Создать' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <div v-if="viewingTask" class="modal-overlay" @click="closeViewModal">
            <div class="modal-content large" @click.stop>
                <div class="modal-header">
                    <h2>Задание #{{ viewingTask.id }}</h2>
                    <button @click="closeViewModal" class="btn-close">&times;</button>
                </div>

                <div class="modal-body">
                    <div class="task-details">
                        <div class="detail-row">
                            <strong>Заказ:</strong> #{{ viewingTask.order_id }}
                        </div>
                        <div class="detail-row">
                            <strong>Компания:</strong> {{ viewingTask.order?.company?.name }}
                        </div>
                        <div class="detail-row">
                            <strong>Статус:</strong>
                            <span :class="`status status-${viewingTask.status}`">
                                {{ getStatusLabel(viewingTask.status) }}
                            </span>
                        </div>
                        <div class="detail-row">
                            <strong>Мастер:</strong> {{ viewingTask.user?.login || 'Не назначен' }}
                        </div>
                    </div>

                    <div v-if="viewingTask.components && viewingTask.components.length > 0" class="components-section">
                        <h3>Компоненты</h3>
                        <table class="components-table">
                            <thead>
                                <tr>
                                    <th>Компонент</th>
                                    <th>Планируемое количество</th>
                                    <th>Использовано</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="component in viewingTask.components" :key="component.id">
                                    <td>{{ component.product?.name }}</td>
                                    <td>{{ component.quantity }} {{ component.product?.unit }}</td>
                                    <td v-if="canEditComponents(viewingTask)">
                                        <input 
                                            v-model.number="component.used_quantity" 
                                            type="number" 
                                            min="0" 
                                            max="10000"
                                            class="form-input small"
                                            :disabled="saving"
                                        />
                                        {{ component.product?.unit }}
                                    </td>
                                    <td v-else>
                                        {{ component.used_quantity || 0 }} {{ component.product?.unit }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                        
                        <div v-if="canEditComponents(viewingTask)" class="form-actions">
                            <button 
                                @click="updateComponents" 
                                :disabled="saving"
                                class="btn btn-primary"
                            >
                                {{ saving ? 'Сохранение...' : 'Сохранить изменения' }}
                            </button>
                        </div>
                    </div>

                    <div class="task-actions">
                        
                        <button 
                            v-if="viewingTask.status === 'wait'"
                            @click="takeTask(viewingTask)" 
                            class="btn btn-primary"
                        >
                            Взять в работу
                        </button>
                        
                        <button 
                            v-if="canSendForInspection(viewingTask)" 
                            @click="sendForInspection(viewingTask)" 
                            class="btn btn-success"
                        >
                            Отправить на проверку
                        </button>
                        
                        <button 
                            v-if="canAcceptTask(viewingTask)" 
                            @click="acceptTask(viewingTask)" 
                            class="btn btn-success"
                        >
                            Принять
                        </button>
                        
                        <button 
                            v-if="canRejectTask(viewingTask)" 
                            @click="rejectTask(viewingTask)" 
                            class="btn btn-danger"
                        >
                            Отклонить
                        </button>
                        
                    </div>
                </div>
            </div>
        </div>

    </div>
</template>

<script>
import axios from 'axios'

export default {
    name: 'ProductionTasks',
    data() {
        return {
            tasks: [],
            pagination: null,
            loading: false,
            loadingOrders: false,
            error: null,
            saving: false,
            showCreateForm: false,
            viewingTask: null,
            availableOrders: [],
            products: [],
            materials: [],
            masters: [],
            user: null,
            activeTab: 'tasks',
            filters: {
                status: '',
                search: ''
            },
            taskForm: {
                order_id: '',
                user_id: '',
                components: []
            },
            searchTimeout: null
        }
    },
    computed: {
        canCreateTask() {
            return this.user?.roles?.some(role => ['admin', 'dispatcher'].includes(role.name))
        }
    },
    watch: {
        activeTab(newTab) {
            if (newTab === 'orders') {
                this.fetchOrdersWithoutTasks()
            }
        }
    },
    mounted() {
        this.loadUser()
        this.fetchTasks()
        this.fetchAvailableOrders()
        this.fetchProducts()
        this.fetchMaterials()
        this.fetchMasters()
        window.addEventListener('openCreateTaskModal', () => {
            this.showCreateForm = true
        })
    },
    methods: {
        async loadUser() {
            try {
                const response = await axios.get('/user')
                this.user = response.data
            } catch (err) {
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
            } catch (err) {
                this.error = 'Ошибка загрузки заданий: ' + (err.response?.data?.message || err.message)
            } finally {
                this.loading = false
            }
        },

        async fetchAvailableOrders() {
            try {
                const response = await axios.get('/orders?status=wait')
                this.availableOrders = response.data.data || []
            } catch (err) {
            }
        },

        async fetchOrdersWithoutTasks() {
            try {
                this.loadingOrders = true
                const response = await axios.get('/orders?status=wait')
                this.availableOrders = response.data.data || []
            } catch (err) {
                this.error = 'Ошибка загрузки заказов: ' + (err.response?.data?.message || err.message)
            } finally {
                this.loadingOrders = false
            }
        },

        async fetchProducts() {
            try {
                const response = await axios.get('/products?type=product')
                this.products = response.data.data || []
            } catch (err) {
            }
        },

        async fetchMaterials() {
            try {
                const response = await axios.get('/products?type=material')
                this.materials = response.data.data || []
            } catch (err) {
            }
        },

        async fetchMasters() {
            try {
                const response = await axios.get('/users?role=master')
                this.masters = response.data.data || []
            } catch (err) {
            }
        },

        debouncedSearch() {
            clearTimeout(this.searchTimeout)
            this.searchTimeout = setTimeout(() => {
                this.fetchTasks()
            }, 500)
        },

        getStatusLabel(status) {
            const labels = {
                'wait': 'Ожидает',
                'in_process': 'В работе',
                'checking': 'На проверке',
                'completed': 'Завершено',
                'rejected': 'Отклонено'
            }
            return labels[status] || status
        },

        formatDate(dateString) {
            return new Date(dateString).toLocaleDateString('ru-RU')
        },

        canTakeTask(task) {
            return this.user.roles?.some(role => ['admin', 'master'].includes(role.name)) &&
                task.status === 'wait'
        },

        canAcceptTask(task) {
            return task.status === 'checking'
        },

        canRejectTask(task) {
            return task.status === 'checking'
        },

        canEditComponents(task) {
            return task.status === 'in_process'
        },

        canSendForInspection(task) {
            return this.user.roles?.some(role => ['admin', 'master'].includes(role.name)) &&
                task.status === 'in_process' &&
                task.user_id === this.user.id
        },

        async takeTask(task) {
            if (!confirm(`Взять задание #${task.id} в работу?`)) {
                return
            }

            try {
                this.error = null
                await axios.post(`/production-tasks/${task.id}/take`)
                this.fetchTasks()
                this.closeViewModal()
            } catch (err) {
                this.error = 'Ошибка взятия задания: ' + (err.response?.data?.message || err.message)
            }
        },

        async sendForInspection(task) {
            if (!confirm(`Отправить задание #${task.id} на проверку?`)) {
                return
            }

            try {
                this.error = null
                await axios.post(`/production-tasks/${task.id}/send-for-inspection`)
                this.fetchTasks()
                this.closeViewModal()
            } catch (err) {
                this.error = 'Ошибка отправки на проверку: ' + (err.response?.data?.message || err.message)
            }
        },


        async acceptTask(task) {
            if (!confirm(`Принять задание #${task.id}?`)) {
                return
            }

            try {
                this.error = null
                await axios.post(`/production-tasks/${task.id}/accept-with-completion`, {
                    decision: 'accepted',
                    otk_user_id: this.user.id
                })
                this.fetchTasks()
            } catch (err) {
                this.error = 'Ошибка принятия задания: ' + (err.response?.data?.message || err.message)
            }
        },

        async rejectTask(task) {
            if (!confirm(`Отклонить задание #${task.id}?`)) {
                return
            }

            try {
                this.error = null
                await axios.post(`/production-tasks/${task.id}/reject`, {
                    otk_user_id: this.user.id
                })
                this.fetchTasks()
            } catch (err) {
                this.error = 'Ошибка отклонения задания: ' + (err.response?.data?.message || err.message)
            }
        },

        viewTask(task) {
            this.viewingTask = task
        },


        async saveTask() {
            try {
                this.saving = true
                this.error = null

                if (this.taskForm.components.length === 0) {
                    this.error = 'Необходимо добавить хотя бы один продукт или материал'
                    return
                }

                const taskData = {
                    order_id: this.taskForm.order_id,
                    user_id: this.taskForm.user_id || null,
                    components: this.taskForm.components
                        .filter(c => c.product_id && c.quantity)
                        .map(c => ({
                            product_id: c.product_id,
                            quantity: parseInt(c.quantity) || 0
                        }))
                        .filter(c => c.quantity > 0)
                }

                if (taskData.components.length === 0) {
                    this.error = 'Необходимо указать количество для всех продуктов/материалов'
                    return
                }

                await axios.post('/production-tasks/with-components', taskData)
                this.closeModal()
                this.fetchTasks()
            } catch (err) {
                const errorMessage = err.response?.data?.error || err.response?.data?.message || err.message
                this.error = 'Ошибка создания задания: ' + errorMessage
            } finally {
                this.saving = false
            }
        },


        addComponent() {
            this.taskForm.components.push({
                product_id: '',
                quantity: ''
            })
        },

        removeComponent(index) {
            this.taskForm.components.splice(index, 1)
        },

        closeModal() {
            this.showCreateForm = false
            this.taskForm = {
                order_id: '',
                user_id: '',
                components: []
            }
        },

        closeViewModal() {
            this.viewingTask = null
        },

        async updateComponents() {
            try {
                this.saving = true
                this.error = null

                const componentsData = this.viewingTask.components.map(component => ({
                    id: component.id,
                    used_quantity: component.used_quantity || 0
                }))

                await axios.put(`/production-tasks/${this.viewingTask.id}/components`, {
                    components: componentsData
                })

                this.fetchTasks()
            } catch (err) {
                this.error = 'Ошибка обновления компонентов: ' + (err.response?.data?.message || err.message)
            } finally {
                this.saving = false
            }
        },

        addTaskToOrder(order) {
            this.taskForm.order_id = order.id
            this.taskForm.components = []
            this.addComponent()
            this.showCreateForm = true
        },

        getStatusText(status) {
            const statuses = {
                wait: 'Ожидание',
                in_process: 'В процессе',
                completed: 'Завершен',
                rejected: 'Отклонен'
            }
            return statuses[status] || status
        },

        formatDate(dateString) {
            return new Date(dateString).toLocaleDateString('ru-RU')
        },

    }
}
</script>