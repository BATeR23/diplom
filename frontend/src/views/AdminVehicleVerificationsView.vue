<template>
  <section class="page-section">
    <div class="page-section__content space-y-6">
      <span class="chip w-fit">Админ-панель</span>
      <h1 class="section-heading">Подтверждение автомобилей</h1>
      <p class="subheading">
        Проверьте документы и подтвердите или отклоните автомобили водителей.
      </p>

      <div v-if="loading" class="text-center text-gray-600 py-8">
        Загрузка...
      </div>

      <div v-else-if="vehicles.length" class="space-y-4">
        <article
          v-for="vehicle in vehicles"
          :key="vehicle.id"
          class="surface-card p-6 space-y-4"
        >
          <div class="flex items-start justify-between">
            <div class="flex-1">
              <p class="text-lg font-semibold text-gray-900">{{ vehicle.make }} {{ vehicle.model }}</p>
              <p class="text-sm text-gray-600">Владелец: {{ vehicle.owner?.name }} ({{ vehicle.owner?.email }})</p>
              <p class="text-sm text-gray-600">
                {{ vehicle.year }} · {{ vehicle.seats }} мест · {{ vehicle.color || 'цвет не указан' }} · 
                Номер: {{ vehicle.plate_number || '—' }}
              </p>
            </div>
            <div class="text-right">
              <span :class="getStatusClass(vehicle.verification_status)" class="chip text-xs">
                {{ getStatusLabel(vehicle.verification_status) }}
              </span>
            </div>
          </div>

          <div class="grid gap-4 md:grid-cols-2 pt-4 border-t border-gray-200">
            <div>
              <p class="text-sm font-semibold text-gray-900 mb-2">Документ о владении ТС</p>
              <a
                :href="getDocumentUrl(vehicle.id, 'ownership')"
                target="_blank"
                class="text-blue-600 hover:text-blue-700 underline text-sm"
              >
                Просмотреть документ
              </a>
            </div>
            <div>
              <p class="text-sm font-semibold text-gray-900 mb-2">Водительские права</p>
              <a
                :href="getDocumentUrl(vehicle.id, 'license')"
                target="_blank"
                class="text-blue-600 hover:text-blue-700 underline text-sm"
              >
                Просмотреть документ
              </a>
            </div>
          </div>

          <div v-if="vehicle.verification_status === 'pending'" class="flex gap-2 pt-4 border-t border-gray-200">
            <button
              @click="approveVehicle(vehicle)"
              class="primary-btn flex-1"
              :disabled="processing"
            >
              Одобрить
            </button>
            <button
              @click="showRejectModal(vehicle)"
              class="secondary-btn flex-1 text-red-600 border-red-300 hover:bg-red-50"
              :disabled="processing"
            >
              Отклонить
            </button>
          </div>

          <div v-if="vehicle.verification_notes" class="p-3 rounded-lg bg-gray-50 border border-gray-200">
            <p class="text-sm text-gray-700"><strong>Примечание:</strong> {{ vehicle.verification_notes }}</p>
          </div>
        </article>
      </div>

      <div v-else class="surface-card p-8 text-gray-600 text-center">
        Нет автомобилей, ожидающих подтверждения
      </div>
    </div>

    <!-- Reject Modal -->
    <div v-if="rejectingVehicle" class="fixed inset-0 bg-black/30 flex items-center justify-center z-50 p-4">
      <div class="surface-card p-8 max-w-md w-full space-y-6">
        <h2 class="text-xl font-semibold text-gray-900">Отклонить автомобиль</h2>
        <div class="form-field">
          <label>Причина отклонения</label>
          <textarea
            v-model="rejectNotes"
            rows="4"
            placeholder="Укажите причину отклонения..."
          />
        </div>
        <div class="flex gap-4">
          <button
            @click="rejectVehicle"
            class="primary-btn flex-1 bg-red-600 hover:bg-red-700"
            :disabled="processing"
          >
            Отклонить
          </button>
          <button
            @click="rejectingVehicle = null; rejectNotes = ''"
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

const loading = ref(false)
const processing = ref(false)
const vehicles = ref([])
const rejectingVehicle = ref(null)
const rejectNotes = ref('')

const fetchVehicles = async () => {
  loading.value = true
  try {
    const { data } = await http.get('/admin/vehicle-verifications')
    vehicles.value = data.data || data
  } catch (error) {
    alert(error.response?.data?.message || 'Ошибка загрузки автомобилей')
  } finally {
    loading.value = false
  }
}

const approveVehicle = async (vehicle) => {
  if (!confirm(`Одобрить автомобиль ${vehicle.make} ${vehicle.model}?`)) return

  processing.value = true
  try {
    const response = await http.post(`/admin/vehicle-verifications/${vehicle.id}/approve`)
    await fetchVehicles()
    alert(response.data?.message || 'Автомобиль одобрен.')
  } catch (error) {
    alert(error.response?.data?.message || 'Ошибка одобрения автомобиля')
  } finally {
    processing.value = false
  }
}

const showRejectModal = (vehicle) => {
  rejectingVehicle.value = vehicle
  rejectNotes.value = ''
}

const rejectVehicle = async () => {
  if (!rejectingVehicle.value) return

  processing.value = true
  try {
    const response = await http.post(`/admin/vehicle-verifications/${rejectingVehicle.value.id}/reject`, {
      notes: rejectNotes.value,
    })
    await fetchVehicles()
    rejectingVehicle.value = null
    rejectNotes.value = ''
    alert(response.data?.message || 'Автомобиль отклонен.')
  } catch (error) {
    alert(error.response?.data?.message || 'Ошибка отклонения автомобиля')
  } finally {
    processing.value = false
  }
}

const getDocumentUrl = (vehicleId, type) => {
  const baseUrl = import.meta.env.VITE_API_BASE_URL || 'http://127.0.0.1:8000/api'
  const token = localStorage.getItem('auth_token')
  // Добавляем токен как query параметр для прямого доступа через браузер
  return `${baseUrl}/admin/vehicle-verifications/${vehicleId}/document/${type}${token ? '?token=' + token : ''}`
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

onMounted(() => {
  fetchVehicles()
})
</script>

