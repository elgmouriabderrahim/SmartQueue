<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useAuthStore } from '@/stores/auth'
import { smartQueueApi } from '@/services/smartQueueApi'
import { toApiError } from '@/utils/http'

const authStore = useAuthStore()

const loading = ref(false)
const error = ref('')
const metrics = ref<Record<string, number | string>>({})
const invitations = ref<any[]>([])

const canSeeInvitations = computed(() => authStore.isAuthenticated && authStore.userRole !== 'admin')
const pendingInvitations = computed(() => invitations.value.filter((item) => item.status === 'pending'))

async function loadDashboard() {
  loading.value = true
  error.value = ''

  try {
    const [dashboardResponse, invitationsResponse] = await Promise.all([
      smartQueueApi.dashboard(),
      canSeeInvitations.value ? smartQueueApi.myInstitutionInvitations() : Promise.resolve(null),
    ])

    metrics.value = dashboardResponse.data.data as Record<string, number | string>
    invitations.value = invitationsResponse?.data.data || []
  } catch (err) {
    error.value = toApiError(err).message
  } finally {
    loading.value = false
  }
}

async function acceptInvitation(id: number): Promise<void> {
  error.value = ''

  try {
    await smartQueueApi.acceptInstitutionInvitation(id)
    await loadDashboard()
  } catch (err) {
    error.value = toApiError(err).message
  }
}

async function rejectInvitation(id: number): Promise<void> {
  error.value = ''

  try {
    await smartQueueApi.rejectInstitutionInvitation(id)
    await loadDashboard()
  } catch (err) {
    error.value = toApiError(err).message
  }
}

onMounted(loadDashboard)
</script>

<template>
  <div class="space-y-8">
    <!-- Header -->
    <div>
      <p class="text-[11px] font-semibold uppercase tracking-[0.3em] text-stone-400">Insights</p>
      <h1 class="mt-2 text-3xl font-light tracking-tight text-stone-800">Dashboard</h1>
      <p class="mt-1 text-stone-500">System activity and service health overview</p>
      <div class="mt-3 h-px w-12 bg-amber-300" />
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="flex justify-center py-12">
      <div class="relative h-8 w-8">
        <div class="absolute inset-0 rounded-full border-2 border-stone-200 border-t-stone-600 animate-spin" />
      </div>
    </div>

    <!-- Error State -->
    <p v-else-if="error" class="text-sm text-rose-500">{{ error }}</p>

    <!-- Metrics Grid -->
    <div v-else class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
      <div
        v-for="(value, key) in metrics"
        :key="key"
        class="group border-b border-stone-100 pb-4 transition-all duration-200 hover:border-stone-200"
      >
        <p class="text-xs uppercase tracking-wider text-stone-400">
          {{ String(key).split('_').join(' ') }}
        </p>
        <p class="mt-2 text-3xl font-light text-stone-700">
          {{ value }}
        </p>
      </div>
    </div>

    <div v-if="canSeeInvitations && pendingInvitations.length > 0" class="space-y-4">
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
              <p class="mt-2 text-sm text-stone-600">You were invited to join this institution as an employee.</p>
            </div>
            <div class="flex gap-2">
              <button class="rounded-full bg-emerald-600 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-700" @click="acceptInvitation(Number(item.id))">Accept</button>
              <button class="rounded-full bg-stone-200 px-4 py-2 text-sm font-medium text-stone-700 hover:bg-stone-300" @click="rejectInvitation(Number(item.id))">Reject</button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>