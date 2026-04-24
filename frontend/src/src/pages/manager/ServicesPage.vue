<script setup lang="ts">
import { computed, onMounted, reactive, ref } from 'vue'
import { useAuthStore } from '@/stores/auth'
import { smartQueueApi } from '@/services/smartQueueApi'
import { toApiError } from '@/utils/http'
import BaseBadge from '@/components/ui/BaseBadge.vue'
import BaseButton from '@/components/ui/BaseButton.vue'
import BaseCard from '@/components/ui/BaseCard.vue'
import BaseModal from '@/components/ui/BaseModal.vue'
import BaseTable from '@/components/ui/BaseTable.vue'

const authStore = useAuthStore()
const institutionId = computed(() => Number(authStore.user?.institution_id || 0))

const loading = ref(false)
const saving = ref(false)
const deletingId = ref<number | null>(null)
const error = ref('')
const services = ref<any[]>([])
const editId = ref<number | null>(null)
const showForm = ref(false)

const form = reactive({
  name: '',
  description: '',
  duration: 15,
})

function resetForm(): void {
  form.name = ''
  form.description = ''
  form.duration = 15
  editId.value = null
}

function openCreate(): void {
  resetForm()
  showForm.value = true
}

function openEdit(service: any): void {
  editId.value = Number(service.id)
  form.name = String(service.name || '')
  form.description = String(service.description || '')
  form.duration = Number(service.duration || 15)
  showForm.value = true
}

async function loadServices(): Promise<void> {
  if (!institutionId.value) {
    services.value = []
    return
  }

  loading.value = true
  error.value = ''

  try {
    const response = await smartQueueApi.services({ per_page: 100 })
    const all = response.data.data.data || []
    services.value = all.filter((item: any) => Number(item.institution_id) === institutionId.value)
  } catch (err) {
    error.value = toApiError(err).message
  } finally {
    loading.value = false
  }
}

async function saveService(): Promise<void> {
  if (!institutionId.value) {
    return
  }

  saving.value = true
  error.value = ''

  try {
    const payload = {
      institution_id: institutionId.value,
      name: form.name,
      description: form.description,
      duration: Number(form.duration),
    }

    if (editId.value) {
      await smartQueueApi.updateService(editId.value, payload)
    } else {
      await smartQueueApi.createService(payload)
    }

    showForm.value = false
    resetForm()
    await loadServices()
  } catch (err) {
    error.value = toApiError(err).message
  } finally {
    saving.value = false
  }
}

async function removeService(id: number): Promise<void> {
  deletingId.value = id
  error.value = ''

  try {
    await smartQueueApi.deleteService(id)
    await loadServices()
  } catch (err) {
    error.value = toApiError(err).message
  } finally {
    deletingId.value = null
  }
}

onMounted(loadServices)
</script>

