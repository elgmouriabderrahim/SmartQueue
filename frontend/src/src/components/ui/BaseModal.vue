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
  if (size === 'lg') return 'max-w-4xl'
  return 'max-w-2xl'
}

onMounted(() => window.addEventListener('keydown', onEsc))
onUnmounted(() => window.removeEventListener('keydown', onEsc))
</script>

<template>
  <div
    v-if="open"
    class="fixed inset-0 z-50 flex items-center justify-center bg-[#243447]/45 p-4"
    @click.self="handleOverlayClick"
  >
    <div
      class="w-full rounded-[1.4rem] border border-[#d8c5a7] bg-[#fffaf1] p-5 shadow-[0_40px_70px_-40px_rgba(25,45,73,0.8)]"
      :class="sizeClass(size)"
    >
      <div class="mb-4 flex items-center justify-between">
        <h3 class="text-lg font-semibold tracking-tight text-[#16283b]">{{ title }}</h3>
        <button class="rounded-lg px-2 py-1 text-[#60758f] hover:bg-[#f3e6d1]" @click="emit('close')">x</button>
      </div>
      <div :class="size === 'full' ? 'h-[calc(90vh-6rem)] overflow-y-auto' : ''">
        <slot />
      </div>
    </div>
  </div>
</template>
