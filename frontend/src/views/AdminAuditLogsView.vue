<template>
  <section class="page-section">
    <div class="page-section__content space-y-6">
      <span class="chip w-fit">Админ-панель</span>
      <h1 class="section-heading">Журнал действий</h1>
      <p class="subheading">
        Аудит событий (вход, создание/изменение сущностей, финансовые действия). Удобно для администрирования и раздела ИБ.
      </p>

      <div class="surface-card p-6 space-y-4">
        <div class="grid gap-3 md:grid-cols-6">
          <div class="md:col-span-2">
            <label class="text-sm text-gray-700 font-semibold">Action</label>
            <input v-model="filters.action" class="form-field-input mt-1 w-full" placeholder="auth.login / ride.create ..." />
          </div>
          <div>
            <label class="text-sm text-gray-700 font-semibold">User ID</label>
            <input v-model="filters.user_id" class="form-field-input mt-1 w-full" placeholder="например 1" />
          </div>
          <div class="md:col-span-2">
            <label class="text-sm text-gray-700 font-semibold">Entity type</label>
            <input v-model="filters.entity_type" class="form-field-input mt-1 w-full" placeholder="Ride / Booking / Vehicle ..." />
          </div>
          <div>
            <label class="text-sm text-gray-700 font-semibold">Entity ID</label>
            <input v-model="filters.entity_id" class="form-field-input mt-1 w-full" placeholder="например 42" />
          </div>
        </div>

        <div class="grid gap-3 md:grid-cols-6">
          <div class="md:col-span-2">
            <label class="text-sm text-gray-700 font-semibold">From</label>
            <input v-model="filters.from" class="form-field-input mt-1 w-full" placeholder="2026-05-01 00:00:00" />
          </div>
          <div class="md:col-span-2">
            <label class="text-sm text-gray-700 font-semibold">To</label>
            <input v-model="filters.to" class="form-field-input mt-1 w-full" placeholder="2026-05-06 23:59:59" />
          </div>
          <div class="md:col-span-2 flex items-end gap-3">
            <button class="primary-btn w-full" type="button" @click="applyFilters">Применить</button>
            <button class="secondary-btn w-full" type="button" @click="resetFilters">Сброс</button>
          </div>
        </div>
      </div>

      <div v-if="loading" class="surface-card p-8 text-center text-gray-600">Загрузка...</div>

      <div v-else class="surface-card overflow-hidden">
        <div class="overflow-x-auto">
          <table class="min-w-full text-sm">
            <thead class="bg-gray-50 text-gray-700">
              <tr>
                <th class="px-4 py-3 text-left font-semibold">Дата</th>
                <th class="px-4 py-3 text-left font-semibold">Action</th>
                <th class="px-4 py-3 text-left font-semibold">User</th>
                <th class="px-4 py-3 text-left font-semibold">Entity</th>
                <th class="px-4 py-3 text-left font-semibold">IP</th>
                <th class="px-4 py-3 text-left font-semibold">Meta</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="row in rows" :key="row.id" class="border-t border-gray-200">
                <td class="px-4 py-3 text-gray-700 whitespace-nowrap">{{ formatDate(row.created_at) }}</td>
                <td class="px-4 py-3">
                  <span class="chip text-xs">{{ row.action }}</span>
                </td>
                <td class="px-4 py-3 text-gray-700">
                  <div class="font-semibold text-gray-900">{{ row.user?.name || '—' }}</div>
                  <div class="text-xs text-gray-500">{{ row.user?.email || '' }}</div>
                </td>
                <td class="px-4 py-3 text-gray-700">
                  <div class="font-semibold text-gray-900">{{ row.entity_type || '—' }}</div>
                  <div class="text-xs text-gray-500">{{ row.entity_id ?? '' }}</div>
                </td>
                <td class="px-4 py-3 text-gray-700 whitespace-nowrap">{{ row.ip || '—' }}</td>
                <td class="px-4 py-3 text-gray-700">
                  <pre class="text-xs bg-gray-50 border border-gray-200 rounded-lg p-2 overflow-x-auto max-w-[28rem]">{{ pretty(row.meta) }}</pre>
                </td>
              </tr>
              <tr v-if="rows.length === 0">
                <td class="px-4 py-6 text-center text-gray-600" colspan="6">Записей не найдено.</td>
              </tr>
            </tbody>
          </table>
        </div>

        <div class="p-4 flex items-center justify-between gap-3 border-t border-gray-200">
          <button class="secondary-btn" type="button" :disabled="page <= 1" @click="goToPage(page - 1)">
            Назад
          </button>
          <div class="text-xs text-gray-600">
            Страница <span class="font-semibold">{{ page }}</span> из <span class="font-semibold">{{ lastPage }}</span>
            · всего: <span class="font-semibold">{{ total }}</span>
          </div>
          <button class="secondary-btn" type="button" :disabled="page >= lastPage" @click="goToPage(page + 1)">
            Вперёд
          </button>
        </div>
      </div>
    </div>
  </section>
</template>

<script setup>
import { computed, onMounted, reactive, ref } from 'vue'
import http from '@/helpers/http'

const loading = ref(true)
const rows = ref([])

const page = ref(1)
const perPage = 20
const total = ref(0)
const lastPage = ref(1)

const filters = reactive({
  action: '',
  user_id: '',
  entity_type: '',
  entity_id: '',
  from: '',
  to: '',
})

const queryParams = computed(() => {
  const params = { page: page.value, per_page: perPage }
  Object.entries(filters).forEach(([k, v]) => {
    const val = String(v ?? '').trim()
    if (val) params[k] = val
  })
  return params
})

const pretty = (v) => {
  if (!v) return ''
  try {
    return JSON.stringify(v, null, 2)
  } catch {
    return String(v)
  }
}

const formatDate = (iso) => {
  if (!iso) return '—'
  return new Date(iso).toLocaleString('ru-RU')
}

const fetchLogs = async () => {
  loading.value = true
  try {
    const { data } = await http.get('/admin/audit-logs', { params: queryParams.value })
    rows.value = data.data || []
    total.value = data.total || 0
    lastPage.value = data.last_page || 1
  } catch (error) {
    alert(error.response?.data?.message || 'Ошибка загрузки журнала действий')
  } finally {
    loading.value = false
  }
}

const goToPage = async (p) => {
  page.value = Math.max(1, p)
  await fetchLogs()
}

const applyFilters = async () => {
  page.value = 1
  await fetchLogs()
}

const resetFilters = async () => {
  filters.action = ''
  filters.user_id = ''
  filters.entity_type = ''
  filters.entity_id = ''
  filters.from = ''
  filters.to = ''
  page.value = 1
  await fetchLogs()
}

onMounted(() => {
  fetchLogs()
})
</script>

