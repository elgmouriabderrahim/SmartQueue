<script setup lang="ts">
import { computed } from 'vue'

const props = defineProps<{
  mine: boolean
  content: string
  author: string
  time: string
}>()

const bubbleClass = computed(() =>
  props.mine
    ? 'ml-auto glass-bubble-mine'
    : 'mr-auto glass-bubble-other',
)

const wrapperClass = computed(() =>
  props.mine
    ? 'ml-auto text-right'
    : 'mr-auto text-left',
)
</script>

<template>
  <div class="max-w-[75%] animate-in fade-in slide-in-from-bottom-2 duration-400" :class="wrapperClass">
    <p class="mb-1.5 text-[11px] font-medium tracking-wide" :class="mine ? 'text-stone-500' : 'text-stone-400'">
      {{ author }} <span class="mx-1">•</span> {{ time }}
    </p>
    <div :class="bubbleClass">
      {{ content }}
    </div>
  </div>
</template>

<style scoped>
.glass-bubble-mine {
  @apply px-4 py-2.5 text-sm;
  background: rgba(44, 95, 110, 0.82);
  backdrop-filter: blur(12px);
  border: 1px solid rgba(255, 255, 255, 0.25);
  border-radius: 24px;
  border-bottom-right-radius: 6px;
  color: white;
  box-shadow: 0 4px 16px rgba(0, 0, 0, 0.04);
  transition: all 0.2s ease;
}

.glass-bubble-mine:hover {
  transform: translateY(-1px);
  background: rgba(44, 95, 110, 0.88);
  box-shadow: 0 8px 22px rgba(44, 95, 110, 0.12);
}

.glass-bubble-other {
  @apply px-4 py-2.5 text-sm;
  background: rgba(255, 255, 255, 0.68);
  backdrop-filter: blur(12px);
  border: 1px solid rgba(255, 255, 255, 0.4);
  border-radius: 24px;
  border-bottom-left-radius: 6px;
  color: #1a1a1a;
  box-shadow: 0 4px 16px rgba(0, 0, 0, 0.02);
  transition: all 0.2s ease;
}

.glass-bubble-other:hover {
  transform: translateY(-1px);
  background: rgba(255, 255, 255, 0.8);
  box-shadow: 0 8px 22px rgba(0, 0, 0, 0.04);
}

/* Smooth animations */
@keyframes fade-in {
  from {
    opacity: 0;
  }
  to {
    opacity: 1;
  }
}

@keyframes slide-in-from-bottom-2 {
  from {
    transform: translateY(8px);
  }
  to {
    transform: translateY(0);
  }
}

.animate-in {
  animation-duration: 0.3s;
  animation-fill-mode: both;
}

.fade-in {
  animation-name: fade-in;
}

.slide-in-from-bottom-2 {
  animation-name: slide-in-from-bottom-2;
}

.duration-400 {
  animation-duration: 0.4s;
}
</style>