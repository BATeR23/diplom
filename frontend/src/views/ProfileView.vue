<template>
  <section class="page-section">
    <div class="page-section__content space-y-6">
      <span class="chip w-fit">Личный кабинет</span>
      <h1 class="section-heading">Профиль пользователя</h1>
      <p class="subheading">
        Краткая информация о вашем аккаунте и статистика.
      </p>

      <div class="surface-card space-y-6 p-8">
        <div class="flex items-center gap-6">
          <div v-if="auth.user?.avatar_url" class="h-20 w-20 rounded-2xl overflow-hidden bg-gray-200">
            <img
              :src="getAvatarUrl(auth.user.avatar_url)"
              alt="Аватар"
              class="w-full h-full object-cover"
            />
          </div>
          <div v-else class="h-20 w-20 rounded-2xl bg-blue-100 grid place-items-center text-2xl font-semibold text-blue-700">
            {{ auth.user?.name?.slice(0, 1) }}
          </div>
          <div>
            <p class="text-2xl font-semibold text-gray-900">{{ auth.user?.name }}</p>
            <p class="text-sm text-gray-600">{{ auth.user?.email }}</p>
          </div>
        </div>

        <div class="grid gap-6 sm:grid-cols-4 pt-6 border-t border-gray-200">
          <div class="text-center">
            <p class="text-3xl font-semibold text-gray-900">{{ auth.user?.rating_average ?? '—' }}</p>
            <p class="text-sm text-gray-600 mt-1">Рейтинг</p>
          </div>
          <div class="text-center">
            <p class="text-3xl font-semibold text-gray-900">{{ auth.user?.rides_completed ?? 0 }}</p>
            <p class="text-sm text-gray-600 mt-1">Завершено поездок</p>
          </div>
          <div class="text-center">
            <p class="text-3xl font-semibold text-gray-900">{{ formatBalance(auth.user?.balance) }}</p>
            <p class="text-sm text-gray-600 mt-1">Баланс</p>
          </div>
          <div class="text-center">
            <p class="text-3xl font-semibold text-gray-900">{{ getRoleLabel(auth.user?.role) }}</p>
            <p class="text-sm text-gray-600 mt-1">Роль</p>
          </div>
        </div>
      </div>

      <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
        <RouterLink :to="{ name: 'balance' }" class="surface-card p-6 hover:shadow-lg transition">
          <div class="text-center space-y-2">
            <h3 class="text-lg font-semibold text-gray-900">Баланс</h3>
            <p class="text-sm text-gray-600">Пополнение и история</p>
          </div>
        </RouterLink>

        <RouterLink :to="{ name: 'my-rides' }" class="surface-card p-6 hover:shadow-lg transition">
          <div class="text-center space-y-2">
            <h3 class="text-lg font-semibold text-gray-900">Мои поездки</h3>
            <p class="text-sm text-gray-600">Управление поездками как водитель</p>
          </div>
        </RouterLink>

        <RouterLink :to="{ name: 'my-bookings' }" class="surface-card p-6 hover:shadow-lg transition">
          <div class="text-center space-y-2">
            <h3 class="text-lg font-semibold text-gray-900">Мои бронирования</h3>
            <p class="text-sm text-gray-600">Активные бронирования</p>
          </div>
        </RouterLink>

        <RouterLink :to="{ name: 'booking-requests' }" class="surface-card p-6 hover:shadow-lg transition">
          <div class="text-center space-y-2">
            <h3 class="text-lg font-semibold text-gray-900">Заявки</h3>
            <p class="text-sm text-gray-600">Заявки от пассажиров</p>
          </div>
        </RouterLink>

        <RouterLink :to="{ name: 'ride-management' }" class="surface-card p-6 hover:shadow-lg transition">
          <div class="text-center space-y-2">
            <h3 class="text-lg font-semibold text-gray-900">Управление</h3>
            <p class="text-sm text-gray-600">Управление поездками</p>
          </div>
        </RouterLink>
      </div>

      <div class="grid gap-6 sm:grid-cols-2">
        <RouterLink :to="{ name: 'edit-profile' }" class="surface-card p-6 hover:shadow-lg transition">
          <div class="space-y-2">
            <h3 class="text-lg font-semibold text-gray-900">Редактировать профиль</h3>
            <p class="text-sm text-gray-600">Изменить имя, email и другие данные</p>
          </div>
        </RouterLink>

        <RouterLink :to="{ name: 'reviews' }" class="surface-card p-6 hover:shadow-lg transition">
          <div class="space-y-2">
            <h3 class="text-lg font-semibold text-gray-900">Отзывы</h3>
            <p class="text-sm text-gray-600">Просмотр отзывов о вас</p>
          </div>
        </RouterLink>
      </div>

      <div class="surface-card p-6 border-2 border-blue-200 bg-blue-50">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">Статистика профиля</h2>
        <p class="text-sm text-gray-600 mb-4">
          Скачайте подробную статистику вашего профиля в формате PDF
        </p>
        <button 
          @click="downloadStatistics" 
          :disabled="downloadingStats"
          class="primary-btn w-full text-center"
        >
          <span v-if="downloadingStats">Загрузка...</span>
          <span v-else>📥 Скачать статистику (PDF)</span>
        </button>
      </div>

      <div v-if="auth.isManager" class="surface-card p-6 border-2 border-yellow-200 bg-yellow-50">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">Панель менеджера</h2>
        <RouterLink :to="{ name: 'manager.dashboard' }" class="primary-btn w-full text-center">
          Открыть панель менеджера
        </RouterLink>
      </div>

      <div v-if="auth.isAdmin" class="surface-card p-6 border-2 border-blue-200 bg-blue-50">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">Админ-панель</h2>
        <RouterLink :to="{ name: 'admin.dashboard' }" class="primary-btn w-full text-center">
          Открыть админ-панель
        </RouterLink>
      </div>
    </div>
  </section>
