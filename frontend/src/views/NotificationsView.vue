<template>
  <section class="page-section">
    <div class="page-section__content space-y-6">
      <div class="flex items-center justify-between">
        <div>
          <span class="chip w-fit">Уведомления</span>
          <h1 class="section-heading">Мои уведомления</h1>
        </div>
        <button
          v-if="notifications.length > 0 && unreadCount > 0"
          @click="markAllAsRead"
          class="secondary-btn text-sm"
          :disabled="markingAll"
        >
          {{ markingAll ? 'Обработка...' : 'Отметить все как прочитанные' }}
        </button>
      </div>

      <div v-if="loading" class="text-center text-gray-600 py-12">
        <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600 mb-2"></div>
        <p class="text-sm">Загрузка уведомлений...</p>
      </div>

      <div v-else-if="notifications.length" class="space-y-3">
        <article
          v-for="notification in notifications"
          :key="notification.id"
          :class="[
            'surface-card p-5 border rounded-lg transition-all cursor-pointer',
            notification.read ? 'border-gray-200 bg-white' : 'border-blue-200 bg-blue-50'
          ]"
          @click="handleNotificationClick(notification)"
        >
          <div class="flex items-start gap-4">
            <div :class="[
              'w-10 h-10 rounded-full flex items-center justify-center flex-shrink-0 text-lg',
              getNotificationIconClass(notification.type)
            ]">
              {{ getNotificationIcon(notification.type) }}
            </div>
            <div class="flex-1 min-w-0">
              <div class="flex items-start justify-between gap-2">
                <div class="flex-1">
                  <h3 :class="[
                    'text-sm font-semibold mb-1',
                    notification.read ? 'text-gray-900' : 'text-gray-900'
                  ]">
                    {{ notification.title }}
                  </h3>
                  <p class="text-sm text-gray-600 mb-2">{{ notification.message }}</p>
                  <div class="flex items-center gap-3 text-xs text-gray-500">
                    <span>{{ formatDate(notification.created_at) }}</span>
                    <span v-if="!notification.read" class="px-2 py-0.5 bg-blue-600 text-white rounded-full text-xs">
                      Новое
                    </span>
                  </div>
                </div>
                <button
                  v-if="!notification.read"
                  @click.stop="markAsRead(notification)"
                  class="text-gray-400 hover:text-gray-600 p-1"
                  title="Отметить как прочитанное"
                >
                  <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                  </svg>
                </button>
              </div>
            </div>
          </div>
        </article>
      </div>

      <div v-else class="surface-card p-12 text-center border border-gray-200">
        <div class="max-w-md mx-auto">
          <div class="w-16 h-16 mx-auto mb-4 rounded-full bg-gray-100 flex items-center justify-center">
            <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
            </svg>
          </div>
          <p class="text-gray-900 font-medium mb-2">Нет уведомлений</p>
          <p class="text-sm text-gray-600">Здесь будут отображаться все ваши уведомления.</p>
        </div>
      </div>
    </div>
  </section>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import { useRouter } from 'vue-router'
import http from '@/helpers/http'
import dayjs from 'dayjs'

const router = useRouter()

const loading = ref(false)
const notifications = ref([])
const unreadCount = ref(0)
const markingAll = ref(false)

const fetchNotifications = async () => {
  loading.value = true
  try {
    const { data } = await http.get('/notifications')
    notifications.value = data.data || data
  } catch (error) {
    console.error('Ошибка загрузки уведомлений:', error)
    alert(error.response?.data?.message || 'Ошибка загрузки уведомлений')
  } finally {
    loading.value = false
  }
}

const fetchUnreadCount = async () => {
  try {
    const { data } = await http.get('/notifications/unread-count')
    unreadCount.value = data.count || 0
  } catch (error) {
    console.error('Ошибка загрузки количества непрочитанных:', error)
  }
}

const markAsRead = async (notification) => {
  if (notification.read) return

  try {
    await http.post(`/notifications/${notification.id}/read`)
    notification.read = true
    notification.read_at = new Date().toISOString()
    unreadCount.value = Math.max(0, unreadCount.value - 1)
  } catch (error) {
    console.error('Ошибка отметки уведомления:', error)
    alert(error.response?.data?.message || 'Ошибка отметки уведомления')
  }
}

const markAllAsRead = async () => {
  markingAll.value = true
  try {
    await http.post('/notifications/read-all')
    notifications.value.forEach(n => {
      n.read = true
      n.read_at = new Date().toISOString()
    })
    unreadCount.value = 0
  } catch (error) {
    console.error('Ошибка отметки всех уведомлений:', error)
    alert(error.response?.data?.message || 'Ошибка отметки всех уведомлений')
  } finally {
    markingAll.value = false
  }
}

const handleNotificationClick = (notification) => {
  if (!notification.read) {
    markAsRead(notification)
  }

  // Переход на соответствующую страницу в зависимости от типа уведомления
  const data = notification.data || {}
  
  if (data.ride_id) {
    router.push({ name: 'rides.show', params: { id: data.ride_id } })
  } else if (data.vehicle_id) {
    router.push({ name: 'vehicles' })
  } else if (notification.type === 'balance_recharge') {
    router.push({ name: 'balance' })
  } else if (notification.type === 'message_received' && data.ride_id) {
    router.push({ name: 'chats' })
  }
}

const formatDate = (date) => {
  return dayjs(date).format('DD.MM.YYYY HH:mm')
}

const getNotificationIcon = (type) => {
  const icons = {
    balance_recharge: '💰',
    booking_created: '📝',
    booking_accepted: '✓',
    booking_rejected: '✗',
    booking_cancelled: '🚫',
    message_received: '💬',
    ride_cancelled: '🚫',
    vehicle_approved: '✓',
    vehicle_rejected: '✗',
  }
  
  return icons[type] || '💬'
}

const getNotificationIconClass = (type) => {
  const classes = {
    balance_recharge: 'bg-green-100 text-green-600',
    booking_created: 'bg-blue-100 text-blue-600',
    booking_accepted: 'bg-emerald-100 text-emerald-600',
    booking_rejected: 'bg-red-100 text-red-600',
    booking_cancelled: 'bg-orange-100 text-orange-600',
    message_received: 'bg-purple-100 text-purple-600',
    ride_cancelled: 'bg-red-100 text-red-600',
    vehicle_approved: 'bg-emerald-100 text-emerald-600',
    vehicle_rejected: 'bg-red-100 text-red-600',
  }
  return classes[type] || 'bg-gray-100 text-gray-600'
}

onMounted(() => {
  fetchNotifications()
  fetchUnreadCount()
})
</script>
