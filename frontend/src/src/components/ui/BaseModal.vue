<script setup lang="ts">
import { onMounted, onUnmounted } from 'vue'

type ModalSize = 'md' | 'lg' | 'full'

const props = withDefaults(defineProps<{
  open: boolean
  title: string
  size?: ModalSize
  closeOnOverlay?: boolean
}>(), {
  size: 'md',
  closeOnOverlay: true,
})

const emit = defineEmits<{
  (e: 'close'): void
}>()

function onEsc(event: KeyboardEvent): void {
  if (event.key === 'Escape' && props.open) {
    emit('close')
  }
}

function handleOverlayClick(): void {
  if (props.closeOnOverlay) {
    emit('close')
  }
}

function sizeClass(size: ModalSize): string {
  if (size === 'full') return 'max-w-[95vw] h-[90vh]'
  if (size === 'lg') return 'max-w-2xl'
  return 'max-w-md'
}

onMounted(() => window.addEventListener('keydown', onEsc))
onUnmounted(() => window.removeEventListener('keydown', onEsc))
</script>

<template>
  <div
    v-if="open"
    class="fixed inset-0 z-50 flex items-center justify-center bg-black/20 backdrop-blur-sm p-4"
    @click.self="handleOverlayClick"
  >
    <div
      class="w-full rounded-2xl bg-white p-6 shadow-xl max-h-[90vh] overflow-y-auto"
      :class="sizeClass(size)"
    >
      <div class="mb-4 flex items-center justify-between">
        <h3 class="text-xl font-light tracking-tight text-stone-800">{{ title }}</h3>
        <button
          type="button"
          class="rounded-full px-3 py-1 text-sm font-medium text-stone-500 hover:bg-stone-100 transition-colors"
          @click="emit('close')"
        >
          Close
        </button>
      </div>
      <div :class="size === 'full' ? 'h-[calc(90vh-6rem)] overflow-y-auto' : ''">
        <slot />
      </div>
    </div>
  </div>
</template>
