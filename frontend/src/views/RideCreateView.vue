<template>
  <section class="page-section">
    <div class="page-section__content space-y-6">
      <span class="chip w-fit">Водитель</span>
      <h1 class="section-heading">Опубликуйте поездку и найдите попутчиков</h1>
      <p class="subheading max-w-2xl">
        Укажите маршрут, время выезда, цену за место и выберите автомобиль. Мы покажем объявление пассажирам и уведомим вас о заявках.
      </p>
      <form class="glass-panel space-y-5 p-6" @submit.prevent="handleSubmit">
        <div class="grid gap-4 md:grid-cols-2">
          <div class="form-field">
            <label for="origin_city">Город отправления</label>
            <input id="origin_city" v-model="form.origin_city" type="text" required />
          </div>
          <div class="form-field">
            <label for="destination_city">Город назначения</label>
            <input id="destination_city" v-model="form.destination_city" type="text" required />
          </div>
        </div>
        <div class="grid gap-4 md:grid-cols-2">
          <div class="form-field">
            <label for="origin_address">Адрес отправления <span class="text-rose-600">*</span></label>
            <input
              id="origin_address"
              v-model="form.origin_address"
              type="text"
              required
              placeholder="Введите адрес для поиска"
              class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
              :class="{ 'border-rose-500': form.origin_address && !isValidOriginAddress, 'border-gray-300': !form.origin_address || isValidOriginAddress }"
              @input="searchAddress('origin', form.origin_city, form.origin_address)"
              @focus="showOriginResults = true"
              @blur="setTimeout(() => { showOriginResults = false }, 200)"
            />
            <div v-if="originSearchResults.length > 0 && showOriginResults" class="mt-2 bg-white rounded-lg shadow-lg max-h-60 overflow-y-auto border border-gray-200 z-10 relative">
              <div
                v-for="(result, index) in originSearchResults"
                :key="index"
                class="p-3 hover:bg-blue-50 cursor-pointer border-b border-gray-200 last:border-b-0 transition-colors"
                @mousedown.prevent="selectAddress('origin', result)"
              >
                <p class="font-semibold text-gray-900">{{ result.display_name.split(',')[0] }}</p>
                <p class="text-sm text-gray-600">{{ result.display_name }}</p>
                <p v-if="result.address" class="text-xs text-gray-500 mt-1">
                  {{ result.address.city || result.address.town || result.address.village || '' }}
                  {{ result.address.state || result.address.region || '' }}
                </p>
              </div>
            </div>
            <div v-else-if="form.origin_address && form.origin_address.length >= 2 && showOriginResults && originSearchResults.length === 0" class="mt-2 p-3 bg-yellow-50 border border-yellow-200 rounded-lg">
              <p class="text-sm text-yellow-800">Адреса не найдены. Попробуйте изменить запрос.</p>
            </div>
            <p v-if="form.origin_address && !isValidOriginAddress && !showOriginResults" class="mt-1 text-sm text-rose-600">
              Выберите адрес из предложенных вариантов
            </p>
            <p v-else-if="!form.origin_address" class="mt-1 text-xs text-gray-500">
              Введите адрес и выберите из предложенных вариантов
            </p>
          </div>
          <div class="form-field">
            <label for="destination_address">Адрес назначения <span class="text-rose-600">*</span></label>
            <input
              id="destination_address"
              v-model="form.destination_address"
              type="text"
              required
              placeholder="Введите адрес для поиска"
              class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
              :class="{ 'border-rose-500': form.destination_address && !isValidDestinationAddress, 'border-gray-300': !form.destination_address || isValidDestinationAddress }"
              @input="searchAddress('destination', form.destination_city, form.destination_address)"
              @focus="showDestinationResults = true"
              @blur="setTimeout(() => { showDestinationResults = false }, 200)"
            />
            <div v-if="destinationSearchResults.length > 0 && showDestinationResults" class="mt-2 bg-white rounded-lg shadow-lg max-h-60 overflow-y-auto border border-gray-200 z-10 relative">
              <div
                v-for="(result, index) in destinationSearchResults"
                :key="index"
                class="p-3 hover:bg-blue-50 cursor-pointer border-b border-gray-200 last:border-b-0 transition-colors"
                @mousedown.prevent="selectAddress('destination', result)"
              >
                <p class="font-semibold text-gray-900">{{ result.display_name.split(',')[0] }}</p>
                <p class="text-sm text-gray-600">{{ result.display_name }}</p>
                <p v-if="result.address" class="text-xs text-gray-500 mt-1">
                  {{ result.address.city || result.address.town || result.address.village || '' }}
                  {{ result.address.state || result.address.region || '' }}
                </p>
              </div>
            </div>
            <div v-else-if="form.destination_address && form.destination_address.length >= 2 && showDestinationResults && destinationSearchResults.length === 0" class="mt-2 p-3 bg-yellow-50 border border-yellow-200 rounded-lg">
              <p class="text-sm text-yellow-800">Адреса не найдены. Попробуйте изменить запрос.</p>
            </div>
            <p v-if="form.destination_address && !isValidDestinationAddress && !showDestinationResults" class="mt-1 text-sm text-rose-600">
              Выберите адрес из предложенных вариантов
            </p>
            <p v-else-if="!form.destination_address" class="mt-1 text-xs text-gray-500">
              Введите адрес и выберите из предложенных вариантов
            </p>
          </div>
        </div>
        <div class="grid gap-4 md:grid-cols-2">
          <div class="form-field">
            <label for="departure_time">Дата и время выезда</label>
            <input id="departure_time" v-model="form.departure_time" type="datetime-local" required />
          </div>
          <div class="form-field">
            <label for="price">Цена за место (₽)</label>
            <input id="price" v-model.number="form.price" type="number" min="0" required />
          </div>
        </div>
        <div class="grid gap-4 md:grid-cols-2">
          <div class="form-field">
            <label for="seats_total">Количество мест</label>
            <input id="seats_total" v-model.number="form.seats_total" type="number" min="1" max="8" required />
          </div>
          <div class="form-field">
            <label for="vehicle_id">Автомобиль</label>
            <select id="vehicle_id" v-model="form.vehicle_id" :disabled="approvedVehicles.length === 0">
              <option value="">Без указания</option>
              <option v-for="vehicle in approvedVehicles" :key="vehicle.id" :value="vehicle.id">
                {{ vehicle.make }} {{ vehicle.model }} · {{ vehicle.seats }} мест
              </option>
            </select>
            <p v-if="approvedVehicles.length === 0" class="text-sm text-rose-600 mt-1">
              У вас нет подтвержденных автомобилей. Добавьте автомобиль и дождитесь подтверждения администратором или менеджером.
            </p>
            <p v-else-if="pendingVehicles.length > 0" class="text-sm text-yellow-600 mt-1">
              У вас {{ pendingVehicles.length }} автомобиль(ей) ожидает подтверждения.
            </p>
          </div>
        </div>
        <div class="grid gap-4 md:grid-cols-2">
          <div class="form-field">
            <label for="luggage_size">Размер багажа</label>
            <select id="luggage_size" v-model="form.luggage_size">
              <option value="small">Ручная кладь</option>
              <option value="medium">1 чемодан</option>
              <option value="large">Много места</option>
            </select>
          </div>
          <div class="form-field flex items-center gap-4">
            <label class="flex items-center gap-2 text-sm text-gray-700">
              <input type="checkbox" v-model="form.pets_allowed" />
              Можно с животными
            </label>
            <label class="flex items-center gap-2 text-sm text-gray-700">
              <input type="checkbox" v-model="form.smoking_allowed" />
              Разрешено курение
            </label>
          </div>
        </div>
        <div class="form-field">
          <label for="notes">Детали поездки</label>
          <textarea
            id="notes"
            v-model="form.notes"
            rows="4"
            placeholder="Расскажите о остановках, музыке, скорости и т.д."
          />
        </div>
        <p v-if="errorMessage" class="text-sm text-rose-600">{{ errorMessage }}</p>
        <p v-if="successMessage" class="text-sm text-emerald-600">{{ successMessage }}</p>
        <button 
          type="submit" 
          class="primary-btn w-full disabled:opacity-60" 
          :disabled="loading || !isValidOriginAddress || !isValidDestinationAddress"
        >
          {{ loading ? 'Публикуем…' : 'Опубликовать поездку' }}
        </button>
      </form>
    </div>
    <div class="page-section__aside">
      <div class="surface-card space-y-4 p-8">
        <h3 class="text-lg font-semibold text-gray-900">Советы по безопасным поездкам</h3>
        <ul class="space-y-3 text-sm text-gray-600">
          <li>• Подтверждайте бронирования только после общения в чате.</li>
          <li>• Обновляйте статус поездки, чтобы пассажиры видели реальное время прибытия.</li>
          <li>• Оставляйте отзывы — так растёт ваш рейтинг и доверие сообщества.</li>
        </ul>
      </div>
    </div>
  </section>
