<template>
  <section class="page-section">
    <div class="page-section__content space-y-6">
      <div class="space-y-2">
        <span class="chip">Монитор поездки</span>
        <h1 class="section-heading">{{ title }}</h1>
        <p class="subheading">{{ message }}</p>
      </div>
      <div class="map-shell min-h-[360px]">
        <l-map
          v-if="mapReady"
          ref="map"
          :zoom="14"
          :center="mapCenterArray"
          style="width:100%; height: 360px;"
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
            v-if="driverLocation.lat"
            :lat-lng="driverLocationArray"
            :icon="driverIconLeaflet"
          />
        </l-map>
      </div>
    </div>

    <div class="page-section__aside">
      <div class="surface-card p-8 space-y-6">
        <p class="text-sm uppercase tracking-[0.4em] text-slate-400">Детали</p>
        <div class="space-y-4 text-sm text-slate-300">
          <div class="rounded-2xl border border-white/10 bg-white/5 p-4">
            <p class="text-xs uppercase tracking-[0.4em] text-slate-400">Пассажир</p>
            <p class="text-base font-semibold text-white">Текущее устройство</p>
            <p>Координаты: {{ currentLocation.lat.toFixed(4) }}, {{ currentLocation.lng.toFixed(4) }}</p>
          </div>
          <div v-if="driverLocation.lat" class="rounded-2xl border border-white/10 bg-white/5 p-4">
            <p class="text-xs uppercase tracking-[0.4em] text-slate-400">Водитель</p>
            <p>Координаты: {{ driverLocation.lat.toFixed(4) }}, {{ driverLocation.lng.toFixed(4) }}</p>
          </div>
          <div class="rounded-2xl border border-white/10 bg-white/5 p-4">
            <p class="text-xs uppercase tracking-[0.4em] text-slate-400">Финиш</p>
            <p class="text-base font-semibold text-white">{{ trip.destination_name || 'Определяется' }}</p>
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
import { onMounted, ref, computed, onUnmounted } from 'vue'
import { LMap, LTileLayer, LMarker } from '@vue-leaflet/vue-leaflet'
import L from 'leaflet'

const location = useLocationStore()
const trip = useTripStore()
const router = useRouter()

const title = ref('Ожидание водителя...')
const message = ref('Когда водитель примет поездку, здесь появится его информация.')

const map = ref(null)
const mapReady = ref(false)

const currentIconLeaflet = L.icon({
  iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-blue.png',
  shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/images/marker-shadow.png',
  iconSize: [25, 41],
  iconAnchor: [12, 41],
})

const driverIconLeaflet = L.icon({
  iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-red.png',
  shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/images/marker-shadow.png',
  iconSize: [25, 41],
  iconAnchor: [12, 41],
})

// Исправленные вычисляемые свойства
const currentLocation = computed(() => {
  if (location.current?.geometry) {
    return {
      lat: Number(location.current.geometry.lat),
      lng: Number(location.current.geometry.lng)
    }
  }
  return { lat: 0, lng: 0 }
})

const driverLocation = computed(() => {
  if (trip.driver_location && trip.driver_location.lat && trip.driver_location.lng) {
    return {
      lat: Number(trip.driver_location.lat),
      lng: Number(trip.driver_location.lng)
    }
  }
  return { lat: 0, lng: 0 }
})

const mapCenterArray = computed(() => {
  if (currentLocation.value.lat && currentLocation.value.lng) {
    return [currentLocation.value.lat, currentLocation.value.lng]
  }
  return [55.7558, 37.6173] // Москва по умолчанию
})

const currentLocationArray = computed(() => {
  return [currentLocation.value.lat, currentLocation.value.lng]
})

const driverLocationArray = computed(() => {
  return [driverLocation.value.lat, driverLocation.value.lng]
})

const onMapReady = () => {
  mapReady.value = true
  updateMapBounds()
}

const updateMapBounds = () => {
  if (!map.value || !currentLocation.value.lat || !driverLocation.value.lat) {
    return
  }

  try {
    if (map.value.leafletObject) {
      const bounds = L.latLngBounds([
        [currentLocation.value.lat, currentLocation.value.lng],
        [driverLocation.value.lat, driverLocation.value.lng]
      ])
      map.value.leafletObject.fitBounds(bounds)
    }
  } catch (error) {
    console.error('Ошибка обновления границ карты:', error)
  }
}

onMounted(() => {
  console.log('TripView mounted')
  console.log('Current location:', location.current)
  console.log('Trip data:', trip)

  // Карта инициализируется через @ready событие

  // ИСПРАВЛЕНО: Используем глобальный Echo вместо создания нового
  if (window.Echo) {
    window.Echo.channel(`passenger_${trip.user_id}`)
        .listen('TripAccepted', (e) => {
          console.log('Trip accepted:', e)
          trip.$patch(e.trip)

          title.value = "Водитель в пути!"
          message.value = `${e.trip.driver.user.name} едет на ${e.trip.driver.year} ${e.trip.driver.color} ${e.trip.driver.make} ${e.trip.driver.model} с номером ${e.trip.driver.license_plate}`

          setTimeout(updateMapBounds, 1000)
        })
        .listen('TripLocationUpdated', (e) => {
          console.log('Trip location updated:', e)
          trip.$patch(e.trip)
          setTimeout(updateMapBounds, 1000)
        })
        .listen('TripStarted', (e) => {
          console.log('Trip started:', e)
          trip.$patch(e.trip)

          // Обновляем локацию
          if (e.trip.destination) {
            location.$patch({
              current: {
                geometry: {
                  lat: Number(e.trip.destination.latitude),
                  lng: Number(e.trip.destination.longitude)
                }
              }
            })
          }

          title.value = "Вы в пути!"
          message.value = `Вы направляетесь к ${e.trip.destination_name}`
        })
        .listen('TripEnded', (e) => {
          console.log('Trip ended:', e)
          trip.$patch(e.trip)

          title.value = "Вы прибыли!"
          message.value = `Надеемся, вам понравилась поездка с ${e.trip.driver.user.name}`

          setTimeout(() => {
            trip.reset()
            location.reset()

            router.push({
              name: 'landing'
            })
          }, 10000)
        })
  } else {
    console.error('Echo is not initialized. Make sure Echo is properly set up in app.js')
  }
})

onUnmounted(() => {
  // Отписываемся от канала при размонтировании компонента
  if (window.Echo && trip.user_id) {
    window.Echo.leave(`passenger_${trip.user_id}`)
  }
})
</script>