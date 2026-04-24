<script setup lang="ts">
import { computed } from 'vue'

const props = withDefaults(
  defineProps<{
    variant?: 'primary' | 'secondary' | 'danger' | 'ghost' | 'glass'
    type?: 'button' | 'submit' | 'reset'
    disabled?: boolean
    block?: boolean
    size?: 'sm' | 'md' | 'lg'
  }>(),
  {
    variant: 'primary',
    type: 'button',
    disabled: false,
    block: false,
    size: 'md',
  },
)

const classes = computed(() => {
  const byVariant: Record<string, string> = {
    primary: 'btn-primary-glass',
    secondary: 'btn-secondary-glass',
    danger: 'btn-danger-glass',
    ghost: 'btn-ghost-glass',
    glass: 'btn-glass-only',
  }

  const bySize: Record<string, string> = {
    sm: 'px-3 py-1.5 text-xs gap-1.5',
    md: 'px-4 py-2.5 text-sm gap-2',
    lg: 'px-6 py-3 text-base gap-2.5',
  }

  return [
    'btn-base',
    byVariant[props.variant],
    bySize[props.size],
    props.block ? 'w-full' : '',
    props.disabled ? 'btn-disabled' : '',
  ].filter(Boolean).join(' ')
})
</script>

<template>
  <button :type="type" :disabled="disabled" :class="classes">
    <slot />
  </button>
</template>

<style scoped>
.btn-base {
  @apply inline-flex items-center justify-center rounded-2xl font-semibold transition-all duration-200;
  backdrop-filter: blur(4px);
}

/* Primary Button - Teal Glass */
.btn-primary-glass {
  background: linear-gradient(135deg, rgba(44, 95, 110, 0.92), rgba(44, 95, 110, 0.85));
  border: 1px solid rgba(255, 255, 255, 0.3);
  color: white;
  box-shadow: 0 2px 8px rgba(44, 95, 110, 0.15);
}

.btn-primary-glass:hover:not(:disabled) {
  background: linear-gradient(135deg, rgba(44, 95, 110, 1), rgba(44, 95, 110, 0.92));
  transform: translateY(-2px);
  box-shadow: 0 8px 20px rgba(44, 95, 110, 0.2);
  border-color: rgba(255, 255, 255, 0.5);
}

.btn-primary-glass:active:not(:disabled) {
  transform: translateY(0);
}

/* Secondary Button - White Glass */
.btn-secondary-glass {
  background: rgba(255, 255, 255, 0.55);
  backdrop-filter: blur(8px);
  border: 1px solid rgba(255, 255, 255, 0.5);
  color: #4a4a4a;
  box-shadow: 0 1px 2px rgba(0, 0, 0, 0.02);
}

.btn-secondary-glass:hover:not(:disabled) {
  background: rgba(255, 255, 255, 0.8);
  transform: translateY(-1px);
  border-color: rgba(255, 255, 255, 0.8);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.04);
}

/* Danger Button - Rose Glass */
.btn-danger-glass {
  background: rgba(239, 68, 68, 0.85);
  backdrop-filter: blur(8px);
  border: 1px solid rgba(255, 255, 255, 0.3);
  color: white;
  box-shadow: 0 2px 8px rgba(239, 68, 68, 0.12);
}

.btn-danger-glass:hover:not(:disabled) {
  background: rgba(239, 68, 68, 0.95);
  transform: translateY(-2px);
  box-shadow: 0 8px 20px rgba(239, 68, 68, 0.2);
  border-color: rgba(255, 255, 255, 0.5);
}

/* Ghost Button - Minimal Glass */
.btn-ghost-glass {
  background: transparent;
  border: 1px solid transparent;
  color: #2c5f6e;
}

.btn-ghost-glass:hover:not(:disabled) {
  background: rgba(44, 95, 110, 0.08);
  border-color: rgba(44, 95, 110, 0.15);
  transform: translateY(-1px);
}

/* Glass Only - Fully Transparent Glass */
.btn-glass-only {
  background: rgba(255, 255, 255, 0.35);
  backdrop-filter: blur(8px);
  border: 1px solid rgba(255, 255, 255, 0.4);
  color: #4a4a4a;
}

.btn-glass-only:hover:not(:disabled) {
  background: rgba(255, 255, 255, 0.55);
  transform: translateY(-1px);
  border-color: rgba(255, 255, 255, 0.6);
}

/* Disabled State */
.btn-disabled {
  @apply cursor-not-allowed opacity-50;
}

/* Loading state support (optional) */
.btn-loading {
  @apply cursor-wait;
}

.btn-loading .btn-spinner {
  @apply mr-2 h-4 w-4 animate-spin;
}
</style>