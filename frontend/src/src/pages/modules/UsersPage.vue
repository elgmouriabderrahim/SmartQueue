<script setup lang="ts">
import { computed, onMounted, reactive, ref, watch } from 'vue'
import EmptyState from '@/components/EmptyState.vue'
import LoadingState from '@/components/LoadingState.vue'
import PageHeader from '@/components/PageHeader.vue'
import BaseModal from '@/components/ui/BaseModal.vue'
import IconButton from '@/components/ui/IconButton.vue'
import { smartQueueApi } from '@/services/smartQueueApi'
import { toApiError } from '@/utils/http'

const api = smartQueueApi as typeof smartQueueApi & {
  departments(params?: { per_page?: number; page?: number }): Promise<any>
}

const loading = ref(false)
const saving = ref(false)
const error = ref('')
const users = ref<any[]>([])
const institutions = ref<any[]>([])
const departments = ref<any[]>([])
const detailLoading = ref(false)
const detailError = ref('')
const selectedUser = ref<any | null>(null)
const editUser = ref<any | null>(null)
const showEdit = ref(false)

const editForm = reactive({
  first_name: '',
  last_name: '',
  email: '',
  password: '',
  role: 'citizen',
  identity_number: '',
  institution_id: 0,
  department_id: 0,
})

const editRequiresInstitution = computed(() => ['employee', 'manager'].includes(editForm.role))

const departmentsForEdit = computed(() => {
  if (!editForm.institution_id) return departments.value
  return departments.value.filter((department: any) => Number(department.institution_id) === Number(editForm.institution_id))
})

watch(
  () => editForm.institution_id,
  (institutionId) => {
    if (!editForm.department_id) return
    const department = departments.value.find((item: any) => Number(item.id) === Number(editForm.department_id))
    if (!department || Number(department.institution_id) !== Number(institutionId)) {
      editForm.department_id = 0
    }
  },
)

watch(
  () => editForm.role,
  (role) => {
    if (!['employee', 'manager'].includes(role)) {
      editForm.institution_id = 0
      editForm.department_id = 0
    }
  },
)

async function loadUsers() {
  loading.value = true
  error.value = ''

  try {
    const [usersResponse, institutionsResponse, departmentsResponse] = await Promise.all([
      api.users({ per_page: 100 }),
      api.institutions({ per_page: 100 }),
      api.departments({ per_page: 100 }),
    ])

    users.value = usersResponse.data.data.data || []
    institutions.value = institutionsResponse.data.data.data || []
    departments.value = departmentsResponse.data.data.data || []
  } catch (err) {
    error.value = toApiError(err).message
  } finally {
    loading.value = false
  }
}

function resolveInstitutionId(institutionId: number, departmentId: number): number {
  if (institutionId > 0) return institutionId

  if (departmentId > 0) {
    const department = departments.value.find((item: any) => Number(item.id) === Number(departmentId))
    return Number(department?.institution_id || 0)
  }

  return 0
}

function openEdit(user: any) {
  editUser.value = user
  editForm.first_name = String(user.first_name || '')
  editForm.last_name = String(user.last_name || '')
  editForm.email = String(user.email || '')
  editForm.password = ''
  editForm.role = String(user.role || 'citizen')
  editForm.identity_number = String(user.identity_number || '')
  editForm.institution_id = Number(user.institution_id || 0)
  editForm.department_id = Number(user.department_id || 0)
  showEdit.value = true
}

function closeEdit() {
  showEdit.value = false
  editUser.value = null
  editForm.first_name = ''
  editForm.last_name = ''
  editForm.email = ''
  editForm.password = ''
  editForm.role = 'citizen'
  editForm.identity_number = ''
  editForm.institution_id = 0
  editForm.department_id = 0
}

