<template>
  <section class="page-section">
    <div class="page-section__content space-y-6" v-if="!trip.is_complete">
      <div class="space-y-2">
        <span class="chip">Режим вождения</span>
        <h1 class="section-heading">{{ title }}</h1>
        <p class="subheading">Мы транслируем ваше местоположение каждые 5 секунд и обновляем маршрут в интерфейсе пассажира.</p>
      </div>
      <div class="map-shell min-h-[320px]">
        <l-map
          v-if="mapReady"
          ref="map"
          :zoom="14"
          :center="mapCenterArray"
          style="width:100%; height: 320px;"
          @ready="onMapReady"
        >
          <l-tile-layer
            url="https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png"
            attribution='&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
          />
          <l-marker
            :lat-lng="currentLocationArray"
            :icon="currentIconLeaflet"
          />
          <l-marker
            :lat-lng="destinationLocationArray"
            :icon="destinationIconLeaflet"
          />
          <l-polyline
            v-if="routeCoordinates.length > 0"
            :lat-lngs="routeCoordinates"
            color="#3b82f6"
            :weight="4"
          />
        </l-map>
      </div>
      <div class="flex flex-wrap gap-4">
        <button
          v-if="trip.is_started"
          class="primary-btn flex-1 min-w-[220px]"
          @click="handleCompleteTrip"
        >
          Завершить поездку
        </button>
        <button
          v-else
          class="primary-btn flex-1 min-w-[220px]"
          @click="handlePassengerPickedUp"
        >
          Пассажир на борту
        </button>
        <button class="secondary-btn flex-1 min-w-[220px]" @click="router.push({ name: 'standby' })">
          В ожидание
        </button>
      </div>
    </div>

    <div class="page-section__content" v-else>
      <div class="surface-card p-8">
        <Tada />
        <p class="mt-6 text-center text-slate-200">Поездка завершена. Возвращаем вас в режим ожидания через несколько секунд.</p>
      </div>
    </div>

    <div class="page-section__aside">
      <div class="surface-card p-8 space-y-4">
        <p class="text-sm uppercase tracking-[0.4em] text-slate-400">Метрики</p>
        <div class="rounded-2xl border border-white/10 bg-white/5 p-4 text-sm text-slate-300 space-y-1">
          <p>Точка A: {{ location.current.geometry.lat?.toFixed(4) }}, {{ location.current.geometry.lng?.toFixed(4) }}</p>
          <p>Точка B: {{ location.destination.geometry.lat?.toFixed(4) }}, {{ location.destination.geometry.lng?.toFixed(4) }}</p>
        </div>
        <div class="timeline">
          <div class="timeline-step">
            <p class="text-base font-semibold">1. Направляемся к пассажиру</p>
            <p class="text-sm text-slate-300">Система рассылает пин пассажиру и показывает ETA.</p>
          </div>
          <div class="timeline-step">
            <p class="text-base font-semibold">2. Пассажир в машине</p>
            <p class="text-sm text-slate-300">Нажмите кнопку «Пассажир на борту», чтобы построить новый маршрут.</p>
          </div>
          <div class="timeline-step">
            <p class="text-base font-semibold">3. Завершение</p>
            <p class="text-sm text-slate-300">После прибытия нажмите «Завершить поездку» — история обновится у всех сторон.</p>
          </div>
        </div>
      </div>
    </div>
  </section>
</template>
<script setup>
import { useLocationStore } from '@/stores/location'
import { useTripStore } from '@/stores/trip'
import { useRouter } from 'vue-router'
import { ref, onMounted, onUnmounted, computed } from 'vue'
import http from '@/helpers/http'
import Tada from '@/components/Tada.vue'
import { LMap, LTileLayer, LMarker, LPolyline } from '@vue-leaflet/vue-leaflet'
import L from 'leaflet'

const location = useLocationStore()
const trip = useTripStore()
const router = useRouter()

const map = ref(null)
const intervalRef = ref(null)
const mapReady = ref(false)
const routeCoordinates = ref([])

const title = ref('Едем к пассажиру...')

const currentIconLeaflet = L.icon({
  iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-blue.png',
  shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/images/marker-shadow.png',
  iconSize: [25, 41],
  iconAnchor: [12, 41],
})

const destinationIconLeaflet = L.icon({
  iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-red.png',
  shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/images/marker-shadow.png',
  iconSize: [25, 41],
  iconAnchor: [12, 41],
})

const mapCenterArray = computed(() => {
  if (location.current?.geometry) {
    return [location.current.geometry.lat, location.current.geometry.lng]
  }
  return [55.7558, 37.6173]
})

const currentLocationArray = computed(() => {
  if (location.current?.geometry) {
    return [location.current.geometry.lat, location.current.geometry.lng]
  }
  return [55.7558, 37.6173]
})

const destinationLocationArray = computed(() => {
  if (location.destination?.geometry) {
    return [location.destination.geometry.lat, location.destination.geometry.lng]
  }
  return [55.7558, 37.6173]
})

const onMapReady = () => {
  mapReady.value = true
  updateMapBounds()
  buildRoute()
}

const updateMapBounds = () => {
  if (!map.value || !location.current?.geometry || !location.destination?.geometry) {
    return
  }

  try {
    if (map.value.leafletObject) {
      const bounds = L.latLngBounds([
        [location.current.geometry.lat, location.current.geometry.lng],
        [location.destination.geometry.lat, location.destination.geometry.lng]
      ])
      map.value.leafletObject.fitBounds(bounds)
    }
  } catch (error) {
    console.error('Ошибка обновления границ карты:', error)
  }
}

const buildRoute = async () => {
  if (!location.current?.geometry || !location.destination?.geometry) {
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

const broadcastDriverLocation = () => {
  http().post(`/api/trip/${trip.id}/location`, {
    driver_location: location.current.geometry
  })
      .then((response) => {})
      .catch((error) => {
        console.error(error)
      })
}

const handlePassengerPickedUp = () => {
  http().post(`/api/trip/${trip.id}/start`)
      .then((response) => {
        title.value = 'Едем к месту назначения...'
        location.$patch({
          destination: {
            name: response.data.destination_name,
            geometry: response.data.destination
          }
        })
        trip.$patch(response.data)
      })
      .catch((error) => {
        console.error(error)
      })
}

const handleCompleteTrip = () => {
  http().post(`/api/trip/${trip.id}/end`)
      .then((response) => {
        title.value = 'Поездка завершена!'

        trip.$patch(response.data)

        setTimeout(() => {
          trip.reset()
          location.reset()

          router.push({
            name: 'standby'
          })
        }, 3000)
      })
      .catch((error) => {
        console.error(error)
      })
}

onMounted(async () => {
  await location.updateCurrentLocation()

  intervalRef.value = setInterval(async () => {
    // update the driver's current position and update map bounds
    await location.updateCurrentLocation()

    // update the driver's position in the database
    broadcastDriverLocation()

    updateMapBounds()
    buildRoute()
  }, 5000)
})

onUnmounted(() => {
  clearInterval(intervalRef.value)
  intervalRef.value = null
})
</script>