<template>
  <section class="page-section">
    <div class="page-section__content space-y-6">
      <div class="flex items-center justify-between">
        <div>
          <span class="chip w-fit">Поиск поездок</span>
          <h1 class="section-heading">Доступные поездки</h1>
        </div>
        <div class="flex gap-3">
          <select v-model="sortBy" @change="applySorting" class="form-field-input text-sm">
            <option value="">Сортировка</option>
            <option value="price_asc">Цена: по возрастанию</option>
            <option value="price_desc">Цена: по убыванию</option>
            <option value="date_asc">Дата: раньше</option>
            <option value="date_desc">Дата: позже</option>
          </select>
          <button @click="showFiltersModal = true" class="primary-btn">
            Фильтр
          </button>
        </div>
      </div>

      <p v-if="ridesStore.error" class="text-sm text-rose-600">{{ ridesStore.error }}</p>
      
      <div class="space-y-4">
        <div
          v-for="ride in sortedRides"
          :key="ride.id"
          class="glass-panel flex flex-col gap-4 p-6 sm:flex-row sm:items-center sm:justify-between"
        >
          <div>
            <p class="text-sm uppercase tracking-[0.3em] text-gray-600">{{ ride.origin_city }} → {{ ride.destination_city }}</p>
            <p class="text-2xl font-semibold text-gray-900">{{ formatDate(ride.departure_time) }}</p>
            <p class="text-sm text-gray-700">
              {{ ride.driver?.name || 'Водитель' }}
              <span v-if="ride.driver?.rating_average"> · ★ {{ Number(ride.driver.rating_average).toFixed(1) }}</span>
              <span v-if="ride.driver?.reviews_received_count"> ({{ ride.driver.reviews_received_count }} {{ reviewsLabel(ride.driver.reviews_received_count) }})</span>
              · {{ ride.vehicle?.make }} {{ ride.vehicle?.model }} · {{ ride.seats_available }} мест
            </p>
          </div>
          <div class="flex flex-col items-start gap-2 sm:items-end">
            <p class="text-lg font-semibold text-gray-900">{{ ride.price }} ₽</p>
            <RouterLink class="secondary-btn" :to="{ name: 'rides.show', params: { id: ride.id } }">
              Детали и бронирование
            </RouterLink>
          </div>
        </div>
        <p v-if="!ridesStore.items.length && ridesStore.status !== 'loading'" class="text-sm text-gray-600">
          По вашему запросу пока нет поездок. Попробуйте изменить фильтры или создайте своё объявление.
        </p>
        <div v-if="ridesStore.status === 'loading'" class="text-center text-gray-600 py-8">
          Загрузка поездок...
        </div>
      </div>
    </div>
    <div class="page-section__aside">
      <div class="surface-card space-y-4 p-8">
        <h3 class="text-lg font-semibold text-gray-900">Почему PulseRide</h3>
        <ul class="space-y-3 text-sm text-gray-600">
          <li>• Проверенные водители и отзывы после каждой поездки.</li>
          <li>• Встроенный чат для уточнения деталей без обмена номерами.</li>
          <li>• Умные советы по цене и автоматический пересчёт мест.</li>
        </ul>
        <RouterLink class="primary-btn w-full" :to="{ name: 'rides.create' }">
          Предложить поездку
        </RouterLink>
      </div>
    </div>

    <!-- Filters Modal -->
    <div v-if="showFiltersModal" class="fixed inset-0 bg-black/30 flex items-center justify-center z-50 p-4" @click.self="showFiltersModal = false">
      <div class="surface-card p-8 max-w-2xl w-full max-h-[90vh] overflow-y-auto space-y-6">
        <div class="flex items-center justify-between">
          <h2 class="text-xl font-semibold text-gray-900">Фильтры поиска</h2>
          <button @click="showFiltersModal = false" class="text-gray-400 hover:text-gray-600">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>

        <form @submit.prevent="applyFilters" class="space-y-6">
          <div class="grid gap-4 sm:grid-cols-2">
            <div class="form-field">
              <label for="origin">Откуда</label>
              <input
                id="origin"
                v-model="filters.origin"
                type="text"
                placeholder="Город отправления"
              />
            </div>
            <div class="form-field">
              <label for="destination">Куда</label>
              <input
                id="destination"
                v-model="filters.destination"
                type="text"
                placeholder="Город назначения"
              />
            </div>
          </div>

          <div class="grid gap-4 sm:grid-cols-2">
            <div class="form-field">
              <label for="date">Дата отправления</label>
              <input
                id="date"
                v-model="filters.date"
                type="date"
              />
            </div>
            <div class="form-field">
              <label for="seats">Количество мест</label>
              <input
                id="seats"
                v-model.number="filters.seats"
                type="number"
                min="1"
                placeholder="1"
              />
            </div>
          </div>

          <div class="grid gap-4 sm:grid-cols-2">
            <div class="form-field">
              <label for="price_min">Цена от (₽)</label>
              <input
                id="price_min"
                v-model.number="filters.price_min"
                type="number"
                min="0"
                placeholder="0"
              />
            </div>
            <div class="form-field">
              <label for="price_max">Цена до (₽)</label>
              <input
                id="price_max"
                v-model.number="filters.price_max"
                type="number"
                min="0"
                placeholder="1000"
              />
            </div>
          </div>

          <div class="form-field">
            <label for="comfort_class">Класс комфорта</label>
            <select id="comfort_class" v-model="filters.comfort_class">
              <option value="">Любой</option>
              <option value="economy">Эконом</option>
              <option value="comfort">Комфорт</option>
              <option value="business">Бизнес</option>
            </select>
          </div>

          <div class="space-y-3">
            <label class="flex items-center gap-3 cursor-pointer">
              <input
                type="checkbox"
                v-model="filters.pets_allowed"
                class="w-5 h-5 rounded border-gray-300 bg-white text-blue-600 focus:ring-2 focus:ring-blue-500"
              />
              <span class="text-sm text-gray-700">Можно с животными</span>
            </label>
            <label class="flex items-center gap-3 cursor-pointer">
              <input
                type="checkbox"
                v-model="filters.smoking_allowed"
                class="w-5 h-5 rounded border-gray-300 bg-white text-blue-600 focus:ring-2 focus:ring-blue-500"
              />
              <span class="text-sm text-gray-700">Курение разрешено</span>
            </label>
          </div>

          <div class="flex gap-4 pt-4">
            <button type="submit" class="primary-btn flex-1" :disabled="ridesStore.status === 'loading'">
              {{ ridesStore.status === 'loading' ? 'Применяем...' : 'Применить фильтры' }}
            </button>
            <button type="button" @click="resetFilters" class="secondary-btn flex-1">
              Сбросить
            </button>
          </div>
        </form>
      </div>
    </div>
  </section>
