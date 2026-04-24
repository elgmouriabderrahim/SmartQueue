<script setup lang="ts">
import { onMounted, reactive, ref } from 'vue'
import { smartQueueApi } from '@/services/smartQueueApi'
import { toApiError } from '@/utils/http'

const loading = ref(false)
const saving = ref(false)
const error = ref('')
const settings = ref<any[]>([])
const institutions = ref<any[]>([])
const editId = ref<number | null>(null)
const showForm = ref(false)
const detailLoading = ref(false)
const detailError = ref('')
const settingDetail = ref<any | null>(null)

const form = reactive({
  institution_id: 0,
  key: '',
  value: '',
  type: 'string',
  description: '',
})

function resetForm(): void {
  form.institution_id = 0
  form.key = ''
  form.value = ''
  form.type = 'string'
  form.description = ''
  editId.value = null
}

function openCreate(): void {
  resetForm()
  showForm.value = true
}

function openEdit(setting: any): void {
  editId.value = Number(setting.id)
  form.institution_id = Number(setting.institution_id || 0)
  form.key = String(setting.key || '')
  form.value = String(setting.value || '')
  form.type = String(setting.type || 'string')
  form.description = String(setting.description || '')
  showForm.value = true
}

async function loadData(): Promise<void> {
  loading.value = true
  error.value = ''

  try {
    const [settingsRes, institutionsRes] = await Promise.all([
      smartQueueApi.settings({ per_page: 100 }),
      smartQueueApi.institutions({ per_page: 100 }),
    ])

    settings.value = settingsRes.data.data.data || []
    institutions.value = institutionsRes.data.data.data || []
  } catch (err) {
    error.value = toApiError(err).message
  } finally {
    loading.value = false
  }
}

