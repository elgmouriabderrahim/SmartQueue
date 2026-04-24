<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { smartQueueApi } from '@/services/smartQueueApi'
import { toApiError } from '@/utils/http'

const loading = ref(false)
const error = ref('')
const metrics = ref<Record<string, number | string>>({})

async function loadDashboard() {
  loading.value = true
  error.value = ''

  try {
    const response = await smartQueueApi.dashboard()
    metrics.value = response.data.data as Record<string, number | string>
  } catch (err) {
    error.value = toApiError(err).message
  } finally {
    loading.value = false
  }
}

onMounted(loadDashboard)
</script>

<template>
  <div class="space-y-8">
    <!-- Header -->
    <div>
      <p class="text-[11px] font-semibold uppercase tracking-[0.3em] text-stone-400">Insights</p>
      <h1 class="mt-2 text-3xl font-light tracking-tight text-stone-800">Dashboard</h1>
      <p class="mt-1 text-stone-500">System activity and service health overview</p>
      <div class="mt-3 h-px w-12 bg-amber-300" />
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="flex justify-center py-12">
      <div class="relative h-8 w-8">
        <div class="absolute inset-0 rounded-full border-2 border-stone-200 border-t-stone-600 animate-spin" />
      </div>
    </div>

    <!-- Error State -->
    <p v-else-if="error" class="text-sm text-rose-500">{{ error }}</p>

    <!-- Metrics Grid -->
    <div v-else class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
      <div
        v-for="(value, key) in metrics"
        :key="key"
        class="group border-b border-stone-100 pb-4 transition-all duration-200 hover:border-stone-200"
      >
        <p class="text-xs uppercase tracking-wider text-stone-400">
          {{ String(key).split('_').join(' ') }}
        </p>
        <p class="mt-2 text-3xl font-light text-stone-700">
          {{ value }}
        </p>
      </div>
    </div>
  </div>
</template>