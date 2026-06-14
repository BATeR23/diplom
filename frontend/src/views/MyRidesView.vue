<template>
  <section class="page-section">
    <div class="page-section__content space-y-6">
      <div class="flex items-center justify-between">
        <div>
          <span class="chip w-fit">Мои поездки</span>
          <h1 class="section-heading">Поездки водителя</h1>
        </div>
        <RouterLink class="primary-btn" :to="{ name: 'rides.create' }">Новая поездка</RouterLink>
      </div>

      <div v-if="loading" class="text-center text-gray-600 py-8">
        Загрузка...
      </div>

      <div v-else-if="rides.length" class="space-y-4">
        <article
          v-for="ride in rides"
          :key="ride.id"
          class="surface-card p-6"
        >
          <div class="flex items-start justify-between">
            <div class="flex-1">
              <p class="text-sm uppercase tracking-[0.3em] text-gray-600">{{ ride.origin_city }} → {{ ride.destination_city }}</p>
              <p class="text-2xl font-semibold text-gray-900 mt-2">{{ formatDate(ride.departure_time) }}</p>
              <p class="text-gray-700 mt-2">Статус: <span class="font-semibold">{{ getStatusLabel(ride.status) }}</span> · Мест: {{ ride.seats_available }}/{{ ride.seats_total }}</p>
              <p class="text-gray-700">Цена: {{ ride.price }} ₽ за место</p>
            </div>
            <div class="flex gap-2 ml-4">
              <RouterLink :to="{ name: 'rides.show', params: { id: ride.id } }" class="secondary-btn text-sm">
                Детали
              </RouterLink>
            </div>
          </div>
        </article>
      </div>

      <div v-else class="surface-card p-8 text-center">
        <p class="text-gray-600">Вы ещё не создали ни одной поездки.</p>
        <RouterLink :to="{ name: 'rides.create' }" class="primary-btn mt-4 inline-block">
          Создать первую поездку
        </RouterLink>
      </div>
    </div>
  </section>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { RouterLink } from 'vue-router'
import dayjs from 'dayjs'
import http from '@/helpers/http'

const loading = ref(false)
const rides = ref([])

const formatDate = (value) => dayjs(value).format('D MMM, HH:mm')

const getStatusLabel = (status) => {
  const labels = {
    pending: 'Ожидает',
    published: 'Опубликована',
    completed: 'Завершена',
    cancelled: 'Отменена'
  }
  return labels[status] || status
}

const fetchRides = async () => {
  loading.value = true
  try {
    const { data } = await http.get('/rides', { params: { driver_id: 'me' } })
    rides.value = data.data || data
  } catch (error) {
    console.error('Error fetching rides:', error)
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  fetchRides()
})
</script>


