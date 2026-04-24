<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { smartQueueApi } from '@/services/smartQueueApi'
import { toApiError } from '@/utils/http'
import BaseBadge from '@/components/ui/BaseBadge.vue'
import BaseButton from '@/components/ui/BaseButton.vue'
import BaseCard from '@/components/ui/BaseCard.vue'
import BaseTable from '@/components/ui/BaseTable.vue'

const loading = ref(false)
const error = ref('')
const queues = ref<any[]>([])
const services = ref<any[]>([])
const appointments = ref<any[]>([])

const selectedServiceId = ref<number>(0)

const queueRows = computed(() => {
  const scopedAppointments = selectedServiceId.value
    ? appointments.value.filter((item) => Number(item.service_id) === selectedServiceId.value)
    : appointments.value

  return scopedAppointments.sort((a, b) => Number(a.queue_position || 0) - Number(b.queue_position || 0))
})

async function loadData(): Promise<void> {
  loading.value = true
  error.value = ''

  try {
    const [queuesRes, servicesRes, appointmentsRes] = await Promise.all([
      smartQueueApi.queues({ per_page: 100 }),
      smartQueueApi.services({ per_page: 100 }),
      smartQueueApi.appointments({ per_page: 100 }),
    ])

    queues.value = queuesRes.data.data.data || []
    services.value = servicesRes.data.data.data || []
    appointments.value = appointmentsRes.data.data.data || []
  } catch (err) {
    error.value = toApiError(err).message
  } finally {
    loading.value = false
  }
}

async function moveForward(queue: any): Promise<void> {
  try {
    await smartQueueApi.updateQueue(Number(queue.id), {
      current_position: Number(queue.current_position || 0) + 1,
    })
    await loadData()
  } catch (err) {
    error.value = toApiError(err).message
  }
}

async function closeQueue(queue: any): Promise<void> {
  try {
    await smartQueueApi.updateQueue(Number(queue.id), { status: 'closed' })
    await loadData()
  } catch (err) {
    error.value = toApiError(err).message
  }
}

onMounted(loadData)
</script>

<template>
  <div class="space-y-8">
    <!-- Header -->
    <div>
      <p class="text-[11px] font-semibold uppercase tracking-[0.3em] text-stone-400">Queues</p>
      <h1 class="mt-2 text-3xl font-light tracking-tight text-stone-800">Queue Positions and Waiting Time</h1>
      <p class="mt-1 text-stone-500">Monitor and manage service queues</p>
      <div class="mt-3 h-px w-12 bg-amber-300" />
    </div>

    <!-- Error & Loading -->
    <p v-if="error" class="text-sm text-rose-500">{{ error }}</p>
    <p v-else-if="loading" class="text-sm text-stone-400">Loading queues...</p>

    <div v-else class="grid gap-6 xl:grid-cols-[1.1fr,1fr]">
      <!-- Queues per Service -->
      <BaseCard title="Queues per Service" subtitle="Queue Sessions">
        <div v-if="queues.length === 0" class="text-sm text-stone-400">No queues found.</div>
        <div v-else class="space-y-3">
          <article v-for="queue in queues" :key="Number(queue.id)" class="rounded-xl border border-stone-100 bg-white/30 p-4 transition-all duration-200 hover:border-stone-200">
            <div class="flex flex-wrap items-center justify-between gap-2">
              <div>
                <p class="font-medium text-stone-800">{{ queue.service?.name || `Service ${queue.service_id}` }}</p>
                <p class="text-xs text-stone-400 mt-0.5">Current {{ queue.current_position }} / {{ queue.total_count }}</p>
              </div>
              <BaseBadge :tone="String(queue.status) === 'active' ? 'success' : 'default'">{{ queue.status }}</BaseBadge>
            </div>
            <div class="mt-4 flex flex-wrap gap-2">
              <BaseButton variant="secondary" @click="selectedServiceId = Number(queue.service_id)">View Positions</BaseButton>
              <BaseButton @click="moveForward(queue)">Move Forward</BaseButton>
              <BaseButton variant="danger" @click="closeQueue(queue)">Close Queue</BaseButton>
            </div>
          </article>
        </div>
      </BaseCard>

      <!-- Queue Positions -->
      <BaseCard title="Queue Positions" subtitle="Waiting Estimate">
        <div class="mb-4">
          <select v-model.number="selectedServiceId" class="w-full rounded-full border border-stone-200 px-4 py-2 text-sm bg-white/60 focus:outline-none focus:border-stone-300">
            <option :value="0">All services</option>
            <option v-for="service in services" :key="Number(service.id)" :value="Number(service.id)">
              {{ service.name }}
            </option>
          </select>
        </div>

        <BaseTable :headers="['Citizen', 'Position', 'Est. Waiting', 'Status']">
          <tr v-for="appointment in queueRows" :key="Number(appointment.id)" class="border-t border-stone-50">
            <td class="px-4 py-3 text-sm text-stone-700">{{ appointment.user?.first_name }} {{ appointment.user?.last_name }}</td>
            <td class="px-4 py-3 text-sm font-medium text-stone-800">#{{ appointment.queue_position ?? '-' }}</td>
            <td class="px-4 py-3 text-sm text-stone-500">{{ appointment.estimated_waiting_minutes ?? 0 }} min</td>
            <td class="px-4 py-3">
              <BaseBadge>{{ appointment.status }}</BaseBadge>
            </td>
          </tr>
          <tr v-if="queueRows.length === 0">
            <td colspan="4" class="px-4 py-8 text-center text-sm text-stone-400">
              No appointments in queue.
            </td>
          </tr>
        </BaseTable>
      </BaseCard>
    </div>
  </div>
</template>