<script setup lang="ts">
import { onMounted, ref } from 'vue'
import LoadingState from '@/components/LoadingState.vue'
import PageHeader from '@/components/PageHeader.vue'
import { smartQueueApi } from '@/services/smartQueueApi'
import { useAuthStore } from '@/stores/auth'
import { toApiError } from '@/utils/http'

const authStore = useAuthStore()

const loading = ref(false)
const syncing = ref(false)
const error = ref('')
const metrics = ref<Record<string, number | string>>({})
const syncDate = ref(new Date().toISOString().slice(0, 10))

async function loadAnalytics() {
  loading.value = true
  error.value = ''

  try {
    const response = await smartQueueApi.analytics()
    metrics.value = response.data.data as Record<string, number | string>
  } catch (err) {
    error.value = toApiError(err).message
  } finally {
    loading.value = false
  }
}

async function syncAnalytics() {
  syncing.value = true
  error.value = ''

  try {
    await smartQueueApi.syncAnalytics({ date: syncDate.value })
    await loadAnalytics()
  } catch (err) {
    error.value = toApiError(err).message
  } finally {
    syncing.value = false
  }
}

onMounted(loadAnalytics)
</script>

<template>
  <div class="space-y-8">
    <PageHeader title="Analytics" subtitle="Service and queue performance metrics." />

    <!-- Sync Section (Admin only) -->
    <div v-if="authStore.role === 'admin'" class="rounded-2xl bg-white/40 backdrop-blur-sm border border-stone-100 p-5">
      <h2 class="text-xs font-semibold uppercase tracking-wider text-stone-500">Sync Analytics</h2>
      <p class="mt-1 text-sm text-stone-400">Import analytics data for a specific date</p>
      <form class="mt-4 flex flex-col gap-3 sm:flex-row" @submit.prevent="syncAnalytics">
        <input 
          v-model="syncDate" 
          type="date" 
          required 
          class="rounded-full border border-stone-200 px-4 py-2 text-sm bg-white/60 focus:outline-none focus:border-stone-300"
        />
        <button 
          :disabled="syncing" 
          class="rounded-full bg-stone-800 px-5 py-2 text-sm font-medium text-white transition-all duration-200 hover:bg-stone-700 hover:-translate-y-0.5 disabled:opacity-50"
        >
          {{ syncing ? 'Syncing...' : 'Sync Data' }}
        </button>
      </form>
    </div>

    <!-- Loading & Error -->
    <LoadingState v-if="loading" />
    <p v-else-if="error" class="text-sm text-rose-500">{{ error }}</p>

    <!-- Metrics Grid -->
    <div v-else class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
      <div
        v-for="(value, key) in metrics"
        :key="key"
        class="group border-b border-stone-100 pb-4 transition-all duration-200 hover:border-stone-300"
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