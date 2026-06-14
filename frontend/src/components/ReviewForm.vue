<template>
  <div v-if="!submitted" class="space-y-3">
    <p class="text-sm font-semibold text-gray-900">Оставить отзыв</p>
    <form @submit.prevent="submitReview" class="space-y-3">
      <div>
        <label class="block text-sm text-gray-700 mb-1">Оценка</label>
        <div class="flex gap-1">
          <button
            v-for="i in 5"
            :key="i"
            type="button"
            @click="reviewForm.rating = i"
            :class="[
              'text-2xl transition-colors',
              i <= reviewForm.rating ? 'text-yellow-400' : 'text-gray-300'
            ]"
          >
            ★
          </button>
        </div>
      </div>
      <div>
        <label for="comment" class="block text-sm text-gray-700 mb-1">Комментарий (необязательно)</label>
        <textarea
          id="comment"
          v-model="reviewForm.comment"
          rows="3"
          class="form-field-input"
          placeholder="Оставьте отзыв о поездке..."
          maxlength="2000"
        />
      </div>
      <p v-if="errorMessage" class="text-sm text-rose-600">{{ errorMessage }}</p>
      <button
        type="submit"
        class="primary-btn text-sm"
        :disabled="loading || !reviewForm.rating"
      >
        {{ loading ? 'Отправка...' : 'Отправить отзыв' }}
      </button>
    </form>
  </div>
  <div v-else class="text-sm text-emerald-600">
    ✓ Отзыв успешно отправлен!
  </div>
</template>

<script setup>
import { ref, reactive } from 'vue'
import http from '@/helpers/http'
import { useAuthStore } from '@/stores/auth'

const props = defineProps({
  rideId: {
    type: Number,
    required: true
  },
  driverId: {
    type: Number,
    required: true
  },
  bookingId: {
    type: Number,
    required: false
  }
})

const auth = useAuthStore()
const loading = ref(false)
const submitted = ref(false)
const errorMessage = ref('')

const reviewForm = reactive({
  rating: 0,
  comment: ''
})

const submitReview = async () => {
  if (!reviewForm.rating) {
    errorMessage.value = 'Пожалуйста, выберите оценку'
    return
  }

  loading.value = true
  errorMessage.value = ''

  try {
    await http.post(`/rides/${props.rideId}/reviews`, {
      target_id: props.driverId,
      rating: reviewForm.rating,
      comment: reviewForm.comment || undefined
    })
    submitted.value = true
  } catch (error) {
    errorMessage.value = error.response?.data?.message || 'Ошибка отправки отзыва'
  } finally {
    loading.value = false
  }
}
</script>