async function updateUser() {
  if (!editUser.value) return

  saving.value = true
  error.value = ''

  try {
    if (editRequiresInstitution.value && !editForm.institution_id) {
      error.value = 'Institution is required for manager and employee roles.'
      return
    }

    const institutionId = resolveInstitutionId(Number(editForm.institution_id), Number(editForm.department_id))

    const payload: Record<string, unknown> = {
      first_name: editForm.first_name,
      last_name: editForm.last_name,
      email: editForm.email,
      role: editForm.role,
      identity_number: editForm.identity_number || null,
      institution_id: institutionId > 0 ? institutionId : null,
      department_id: editForm.department_id > 0 ? Number(editForm.department_id) : null,
    }

    if (editForm.password) {
      payload.password = editForm.password
    }

    await smartQueueApi.updateUser(Number(editUser.value.id), payload)
    closeEdit()
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

    <LoadingState v-if="loading" />
    <p v-else-if="error" class="text-sm text-rose-500">{{ error }}</p>
    <EmptyState v-else-if="users.length === 0" message="No users found." />

    <div v-else class="grid gap-8 lg:grid-cols-[minmax(0,1fr),320px]">
      <div class="overflow-hidden rounded-2xl border border-stone-100 bg-white/40 backdrop-blur-sm">
        <table class="w-full text-left">
          <thead class="border-b border-stone-100">
            <tr>
              <th class="px-5 py-4 text-xs font-medium uppercase tracking-wider text-stone-400">Name</th>
              <th class="px-5 py-4 text-xs font-medium uppercase tracking-wider text-stone-400">Email</th>
              <th class="px-5 py-4 text-xs font-medium uppercase tracking-wider text-stone-400">Role</th>
              <th class="px-5 py-4 text-xs font-medium uppercase tracking-wider text-stone-400">Institution</th>
              <th class="px-5 py-4 text-xs font-medium uppercase tracking-wider text-stone-400"></th>
            </tr>
          </thead>
          <tbody class="divide-y divide-stone-50">
            <tr v-for="user in users" :key="Number(user.id)" class="transition-colors hover:bg-stone-50/50">
              <td class="px-5 py-3 text-sm text-stone-700">{{ user.first_name }} {{ user.last_name }}</td>
              <td class="px-5 py-3 text-sm text-stone-600">{{ user.email }}</td>
              <td class="px-5 py-3">
                <span class="inline-flex rounded-full bg-stone-100 px-2.5 py-0.5 text-xs font-medium text-stone-600">{{ user.api_role || user.role }}</span>
              </td>
              <td class="px-5 py-3 text-sm text-stone-500">
                {{ user.institution?.name || '-' }}
                <span v-if="user.department?.name" class="block text-xs text-stone-400">{{ user.department.name }}</span>
              </td>
              <td class="px-5 py-3 text-right">
                <div class="flex items-center justify-end gap-2">
                  <IconButton icon="view" size="sm" variant="ghost" @click="loadUserDetails(Number(user.id))" />
                  <IconButton icon="edit" size="sm" variant="ghost" @click="openEdit(user)" />
                  <IconButton icon="delete" size="sm" variant="ghost" @click="deleteUser(Number(user.id))" />
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <div class="rounded-2xl border border-stone-100 bg-white/40 p-5 backdrop-blur-sm">
        <div class="mb-4 flex items-center justify-between">
          <p class="text-xs font-semibold uppercase tracking-wider text-stone-400">User Detail</p>
          <IconButton v-if="selectedUser" icon="close" size="sm" variant="ghost" @click="clearDetails" />
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
            <p class="text-[10px] uppercase tracking-wider text-stone-400">Institution</p>
            <p class="text-sm text-stone-600">{{ selectedUser.institution?.name || '-' }}</p>
          </div>
          <div class="border-b border-stone-100 pb-2">
            <p class="text-[10px] uppercase tracking-wider text-stone-400">Department</p>
            <p class="text-sm text-stone-600">{{ selectedUser.department?.name || '-' }}</p>
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

    <BaseModal :open="showEdit" title="Edit User" @close="closeEdit">
      <form class="space-y-4" @submit.prevent="updateUser">
        <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
          <input v-model="editForm.first_name" required placeholder="First name" class="input-shell" />
          <input v-model="editForm.last_name" required placeholder="Last name" class="input-shell" />
          <input v-model="editForm.email" type="email" required placeholder="Email" class="input-shell md:col-span-2" />
          <input v-model="editForm.password" type="password" minlength="8" placeholder="New password (optional)" class="input-shell md:col-span-2" />
          <input v-model="editForm.identity_number" placeholder="Identity number (optional)" class="input-shell" />
          <select v-model="editForm.role" class="input-shell">
            <option value="citizen">Citizen</option>
            <option value="employee">Employee</option>
            <option value="manager">Manager</option>
            <option value="admin">Admin</option>
          </select>
          <select v-model.number="editForm.institution_id" :disabled="!editRequiresInstitution" class="input-shell disabled:opacity-50">
            <option :value="0">{{ editRequiresInstitution ? 'Select institution' : 'No institution' }}</option>
            <option v-for="institution in institutions" :key="Number(institution.id)" :value="Number(institution.id)">
              {{ institution.name }}
            </option>
          </select>
          <select v-model.number="editForm.department_id" :disabled="!editRequiresInstitution || !editForm.institution_id" class="input-shell md:col-span-2 disabled:opacity-50">
            <option :value="0">{{ editRequiresInstitution ? 'Select department (optional)' : 'No department' }}</option>
            <option v-for="department in departmentsForEdit" :key="Number(department.id)" :value="Number(department.id)">
              {{ department.name }}
            </option>
          </select>
        </div>

        <div class="flex justify-end gap-3 pt-2">
          <button type="button" class="rounded-xl px-4 py-2 text-sm font-medium text-stone-500 hover:bg-stone-100" @click="closeEdit">Cancel</button>
          <button type="submit" :disabled="saving" class="rounded-xl bg-black px-4 py-2 text-sm font-semibold text-white disabled:opacity-50">
            {{ saving ? 'Saving...' : 'Save changes' }}
          </button>
        </div>
      </form>
    </BaseModal>
  </div>
</template>
