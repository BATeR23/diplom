<template>
  <section class="page-section">
    <div class="page-section__content space-y-6">
      <span class="chip w-fit">С возвращением!</span>
      <h1 class="section-heading">Войдите, чтобы бронировать поездки и управлять своими маршрутами</h1>
      <p class="subheading max-w-2xl">
        Один профиль объединяет роли водителя и пассажира. Сохраняем рейтинг, автомобили, предпочтения и историю поездок.
      </p>
      <ul class="grid gap-4 md:grid-cols-2">
        <li class="glass-panel p-5">
          <p class="text-sm uppercase tracking-[0.3em] text-gray-600">Проверенные водители</p>
          <p class="mt-2 text-lg font-semibold text-gray-900">Изучайте рейтинг и отзывы перед бронированием.</p>
        </li>
        <li class="glass-panel p-5">
          <p class="text-sm uppercase tracking-[0.3em] text-gray-600">Гибкое расписание</p>
          <p class="mt-2 text-lg font-semibold text-gray-900">Фильтры по дате, направлению, местам и классу комфорта.</p>
        </li>
      </ul>
    </div>
    <div class="page-section__aside">
      <div class="surface-card space-y-6 p-8">
        <div class="flex items-center justify-between text-xs uppercase tracking-[0.2em] text-gray-600">
          <span>Вход</span>
          <RouterLink class="text-blue-600 hover:text-blue-700" :to="{ name: 'register' }">
            Нет аккаунта?
          </RouterLink>
        </div>
        <form class="space-y-5" @submit.prevent="handleSubmit">
          <div class="form-field">
            <label for="email">Email</label>
            <input
              id="email"
              v-model="form.email"
              type="email"
              placeholder="driver@pulseride.com"
              required
            />
          </div>
          <div class="form-field">
            <label for="password">Пароль</label>
            <input
              id="password"
              v-model="form.password"
              type="password"
              placeholder="Ваш пароль"
              required
            />
          </div>
          <p v-if="errorMessage" class="text-sm text-rose-600">
            {{ errorMessage }}
          </p>
          <button type="submit" class="primary-btn w-full disabled:opacity-60" :disabled="loading">
            {{ loading ? 'Авторизуем…' : 'Войти' }}
          </button>
        </form>
        <p class="text-xs text-gray-500">
          Входя в аккаунт, вы принимаете условия PulseRide и правила совместных поездок.
        </p>
      </div>
    </div>
  </section>
</template>

<script setup>
import { reactive, ref } from 'vue'
import { RouterLink, useRoute, useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const router = useRouter()
const route = useRoute()
const auth = useAuthStore()

const form = reactive({
  email: '',
  password: '',
})

const loading = ref(false)
const errorMessage = ref('')

const handleSubmit = async () => {
  loading.value = true
  errorMessage.value = ''
  try {
    await auth.login({ ...form })
    const redirectTarget = route.query.redirect
      ? { path: route.query.redirect }
      : { name: 'profile' }
    router.push(redirectTarget)
  } catch (error) {
    errorMessage.value = auth.error || 'Не удалось войти. Проверьте данные и повторите попытку.'
  } finally {
    loading.value = false
  }
}
</script>