</template>

<script setup>
import { onMounted, onUnmounted, reactive, ref, computed } from 'vue'
import http from '@/helpers/http'

const vehicles = ref([])

const approvedVehicles = computed(() => {
  return vehicles.value.filter(v => v.verification_status === 'approved')
})

const pendingVehicles = computed(() => {
  return vehicles.value.filter(v => v.verification_status === 'pending')
})
const loading = ref(false)
const errorMessage = ref('')
const successMessage = ref('')
const originSearchResults = ref([])
const destinationSearchResults = ref([])
const showOriginResults = ref(false)
const showDestinationResults = ref(false)
const selectedOriginAddress = ref(null) // Храним выбранный адрес из результатов поиска
const selectedDestinationAddress = ref(null) // Храним выбранный адрес из результатов поиска

// Проверка, что выбранный адрес отправления из предложенных вариантов
const isValidOriginAddress = computed(() => {
  if (!form.origin_address || !selectedOriginAddress.value) return false
  return selectedOriginAddress.value.display_name === form.origin_address ||
         originSearchResults.value.some(addr => addr.display_name === form.origin_address)
})

// Проверка, что выбранный адрес назначения из предложенных вариантов
const isValidDestinationAddress = computed(() => {
  if (!form.destination_address || !selectedDestinationAddress.value) return false
  return selectedDestinationAddress.value.display_name === form.destination_address ||
         destinationSearchResults.value.some(addr => addr.display_name === form.destination_address)
})

