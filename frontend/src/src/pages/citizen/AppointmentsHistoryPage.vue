<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import EmptyState from '@/components/EmptyState.vue'
import LoadingState from '@/components/LoadingState.vue'
import PageHeader from '@/components/PageHeader.vue'
import { smartQueueApi } from '@/services/smartQueueApi'
import { toApiError } from '@/utils/http'

const loading = ref(false)
const error = ref('')
const appointments = ref<any[]>([])

const completedAppointments = computed(() =>
  appointments.value.filter((appointment: any) => {
    const status = String(appointment.status)
    return status === 'completed' || status === 'cancelled'
  }),
)

async function loadData() {
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

onMounted(loadData)
</script>

<template>
  <div class="space-y-8">
    <PageHeader title="Appointments History" subtitle="Completed and cancelled appointments." />

    <LoadingState v-if="loading" />
    <p v-else-if="error" class="text-sm text-rose-500">{{ error }}</p>
    <EmptyState v-else-if="completedAppointments.length === 0" message="No completed appointments." icon="none" />

    <div v-else class="border border-stone-100 rounded-2xl overflow-hidden bg-white/40 backdrop-blur-sm">
      <div class="px-5 py-3 border-b border-stone-100 text-xs font-semibold uppercase tracking-wider text-stone-500">Done Appointments</div>
      <div class="overflow-x-auto">
        <table class="w-full text-left">
          <thead class="border-b border-stone-100">
            <tr>
              <th class="px-5 py-4 text-xs font-medium uppercase tracking-wider text-stone-400">Reference</th>
              <th class="px-5 py-4 text-xs font-medium uppercase tracking-wider text-stone-400">Service</th>
              <th class="px-5 py-4 text-xs font-medium uppercase tracking-wider text-stone-400">Date</th>
              <th class="px-5 py-4 text-xs font-medium uppercase tracking-wider text-stone-400">Status</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-stone-50">
            <tr v-for="appointment in completedAppointments" :key="Number(appointment.id)" class="hover:bg-stone-50/30 transition-colors">
              <td class="px-5 py-3 text-sm font-mono text-stone-600">{{ appointment.reference_code || '-' }}</td>
              <td class="px-5 py-3 text-sm text-stone-700">{{ appointment.service?.name || appointment.service_id }}</td>
              <td class="px-5 py-3 text-sm text-stone-500">{{ appointment.appointment_date }}</td>
              <td class="px-5 py-3 text-sm text-stone-600">{{ appointment.status }}</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</template>