</template>

<script setup>
import { RouterLink, useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { onMounted, ref } from 'vue'
import http from '@/helpers/http'

const router = useRouter()

const auth = useAuthStore()
const downloadingStats = ref(false)

const formatBalance = (balance) => {
  if (balance === null || balance === undefined) return '0.00'
  return parseFloat(balance).toFixed(2)
}

const getAvatarUrl = (path) => {
  if (!path) return null
  const baseUrl = import.meta.env.VITE_API_BASE_URL || 'http://127.0.0.1:8000'
  return `${baseUrl}/storage/${path}`
}

const getRoleLabel = (role) => {
  const labels = {
    driver: 'Водитель',
    passenger: 'Пассажир',
    admin: 'Администратор',
    manager: 'Менеджер'
  }
  return labels[role] || role
}

const downloadStatistics = async () => {
  downloadingStats.value = true
  try {
    // Проверяем авторизацию
    if (!auth.isAuthenticated || !auth.token) {
      alert('Требуется авторизация. Пожалуйста, войдите снова.')
      router.push({ name: 'login' })
      return
    }

    // Получаем токен из разных возможных источников
    const token = auth.token || localStorage.getItem('auth_token')
    
    if (!token) {
      alert('Требуется авторизация. Пожалуйста, войдите снова.')
      router.push({ name: 'login' })
      return
    }
    
    const baseUrl = import.meta.env.VITE_API_BASE_URL || 'http://127.0.0.1:8000/api'
    const url = `${baseUrl}/auth/profile/statistics/pdf`
    
    console.log('Запрос на:', url, 'с токеном:', token ? 'Есть' : 'Нет')
    
    const response = await fetch(url, {
      headers: {
        'Authorization': `Bearer ${token}`,
        'Accept': 'application/json, application/pdf, text/html, */*'
      },
      credentials: 'include'
    })
    
    console.log('Статус ответа:', response.status)
    
    if (response.status === 401) {
      // Токен недействителен, очищаем и перенаправляем
      auth.logout()
      alert('Сессия истекла. Пожалуйста, войдите снова.')
      router.push({ name: 'login' })
      return
    }
    
    if (!response.ok) {
      const errorText = await response.text()
      console.error('Ошибка ответа:', errorText)
      throw new Error(`Ошибка сервера: ${response.status} ${response.statusText}`)
    }
    
    const blob = await response.blob()
    const contentType = response.headers.get('content-type') || ''
    
    // Определяем расширение файла
    let extension = 'html'
    let filename = `profile-statistics-${auth.user?.id}-${new Date().toISOString().split('T')[0]}.${extension}`
    
    if (contentType.includes('pdf')) {
      extension = 'pdf'
      filename = `profile-statistics-${auth.user?.id}-${new Date().toISOString().split('T')[0]}.pdf`
    } else if (contentType.includes('html')) {
      extension = 'html'
      filename = `profile-statistics-${auth.user?.id}-${new Date().toISOString().split('T')[0]}.html`
    }
    
    // Создаем ссылку для скачивания
    const downloadUrl = window.URL.createObjectURL(blob)
    const link = document.createElement('a')
    link.href = downloadUrl
    link.download = filename
    document.body.appendChild(link)
    link.click()
    document.body.removeChild(link)
    window.URL.revokeObjectURL(downloadUrl)
    
    console.log('Файл успешно скачан:', filename)
  } catch (error) {
    console.error('Ошибка скачивания статистики:', error)
    alert(error.message || 'Не удалось скачать статистику. Попробуйте позже.')
  } finally {
    downloadingStats.value = false
  }
}

// Обновляем баланс при загрузке страницы
onMounted(async () => {
  try {
    const { data } = await http.get('/balance')
    if (auth.user) {
      auth.user.balance = parseFloat(data.balance) || 0
    }
  } catch (error) {
    console.error('Ошибка загрузки баланса:', error)
  }
})
</script>