</template>

<script setup>
import { reactive, ref, computed, onMounted } from 'vue'
import { RouterLink } from 'vue-router'
import dayjs from 'dayjs'
import { useRidesStore } from '@/stores/rides'

const ridesStore = useRidesStore()
const showFiltersModal = ref(false)
const sortBy = ref('')

const filters = reactive({
  origin: '',
  destination: '',
  date: '',
  seats: null,
  price_min: null,
  price_max: null,
  comfort_class: '',
  pets_allowed: false,
  smoking_allowed: false,
})

const sortedRides = computed(() => {
  const rides = [...ridesStore.items]
  
  if (!sortBy.value) return rides
  
  switch (sortBy.value) {
    case 'price_asc':
      return rides.sort((a, b) => parseFloat(a.price) - parseFloat(b.price))
    case 'price_desc':
      return rides.sort((a, b) => parseFloat(b.price) - parseFloat(a.price))
    case 'date_asc':
      return rides.sort((a, b) => new Date(a.departure_time) - new Date(b.departure_time))
    case 'date_desc':
      return rides.sort((a, b) => new Date(b.departure_time) - new Date(a.departure_time))
    default:
      return rides
  }
})

const applySorting = () => {
  // Sorting is handled by computed property
}

const fetchRides = async () => {
  const params = {}
  
  if (filters.origin) params.origin = filters.origin
  if (filters.destination) params.destination = filters.destination
  if (filters.date) params.date = filters.date
  if (filters.seats) params.seats = filters.seats
  if (filters.price_min !== null && filters.price_min !== '') params.price_min = filters.price_min
  if (filters.price_max !== null && filters.price_max !== '') params.price_max = filters.price_max
  if (filters.comfort_class) params.comfort_class = filters.comfort_class
  if (filters.pets_allowed) params.pets_allowed = filters.pets_allowed
  if (filters.smoking_allowed) params.smoking_allowed = filters.smoking_allowed

  await ridesStore.search(params)
  showFiltersModal.value = false
}

const applyFilters = () => {
  fetchRides()
}

const resetFilters = () => {
  filters.origin = ''
  filters.destination = ''
  filters.date = ''
  filters.seats = null
  filters.price_min = null
  filters.price_max = null
  filters.comfort_class = ''
  filters.pets_allowed = false
  filters.smoking_allowed = false
  fetchRides()
}

const formatDate = (value) => {
  return dayjs(value).format('D MMMM, HH:mm')
}

const reviewsLabel = (count) => {
  const mod10 = count % 10
  const mod100 = count % 100
  if (mod10 === 1 && mod100 !== 11) return 'отзыв'
  if (mod10 >= 2 && mod10 <= 4 && (mod100 < 10 || mod100 >= 20)) return 'отзыва'
  return 'отзывов'
}

onMounted(() => {
  // Загружаем все поездки без фильтров при загрузке страницы
  fetchRides()
})
</script>

