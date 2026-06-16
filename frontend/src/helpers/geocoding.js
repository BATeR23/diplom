import http from './http'

export async function searchAddresses(query, options = {}) {
    const { limit = 5, addressdetails = true, extratags = false } = options

    const response = await http.get('/geocoding/search', {
        params: {
            q: query,
            limit,
            addressdetails,
            extratags,
        },
    })

    return response.data ?? []
}
