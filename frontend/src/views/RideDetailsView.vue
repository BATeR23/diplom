<template>
  <section class="page-section" v-if="ride">
    <div class="page-section__content space-y-6">
      <span class="chip w-fit">Поездка № {{ ride.id }}</span>
      <div class="glass-panel p-6 space-y-4">
        <div>
          <p class="text-sm uppercase tracking-[0.3em] text-gray-600">Маршрут</p>
          <h1 class="text-3xl font-semibold text-gray-900">
            {{ ride.origin_city }} → {{ ride.destination_city }}
          </h1>
          <p class="text-sm text-gray-700">{{ ride.origin_address }} · {{ ride.destination_address }}</p>
        </div>
        <div class="grid gap-4 sm:grid-cols-3">
          <div>
            <p class="text-xs uppercase tracking-[0.3em] text-gray-500">Отправление</p>
            <p class="text-lg font-semibold text-gray-900">{{ formatDate(ride.departure_time) }}</p>
          </div>
          <div>
            <p class="text-xs uppercase tracking-[0.3em] text-gray-500">Доступно мест</p>
            <p class="text-lg font-semibold text-gray-900">{{ ride.seats_available }} из {{ ride.seats_total }}</p>
          </div>
          <div>
            <p class="text-xs uppercase tracking-[0.3em] text-gray-500">Стоимость</p>
            <p class="text-lg font-semibold text-gray-900">{{ ride.price }} ₽</p>
          </div>
        </div>
      </div>
      <div class="glass-panel p-6 space-y-4" v-if="ride.driver">
        <p class="text-sm uppercase tracking-[0.3em] text-gray-600">Водитель</p>
        <div class="flex items-center gap-4">
          <div class="h-12 w-12 rounded-2xl bg-blue-100 grid place-items-center text-lg font-semibold text-blue-700">
            {{ ride.driver.name.slice(0, 1) }}
          </div>
          <div>
            <p class="text-lg font-semibold text-gray-900">{{ ride.driver.name }}</p>
            <p class="text-sm text-gray-700">Рейтинг: {{ ride.driver.rating_average ?? '—' }}</p>
          </div>
        </div>
        <p class="text-sm text-gray-700" v-if="ride.vehicle">
          Автомобиль: {{ ride.vehicle.make }} {{ ride.vehicle.model }}, {{ ride.vehicle.year }} ·
          класс {{ ride.vehicle.comfort_class }}
        </p>
      </div>
      <div class="glass-panel p-6 space-y-4" v-if="isDriver && routeInfo.distance">
        <p class="text-sm uppercase tracking-[0.3em] text-gray-600">Информация о маршруте</p>
        <div class="grid gap-4 sm:grid-cols-3">
          <div>
            <p class="text-xs uppercase tracking-[0.3em] text-gray-500">Расстояние</p>
            <p class="text-lg font-semibold text-gray-900">{{ formatDistance(routeInfo.distance) }}</p>
          </div>
          <div>
            <p class="text-xs uppercase tracking-[0.3em] text-gray-500">Время в пути</p>
            <p class="text-lg font-semibold text-gray-900">{{ formatDuration(routeInfo.duration) }}</p>
          </div>
          <div>
            <p class="text-xs uppercase tracking-[0.3em] text-gray-500">Расход топлива</p>
            <p class="text-lg font-semibold text-gray-900">{{ formatFuelConsumption(routeInfo.distance) }} л</p>
            <p class="text-xs text-gray-500 mt-1">(при среднем расходе 8 л/100км)</p>
          </div>
        </div>
      </div>
      <div class="glass-panel p-6 space-y-4">
        <p class="text-sm uppercase tracking-[0.3em] text-gray-600">Маршрут на карте</p>
        <div class="map-shell min-h-[400px] rounded-lg overflow-hidden">
          <l-map
            ref="map"
            :zoom="zoom"
            :center="mapCenter"
            style="width: 100%; height: 400px;"
            @ready="onMapReady"
          >
            <l-tile-layer
              url="https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png"
              attribution='&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
            />
            <l-marker
              v-if="originPosition.lat && originPosition.lng"
              :lat-lng="[originPosition.lat, originPosition.lng]"
              :icon="originIcon"
            />
            <l-marker
              v-if="destinationPosition.lat && destinationPosition.lng"
              :lat-lng="[destinationPosition.lat, destinationPosition.lng]"
              :icon="destinationIcon"
            />
            <l-polyline
              v-if="routeCoordinates.length > 0"
              :lat-lngs="routeCoordinates"
              color="#3b82f6"
              :weight="4"
            />
          </l-map>
        </div>
      </div>
    </div>
    <div class="page-section__aside">
      <div class="surface-card space-y-5 p-8">
        <h3 class="text-lg font-semibold text-gray-900">Забронировать место</h3>
        <form class="space-y-4" @submit.prevent="handleBooking">
          <div class="form-field">
            <label for="seats">Количество мест</label>
            <input
              id="seats"
              v-model.number="booking.seats_requested"
              type="number"
              min="1"
              :max="ride.seats_available"
              required
            />
          </div>
          <div class="form-field">
            <label for="notes">Сообщение водителю (необязательно)</label>
            <textarea
              id="notes"
              v-model="booking.notes"
              rows="3"
              placeholder="Например: у меня небольшая ручная кладь"
            />
          </div>
          <p v-if="errorMessage" class="text-sm text-rose-600">{{ errorMessage }}</p>
          <p v-if="successMessage" class="text-sm text-emerald-600">{{ successMessage }}</p>
          <button
            type="submit"
            class="primary-btn w-full disabled:opacity-60"
            :disabled="isBooking || ride.seats_available === 0"
          >
            {{ bookingButtonLabel }}
          </button>
        </form>
        <RouterLink class="secondary-btn w-full" :to="{ name: 'rides.search' }">
          Вернуться к поиску
        </RouterLink>
      </div>
    </div>
  </section>
  <section v-else class="page-section">
    <div class="page-section__content">
      <p class="text-gray-600">Загружаем поездку…</p>
    </div>
  </section>
