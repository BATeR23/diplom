import http from './http'

export async function searchAddresses(query, options = {}) {
    const { limit = 5, addressdetails = true, extratags = false } = options

    // ПРЕОБРАЗУЕМ булевы значения в строки '1' и '0' для бэкенда
    const response = await http.get('/geocoding/search', {
        params: {
            q: query,
            limit,
            addressdetails: addressdetails ? '1' : '0',   // Явно передаем как строку '1' или '0'
            extratags: extratags ? '1' : '0',            // Явно передаем как строку '1' или '0'
        },
    })

    return response.data ?? []
}