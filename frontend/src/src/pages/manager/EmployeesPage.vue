<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useAuthStore } from '@/stores/auth'
import { smartQueueApi } from '@/services/smartQueueApi'
import { toApiError } from '@/utils/http'

const authStore = useAuthStore()
const institutionId = computed(() => Number(authStore.user?.institution_id || 0))

const loading = ref(false)
const inviting = ref(false)
const leaving = ref(false)
const error = ref('')
const inviteEmail = ref('')
const newManagerUserId = ref<number | null>(null)
const staff = ref<any[]>([])
const invitations = ref<any[]>([])

const pendingInvitations = computed(() => invitations.value.filter((item) => item.status === 'pending'))

const removableEmployees = computed(() =>
  staff.value.filter((member) => member.role === 'employee' && Number(member.id) !== Number(authStore.user?.id)),
)

async function loadEmployees(): Promise<void> {
  if (!institutionId.value) {
    staff.value = []
    return
  }

  loading.value = true
  error.value = ''

  try {
    const [staffResponse, invitationsResponse] = await Promise.all([
      smartQueueApi.institutionStaff(institutionId.value),
      smartQueueApi.institutionStaffInvitations(institutionId.value),
    ])

    staff.value = staffResponse.data.data || []
    invitations.value = invitationsResponse.data.data || []
  } catch (err) {
    error.value = toApiError(err).message
  } finally {
    loading.value = false
  }
}

async function inviteEmployee(): Promise<void> {
  if (!institutionId.value || !inviteEmail.value.trim()) {
    return
  }

  inviting.value = true
  error.value = ''

  try {
    await smartQueueApi.inviteInstitutionEmployee(institutionId.value, { email: inviteEmail.value.trim() })
    inviteEmail.value = ''
    await loadEmployees()
  } catch (err) {
    error.value = toApiError(err).message
  } finally {
    inviting.value = false
  }
}

async function removeEmployee(userId: number): Promise<void> {
  if (!institutionId.value || Number(authStore.user?.id) === userId) {
    return
  }

  try {
    await smartQueueApi.removeInstitutionEmployee(institutionId.value, userId)
    await loadEmployees()
  } catch (err) {
    error.value = toApiError(err).message
  }
}

async function leaveAsManager(): Promise<void> {
  if (!institutionId.value || !newManagerUserId.value) {
    error.value = 'Select an employee to become manager before leaving.'
    return
  }

  leaving.value = true
  error.value = ''

  try {
    await smartQueueApi.leaveInstitution(institutionId.value, { new_manager_user_id: Number(newManagerUserId.value) })
    await authStore.logout()
    window.location.href = '/auth/login'
  } catch (err) {
    error.value = toApiError(err).message
  } finally {
    leaving.value = false
  }
}

onMounted(loadEmployees)
</script>

