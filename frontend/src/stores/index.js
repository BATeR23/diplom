import { createPinia, setActivePinia } from 'pinia'

export const pinia = createPinia()

export const ensureActivePinia = () => {
    setActivePinia(pinia)
}











