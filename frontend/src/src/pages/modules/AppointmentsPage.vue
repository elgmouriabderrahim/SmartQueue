<script setup lang="ts">
import { computed, onMounted, onUnmounted, reactive, ref } from 'vue'
import { useRouter } from 'vue-router'
import EmptyState from '@/components/EmptyState.vue'
import LoadingState from '@/components/LoadingState.vue'
import PageHeader from '@/components/PageHeader.vue'
import IconButton from '@/components/ui/IconButton.vue'
import { smartQueueApi } from '@/services/smartQueueApi'
import { useAuthStore } from '@/stores/auth'
import { toApiError } from '@/utils/http'

const authStore = useAuthStore()
const router = useRouter()
const isCitizen = computed(() => authStore.role === 'citizen')
const canComplete = computed(() => ['manager', 'employee', 'admin'].includes(String(authStore.userRole || '')))
const canTrackQueue = computed(() => isCitizen.value)

const loading = ref(false)
const saving = ref(false)
const error = ref('')
const appointments = ref<any[]>([])

const queueLoading = ref(false)
const queueError = ref('')
const selectedAppointmentId = ref<number | null>(null)
const queueInfo = ref<any | null>(null)
const detailLoading = ref(false)
const detailError = ref('')
const appointmentDetail = ref<any | null>(null)
const showEdit = ref(false)
const editId = ref<number | null>(null)

const editForm = reactive({
  appointment_date: '',
})

const activeAppointments = computed(() =>
  appointments.value.filter((a: any) => !['completed', 'cancelled'].includes(String(a.status))),
)

const visibleAppointments = computed(() => {
  if (isCitizen.value) {
    return appointments.value.filter((a: any) => {
      const status = String(a.status)
      return status !== 'completed' && status !== 'cancelled'
    })
  }

  return activeAppointments.value
})

const historyAppointments = computed(() =>
  appointments.value.filter((a: any) => ['completed', 'cancelled'].includes(String(a.status))),
)

let queuePollTimer: number | null = null

async function loadData() {
  loading.value = true
  error.value = ''

  try {
    const appointmentsResponse = await smartQueueApi.appointments({ per_page: 100 })

    appointments.value = appointmentsResponse.data.data.data
  } catch (err) {
    error.value = toApiError(err).message
  } finally {
    loading.value = false
  }
}

function openEdit(appointment: any) {
  editId.value = Number(appointment.id)
  const dt = new Date(String(appointment.appointment_date))
  if (!Number.isNaN(dt.getTime())) {
    editForm.appointment_date = new Date(dt.getTime() - dt.getTimezoneOffset() * 60000)
      .toISOString()
      .slice(0, 16)
  } else {
    editForm.appointment_date = ''
  }
  showEdit.value = true
}

function closeEdit() {
  showEdit.value = false
  editId.value = null
  editForm.appointment_date = ''
}

