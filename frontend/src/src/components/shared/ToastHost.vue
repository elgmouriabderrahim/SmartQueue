<script setup lang="ts">
import { storeToRefs } from 'pinia'
import { useToastStore } from '@/stores/toast'

const toastStore = useToastStore()
const { items } = storeToRefs(toastStore)
</script>

<template>
  <div class="toast-container">
    <article
      v-for="item in items"
      :key="item.id"
      class="toast-message"
      :class="{
        'toast-success': item.tone === 'success',
        'toast-error': item.tone === 'error',
        'toast-info': item.tone === 'info',
      }"
    >
      <div class="toast-icon" :class="{
        'icon-success': item.tone === 'success',
        'icon-error': item.tone === 'error',
        'icon-info': item.tone === 'info',
      }">
        <svg v-if="item.tone === 'success'" class="toast-svg" viewBox="0 0 24 24" fill="none">
          <path d="M5 13l4 4L19 7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
        <svg v-else-if="item.tone === 'error'" class="toast-svg" viewBox="0 0 24 24" fill="none">
          <path d="M6 18L18 6M6 6l12 12" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
        <svg v-else class="toast-svg" viewBox="0 0 24 24" fill="none">
          <path d="M12 8v4M12 16h.01" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
          <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="2"/>
        </svg>
      </div>
      <p class="toast-text">{{ item.message }}</p>
      <button class="toast-close" @click="toastStore.remove(item.id)">
        <svg class="toast-close-svg" viewBox="0 0 24 24" fill="none">
          <path d="M6 18L18 6M6 6l12 12" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
        </svg>
      </button>
    </article>
  </div>
</template>

<style scoped>
.toast-container {
  @apply pointer-events-none fixed right-5 top-5 z-[70] flex w-full max-w-sm flex-col gap-3;
}

/* Base Toast Glass Style */
.toast-message {
  @apply pointer-events-auto flex items-start gap-3 rounded-2xl px-4 py-3 text-sm font-medium;
  background: rgba(255, 255, 255, 0.85);
  backdrop-filter: blur(12px);
  border: 1px solid rgba(255, 255, 255, 0.5);
  box-shadow: 0 8px 24px rgba(0, 0, 0, 0.08);
  animation: toastSlideIn 0.3s cubic-bezier(0.2, 0.9, 0.4, 1.1) both;
  transition: all 0.2s ease;
}

.toast-message:hover {
  transform: translateX(-2px);
}

/* Toast Variants */
.toast-success {
  border-left: 3px solid #10b981;
  background: rgba(255, 255, 255, 0.92);
}

.toast-error {
  border-left: 3px solid #ef4444;
  background: rgba(255, 255, 255, 0.92);
}

.toast-info {
  border-left: 3px solid #2c5f6e;
  background: rgba(255, 255, 255, 0.92);
}

/* Toast Icon */
.toast-icon {
  @apply inline-flex h-5 w-5 shrink-0 items-center justify-center rounded-full;
}

.icon-success {
  color: #10b981;
}

.icon-error {
  color: #ef4444;
}

.icon-info {
  color: #2c5f6e;
}

.toast-svg {
  @apply h-4 w-4;
}

/* Toast Text */
.toast-text {
  @apply flex-1 text-sm leading-relaxed;
  color: #1a1a1a;
}

/* Close Button */
.toast-close {
  @apply inline-flex h-5 w-5 shrink-0 items-center justify-center rounded-full transition-all duration-200;
  color: #9ca3af;
  background: rgba(0, 0, 0, 0.04);
}

.toast-close:hover {
  color: #6b7280;
  background: rgba(0, 0, 0, 0.08);
  transform: scale(1.1);
}

.toast-close-svg {
  @apply h-3 w-3;
}

/* Animations */
@keyframes toastSlideIn {
  from {
    transform: translateX(100%);
    opacity: 0;
  }
  to {
    transform: translateX(0);
    opacity: 1;
  }
}

/* Toast exit animation (optional - depends on how your store removes toasts) */
.toast-message-exit {
  animation: toastSlideOut 0.25s ease-in forwards;
}

@keyframes toastSlideOut {
  to {
    transform: translateX(100%);
    opacity: 0;
  }
}
</style>