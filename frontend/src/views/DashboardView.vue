<template>
  <section class="page-section">
    <div class="page-section__content space-y-6">
      <span class="chip w-fit">Личный кабинет</span>
      <h1 class="section-heading">Ваши поездки и заявки</h1>
      <p class="subheading">
        Управляйте поездками как водитель, отслеживайте бронирования как пассажир и держите высокий рейтинг.
      </p>
      <div class="space-y-6">
        <div class="surface-card space-y-4 p-6">
          <div class="flex items-center justify-between">
            <h2 class="text-lg font-semibold text-gray-900">Мои поездки (водитель)</h2>
            <RouterLink class="secondary-btn" :to="{ name: 'rides.create' }">Новая поездка</RouterLink>
          </div>
          <div v-if="driverRides.length" class="space-y-3">
            <article
              v-for="ride in driverRides"
              :key="ride.id"
              class="rounded-2xl border border-gray-200 bg-gray-50 p-4 text-sm"
            >
              <p class="text-gray-600 uppercase tracking-[0.3em] text-xs">{{ ride.origin_city }} → {{ ride.destination_city }}</p>
              <p class="text-lg font-semibold text-gray-900">{{ formatDate(ride.departure_time) }}</p>
              <p class="text-gray-700">Статус: {{ ride.status }} · Мест: {{ ride.seats_available }}/{{ ride.seats_total }}</p>
            </article>
          </div>
          <p v-else class="text-sm text-gray-600">Вы ещё не создали ни одной поездки.</p>
        </div>
        
        <div class="surface-card space-y-4 p-6">
          <div class="flex items-center justify-between">
            <h2 class="text-lg font-semibold text-gray-900">Мои бронирования</h2>
          </div>
          <div v-if="bookingsStore.asPassenger.length" class="space-y-3">
            <article
              v-for="booking in bookingsStore.asPassenger"
              :key="booking.id"
              class="rounded-2xl border border-gray-200 bg-gray-50 p-4 text-sm"
            >
              <p class="text-gray-600 uppercase tracking-[0.3em] text-xs">
                {{ booking.ride.origin_city }} → {{ booking.ride.destination_city }}
              </p>
              <p class="text-lg font-semibold text-gray-900">
                {{ formatDate(booking.ride.departure_time) }} · {{ booking.price_total }} ₽
              </p>
              <p class="text-gray-700">Статус: {{ booking.status }} · Мест: {{ booking.seats_requested }}</p>
            </article>
          </div>
          <p v-else class="text-sm text-gray-600">У вас пока нет активных бронирований.</p>
        </div>
        
        <div class="surface-card space-y-4 p-6">
          <div class="flex items-center justify-between">
            <h2 class="text-lg font-semibold text-gray-900">Заявки пассажиров</h2>
          </div>
          <div v-if="bookingsStore.asDriver.length" class="space-y-3">
            <article
              v-for="booking in bookingsStore.asDriver"
              :key="booking.id"
              class="rounded-2xl border border-gray-200 bg-gray-50 p-4 text-sm text-gray-700"
            >
              <p class="text-xs uppercase tracking-[0.3em] text-gray-600">{{ booking.ride.origin_city }} → {{ booking.ride.destination_city }}</p>
              <p class="text-lg font-semibold text-gray-900">
                {{ booking.passenger.name }} · {{ booking.seats_requested }} мест · {{ formatDate(booking.ride.departure_time) }}
              </p>
              <p class="text-gray-700">Комментарий: {{ booking.notes || '—' }}</p>
              <div class="mt-3 flex flex-wrap gap-3">
                <button
                  class="secondary-btn text-emerald-700 border-emerald-300 hover:bg-emerald-50"
                  type="button"
                  @click="bookingsStore.updateStatus(booking.id, 'accepted')"
                >
                  Принять
                </button>
                <button
                  class="secondary-btn text-rose-700 border-rose-300 hover:bg-rose-50"
                  type="button"
                  @click="bookingsStore.updateStatus(booking.id, 'rejected')"
                >
                  Отклонить
                </button>
              </div>
            </article>
          </div>
          <p v-else class="text-sm text-gray-600">Нет новых заявок от пассажиров.</p>
        </div>
        
        <div class="surface-card space-y-4 p-6">
          <div class="flex items-center justify-between">
            <h2 class="text-lg font-semibold text-gray-900">Управление поездками</h2>
          </div>
          <div v-if="driverRides.length" class="space-y-3">
            <article
              v-for="ride in driverRides"
              :key="ride.id"
              class="rounded-2xl border border-gray-200 bg-gray-50 p-4 text-sm text-gray-700"
            >
              <p class="text-xs uppercase tracking-[0.3em] text-gray-600">{{ ride.origin_city }} → {{ ride.destination_city }}</p>
              <p class="text-lg font-semibold text-gray-900">{{ formatDate(ride.departure_time) }}</p>
              <p class="text-gray-700">Статус: {{ ride.status }} · Свободно {{ ride.seats_available }}</p>
              <div class="mt-3 flex flex-wrap gap-3">
                <button class="secondary-btn" type="button" @click="updateRideStatus(ride.id, 'published')">
                  Опубликовать
                </button>
                <button class="secondary-btn text-rose-700 border-rose-300 hover:bg-rose-50" type="button" @click="updateRideStatus(ride.id, 'cancelled')">
                  Отменить
                </button>
              </div>
            </article>
          </div>
          <p v-else class="text-sm text-gray-600">Создайте поездку, чтобы начать получать бронирования.</p>
        </div>
      </div>
    </div>
    <div class="page-section__aside">
      <div class="surface-card space-y-4 p-8">
        <div class="flex items-center gap-4">
          <div class="h-14 w-14 rounded-2xl bg-blue-100 grid place-items-center text-xl font-semibold text-blue-700">
            {{ auth.user?.name?.slice(0, 1) }}
          </div>
          <div>
            <p class="text-lg font-semibold text-gray-900">{{ auth.user?.name }}</p>
            <p class="text-sm text-gray-600">{{ auth.user?.email }}</p>
          </div>
        </div>
        <div class="rounded-2xl border border-gray-200 bg-gray-50 p-4 text-sm text-gray-700">
          <p>Рейтинг: {{ auth.user?.rating_average ?? '—' }}</p>
          <p>Завершено поездок: {{ auth.user?.rides_completed ?? 0 }}</p>
          <p>Роль: {{ auth.user?.role === 'driver' ? 'Водитель' : 'Пассажир' }}</p>
        </div>
        <button class="secondary-btn w-full" type="button" @click="refreshData">
          Обновить данные
        </button>
      </div>
    </div>
  </section>
</template>

<script setup>
import { onMounted, ref } from 'vue'
import { RouterLink } from 'vue-router'
import dayjs from 'dayjs'
import http from '@/helpers/http'
import { useAuthStore } from '@/stores/auth'
import { useBookingsStore } from '@/stores/bookings'

const auth = useAuthStore()
const bookingsStore = useBookingsStore()
const driverRides = ref([])

const formatDate = (value) => dayjs(value).format('D MMM, HH:mm')

const refreshData = async () => {
  const [ridesResponse] = await Promise.all([
    http.get('/rides'),
    bookingsStore.fetchPassengerBookings(),
    bookingsStore.fetchDriverBookings(),
  ])
  driverRides.value = ridesResponse.data.data || ridesResponse.data
}

const updateRideStatus = async (rideId, status) => {
  await http.patch(`/rides/${rideId}`, { status })
  await refreshData()
}

onMounted(() => {
  if (!auth.user) {
    auth.bootstrap().then(refreshData)
  } else {
    refreshData()
  }
})
</script>

