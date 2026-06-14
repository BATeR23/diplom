<template>
  <section class="page-section">
    <div class="page-section__content space-y-6">
      <span class="chip w-fit">Автопарк</span>
      <h1 class="section-heading">Ваши автомобили</h1>
      <p class="subheading">
        Добавьте машину, чтобы пассажиры видели модель, количество мест и уровень комфорта. Вы можете скрыть или удалить авто в любой момент.
      </p>
      <form class="surface-card space-y-4 p-6" @submit.prevent="handleSubmit" enctype="multipart/form-data">
        <div class="grid gap-4 md:grid-cols-2">
          <div class="form-field">
            <label for="make">Марка</label>
            <input id="make" v-model="form.make" type="text" placeholder="Toyota" required />
          </div>
          <div class="form-field">
            <label for="model">Модель</label>
            <input id="model" v-model="form.model" type="text" placeholder="Camry" required />
          </div>
        </div>
        <div class="grid gap-4 md:grid-cols-2">
          <div class="form-field">
            <label for="year">Год</label>
            <input id="year" v-model.number="form.year" type="number" min="1980" :max="new Date().getFullYear() + 1" />
          </div>
          <div class="form-field">
            <label for="seats">Мест</label>
            <input id="seats" v-model.number="form.seats" type="number" min="1" max="8" required />
          </div>
        </div>
        <div class="grid gap-4 md:grid-cols-2">
          <div class="form-field">
            <label for="color">Цвет</label>
            <input id="color" v-model="form.color" type="text" placeholder="Белый" />
          </div>
          <div class="form-field">
            <label for="plate">Номер</label>
            <input id="plate" v-model="form.plate_number" type="text" placeholder="А123БВ 777" />
          </div>
        </div>
        <div class="form-field">
          <label for="comfort">Класс комфорта</label>
          <select id="comfort" v-model="form.comfort_class" class="w-full rounded-lg border border-gray-300 bg-white px-4 py-3 text-gray-900">
            <option value="standard">Standard</option>
            <option value="comfort">Comfort</option>
            <option value="premium">Premium</option>
          </select>
        </div>
        <div class="flex flex-wrap gap-4 text-sm text-gray-700">
          <label class="flex items-center gap-2 cursor-pointer">
            <input type="checkbox" v-model="form.allows_pets" class="cursor-pointer" />
            Разрешены животные
          </label>
          <label class="flex items-center gap-2 cursor-pointer">
            <input type="checkbox" v-model="form.allows_smoking" class="cursor-pointer" />
            Разрешено курение
          </label>
        </div>
        <div v-if="!editingVehicle" class="space-y-4 pt-4 border-t border-white/10">
          <p class="text-sm text-gray-900 font-semibold">Документы для подтверждения</p>
          <div class="grid gap-4 md:grid-cols-2">
            <div class="form-field">
              <label for="ownership_document">Документ о владении ТС</label>
              <input
                id="ownership_document"
                type="file"
                accept=".jpg,.jpeg,.png,.pdf"
                @change="handleFileChange('ownership', $event)"
                required
                class="block w-full text-sm text-gray-600 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100"
              />
              <p class="text-xs text-gray-500 mt-1">JPG, PNG или PDF, макс. 10MB</p>
            </div>
            <div class="form-field">
              <label for="license_document">Водительские права</label>
              <input
                id="license_document"
                type="file"
                accept=".jpg,.jpeg,.png,.pdf"
                @change="handleFileChange('license', $event)"
                required
                class="block w-full text-sm text-gray-600 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100"
              />
              <p class="text-xs text-gray-500 mt-1">JPG, PNG или PDF, макс. 10MB</p>
            </div>
          </div>
          <p class="text-xs text-yellow-600 bg-yellow-50 p-2 rounded">После загрузки документов администратор проверит их и подтвердит автомобиль.</p>
        </div>
        <button type="submit" class="primary-btn w-full sm:w-auto" :disabled="saving">
          {{ editingVehicle ? 'Сохранить изменения' : 'Добавить авто' }}
        </button>
      </form>

      <div class="space-y-4">
        <h2 class="text-lg font-semibold text-white">Сохранённые автомобили</h2>
        <div v-if="vehicles.length" class="grid gap-4 md:grid-cols-2">
          <article
            v-for="vehicle in vehicles"
            :key="vehicle.id"
            class="surface-card p-5 space-y-3 border border-gray-200"
          >
            <p class="text-xl font-semibold text-gray-900">{{ vehicle.make }} {{ vehicle.model }}</p>
            <p class="text-sm text-gray-600">{{ vehicle.year }} · {{ vehicle.seats }} мест · {{ vehicle.color || 'цвет не указан' }}</p>
            <p class="text-sm text-gray-600">Класс: {{ vehicle.comfort_class }} · Номер: {{ vehicle.plate_number || '—' }}</p>
            <p class="text-sm text-gray-600">Опции: {{ formatFeatures(vehicle) }}</p>
            <div class="pt-2 border-t border-gray-200">
              <span :class="[
                'chip text-xs',
                vehicle.verification_status === 'approved' ? 'bg-emerald-100 text-emerald-700 border-emerald-200' :
                vehicle.verification_status === 'rejected' ? 'bg-red-100 text-red-700 border-red-200' :
                'bg-yellow-100 text-yellow-700 border-yellow-200'
              ]">
                {{ getVerificationStatusLabel(vehicle.verification_status) }}
              </span>
            </div>
            <div class="flex gap-3 pt-2">
              <button class="secondary-btn flex-1" type="button" @click="startEdit(vehicle)">Редактировать</button>
              <button class="secondary-btn flex-1 text-red-600 border-red-300 hover:bg-red-50" type="button" @click="handleDelete(vehicle.id)">
                Удалить
              </button>
            </div>
          </article>
        </div>
        <p v-else class="text-sm text-gray-600 text-center py-8">Добавьте первый автомобиль, чтобы создавать поездки.</p>
      </div>
    </div>
  </section>
