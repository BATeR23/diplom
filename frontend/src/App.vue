<script setup>
import { RouterLink, RouterView, useRouter, useRoute } from 'vue-router'
import { computed, onMounted, ref, onUnmounted, watch } from 'vue'
import { useAuthStore } from '@/stores/auth'
import http from '@/helpers/http'

const router = useRouter()
const route = useRoute()
const auth = useAuthStore()
const currentYear = new Date().getFullYear()
const mobileMenuOpen = ref(false)

const isAuthenticated = computed(() => auth.isAuthenticated)
const isAdmin = computed(() => auth.isAdmin)
const isManager = computed(() => auth.isManager)
const unreadNotificationsCount = ref(0)

const navLinks = [
  { label: 'Поездки', to: '/rides', authOnly: false },
  { label: 'Автомобили', to: '/vehicles', authOnly: true },
  { label: 'Чаты', to: '/chats', authOnly: true },
  { label: 'Уведомления', to: '/notifications', authOnly: true },
  { label: 'Личный кабинет', to: '/profile', authOnly: true },
]

const visibleNavLinks = computed(() =>
  navLinks.filter((link) => !link.authOnly || isAuthenticated.value)
)

const adminLink = computed(() => {
  if (isAdmin.value) return { label: 'Админ-панель', to: '/admin', name: 'admin.dashboard' }
  if (isManager.value) return { label: 'Панель менеджера', to: '/manager', name: 'manager.dashboard' }
  return null
})

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
  mobileMenuOpen.value = false
  await auth.logout()
  router.push({ name: 'login' })
}

const handleLogoClick = () => {
  mobileMenuOpen.value = false
  router.push({ name: isAuthenticated.value ? 'profile' : 'landing' })
}

const closeMobileMenu = () => {
  mobileMenuOpen.value = false
}

const toggleMobileMenu = () => {
  mobileMenuOpen.value = !mobileMenuOpen.value
}

watch(() => route.fullPath, () => {
  mobileMenuOpen.value = false
})

