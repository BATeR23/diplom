<template>
  <section class="relative z-10 mx-auto flex w-full flex-col gap-12 px-4 pb-20 pt-16 sm:px-8 lg:px-12">
    <div class="flex flex-col gap-10 lg:flex-row">
      <div class="flex-1 space-y-6">
        <span class="chip w-fit">PulseRide — путешествуйте с комфортом</span>
        <h1 class="text-4xl font-semibold tracking-tight text-gray-900 sm:text-5xl">
          Находите попутчиков и делите путь с людьми, которым по дороге
        </h1>
        <p class="text-lg text-gray-600 sm:max-w-xl">
          Создайте поездку как водитель или найдите место как пассажир. Мы покажем рейтинг водителя, обсчитаем стоимость,
          запомним предпочтения и обеспечим быстрый чат.
        </p>
        <div class="flex flex-wrap gap-4">
          <RouterLink class="primary-btn" :to="{ name: 'rides.search' }">
            Найти поездку
          </RouterLink>
          <RouterLink class="secondary-btn" :to="{ name: 'rides.create' }">
            Предложить поездку
          </RouterLink>
        </div>
        <div class="grid gap-6 sm:grid-cols-3">
          <div v-for="metric in metrics" :key="metric.label" class="glass-panel p-5">
            <p class="text-3xl font-semibold text-gray-900">{{ metric.value }}</p>
            <p class="text-sm uppercase tracking-widest text-gray-600">{{ metric.label }}</p>
          </div>
        </div>
      </div>
      <div class="glass-panel flex-1 space-y-6 p-8">
        <p class="chip w-fit bg-emerald-100 text-emerald-700 border-emerald-200">Как это работает</p>
        <div class="timeline">
          <div v-for="step in steps" :key="step.title" class="timeline-step">
            <h3 class="text-gray-900">{{ step.title }}</h3>
            <p class="text-sm text-gray-600">{{ step.description }}</p>
          </div>
        </div>
      </div>
    </div>
    <div class="glass-panel grid gap-6 p-8 sm:grid-cols-3">
      <div v-for="card in cards" :key="card.title" class="rounded-2xl border border-gray-200 bg-gray-50 p-6">
        <div class="mb-4 inline-flex h-12 w-12 items-center justify-center rounded-2xl bg-blue-100 text-xl">
          {{ card.icon }}
        </div>
        <h3 class="text-lg font-semibold text-gray-900">{{ card.title }}</h3>
        <p class="mt-2 text-sm text-gray-600">{{ card.description }}</p>
      </div>
    </div>
    <div class="glass-panel space-y-4 p-8">
      <div class="flex items-center justify-between">
        <h3 class="text-lg font-semibold text-gray-900">Популярные направления</h3>
        <RouterLink class="text-sm text-blue-600 hover:text-blue-700" :to="{ name: 'rides.search' }">
          Смотреть все
        </RouterLink>
      </div>
      <div class="grid gap-4 md:grid-cols-3">
        <article
          v-for="route in sampleRoutes"
          :key="route.id"
          class="rounded-2xl border border-gray-200 bg-gray-50 p-4 text-sm text-gray-600"
        >
          <p class="text-xs uppercase tracking-[0.4em] text-gray-500">{{ route.day }}</p>
          <p class="text-xl font-semibold text-gray-900">{{ route.origin }} → {{ route.destination }}</p>
          <p>{{ route.time }} · {{ route.price }} ₽ · {{ route.seats }} мест</p>
        </article>
      </div>
    </div>
  </section>
</template>

<script setup>
import { RouterLink } from 'vue-router'

const metrics = [
  { label: 'Активных поездок', value: '1 240+' },
  { label: 'Проверенных водителей', value: '980' },
  { label: 'Средний рейтинг', value: '4.9/5' }
]

const steps = [
  {
    title: 'Создайте профиль водителя или пассажира',
    description: 'Укажите автомобиль, предпочтения, тип поездок и получите видимость в поиске.'
  },
  {
    title: 'Ищите или публикуйте поездки',
    description: 'Фильтруйте по направлению и дате или задавайте цену и доступные места.'
  },
  {
    title: 'Общайтесь в чате и собирайте отзывы',
    description: 'Пассажиры оставляют рейтинг, а вы подтверждаете бронирования в пару кликов.'
  }
]

const cards = [
  {
    title: 'Гибкие фильтры',
    description: 'Поиск по городам, датам, количеству мест, уровню комфорта и стоимости.',
    icon: '🎯'
  },
  {
    title: 'Безопасные профили',
    description: 'Каждый водитель собирает рейтинг, а пассажир видит реальные отзывы.',
    icon: '🛡️'
  },
  {
    title: 'Быстрый чат',
    description: 'Обсудите детали поездки до подтверждения и держите связь в пути.',
    icon: '💬'
  }
]

const sampleRoutes = [
  { id: 1, origin: 'Краков', destination: 'Вроцлав', day: 'ПТ · 18:30', time: '3ч 20м', price: 58, seats: 2 },
  { id: 2, origin: 'Варшава', destination: 'Гданьск', day: 'СБ · 08:00', time: '4ч 50м', price: 79, seats: 3 },
  { id: 3, origin: 'Познань', destination: 'Лодзь', day: 'ВС · 17:10', time: '2ч 15м', price: 44, seats: 1 }
]
</script>