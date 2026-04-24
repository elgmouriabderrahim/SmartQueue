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
import IconButton from '@/components/ui/IconButton.vue'

const authStore = useAuthStore()
const institutionId = computed(() => Number(authStore.user?.institution_id || 0))

const loading = ref(false)
const saving = ref(false)
const error = ref('')
const counters = ref<any[]>([])
const services = ref<any[]>([])
const editId = ref<number | null>(null)
const showForm = ref(false)
const detailLoading = ref(false)
const detailError = ref('')
const counterDetail = ref<any | null>(null)

const form = reactive({
  service_id: 0,
  counter_number: 1,
  name: '',
  status: 'available',
})

function resetForm(): void {
  form.service_id = 0
  form.counter_number = 1
  form.name = ''
  form.status = 'available'
  editId.value = null
}

function openCreate(): void {
  resetForm()
  showForm.value = true
}

function openEdit(counter: any): void {
  editId.value = Number(counter.id)
  form.service_id = Number(counter.service_id)
  form.counter_number = Number(counter.counter_number || 1)
  form.name = String(counter.name || '')
  form.status = String(counter.status || 'available')
  showForm.value = true
}

async function loadData(): Promise<void> {
  if (!institutionId.value) {
    counters.value = []
    services.value = []
    return
  }

  loading.value = true
  error.value = ''

  try {
    const [countersRes, servicesRes] = await Promise.all([
      smartQueueApi.serviceCounters({ per_page: 100 }),
      smartQueueApi.services({ per_page: 100 }),
    ])

    const allServices = servicesRes.data.data.data || []
    services.value = allServices.filter((item: any) => Number(item.institution_id) === institutionId.value)

    const serviceIds = new Set(services.value.map((item: any) => Number(item.id)))
    counters.value = (countersRes.data.data.data || []).filter((item: any) => serviceIds.has(Number(item.service_id)))
  } catch (err) {
    error.value = toApiError(err).message
  } finally {
    loading.value = false
  }
}

async function saveCounter(): Promise<void> {
  saving.value = true
  error.value = ''

  try {
    const payload = {
      service_id: Number(form.service_id),
      counter_number: Number(form.counter_number),
      name: form.name,
      status: form.status,
    }

    if (editId.value) {
      await smartQueueApi.updateServiceCounter(editId.value, payload)
    } else {
      await smartQueueApi.createServiceCounter(payload)
    }

    showForm.value = false
    resetForm()
    await loadData()
  } catch (err) {
    error.value = toApiError(err).message
  } finally {
    saving.value = false
  }
}

async function removeCounter(id: number): Promise<void> {
  error.value = ''

  try {
    await smartQueueApi.deleteServiceCounter(id)
    await loadData()
  } catch (err) {
    error.value = toApiError(err).message
  }
}

async function loadCounterDetail(id: number): Promise<void> {
  detailLoading.value = true
  detailError.value = ''

  try {
    const response = await smartQueueApi.serviceCounter(id)
    counterDetail.value = response.data.data
  } catch (err) {
    detailError.value = toApiError(err).message
  } finally {
    detailLoading.value = false
  }
}

function serviceName(serviceId: number): string {
  const service = services.value.find((item) => Number(item.id) === Number(serviceId))
  return String(service?.name || '-')
}

onMounted(loadData)
</script>