const form = reactive({
  origin_city: '',
  origin_address: '',
  destination_city: '',
  destination_address: '',
  departure_time: '',
  price: 50,
  seats_total: 3,
  vehicle_id: '',
  luggage_size: 'medium',
  pets_allowed: false,
  smoking_allowed: false,
  notes: '',
})

const handleSubmit = async () => {
  // Валидация адресов
  if (!isValidOriginAddress.value) {
    errorMessage.value = 'Пожалуйста, выберите адрес отправления из предложенных вариантов.'
    return
  }
  
  if (!isValidDestinationAddress.value) {
    errorMessage.value = 'Пожалуйста, выберите адрес назначения из предложенных вариантов.'
    return
  }

  // Дополнительная проверка адресов перед отправкой
  if (selectedOriginAddress.value) {
    const originValid = await verifyAddress(selectedOriginAddress.value)
    if (!originValid) {
      errorMessage.value = 'Адрес отправления не может быть использован. Пожалуйста, выберите другой адрес.'
      return
    }
  }

  if (selectedDestinationAddress.value) {
    const destinationValid = await verifyAddress(selectedDestinationAddress.value)
    if (!destinationValid) {
      errorMessage.value = 'Адрес назначения не может быть использован. Пожалуйста, выберите другой адрес.'
      return
    }
  }

  // Проверяем наличие подтвержденных ТС
  if (approvedVehicles.value.length === 0) {
    errorMessage.value = 'Для создания поездки необходимо иметь хотя бы одно подтвержденное транспортное средство. Пожалуйста, добавьте автомобиль и дождитесь его подтверждения администратором или менеджером.'
    return
  }

  loading.value = true
  errorMessage.value = ''
  successMessage.value = ''

  try {
    // Подготавливаем данные с координатами
    const rideData = {
      ...form,
      vehicle_id: form.vehicle_id || null,
    }

    // Добавляем координаты, если они есть
    if (selectedOriginAddress.value) {
      rideData.origin_lat = parseFloat(selectedOriginAddress.value.lat)
      rideData.origin_lng = parseFloat(selectedOriginAddress.value.lon)
    }

    if (selectedDestinationAddress.value) {
      rideData.destination_lat = parseFloat(selectedDestinationAddress.value.lat)
      rideData.destination_lng = parseFloat(selectedDestinationAddress.value.lon)
    }

    await http.post('/rides', rideData)
    successMessage.value = 'Поездка опубликована! Пассажиры уже видят её в поиске.'
    // Очищаем форму после успешной отправки
    form.origin_city = ''
    form.origin_address = ''
    form.destination_city = ''
    form.destination_address = ''
    form.departure_time = ''
    form.price = 50
    form.seats_total = 3
    form.vehicle_id = ''
    form.luggage_size = 'medium'
    form.pets_allowed = false
    form.smoking_allowed = false
    form.notes = ''
  } catch (error) {
    errorMessage.value = error.response?.data?.message || 'Не удалось создать поездку.'
  } finally {
    loading.value = false
  }
}

