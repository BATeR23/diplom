import axios from 'axios'

const API_BASE_URL = 'https://sweet-reverence-copy-production.up.railway.app/api'

const http = axios.create({
    baseURL: API_BASE_URL,
    timeout: 15000,
    withCredentials: true,
    headers: {
        Accept: 'application/json',
    },
})

http.interceptors.request.use((config) => {
    const token = localStorage.getItem('auth_token')

    if (token) {
        config.headers.Authorization = `Bearer ${token}`
    }

    if (!config.headers['Content-Type']) {
        config.headers['Content-Type'] = 'application/json'
    }

    return config
})

// Interceptor для обработки ошибок валидации
http.interceptors.response.use(
    (response) => response,
    (error) => {
        // Обрабатываем ошибки валидации (422)
        if (error.response?.status === 422 && error.response?.data?.errors) {
            // Форматируем ошибки валидации для удобного отображения
            const errors = error.response.data.errors
            const errorMessages = []
            
            // Собираем все сообщения об ошибках
            Object.keys(errors).forEach((field) => {
                if (Array.isArray(errors[field])) {
                    errors[field].forEach((message) => {
                        errorMessages.push(message)
                    })
                } else {
                    errorMessages.push(errors[field])
                }
            })
            
            // Добавляем отформатированные ошибки в response для удобного доступа
            error.formattedErrors = errorMessages
            error.errorMessage = errorMessages.join('. ')
        }
        
        return Promise.reject(error)
    }
)

export default http