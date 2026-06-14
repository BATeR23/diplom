import { createApp } from 'vue'

import App from './App.vue'
import router from './router'
import { pinia } from './stores'

import './assets/main.css'

// Настройка dayjs для русского языка
import dayjs from 'dayjs'
import 'dayjs/locale/ru'

dayjs.locale('ru')

// Настройка Leaflet CSS
import 'leaflet/dist/leaflet.css'

// Инициализация Laravel Echo для WebSocket соединений
import './config/echo'

const app = createApp(App)

app.use(pinia)
app.use(router)

app.mount('#app')