const fetchVehicles = async () => {
  try {
    const { data } = await http.get('/vehicles')
    vehicles.value = data
  } catch (error) {
    // ignore optional error
  }
}

// Проверка, что адрес корректно геокодируется
const verifyAddress = async (addressData) => {
  try {
    // Проверяем, что у адреса есть координаты
    if (!addressData.lat || !addressData.lon) {
      return false
    }

    // Дополнительная проверка через обратное геокодирование
    const lat = parseFloat(addressData.lat)
    const lon = parseFloat(addressData.lon)
    
    if (isNaN(lat) || isNaN(lon)) {
      return false
    }

    // Проверяем, что координаты в разумных пределах (для России примерно)
    if (lat < 41 || lat > 82 || lon < 19 || lon > 180) {
      return false
    }

    return true
  } catch (error) {
    console.error('Ошибка проверки адреса:', error)
    return false
  }
}

const searchAddress = async (type, city, address) => {
  // Сбрасываем выбранный адрес при изменении текста
  if (type === 'origin') {
    selectedOriginAddress.value = null
  } else {
    selectedDestinationAddress.value = null
  }

  if (!address || address.length < 2) {
    if (type === 'origin') {
      originSearchResults.value = []
    } else {
      destinationSearchResults.value = []
    }
    return
  }

  try {
    // Формируем запрос: если указан город, добавляем его, иначе ищем только по адресу
    const query = city ? `${city}, ${address}` : address
    const response = await fetch(
      `https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(query)}&limit=15&addressdetails=1&extratags=1`
    )
    
    if (!response.ok) {
      throw new Error('Ошибка запроса к Nominatim')
    }
    
    const data = await response.json()

    if (!data || !Array.isArray(data)) {
      if (type === 'origin') {
        originSearchResults.value = []
      } else {
        destinationSearchResults.value = []
      }
      return
    }

    // Фильтруем и проверяем адреса
    const verifiedAddresses = []
    for (const addr of data) {
      const isValid = await verifyAddress(addr)
      if (isValid) {
        verifiedAddresses.push(addr)
      }
      // Ограничиваем количество проверенных адресов для производительности
      if (verifiedAddresses.length >= 8) {
        break
      }
    }

    if (type === 'origin') {
      originSearchResults.value = verifiedAddresses
    } else {
      destinationSearchResults.value = verifiedAddresses
    }
  } catch (error) {
    console.error('Ошибка поиска адреса:', error)
    if (type === 'origin') {
      originSearchResults.value = []
    } else {
      destinationSearchResults.value = []
    }
  }
}

const selectAddress = async (type, result) => {
  // Проверяем адрес перед выбором
  const isValid = await verifyAddress(result)
  
  if (!isValid) {
    errorMessage.value = 'Выбранный адрес не может быть использован. Пожалуйста, выберите другой адрес из списка.'
    return
  }

  if (type === 'origin') {
    // Сохраняем полный адрес для валидации
    form.origin_address = result.display_name
    selectedOriginAddress.value = result
    originSearchResults.value = []
    showOriginResults.value = false
    errorMessage.value = '' // Очищаем ошибку при успешном выборе
  } else {
    // Сохраняем полный адрес для валидации
    form.destination_address = result.display_name
    selectedDestinationAddress.value = result
    destinationSearchResults.value = []
    showDestinationResults.value = false
    errorMessage.value = '' // Очищаем ошибку при успешном выборе
  }
}

// Закрываем результаты при клике вне
const handleClickOutside = (event) => {
  if (!event.target.closest('.form-field')) {
    showOriginResults.value = false
    showDestinationResults.value = false
  }
}

onMounted(() => {
  fetchVehicles()
  document.addEventListener('click', handleClickOutside)
})

onUnmounted(() => {
  document.removeEventListener('click', handleClickOutside)
})
</script>