async function saveSetting(): Promise<void> {
  saving.value = true
  error.value = ''

  try {
    const payload = {
      institution_id: form.institution_id > 0 ? Number(form.institution_id) : null,
      key: form.key,
      value: form.value,
      type: form.type,
      description: form.description || null,
    }

    if (editId.value) {
      await smartQueueApi.updateSetting(editId.value, payload)
    } else {
      await smartQueueApi.createSetting(payload)
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

async function removeSetting(id: number): Promise<void> {
  error.value = ''

  try {
    await smartQueueApi.deleteSetting(id)
    await loadData()
  } catch (err) {
    error.value = toApiError(err).message
  }
}

async function loadSettingDetail(id: number): Promise<void> {
  detailLoading.value = true
  detailError.value = ''

  try {
    const response = await smartQueueApi.setting(id)
    settingDetail.value = response.data.data
  } catch (err) {
    detailError.value = toApiError(err).message
  } finally {
    detailLoading.value = false
  }
}

function institutionName(id: number | null): string {
  if (!id) return 'Global'
  const institution = institutions.value.find((item) => Number(item.id) === Number(id))
  return institution?.name || `Institution #${id}`
}

onMounted(loadData)
</script>

<template>
  <div class="space-y-8">
    <!-- Header -->
    <div class="flex flex-wrap items-center justify-between gap-4">
      <div>
        <p class="text-[11px] font-semibold uppercase tracking-[0.3em] text-stone-400">Configuration</p>
        <h1 class="mt-2 text-3xl font-light tracking-tight text-stone-800">Platform Settings</h1>
        <p class="mt-1 text-stone-500">Manage system configuration keys</p>
        <div class="mt-3 h-px w-12 bg-amber-300" />
      </div>
      <button 
        @click="openCreate" 
        class="rounded-full bg-stone-800 px-4 py-2 text-sm font-medium text-white transition-all duration-200 hover:bg-stone-700 hover:-translate-y-0.5"
      >
        New Setting
      </button>
    </div>

    <!-- Loading & Error -->
    <p v-if="error" class="text-sm text-rose-500">{{ error }}</p>
    <p v-else-if="loading" class="text-sm text-stone-400">Loading settings...</p>

    <!-- Empty State -->
    <div v-else-if="settings.length === 0" class="text-center py-12">
      <p class="text-stone-400">No settings configured yet.</p>
    </div>

    <!-- Main Grid -->
    <div v-else class="grid gap-8 xl:grid-cols-[minmax(0,1fr),320px]">
      <!-- Settings Table -->
      <div class="border border-stone-100 rounded-2xl overflow-hidden bg-white/40 backdrop-blur-sm">
        <table class="w-full text-left">
          <thead class="border-b border-stone-100">
            <tr>
              <th class="px-5 py-4 text-xs font-medium uppercase tracking-wider text-stone-400">Key</th>
              <th class="px-5 py-4 text-xs font-medium uppercase tracking-wider text-stone-400">Value</th>
              <th class="px-5 py-4 text-xs font-medium uppercase tracking-wider text-stone-400">Type</th>
              <th class="px-5 py-4 text-xs font-medium uppercase tracking-wider text-stone-400">Scope</th>
              <th class="px-5 py-4 text-xs font-medium uppercase tracking-wider text-stone-400"></th>
            </tr>
          </thead>
          <tbody class="divide-y divide-stone-50">
            <tr v-for="setting in settings" :key="Number(setting.id)" class="hover:bg-stone-50/50 transition-colors">
              <td class="px-5 py-3">
                <span class="font-mono text-sm font-medium text-stone-800">{{ setting.key }}</span>
              </td>
              <td class="px-5 py-3 text-sm text-stone-600 max-w-[200px] truncate">{{ setting.value }}</td>
              <td class="px-5 py-3">
                <span class="inline-flex rounded-full bg-stone-100 px-2 py-0.5 text-xs font-medium text-stone-600">
                  {{ setting.type }}
                </span>
              </td>
              <td class="px-5 py-3 text-sm text-stone-500">{{ institutionName(setting.institution_id ? Number(setting.institution_id) : null) }}</td>
              <td class="px-5 py-3">
                <div class="flex items-center gap-2">
                  <button @click="loadSettingDetail(Number(setting.id))" class="text-xs text-stone-400 hover:text-stone-600 transition-colors">View</button>
                  <button @click="openEdit(setting)" class="text-xs text-stone-400 hover:text-stone-600 transition-colors">Edit</button>
                  <button @click="removeSetting(Number(setting.id))" class="text-xs text-rose-400 hover:text-rose-600 transition-colors">Delete</button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Detail Panel -->
      <div class="border border-stone-100 rounded-2xl bg-white/40 backdrop-blur-sm p-5">
        <p class="text-xs font-semibold uppercase tracking-wider text-stone-400">Configuration Detail</p>
        <p class="mt-1 text-sm text-stone-500">Setting information</p>
        
        <div class="mt-4">
          <p v-if="detailLoading" class="text-sm text-stone-400">Loading...</p>
          <p v-else-if="detailError" class="text-sm text-rose-500">{{ detailError }}</p>
          <p v-else-if="!settingDetail" class="text-sm text-stone-400">Select a setting to view details</p>
          <div v-else class="space-y-3">
            <div class="border-b border-stone-100 pb-2">
              <p class="text-[10px] uppercase tracking-wider text-stone-400">Key</p>
              <p class="font-mono text-sm font-medium text-stone-800">{{ settingDetail.key }}</p>
            </div>
            <div class="border-b border-stone-100 pb-2">
              <p class="text-[10px] uppercase tracking-wider text-stone-400">Type</p>
              <p class="text-sm text-stone-600">{{ settingDetail.type }}</p>
            </div>
            <div class="border-b border-stone-100 pb-2">
              <p class="text-[10px] uppercase tracking-wider text-stone-400">Value</p>
              <p class="text-sm text-stone-700 break-words">{{ settingDetail.value }}</p>
            </div>
            <div class="border-b border-stone-100 pb-2">
              <p class="text-[10px] uppercase tracking-wider text-stone-400">Scope</p>
              <p class="text-sm text-stone-600">{{ institutionName(settingDetail.institution_id ? Number(settingDetail.institution_id) : null) }}</p>
            </div>
            <div>
              <p class="text-[10px] uppercase tracking-wider text-stone-400">Description</p>
              <p class="text-sm text-stone-500">{{ settingDetail.description || '—' }}</p>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal -->
    <div v-if="showForm" class="fixed inset-0 z-50 flex items-center justify-center bg-black/20 backdrop-blur-sm" @click.self="showForm = false">
      <div class="w-full max-w-md rounded-2xl bg-white p-6 shadow-xl">
        <h2 class="text-xl font-light tracking-tight text-stone-800 mb-4">{{ editId ? 'Update Setting' : 'New Setting' }}</h2>
        
        <form class="space-y-4" @submit.prevent="saveSetting">
          <div>
            <label class="block text-xs font-medium text-stone-500 mb-1">Scope</label>
            <select v-model.number="form.institution_id" class="w-full rounded-xl border border-stone-200 px-4 py-2.5 text-sm focus:outline-none focus:border-stone-300 bg-white/60">
              <option :value="0">Global (all institutions)</option>
              <option v-for="institution in institutions" :key="Number(institution.id)" :value="Number(institution.id)">
                {{ institution.name }}
              </option>
            </select>
          </div>

          <div>
            <label class="block text-xs font-medium text-stone-500 mb-1">Config Key</label>
            <input v-model="form.key" required class="w-full rounded-xl border border-stone-200 px-4 py-2.5 text-sm focus:outline-none focus:border-stone-300 bg-white/60 font-mono" placeholder="e.g., queue_capacity" />
          </div>

          <div>
            <label class="block text-xs font-medium text-stone-500 mb-1">Type</label>
            <select v-model="form.type" class="w-full rounded-xl border border-stone-200 px-4 py-2.5 text-sm focus:outline-none focus:border-stone-300 bg-white/60">
              <option value="string">String</option>
              <option value="integer">Integer</option>
              <option value="boolean">Boolean</option>
              <option value="json">JSON</option>
            </select>
          </div>

          <div>
            <label class="block text-xs font-medium text-stone-500 mb-1">Value</label>
            <textarea v-model="form.value" required rows="3" class="w-full rounded-xl border border-stone-200 px-4 py-2.5 text-sm focus:outline-none focus:border-stone-300 bg-white/60 font-mono" placeholder="Setting value" />
          </div>

          <div>
            <label class="block text-xs font-medium text-stone-500 mb-1">Description (optional)</label>
            <input v-model="form.description" class="w-full rounded-xl border border-stone-200 px-4 py-2.5 text-sm focus:outline-none focus:border-stone-300 bg-white/60" placeholder="What does this setting control?" />
          </div>

          <div class="flex justify-end gap-3 pt-2">
            <button type="button" @click="showForm = false" class="rounded-full px-4 py-2 text-sm font-medium text-stone-500 hover:bg-stone-100 transition-colors">Cancel</button>
            <button type="submit" :disabled="saving" class="rounded-full bg-stone-800 px-4 py-2 text-sm font-medium text-white transition-all hover:bg-stone-700 hover:-translate-y-0.5 disabled:opacity-50">
              {{ saving ? 'Saving...' : 'Save Setting' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>