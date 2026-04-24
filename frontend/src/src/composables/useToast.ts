import { useToastStore } from '@/stores/toast'

export function useToast() {
  const toastStore = useToastStore()

  return {
    toastSuccess(message: string): void {
      toastStore.show(message, 'success')
    },
    toastError(message: string): void {
      toastStore.show(message, 'error', 3600)
    },
    toastInfo(message: string): void {
      toastStore.show(message, 'info')
    },
  }
}
