<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { smartQueueApi } from '@/services/smartQueueApi'
import { toApiError } from '@/utils/http'

const loading = ref(false)
const error = ref('')
const metrics = ref<Record<string, number | string>>({})

const highlightStats = computed(() => [
  { key: 'total_appointments', label: 'Total Appointments', value: Number(metrics.value.total_appointments || 0) },
  { key: 'users_served', label: 'Users Served', value: Number(metrics.value.users_served || 0) },
  { key: 'active_services', label: 'Active Services', value: Number(metrics.value.active_services || 0) },
])

async function loadDashboard(): Promise<void> {
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
      <p class="text-[11px] font-semibold uppercase tracking-[0.3em] text-stone-400">Dashboard</p>
      <h1 class="mt-2 text-3xl font-light tracking-tight text-stone-800">Institution Overview</h1>
      <p class="mt-1 text-stone-500">Key metrics and performance indicators</p>
      <div class="mt-3 h-px w-12 bg-amber-300" />
    </div>

    <!-- Loading & Error -->
    <p v-if="error" class="text-sm text-rose-500">{{ error }}</p>
    <p v-else-if="loading" class="text-sm text-stone-400">Loading dashboard...</p>

    <!-- Highlight Stats Grid -->
    <div v-else class="grid gap-6 sm:grid-cols-2 xl:grid-cols-3">
      <div
        v-for="item in highlightStats"
        :key="item.key"
        class="group border-b border-stone-100 pb-4 transition-all duration-200 hover:border-stone-300"
      >
        <p class="text-xs uppercase tracking-wider text-stone-400">{{ item.label }}</p>
        <p class="mt-2 text-3xl font-light text-stone-700">{{ item.value.toLocaleString() }}</p>
        <div class="mt-3 h-px w-8 bg-stone-200 group-hover:w-full transition-all duration-300" />
      </div>
    </div>

    <!-- Summary Section -->
    <div>
      <div class="mb-5">
        <h2 class="text-lg font-light tracking-tight text-stone-800">Summary</h2>
        <p class="text-xs text-stone-400 mt-0.5">Complete analytics breakdown</p>
        <div class="mt-2 h-px w-8 bg-amber-300" />
      </div>
      
      <div class="grid gap-5 sm:grid-cols-2">
        <div 
          v-for="(value, key) in metrics" 
          :key="key" 
          class="border-b border-stone-100 pb-3 transition-all duration-200 hover:border-stone-200"
        >
          <p class="text-xs uppercase tracking-wider text-stone-400">{{ String(key).replaceAll('_', ' ') }}</p>
          <p class="mt-1 text-base font-light text-stone-600">{{ value }}</p>
        </div>
      </div>
    </div>
  </div>
</template>