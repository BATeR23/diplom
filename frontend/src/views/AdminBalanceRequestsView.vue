<template>
  <section class="page-section">
    <div class="page-section__content space-y-6">
      <span class="chip w-fit">Админ-панель</span>
      <h1 class="section-heading">Запросы на пополнение баланса</h1>

      <div class="surface-card p-6 space-y-4">
        <div class="flex gap-4">
          <select v-model="statusFilter" @change="fetchRequests" class="form-field-input">
            <option value="">Все статусы</option>
            <option value="pending">Ожидают</option>
            <option value="approved">Одобрены</option>
            <option value="rejected">Отклонены</option>
          </select>
        </div>
      </div>

      <div v-if="loading" class="text-center text-gray-600 py-8">
        Загрузка...
      </div>

      <div v-else-if="requests.length" class="space-y-4">
        <div
          v-for="req in requests"
          :key="req.id"
          class="surface-card p-6 space-y-4"
        >
          <div class="flex items-center justify-between">
            <div>
              <p class="text-lg font-semibold text-gray-900">{{ req.user?.name }}</p>
              <p class="text-sm text-gray-600">{{ req.user?.email }}</p>
            </div>
            <div class="text-right">
              <p class="text-2xl font-semibold text-gray-900">{{ req.amount }} ₽</p>
              <span :class="getStatusClass(req.status)" class="chip text-xs">
                {{ getStatusLabel(req.status) }}
              </span>
            </div>
          </div>

          <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm text-gray-700">
            <p>Создан: {{ formatDate(req.created_at) }}</p>
            <p v-if="req.processed_at">Обработан: {{ formatDate(req.processed_at) }}</p>
            <p v-if="req.admin">Админ: {{ req.admin?.name }}</p>
            <div class="flex gap-2">
              <a
                :href="getReceiptUrl(req.id)"
                target="_blank"
                class="text-blue-600 hover:text-blue-700 underline"
              >
                Просмотреть чек
              </a>
            </div>
          </div>

          <div v-if="req.admin_notes" class="p-3 rounded-lg bg-gray-50 border border-gray-200">
            <p class="text-sm text-gray-700"><strong>Примечание:</strong> {{ req.admin_notes }}</p>
          </div>

          <div v-if="req.status === 'pending'" class="flex gap-2 pt-4 border-t border-gray-200">
            <button
              @click="approveRequest(req)"
              class="primary-btn flex-1"
              :disabled="processing"
            >
              Одобрить
            </button>
            <button
              @click="showRejectModal(req)"
              class="secondary-btn flex-1 text-red-600 border-red-300 hover:bg-red-50"
              :disabled="processing"
            >
              Отклонить
            </button>
          </div>
        </div>
      </div>

      <div v-else class="surface-card p-8 text-gray-600 text-center">
        Запросы не найдены
      </div>
    </div>

    <!-- Reject Modal -->
    <div v-if="rejectingRequest" class="fixed inset-0 bg-black/30 flex items-center justify-center z-50 p-4">
      <div class="surface-card p-8 max-w-md w-full space-y-6">
        <h2 class="text-xl font-semibold text-gray-900">Отклонить запрос</h2>
        <div class="form-field">
          <label>Причина отклонения</label>
          <textarea
            v-model="rejectNotes"
            rows="4"
            placeholder="Укажите причину отклонения запроса..."
          />
        </div>
        <div class="flex gap-4">
          <button
            @click="rejectRequest"
            class="primary-btn flex-1 bg-red-600 hover:bg-red-700"
            :disabled="processing"
          >
            Отклонить
          </button>
          <button
            @click="rejectingRequest = null; rejectNotes = ''"
            class="secondary-btn flex-1"
          >
            Отмена
          </button>
        </div>
      </div>
    </div>
  </section>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import http from '@/helpers/http'
import dayjs from 'dayjs'

const loading = ref(false)
const processing = ref(false)
const requests = ref([])
const statusFilter = ref('')
const rejectingRequest = ref(null)
const rejectNotes = ref('')

const fetchRequests = async () => {
  loading.value = true
  try {
    const params = {}
    if (statusFilter.value) params.status = statusFilter.value
    const { data } = await http.get('/admin/balance-requests', { params })
    requests.value = data.data || data
  } catch (error) {
    alert(error.response?.data?.message || 'Ошибка загрузки запросов')
  } finally {
    loading.value = false
  }
}

const approveRequest = async (req) => {
  if (!confirm(`Одобрить пополнение баланса на ${req.amount} ₽ для пользователя ${req.user?.name}?`)) return

  processing.value = true
  try {
    const response = await http.post(`/admin/balance-requests/${req.id}/approve`)
    // Обновляем список запросов после успешного одобрения
    await fetchRequests()
    alert(response.data?.message || 'Запрос одобрен. Баланс пользователя пополнен.')
  } catch (error) {
    const errorMessage = error.response?.data?.message || 'Ошибка одобрения запроса'
    alert(errorMessage)
    // Обновляем список запросов, чтобы увидеть актуальный статус
    await fetchRequests()
  } finally {
    processing.value = false
  }
}

const showRejectModal = (req) => {
  rejectingRequest.value = req
  rejectNotes.value = ''
}

const rejectRequest = async () => {
  if (!rejectingRequest.value) return

  processing.value = true
  try {
    const response = await http.post(`/admin/balance-requests/${rejectingRequest.value.id}/reject`, {
      notes: rejectNotes.value,
    })
    await fetchRequests()
    rejectingRequest.value = null
    rejectNotes.value = ''
    alert(response.data?.message || 'Запрос отклонен.')
  } catch (error) {
    const errorMessage = error.response?.data?.message || 'Ошибка отклонения запроса'
    alert(errorMessage)
    // Обновляем список запросов, чтобы увидеть актуальный статус
    await fetchRequests()
  } finally {
    processing.value = false
  }
}

const getReceiptUrl = (requestId) => {
  const baseUrl = import.meta.env.VITE_API_BASE_URL || 'http://127.0.0.1:8000/api'
  const token = localStorage.getItem('auth_token')
  // Добавляем токен как query параметр для прямого доступа через браузер
  return `${baseUrl}/admin/balance-requests/${requestId}/receipt${token ? '?token=' + token : ''}`
}

const getStatusLabel = (status) => {
  const labels = {
    pending: 'Ожидает',
    approved: 'Одобрено',
    rejected: 'Отклонено',
  }
  return labels[status] || status
}

const getStatusClass = (status) => {
  const classes = {
    pending: 'bg-yellow-100 text-yellow-700 border-yellow-200',
    approved: 'bg-emerald-100 text-emerald-700 border-emerald-200',
    rejected: 'bg-red-100 text-red-700 border-red-200',
  }
  return classes[status] || ''
}

const formatDate = (value) => dayjs(value).format('D MMM YYYY, HH:mm')

onMounted(() => {
  fetchRequests()
})
</script>