<template>
  <div class="space-y-8">
    <!-- Header -->
    <div>
      <p class="text-[11px] font-semibold uppercase tracking-[0.3em] text-stone-400">Team</p>
      <h1 class="mt-2 text-3xl font-light tracking-tight text-stone-800">Invite and Manage Team</h1>
      <p class="mt-1 text-stone-500">Add employees and manage institution staff</p>
      <div class="mt-3 h-px w-12 bg-amber-300" />
    </div>

    <!-- Error -->
    <p v-if="error" class="text-sm text-rose-500">{{ error }}</p>

    <!-- Invite Employee Form -->
    <div class="rounded-2xl bg-white/40 backdrop-blur-sm border border-stone-100 p-5">
      <h2 class="text-xs font-semibold uppercase tracking-wider text-stone-500">Invite Employee</h2>
      <p class="mt-1 text-sm text-stone-400">Send a pending invitation. The user must accept it from their dashboard.</p>
      
      <form class="mt-4 flex flex-col gap-3 sm:flex-row" @submit.prevent="inviteEmployee">
        <input 
          v-model="inviteEmail" 
          type="email" 
          required 
          class="flex-1 rounded-full border border-stone-200 px-4 py-2 text-sm bg-white/60 focus:outline-none focus:border-stone-300 placeholder:text-stone-400"
          placeholder="employee@email.com"
        />
        <button 
          type="submit" 
          :disabled="inviting" 
          class="rounded-full bg-stone-800 px-5 py-2 text-sm font-medium text-white transition-all duration-200 hover:bg-stone-700 hover:-translate-y-0.5 disabled:opacity-50 disabled:hover:translate-y-0"
        >
          {{ inviting ? 'Inviting...' : 'Send Invitation' }}
        </button>
      </form>
    </div>

    <!-- Loading & Empty States -->
    <p v-if="loading" class="text-sm text-stone-400">Loading employees...</p>
    <div v-else-if="staff.length === 0" class="text-center py-12">
      <p class="text-stone-400">No employees available in this institution yet.</p>
    </div>

    <!-- Employees Table -->
    <div v-else class="border border-stone-100 rounded-2xl overflow-hidden bg-white/40 backdrop-blur-sm">
      <div class="overflow-x-auto">
        <table class="w-full text-left">
          <thead class="border-b border-stone-100">
            <tr>
              <th class="px-5 py-4 text-xs font-medium uppercase tracking-wider text-stone-400">Name</th>
              <th class="px-5 py-4 text-xs font-medium uppercase tracking-wider text-stone-400">Email</th>
              <th class="px-5 py-4 text-xs font-medium uppercase tracking-wider text-stone-400">Role</th>
              <th class="px-5 py-4 text-xs font-medium uppercase tracking-wider text-stone-400"></th>
            </tr>
          </thead>
          <tbody class="divide-y divide-stone-50">
            <tr v-for="member in staff" :key="Number(member.id)" class="hover:bg-stone-50/50 transition-colors">
              <td class="px-5 py-3 text-sm font-medium text-stone-800">{{ member.first_name }} {{ member.last_name }}</td>
              <td class="px-5 py-3 text-sm text-stone-600">{{ member.email }}</td>
              <td class="px-5 py-3">
                <span 
                  class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium"
                  :class="member.role === 'manager' ? 'bg-amber-100 text-amber-700' : 'bg-stone-100 text-stone-600'"
                >
                  {{ member.role }}
                </span>
              </td>
              <td class="px-5 py-3">
                <button
                  v-if="member.role !== 'manager' && Number(member.id) !== Number(authStore.user?.id)"
                  class="text-xs text-rose-400 hover:text-rose-600 transition-colors"
                  @click="removeEmployee(Number(member.id))"
                >
                  Remove
                </button>
                <span v-else class="text-xs text-stone-400">Protected</span>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Manager Transition -->
    <div class="rounded-2xl bg-white/40 backdrop-blur-sm border border-stone-100 p-5">
      <h2 class="text-xs font-semibold uppercase tracking-wider text-stone-500">Manager Transition</h2>
      <p class="mt-1 text-sm text-stone-400">Transfer ownership before leaving the institution</p>
      
      <div class="mt-4 flex flex-col gap-3 sm:flex-row">
        <select 
          v-model.number="newManagerUserId" 
          class="flex-1 rounded-full border border-stone-200 px-4 py-2 text-sm bg-white/60 focus:outline-none focus:border-stone-300"
          required
        >
          <option :value="null" disabled>Select replacement manager</option>
          <option v-for="employee in removableEmployees" :key="Number(employee.id)" :value="Number(employee.id)">
            {{ employee.first_name }} {{ employee.last_name }}
          </option>
        </select>
        <button 
          :disabled="leaving" 
          class="rounded-full bg-rose-100 px-5 py-2 text-sm font-medium text-rose-600 transition-all duration-200 hover:bg-rose-200 hover:-translate-y-0.5 disabled:opacity-50 disabled:hover:translate-y-0"
          @click="leaveAsManager"
        >
          {{ leaving ? 'Processing...' : 'Leave Institution' }}
        </button>
      </div>
      <p class="mt-3 text-xs text-stone-400">Manager must assign a new manager before leaving.</p>
    </div>

    <div class="rounded-2xl bg-white/40 backdrop-blur-sm border border-stone-100 p-5">
      <h2 class="text-xs font-semibold uppercase tracking-wider text-stone-500">Pending Invitations</h2>
      <p class="mt-1 text-sm text-stone-400">Invited users still need to accept before becoming employees.</p>

      <div v-if="pendingInvitations.length === 0" class="mt-4 text-sm text-stone-400">No pending invitations.</div>
      <ul v-else class="mt-4 space-y-3">
        <li v-for="item in pendingInvitations" :key="Number(item.id)" class="rounded-xl border border-stone-100 bg-white/60 p-4">
          <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
              <p class="font-medium text-stone-800">{{ item.email }}</p>
              <p class="text-xs text-stone-400">Status: {{ item.status }}</p>
            </div>
          </div>
        </li>
      </ul>
    </div>
  </div>
</template>