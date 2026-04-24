<template>
  <button
    :type="type"
    :class="[
      'inline-flex items-center justify-center rounded-lg transition-all duration-200',
      sizeClasses,
      variantClasses,
      disabled && 'opacity-50 cursor-not-allowed',
    ]"
    :disabled="disabled"
    @click="$emit('click')"
  >
    <svg
      v-if="icon"
      :class="['flex-shrink-0', iconSizeClasses]"
      viewBox="0 0 24 24"
      fill="none"
      stroke="currentColor"
      :stroke-width="strokeWidth"
      stroke-linecap="round"
      stroke-linejoin="round"
    >
      <path :d="icon" />
    </svg>
    <span v-if="label" :class="['ml-2 font-medium', labelSizeClasses]">{{ label }}</span>
    <slot />
  </button>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { getIcon } from '@/utils/icons'

interface Props {
  icon?: string
  label?: string
  size?: 'sm' | 'md' | 'lg'
  variant?: 'primary' | 'secondary' | 'danger' | 'ghost' | 'outlined'
  disabled?: boolean
  strokeWidth?: number
  type?: 'button' | 'submit' | 'reset'
}

const props = withDefaults(defineProps<Props>(), {
  size: 'md',
  variant: 'ghost',
  disabled: false,
  strokeWidth: 1.5,
  type: 'button',
})

defineEmits<{
  click: []
}>()

// Resolve icon if provided as string key
const icon = computed(() => {
  if (!props.icon) return undefined
  // If it looks like a Material Design icon key, get the SVG path
  if (typeof props.icon === 'string' && !props.icon.includes('M')) {
    return getIcon(props.icon)
  }
  return props.icon
})

const sizeClasses = computed(() => {
  const sizes = {
    sm: 'px-2 py-1.5',
    md: 'px-3 py-2',
    lg: 'px-4 py-2.5',
  }
  return sizes[props.size]
})

const iconSizeClasses = computed(() => {
  const sizes = {
    sm: 'h-3.5 w-3.5',
    md: 'h-4 w-4',
    lg: 'h-5 w-5',
  }
  return sizes[props.size]
})

const labelSizeClasses = computed(() => {
  const sizes = {
    sm: 'text-xs',
    md: 'text-sm',
    lg: 'text-base',
  }
  return sizes[props.size]
})

const variantClasses = computed(() => {
  const variants = {
    primary: 'bg-orange-500 text-white hover:bg-orange-600 active:bg-orange-700',
    secondary: 'bg-teal-600 text-white hover:bg-teal-700 active:bg-teal-800',
    danger: 'bg-red-500 text-white hover:bg-red-600 active:bg-red-700',
    ghost: 'text-gray-700 hover:bg-gray-100 active:bg-gray-200 dark:text-gray-300 dark:hover:bg-gray-700 dark:active:bg-gray-600',
    outlined: 'border border-gray-300 text-gray-700 hover:bg-gray-50 active:bg-gray-100 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-800 dark:active:bg-gray-700',
  }
  return variants[props.variant]
})
</script>
