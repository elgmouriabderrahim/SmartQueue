<script setup lang="ts">
import { computed, onMounted, reactive, ref } from 'vue'
import { useAuthStore } from '@/stores/auth'
import { smartQueueApi } from '@/services/smartQueueApi'
import { toApiError } from '@/utils/http'
import IconButton from '@/components/ui/IconButton.vue'
import { getIcon } from '@/utils/icons'

const authStore = useAuthStore()
const institutionId = computed(() => Number(authStore.user?.institution_id || 0))

const loading = ref(false)
const saving = ref(false)
const error = ref('')
const departments = ref<any[]>([])
const editId = ref<number | null>(null)
const showForm = ref(false)
const detailLoading = ref(false)
const detailError = ref('')
const departmentDetail = ref<any | null>(null)

const form = reactive({
  institution_id: 0,
  name: '',
  slug: '',
  description: '',
  location: '',
  status: 'active',
})

async function loadDepartments(): Promise<void> {
  loading.value = true
  error.value = ''

  try {
    const response = await smartQueueApi.departments({ per_page: 100 })
    departments.value = response.data.data.data || []
  } catch (err) {
    error.value = toApiError(err).message
  } finally {
    loading.value = false
  }
}

function openCreate(): void {
  resetForm()
  showForm.value = true
}

function openEdit(department: any): void {
  editId.value = Number(department.id)
  form.institution_id = Number(department.institution_id || institutionId.value)
  form.name = String(department.name || '')
  form.slug = String(department.slug || '')
  form.description = String(department.description || '')
  form.location = String(department.location || '')
  form.status = String(department.status || 'active')
  showForm.value = true
}

function resetForm(): void {
  form.institution_id = institutionId.value
  form.name = ''
  form.slug = ''
  form.description = ''
  form.location = ''
  form.status = 'active'
  editId.value = null
}

async function saveDepartment(): Promise<void> {
  saving.value = true
  error.value = ''

  try {
    const payload = {
      institution_id: Number(form.institution_id),
      name: form.name,
      slug: form.slug,
      description: form.description,
      location: form.location,
      status: form.status,
    }

    if (editId.value) {
      await smartQueueApi.updateDepartment(editId.value, payload)
    } else {
      await smartQueueApi.createDepartment(payload)
    }

    showForm.value = false
    resetForm()
    await loadDepartments()
  } catch (err) {
    error.value = toApiError(err).message
  } finally {
    saving.value = false
  }
}

async function deleteDepartment(id: number): Promise<void> {
  try {
    await smartQueueApi.deleteDepartment(id)
    await loadDepartments()
  } catch (err) {
    error.value = toApiError(err).message
  }
}

async function loadDepartmentDetail(id: number): Promise<void> {
  detailLoading.value = true
  detailError.value = ''

  try {
    const response = await smartQueueApi.department(id)
    departmentDetail.value = response.data.data
  } catch (err) {
    detailError.value = toApiError(err).message
  } finally {
    detailLoading.value = false
  }
}

onMounted(() => {
  form.institution_id = institutionId.value
  loadDepartments()
})
</script>

