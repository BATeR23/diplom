<template>
  <section class="page-section">
    <div class="page-section__content space-y-6">
      <span class="chip w-fit">Админ-панель</span>
      <h1 class="section-heading">Управление поездками</h1>

      <div class="surface-card p-6 space-y-4">
        <div class="flex gap-4">
          <select v-model="statusFilter" @change="fetchRides" class="form-field-input">
            <option value="">Все статусы</option>
            <option value="pending">Ожидает</option>
            <option value="published">Опубликована</option>
            <option value="completed">Завершена</option>
            <option value="cancelled">Отменена</option>
          </select>
        </div>
      </div>

      <div v-if="loading" class="surface-card p-8 text-center text-gray-600">
        Загрузка...
      </div>

      <div v-else-if="rides.length" class="space-y-4">
        <div
          v-for="ride in rides"
          :key="ride.id"
          class="surface-card p-6 space-y-4"
        >
          <div class="flex items-center justify-between">
            <div>
              <p class="text-lg font-semibold text-gray-900">{{ ride.origin_city }} → {{ ride.destination_city }}</p>
              <p class="text-sm text-gray-700">Водитель: {{ ride.driver?.name }} · {{ formatDate(ride.departure_time) }}</p>
            </div>
            <div class="flex items-center gap-2">
              <select
                :value="ride.status"
                @change="updateRideStatus(ride.id, $event.target.value)"
                class="form-field-input text-xs"
              >
                <option value="pending">Ожидает</option>
                <option value="published">Опубликована</option>
                <option value="completed">Завершена</option>
                <option value="cancelled">Отменена</option>
              </select>
              <button @click="deleteRide(ride)" class="secondary-btn text-xs px-3 py-1 text-red-600 border-red-300 hover:bg-red-50">Удалить</button>
            </div>
          </div>
          <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm text-gray-700">
            <p>Мест: {{ ride.seats_available }}/{{ ride.seats_total }}</p>
            <p>Цена: {{ ride.price }} ₽</p>
            <p>Авто: {{ ride.vehicle?.make }} {{ ride.vehicle?.model }}</p>
            <p>Создана: {{ formatDate(ride.created_at) }}</p>
          </div>
        </div>
      </div>

      <div v-else class="surface-card p-8 text-gray-600 text-center">
        Поездки не найдены
      </div>
    </div>
  </section>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import http from '@/helpers/http'
import dayjs from 'dayjs'

const loading = ref(false)
const rides = ref([])
const statusFilter = ref('')

const fetchRides = async () => {
  loading.value = true
  try {
    const params = {}
    if (statusFilter.value) params.status = statusFilter.value
    const { data } = await http.get('/admin/rides', { params })
    rides.value = data.data || data
  } catch (error) {
    alert(error.response?.data?.message || 'Ошибка загрузки поездок')
  } finally {
    loading.value = false
  }
}

const updateRideStatus = async (rideId, status) => {
  try {
    await http.patch(`/admin/rides/${rideId}`, { status })
    await fetchRides()
  } catch (error) {
    alert(error.response?.data?.message || 'Ошибка обновления статуса')
  }
}

const deleteRide = async (ride) => {
  if (!confirm(`Вы уверены, что хотите удалить поездку ${ride.origin_city} → ${ride.destination_city}?`)) return
  try {
    await http.delete(`/admin/rides/${ride.id}`)
    await fetchRides()
  } catch (error) {
    alert(error.response?.data?.message || 'Ошибка удаления поездки')
  }
}

const formatDate = (value) => dayjs(value).format('D MMM YYYY, HH:mm')

onMounted(() => {
  fetchRides()
})
</script>

