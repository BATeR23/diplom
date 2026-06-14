<template>
  <section class="page-section">
    <div class="page-section__content space-y-6">
      <span class="chip w-fit">Заявки пассажиров</span>
      <h1 class="section-heading">Заявки на бронирование</h1>
      <p class="subheading">
        Управляйте заявками от пассажиров на ваши поездки.
      </p>

      <div v-if="loading" class="text-center text-gray-600 py-8">
        Загрузка...
      </div>

      <div v-else-if="bookings.length" class="space-y-4">
        <article
          v-for="booking in bookings"
          :key="booking.id"
          class="surface-card p-6"
        >
          <div class="flex items-start justify-between">
            <div class="flex-1">
              <p class="text-sm uppercase tracking-[0.3em] text-gray-600">
                {{ booking.ride?.origin_city }} → {{ booking.ride?.destination_city }}
              </p>
              <p class="text-xl font-semibold text-gray-900 mt-2">
                {{ booking.passenger?.name }}
              </p>
              <p class="text-gray-700 mt-2">
                Мест: {{ booking.seats_requested }} · 
                Дата: {{ formatDate(booking.ride?.departure_time) }}
              </p>
              <p v-if="booking.comment" class="text-gray-600 mt-2 italic">
                Комментарий: {{ booking.comment }}
              </p>
              <p class="text-sm text-gray-600 mt-2">
                Статус: <span class="font-semibold">{{ getStatusLabel(booking.status) }}</span>
              </p>
            </div>
            <div v-if="booking.status === 'pending'" class="flex flex-col gap-2 ml-4">
              <button
                class="secondary-btn text-sm text-emerald-700 border-emerald-300 hover:bg-emerald-50"
                type="button"
                @click="updateStatus(booking.id, 'accepted')"
              >
                Принять
              </button>
              <button
                class="secondary-btn text-sm text-rose-700 border-rose-300 hover:bg-rose-50"
                type="button"
                @click="updateStatus(booking.id, 'rejected')"
              >
                Отклонить
              </button>
            </div>
          </div>
        </article>
      </div>

      <div v-else class="surface-card p-8 text-center">
        <p class="text-gray-600">Нет новых заявок от пассажиров.</p>
      </div>
    </div>
  </section>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import dayjs from 'dayjs'
import { useBookingsStore } from '@/stores/bookings'

const bookingsStore = useBookingsStore()
const loading = ref(false)
const bookings = ref([])

const formatDate = (value) => dayjs(value).format('D MMM, HH:mm')

const getStatusLabel = (status) => {
  const labels = {
    pending: 'Ожидает',
    accepted: 'Принято',
    rejected: 'Отклонено',
    cancelled: 'Отменено',
    completed: 'Завершено'
  }
  return labels[status] || status
}

const updateStatus = async (bookingId, status) => {
  try {
    await bookingsStore.updateStatus(bookingId, status)
    await fetchBookings()
  } catch (error) {
    alert(error.response?.data?.message || 'Ошибка обновления статуса')
  }
}

const fetchBookings = async () => {
  loading.value = true
  try {
    await bookingsStore.fetchDriverBookings()
    bookings.value = bookingsStore.asDriver
  } catch (error) {
    console.error('Error fetching bookings:', error)
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  fetchBookings()
})
</script>











