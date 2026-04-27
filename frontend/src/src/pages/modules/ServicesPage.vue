<script setup lang="ts">
import { computed, onMounted, reactive, ref } from 'vue'
import EmptyState from '@/components/EmptyState.vue'
import LoadingState from '@/components/LoadingState.vue'
import PageHeader from '@/components/PageHeader.vue'
import IconButton from '@/components/ui/IconButton.vue'
import { smartQueueApi } from '@/services/smartQueueApi'
import { useAuthStore } from '@/stores/auth'
import { toApiError } from '@/utils/http'

const authStore = useAuthStore()
const canManageServices = computed(() => authStore.user?.role === 'admin' || authStore.user?.role === 'manager')

const departmentsByInstitution = computed(() => {
  if (!form.institution_id) return []
  return departments.value.filter((d: any) => Number(d.institution_id) === Number(form.institution_id))
})

const loading = ref(false)
const saving = ref(false)
const error = ref('')
const services = ref<any[]>([])
const institutions = ref<any[]>([])
const departments = ref<any[]>([])

const form = reactive({
  institution_id: 0,
  department_id: '',
  name: '',
  description: '',
  duration: 15,
  capacity: 20,
  opening_time: '08:00',
  closing_time: '16:00',
  working_days: 'monday,tuesday,wednesday,thursday,friday',
  status: 'active',
})

async function loadData() {
  loading.value = true
  error.value = ''

  try {
    const [servicesResponse, institutionsResponse, departmentsResponse] = await Promise.all([
      smartQueueApi.services(),
      smartQueueApi.institutions(),
      smartQueueApi.departments({ per_page: 100 }),
    ])

    services.value = servicesResponse.data.data.data
    institutions.value = institutionsResponse.data.data.data
    departments.value = departmentsResponse.data.data.data || []
  } catch (err) {
    error.value = toApiError(err).message
  } finally {
    loading.value = false
  }
}

async function createService() {
  saving.value = true
  error.value = ''

  try {
    if (!form.department_id) {
      throw new Error('Please select a department for this service.')
    }

    await smartQueueApi.createService({
      ...form,
      institution_id: Number(form.institution_id),
      department_id: Number(form.department_id),
      duration: Number(form.duration),
      capacity: Number(form.capacity),
      working_days: form.working_days.split(',').map((day) => day.trim()).filter(Boolean),
    })
    await loadData()
  } catch (err) {
    error.value = toApiError(err).message
  } finally {
    saving.value = false
  }
}

onMounted(loadData)
</script>

