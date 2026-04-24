import { defineStore } from 'pinia'
import { ref } from 'vue'

export type ToastTone = 'success' | 'error' | 'info'

export interface ToastItem {
  id: number
  message: string
  tone: ToastTone
}

export const useToastStore = defineStore('toast', () => {
  const items = ref<ToastItem[]>([])

  function show(message: string, tone: ToastTone = 'info', timeout = 2800): void {
    const id = Date.now() + Math.floor(Math.random() * 1000)
    items.value.push({ id, message, tone })

    window.setTimeout(() => remove(id), timeout)
  }

  function remove(id: number): void {
    items.value = items.value.filter((item) => item.id !== id)
  }

  return { items, show, remove }
})