</template>

<script setup>
import { onMounted, reactive, ref, computed, watch } from 'vue'
import { RouterLink, useRoute } from 'vue-router'
import dayjs from 'dayjs'
import http from '@/helpers/http'
import { useRidesStore } from '@/stores/rides'
import { useAuthStore } from '@/stores/auth'
import { LMap, LTileLayer, LMarker, LPolyline } from '@vue-leaflet/vue-leaflet'
import L from 'leaflet'
import 'leaflet/dist/leaflet.css'

// Исправление иконок Leaflet (проблема с путями в Webpack/Vite)
delete L.Icon.Default.prototype._getIconUrl
L.Icon.Default.mergeOptions({
  iconRetinaUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/images/marker-icon-2x.png',
  iconUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/images/marker-icon.png',
  shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/images/marker-shadow.png',
})

const route = useRoute()
const ridesStore = useRidesStore()
const auth = useAuthStore()

const booking = reactive({
  seats_requested: 1,
  notes: '',
})

const errorMessage = ref('')
const successMessage = ref('')
const isBooking = ref(false)
const map = ref(null)
const mapReady = ref(false)
const zoom = ref(8)
const routeCoordinates = ref([])
const routeInfo = ref({
  distance: null, // в метрах
  duration: null, // в секундах
})

const ride = computed(() => ridesStore.current)

// Проверяем, является ли текущий пользователь водителем этой поездки
const isDriver = computed(() => {
  if (!auth.isAuthenticated || !ride.value?.driver || !auth.user) {
    return false
  }
  // Проверяем разные возможные структуры данных
  return ride.value.driver.user_id === auth.user.id ||
         ride.value.driver.user?.id === auth.user.id ||
         ride.value.driver.id === auth.user.id ||
         ride.value.driver_id === auth.user.id
})

const bookingButtonLabel = computed(() => {
  if (!auth.isAuthenticated) return 'Войдите, чтобы бронировать'
  if (ride.value?.seats_available === 0) return 'Нет свободных мест'
  return isBooking.value ? 'Отправляем запрос…' : 'Отправить заявку'
})

const formatDate = (value) => dayjs(value).format('D MMMM, HH:mm')

// Форматирование расстояния
const formatDistance = (meters) => {
  if (!meters) return '—'
  if (meters < 1000) {
    return `${Math.round(meters)} м`
  }
  return `${(meters / 1000).toFixed(1)} км`
}