<template>
  <div class="space-y-8">
    <!-- Header -->
    <div class="flex flex-wrap items-center justify-between gap-4">
      <div>
        <p class="text-[11px] font-semibold uppercase tracking-[0.3em] text-stone-400">Operations</p>
        <h1 class="mt-2 text-3xl font-light tracking-tight text-stone-800">Service Counters</h1>
        <p class="mt-1 text-stone-500">Manage counter assignments and statuses</p>
        <div class="mt-3 h-px w-12 bg-amber-300" />
      </div>
      <IconButton
        icon="add"
        label="Create Counter"
        variant="primary"
        @click="openCreate"
      />
    </div>

    <!-- Error & Loading -->
    <p v-if="error" class="text-sm text-rose-500">{{ error }}</p>
    <p v-else-if="loading" class="text-sm text-stone-400">Loading counters...</p>

    <!-- Empty State -->
    <div v-else-if="counters.length === 0" class="text-center py-12">
      <p class="text-stone-400">No service counters yet for your institution.</p>
    </div>

    <!-- Main Grid: Table + Detail -->
    <div v-else class="grid gap-8 lg:grid-cols-[minmax(0,1fr),320px]">
      <!-- Counters Table -->
      <div class="border border-stone-100 rounded-2xl overflow-hidden bg-white/40 backdrop-blur-sm">
        <div class="overflow-x-auto">
          <table class="w-full text-left">
            <thead class="border-b border-stone-100">
              <tr>
                <th class="px-5 py-4 text-xs font-medium uppercase tracking-wider text-stone-400">Counter</th>
                <th class="px-5 py-4 text-xs font-medium uppercase tracking-wider text-stone-400">Service</th>
                <th class="px-5 py-4 text-xs font-medium uppercase tracking-wider text-stone-400">Name</th>
                <th class="px-5 py-4 text-xs font-medium uppercase tracking-wider text-stone-400">Status</th>
                <th class="px-5 py-4 text-xs font-medium uppercase tracking-wider text-stone-400"></th>
              </tr>
            </thead>
            <tbody class="divide-y divide-stone-50">
              <tr v-for="counter in counters" :key="Number(counter.id)" class="hover:bg-stone-50/30 transition-colors">
                <td class="px-5 py-3 text-sm font-medium text-stone-800">#{{ counter.counter_number }}</td>
                <td class="px-5 py-3 text-sm text-stone-600">{{ serviceName(Number(counter.service_id)) }}</td>
                <td class="px-5 py-3 text-sm text-stone-600">{{ counter.name || '—' }}</td>
                <td class="px-5 py-3">
                  <span 
                    class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium"
                    :class="{
                      'bg-emerald-100 text-emerald-700': counter.status === 'available',
                      'bg-amber-100 text-amber-700': counter.status === 'busy',
                      'bg-stone-100 text-stone-500': counter.status === 'offline'
                    }"
                  >
                    {{ counter.status }}
                  </span>
                </td>
                <td class="px-5 py-3">
                  <div class="flex items-center gap-2">
                    <IconButton 
                      icon="view"
                      size="sm"
                      variant="ghost"
                      @click="loadCounterDetail(Number(counter.id))"
                    />
                    <IconButton 
                      icon="edit"
                      size="sm"
                      variant="ghost"
                      @click="openEdit(counter)"
                    />
                    <IconButton 
                      icon="delete"
                      size="sm"
                      variant="ghost"
                      @click="removeCounter(Number(counter.id))"
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
          <p class="text-xs font-semibold uppercase tracking-wider text-stone-400">Counter Detail</p>
          <IconButton 
            v-if="counterDetail" 
            icon="close"
            size="sm"
            variant="ghost"
            @click="counterDetail = null; detailError = ''"
          />
        </div>

        <p v-if="detailLoading" class="text-sm text-stone-400">Loading detail...</p>
        <p v-else-if="detailError" class="text-sm text-rose-500">{{ detailError }}</p>
        <p v-else-if="!counterDetail" class="text-sm text-stone-400">Select a counter and click View.</p>

        <div v-else class="space-y-3">
          <div class="border-b border-stone-100 pb-2">
            <p class="text-[10px] uppercase tracking-wider text-stone-400">ID</p>
            <p class="text-sm text-stone-600">{{ counterDetail.id }}</p>
          </div>
          <div class="border-b border-stone-100 pb-2">
            <p class="text-[10px] uppercase tracking-wider text-stone-400">Counter Number</p>
            <p class="text-sm font-medium text-stone-700">#{{ counterDetail.counter_number }}</p>
          </div>
          <div class="border-b border-stone-100 pb-2">
            <p class="text-[10px] uppercase tracking-wider text-stone-400">Service</p>
            <p class="text-sm text-stone-600">{{ counterDetail.service?.name || counterDetail.service_id }}</p>
          </div>
          <div class="border-b border-stone-100 pb-2">
            <p class="text-[10px] uppercase tracking-wider text-stone-400">Name</p>
            <p class="text-sm text-stone-600">{{ counterDetail.name || '—' }}</p>
          </div>
          <div>
            <p class="text-[10px] uppercase tracking-wider text-stone-400">Status</p>
            <p class="text-sm text-stone-600">{{ counterDetail.status }}</p>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal -->
    <BaseModal :open="showForm" :title="editId ? 'Update Counter' : 'Create Counter'" @close="showForm = false">
      <form class="space-y-4" @submit.prevent="saveCounter">
        <div>
          <label class="block text-xs font-medium text-stone-500 mb-1">Service</label>
          <select v-model.number="form.service_id" required class="w-full rounded-full border border-stone-200 px-4 py-2 text-sm bg-white/60 focus:outline-none focus:border-stone-300">
            <option :value="0" disabled>Select service</option>
            <option v-for="service in services" :key="Number(service.id)" :value="Number(service.id)">
              {{ service.name }}
            </option>
          </select>
        </div>

        <div>
          <label class="block text-xs font-medium text-stone-500 mb-1">Counter Number</label>
          <input 
            v-model.number="form.counter_number" 
            type="number" 
            min="1" 
            required 
            class="w-full rounded-full border border-stone-200 px-4 py-2 text-sm bg-white/60 focus:outline-none focus:border-stone-300"
            placeholder="Counter number"
          />
        </div>

        <div>
          <label class="block text-xs font-medium text-stone-500 mb-1">Counter Name</label>
          <input 
            v-model="form.name" 
            required 
            class="w-full rounded-full border border-stone-200 px-4 py-2 text-sm bg-white/60 focus:outline-none focus:border-stone-300 placeholder:text-stone-400"
            placeholder="e.g., Window A, Counter 1"
          />
        </div>

        <div>
          <label class="block text-xs font-medium text-stone-500 mb-1">Status</label>
          <select v-model="form.status" class="w-full rounded-full border border-stone-200 px-4 py-2 text-sm bg-white/60 focus:outline-none focus:border-stone-300">
            <option value="available">Available</option>
            <option value="busy">Busy</option>
            <option value="offline">Offline</option>
          </select>
        </div>

        <div class="flex justify-end gap-2 pt-2">
          <IconButton 
            type="button" 
            icon="close"
            label="Cancel"
            variant="ghost"
            @click="showForm = false" 
          />
          <IconButton 
            type="submit" 
            icon="save"
            label="Save"
            variant="primary"
            :disabled="saving" 
          />
        </div>
      </form>
    </BaseModal>
  </div>
</template>