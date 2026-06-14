<template>
  <section class="page-section">
    <div class="page-section__content space-y-6">
      <span class="chip w-fit">Редактирование профиля</span>
      <h1 class="section-heading">Настройки профиля</h1>
      <p class="subheading">
        Измените информацию о себе.
      </p>

      <div class="surface-card p-8 space-y-6">
        <form @submit.prevent="saveProfile" class="space-y-6" enctype="multipart/form-data">
          <div class="form-field">
            <label>Аватар</label>
            <div class="flex items-center gap-4">
              <div v-if="avatarPreview || auth.user?.avatar_url" class="h-20 w-20 rounded-full overflow-hidden bg-gray-200">
                <img
                  :src="avatarPreview || getAvatarUrl(auth.user?.avatar_url)"
                  alt="Аватар"
                  class="w-full h-full object-cover"
                />
              </div>
              <div v-else class="h-20 w-20 rounded-full bg-gray-200 flex items-center justify-center text-gray-400">
                <span class="text-2xl">{{ auth.user?.name?.slice(0, 1) }}</span>
              </div>
              <div class="flex-1">
                <input
                  id="avatar"
                  type="file"
                  accept="image/jpeg,image/jpg,image/png"
                  @change="handleAvatarChange"
                  class="block w-full text-sm text-gray-600 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100"
                />
                <p class="text-xs text-gray-500 mt-1">Максимальный размер: 5MB. Форматы: JPG, PNG</p>
              </div>
            </div>
          </div>

          <div class="form-field">
            <label for="name">Имя</label>
            <input
              id="name"
              v-model="profileForm.name"
              type="text"
              required
            />
          </div>

          <div class="form-field">
            <label for="email">Email</label>
            <input
              id="email"
              v-model="profileForm.email"
              type="email"
              required
            />
          </div>

          <div class="form-field">
            <label for="bio">О себе</label>
            <textarea
              id="bio"
              v-model="profileForm.bio"
              rows="4"
              placeholder="Расскажите о себе..."
            ></textarea>
          </div>

          <div class="form-field">
            <label for="password">Новый пароль (оставьте пустым, чтобы не менять)</label>
            <input
              id="password"
              v-model="profileForm.password"
              type="password"
              placeholder="********"
            />
          </div>

          <div class="flex gap-4">
            <button type="submit" class="primary-btn" :disabled="saving">
              {{ saving ? 'Сохранение...' : 'Сохранить изменения' }}
            </button>
            <RouterLink :to="{ name: 'profile' }" class="secondary-btn">
              Отмена
            </RouterLink>
          </div>
        </form>
      </div>
    </div>
  </section>
</template>

<script setup>
import { reactive, ref, onMounted } from 'vue'
import { RouterLink, useRouter } from 'vue-router'
import http from '@/helpers/http'
import { useAuthStore } from '@/stores/auth'

const router = useRouter()
const auth = useAuthStore()
const saving = ref(false)
const avatarFile = ref(null)
const avatarPreview = ref(null)

const profileForm = reactive({
  name: '',
  email: '',
  bio: '',
  password: '',
})

const getAvatarUrl = (path) => {
  if (!path) return null
  const baseUrl = import.meta.env.VITE_API_BASE_URL || 'http://127.0.0.1:8000'
  return `${baseUrl}/storage/${path}`
}

const handleAvatarChange = (event) => {
  const file = event.target.files[0]
  if (file) {
    if (file.size > 5 * 1024 * 1024) {
      alert('Размер файла не должен превышать 5MB')
      event.target.value = ''
      return
    }
    avatarFile.value = file
    const reader = new FileReader()
    reader.onload = (e) => {
      avatarPreview.value = e.target.result
    }
    reader.readAsDataURL(file)
  }
}

const loadProfile = async () => {
  try {
    const { data } = await http.get('/auth/me')
    profileForm.name = data.name || ''
    profileForm.email = data.email || ''
    profileForm.bio = data.bio || ''
  } catch (error) {
    console.error('Error loading profile:', error)
  }
}

const saveProfile = async () => {
  saving.value = true
  try {
    const formData = new FormData()
    formData.append('name', profileForm.name)
    formData.append('email', profileForm.email)
    if (profileForm.bio) {
      formData.append('bio', profileForm.bio)
    }
    if (profileForm.password) {
      formData.append('password', profileForm.password)
    }
    if (avatarFile.value) {
      formData.append('avatar', avatarFile.value)
    }

    await http.put('/auth/profile', formData, {
      headers: {
        'Content-Type': 'multipart/form-data',
      },
    })
    await auth.bootstrap() // Refresh user data
    router.push({ name: 'profile' })
  } catch (error) {
    alert(error.response?.data?.message || 'Ошибка сохранения профиля')
  } finally {
    saving.value = false
  }
}

onMounted(() => {
  loadProfile()
})
</script>










