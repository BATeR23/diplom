<template>
  <section class="page-section">
    <div class="page-section__content space-y-6">
      <div class="flex items-center justify-between">
        <div>
          <span class="chip w-fit">Админ-панель</span>
          <h1 class="section-heading">Управление пользователями</h1>
        </div>
        <button @click="showCreateModal = true" class="primary-btn">Создать пользователя</button>
      </div>

      <div class="surface-card p-6 space-y-4">
        <div class="flex gap-4">
          <input
            v-model="searchQuery"
            type="text"
            placeholder="Поиск по имени или email..."
            class="flex-1 form-field-input"
            @input="fetchUsers"
          />
          <select v-model="roleFilter" @change="fetchUsers" class="form-field-input">
            <option value="">Все роли</option>
            <option value="passenger">Пассажир</option>
            <option value="driver">Водитель</option>
            <option value="admin">Админ</option>
          </select>
        </div>
      </div>

      <div v-if="loading" class="surface-card p-8 text-center text-gray-600">
        Загрузка...
      </div>

      <div v-else-if="users.length" class="space-y-4">
        <div
          v-for="user in users"
          :key="user.id"
          class="surface-card p-6 space-y-4"
        >
          <div class="flex items-center justify-between">
            <div class="flex items-center gap-4">
              <div class="h-12 w-12 rounded-xl bg-blue-100 grid place-items-center text-lg font-semibold text-blue-700">
                {{ user.name?.slice(0, 1) }}
              </div>
              <div>
                <p class="text-lg font-semibold text-gray-900">{{ user.name }}</p>
                <p class="text-sm text-gray-600">{{ user.email }}</p>
              </div>
            </div>
            <div class="flex items-center gap-2">
              <span class="chip">{{ user.role }}</span>
              <button @click="editUser(user)" class="secondary-btn text-xs px-3 py-1">Редактировать</button>
              <button @click="deleteUser(user)" class="secondary-btn text-xs px-3 py-1 text-red-600 border-red-300 hover:bg-red-50">Удалить</button>
            </div>
          </div>
          <div class="grid grid-cols-3 gap-4 text-sm text-gray-700">
            <p>Поездок: {{ user.rides_as_driver_count ?? 0 }}</p>
            <p>Бронирований: {{ user.bookings_count ?? 0 }}</p>
            <p>Создан: {{ formatDate(user.created_at) }}</p>
          </div>
        </div>
      </div>

      <div v-else class="surface-card p-8 text-gray-600 text-center">
        Пользователи не найдены
      </div>
    </div>

    <!-- Create/Edit Modal -->
    <div v-if="showCreateModal || editingUser" class="fixed inset-0 bg-black/30 flex items-center justify-center z-50">
      <div class="surface-card p-8 max-w-md w-full mx-4 space-y-6">
        <h2 class="text-xl font-semibold text-gray-900">{{ editingUser ? 'Редактировать пользователя' : 'Создать пользователя' }}</h2>
        <form @submit.prevent="saveUser" class="space-y-4">
          <div class="form-field">
            <label>Имя</label>
            <input v-model="userForm.name" type="text" required />
          </div>
          <div class="form-field">
            <label>Email</label>
            <input v-model="userForm.email" type="email" required />
          </div>
          <div class="form-field">
            <label>Пароль{{ editingUser ? ' (оставьте пустым, чтобы не менять)' : '' }}</label>
            <input v-model="userForm.password" type="password" :required="!editingUser" />
          </div>
          <div class="form-field">
            <label>Роль</label>
            <select v-model="userForm.role" required>
              <option value="passenger">Пассажир</option>
              <option value="driver">Водитель</option>
              <option value="admin">Админ</option>
            </select>
          </div>
          <div class="flex gap-4">
            <button type="submit" class="primary-btn flex-1" :disabled="saving">
              {{ saving ? 'Сохранение...' : 'Сохранить' }}
            </button>
            <button type="button" @click="closeModal" class="secondary-btn flex-1">Отмена</button>
          </div>
        </form>
      </div>
    </div>
  </section>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import http from '@/helpers/http'
import dayjs from 'dayjs'

const loading = ref(false)
const users = ref([])
const searchQuery = ref('')
const roleFilter = ref('')
const showCreateModal = ref(false)
const editingUser = ref(null)
const saving = ref(false)

const userForm = reactive({
  name: '',
  email: '',
  password: '',
  role: 'passenger',
})

const fetchUsers = async () => {
  loading.value = true
  try {
    const params = {}
    if (searchQuery.value) params.search = searchQuery.value
    if (roleFilter.value) params.role = roleFilter.value
    const { data } = await http.get('/admin/users', { params })
    users.value = data.data || data
  } catch (error) {
    alert(error.response?.data?.message || 'Ошибка загрузки пользователей')
  } finally {
    loading.value = false
  }
}

const editUser = (user) => {
  editingUser.value = user
  userForm.name = user.name
  userForm.email = user.email
  userForm.password = ''
  userForm.role = user.role
}

const deleteUser = async (user) => {
  if (!confirm(`Вы уверены, что хотите удалить пользователя ${user.name}?`)) return
  try {
    await http.delete(`/admin/users/${user.id}`)
    await fetchUsers()
  } catch (error) {
    alert(error.response?.data?.message || 'Ошибка удаления пользователя')
  }
}

const saveUser = async () => {
  saving.value = true
  try {
    if (editingUser.value) {
      await http.put(`/admin/users/${editingUser.value.id}`, userForm)
    } else {
      await http.post('/admin/users', userForm)
    }
    closeModal()
    await fetchUsers()
  } catch (error) {
    alert(error.response?.data?.message || 'Ошибка сохранения пользователя')
  } finally {
    saving.value = false
  }
}

const closeModal = () => {
  showCreateModal.value = false
  editingUser.value = null
  userForm.name = ''
  userForm.email = ''
  userForm.password = ''
  userForm.role = 'passenger'
}

const formatDate = (value) => dayjs(value).format('D MMM YYYY')

onMounted(() => {
  fetchUsers()
})
</script>

