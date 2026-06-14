<template>
  <section class="page-section">
    <div class="page-section__content space-y-6">
      <span class="chip w-fit">Отзывы</span>
      <h1 class="section-heading">Отзывы о вас</h1>
      <p class="subheading">
        Просмотр отзывов, оставленных другими пользователями.
      </p>

      <div v-if="loading" class="text-center text-gray-600 py-8">
        Загрузка...
      </div>

      <div v-else-if="reviews.length" class="space-y-4">
        <article
          v-for="review in reviews"
          :key="review.id"
          class="surface-card p-6"
        >
          <div class="flex items-start justify-between">
            <div class="flex-1">
              <div class="flex items-center gap-3 mb-2">
                <div class="h-10 w-10 rounded-full bg-blue-100 grid place-items-center text-sm font-semibold text-blue-700">
                  {{ review.author?.name?.slice(0, 1) }}
                </div>
                <div>
                  <p class="font-semibold text-gray-900">{{ review.author?.name }}</p>
                  <p class="text-sm text-gray-600">{{ formatDate(review.created_at) }}</p>
                </div>
              </div>
              <div class="flex items-center gap-2 mb-2">
                <span class="text-2xl font-semibold text-gray-900">{{ review.rating }}</span>
                <div class="flex text-yellow-400">
                  <span v-for="i in 5" :key="i" :class="i <= review.rating ? 'text-yellow-400' : 'text-gray-300'">★</span>
                </div>
              </div>
              <p v-if="review.comment" class="text-gray-700">{{ review.comment }}</p>
              <p class="text-sm text-gray-600 mt-2">
                Поездка: {{ review.ride?.origin_city }} → {{ review.ride?.destination_city }}
              </p>
            </div>
          </div>
        </article>
      </div>

      <div v-else class="surface-card p-8 text-center">
        <p class="text-gray-600">У вас пока нет отзывов.</p>
      </div>
    </div>
  </section>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import dayjs from 'dayjs'
import http from '@/helpers/http'

const loading = ref(false)
const reviews = ref([])

const formatDate = (value) => dayjs(value).format('D MMM YYYY')

const fetchReviews = async () => {
  loading.value = true
  try {
    const { data } = await http.get('/reviews/me')
    reviews.value = data.data || data
  } catch (error) {
    console.error('Error fetching reviews:', error)
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  fetchReviews()
})
</script>

