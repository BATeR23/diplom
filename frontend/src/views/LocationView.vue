<template>
  <section class="page-section">
    <div class="page-section__content space-y-6">
      <div class="space-y-3">
        <span class="chip">Шаг 1 · Маршрут</span>
        <h1 class="section-heading">Укажите точку назначения</h1>
        <p class="subheading">
          Мы вооружим водителя координатами, временем прибытия и напоминаниями. Используйте поиск, чтобы выбрать адрес.
        </p>
      </div>

      <div class="surface-card p-8 space-y-6">
        <div class="form-field">
          <label for="destination">Введите адрес</label>
          <input
            id="destination"
            v-model="searchQuery"
            type="text"
            placeholder="Например, Москва, Большая Никитская 56"
            class="mt-2 block w-full rounded-2xl border border-white/20 bg-night/40 px-4 py-3 text-base text-snow placeholder-slate-400 focus:border-brand-400 focus:bg-night/70"
            @input="handleSearch"
            @keydown.enter.prevent="handleSearch"
          />
          <div v-if="searchResults.length > 0" class="mt-2 bg-white rounded-lg shadow-lg max-h-60 overflow-y-auto">
            <div
              v-for="(result, index) in searchResults"
              :key="index"
              class="p-3 hover:bg-gray-100 cursor-pointer border-b border-gray-200 last:border-b-0"
              @click="selectLocation(result)"
            >
              <p class="font-semibold text-gray-900">{{ result.display_name.split(',')[0] }}</p>
              <p class="text-sm text-gray-600">{{ result.display_name }}</p>
            </div>
          </div>
        </div>
        <div class="flex flex-wrap gap-4">
          <button class="primary-btn flex-1 min-w-[220px]" type="button" @click="handleSelectLocation">
            Построить маршрут
          </button>
          <button class="secondary-btn flex-1 min-w-[220px]" type="button" @click="router.back()">
            Назад
          </button>
        </div>
      </div>
    </div>

    <div class="page-section__aside">
      <div class="surface-card p-8 space-y-6">
        <p class="text-sm uppercase tracking-[0.4em] text-slate-300">Предпросмотр</p>
        <div v-if="location.destination.name" class="space-y-4">
          <p class="text-2xl font-display font-semibold text-white">{{ location.destination.name }}</p>
          <p class="text-sm text-slate-300">{{ location.destination.address }}</p>
          <div class="rounded-2xl border border-white/10 bg-white/5 p-4 text-sm text-slate-300">
            <p>Широта: {{ location.destination.geometry.lat?.toFixed(4) }}</p>
            <p>Долгота: {{ location.destination.geometry.lng?.toFixed(4) }}</p>
          </div>
        </div>
        <div v-else class="text-sm text-slate-400">
          Координаты появятся сразу после выбора адреса. Это позволит построить точный маршрут и показать прогноз прибытия.
        </div>
      </div>
    </div>
  </section>
</template>
<script setup>
import { useLocationStore } from '@/stores/location'
import { useRouter } from 'vue-router'
import { ref } from 'vue'

const location = useLocationStore()
const router = useRouter()
const searchQuery = ref('')
const searchResults = ref([])

const handleSearch = async () => {
  if (!searchQuery.value || searchQuery.value.length < 3) {
    searchResults.value = []
    return
  }

  try {
    const response = await fetch(
      `https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(searchQuery.value)}&limit=5&addressdetails=1`
    )
    const data = await response.json()
    searchResults.value = data || []
  } catch (error) {
    console.error('Ошибка поиска адреса:', error)
    searchResults.value = []
  }
}

const selectLocation = (result) => {
  location.$patch({
    destination: {
      name: result.display_name.split(',')[0],
      address: result.display_name,
      geometry: {
        lat: parseFloat(result.lat),
        lng: parseFloat(result.lon)
      }
    }
  })
  searchQuery.value = result.display_name
  searchResults.value = []
}

const handleSelectLocation = () => {
    if (location.destination.name !== '') {
        router.push({
            name: 'map'
        })
    }
}
</script>