<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { smartQueueApi } from '@/services/smartQueueApi'
import { toApiError } from '@/utils/http'

const loading = ref(false)
const error = ref('')
const busyId = ref<number | null>(null)
const appointments = ref<any[]>([])

const hasAppointments = computed(() => appointments.value.length > 0)

function toneFor(status: string): string {
  if (status === 'completed') return 'success'
  if (status === 'pending' || status === 'confirmed') return 'warning'
  return 'default'
}

async function loadAppointments(): Promise<void> {
  loading.value = true
  error.value = ''

  try {
    const response = await smartQueueApi.appointments({ per_page: 100 })
    appointments.value = response.data.data.data || []
  } catch (err) {
    error.value = toApiError(err).message
  } finally {
    loading.value = false
  }
}

async function markCompleted(id: number): Promise<void> {
  busyId.value = id
  error.value = ''

  try {
    await smartQueueApi.completeAppointment(id)
    await loadAppointments()
  } catch (err) {
    error.value = toApiError(err).message
  } finally {
    busyId.value = null
  }
}

onMounted(loadAppointments)
</script>

<template>
  <div class="space-y-8">
    <!-- Header -->
    <div>
      <p class="text-[11px] font-semibold uppercase tracking-[0.3em] text-stone-400">Appointments</p>
      <h1 class="mt-2 text-3xl font-light tracking-tight text-stone-800">Track and Complete Appointments</h1>
      <p class="mt-1 text-stone-500">Manage and update appointment statuses</p>
      <div class="mt-3 h-px w-12 bg-amber-300" />
    </div>

    <!-- Loading & Error -->
    <p v-if="error" class="text-sm text-rose-500">{{ error }}</p>
    <p v-else-if="loading" class="text-sm text-stone-400">Loading appointments...</p>

    <!-- Empty State -->
    <div v-else-if="!hasAppointments" class="text-center py-12">
      <p class="text-stone-400">No appointments yet.</p>
    </div>

    <!-- Appointments Table -->
    <div v-else class="border border-stone-100 rounded-2xl overflow-hidden bg-white/40 backdrop-blur-sm">
      <div class="overflow-x-auto">
        <table class="w-full text-left">
          <thead class="border-b border-stone-100">
            <tr>
              <th class="px-5 py-4 text-xs font-medium uppercase tracking-wider text-stone-400">Reference</th>
              <th class="px-5 py-4 text-xs font-medium uppercase tracking-wider text-stone-400">Citizen</th>
              <th class="px-5 py-4 text-xs font-medium uppercase tracking-wider text-stone-400">Service</th>
              <th class="px-5 py-4 text-xs font-medium uppercase tracking-wider text-stone-400">Date</th>
              <th class="px-5 py-4 text-xs font-medium uppercase tracking-wider text-stone-400">Status</th>
              <th class="px-5 py-4 text-xs font-medium uppercase tracking-wider text-stone-400">Queue</th>
              <th class="px-5 py-4 text-xs font-medium uppercase tracking-wider text-stone-400"></th>
            </tr>
          </thead>
          <tbody class="divide-y divide-stone-50">
            <tr v-for="appointment in appointments" :key="Number(appointment.id)" class="hover:bg-stone-50/50 transition-colors">
              <td class="px-5 py-3 text-sm font-mono text-stone-600">{{ appointment.reference_code || '-' }}</td>
              <td class="px-5 py-3 text-sm text-stone-700">{{ appointment.user?.first_name }} {{ appointment.user?.last_name }}</td>
              <td class="px-5 py-3 text-sm text-stone-600">{{ appointment.service?.name || appointment.service_id }}</td>
              <td class="px-5 py-3 text-sm text-stone-500">{{ appointment.appointment_date }}</td>
              <td class="px-5 py-3">
                <span 
                  class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium"
                  :class="{
                    'bg-emerald-100 text-emerald-700': toneFor(String(appointment.status)) === 'success',
                    'bg-amber-100 text-amber-700': toneFor(String(appointment.status)) === 'warning',
                    'bg-stone-100 text-stone-600': toneFor(String(appointment.status)) === 'default'
                  }"
                >
                  {{ appointment.status }}
                </span>
              </td>
              <td class="px-5 py-3 text-sm text-stone-600">
                #{{ appointment.queue_position ?? '-' }}
                <span class="text-xs text-stone-400">({{ appointment.estimated_waiting_minutes ?? 0 }} min)</span>
              </td>
              <td class="px-5 py-3">
                <button
                  v-if="String(appointment.status) !== 'completed'"
                  :disabled="busyId === Number(appointment.id)"
                  class="rounded-full bg-stone-800 px-4 py-1.5 text-xs font-medium text-white transition-all duration-200 hover:bg-stone-700 hover:-translate-y-0.5 disabled:opacity-50 disabled:hover:translate-y-0"
                  @click="markCompleted(Number(appointment.id))"
                >
                  {{ busyId === Number(appointment.id) ? 'Updating...' : 'Complete' }}
                </button>
                <span v-else class="text-xs text-stone-400">Completed</span>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</template>