// Форматирование времени в пути
const formatDuration = (seconds) => {
  if (!seconds) return '—'
  const hours = Math.floor(seconds / 3600)
  const minutes = Math.floor((seconds % 3600) / 60)
  
  if (hours > 0) {
    return `${hours} ч ${minutes} мин`
  }
  return `${minutes} мин`
}

// Расчет расхода топлива (средний расход 8 л/100км)
const formatFuelConsumption = (meters) => {
  if (!meters) return '—'
  const km = meters / 1000
  const avgConsumption = 8 // л/100км
  const fuel = (km * avgConsumption) / 100
  return fuel.toFixed(1)
}

// Расчет расстояния по прямой (Haversine formula)
const calculateDistance = (lat1, lon1, lat2, lon2) => {
  const R = 6371000 // радиус Земли в метрах
  const dLat = (lat2 - lat1) * Math.PI / 180
  const dLon = (lon2 - lon1) * Math.PI / 180
  const a = 
    Math.sin(dLat / 2) * Math.sin(dLat / 2) +
    Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) *
    Math.sin(dLon / 2) * Math.sin(dLon / 2)
  const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a))
  return R * c
}

// Иконки для маркеров Leaflet
const originIcon = L.icon({
  iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-blue.png',
  shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/images/marker-shadow.png',
  iconSize: [25, 41],
  iconAnchor: [12, 41],
  popupAnchor: [1, -34],
  shadowSize: [41, 41]
})

const destinationIcon = L.icon({
  iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-red.png',
  shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/images/marker-shadow.png',
  iconSize: [25, 41],
  iconAnchor: [12, 41],
  popupAnchor: [1, -34],
  shadowSize: [41, 41]
})

// Позиции для маркеров (используем геокодирование адресов)
const originPosition = ref({ lat: 55.7558, lng: 37.6173 }) // Москва по умолчанию
const destinationPosition = ref({ lat: 55.7558, lng: 37.6173 }) // Москва по умолчанию

const mapCenter = computed(() => {
  if (originPosition.value && destinationPosition.value && originPosition.value.lat && destinationPosition.value.lat) {
    // Центр между двумя точками (формат для Leaflet: [lat, lng])
    return [
      (originPosition.value.lat + destinationPosition.value.lat) / 2,
      (originPosition.value.lng + destinationPosition.value.lng) / 2
    ]
  }
  return [55.7558, 37.6173] // Москва по умолчанию
})

// Обработчик готовности карты
const onMapReady = () => {
  mapReady.value = true
  console.log('Карта готова')
  // После инициализации карты строим маршрут
  if (ride.value && ride.value.origin_address && ride.value.destination_address) {
    setTimeout(() => {
      buildRoute()
    }, 500)
  }
}

