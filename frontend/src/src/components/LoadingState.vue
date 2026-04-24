<script setup lang="ts">
withDefaults(
  defineProps<{
    message?: string
    size?: 'sm' | 'md' | 'lg'
    variant?: 'glass' | 'solid'
  }>(),
  {
    message: 'Loading...',
    size: 'md',
    variant: 'glass',
  },
)
</script>

<template>
  <div 
    class="rounded-2xl p-6 text-center transition-all duration-200"
    :class="[
      variant === 'glass' 
        ? 'bg-white/70 backdrop-blur-sm border border-white/50 shadow-md' 
        : 'bg-white border border-stone-200 shadow-sm',
      size === 'sm' ? 'p-4' : size === 'lg' ? 'p-8' : 'p-6'
    ]"
  >
    <div class="flex flex-col items-center justify-center gap-3">
      <!-- Spinner -->
      <div class="relative" :class="[
        size === 'sm' ? 'h-6 w-6' : size === 'lg' ? 'h-12 w-12' : 'h-8 w-8'
      ]">
        <div 
          class="absolute inset-0 rounded-full border-2 border-teal-200/50 border-t-teal-600 animate-spin"
        />
        <div 
          class="absolute inset-0 rounded-full border-2 border-amber-200/30 border-r-amber-500 animate-spin"
          style="animation-duration: 1.2s; animation-direction: reverse;"
        />
      </div>
      
      <!-- Message -->
      <p 
        class="font-medium text-stone-500"
        :class="[
          size === 'sm' ? 'text-xs' : size === 'lg' ? 'text-base' : 'text-sm'
        ]"
      >
        {{ message }}
      </p>
    </div>
  </div>
</template>