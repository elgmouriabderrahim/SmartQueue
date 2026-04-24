<script setup lang="ts">
defineProps<{ title: string; subtitle?: string }>()
</script>

<template>
  <header 
    class="mb-6 rounded-2xl px-5 py-4 transition-all duration-200 sm:px-6"
    :class="[
      'bg-white/70 backdrop-blur-sm border border-white/50 shadow-md',
      'hover:shadow-lg hover:-translate-y-0.5'
    ]"
  >
    <p class="text-[11px] font-semibold uppercase tracking-[0.2em] text-teal-600/70">
      SmartQueue Module
    </p>
    <h1 class="mt-1 text-3xl font-semibold tracking-tight text-stone-800">
      {{ title }}
    </h1>
    <p v-if="subtitle" class="mt-1 text-sm text-stone-500">
      {{ subtitle }}
    </p>
  </header>
</template>