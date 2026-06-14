<template>
  <section class="page-section">
    <div class="page-section__content space-y-6">
      <span class="chip w-fit">Управление поездками</span>
      <h1 class="section-heading">Управление поездками</h1>
      <p class="subheading">
        Изменяйте статус и управляйте своими поездками.
      </p>

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
              <p class="text-sm uppercase tracking-[0.3em] text-gray-600">
                {{ ride.origin_city }} → {{ ride.destination_city }}
              </p>
              <p class="text-2xl font-semibold text-gray-900 mt-2">
                {{ formatDate(ride.departure_time) }}
              </p>
              <p class="text-gray-700 mt-2">
                Статус: <span class="font-semibold">{{ getStatusLabel(ride.status) }}</span> · 
                Свободно мест: {{ ride.seats_available }}/{{ ride.seats_total }}
              </p>
              <p class="text-gray-700">Цена: {{ ride.price }} ₽ за место</p>
            </div>
            <div class="flex flex-col gap-2 ml-4">
              <select
                v-if="ride.status !== 'completed'"
                :value="ride.status"
                @change="updateStatus(ride.id, $event.target.value)"
                class="form-field-input text-sm"
              >
                <option value="pending">Ожидает</option>
                <option value="published">Опубликована</option>
                <option value="cancelled">Отменена</option>
              </select>
              <button
                v-if="ride.status === 'published' && hasAcceptedBookings(ride)"
                class="primary-btn text-sm"
                @click="completeRide(ride.id)"
                :disabled="completing"
              >
                {{ completing ? 'Завершение...' : 'Завершить поездку' }}
              </button>
              <RouterLink 
                :to="{ name: 'rides.show', params: { id: ride.id } }" 
                class="secondary-btn text-sm text-center"
              >
                Детали
              </RouterLink>
            </div>
          </div>
        </article>
      </div>

      <div v-else class="surface-card p-8 text-center">
        <p class="text-gray-600">Создайте поездку, чтобы начать получать бронирования.</p>
        <RouterLink :to="{ name: 'rides.create' }" class="primary-btn mt-4 inline-block">
          Создать поездку
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
const completing = ref(false)

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

const hasAcceptedBookings = (ride) => {
  return ride.bookings && ride.bookings.some(b => b.status === 'accepted')
}

const updateStatus = async (rideId, status) => {
  try {
    await http.patch(`/rides/${rideId}`, { status })
    await fetchRides()
  } catch (error) {
    alert(error.response?.data?.message || 'Ошибка обновления статуса')
  }
}

const completeRide = async (rideId) => {
  if (!confirm('Вы уверены, что хотите завершить поездку? Это списает баланс у всех принятых пассажиров.')) {
    return
  }

  completing.value = true
  try {
    await http.post(`/rides/${rideId}/complete`)
    await fetchRides()
    alert('Поездка успешно завершена! Баланс списан у всех пассажиров.')
  } catch (error) {
    alert(error.response?.data?.message || 'Ошибка завершения поездки')
  } finally {
    completing.value = false
  }
}

const fetchRides = async () => {
  loading.value = true
  try {
    const { data } = await http.get('/rides')
    rides.value = (data.data || data).map(ride => ({
      ...ride,
      bookings: ride.bookings || []
    }))
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