<template>
  <div class="space-y-8">
    <!-- Header with Create Button -->
    <div class="flex items-center justify-between">
      <div>
        <p class="text-[11px] font-semibold uppercase tracking-[0.3em] text-stone-400">Structure</p>
        <h1 class="mt-2 text-3xl font-light tracking-tight text-stone-800">Manage Institution Departments</h1>
        <p class="mt-1 text-stone-500">Create and organize your institution departments</p>
        <div class="mt-3 h-px w-12 bg-amber-300" />
      </div>
      <IconButton
        icon="add"
        label="New Department"
        variant="primary"
        @click="openCreate"
      />
    </div>

    <!-- Error -->
    <p v-if="error" class="text-sm text-rose-500">{{ error }}</p>

    <!-- Loading State -->
    <p v-if="loading" class="text-sm text-stone-400">Loading departments...</p>

    <!-- Empty State -->
    <div v-else-if="departments.length === 0" class="text-center py-12">
      <p class="text-stone-400">No departments available yet.</p>
    </div>

    <!-- Main Grid: Table + Detail -->
    <div v-else class="grid gap-8 lg:grid-cols-[minmax(0,1fr),320px]">
      <!-- Departments Table -->
      <div class="border border-stone-100 rounded-2xl overflow-hidden bg-white/40 backdrop-blur-sm">
        <div class="overflow-x-auto">
          <table class="w-full text-left">
            <thead class="border-b border-stone-100">
              <tr>
                <th class="px-5 py-4 text-xs font-medium uppercase tracking-wider text-stone-400">Name</th>
                <th class="px-5 py-4 text-xs font-medium uppercase tracking-wider text-stone-400">Slug</th>
                <th class="px-5 py-4 text-xs font-medium uppercase tracking-wider text-stone-400">Location</th>
                <th class="px-5 py-4 text-xs font-medium uppercase tracking-wider text-stone-400">Status</th>
                <th class="px-5 py-4 text-xs font-medium uppercase tracking-wider text-stone-400"></th>
              </tr>
            </thead>
            <tbody class="divide-y divide-stone-50">
              <tr v-for="department in departments" :key="Number(department.id)" class="hover:bg-stone-50/50 transition-colors">
                <td class="px-5 py-3 text-sm font-medium text-stone-800">{{ department.name }}</td>
                <td class="px-5 py-3 text-sm text-stone-600 font-mono text-xs">{{ department.slug }}</td>
                <td class="px-5 py-3 text-sm text-stone-600">{{ department.location }}</td>
                <td class="px-5 py-3">
                  <span 
                    class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium"
                    :class="{
                      'bg-emerald-100 text-emerald-700': department.status === 'active',
                      'bg-stone-100 text-stone-700': department.status === 'inactive'
                    }"
                  >
                    {{ department.status }}
                  </span>
                </td>
                <td class="px-5 py-3">
                  <div class="flex items-center gap-2">
                    <IconButton 
                      icon="view"
                      size="sm"
                      variant="ghost"
                      @click="loadDepartmentDetail(Number(department.id))"
                    />
                    <IconButton 
                      icon="edit"
                      size="sm"
                      variant="ghost"
                      @click="openEdit(department)"
                    />
                    <IconButton 
                      icon="delete"
                      size="sm"
                      variant="ghost"
                      @click="deleteDepartment(Number(department.id))"
                    />
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Detail Panel -->
      <div class="border border-stone-100 rounded-2xl bg-white/40 backdrop-blur-sm p-5">
        <div class="flex items-center justify-between mb-4">
          <p class="text-xs font-semibold uppercase tracking-wider text-stone-400">Department Detail</p>
          <IconButton 
            v-if="departmentDetail" 
            icon="close"
            size="sm"
            variant="ghost"
            @click="departmentDetail = null; detailError = ''"
          />
        </div>

        <p v-if="detailLoading" class="text-sm text-stone-400">Loading detail...</p>
        <p v-else-if="detailError" class="text-sm text-rose-500">{{ detailError }}</p>
        <p v-else-if="!departmentDetail" class="text-sm text-stone-400">Select a department and click View.</p>

        <div v-else class="space-y-3">
          <div class="border-b border-stone-100 pb-2">
            <p class="text-[10px] uppercase tracking-wider text-stone-400">ID</p>
            <p class="text-sm text-stone-600">{{ departmentDetail.id }}</p>
          </div>
          <div class="border-b border-stone-100 pb-2">
            <p class="text-[10px] uppercase tracking-wider text-stone-400">Name</p>
            <p class="text-sm font-medium text-stone-700">{{ departmentDetail.name }}</p>
          </div>
          <div class="border-b border-stone-100 pb-2">
            <p class="text-[10px] uppercase tracking-wider text-stone-400">Slug</p>
            <p class="text-sm font-mono text-stone-600">{{ departmentDetail.slug }}</p>
          </div>
          <div class="border-b border-stone-100 pb-2">
            <p class="text-[10px] uppercase tracking-wider text-stone-400">Location</p>
            <p class="text-sm text-stone-600">{{ departmentDetail.location }}</p>
          </div>
          <div class="border-b border-stone-100 pb-2">
            <p class="text-[10px] uppercase tracking-wider text-stone-400">Description</p>
            <p class="text-sm text-stone-600">{{ departmentDetail.description }}</p>
          </div>
          <div>
            <p class="text-[10px] uppercase tracking-wider text-stone-400">Status</p>
            <p class="text-sm text-stone-600 capitalize">{{ departmentDetail.status }}</p>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal -->
    <div v-if="showForm" class="fixed inset-0 z-50 flex items-center justify-center bg-black/20 backdrop-blur-sm" @click.self="showForm = false">
      <div class="w-full max-w-md rounded-2xl bg-white p-6 shadow-xl max-h-[90vh] overflow-y-auto">
        <h2 class="text-xl font-light tracking-tight text-stone-800 mb-4">{{ editId ? 'Update Department' : 'New Department' }}</h2>
        
        <form class="space-y-4" @submit.prevent="saveDepartment">
          <!-- Name -->
          <div>
            <label class="block text-xs font-medium text-stone-500 mb-1">Department Name *</label>
            <input 
              v-model="form.name" 
              required 
              class="w-full rounded-full border border-stone-200 px-4 py-2 text-sm bg-white/60 focus:outline-none focus:border-stone-300 placeholder:text-stone-400"
              placeholder="e.g., Human Resources"
            />
          </div>

          <!-- Slug -->
          <div>
            <label class="block text-xs font-medium text-stone-500 mb-1">Slug *</label>
            <input 
              v-model="form.slug" 
              required 
              class="w-full rounded-full border border-stone-200 px-4 py-2 text-sm bg-white/60 focus:outline-none focus:border-stone-300 placeholder:text-stone-400"
              placeholder="e.g., human-resources"
            />
            <p class="text-xs text-stone-400 mt-1">URL-friendly identifier (unique per institution)</p>
          </div>

          <!-- Description -->
          <div>
            <label class="block text-xs font-medium text-stone-500 mb-1">Description *</label>
            <textarea 
              v-model="form.description" 
              required 
              rows="3"
              class="w-full rounded-2xl border border-stone-200 px-4 py-2 text-sm bg-white/60 focus:outline-none focus:border-stone-300 placeholder:text-stone-400 resize-none"
              placeholder="Brief description of the department"
            />
          </div>

          <!-- Location -->
          <div>
            <label class="block text-xs font-medium text-stone-500 mb-1">Location *</label>
            <input 
              v-model="form.location" 
              required 
              class="w-full rounded-full border border-stone-200 px-4 py-2 text-sm bg-white/60 focus:outline-none focus:border-stone-300 placeholder:text-stone-400"
              placeholder="e.g., Building A, Floor 2"
            />
          </div>

          <!-- Status -->
          <div>
            <label class="block text-xs font-medium text-stone-500 mb-1">Status (optional)</label>
            <select 
              v-model="form.status"
              class="w-full rounded-full border border-stone-200 px-4 py-2 text-sm bg-white/60 focus:outline-none focus:border-stone-300"
            >
              <option value="active">Active</option>
              <option value="inactive">Inactive</option>
            </select>
          </div>

          <div class="flex justify-end gap-2 pt-2">
            <IconButton 
              icon="close"
              label="Cancel"
              variant="ghost"
              type="button"
              @click="() => { showForm = false; resetForm() }" 
            />
            <IconButton 
              icon="save"
              label="Save"
              variant="primary"
              type="submit"
              :disabled="saving" 
            />
          </div>
        </form>
      </div>
    </div>
  </div>
</template>