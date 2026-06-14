<template>
  <section class="page-section">
    <div class="page-section__content space-y-6">
      <span class="chip w-fit">Сообщения</span>
      <h1 class="section-heading">Мои чаты</h1>
      <p class="subheading">
        Общайтесь с водителями и пассажирами по вашим поездкам.
      </p>

      <div v-if="loading" class="text-center text-gray-600 py-12">
        <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
        <p class="mt-2">Загрузка чатов...</p>
      </div>

      <div v-else-if="chatRides.length" class="space-y-4">
        <article
          v-for="chatRide in chatRides"
          :key="`${chatRide.ride.id}-${chatRide.role}`"
          class="surface-card p-6 space-y-4 border border-gray-200 hover:shadow-md transition-shadow"
        >
          <div class="flex items-start justify-between">
            <div class="flex-1">
              <p class="text-lg font-semibold text-gray-900">
                {{ chatRide.ride.origin_city }} → {{ chatRide.ride.destination_city }}
              </p>
              <p class="text-sm text-gray-600 mt-1">
                {{ formatDate(chatRide.ride.departure_time) }} · 
                <span class="font-medium">{{ chatRide.role === 'driver' ? 'Вы водитель' : 'Вы пассажир' }}</span>
              </p>
            </div>
            <span :class="getStatusClass(chatRide.ride.status)" class="chip text-xs">
              {{ getStatusLabel(chatRide.ride.status) }}
            </span>
          </div>

          <div class="space-y-3 pt-4 border-t border-gray-200">
            <p class="text-sm font-semibold text-gray-900">Собеседники:</p>
            <div
              v-for="partner in chatRide.chat_partners"
              :key="partner.booking_id"
              class="flex items-center justify-between p-4 rounded-lg bg-gray-50 border border-gray-200 hover:bg-gray-100 transition-colors"
            >
              <div class="flex items-center gap-3 flex-1">
                <div class="w-12 h-12 rounded-full bg-gradient-to-br from-blue-500 to-indigo-600 flex items-center justify-center text-white font-semibold text-lg shadow-sm">
                  {{ partner.user?.name?.charAt(0)?.toUpperCase() || '?' }}
                </div>
                <div class="flex-1 min-w-0">
                  <p class="text-sm font-semibold text-gray-900 truncate">{{ partner.user?.name }}</p>
                  <p class="text-xs text-gray-500 truncate">{{ partner.user?.email }}</p>
                  <span :class="[
                    'inline-block mt-1 text-xs px-2 py-0.5 rounded',
                    partner.status === 'accepted' ? 'bg-emerald-100 text-emerald-700' :
                    partner.status === 'pending' ? 'bg-yellow-100 text-yellow-700' :
                    partner.status === 'rejected' ? 'bg-red-100 text-red-700' :
                    'bg-gray-100 text-gray-700'
                  ]">
                    {{ getBookingStatusLabel(partner.status) }}
                  </span>
                </div>
              </div>
              <button
                @click="openChat(chatRide.ride, partner)"
                class="primary-btn text-sm ml-4 whitespace-nowrap"
              >
                Открыть чат
              </button>
            </div>
          </div>
        </article>
      </div>

      <div v-else class="surface-card p-12 text-center border border-gray-200">
        <div class="max-w-md mx-auto">
          <div class="w-16 h-16 mx-auto mb-4 rounded-full bg-gray-100 flex items-center justify-center">
            <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
            </svg>
          </div>
          <p class="text-gray-900 font-medium mb-2">У вас пока нет активных чатов</p>
          <p class="text-sm text-gray-600">Создайте поездку или забронируйте место, чтобы начать общение.</p>
        </div>
      </div>
    </div>

    <!-- Chat Modal -->
    <div v-if="selectedChat" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
      <div class="bg-white rounded-lg shadow-xl w-full max-w-4xl max-h-[90vh] flex flex-col">
        <div class="p-4 border-b border-gray-200 flex items-center justify-between bg-gray-50">
          <div>
            <h2 class="text-lg font-semibold text-gray-900">Чат с {{ selectedChat.partner.user?.name }}</h2>
            <p class="text-sm text-gray-600">
              {{ selectedChat.ride.origin_city }} → {{ selectedChat.ride.destination_city }}
            </p>
          </div>
          <button
            @click="closeChat"
            class="text-gray-500 hover:text-gray-700 text-2xl font-bold w-8 h-8 flex items-center justify-center rounded hover:bg-gray-200"
          >
            ×
          </button>
        </div>
        <div class="flex-1 overflow-hidden min-h-0">
          <ChatWindow
            :ride-id="selectedChat.ride.id"
            :booking-id="selectedChat.partner.booking_id"
            :other-user="selectedChat.partner.user"
            :ride="selectedChat.ride"
          />
        </div>
      </div>
    </div>
  </section>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import http from '@/helpers/http'
import ChatWindow from '@/components/ChatWindow.vue'
import dayjs from 'dayjs'

const loading = ref(false)
const chatRides = ref([])
const selectedChat = ref(null)

const fetchChatRides = async () => {
  loading.value = true
  try {
    const { data } = await http.get('/chats/rides')
    chatRides.value = data
  } catch (error) {
    console.error('Ошибка загрузки чатов:', error)
    alert(error.response?.data?.message || 'Ошибка загрузки чатов')
  } finally {
    loading.value = false
  }
}

const openChat = (ride, partner) => {
  selectedChat.value = {
    ride,
    partner
  }
}

const closeChat = () => {
  selectedChat.value = null
}

const formatDate = (date) => {
  return dayjs(date).format('DD.MM.YYYY HH:mm')
}

const getStatusLabel = (status) => {
  const labels = {
    draft: 'Черновик',
    published: 'Опубликована',
    in_progress: 'В пути',
    completed: 'Завершена',
    cancelled: 'Отменена',
  }
  return labels[status] || status
}

const getStatusClass = (status) => {
  const classes = {
    draft: 'bg-gray-100 text-gray-700 border-gray-200',
    published: 'bg-blue-100 text-blue-700 border-blue-200',
    in_progress: 'bg-yellow-100 text-yellow-700 border-yellow-200',
    completed: 'bg-emerald-100 text-emerald-700 border-emerald-200',
    cancelled: 'bg-red-100 text-red-700 border-red-200',
  }
  return classes[status] || ''
}

const getBookingStatusLabel = (status) => {
  const labels = {
    pending: 'Ожидает',
    accepted: 'Принято',
    rejected: 'Отклонено',
    completed: 'Завершено',
  }
  return labels[status] || status
}

onMounted(() => {
  fetchChatRides()
})
</script>
