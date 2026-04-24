<script setup lang="ts">
import { onMounted, reactive, ref } from 'vue'
import EmptyState from '@/components/EmptyState.vue'
import LoadingState from '@/components/LoadingState.vue'
import PageHeader from '@/components/PageHeader.vue'
import IconButton from '@/components/ui/IconButton.vue'
import { smartQueueApi } from '@/services/smartQueueApi'
import { toApiError } from '@/utils/http'

const loading = ref(false)
const saving = ref(false)
const error = ref('')
const users = ref<any[]>([])
const detailLoading = ref(false)
const detailError = ref('')
const selectedUser = ref<any | null>(null)

const form = reactive({
  first_name: '',
  last_name: '',
  email: '',
  password: '',
  role: 'citizen',
  identity_number: '',
})

async function loadUsers() {
  loading.value = true
  error.value = ''
  try {
    const response = await smartQueueApi.users()
    users.value = response.data.data.data
  } catch (err) {
    error.value = toApiError(err).message
  } finally {
    loading.value = false
  }
}

async function createUser() {
  saving.value = true
  error.value = ''

  try {
    await smartQueueApi.createUser({ ...form, identity_number: form.identity_number || null })
    Object.assign(form, {
      first_name: '',
      last_name: '',
      email: '',
      password: '',
      role: 'citizen',
      identity_number: '',
    })
    await loadUsers()
  } catch (err) {
    error.value = toApiError(err).message
  } finally {
    saving.value = false
  }
}

async function deleteUser(id: number) {
  error.value = ''
  try {
    await smartQueueApi.deleteUser(id)
    await loadUsers()
  } catch (err) {
    error.value = toApiError(err).message
  }
}

async function loadUserDetails(id: number) {
  detailLoading.value = true
  detailError.value = ''

  try {
    const response = await smartQueueApi.user(id)
    selectedUser.value = response.data.data
  } catch (err) {
    detailError.value = toApiError(err).message
  } finally {
    detailLoading.value = false
  }
}

function clearDetails() {
  selectedUser.value = null
  detailError.value = ''
}

onMounted(loadUsers)
</script>