<template>
  <div class="space-y-8">
    <PageHeader title="Services" subtitle="List and manage institution services." />

    <!-- Create Service Form (Admin/Manager only) -->
    <div v-if="canManageServices" class="rounded-2xl bg-white/40 backdrop-blur-sm border border-stone-100 p-5">
      <h2 class="text-xs font-semibold uppercase tracking-wider text-stone-500">Create Service</h2>
      <p class="mt-1 text-sm text-stone-400">Add a new service to an institution</p>
      
      <form class="mt-4 grid grid-cols-1 gap-3 md:grid-cols-2" @submit.prevent="createService">
        <div class="md:col-span-2">
          <label class="block text-xs font-medium text-stone-500 mb-1">Institution *</label>
          <select v-model.number="form.institution_id" required class="w-full rounded-full border border-stone-200 px-4 py-2 text-sm bg-white/60 focus:outline-none focus:border-stone-300">
            <option :value="0" disabled>Select institution</option>
            <option v-for="institution in institutions" :key="Number(institution.id)" :value="Number(institution.id)">
              {{ institution.name }}
            </option>
          </select>
        </div>

        <div class="md:col-span-2">
          <label class="block text-xs font-medium text-stone-500 mb-1">Department *</label>
          <select v-model.number="form.department_id" required :disabled="departmentsByInstitution.length === 0" class="w-full rounded-full border border-stone-200 px-4 py-2 text-sm bg-white/60 focus:outline-none focus:border-stone-300">
            <option value="" disabled>Select department</option>
            <option v-for="dept in departmentsByInstitution" :key="Number(dept.id)" :value="Number(dept.id)">
              {{ dept.name }}
            </option>
          </select>
          <p v-if="departmentsByInstitution.length === 0" class="mt-1 text-xs text-stone-400">No departments available for this institution. Create a department first.</p>
        </div>

        <div>
          <label class="block text-xs font-medium text-stone-500 mb-1">Service Name *</label>
          <input 
            v-model="form.name" 
            placeholder="e.g., Registration" 
            required 
            class="w-full rounded-full border border-stone-200 px-4 py-2 text-sm bg-white/60 focus:outline-none focus:border-stone-300 placeholder:text-stone-400"
          />
        </div>

        <div>
          <label class="block text-xs font-medium text-stone-500 mb-1">Duration (minutes) *</label>
          <input 
            v-model.number="form.duration" 
            type="number" 
            min="1" 
            required 
            placeholder="15" 
            class="w-full rounded-full border border-stone-200 px-4 py-2 text-sm bg-white/60 focus:outline-none focus:border-stone-300 placeholder:text-stone-400"
          />
        </div>

        <div>
          <label class="block text-xs font-medium text-stone-500 mb-1">Capacity *</label>
          <input 
            v-model.number="form.capacity" 
            type="number" 
            min="1" 
            required 
            placeholder="20" 
            class="w-full rounded-full border border-stone-200 px-4 py-2 text-sm bg-white/60 focus:outline-none focus:border-stone-300 placeholder:text-stone-400"
          />
        </div>

        <div>
          <label class="block text-xs font-medium text-stone-500 mb-1">Opening Time *</label>
          <input 
            v-model="form.opening_time" 
            type="time" 
            required 
            class="w-full rounded-full border border-stone-200 px-4 py-2 text-sm bg-white/60 focus:outline-none focus:border-stone-300"
          />
        </div>

        <div>
          <label class="block text-xs font-medium text-stone-500 mb-1">Closing Time *</label>
          <input 
            v-model="form.closing_time" 
            type="time" 
            required 
            class="w-full rounded-full border border-stone-200 px-4 py-2 text-sm bg-white/60 focus:outline-none focus:border-stone-300"
          />
        </div>

        <div class="md:col-span-2">
          <label class="block text-xs font-medium text-stone-500 mb-1">Working Days * <span class="text-stone-400">(comma-separated)</span></label>
          <input 
            v-model="form.working_days" 
            placeholder="e.g., monday,tuesday,wednesday,thursday,friday" 
            required 
            class="w-full rounded-full border border-stone-200 px-4 py-2 text-sm bg-white/60 focus:outline-none focus:border-stone-300 placeholder:text-stone-400"
          />
          <p class="text-xs text-stone-400 mt-1">Day names: monday, tuesday, wednesday, thursday, friday, saturday, sunday</p>
        </div>

        <div class="md:col-span-2">
          <label class="block text-xs font-medium text-stone-500 mb-1">Description *</label>
          <textarea 
            v-model="form.description" 
            placeholder="Brief description of what this service offers" 
            required 
            rows="3"
            class="w-full rounded-2xl border border-stone-200 px-4 py-2 text-sm bg-white/60 focus:outline-none focus:border-stone-300 placeholder:text-stone-400 resize-none"
          />
        </div>

        <IconButton 
          icon="add"
          label="Create Service"
          variant="primary"
          type="submit"
          :disabled="saving || departmentsByInstitution.length === 0"
          class="md:col-span-2"
        />
      </form>
    </div>

    <!-- Loading & Error -->
    <LoadingState v-if="loading" />
    <p v-else-if="error" class="text-sm text-rose-500">{{ error }}</p>
    <EmptyState v-else-if="services.length === 0" message="No services found." />

    <!-- Services Grid -->
    <div v-else class="grid gap-6 sm:grid-cols-2 xl:grid-cols-3">
      <router-link 
        v-for="service in services" 
        :key="Number(service.id)" 
        :to="`/services/${service.id}`"
        class="group block"
      >
        <div class="rounded-2xl border border-stone-100 bg-white/40 backdrop-blur-sm p-5 transition-all duration-200 hover:border-stone-200 hover:bg-white/60">
          <h3 class="text-lg font-medium tracking-tight text-stone-800">{{ service.name }}</h3>
          <p class="mt-1 text-sm text-stone-500 line-clamp-2">{{ service.description }}</p>
          <div class="mt-3 flex flex-wrap gap-3 text-xs text-stone-400">
            <span class="flex items-center gap-1">
              <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                <circle cx="12" cy="12" r="10" />
                <polyline points="12 6 12 12 16 14" />
              </svg>
              {{ service.duration }} min
            </span>
            <span class="flex items-center gap-1">
              <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                <path d="M12 2v4M12 22v-4M4 12H2M22 12h-2M19.07 4.93l-2.83 2.83M4.93 19.07l2.83-2.83M19.07 19.07l-2.83-2.83M4.93 4.93l2.83 2.83" />
              </svg>
              {{ service.capacity }}
            </span>
            <span class="capitalize flex items-center gap-1">
              <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                <circle cx="12" cy="12" r="3" />
                <path d="M20 7h-4.18A3 3 0 0 0 16 5.18V4a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v1.18A3 3 0 0 0 8.18 7H4" />
              </svg>
              {{ service.status }}
            </span>
          </div>
          <div class="mt-3 inline-flex items-center gap-1 text-xs text-stone-400 group-hover:text-stone-600 transition-colors">
            View details
            <svg class="h-3 w-3 transition-transform group-hover:translate-x-1" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
              <path d="M9 6l6 6-6 6" stroke="currentColor" stroke-linecap="round"/>
            </svg>
          </div>
        </div>
      </router-link>
    </div>
  </div>
</template>