// Геокодирование адресов и построение маршрута
const buildRoute = async () => {
  if (!ride.value || !ride.value.origin_address || !ride.value.destination_address) {
    return
  }

  try {
    // Используем Nominatim для геокодирования (OpenStreetMap) с улучшенной обработкой ошибок
    const geocodeAddress = async (city, address) => {
      // Пробуем разные варианты запроса
      const queries = [
        `${city}, ${address}`,
        `${address}, ${city}`,
        address,
        city
      ]

      for (const query of queries) {
        try {
          const response = await fetch(
            `https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(query)}&limit=5&addressdetails=1`
          )
          const data = await response.json()
          
          if (data && data.length > 0) {
            // Ищем наиболее подходящий результат
            const bestMatch = data.find(item => 
              item.display_name.toLowerCase().includes(city.toLowerCase()) ||
              item.display_name.toLowerCase().includes(address.toLowerCase())
            ) || data[0]
            
            return {
              lat: parseFloat(bestMatch.lat),
              lng: parseFloat(bestMatch.lon),
              display_name: bestMatch.display_name
            }
          }
        } catch (error) {
          console.warn(`Ошибка при поиске адреса "${query}":`, error)
          continue
        }
      }
      
      // Если ничего не найдено, пробуем найти хотя бы город
      try {
        const response = await fetch(
          `https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(city)}&limit=1`
        )
        const data = await response.json()
        
        if (data && data.length > 0) {
          console.warn(`Найден только город "${city}", точный адрес не найден`)
          return {
            lat: parseFloat(data[0].lat),
            lng: parseFloat(data[0].lon),
            display_name: data[0].display_name
          }
        }
      } catch (error) {
        console.error('Ошибка при поиске города:', error)
      }
      
      throw new Error(`Не удалось найти адрес: ${city}, ${address}`)
    }

    // Геокодируем оба адреса
    const [originCoords, destinationCoords] = await Promise.all([
      geocodeAddress(ride.value.origin_city, ride.value.origin_address),
      geocodeAddress(ride.value.destination_city, ride.value.destination_address)
    ])

    // Обновляем позиции маркеров
    originPosition.value = originCoords
    destinationPosition.value = destinationCoords

    // Центр карты обновится автоматически через computed свойство mapCenter

    // Строим маршрут используя OSRM (Open Source Routing Machine)
    const buildOSRMRoute = async (start, end) => {
      try {
        const response = await fetch(
          `https://router.project-osrm.org/route/v1/driving/${start.lng},${start.lat};${end.lng},${end.lat}?overview=full&geometries=geojson`
        )
        const data = await response.json()
        
        if (data.code === 'Ok' && data.routes && data.routes.length > 0) {
          const route = data.routes[0]
          
          // Сохраняем информацию о маршруте
          routeInfo.value = {
            distance: route.distance, // в метрах
            duration: route.duration, // в секундах
          }
          
          // Преобразуем GeoJSON координаты в формат [lat, lng] для Leaflet
          const coordinates = route.geometry.coordinates.map(coord => [coord[1], coord[0]])
          routeCoordinates.value = coordinates
          
          // Подгоняем карту под маршрут
          if (map.value && map.value.leafletObject) {
            const bounds = L.latLngBounds(coordinates)
            map.value.leafletObject.fitBounds(bounds)
          }
        }
      } catch (error) {
        console.warn('Не удалось построить маршрут через OSRM, используем прямую линию:', error)
        // Если не удалось построить маршрут, рисуем прямую линию и рассчитываем расстояние по прямой
        routeCoordinates.value = [
          [originCoords.lat, originCoords.lng],
          [destinationCoords.lat, destinationCoords.lng]
        ]
        
        // Рассчитываем расстояние по прямой (Haversine formula)
        const distance = calculateDistance(
          originCoords.lat, originCoords.lng,
          destinationCoords.lat, destinationCoords.lng
        )
        routeInfo.value = {
          distance: distance,
          duration: null,
        }
      }
    }

    await buildOSRMRoute(originCoords, destinationCoords)
  } catch (error) {
    console.error('Ошибка при построении маршрута:', error)
  }
}

// Отслеживаем изменения ride для перестроения маршрута
watch(() => ride.value, (newRide) => {
  if (newRide && newRide.origin_address && newRide.destination_address) {
    setTimeout(() => {
      buildRoute()
    }, 500)
  }
}, { deep: true })

const fetchRide = async () => {
  try {
    await ridesStore.fetchRide(route.params.id)
  } catch (error) {
    errorMessage.value = 'Поездка недоступна или была удалена.'
  }
}

const handleBooking = async () => {
  if (!auth.isAuthenticated) {
    errorMessage.value = 'Авторизуйтесь, чтобы отправить заявку.'
    return
  }

  isBooking.value = true
  errorMessage.value = ''
  successMessage.value = ''

  try {
    await http.post(`/rides/${ride.value.id}/bookings`, {
      seats_requested: booking.seats_requested,
      notes: booking.notes || undefined,
    })
    successMessage.value = 'Заявка отправлена! Водитель уведомлён и скоро ответит.'
    booking.notes = ''
    booking.seats_requested = 1
    await fetchRide()
  } catch (error) {
    errorMessage.value = error.response?.data?.message || 'Не удалось отправить заявку. Попробуйте ещё раз.'
  } finally {
    isBooking.value = false
  }
}

onMounted(async () => {
  await fetchRide()
  // Устанавливаем mapReady в true сразу, чтобы карта отобразилась
  mapReady.value = true
  // Строим маршрут после загрузки поездки
  setTimeout(() => {
    buildRoute()
  }, 1000)
})
</script>

