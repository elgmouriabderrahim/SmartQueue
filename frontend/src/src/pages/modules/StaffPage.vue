<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import EmptyState from '@/components/EmptyState.vue'
import LoadingState from '@/components/LoadingState.vue'
import PageHeader from '@/components/PageHeader.vue'
import { smartQueueApi } from '@/services/smartQueueApi'
import { useAuthStore } from '@/stores/auth'
import { toApiError } from '@/utils/http'

const authStore = useAuthStore()

const loading = ref(false)
const inviting = ref(false)
const leaving = ref(false)
const error = ref('')
const staff = ref<any[]>([])
const institutionId = ref<number | null>(authStore.user?.institution_id ?? null)
const inviteEmail = ref('')
const newManagerUserId = ref<number | null>(null)

const canManage = computed(() => authStore.role === 'admin' || (authStore.user?.role === 'manager' && !!institutionId.value))

async function loadStaff() {
  if (!institutionId.value) {
    return
  }

  loading.value = true
  error.value = ''

  try {
    const response = await smartQueueApi.institutionStaff(institutionId.value)
    staff.value = response.data.data
  } catch (err) {
    error.value = toApiError(err).message
  } finally {
    loading.value = false
  }
}

async function invite() {
  if (!institutionId.value || !inviteEmail.value.trim()) {
    return
  }

  inviting.value = true
  error.value = ''

  try {
    await smartQueueApi.inviteInstitutionEmployee(institutionId.value, { email: inviteEmail.value.trim() })
    inviteEmail.value = ''
    await loadStaff()
  } catch (err) {
    error.value = toApiError(err).message
  } finally {
    inviting.value = false
  }
}

async function removeStaff(userId: number) {
  if (!institutionId.value) {
    return
  }

  try {
    await smartQueueApi.removeInstitutionEmployee(institutionId.value, userId)
    await loadStaff()
  } catch (err) {
    error.value = toApiError(err).message
  }
}

async function leaveInstitution() {
  if (!institutionId.value) {
    return
  }

  leaving.value = true
  error.value = ''

  try {
    const payload = authStore.user?.role === 'manager' && newManagerUserId.value
      ? { new_manager_user_id: Number(newManagerUserId.value) }
      : undefined

    await smartQueueApi.leaveInstitution(institutionId.value, payload)
    await authStore.logout()
    window.location.href = '/auth/login'
  } catch (err) {
    error.value = toApiError(err).message
  } finally {
    leaving.value = false
  }
}

onMounted(loadStaff)
</script>

<template>
  <div class="space-y-8">
    <PageHeader title="Institution Team" subtitle="Invite citizens as employees and manage your institution staff." />

    <!-- No Institution Warning -->
    <div v-if="!institutionId" class="rounded-2xl bg-amber-50/80 backdrop-blur-sm border border-amber-200 p-4 text-sm text-amber-700">
      You need an approved institution to manage staff. Submit a request from Institution Requests.
    </div>

    <!-- Invite Employee Form -->
    <div v-if="canManage && institutionId" class="rounded-2xl bg-white/40 backdrop-blur-sm border border-stone-100 p-5">
      <h2 class="text-xs font-semibold uppercase tracking-wider text-stone-500">Invite Employee</h2>
      <p class="mt-1 text-sm text-stone-400">Send an invitation to join your institution</p>
      
      <form class="mt-4 flex flex-col gap-3 sm:flex-row" @submit.prevent="invite">
        <input 
          v-model="inviteEmail" 
          type="email" 
          required 
          placeholder="citizen@email.com" 
          class="flex-1 rounded-full border border-stone-200 px-4 py-2 text-sm bg-white/60 focus:outline-none focus:border-stone-300 placeholder:text-stone-400"
        />
        <button 
          :disabled="inviting" 
          class="rounded-full bg-stone-800 px-5 py-2 text-sm font-medium text-white transition-all duration-200 hover:bg-stone-700 hover:-translate-y-0.5 disabled:opacity-50"
        >
          {{ inviting ? 'Inviting...' : 'Invite Citizen' }}
        </button>
      </form>
    </div>

    <!-- Leave Institution Section -->
    <div v-if="institutionId" class="rounded-2xl bg-white/40 backdrop-blur-sm border border-stone-100 p-5">
      <h2 class="text-xs font-semibold uppercase tracking-wider text-stone-500">Leave Institution</h2>
      <p class="mt-1 text-sm text-stone-400">Transfer ownership or leave your institution</p>
      
      <div class="mt-4 space-y-3">
        <select
          v-if="authStore.user?.role === 'manager'"
          v-model.number="newManagerUserId"
          class="w-full rounded-full border border-stone-200 px-4 py-2 text-sm bg-white/60 focus:outline-none focus:border-stone-300"
        >
          <option :value="null" disabled>Select new manager from employees</option>
          <option v-for="member in staff.filter((item) => item.role === 'employee')" :key="Number(member.id)" :value="Number(member.id)">
            {{ member.first_name }} {{ member.last_name }}
          </option>
        </select>
        <button 
          :disabled="leaving" 
          class="rounded-full bg-rose-100 px-5 py-2 text-sm font-medium text-rose-600 transition-all duration-200 hover:bg-rose-200 hover:-translate-y-0.5 disabled:opacity-50" 
          @click="leaveInstitution"
        >
          {{ leaving ? 'Processing...' : 'Leave Institution' }}
        </button>
      </div>
    </div>

    <!-- Loading & Error -->
    <LoadingState v-if="loading" />
    <p v-else-if="error" class="text-sm text-rose-500">{{ error }}</p>
    <EmptyState v-else-if="staff.length === 0" message="No staff members found for this institution." />

    <!-- Staff Table -->
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
            <tr v-for="member in staff" :key="Number(member.id)" class="hover:bg-stone-50/30 transition-colors">
              <td class="px-5 py-3 text-sm text-stone-700">{{ member.first_name }} {{ member.last_name }}</td>
              <td class="px-5 py-3 text-sm text-stone-600">{{ member.email }}</td>
              <td class="px-5 py-3">
                <span 
                  class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium"
                  :class="member.role === 'manager' ? 'bg-amber-100 text-amber-700' : 'bg-stone-100 text-stone-600'"
                >
                  {{ member.role }}
                </span>
              </td>
              <td class="px-5 py-3 text-right">
                <button
                  v-if="canManage && member.role === 'employee'"
                  class="text-xs text-rose-400 hover:text-rose-600 transition-colors"
                  @click="removeStaff(Number(member.id))"
                >
                  Remove
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</template>