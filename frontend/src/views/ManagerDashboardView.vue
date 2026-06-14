<template>
  <section class="page-section">
    <div class="page-section__content space-y-6">
      <span class="chip w-fit">Панель менеджера</span>
      <h1 class="section-heading">Панель управления</h1>
      <p class="subheading">
        Подтверждение пополнений баланса и транспортных средств.
      </p>

      <div class="grid gap-6 md:grid-cols-3">
        <RouterLink :to="{ name: 'manager.balance-requests' }" class="surface-card p-6 hover:shadow-lg transition">
          <div class="text-center space-y-2">
            <h3 class="text-lg font-semibold text-gray-900">Пополнения баланса</h3>
            <p class="text-sm text-gray-600">Подтверждение запросов на пополнение</p>
            <p v-if="pendingBalanceRequests > 0" class="text-sm font-semibold text-yellow-600 mt-2">
              Ожидает: {{ pendingBalanceRequests }}
            </p>
          </div>
        </RouterLink>

        <RouterLink :to="{ name: 'manager.vehicle-verifications' }" class="surface-card p-6 hover:shadow-lg transition">
          <div class="text-center space-y-2">
            <h3 class="text-lg font-semibold text-gray-900">Подтверждение ТС</h3>
            <p class="text-sm text-gray-600">Проверка документов автомобилей</p>
            <p v-if="pendingVehicles > 0" class="text-sm font-semibold text-yellow-600 mt-2">
              Ожидает: {{ pendingVehicles }}
            </p>
          </div>
        </RouterLink>

        <RouterLink :to="{ name: 'manager.audit-logs' }" class="surface-card p-6 hover:shadow-lg transition">
          <div class="text-center space-y-2">
            <h3 class="text-lg font-semibold text-gray-900">Журнал действий</h3>
            <p class="text-sm text-gray-600">Контроль событий и администрирование</p>
          </div>
        </RouterLink>
      </div>

      <div v-if="loading" class="text-center text-gray-600 py-8">
        Загрузка статистики...
      </div>

      <div v-else class="grid gap-6 md:grid-cols-3">
        <div class="surface-card p-6">
          <p class="text-sm uppercase tracking-[0.3em] text-gray-600 mb-2">Пополнения</p>
          <p class="text-3xl font-semibold text-gray-900">{{ stats.pending_balance_requests || 0 }}</p>
          <p class="text-xs text-gray-500 mt-1">Ожидают подтверждения</p>
        </div>
        <div class="surface-card p-6">
          <p class="text-sm uppercase tracking-[0.3em] text-gray-600 mb-2">Транспортные средства</p>
          <p class="text-3xl font-semibold text-gray-900">{{ stats.pending_vehicles || 0 }}</p>
          <p class="text-xs text-gray-500 mt-1">Ожидают подтверждения</p>
        </div>
        <div class="surface-card p-6">
          <p class="text-sm uppercase tracking-[0.3em] text-gray-600 mb-2">Одобрено сегодня</p>
          <p class="text-3xl font-semibold text-gray-900">{{ stats.approved_today || 0 }}</p>
          <p class="text-xs text-gray-500 mt-1">Пополнений и ТС</p>
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
const stats = ref({})
const pendingBalanceRequests = ref(0)
const pendingVehicles = ref(0)

const fetchStats = async () => {
  try {
    // Загружаем статистику для менеджера
    const [balanceRes, vehiclesRes] = await Promise.all([
      http.get('/admin/balance-requests', { params: { status: 'pending' } }).catch(() => ({ data: { data: [] } })),
      http.get('/admin/vehicle-verifications').catch(() => ({ data: { data: [] } }))
    ])
    
    pendingBalanceRequests.value = balanceRes.data?.data?.length || balanceRes.data?.length || 0
    pendingVehicles.value = vehiclesRes.data?.data?.length || vehiclesRes.data?.length || 0
    
    stats.value = {
      pending_balance_requests: pendingBalanceRequests.value,
      pending_vehicles: pendingVehicles.value,
      approved_today: 0, // Можно добавить подсчет, если нужно
    }
  } catch (error) {
    console.error('Ошибка загрузки статистики:', error)
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  fetchStats()
})
</script>

