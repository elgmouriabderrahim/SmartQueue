<script setup lang="ts">
withDefaults(
  defineProps<{
    message: string
    icon?: 'plus' | 'neutral' | 'none'
    variant?: 'glass' | 'solid'
  }>(),
  {
    icon: 'plus',
    variant: 'glass',
  },
)
</script>

<template>
  <div 
    class="empty-state" 
    :class="[
      variant === 'glass' ? 'empty-state-glass' : 'empty-state-solid',
    ]"
  >
    <div v-if="icon !== 'none'" class="empty-icon-wrapper">
      <div class="empty-icon" :class="variant === 'glass' ? 'empty-icon-glass' : 'empty-icon-solid'">
        <svg v-if="icon === 'plus'" class="empty-svg" viewBox="0 0 24 24" fill="none">
          <path d="M5 12h14M12 5v14" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" />
        </svg>
        <svg v-else-if="icon === 'neutral'" class="empty-svg" viewBox="0 0 24 24" fill="none">
          <path d="M12 8h.01M12 12h.01M12 16h.01" stroke="currentColor" stroke-width="2" stroke-linecap="round" />
        </svg>
        <svg v-else class="empty-svg" viewBox="0 0 24 24" fill="none">
          <path d="M20 12H4M12 4v16" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" />
        </svg>
      </div>
    </div>
    <p class="empty-message">{{ message }}</p>
  </div>
</template>

<style scoped>
.empty-state {
  @apply rounded-2xl p-8 text-center transition-all duration-200;
}

/* Glass Variant */
.empty-state-glass {
  background: rgba(255, 255, 255, 0.55);
  backdrop-filter: blur(12px);
  border: 1px dashed rgba(255, 255, 255, 0.6);
  box-shadow: 0 8px 24px rgba(0, 0, 0, 0.02);
}

.empty-state-glass:hover {
  background: rgba(255, 255, 255, 0.65);
  border-color: rgba(255, 255, 255, 0.8);
  transform: translateY(-2px);
}

/* Solid Variant (fallback) */
.empty-state-solid {
  background: white;
  border: 1px dashed #e0e0e0;
  box-shadow: 0 4px 16px rgba(0, 0, 0, 0.02);
}

/* Icon Wrapper */
.empty-icon-wrapper {
  @apply mx-auto mb-4;
}

.empty-icon {
  @apply inline-flex h-14 w-14 items-center justify-center rounded-2xl transition-all duration-200;
}

.empty-icon-glass {
  background: rgba(255, 255, 255, 0.7);
  backdrop-filter: blur(4px);
  border: 1px solid rgba(255, 255, 255, 0.5);
  color: #2c5f6e;
}

.empty-icon-solid {
  background: #f5f5f5;
  border: 1px solid #e8e8e8;
  color: #8a8a8a;
}

.empty-state-glass:hover .empty-icon-glass {
  transform: scale(1.05);
  background: rgba(255, 255, 255, 0.85);
  color: #1e454f;
}

.empty-icon-glass .empty-svg,
.empty-icon-solid .empty-svg {
  @apply h-6 w-6;
}

.empty-svg {
  transition: transform 0.2s ease;
}

/* Message */
.empty-message {
  @apply text-sm leading-relaxed;
  color: #6b7280;
}

.empty-state-glass .empty-message {
  color: #7a7a7a;
}

.empty-state-solid .empty-message {
  color: #8a8a8a;
}

/* Responsive */
@media (max-width: 640px) {
  .empty-state {
    @apply p-5;
  }
  
  .empty-icon {
    @apply h-11 w-11;
  }
  
  .empty-svg {
    @apply h-5 w-5;
  }
  
  .empty-message {
    @apply text-xs;
  }
}
</style>