<template>
  <section class="page-section">
    <div class="page-section__content space-y-6">
      <span class="chip w-fit">Мои бронирования</span>
      <h1 class="section-heading">Активные бронирования</h1>
      <p class="subheading">
        Ваши бронирования поездок как пассажир.
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
              <p class="text-2xl font-semibold text-gray-900 mt-2">
                {{ formatDate(booking.ride?.departure_time) }}
              </p>
              <p class="text-gray-700 mt-2">
                Статус: <span class="font-semibold">{{ getStatusLabel(booking.status) }}</span> · 
                Мест: {{ booking.seats_requested }} · 
                Цена: {{ booking.price_total }} ₽
              </p>
              <div v-if="booking.status === 'accepted' || booking.status === 'completed'" class="mt-4 pt-4 border-t border-gray-200 space-y-4">
                <div v-if="booking.status === 'accepted'">
                  <ChatWindow
                    v-if="booking.ride_id && booking.ride?.driver"
                    :ride-id="booking.ride_id"
                    :booking-id="booking.id"
                    :other-user="booking.ride.driver"
                    :ride="booking.ride"
                  />
                </div>
                <div v-if="booking.status === 'completed' && booking.ride_id">
                  <ReviewForm 
                    :ride-id="booking.ride_id" 
                    :driver-id="booking.ride?.driver_id"
                    :booking-id="booking.id"
                  />
                </div>
              </div>
            </div>
            <div class="flex gap-2 ml-4">
              <RouterLink 
                v-if="booking.ride_id" 
                :to="{ name: 'rides.show', params: { id: booking.ride_id } }" 
                class="secondary-btn text-sm"
              >
                Детали
              </RouterLink>
            </div>
          </div>
        </article>
      </div>

      <div v-else class="surface-card p-8 text-center">
        <p class="text-gray-600">У вас пока нет активных бронирований.</p>
        <RouterLink :to="{ name: 'rides.search' }" class="primary-btn mt-4 inline-block">
          Найти поездку
        </RouterLink>
      </div>
    </div>
  </section>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { RouterLink } from 'vue-router'
import dayjs from 'dayjs'
import { useBookingsStore } from '@/stores/bookings'
import ReviewForm from '@/components/ReviewForm.vue'
import ChatWindow from '@/components/ChatWindow.vue'

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

const fetchBookings = async () => {
  loading.value = true
  try {
    await bookingsStore.fetchPassengerBookings()
    bookings.value = bookingsStore.asPassenger
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


