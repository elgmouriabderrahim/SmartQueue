<script setup lang="ts">
import { computed } from 'vue'

const props = withDefaults(
  defineProps<{
    tone?: 'default' | 'success' | 'warning' | 'danger' | 'info'
    outline?: boolean
    rounded?: 'full' | 'lg' | 'md'
  }>(),
  {
    tone: 'default',
    outline: false,
    rounded: 'full',
  },
)

const classes = computed(() => {
  const roundedClass = {
    full: 'rounded-full',
    lg: 'rounded-lg',
    md: 'rounded-md',
  }[props.rounded]

  if (props.outline) {
    const outlineByTone: Record<string, string> = {
      default: 'border border-stone-300 bg-white/60 text-stone-600',
      success: 'border border-emerald-300 bg-emerald-50/60 text-emerald-700',
      warning: 'border border-amber-300 bg-amber-50/60 text-amber-700',
      danger: 'border border-red-300 bg-red-50/60 text-red-700',
      info: 'border border-teal-300 bg-teal-50/60 text-teal-700',
    }
    return `inline-flex items-center gap-1 px-2.5 py-1 text-xs font-medium backdrop-blur-sm ${roundedClass} ${outlineByTone[props.tone]}`
  }

  const solidByTone: Record<string, string> = {
    default: 'bg-stone-100/80 backdrop-blur-sm text-stone-700 border border-stone-200/50',
    success: 'bg-emerald-100/80 backdrop-blur-sm text-emerald-800 border border-emerald-200/50',
    warning: 'bg-amber-100/80 backdrop-blur-sm text-amber-800 border border-amber-200/50',
    danger: 'bg-red-100/80 backdrop-blur-sm text-red-800 border border-red-200/50',
    info: 'bg-teal-100/80 backdrop-blur-sm text-teal-800 border border-teal-200/50',
  }

  return `inline-flex items-center gap-1 px-2.5 py-1 text-xs font-medium ${roundedClass} ${solidByTone[props.tone]}`
})
</script>

<template>
  <span :class="classes">
    <slot />
  </span>
</template>