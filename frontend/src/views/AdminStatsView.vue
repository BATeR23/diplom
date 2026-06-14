<template>
  <section class="page-section">
    <div class="page-section__content space-y-6">
      <span class="chip w-fit">Админ-панель</span>
      <h1 class="section-heading">Статистика и аналитика</h1>
      <p class="subheading">
        Детальная статистика по платформе.
      </p>

      <div v-if="loading" class="text-center text-gray-600 py-8">
        Загрузка...
      </div>

      <div v-else-if="stats" class="space-y-6">
        <div class="grid gap-6 md:grid-cols-3">
          <div class="surface-card p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Пользователи</h3>
            <div class="space-y-2 text-sm text-gray-700">
              <p>Всего: <span class="font-semibold">{{ stats.users_count }}</span></p>
              <p>Водителей: <span class="font-semibold">{{ stats.drivers_count }}</span></p>
              <p>Пассажиров: <span class="font-semibold">{{ stats.passengers_count }}</span></p>
              <p>Админов: <span class="font-semibold">{{ stats.admins_count || 0 }}</span></p>
            </div>
          </div>

          <div class="surface-card p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Поездки</h3>
            <div class="space-y-2 text-sm text-gray-700">
              <p>Всего: <span class="font-semibold">{{ stats.rides_count }}</span></p>
              <p>Опубликовано: <span class="font-semibold">{{ stats.active_rides }}</span></p>
              <p>Завершено: <span class="font-semibold">{{ stats.completed_rides }}</span></p>
              <p>Отменено: <span class="font-semibold">{{ stats.cancelled_rides || 0 }}</span></p>
            </div>
          </div>

          <div class="surface-card p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Бронирования</h3>
            <div class="space-y-2 text-sm text-gray-700">
              <p>Всего: <span class="font-semibold">{{ stats.bookings_count }}</span></p>
              <p>Ожидают: <span class="font-semibold">{{ stats.pending_bookings }}</span></p>
              <p>Принято: <span class="font-semibold">{{ stats.accepted_bookings || 0 }}</span></p>
              <p>Завершено: <span class="font-semibold">{{ stats.completed_bookings || 0 }}</span></p>
            </div>
          </div>
        </div>

        <div class="surface-card p-6">
          <h3 class="text-lg font-semibold text-gray-900 mb-4">Финансы</h3>
          <div class="grid gap-4 md:grid-cols-4">
            <div>
              <p class="text-sm text-gray-600">Общий доход</p>
              <p class="text-2xl font-semibold text-gray-900">{{ stats.total_revenue || 0 }} ₽</p>
            </div>
            <div>
              <p class="text-sm text-gray-600">Средний чек</p>
              <p class="text-2xl font-semibold text-gray-900">
                {{ stats.avg_booking_price || 0 }} ₽
              </p>
            </div>
            <div>
              <p class="text-sm text-gray-600">Средняя цена поездки</p>
              <p class="text-2xl font-semibold text-gray-900">
                {{ stats.avg_ride_price || 0 }} ₽
              </p>
            </div>
            <div>
              <p class="text-sm text-gray-600">Завершено бронирований</p>
              <p class="text-2xl font-semibold text-gray-900">{{ stats.completed_bookings || 0 }}</p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import http from '@/helpers/http'

const loading = ref(true)
const stats = ref(null)

const fetchStats = async () => {
  try {
    const { data } = await http.get('/admin/stats')
    stats.value = data
  } catch (error) {
    alert(error.response?.data?.message || 'Ошибка загрузки статистики')
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  fetchStats()
})
</script>


