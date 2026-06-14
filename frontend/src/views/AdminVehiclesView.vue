<template>
  <section class="page-section">
    <div class="page-section__content space-y-6">
      <span class="chip w-fit">Админ-панель</span>
      <h1 class="section-heading">Управление автомобилями</h1>

      <div v-if="loading" class="surface-card p-8 text-center text-gray-600">
        Загрузка...
      </div>

      <div v-else-if="vehicles.length" class="space-y-4">
        <div
          v-for="vehicle in vehicles"
          :key="vehicle.id"
          class="surface-card p-6 space-y-4"
        >
          <div class="flex items-center justify-between">
            <div>
              <p class="text-lg font-semibold text-gray-900">{{ vehicle.make }} {{ vehicle.model }}</p>
              <p class="text-sm text-gray-700">Владелец: {{ vehicle.owner?.name }} ({{ vehicle.owner?.email }})</p>
            </div>
            <button @click="deleteVehicle(vehicle)" class="secondary-btn text-xs px-3 py-1 text-red-600 border-red-300 hover:bg-red-50">Удалить</button>
          </div>
          <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm text-gray-700">
            <p>Цвет: {{ vehicle.color }}</p>
            <p>Гос. номер: {{ vehicle.plate_number }}</p>
            <p>Класс: {{ vehicle.comfort_class }}</p>
            <p>Создан: {{ formatDate(vehicle.created_at) }}</p>
          </div>
        </div>
      </div>

      <div v-else class="surface-card p-8 text-gray-600 text-center">
        Автомобили не найдены
      </div>
    </div>
  </section>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import http from '@/helpers/http'
import dayjs from 'dayjs'

const loading = ref(false)
const vehicles = ref([])

const fetchVehicles = async () => {
  loading.value = true
  try {
    const { data } = await http.get('/admin/vehicles')
    vehicles.value = data.data || data
  } catch (error) {
    alert(error.response?.data?.message || 'Ошибка загрузки автомобилей')
  } finally {
    loading.value = false
  }
}

const deleteVehicle = async (vehicle) => {
  if (!confirm(`Вы уверены, что хотите удалить автомобиль ${vehicle.brand} ${vehicle.model}?`)) return
  try {
    await http.delete(`/admin/vehicles/${vehicle.id}`)
    await fetchVehicles()
  } catch (error) {
    alert(error.response?.data?.message || 'Ошибка удаления автомобиля')
  }
}

const formatDate = (value) => dayjs(value).format('D MMM YYYY')

onMounted(() => {
  fetchVehicles()
})
</script>

