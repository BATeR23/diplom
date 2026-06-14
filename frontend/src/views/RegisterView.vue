<template>
  <section class="page-section">
    <div class="page-section__content space-y-6">
      <span class="chip w-fit">Создайте профиль</span>
      <h1 class="section-heading">Станьте частью комьюнити PulseRide</h1>
      <p class="subheading max-w-2xl">
        Расскажите о себе и, если вы водитель, добавьте автомобиль. Мы подберём попутчиков и сохраним ваши предпочтения.
      </p>
      <ul class="grid gap-4 md:grid-cols-2">
        <li class="glass-panel p-5">
          <p class="text-sm uppercase tracking-[0.3em] text-gray-600">Прозрачные профили</p>
          <p class="mt-2 text-lg font-semibold text-gray-900">Пассажиры видят рейтинг, авто и правила поездки.</p>
        </li>
        <li class="glass-panel p-5">
          <p class="text-sm uppercase tracking-[0.3em] text-gray-600">Полный контроль</p>
          <p class="mt-2 text-lg font-semibold text-gray-900">Назначайте цену, количество мест и фильтруйте бронирования.</p>
        </li>
      </ul>
    </div>
    <div class="page-section__aside">
      <div class="surface-card space-y-6 p-8">
        <div class="flex items-center justify-between text-xs uppercase tracking-[0.2em] text-gray-600">
          <span>Регистрация</span>
          <RouterLink class="text-blue-600 hover:text-blue-700" :to="{ name: 'login' }">
            Уже с нами?
          </RouterLink>
        </div>
        <form class="space-y-5" @submit.prevent="handleSubmit">
          <div class="form-field">
            <label for="name">Имя и фамилия</label>
            <input
              id="name"
              v-model="form.name"
              type="text"
              placeholder="Алексей Петров"
              required
            />
          </div>
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
            <label for="phone">Телефон (необязательно)</label>
            <input
              id="phone"
              v-model="form.phone"
              type="tel"
              placeholder="+48 600 000 000"
            />
          </div>
          <div class="grid gap-4 sm:grid-cols-2">
            <div class="form-field">
              <label for="password">Пароль</label>
              <input
                id="password"
                v-model="form.password"
                type="password"
                placeholder="Минимум 8 символов"
                required
              />
            </div>
            <div class="form-field">
              <label for="password_confirmation">Повторите пароль</label>
              <input
                id="password_confirmation"
                v-model="form.password_confirmation"
                type="password"
                placeholder="Подтверждение"
                required
              />
            </div>
          </div>
          <div class="form-field">
            <label for="role" class="mb-2 block">Я планирую</label>
            <select id="role" v-model="form.role">
              <option value="passenger">Искать поездки как пассажир</option>
              <option value="driver">Предлагать поездки как водитель</option>
            </select>
          </div>
          <p v-if="errorMessage" class="text-sm text-rose-600">
            {{ errorMessage }}
          </p>
          <button type="submit" class="primary-btn w-full disabled:opacity-60" :disabled="loading">
            {{ loading ? 'Создаём профиль…' : 'Зарегистрироваться' }}
          </button>
        </form>
        <p class="text-xs text-gray-500">
          Продолжая, вы соглашаетесь с политикой обработки данных и правилами сообщества PulseRide.
        </p>
      </div>
    </div>
  </section>
</template>

<script setup>
import { reactive, ref } from 'vue'
import { RouterLink, useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const router = useRouter()
const auth = useAuthStore()

const form = reactive({
  name: '',
  email: '',
  phone: '',
  password: '',
  password_confirmation: '',
  role: 'passenger',
})

const loading = ref(false)
const errorMessage = ref('')

const handleSubmit = async () => {
  loading.value = true
  errorMessage.value = ''
  try {
    await auth.register({ ...form })
    router.push({ name: 'profile' })
  } catch (error) {
    errorMessage.value = auth.error || 'Не удалось создать профиль. Попробуйте ещё раз.'
  } finally {
    loading.value = false
  }
}
</script>

