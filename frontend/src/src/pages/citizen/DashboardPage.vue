<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import BaseCard from '@/components/ui/BaseCard.vue'
import { smartQueueApi } from '@/services/smartQueueApi'
import { toApiError } from '@/utils/http'

const loading = ref(false)
const error = ref('')
const appointments = ref<any[]>([])
const notifications = ref<any[]>([])
const invitations = ref<any[]>([])
const invitationActionId = ref<number | null>(null)

const upcomingAppointments = computed(() =>
  appointments.value.filter((item) => ['pending', 'confirmed'].includes(String(item.status || ''))).length,
)

const completedAppointments = computed(() =>
  appointments.value.filter((item) => String(item.status) === 'completed').length,
)

const unreadNotifications = computed(() => notifications.value.filter((item) => !item.is_read).length)
const pendingInvitations = computed(() => invitations.value.filter((item) => item.status === 'pending'))

async function loadDashboard(): Promise<void> {
  loading.value = true
  error.value = ''

  try {
    const [appointmentsRes, notificationsRes, invitationsRes] = await Promise.all([
      smartQueueApi.appointments({ per_page: 100 }),
      smartQueueApi.notifications(),
      smartQueueApi.myInstitutionInvitations(),
    ])

    appointments.value = appointmentsRes.data.data.data || []
    notifications.value = notificationsRes.data.data.data || []
    invitations.value = invitationsRes.data.data || []
  } catch (err) {
    error.value = toApiError(err).message
  } finally {
    loading.value = false
  }
}

async function acceptInvitation(id: number): Promise<void> {
  invitationActionId.value = id
  error.value = ''

  try {
    await smartQueueApi.acceptInstitutionInvitation(id)
    await loadDashboard()
    window.location.reload()
  } catch (err) {
    error.value = toApiError(err).message
  } finally {
    invitationActionId.value = null
  }
}

async function rejectInvitation(id: number): Promise<void> {
  invitationActionId.value = id
  error.value = ''

  try {
    await smartQueueApi.rejectInstitutionInvitation(id)
    await loadDashboard()
  } catch (err) {
    error.value = toApiError(err).message
  } finally {
    invitationActionId.value = null
  }
}

onMounted(loadDashboard)
</script>

<template>
  <div class="space-y-8">
    <!-- Header -->
    <div>
      <p class="text-[11px] font-semibold uppercase tracking-[0.3em] text-stone-400">Overview</p>
      <h1 class="mt-2 text-3xl font-light tracking-tight text-stone-800">Your Appointment Overview</h1>
      <p class="mt-1 text-stone-500">Track your appointments and notifications</p>
      <div class="mt-3 h-px w-12 bg-amber-300" />
    </div>

    <!-- Error & Loading -->
    <p v-if="error" class="text-sm text-rose-500">{{ error }}</p>
    <p v-else-if="loading" class="text-sm text-stone-400">Loading your data...</p>

    <!-- Stats Grid -->
    <div v-else class="grid gap-6 sm:grid-cols-2 xl:grid-cols-3">
      <div class="group border-b border-stone-100 pb-4 transition-all duration-200 hover:border-stone-300">
        <p class="text-xs uppercase tracking-wider text-stone-400">Upcoming Appointments</p>
        <p class="mt-2 text-3xl font-light text-stone-700">{{ upcomingAppointments }}</p>
        <div class="mt-3 h-px w-8 bg-stone-200 group-hover:w-full transition-all duration-300" />
      </div>

      <div class="group border-b border-stone-100 pb-4 transition-all duration-200 hover:border-stone-300">
        <p class="text-xs uppercase tracking-wider text-stone-400">Completed</p>
        <p class="mt-2 text-3xl font-light text-stone-700">{{ completedAppointments }}</p>
        <div class="mt-3 h-px w-8 bg-stone-200 group-hover:w-full transition-all duration-300" />
      </div>

      <div class="group border-b border-stone-100 pb-4 transition-all duration-200 hover:border-stone-300">
        <p class="text-xs uppercase tracking-wider text-stone-400">Unread Notifications</p>
        <p class="mt-2 text-3xl font-light text-stone-700">{{ unreadNotifications }}</p>
        <div class="mt-3 h-px w-8 bg-stone-200 group-hover:w-full transition-all duration-300" />
      </div>
    </div>

    <div v-if="pendingInvitations.length > 0" class="space-y-4">
      <div>
        <p class="text-[11px] font-semibold uppercase tracking-[0.3em] text-stone-400">Invitations</p>
        <h2 class="mt-2 text-2xl font-light tracking-tight text-stone-800">Pending institution invitations</h2>
      </div>

      <div class="grid gap-4">
        <div v-for="item in pendingInvitations" :key="Number(item.id)" class="rounded-2xl border border-stone-100 bg-white/40 backdrop-blur-sm p-5">
          <div class="flex flex-wrap items-start justify-between gap-3">
            <div>
              <p class="text-sm font-medium text-stone-800">{{ item.institution?.name || 'Institution' }}</p>
              <p class="mt-1 text-sm text-stone-500">{{ item.institution?.city || '' }}</p>
              <p class="mt-2 text-sm text-stone-600">Accept to join as an employee or reject to decline.</p>
            </div>
            <div class="flex gap-2">
              <button
                :disabled="invitationActionId === Number(item.id)"
                class="rounded-full bg-emerald-600 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-700 disabled:opacity-50"
                @click="acceptInvitation(Number(item.id))"
              >
                {{ invitationActionId === Number(item.id) ? 'Please wait...' : 'Accept' }}
              </button>
              <button
                :disabled="invitationActionId === Number(item.id)"
                class="rounded-full bg-stone-200 px-4 py-2 text-sm font-medium text-stone-700 hover:bg-stone-300 disabled:opacity-50"
                @click="rejectInvitation(Number(item.id))"
              >
                Reject
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>