<template>
  <div class="space-y-8">
    <PageHeader title="Users" subtitle="Manage platform users (admin access)." />

    <!-- Create User Form -->
    <div class="rounded-2xl bg-white/40 backdrop-blur-sm border border-stone-100 p-5">
      <h2 class="text-xs font-semibold uppercase tracking-wider text-stone-500">Create User</h2>
      <p class="mt-1 text-sm text-stone-400">Add a new user to the platform</p>
      
      <form class="mt-4 grid grid-cols-1 gap-3 md:grid-cols-3" @submit.prevent="createUser">
        <input 
          v-model="form.first_name" 
          placeholder="First name" 
          required 
          class="rounded-full border border-stone-200 px-4 py-2 text-sm bg-white/60 focus:outline-none focus:border-stone-300"
        />
        <input 
          v-model="form.last_name" 
          placeholder="Last name" 
          required 
          class="rounded-full border border-stone-200 px-4 py-2 text-sm bg-white/60 focus:outline-none focus:border-stone-300"
        />
        <input 
          v-model="form.email" 
          type="email" 
          placeholder="Email" 
          required 
          class="rounded-full border border-stone-200 px-4 py-2 text-sm bg-white/60 focus:outline-none focus:border-stone-300"
        />
        <input 
          v-model="form.password" 
          type="password" 
          placeholder="Password (min 8 characters)" 
          minlength="8" 
          required 
          class="rounded-full border border-stone-200 px-4 py-2 text-sm bg-white/60 focus:outline-none focus:border-stone-300"
        />
        <input 
          v-model="form.identity_number" 
          placeholder="Identity number (optional)" 
          class="rounded-full border border-stone-200 px-4 py-2 text-sm bg-white/60 focus:outline-none focus:border-stone-300"
        />
        <select 
          v-model="form.role" 
          class="rounded-full border border-stone-200 px-4 py-2 text-sm bg-white/60 focus:outline-none focus:border-stone-300"
        >
          <option value="citizen">Citizen</option>
          <option value="employee">Employee</option>
          <option value="manager">Manager</option>
          <option value="admin">Admin</option>
        </select>
        <IconButton
          icon="add"
          label="Create User"
          variant="primary"
          type="submit"
          :disabled="saving"
          class="md:col-span-3"
        />
      </form>
    </div>

    <!-- Loading & Error -->
    <LoadingState v-if="loading" />
    <p v-else-if="error" class="text-sm text-rose-500">{{ error }}</p>
    <EmptyState v-else-if="users.length === 0" message="No users found." />

    <!-- Users Table + Detail Panel -->
    <div v-else class="grid gap-8 lg:grid-cols-[minmax(0,1fr),320px]">
      <!-- Users Table -->
      <div class="border border-stone-100 rounded-2xl overflow-hidden bg-white/40 backdrop-blur-sm">
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
            <tr v-for="user in users" :key="Number(user.id)" class="hover:bg-stone-50/50 transition-colors">
              <td class="px-5 py-3 text-sm text-stone-700">{{ user.first_name }} {{ user.last_name }}</td>
              <td class="px-5 py-3 text-sm text-stone-600">{{ user.email }}</td>
              <td class="px-5 py-3">
                <span class="inline-flex rounded-full bg-stone-100 px-2.5 py-0.5 text-xs font-medium text-stone-600">
                  {{ user.api_role || user.role }}
                </span>
              </td>
              <td class="px-5 py-3 text-right">
                <div class="flex items-center justify-end gap-2">
                  <IconButton 
                    icon="view"
                    size="sm"
                    variant="ghost"
                    @click="loadUserDetails(Number(user.id))"
                  />
                  <IconButton 
                    icon="delete"
                    size="sm"
                    variant="ghost"
                    @click="deleteUser(Number(user.id))"
                  />
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Detail Panel -->
      <div class="border border-stone-100 rounded-2xl bg-white/40 backdrop-blur-sm p-5">
        <div class="flex items-center justify-between mb-4">
          <p class="text-xs font-semibold uppercase tracking-wider text-stone-400">User Detail</p>
          <IconButton 
            v-if="selectedUser" 
            icon="close"
            size="sm"
            variant="ghost"
            @click="clearDetails"
          />
        </div>

        <p v-if="detailLoading" class="text-sm text-stone-400">Loading user details...</p>
        <p v-else-if="detailError" class="text-sm text-rose-500">{{ detailError }}</p>
        <p v-else-if="!selectedUser" class="text-sm text-stone-400">Select a user and click View.</p>

        <div v-else class="space-y-3">
          <div class="border-b border-stone-100 pb-2">
            <p class="text-[10px] uppercase tracking-wider text-stone-400">Name</p>
            <p class="text-sm text-stone-700">{{ selectedUser.first_name }} {{ selectedUser.last_name }}</p>
          </div>
          <div class="border-b border-stone-100 pb-2">
            <p class="text-[10px] uppercase tracking-wider text-stone-400">Email</p>
            <p class="text-sm text-stone-600">{{ selectedUser.email }}</p>
          </div>
          <div class="border-b border-stone-100 pb-2">
            <p class="text-[10px] uppercase tracking-wider text-stone-400">Role</p>
            <p class="text-sm text-stone-600">{{ selectedUser.api_role || selectedUser.role }}</p>
          </div>
          <div class="border-b border-stone-100 pb-2">
            <p class="text-[10px] uppercase tracking-wider text-stone-400">Identity Number</p>
            <p class="text-sm text-stone-500">{{ selectedUser.identity_number || '-' }}</p>
          </div>
          <div>
            <p class="text-[10px] uppercase tracking-wider text-stone-400">Created</p>
            <p class="text-sm text-stone-500">{{ selectedUser.created_at?.split('T')[0] || '-' }}</p>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>