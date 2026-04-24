<script setup lang="ts">
import { computed, onMounted, onUnmounted, reactive, ref } from 'vue'
import EmptyState from '@/components/EmptyState.vue'
import LoadingState from '@/components/LoadingState.vue'
import PageHeader from '@/components/PageHeader.vue'
import IconButton from '@/components/ui/IconButton.vue'
import { smartQueueApi } from '@/services/smartQueueApi'
import { useAuthStore } from '@/stores/auth'
import { toApiError } from '@/utils/http'

const authStore = useAuthStore()
const isCitizen = computed(() => authStore.role === 'citizen')
const canComplete = computed(() => authStore.role === 'institution')
const canTrackQueue = computed(() => isCitizen.value)

const countersByService = computed(() => {
  if (!form.service_id) return []
  return serviceCounters.value.filter((c: any) => Number(c.service_id) === Number(form.service_id))
})

const loading = ref(false)
const saving = ref(false)
const error = ref('')
const appointments = ref<any[]>([])
const services = ref<any[]>([])
const serviceCounters = ref<any[]>([])

const queueLoading = ref(false)
const queueError = ref('')
const selectedAppointmentId = ref<number | null>(null)
const queueInfo = ref<any | null>(null)
const detailLoading = ref(false)
const detailError = ref('')
const appointmentDetail = ref<any | null>(null)

const form = reactive({
  service_id: 0,
  service_counter_id: 0,
  appointment_date: '',
})

let queuePollTimer: number | null = null

async function loadData() {
  loading.value = true
  error.value = ''

  try {
    const [appointmentsResponse, servicesResponse, countersResponse] = await Promise.all([
      smartQueueApi.appointments(),
      smartQueueApi.services(),
      smartQueueApi.serviceCounters({ per_page: 100 }),
    ])

    appointments.value = appointmentsResponse.data.data.data
    services.value = servicesResponse.data.data.data
    serviceCounters.value = countersResponse.data.data.data || []
  } catch (err) {
    error.value = toApiError(err).message
  } finally {
    loading.value = false
  }
}

async function createAppointment() {
  saving.value = true
  error.value = ''

  try {
    await smartQueueApi.createAppointment({
      service_id: Number(form.service_id),
      service_counter_id: form.service_counter_id ? Number(form.service_counter_id) : null,
      appointment_date: form.appointment_date,
    })
    form.service_id = 0
    form.service_counter_id = 0
    form.appointment_date = ''
    await loadData()
  } catch (err) {
    error.value = toApiError(err).message
  } finally {
    saving.value = false
  }
}

async function cancelAppointment(id: number) {
  error.value = ''
  try {
    await smartQueueApi.deleteAppointment(id)
    await loadData()
  } catch (err) {
    error.value = toApiError(err).message
  }
}

async function completeAppointment(id: number) {
  error.value = ''
  try {
    await smartQueueApi.completeAppointment(id)
    await loadData()
    if (selectedAppointmentId.value === id) {
      await fetchQueuePosition(id)
    }
  } catch (err) {
    error.value = toApiError(err).message
  }
}

async function fetchQueuePosition(appointmentId: number) {
  queueLoading.value = true
  queueError.value = ''

  try {
    const response = await smartQueueApi.appointmentQueuePosition(appointmentId)
    queueInfo.value = response.data.data
  } catch (err) {
    queueError.value = toApiError(err).message
  } finally {
    queueLoading.value = false
  }
}

async function fetchAppointmentDetail(appointmentId: number) {
  detailLoading.value = true
  detailError.value = ''

  try {
    const response = await smartQueueApi.appointment(appointmentId)
    appointmentDetail.value = response.data.data
  } catch (err) {
    detailError.value = toApiError(err).message
  } finally {
    detailLoading.value = false
  }
}

function startQueuePolling(appointmentId: number) {
  if (!canTrackQueue.value) {
    return
  }

  selectedAppointmentId.value = appointmentId
  fetchQueuePosition(appointmentId)

  if (queuePollTimer) {
    window.clearInterval(queuePollTimer)
  }

  queuePollTimer = window.setInterval(() => {
    if (selectedAppointmentId.value) {
      fetchQueuePosition(selectedAppointmentId.value)
    }
  }, 10000)
}

function stopQueuePolling() {
  selectedAppointmentId.value = null
  queueInfo.value = null

  if (queuePollTimer) {
    window.clearInterval(queuePollTimer)
    queuePollTimer = null
  }
}

onMounted(loadData)
onUnmounted(stopQueuePolling)
</script>

