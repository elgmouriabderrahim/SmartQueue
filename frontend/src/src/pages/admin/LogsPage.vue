<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { smartQueueApi } from '@/services/smartQueueApi'
import { toApiError } from '@/utils/http'

const loading = ref(false)
const error = ref('')
const logs = ref<any[]>([])
const detailLoading = ref(false)
const detailError = ref('')
const logDetail = ref<any | null>(null)

async function loadLogs(): Promise<void> {
  loading.value = true
  error.value = ''

  try {
    const logsResponse = await smartQueueApi.activityLogs({ per_page: 100 })

    logs.value = logsResponse.data.data.data || []
  } catch (err) {
    error.value = toApiError(err).message
  } finally {
    loading.value = false
  }
}

async function removeLog(id: number): Promise<void> {
  error.value = ''

  try {
    await smartQueueApi.deleteActivityLog(id)
    await loadLogs()
  } catch (err) {
    error.value = toApiError(err).message
  }
}

async function loadLogDetail(id: number): Promise<void> {
  detailLoading.value = true
  detailError.value = ''

  try {
    const response = await smartQueueApi.activityLog(id)
    logDetail.value = response.data.data
  } catch (err) {
    detailError.value = toApiError(err).message
  } finally {
    detailLoading.value = false
  }
}

onMounted(loadLogs)
</script>

<template>
  <div class="space-y-8">
    <!-- Header -->
    <div>
      <p class="text-[11px] font-semibold uppercase tracking-[0.3em] text-stone-400">Audit</p>
      <h1 class="mt-2 text-3xl font-light tracking-tight text-stone-800">Activity Logs</h1>
      <p class="mt-1 text-stone-500">Track system events and user actions</p>
      <div class="mt-3 h-px w-12 bg-amber-300" />
    </div>

    <!-- Error & Loading -->
    <p v-if="error" class="text-sm text-rose-500">{{ error }}</p>
    <p v-else-if="loading" class="text-sm text-stone-400">Loading logs...</p>

    <!-- Empty State -->
    <div v-else-if="logs.length === 0" class="text-center py-12">
      <p class="text-stone-400">No activity logs available.</p>
    </div>

    <!-- Main Content: Table + Detail -->
    <div v-else class="grid gap-8 xl:grid-cols-[minmax(0,1fr),320px]">
      <!-- Logs Table -->
      <div class="border border-stone-100 rounded-2xl overflow-hidden bg-white/40 backdrop-blur-sm">
        <table class="w-full text-left">
          <thead class="border-b border-stone-100">
            <tr>
              <th class="px-5 py-4 text-xs font-medium uppercase tracking-wider text-stone-400">Action</th>
              <th class="px-5 py-4 text-xs font-medium uppercase tracking-wider text-stone-400">User</th>
              <th class="px-5 py-4 text-xs font-medium uppercase tracking-wider text-stone-400">Institution</th>
              <th class="px-5 py-4 text-xs font-medium uppercase tracking-wider text-stone-400">Created</th>
              <th class="px-5 py-4 text-xs font-medium uppercase tracking-wider text-stone-400"></th>
            </tr>
          </thead>
          <tbody class="divide-y divide-stone-50">
            <tr v-for="log in logs" :key="Number(log.id)" class="hover:bg-stone-50/50 transition-colors">
              <td class="px-5 py-3">
                <span class="inline-flex rounded-full bg-stone-100 px-2.5 py-0.5 text-xs font-medium text-stone-600">
                  {{ log.action || 'event' }}
                </span>
              </td>
              <td class="px-5 py-3 text-sm text-stone-600">{{ log.user?.email || '-' }}</td>
              <td class="px-5 py-3 text-sm text-stone-500">{{ log.institution?.name || '-' }}</td>
              <td class="px-5 py-3 text-sm text-stone-400">{{ log.created_at?.split('T')[0] || '-' }}</td>
              <td class="px-5 py-3">
                <div class="flex items-center gap-2">
                  <button @click="loadLogDetail(Number(log.id))" class="text-xs text-stone-400 hover:text-stone-600 transition-colors">View</button>
                  <button @click="removeLog(Number(log.id))" class="text-xs text-rose-400 hover:text-rose-600 transition-colors">Delete</button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Detail Panel -->
      <div class="border border-stone-100 rounded-2xl bg-white/40 backdrop-blur-sm p-5">
        <p class="text-xs font-semibold uppercase tracking-wider text-stone-400">Detail</p>
        <p class="mt-1 text-sm text-stone-500">Log information</p>
        
        <div class="mt-4">
          <p v-if="detailLoading" class="text-sm text-stone-400">Loading...</p>
          <p v-else-if="detailError" class="text-sm text-rose-500">{{ detailError }}</p>
          <p v-else-if="!logDetail" class="text-sm text-stone-400">Select a log to view details</p>
          <div v-else class="space-y-3">
            <div class="border-b border-stone-100 pb-2">
              <p class="text-[10px] uppercase tracking-wider text-stone-400">Action</p>
              <p class="text-sm font-medium text-stone-700">{{ logDetail.action }}</p>
            </div>
            <div class="border-b border-stone-100 pb-2">
              <p class="text-[10px] uppercase tracking-wider text-stone-400">User</p>
              <p class="text-sm text-stone-600">{{ logDetail.user?.email || logDetail.user_id || '-' }}</p>
            </div>
            <div class="border-b border-stone-100 pb-2">
              <p class="text-[10px] uppercase tracking-wider text-stone-400">Institution</p>
              <p class="text-sm text-stone-600">{{ logDetail.institution?.name || logDetail.institution_id || '-' }}</p>
            </div>
            <div>
              <p class="text-[10px] uppercase tracking-wider text-stone-400">Created</p>
              <p class="text-sm text-stone-500">{{ logDetail.created_at || '-' }}</p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>