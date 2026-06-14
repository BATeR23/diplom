<script setup>
import { RouterLink, RouterView, useRouter } from 'vue-router'
import { computed, onMounted, ref, onUnmounted } from 'vue'
import { useAuthStore } from '@/stores/auth'
import http from '@/helpers/http'

const router = useRouter()
const auth = useAuthStore()
const currentYear = new Date().getFullYear()

const isAuthenticated = computed(() => auth.isAuthenticated)
const isAdmin = computed(() => auth.isAdmin)
const unreadNotificationsCount = ref(0)

const navLinks = [
  { label: 'Поездки', to: '/rides', authOnly: false },
  { label: 'Автомобили', to: '/vehicles', authOnly: true },
  { label: 'Чаты', to: '/chats', authOnly: true },
  { label: 'Уведомления', to: '/notifications', authOnly: true },
  { label: 'Личный кабинет', to: '/profile', authOnly: true }
]

const fetchUnreadCount = async () => {
  if (!isAuthenticated.value) return
  
  try {
    const { data } = await http.get('/notifications/unread-count')
    unreadNotificationsCount.value = data.count || 0
  } catch (error) {
    console.error('Ошибка загрузки количества уведомлений:', error)
  }
}

let notificationInterval = null

const handleSignOut = async () => {
  await auth.logout()
  router.push({ name: 'login' })
}

const handleLogoClick = () => {
  router.push({ name: isAuthenticated.value ? 'profile' : 'landing' })
}

onMounted(() => {
  auth.bootstrap()
  if (isAuthenticated.value) {
    fetchUnreadCount()
    // Обновляем счетчик каждые 30 секунд
    notificationInterval = setInterval(fetchUnreadCount, 30000)
  }
})

onUnmounted(() => {
  if (notificationInterval) {
    clearInterval(notificationInterval)
  }
})
</script>

<template>
  <div class="app-shell antialiased">
    <div class="relative z-10 flex min-h-screen flex-col">
      <header class="mx-auto flex w-full items-center justify-between px-4 py-6 sm:px-8 lg:px-12 border-b border-gray-200 bg-white/80 backdrop-blur-sm">
        <div class="flex items-center gap-3 cursor-pointer" @click="handleLogoClick">
          <div class="grid h-12 w-12 place-items-center rounded-2xl bg-gradient-to-br from-blue-500 to-indigo-600 shadow-md">
            <span class="text-xl font-semibold text-white">PR</span>
          </div>
          <div>
            <p class="text-lg font-display font-semibold tracking-wide text-gray-900">PulseRide</p>
            <p class="text-xs uppercase tracking-[0.3em] text-gray-500">mobility os</p>
          </div>
        </div>
        <nav class="flex flex-1 flex-wrap items-center justify-center gap-4 text-sm font-semibold text-gray-700 lg:justify-end">
          <RouterLink
            v-for="link in navLinks.filter(link => !link.authOnly || isAuthenticated)"
            :key="link.to"
            :to="link.to"
            class="transition hover:text-blue-600 relative"
          >
            {{ link.label }}
            <span
              v-if="link.to === '/notifications' && unreadNotificationsCount > 0"
              class="absolute -top-1 -right-1 bg-red-500 text-white text-xs rounded-full w-5 h-5 flex items-center justify-center"
            >
              {{ unreadNotificationsCount > 9 ? '9+' : unreadNotificationsCount }}
            </span>
          </RouterLink>
          <RouterLink
            v-if="isAdmin"
            :to="{ name: 'admin.dashboard' }"
            class="transition hover:text-amber-600 text-amber-500"
          >
            Админ-панель
          </RouterLink>
        </nav>
        <div class="flex items-center gap-3">
          <template v-if="isAuthenticated">
            <span class="text-sm text-gray-700 hidden sm:inline">{{ auth.user?.name }}</span>
            <button
              class="secondary-btn text-sm"
              type="button"
              @click="handleSignOut"
            >
              Выйти
            </button>
          </template>
          <button
            v-else
            class="primary-btn text-sm"
            type="button"
            @click="router.push({ name: 'login' })"
          >
            Войти
          </button>
        </div>
      </header>
      <main class="flex-1">
        <RouterView />
      </main>
      <footer class="mx-auto w-full px-4 pb-10 pt-4 text-xs text-gray-500 sm:px-8 lg:px-12 border-t border-gray-200 bg-white/50">
        PulseRide © {{ currentYear }} · Гибкая платформа для городских поездок
      </footer>
    </div>
  </div>
</template>