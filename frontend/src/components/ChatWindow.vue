<template>
  <div class="chat-window flex flex-col h-full bg-white">
    <div v-if="loading" class="flex-1 flex items-center justify-center p-8">
      <div class="text-center text-gray-500">
        <div class="inline-block animate-spin rounded-full h-6 w-6 border-b-2 border-blue-600 mb-2"></div>
        <p class="text-sm">Загрузка сообщений...</p>
      </div>
    </div>
    <div v-else ref="messagesContainer" class="chat-messages flex-1 overflow-y-auto p-4 space-y-4 bg-gray-50">
      <div v-if="messages.length === 0" class="flex items-center justify-center h-full text-gray-500">
        <p class="text-sm">Нет сообщений. Начните общение!</p>
      </div>
      <div
        v-for="message in messages"
        :key="message.id"
        :class="[
          'message flex',
          message.sender_id === currentUserId ? 'justify-end' : 'justify-start'
        ]"
      >
        <div
          :class="[
            'message-bubble max-w-xs lg:max-w-md px-4 py-3 rounded-lg shadow-sm',
            message.sender_id === currentUserId
              ? 'bg-blue-600 text-white rounded-br-none'
              : 'bg-white text-gray-900 border border-gray-200 rounded-bl-none'
          ]"
        >
          <div v-if="message.sender_id !== currentUserId" class="text-xs font-semibold mb-1 text-gray-700">
            {{ message.sender?.name }}
          </div>
          <p class="text-sm break-words">{{ message.body }}</p>
          <p :class="['text-xs mt-1', message.sender_id === currentUserId ? 'text-blue-100' : 'text-gray-500']">
            {{ formatTime(message.created_at) }}
          </p>
        </div>
      </div>
    </div>

    <div class="chat-input p-4 border-t border-gray-200 bg-white">
      <form @submit.prevent="sendMessage" class="flex gap-2">
        <input
          v-model="newMessage"
          type="text"
          placeholder="Введите сообщение..."
          class="flex-1 form-field-input border border-gray-300 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
          :disabled="sending"
          maxlength="2000"
        />
        <button
          type="submit"
          class="primary-btn px-6 whitespace-nowrap"
          :disabled="sending || !newMessage.trim()"
        >
          {{ sending ? 'Отправка...' : 'Отправить' }}
        </button>
      </form>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted, watch, nextTick } from 'vue'
import http from '@/helpers/http'
import dayjs from 'dayjs'
import { useAuthStore } from '@/stores/auth'

const props = defineProps({
  rideId: {
    type: Number,
    required: true
  },
  bookingId: {
    type: Number,
    required: true
  },
  otherUser: {
    type: Object,
    required: true
  },
  ride: {
    type: Object,
    required: true
  }
})

const auth = useAuthStore()
const currentUserId = auth.user?.id

const messages = ref([])
const newMessage = ref('')
const sending = ref(false)
const messagesContainer = ref(null)
const loading = ref(false)

const formatTime = (value) => dayjs(value).format('HH:mm')

const fetchMessages = async () => {
  loading.value = true
  try {
    const { data } = await http.get(`/rides/${props.rideId}/messages`)
    messages.value = (data.data || data).reverse() // Переворачиваем для правильного отображения
  } catch (error) {
    console.error('Ошибка загрузки сообщений:', error)
  } finally {
    loading.value = false
  }
}

const sendMessage = async () => {
  if (!newMessage.value.trim() || sending.value) return

  sending.value = true
  try {
    await http.post(`/rides/${props.rideId}/messages`, {
      booking_id: props.bookingId,
      body: newMessage.value.trim()
    })
    newMessage.value = ''
    await fetchMessages()
    scrollToBottom()
  } catch (error) {
    console.error('Ошибка отправки сообщения:', error)
    alert(error.response?.data?.message || 'Ошибка отправки сообщения')
  } finally {
    sending.value = false
  }
}

const scrollToBottom = () => {
  nextTick(() => {
    if (messagesContainer.value) {
      messagesContainer.value.scrollTop = messagesContainer.value.scrollHeight
    }
  })
}

// Подключение к WebSocket для real-time обновлений
let echoChannel = null

onMounted(async () => {
  await fetchMessages()
  scrollToBottom()

  // Подключаемся к каналу чата
  if (window.Echo) {
    echoChannel = window.Echo.private(`ride.${props.rideId}`)
      .listen('.message.sent', (e) => {
        // Используем точку перед именем события, чтобы Laravel Echo правильно обработал кастомное имя
        if (e.message) {
          messages.value.push(e.message)
          scrollToBottom()
        }
      })
      .error((error) => {
        console.error('Ошибка подписки на канал:', error)
      })
  }
})

onUnmounted(() => {
  if (echoChannel) {
    window.Echo.leave(`ride.${props.rideId}`)
  }
})

watch(() => messages.value.length, () => {
  scrollToBottom()
})
</script>

<style scoped>
.chat-window {
  height: 100%;
  min-height: 400px;
  display: flex;
  flex-direction: column;
}

.chat-messages {
  flex: 1;
  min-height: 0;
  overflow-y: auto;
}
</style>

