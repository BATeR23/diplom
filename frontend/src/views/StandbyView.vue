<template>
  <section class="page-section">
    <div class="page-section__content space-y-6">
      <div class="space-y-2">
        <span class="chip">Центр водителя</span>
        <h1 class="section-heading">{{ title }}</h1>
        <p class="subheading">Следим за эфиром канала drivers и мгновенно отображаем заявки рядом с вашей локацией.</p>
      </div>

      <div v-if="!trip.id" class="surface-card p-10 flex items-center justify-center">
        <Loader />
      </div>

      <div v-else class="space-y-6">
        <div class="map-shell min-h-[320px]">
          <l-map
            v-if="mapReady && trip.destination"
            ref="map"
            :zoom="14"
            :center="destinationCenter"
            style="width:100%; height: 320px;"
            @ready="onMapReady"
          >
            <l-tile-layer
              url="https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png"
              attribution='&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
            />
            <l-marker
              v-if="trip.origin"
              :lat-lng="originArray"
            />
            <l-marker
              v-if="trip.destination"
              :lat-lng="destinationArray"
            />
            <l-polyline
              v-if="routeCoordinates.length > 0"
              :lat-lngs="routeCoordinates"
              color="#3b82f6"
              :weight="4"
            />
          </l-map>
        </div>
        <div class="glass-panel p-6 space-y-4">
          <p class="text-sm uppercase tracking-[0.4em] text-slate-300">Назначение пассажира</p>
          <p class="text-2xl font-display font-semibold text-white">{{ trip.destination_name }}</p>
          <div class="flex flex-wrap gap-4">
            <button class="secondary-btn flex-1 min-w-[180px]" @click="handleDeclineTrip">Отказать</button>
            <button class="primary-btn flex-1 min-w-[180px]" @click="handleAcceptTrip">Принять заявку</button>
          </div>
        </div>
      </div>
    </div>
    <div class="page-section__aside">
      <div class="surface-card p-8 space-y-6">
        <p class="text-sm uppercase tracking-[0.4em] text-slate-400">Протокол</p>
        <div class="timeline">
          <div class="timeline-step">
            <p class="text-base font-semibold">Получаем координаты пассажира</p>
            <p class="text-sm text-slate-300">Как только заявка приходит, карта обновляется автоматически.</p>
          </div>
          <div class="timeline-step">
            <p class="text-base font-semibold">Принять или отклонить</p>
            <p class="text-sm text-slate-300">Подтверждение отправляет пассажиру уведомление и строит маршрут.</p>
          </div>
          <div class="timeline-step">
            <p class="text-base font-semibold">Переход в Driving</p>
            <p class="text-sm text-slate-300">Принятый заказ передает контроль в экран движения.</p>
          </div>
        </div>
      </div>
    </div>
  </section>
</template>
<script setup>
import { ref, computed } from 'vue'
import Loader from '@/components/Loader.vue'
import { onMounted, onUnmounted } from 'vue'
import { useTripStore } from '@/stores/trip'
import { useLocationStore } from '@/stores/location'
import http from '@/helpers/http'
import { useRouter } from 'vue-router'
import { LMap, LTileLayer, LMarker, LPolyline } from '@vue-leaflet/vue-leaflet'
import L from 'leaflet'

const title = ref('Ожидание заявки на поездку...')
const map = ref(null)
const mapReady = ref(false)
const routeCoordinates = ref([])
const trip = useTripStore()
const location = useLocationStore()
const router = useRouter()

const destinationCenter = computed(() => {
  if (trip.destination && trip.destination.lat && trip.destination.lng) {
    return [trip.destination.lat, trip.destination.lng]
  }
  return [55.7558, 37.6173]
})

const originArray = computed(() => {
  if (trip.origin && trip.origin.lat && trip.origin.lng) {
    return [trip.origin.lat, trip.origin.lng]
  }
  return [55.7558, 37.6173]
})

const destinationArray = computed(() => {
  if (trip.destination && trip.destination.lat && trip.destination.lng) {
    return [trip.destination.lat, trip.destination.lng]
  }
  return [55.7558, 37.6173]
})

const handleDeclineTrip = () => {
  trip.reset()
  title.value = 'Ожидание заявки на поездку...'
}

const handleAcceptTrip = () => {
  http().post(`/api/trip/${trip.id}/accept`, {
    driver_location: location.current.geometry
  })
      .then((response) => {
        location.$patch({
          destination: {
            name: 'Пассажир',
            geometry: response.data.origin
          }
        })
        router.push({ name: 'driving' })
      })
      .catch((error) => {
        console.error(error)
      })
}

onMounted(async () => {
  console.log('StandbyView mounted - waiting for trips')
  await location.updateCurrentLocation()

  // ДОБАВЬТЕ ОТЛАДОЧНУЮ ИНФОРМАЦИЮ
  console.log('Echo available:', !!window.Echo)
  console.log('Subscribing to drivers channel')

  if (window.Echo) {
    window.Echo.channel('drivers')
        .listen('TripCreated', (e) => {
          console.log('✅ TripCreated event received:', e)
          title.value = 'Заявка на поездку:'
          trip.$patch(e.trip)
          console.log('Trip data after patch:', trip)

          setTimeout(initMapDirections, 2000)
        })
        .error((error) => {
          console.error('❌ Echo channel error:', error)
        })
  } else {
    console.error('❌ Echo is not available')
  }
})

onUnmounted(() => {
  if (window.Echo) {
    console.log('Leaving drivers channel')
    window.Echo.leave('drivers')
  }
})

const onMapReady = () => {
  mapReady.value = true
  if (trip.origin && trip.destination) {
    initMapDirections()
  }
}

const initMapDirections = async () => {
  if (!trip.origin || !trip.destination) {
    return
  }

  try {
    const start = trip.origin
    const end = trip.destination

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
</script>