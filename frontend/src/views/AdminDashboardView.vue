<template>
  <section class="page-section">
    <div class="page-section__content space-y-6">
      <span class="chip w-fit">Админ-панель</span>
      <h1 class="section-heading">Панель управления</h1>
      <p class="subheading">
        Общая статистика и управление платформой.
      </p>

      <div v-if="loading" class="surface-card p-8 text-center text-gray-600">
        Загрузка данных...
      </div>

      <div v-else-if="stats" class="grid gap-6 md:grid-cols-2 lg:grid-cols-4">
        <div class="surface-card p-6">
          <p class="text-sm uppercase tracking-[0.3em] text-gray-600 mb-2">Пользователи</p>
          <p class="text-3xl font-semibold text-gray-900">{{ stats.users_count }}</p>
          <p class="text-xs text-gray-500 mt-1">Водителей: {{ stats.drivers_count }} · Пассажиров: {{ stats.passengers_count }}</p>
          <p class="text-xs text-gray-500 mt-1">Новых за месяц: {{ stats.users_this_month }}</p>
        </div>
        <div class="surface-card p-6">
          <p class="text-sm uppercase tracking-[0.3em] text-gray-600 mb-2">Поездки</p>
          <p class="text-3xl font-semibold text-gray-900">{{ stats.rides_count }}</p>
          <p class="text-xs text-gray-500 mt-1">Активных: {{ stats.active_rides }} · Завершено: {{ stats.completed_rides }}</p>
          <p class="text-xs text-gray-500 mt-1">Сегодня: {{ stats.rides_today }}</p>
        </div>
        <div class="surface-card p-6">
          <p class="text-sm uppercase tracking-[0.3em] text-gray-600 mb-2">Бронирования</p>
          <p class="text-3xl font-semibold text-gray-900">{{ stats.bookings_count }}</p>
          <p class="text-xs text-gray-500 mt-1">Ожидают: {{ stats.pending_bookings }} · Сегодня: {{ stats.bookings_today }}</p>
        </div>
        <div class="surface-card p-6">
          <p class="text-sm uppercase tracking-[0.3em] text-gray-600 mb-2">Автомобили</p>
          <p class="text-3xl font-semibold text-gray-900">{{ stats.vehicles_count }}</p>
          <p class="text-xs text-gray-500 mt-1">Всего зарегистрировано</p>
        </div>
      </div>

      <div v-if="stats" class="surface-card p-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">Финансовая статистика</h2>
        <div class="grid gap-4 md:grid-cols-3">
          <div>
            <p class="text-sm text-gray-600">Общий доход</p>
            <p class="text-2xl font-semibold text-gray-900">{{ stats.total_revenue || 0 }} ₽</p>
          </div>
          <div>
            <p class="text-sm text-gray-600">Завершено поездок</p>
            <p class="text-2xl font-semibold text-gray-900">{{ stats.completed_rides }}</p>
          </div>
          <div>
            <p class="text-sm text-gray-600">Средний чек</p>
            <p class="text-2xl font-semibold text-gray-900">
              {{ stats.completed_rides > 0 ? Math.round((stats.total_revenue || 0) / stats.completed_rides) : 0 }} ₽
            </p>
          </div>
        </div>
      </div>

      <div class="grid gap-6 lg:grid-cols-2">
        <div class="surface-card p-6 space-y-4">
          <h2 class="text-lg font-semibold text-gray-900">Последние пользователи</h2>
          <div v-if="stats?.recent_users?.length" class="space-y-3">
            <div
              v-for="user in stats.recent_users"
              :key="user.id"
              class="flex items-center justify-between p-3 rounded-lg bg-gray-50 border border-gray-200"
            >
              <div>
                <p class="text-gray-900 font-semibold">{{ user.name }}</p>
                <p class="text-sm text-gray-600">{{ user.email }}</p>
              </div>
              <span class="chip text-xs">{{ user.role }}</span>
            </div>
          </div>
          <RouterLink :to="{ name: 'admin.users' }" class="secondary-btn w-full">Все пользователи</RouterLink>
        </div>

        <div class="surface-card p-6 space-y-4">
          <h2 class="text-lg font-semibold text-gray-900">Последние поездки</h2>
          <div v-if="stats?.recent_rides?.length" class="space-y-3">
            <div
              v-for="ride in stats.recent_rides"
              :key="ride.id"
              class="p-3 rounded-lg bg-gray-50 border border-gray-200"
            >
              <p class="text-gray-900 font-semibold">{{ ride.origin_city }} → {{ ride.destination_city }}</p>
              <p class="text-sm text-gray-700">Водитель: {{ ride.driver?.name }} · Статус: {{ ride.status }}</p>
            </div>
          </div>
          <RouterLink :to="{ name: 'admin.rides' }" class="secondary-btn w-full">Все поездки</RouterLink>
        </div>
      </div>

      <div class="surface-card p-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">Быстрые действия</h2>
        <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-8">
          <RouterLink :to="{ name: 'admin.users' }" class="primary-btn text-center">Пользователи</RouterLink>
          <RouterLink :to="{ name: 'admin.rides' }" class="primary-btn text-center">Поездки</RouterLink>
          <RouterLink :to="{ name: 'admin.bookings' }" class="primary-btn text-center">Бронирования</RouterLink>
          <RouterLink :to="{ name: 'admin.vehicles' }" class="primary-btn text-center">Автомобили</RouterLink>
          <RouterLink :to="{ name: 'admin.vehicle-verifications' }" class="primary-btn text-center bg-yellow-600 hover:bg-yellow-700">Подтверждения</RouterLink>
          <RouterLink :to="{ name: 'admin.balance-requests' }" class="primary-btn text-center">Пополнения</RouterLink>
          <RouterLink :to="{ name: 'admin.stats' }" class="primary-btn text-center">Статистика</RouterLink>
          <RouterLink :to="{ name: 'admin.audit-logs' }" class="primary-btn text-center bg-slate-800 hover:bg-slate-900">Аудит-лог</RouterLink>
        </div>
      </div>
    </div>
  </section>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { RouterLink } from 'vue-router'
import http from '@/helpers/http'

const loading = ref(true)
const stats = ref(null)

const fetchStats = async () => {
  try {
    const { data } = await http.get('/admin/dashboard')
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

