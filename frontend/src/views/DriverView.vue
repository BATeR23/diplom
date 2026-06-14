<template>
  <section class="page-section">
    <div class="page-section__content space-y-6">
      <div class="space-y-3">
        <span class="chip">Профиль водителя</span>
        <h1 class="section-heading">Заполните карточку водителя и автомобиля</h1>
        <p class="subheading">Эти данные увидит пассажир после принятия заявки. Заполните все поля, чтобы повысить доверие и прозрачность.</p>
      </div>
      <div class="surface-card p-8">
        <form class="space-y-6" @submit.prevent="handleSaveDriver">
          <div class="grid gap-4 sm:grid-cols-2">
            <div class="form-field sm:col-span-2">
              <label for="name">Имя и фамилия</label>
              <input type="text" id="name" v-model="driverDetails.name" placeholder="Андрей Иванов">
            </div>
            <div class="form-field">
              <label for="year">Год выпуска</label>
              <input type="number" id="year" v-model="driverDetails.year" placeholder="2022">
            </div>
            <div class="form-field">
              <label for="color">Цвет</label>
              <input type="text" id="color" v-model="driverDetails.color" placeholder="Graphite Grey">
            </div>
            <div class="form-field">
              <label for="make">Марка</label>
              <input type="text" id="make" v-model="driverDetails.make" placeholder="Volvo">
            </div>
            <div class="form-field">
              <label for="model">Модель</label>
              <input type="text" id="model" v-model="driverDetails.model" placeholder="XC60 Recharge">
            </div>
            <div class="form-field sm:col-span-2">
              <label for="license_plate">Госномер</label>
              <input type="text" id="license_plate" v-model="driverDetails.license_plate" placeholder="А000АА 777">
            </div>
          </div>
          <div class="flex flex-wrap gap-4">
            <button type="submit" class="primary-btn flex-1 min-w-[200px]">Сохранить профиль</button>
            <button type="button" class="secondary-btn flex-1 min-w-[200px]" @click="router.back()">Отмена</button>
          </div>
        </form>
      </div>
    </div>
    <div class="page-section__aside">
      <div class="surface-card p-8 space-y-4">
        <p class="text-sm uppercase tracking-[0.4em] text-slate-400">Зачем это нужно</p>
        <ul class="space-y-3 text-sm text-slate-300">
          <li>Пассажиры видят ваш автомобиль и подтверждают посадку по номеру.</li>
          <li>Водительский профиль сохраняется и не требует повторного заполнения.</li>
          <li>Сервисы безопасности используют эти данные при возникновении вопросов.</li>
        </ul>
      </div>
    </div>
  </section>
</template>
<script setup>
import { reactive } from 'vue'
import http from '@/helpers/http'
import router from '../router';

const driverDetails = reactive({
    name: '',
    year: null,
    make: '',
    model: '',
    color: '',
    license_plate: ''
})
const handleSaveDriver = () => {
    http().post('/api/driver', driverDetails)
        .then((response) => {
            router.push({
                name: 'standby'
            })
        })
        .catch((error) => {
            console.error(error)
        })
}
</script>