</template>

<script setup>
import { reactive, ref, onMounted } from 'vue'
import http from '@/helpers/http'

const vehicles = ref([])
const saving = ref(false)
const editingVehicle = ref(null)
const ownershipFile = ref(null)
const licenseFile = ref(null)

const formDefaults = {
  make: '',
  model: '',
  year: new Date().getFullYear(),
  seats: 4,
  color: '',
  plate_number: '',
  comfort_class: 'standard',
  allows_pets: false,
  allows_smoking: false,
}

const form = reactive({ ...formDefaults })

const handleFileChange = (type, event) => {
  const file = event.target.files[0]
  if (file) {
    if (file.size > 10 * 1024 * 1024) {
      alert('Размер файла не должен превышать 10MB')
      event.target.value = ''
      return
    }
    if (type === 'ownership') {
      ownershipFile.value = file
    } else if (type === 'license') {
      licenseFile.value = file
    }
  }
}

const resetForm = () => {
  Object.assign(form, formDefaults)
  editingVehicle.value = null
  ownershipFile.value = null
  licenseFile.value = null
}

const fetchVehicles = async () => {
  const { data } = await http.get('/vehicles')
  vehicles.value = data
}

const handleSubmit = async () => {
  saving.value = true
  try {
    if (editingVehicle.value) {
      await http.put(`/vehicles/${editingVehicle.value.id}`, form)
    } else {
      // Для нового автомобиля отправляем FormData с файлами
      const formData = new FormData()
      
      // Добавляем обязательные поля
      formData.append('make', form.make)
      formData.append('model', form.model)
      formData.append('seats', form.seats)
      formData.append('comfort_class', form.comfort_class)
      
      // Добавляем необязательные поля только если они заполнены
      if (form.year) {
        formData.append('year', form.year)
      }
      if (form.color) {
        formData.append('color', form.color)
      }
      if (form.plate_number) {
        formData.append('plate_number', form.plate_number)
      }
      
      // Boolean значения передаем как строки '1' или '0'
      formData.append('allows_pets', form.allows_pets ? '1' : '0')
      formData.append('allows_smoking', form.allows_smoking ? '1' : '0')
      
      // Добавляем файлы
      if (ownershipFile.value) {
        formData.append('ownership_document', ownershipFile.value)
      }
      if (licenseFile.value) {
        formData.append('license_document', licenseFile.value)
      }

      await http.post('/vehicles', formData, {
        headers: {
          'Content-Type': 'multipart/form-data',
        },
      })
    }
    await fetchVehicles()
    resetForm()
    ownershipFile.value = null
    licenseFile.value = null
    document.getElementById('ownership_document')?.value && (document.getElementById('ownership_document').value = '')
    document.getElementById('license_document')?.value && (document.getElementById('license_document').value = '')
  } catch (error) {
    console.error('Ошибка сохранения автомобиля:', error.response?.data)
    const errorMessage = error.response?.data?.message || 
                        (error.response?.data?.errors ? JSON.stringify(error.response.data.errors) : 'Ошибка сохранения автомобиля')
    alert(errorMessage)
  } finally {
    saving.value = false
  }
}

const startEdit = (vehicle) => {
  editingVehicle.value = vehicle
  Object.assign(form, {
    make: vehicle.make,
    model: vehicle.model,
    year: vehicle.year,
    seats: vehicle.seats,
    color: vehicle.color,
    plate_number: vehicle.plate_number,
    comfort_class: vehicle.comfort_class,
    allows_pets: vehicle.allows_pets,
    allows_smoking: vehicle.allows_smoking,
  })
}

const handleDelete = async (id) => {
  await http.delete(`/vehicles/${id}`)
  if (editingVehicle.value?.id === id) {
    resetForm()
  }
  await fetchVehicles()
}

const formatFeatures = (vehicle) => {
  const features = []
  if (vehicle.allows_pets) features.push('животные ок')
  if (vehicle.allows_smoking) features.push('курение разрешено')
  return features.length ? features.join(', ') : 'стандартные'
}

const getVerificationStatusLabel = (status) => {
  const labels = {
    pending: 'Ожидает подтверждения',
    approved: 'Подтвержден',
    rejected: 'Отклонен'
  }
  return labels[status] || status
}

onMounted(fetchVehicles)
</script>










