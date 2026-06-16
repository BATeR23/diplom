import Echo from 'laravel-echo';
import Pusher from 'pusher-js';
import axios from 'axios';

// Настройка Pusher
window.Pusher = Pusher;

// Базовый URL API
const API_BASE_URL = import.meta.env.VITE_API_BASE_URL || 'http://127.0.0.1:8000/api'
axios.defaults.baseURL = API_BASE_URL

// Инициализация Echo с авторизацией
const echo = new Echo({
  broadcaster: 'pusher',
  key: 'mykey', // Должен совпадать с PUSHER_APP_KEY в config/websockets.php
  wsHost: window.location.hostname === 'localhost' ? '127.0.0.1' : window.location.hostname,
  wsPort: 6001,
  wssPort: 6001,
  forceTLS: false,
  enabledTransports: ['ws', 'wss'],
  disableStats: true,
  cluster: 'mt1',
  // Настройка авторизатора для приватных каналов
  authorizer: (channel, options) => {
    return {
      authorize: (socketId, callback) => {
        // Получаем токен из localStorage
        const token = localStorage.getItem('auth_token');

        if (!token) {
          console.error('Токен авторизации не найден');
          callback(true, { message: 'Токен авторизации не найден' });
          return;
        }

        // Создаем запрос для авторизации
        axios.post(`${API_BASE_URL}/broadcasting/auth`, {
          socket_id: socketId,
          channel_name: channel.name
        }, {
          headers: {
            'Authorization': `Bearer ${token}`,
            'Accept': 'application/json',
            'Content-Type': 'application/json'
          }
        })
        .then(response => {
          // Успешная авторизация
          callback(false, response.data);
        })
        .catch(error => {
          // Ошибка авторизации
          console.error('Ошибка авторизации канала:', error.response?.data || error.message);
          callback(true, error.response?.data || { message: 'Ошибка авторизации' });
        });
      }
    };
  }
});

// Делаем Echo глобально доступным
window.Echo = echo;

// Логирование статуса подключения для отладки
echo.connector.pusher.connection.bind('connected', () => {
  console.log('✅ WebSocket подключен успешно');
});

echo.connector.pusher.connection.bind('error', (error) => {
  console.error('❌ Ошибка WebSocket подключения:', error);
});

echo.connector.pusher.connection.bind('disconnected', () => {
  console.warn('⚠️ WebSocket отключен');
});

export default echo;
