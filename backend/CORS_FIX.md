# Исправление проблемы CORS

## Шаги для исправления:

1. **Добавьте в `backend/.env` файл:**
   ```
   FRONTEND_ORIGINS=http://localhost:5173,http://127.0.0.1:5173
   ```

2. **Очистите кэш конфигурации Laravel:**
   ```bash
   cd backend
   php artisan config:clear
   php artisan cache:clear
   ```

3. **Перезапустите Laravel сервер:**
   ```bash
   php artisan serve
   ```

4. **Убедитесь, что фронтенд использует правильный URL:**
   - Проверьте `frontend/.env` или `frontend/.env.local`
   - Должно быть: `VITE_API_BASE_URL=http://127.0.0.1:8000/api`

## Что было исправлено:

1. ✅ Включен `supports_credentials => true` в `config/cors.php`
2. ✅ Добавлен `withCredentials: true` в `frontend/src/helpers/http.js`
3. ✅ Обновлены `allowed_origins` для поддержки localhost:5173

## Если проблема сохраняется:

Проверьте, что:
- Laravel сервер запущен на порту 8000
- Фронтенд запущен на порту 5173
- В браузере нет блокировщиков CORS расширений
- Проверьте консоль браузера на наличие других ошибок











