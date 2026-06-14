<template>
  <section class="page-section">
    <div class="page-section__content space-y-6">
      <div class="space-y-3">
        <span class="chip">Шаг 2 · Предпросмотр</span>
        <h1 class="section-heading">Подтвердите маршрут и запросите водителя</h1>
        <p class="subheading">
          Мы зафиксируем текущие координаты, построим линию движения и уведомим ближайших водителей. Проверьте название точки и нажмите кнопку.
        </p>
      </div>

      <div class="map-shell min-h-[320px]" v-if="location.destination.name">
        <l-map
          v-if="mapReady"
          ref="map"
          :zoom="11"
          :center="[location.destination.geometry.lat, location.destination.geometry.lng]"
          style="width: 100%; height: 360px;"
          @ready="onMapReady"
        >
          <l-tile-layer
            url="https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png"
            attribution='&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
          />
          <l-marker
            v-if="location.current.geometry"
            :lat-lng="[location.current.geometry.lat, location.current.geometry.lng]"
          />
          <l-marker
            :lat-lng="[location.destination.geometry.lat, location.destination.geometry.lng]"
          />
          <l-polyline
            v-if="routeCoordinates.length > 0"
            :lat-lngs="routeCoordinates"
            color="#3b82f6"
            :weight="4"
          />
        </l-map>
      </div>
      <div v-else class="surface-card p-8 text-slate-300">
        Сначала выберите адрес — карта появится автоматически.
      </div>
    </div>

    <div class="page-section__aside">
      <div class="surface-card p-8 space-y-6">
        <div class="space-y-2">
          <p class="text-sm uppercase tracking-[0.4em] text-slate-400">Назначение</p>
          <p class="text-2xl font-display font-semibold text-white">{{ location.destination.name }}</p>
          <p class="text-sm text-slate-300">{{ location.destination.address }}</p>
        </div>
        <div class="rounded-2xl border border-white/10 bg-white/5 p-4 text-sm text-slate-300 space-y-1">
          <p>Старт: текущая геолокация</p>
          <p>Финиш: {{ location.destination.name || '—' }}</p>
        </div>
        <button class="primary-btn w-full disabled:opacity-50" :disabled="!location.destination.name" @click="handleConfirmTrip">
          Найти водителя
        </button>
      </div>
    </div>
  </section>
</template>

<script setup>
import { useLocationStore } from '@/stores/location'
import { useTripStore } from '@/stores/trip'
import http from '@/helpers/http'
import { onMounted, ref } from 'vue';
import { useRouter } from 'vue-router'
import { LMap, LTileLayer, LMarker, LPolyline } from '@vue-leaflet/vue-leaflet'
import L from 'leaflet'

const location = useLocationStore()
const trip = useTripStore()
const router = useRouter()

const map = ref(null)
const mapReady = ref(false)
const routeCoordinates = ref([])

const handleConfirmTrip = () => {
  console.log('Создание поездки...')

  http().post('/api/trip', {
    origin: location.current.geometry,
    destination: location.destination.geometry,
    destination_name: location.destination.name
  })
      .then((response) => {
        console.log('Поездка создана:', response.data)
        trip.$patch(response.data)
        router.push({
          name: 'trip'
        })
      })
      .catch((error) => {
        console.error('Ошибка при создании поездки:', error)
        if (error.response?.status === 401) {
          alert('Пожалуйста, войдите в систему для создания поездки')
          router.push({ name: 'login' })
        }
      })
}

onMounted(async () => {
  // проверяем, установлен ли пункт назначения
  if (location.destination.name === '') {
    router.push({
      name: 'location'
    })
    return
  }

  // получаем текущее местоположение пользователя
  try {
    await location.updateCurrentLocation()
    console.log('Текущее местоположение получено:', location.current)
  } catch (error) {
    console.error('Ошибка получения местоположения:', error)
  }

const onMapReady = () => {
  mapReady.value = true
  buildRoute()
}

const buildRoute = async () => {
  if (!location.current.geometry || !location.destination.geometry) {
    return
  }

  try {
    const start = location.current.geometry
    const end = location.destination.geometry

    const response = await fetch(
      `https://router.project-osrm.org/route/v1/driving/${start.lng},${start.lat};${end.lng},${end.lat}?overview=full&geometries=geojson`
    )
    const data = await response.json()

    if (data.code === 'Ok' && data.routes && data.routes.length > 0) {
      const coordinates = data.routes[0].geometry.coordinates.map(coord => [coord[1], coord[0]])
      routeCoordinates.value = coordinates

      if (map.value && map.value.leafletObject) {
        const bounds = L.latLngBounds(coordinates)
        map.value.leafletObject.fitBounds(bounds)
      }
    }
  } catch (error) {
    console.error('Ошибка построения маршрута:', error)
  }
}

onMounted(async () => {
  // проверяем, установлен ли пункт назначения
  if (location.destination.name === '') {
    router.push({
      name: 'location'
    })
    return
  }

  // получаем текущее местоположение пользователя
  try {
    await location.updateCurrentLocation()
    console.log('Текущее местоположение получено:', location.current)
  } catch (error) {
    console.error('Ошибка получения местоположения:', error)
  }
})
</script>