<template>
  <div class="space-y-8">
    <PageHeader title="Appointments" subtitle="Create, track, and manage appointments with live queue info." />

    <!-- Book Appointment Form (Citizen only) -->
    <div v-if="isCitizen" class="rounded-2xl bg-white/40 backdrop-blur-sm border border-stone-100 p-5">
      <h2 class="text-xs font-semibold uppercase tracking-wider text-stone-500">Book Appointment</h2>
      <p class="mt-1 text-sm text-stone-400">Schedule a new appointment for a service</p>
      
      <form class="mt-4 grid grid-cols-1 gap-3 md:grid-cols-3" @submit.prevent="createAppointment">
        <div>
          <label class="block text-xs font-medium text-stone-500 mb-1">Service *</label>
          <select v-model.number="form.service_id" required class="w-full rounded-full border border-stone-200 px-4 py-2 text-sm bg-white/60 focus:outline-none focus:border-stone-300">
            <option :value="0" disabled>-- Select service --</option>
            <option v-for="service in services" :key="Number(service.id)" :value="Number(service.id)">
              {{ service.name }}
            </option>
          </select>
        </div>

        <div v-if="countersByService.length > 0">
          <label class="block text-xs font-medium text-stone-500 mb-1">Preferred Counter (optional)</label>
          <select v-model.number="form.service_counter_id" class="w-full rounded-full border border-stone-200 px-4 py-2 text-sm bg-white/60 focus:outline-none focus:border-stone-300">
            <option :value="0">-- Any available --</option>
            <option v-for="counter in countersByService" :key="Number(counter.id)" :value="Number(counter.id)">
              {{ counter.name }} (Counter #{{ counter.counter_number }})
            </option>
          </select>
        </div>

        <div :class="{ 'md:col-span-3': !countersByService.length, 'md:col-span-1': countersByService.length }">
          <label class="block text-xs font-medium text-stone-500 mb-1">Appointment Date & Time *</label>
          <input 
            v-model="form.appointment_date" 
            type="datetime-local" 
            required 
            class="w-full rounded-full border border-stone-200 px-4 py-2 text-sm bg-white/60 focus:outline-none focus:border-stone-300"
          />
        </div>

        <IconButton 
          icon="calendar"
          label="Book Appointment"
          variant="primary"
          type="submit"
          :disabled="saving || !form.service_id"
          :class="{ 'md:col-span-3': !countersByService.length, 'md:col-span-1': countersByService.length }"
        />
      </form>
    </div>

    <!-- Loading & Error -->
    <LoadingState v-if="loading" />
    <p v-else-if="error" class="text-sm text-rose-500">{{ error }}</p>

    <!-- Empty State -->
    <EmptyState v-else-if="appointments.length === 0" message="No appointments yet." />

    <!-- Main Grid: Table + Sidebar -->
    <div v-else class="grid gap-6 lg:grid-cols-[minmax(0,1fr),320px]">
      <!-- Appointments Table -->
      <div class="border border-stone-100 rounded-2xl overflow-hidden bg-white/40 backdrop-blur-sm">
        <div class="overflow-x-auto">
          <table class="w-full text-left">
            <thead class="border-b border-stone-100">
              <tr>
                <th class="px-5 py-4 text-xs font-medium uppercase tracking-wider text-stone-400">Reference</th>
                <th class="px-5 py-4 text-xs font-medium uppercase tracking-wider text-stone-400">Service</th>
                <th class="px-5 py-4 text-xs font-medium uppercase tracking-wider text-stone-400">Date</th>
                <th class="px-5 py-4 text-xs font-medium uppercase tracking-wider text-stone-400">Status</th>
                <th class="px-5 py-4 text-xs font-medium uppercase tracking-wider text-stone-400">Queue</th>
                <th class="px-5 py-4 text-xs font-medium uppercase tracking-wider text-stone-400"></th>
              </tr>
            </thead>
            <tbody class="divide-y divide-stone-50">
              <tr v-for="appointment in appointments" :key="Number(appointment.id)" class="hover:bg-stone-50/30 transition-colors">
                <td class="px-5 py-3 text-sm font-mono text-stone-600">{{ appointment.reference_code || '-' }}</td>
                <td class="px-5 py-3 text-sm text-stone-700">{{ appointment.service?.name || appointment.service_id }}</td>
                <td class="px-5 py-3 text-sm text-stone-500">{{ appointment.appointment_date }}</td>
                <td class="px-5 py-3">
                  <span 
                    class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium"
                    :class="{
                      'bg-amber-100 text-amber-700': appointment.status === 'pending' || appointment.status === 'confirmed',
                      'bg-emerald-100 text-emerald-700': appointment.status === 'completed',
                      'bg-rose-100 text-rose-700': appointment.status === 'cancelled'
                    }"
                  >
                    {{ appointment.status }}
                  </span>
                </td>
                <td class="px-5 py-3 text-sm text-stone-600">
                  #{{ appointment.queue_position ?? '-' }}
                  <span class="text-xs text-stone-400">({{ appointment.estimated_waiting_minutes ?? 0 }}m)</span>
                </td>
                <td class="px-5 py-3">
                  <div class="flex items-center gap-2">
                    <IconButton 
                      v-if="canTrackQueue" 
                      icon="tracker"
                      size="sm"
                      variant="ghost"
                      @click="startQueuePolling(Number(appointment.id))"
                    />
                    <IconButton 
                      icon="view"
                      size="sm"
                      variant="ghost"
                      @click="fetchAppointmentDetail(Number(appointment.id))"
                    />
                    <IconButton 
                      v-if="isCitizen" 
                      icon="delete"
                      size="sm"
                      variant="ghost"
                      @click="cancelAppointment(Number(appointment.id))"
                    />
                    <IconButton 
                      v-if="canComplete" 
                      icon="check"
                      size="sm"
                      variant="ghost"
                      @click="completeAppointment(Number(appointment.id))"
                    />
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Right Sidebar -->
      <div class="space-y-6">
        <!-- Queue Tracker -->
        <div v-if="canTrackQueue" class="border border-stone-100 rounded-2xl bg-white/40 backdrop-blur-sm p-5">
          <div class="flex items-center justify-between mb-4">
            <p class="text-xs font-semibold uppercase tracking-wider text-stone-500">Queue Tracker</p>
            <IconButton 
              v-if="selectedAppointmentId" 
              icon="close"
              size="sm"
              variant="ghost"
              @click="stopQueuePolling"
            />
          </div>

          <p v-if="!selectedAppointmentId" class="text-sm text-stone-400">Select an appointment and click Track.</p>
          <p v-else-if="queueLoading" class="text-sm text-stone-400">Refreshing queue status...</p>
          <p v-else-if="queueError" class="text-sm text-rose-500">{{ queueError }}</p>

          <div v-else-if="queueInfo" class="space-y-3">
            <div class="border-b border-stone-100 pb-2">
              <p class="text-[10px] uppercase tracking-wider text-stone-400">Status</p>
              <p class="text-sm font-medium text-stone-700">{{ queueInfo.status }}</p>
            </div>
            <div class="border-b border-stone-100 pb-2">
              <p class="text-[10px] uppercase tracking-wider text-stone-400">Queue Position</p>
              <p class="text-2xl font-light text-stone-800">#{{ queueInfo.queue_position ?? '-' }}</p>
            </div>
            <div class="border-b border-stone-100 pb-2">
              <p class="text-[10px] uppercase tracking-wider text-stone-400">Current Counter</p>
              <p class="text-sm text-stone-600">{{ queueInfo.queue_current_position ?? '-' }}</p>
            </div>
            <div>
              <p class="text-[10px] uppercase tracking-wider text-stone-400">Estimated Wait</p>
              <p class="text-sm text-stone-600">{{ queueInfo.estimated_waiting_minutes }} minutes</p>
            </div>
          </div>
        </div>

        <!-- Appointment Detail -->
        <div class="border border-stone-100 rounded-2xl bg-white/40 backdrop-blur-sm p-5">
          <p class="text-xs font-semibold uppercase tracking-wider text-stone-500 mb-4">Appointment Detail</p>
          
          <p v-if="detailLoading" class="text-sm text-stone-400">Loading detail...</p>
          <p v-else-if="detailError" class="text-sm text-rose-500">{{ detailError }}</p>
          <p v-else-if="!appointmentDetail" class="text-sm text-stone-400">Select an appointment and click View.</p>
          
          <div v-else class="space-y-3">
            <div class="border-b border-stone-100 pb-2">
              <p class="text-[10px] uppercase tracking-wider text-stone-400">Reference</p>
              <p class="text-sm font-mono text-stone-600">{{ appointmentDetail.reference_code || '-' }}</p>
            </div>
            <div class="border-b border-stone-100 pb-2">
              <p class="text-[10px] uppercase tracking-wider text-stone-400">Citizen</p>
              <p class="text-sm text-stone-700">{{ appointmentDetail.user?.first_name }} {{ appointmentDetail.user?.last_name }}</p>
            </div>
            <div class="border-b border-stone-100 pb-2">
              <p class="text-[10px] uppercase tracking-wider text-stone-400">Service</p>
              <p class="text-sm text-stone-600">{{ appointmentDetail.service?.name || appointmentDetail.service_id }}</p>
            </div>
            <div class="border-b border-stone-100 pb-2">
              <p class="text-[10px] uppercase tracking-wider text-stone-400">Date</p>
              <p class="text-sm text-stone-500">{{ appointmentDetail.appointment_date }}</p>
            </div>
            <div>
              <p class="text-[10px] uppercase tracking-wider text-stone-400">Status</p>
              <p class="text-sm text-stone-600">{{ appointmentDetail.status }}</p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>