onMounted(() => {
  auth.bootstrap()
  if (isAuthenticated.value) {
    fetchUnreadCount()
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
      <header class="sticky top-0 z-50 w-full border-b border-gray-200 bg-white/95 backdrop-blur-md">
        <div class="mx-auto flex w-full max-w-7xl items-center justify-between gap-3 px-4 py-3 sm:px-6 lg:px-8">
          <div class="flex min-w-0 items-center gap-2 sm:gap-3 cursor-pointer shrink-0" @click="handleLogoClick">
            <div class="grid h-10 w-10 sm:h-11 sm:w-11 place-items-center rounded-2xl bg-gradient-to-br from-blue-500 to-indigo-600 shadow-md">
              <span class="text-base sm:text-lg font-semibold text-white">PR</span>
            </div>
            <div class="min-w-0">
              <p class="truncate text-base sm:text-lg font-display font-semibold tracking-wide text-gray-900">PulseRide</p>
              <p class="hidden sm:block text-xs uppercase tracking-[0.3em] text-gray-500">mobility os</p>
            </div>
          </div>

          <nav class="hidden lg:flex flex-1 items-center justify-center gap-1 xl:gap-2">
            <RouterLink
              v-for="link in visibleNavLinks"
              :key="link.to"
              :to="link.to"
              class="relative rounded-full px-3 py-2 text-sm font-semibold text-gray-700 transition hover:bg-gray-100 hover:text-blue-600"
              active-class="text-blue-600 bg-blue-50"
            >
              {{ link.label }}
              <span
                v-if="link.to === '/notifications' && unreadNotificationsCount > 0"
                class="absolute -top-0.5 -right-0.5 bg-red-500 text-white text-[10px] rounded-full min-w-[18px] h-[18px] flex items-center justify-center px-1"
              >
                {{ unreadNotificationsCount > 9 ? '9+' : unreadNotificationsCount }}
              </span>
            </RouterLink>
            <RouterLink
              v-if="adminLink"
              :to="{ name: adminLink.name }"
              class="rounded-full px-3 py-2 text-sm font-semibold text-amber-600 transition hover:bg-amber-50"
            >
              {{ adminLink.label }}
            </RouterLink>
          </nav>

          <div class="hidden lg:flex items-center gap-3 shrink-0">
            <template v-if="isAuthenticated">
              <span class="max-w-[140px] truncate text-sm text-gray-700 xl:max-w-[180px]">{{ auth.user?.name }}</span>
              <button class="secondary-btn text-sm py-2 px-4" type="button" @click="handleSignOut">
                Выйти
              </button>
            </template>
            <button
              v-else
              class="primary-btn text-sm py-2 px-4"
              type="button"
              @click="router.push({ name: 'login' })"
            >
              Войти
            </button>
          </div>

          <div class="flex items-center gap-2 lg:hidden shrink-0">
            <RouterLink
              v-if="isAuthenticated"
              to="/notifications"
              class="relative grid h-10 w-10 place-items-center rounded-xl border border-gray-200 text-gray-700"
            >
              <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6 6 0 10-12 0v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
              </svg>
              <span
                v-if="unreadNotificationsCount > 0"
                class="absolute -top-1 -right-1 bg-red-500 text-white text-[10px] rounded-full min-w-[18px] h-[18px] flex items-center justify-center px-1"
              >
                {{ unreadNotificationsCount > 9 ? '9+' : unreadNotificationsCount }}
              </span>
            </RouterLink>
            <button
              type="button"
              class="grid h-10 w-10 place-items-center rounded-xl border border-gray-200 text-gray-700"
              :aria-expanded="mobileMenuOpen"
              aria-label="Меню"
              @click="toggleMobileMenu"
            >
              <svg v-if="!mobileMenuOpen" class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
              </svg>
              <svg v-else class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>
        </div>

        <div
          v-if="mobileMenuOpen"
          class="lg:hidden border-t border-gray-200 bg-white px-4 py-4 sm:px-6"
        >
          <div v-if="isAuthenticated" class="mb-4 rounded-2xl bg-gray-50 px-4 py-3">
            <p class="text-sm font-semibold text-gray-900">{{ auth.user?.name }}</p>
            <p class="text-xs text-gray-500">{{ auth.user?.email }}</p>
          </div>

          <nav class="flex flex-col gap-1">
            <RouterLink
              v-for="link in visibleNavLinks"
              :key="link.to"
              :to="link.to"
              class="rounded-xl px-4 py-3 text-sm font-semibold text-gray-700 transition hover:bg-gray-100"
              active-class="bg-blue-50 text-blue-600"
              @click="closeMobileMenu"
            >
              {{ link.label }}
            </RouterLink>
            <RouterLink
              v-if="adminLink"
              :to="{ name: adminLink.name }"
              class="rounded-xl px-4 py-3 text-sm font-semibold text-amber-600 transition hover:bg-amber-50"
              @click="closeMobileMenu"
            >
              {{ adminLink.label }}
            </RouterLink>
          </nav>

          <div class="mt-4 pt-4 border-t border-gray-200">
            <button
              v-if="isAuthenticated"
              class="secondary-btn w-full"
              type="button"
              @click="handleSignOut"
            >
              Выйти
            </button>
            <button
              v-else
              class="primary-btn w-full"
              type="button"
              @click="router.push({ name: 'login' }); closeMobileMenu()"
            >
              Войти
            </button>
          </div>
        </div>
      </header>

      <main class="flex-1">
        <RouterView />
      </main>

      <footer class="mx-auto w-full max-w-7xl px-4 pb-10 pt-4 text-xs text-gray-500 sm:px-6 lg:px-8 border-t border-gray-200 bg-white/50">
        PulseRide © {{ currentYear }} · Гибкая платформа для городских поездок
      </footer>
    </div>
  </div>
</template>