<template>
  <div class="space-y-8">
    <!-- Header -->
    <div class="flex flex-wrap items-center justify-between gap-4">
      <div>
        <p class="text-[11px] font-semibold uppercase tracking-[0.3em] text-stone-400">Catalog</p>
        <h1 class="mt-2 text-3xl font-light tracking-tight text-stone-800">Manage Institution Services</h1>
        <p class="mt-1 text-stone-500">Create and manage services offered to citizens</p>
        <div class="mt-3 h-px w-12 bg-amber-300" />
      </div>
      <button 
        @click="openCreate" 
        class="rounded-full bg-stone-800 px-4 py-2 text-sm font-medium text-white transition-all duration-200 hover:bg-stone-700 hover:-translate-y-0.5"
      >
        Create Service
      </button>
    </div>

    <!-- Error & Loading -->
    <p v-if="error" class="text-sm text-rose-500">{{ error }}</p>
    <p v-else-if="loading" class="text-sm text-stone-400">Loading services...</p>

    <!-- Empty State -->
    <div v-else-if="services.length === 0" class="text-center py-12">
      <p class="text-stone-400">No services yet. Create your first service.</p>
    </div>

    <!-- Services Table -->
    <div v-else class="border border-stone-100 rounded-2xl overflow-hidden bg-white/40 backdrop-blur-sm">
      <div class="overflow-x-auto">
        <table class="w-full text-left">
          <thead class="border-b border-stone-100">
            <tr>
              <th class="px-5 py-4 text-xs font-medium uppercase tracking-wider text-stone-400">Name</th>
              <th class="px-5 py-4 text-xs font-medium uppercase tracking-wider text-stone-400">Description</th>
              <th class="px-5 py-4 text-xs font-medium uppercase tracking-wider text-stone-400">Duration</th>
              <th class="px-5 py-4 text-xs font-medium uppercase tracking-wider text-stone-400">Status</th>
              <th class="px-5 py-4 text-xs font-medium uppercase tracking-wider text-stone-400"></th>
            </tr>
          </thead>
          <tbody class="divide-y divide-stone-50">
            <tr v-for="service in services" :key="Number(service.id)" class="hover:bg-stone-50/30 transition-colors">
              <td class="px-5 py-3 text-sm font-medium text-stone-800">{{ service.name }}</td>
              <td class="px-5 py-3 text-sm text-stone-600 max-w-md">{{ service.description }}</td>
              <td class="px-5 py-3">
                <span class="inline-flex rounded-full bg-stone-100 px-2.5 py-0.5 text-xs font-medium text-stone-600">
                  {{ service.duration }} min
                </span>
              </td>
              <td class="px-5 py-3">
                <span 
                  class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium"
                  :class="service.status === 'active' || !service.status ? 'bg-emerald-100 text-emerald-700' : 'bg-stone-100 text-stone-500'"
                >
                  {{ service.status || 'active' }}
                </span>
              </td>
              <td class="px-5 py-3">
                <div class="flex items-center gap-3">
                  <button 
                    class="text-xs text-stone-400 hover:text-stone-600 transition-colors" 
                    @click="openEdit(service)"
                  >
                    Edit
                  </button>
                  <button 
                    class="text-xs text-rose-400 hover:text-rose-600 transition-colors" 
                    :disabled="deletingId === Number(service.id)"
                    @click="removeService(Number(service.id))"
                  >
                    {{ deletingId === Number(service.id) ? 'Removing...' : 'Delete' }}
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Modal -->
    <BaseModal :open="showForm" :title="editId ? 'Update Service' : 'Create Service'" @close="showForm = false">
      <form class="space-y-4" @submit.prevent="saveService">
        <div>
          <label class="block text-xs font-medium text-stone-500 mb-1">Service Name</label>
          <input 
            v-model="form.name" 
            required 
            class="w-full rounded-full border border-stone-200 px-4 py-2 text-sm bg-white/60 focus:outline-none focus:border-stone-300 placeholder:text-stone-400"
            placeholder="e.g., Passport Renewal"
          />
        </div>

        <div>
          <label class="block text-xs font-medium text-stone-500 mb-1">Description</label>
          <textarea 
            v-model="form.description" 
            required 
            rows="4" 
            class="w-full rounded-2xl border border-stone-200 px-4 py-2 text-sm bg-white/60 focus:outline-none focus:border-stone-300 placeholder:text-stone-400 resize-none"
            placeholder="Describe the service..."
          />
        </div>

        <div>
          <label class="block text-xs font-medium text-stone-500 mb-1">Duration (minutes)</label>
          <input 
            v-model.number="form.duration" 
            type="number" 
            min="5" 
            required 
            class="w-full rounded-full border border-stone-200 px-4 py-2 text-sm bg-white/60 focus:outline-none focus:border-stone-300"
            placeholder="Duration in minutes"
          />
        </div>

        <div class="flex justify-end gap-3 pt-2">
          <button 
            type="button" 
            @click="showForm = false" 
            class="rounded-full px-4 py-2 text-sm font-medium text-stone-500 hover:bg-stone-100 transition-colors"
          >
            Cancel
          </button>
          <button 
            type="submit" 
            :disabled="saving" 
            class="rounded-full bg-stone-800 px-4 py-2 text-sm font-medium text-white transition-all duration-200 hover:bg-stone-700 hover:-translate-y-0.5 disabled:opacity-50"
          >
            {{ saving ? 'Saving...' : 'Save Service' }}
          </button>
        </div>
      </form>
    </BaseModal>
  </div>
</template>