<script setup lang="ts">
import { computed, onMounted, reactive, ref } from 'vue'
import EmptyState from '@/components/EmptyState.vue'
import LoadingState from '@/components/LoadingState.vue'
import PageHeader from '@/components/PageHeader.vue'
import { smartQueueApi } from '@/services/smartQueueApi'
import { useAuthStore } from '@/stores/auth'
import { toApiError } from '@/utils/http'

const authStore = useAuthStore()
const canManageQueues = computed(() => ['manager', 'employee'].includes(String(authStore.user?.role || '')))

const loading = ref(false)
const saving = ref(false)
const error = ref('')
const queues = ref<any[]>([])
const services = ref<any[]>([])
const detailLoading = ref(false)
const detailError = ref('')
const queueDetail = ref<any | null>(null)

const form = reactive({
  service_id: 0,
  date: '',
  current_position: 0,
  total_count: 0,
  status: 'active',
})

async function loadData() {
  loading.value = true
  error.value = ''

  try {
    const [queueResponse, servicesResponse] = await Promise.all([smartQueueApi.queues(), smartQueueApi.services()])
    queues.value = queueResponse.data.data.data
    services.value = servicesResponse.data.data.data
  } catch (err) {
    error.value = toApiError(err).message
  } finally {
    loading.value = false
  }
}

async function createQueue() {
  saving.value = true
  error.value = ''

  try {
    await smartQueueApi.createQueue({
      ...form,
      service_id: Number(form.service_id),
      current_position: Number(form.current_position),
      total_count: Number(form.total_count),
    })
    await loadData()
  } catch (err) {
    error.value = toApiError(err).message
  } finally {
    saving.value = false
  }
}

async function deleteQueue(id: number) {
  try {
    await smartQueueApi.deleteQueue(id)
    await loadData()
  } catch (err) {
    error.value = toApiError(err).message
  }
}

async function progressQueue(queue: any) {
  try {
    await smartQueueApi.updateQueue(Number(queue.id), {
      current_position: Number(queue.current_position || 0) + 1,
    })
    await loadData()
  } catch (err) {
    error.value = toApiError(err).message
  }
}

async function loadQueueDetail(id: number) {
  detailLoading.value = true
  detailError.value = ''

  try {
    const response = await smartQueueApi.queue(id)
    queueDetail.value = response.data.data
  } catch (err) {
    detailError.value = toApiError(err).message
  } finally {
    detailLoading.value = false
  }
}

onMounted(loadData)
</script>

<template>
  <section>
    <PageHeader title="Queues" subtitle="Monitor and manage queue sessions." />

    <div v-if="canManageQueues" class="mb-6 rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
      <h2 class="mb-3 text-sm font-semibold uppercase tracking-wide text-slate-500">Create Queue</h2>
      <form class="grid grid-cols-1 gap-3 md:grid-cols-3" @submit.prevent="createQueue">
        <select v-model.number="form.service_id" required class="rounded-md border border-slate-300 px-3 py-2">
          <option :value="0" disabled>Select service</option>
          <option v-for="service in services" :key="Number(service.id)" :value="Number(service.id)">
            {{ service.name }}
          </option>
        </select>
        <input v-model="form.date" type="date" required class="rounded-md border border-slate-300 px-3 py-2" />
        <select v-model="form.status" class="rounded-md border border-slate-300 px-3 py-2">
          <option value="active">active</option>
          <option value="paused">paused</option>
          <option value="closed">closed</option>
        </select>
        <button :disabled="saving" class="rounded-md bg-brand-600 px-4 py-2 font-medium text-white hover:bg-brand-700 md:col-span-3">
          {{ saving ? 'Saving...' : 'Create queue' }}
        </button>
      </form>
    </div>

    <LoadingState v-if="loading" />
    <p v-else-if="error" class="mb-4 rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-700">{{ error }}</p>
    <EmptyState v-else-if="queues.length === 0" message="No queue sessions found." />

    <div v-else class="grid gap-4 xl:grid-cols-[minmax(0,1fr),320px]">
      <div class="grid gap-4 sm:grid-cols-2">
        <article v-for="queue in queues" :key="Number(queue.id)" class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
        <h3 class="text-lg font-semibold text-slate-900">Queue #{{ queue.id }}</h3>
        <p class="text-sm text-slate-500">{{ queue.service?.name || `Service ${queue.service_id}` }}</p>
        <div class="mt-2 space-y-1 text-xs text-slate-600">
          <p>Date: {{ queue.date }}</p>
          <p>Status: {{ queue.status }}</p>
          <p>Current Position: {{ queue.current_position }}</p>
          <p>Total Count: {{ queue.total_count }}</p>
        </div>
        <div class="mt-3 flex items-center gap-3">
          <button class="text-sm text-[#136d7b] hover:underline" @click="loadQueueDetail(Number(queue.id))">View</button>
          <button class="text-sm text-emerald-700 hover:underline" @click="progressQueue(queue)">Progress queue</button>
          <button v-if="canManageQueues" class="text-sm text-red-600 hover:underline" @click="deleteQueue(Number(queue.id))">Delete</button>
        </div>
        </article>
      </div>

      <aside class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
        <h3 class="mb-2 text-sm font-semibold uppercase tracking-wide text-slate-500">Queue Detail</h3>
        <p v-if="detailLoading" class="text-sm text-slate-500">Loading queue detail...</p>
        <p v-else-if="detailError" class="text-sm text-red-600">{{ detailError }}</p>
        <p v-else-if="!queueDetail" class="text-sm text-slate-500">Select a queue card and click View.</p>
        <div v-else class="space-y-1 text-sm text-slate-700">
          <p><span class="font-medium">ID:</span> {{ queueDetail.id }}</p>
          <p><span class="font-medium">Service:</span> {{ queueDetail.service?.name || queueDetail.service_id }}</p>
          <p><span class="font-medium">Date:</span> {{ queueDetail.date }}</p>
          <p><span class="font-medium">Status:</span> {{ queueDetail.status }}</p>
          <p><span class="font-medium">Current Position:</span> {{ queueDetail.current_position }}</p>
          <p><span class="font-medium">Total Count:</span> {{ queueDetail.total_count }}</p>
        </div>
      </aside>
    </div>
  </section>
</template>
