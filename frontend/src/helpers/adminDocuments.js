import http from './http'

async function parseBlobError(error) {
    const data = error.response?.data
    if (data instanceof Blob && data.type?.includes('json')) {
        try {
            const text = await data.text()
            const json = JSON.parse(text)
            return json.message || 'Не удалось загрузить документ'
        } catch {
            return 'Не удалось загрузить документ'
        }
    }
    return error.response?.data?.message || error.message || 'Не удалось загрузить документ'
}

export async function openAdminDocument(path) {
    try {
        const response = await http.get(path, { responseType: 'blob' })

        if (response.data.type?.includes('json')) {
            const text = await response.data.text()
            const json = JSON.parse(text)
            throw new Error(json.message || 'Документ не найден')
        }

        const contentType = response.headers['content-type'] || response.data.type || 'application/octet-stream'
        const blob = new Blob([response.data], { type: contentType })
        const url = URL.createObjectURL(blob)
        window.open(url, '_blank', 'noopener,noreferrer')
        setTimeout(() => URL.revokeObjectURL(url), 60000)
    } catch (error) {
        const message = await parseBlobError(error)
        alert(message)
    }
}

export function getReceiptPath(requestId) {
    return `/admin/balance-requests/${requestId}/receipt`
}

export function getVehicleDocumentPath(vehicleId, type) {
    return `/admin/vehicle-verifications/${vehicleId}/document/${type}`
}