async function updateAppointment() {
  if (!editId.value || !editForm.appointment_date) return

  saving.value = true
  error.value = ''

  try {
    await smartQueueApi.updateAppointment(editId.value, {
      appointment_date: editForm.appointment_date,
    })
    closeEdit()
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

function startMessageFromAppointmentDetail(): void {
  if (!appointmentDetail.value) return

  const appointmentId = Number(appointmentDetail.value.id || 0)
  if (appointmentId <= 0) return

  if (isCitizen.value) {
    const institutionId = Number(appointmentDetail.value.service?.institution_id || 0)
    if (institutionId > 0) {
      router.push({
        path: '/app/citizen/messages',
        query: {
          institution_id: String(institutionId),
          appointment_id: String(appointmentId),
        },
      })
    }
    return
  }

  const recipientId = Number(appointmentDetail.value.user?.id || appointmentDetail.value.user_id || 0)
  if (recipientId > 0) {
    router.push({
      path: '/app/employee/messages',
      query: {
        recipient_id: String(recipientId),
        appointment_id: String(appointmentId),
      },
    })
  }
}

onMounted(loadData)
onUnmounted(stopQueuePolling)
</script>

<template>
  <div class="space-y-8">
    <PageHeader title="My Appointments" subtitle="Track and manage your active appointments." />

    <div v-if="isCitizen" class="rounded-2xl border border-stone-100 bg-white/40 p-4 text-sm text-stone-500">
      Book new appointments from institution or service pages only.
      <router-link to="/services" class="ml-2 font-medium text-stone-700 hover:text-stone-900">Browse services</router-link>
      <router-link to="/app/citizen/appointments-history" class="ml-3 font-medium text-stone-700 hover:text-stone-900">Appointments history</router-link>
    </div>

    <!-- Loading & Error -->
    <LoadingState v-if="loading" />
    <p v-else-if="error" class="text-sm text-rose-500">{{ error }}</p>

    <!-- Empty State -->
    <div v-else-if="isCitizen && visibleAppointments.length === 0" class="rounded-2xl border border-stone-100 bg-white/40 p-6">
      <p class="text-sm text-stone-500">No booked appointments.</p>
      <router-link to="/services" class="mt-2 inline-block text-sm font-medium text-stone-700 hover:text-stone-900">Browse services</router-link>
    </div>
    <EmptyState v-else-if="appointments.length === 0" message="No appointments yet." icon="none" />

    <!-- Main Grid: Table + Sidebar -->
    <div v-else class="grid gap-6 lg:grid-cols-[minmax(0,1fr),320px]">
      <!-- Appointments Table -->
      <div class="border border-stone-100 rounded-2xl overflow-hidden bg-white/40 backdrop-blur-sm">
        <div class="px-5 py-3 border-b border-stone-100 text-xs font-semibold uppercase tracking-wider text-stone-500">{{ isCitizen ? 'Appointments' : 'Active Appointments' }}</div>
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
              <tr v-for="appointment in visibleAppointments" :key="Number(appointment.id)" class="hover:bg-stone-50/30 transition-colors">
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
                      v-if="isCitizen && ['pending', 'confirmed'].includes(String(appointment.status))"
                      icon="edit"
                      size="sm"
                      variant="ghost"
                      @click="openEdit(appointment)"
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
              <tr v-if="visibleAppointments.length === 0">
                <td colspan="6" class="px-5 py-6 text-sm text-stone-400">No active appointments.</td>
              </tr>
            </tbody>
          </table>
        </div>

        <div v-if="!isCitizen" class="px-5 py-3 border-y border-stone-100 text-xs font-semibold uppercase tracking-wider text-stone-500">Appointment History</div>
        <div v-if="!isCitizen" class="overflow-x-auto">
          <table class="w-full text-left">
            <thead class="border-b border-stone-100">
              <tr>
                <th class="px-5 py-3 text-xs font-medium uppercase tracking-wider text-stone-400">Reference</th>
                <th class="px-5 py-3 text-xs font-medium uppercase tracking-wider text-stone-400">Service</th>
                <th class="px-5 py-3 text-xs font-medium uppercase tracking-wider text-stone-400">Date</th>
                <th class="px-5 py-3 text-xs font-medium uppercase tracking-wider text-stone-400">Status</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-stone-50">
              <tr v-for="appointment in historyAppointments" :key="`h-${Number(appointment.id)}`" class="hover:bg-stone-50/30 transition-colors">
                <td class="px-5 py-3 text-sm font-mono text-stone-600">{{ appointment.reference_code || '-' }}</td>
                <td class="px-5 py-3 text-sm text-stone-700">{{ appointment.service?.name || appointment.service_id }}</td>
                <td class="px-5 py-3 text-sm text-stone-500">{{ appointment.appointment_date }}</td>
                <td class="px-5 py-3 text-sm text-stone-600">{{ appointment.status }}</td>
              </tr>
              <tr v-if="historyAppointments.length === 0">
                <td colspan="4" class="px-5 py-6 text-sm text-stone-400">No appointment history yet.</td>
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
          <div class="mb-4 flex items-center justify-between">
            <p class="text-xs font-semibold uppercase tracking-wider text-stone-500">Appointment Detail</p>
            <IconButton
              v-if="appointmentDetail"
              icon="message"
              size="sm"
              variant="ghost"
              @click="startMessageFromAppointmentDetail"
            />
          </div>
          
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

    <div v-if="showEdit" class="fixed inset-0 z-50 flex items-center justify-center bg-black/20 backdrop-blur-sm" @click.self="closeEdit">
      <div class="w-full max-w-md rounded-2xl bg-white p-6 shadow-xl max-h-[90vh] overflow-y-auto">
        <h3 class="text-xl font-light tracking-tight text-stone-800">Update Appointment</h3>
        <p class="mt-1 text-sm text-stone-500">Change your appointment date and time.</p>

        <form class="mt-4 space-y-4" @submit.prevent="updateAppointment">
          <div>
            <label class="block text-xs font-medium text-stone-500 mb-1">Appointment Date & Time</label>
            <input
              v-model="editForm.appointment_date"
              type="datetime-local"
              required
              class="w-full rounded-full border border-stone-200 px-4 py-2 text-sm bg-white/60 focus:outline-none focus:border-stone-300"
            />
          </div>

          <div class="flex justify-end gap-2">
            <IconButton icon="close" label="Cancel" variant="ghost" type="button" @click="closeEdit" />
            <IconButton icon="save" label="Save" variant="primary" type="submit" :disabled="saving" />
          </div>
        </form>
      </div>
    </div>
  </div>
</template>