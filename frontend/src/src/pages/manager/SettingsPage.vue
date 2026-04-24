<script setup lang="ts">
import { computed, onMounted, reactive, ref } from 'vue'
import { useAuthStore } from '@/stores/auth'
import { smartQueueApi } from '@/services/smartQueueApi'
import { toApiError } from '@/utils/http'
import BaseButton from '@/components/ui/BaseButton.vue'
import BaseCard from '@/components/ui/BaseCard.vue'

const authStore = useAuthStore()
const institutionId = computed(() => Number(authStore.user?.institution_id || 0))

const loading = ref(false)
const saving = ref(false)
const error = ref('')
const success = ref('')

const form = reactive({
  name: '',
  description: '',
  city: '',
  address: '',
  working_days: 'monday,tuesday,wednesday,thursday,friday',
  opening_time: '08:00',
  closing_time: '17:00',
})

async function loadInstitution(): Promise<void> {
  if (!institutionId.value) {
    return
  }

  loading.value = true
  error.value = ''

  try {
    const response = await smartQueueApi.institution(institutionId.value)
    const item = response.data.data

    form.name = String(item.name || '')
    form.description = String(item.description || '')
    form.city = String(item.city || '')
    form.address = String((item as any).adress || '')
    form.opening_time = String(item.opening_time || '08:00')
    form.closing_time = String(item.closing_time || '17:00')
    form.working_days = String((item as any).working_days || form.working_days)
  } catch (err) {
    error.value = toApiError(err).message
  } finally {
    loading.value = false
  }
}

async function saveInstitution(): Promise<void> {
  if (!institutionId.value) {
    return
  }

  saving.value = true
  error.value = ''
  success.value = ''

  try {
    await smartQueueApi.updateInstitution(institutionId.value, {
      name: form.name,
      description: form.description,
      city: form.city,
      adress: form.address,
      working_days: form.working_days.split(',').map((d) => d.trim()).filter(Boolean),
      opening_time: form.opening_time,
      closing_time: form.closing_time,
    })

    success.value = 'Institution profile updated successfully.'
  } catch (err) {
    error.value = toApiError(err).message
  } finally {
    saving.value = false
  }
}

onMounted(loadInstitution)
</script>

<template>
  <div class="space-y-8">
    <!-- Header -->
    <div>
      <p class="text-[11px] font-semibold uppercase tracking-[0.3em] text-stone-400">Settings</p>
      <h1 class="mt-2 text-3xl font-light tracking-tight text-stone-800">Institution Profile</h1>
      <p class="mt-1 text-stone-500">Manage your institution details and working hours</p>
      <div class="mt-3 h-px w-12 bg-amber-300" />
    </div>

    <!-- Messages -->
    <p v-if="error" class="text-sm text-rose-500">{{ error }}</p>
    <p v-if="success" class="text-sm text-emerald-600">{{ success }}</p>

    <!-- Form Card -->
    <BaseCard title="Institution Information" subtitle="Update Details">
      <p v-if="loading" class="text-sm text-stone-400">Loading institution...</p>

      <form v-else class="grid gap-4 md:grid-cols-2" @submit.prevent="saveInstitution">
        <div>
          <label class="block text-xs font-medium text-stone-500 mb-1">Institution Name</label>
          <input 
            v-model="form.name" 
            required 
            class="w-full rounded-full border border-stone-200 px-4 py-2 text-sm bg-white/60 focus:outline-none focus:border-stone-300 placeholder:text-stone-400"
            placeholder="Institution name"
          />
        </div>

        <div>
          <label class="block text-xs font-medium text-stone-500 mb-1">City</label>
          <input 
            v-model="form.city" 
            required 
            class="w-full rounded-full border border-stone-200 px-4 py-2 text-sm bg-white/60 focus:outline-none focus:border-stone-300 placeholder:text-stone-400"
            placeholder="City"
          />
        </div>

        <div class="md:col-span-2">
          <label class="block text-xs font-medium text-stone-500 mb-1">Address</label>
          <input 
            v-model="form.address" 
            required 
            class="w-full rounded-full border border-stone-200 px-4 py-2 text-sm bg-white/60 focus:outline-none focus:border-stone-300 placeholder:text-stone-400"
            placeholder="Address"
          />
        </div>

        <div class="md:col-span-2">
          <label class="block text-xs font-medium text-stone-500 mb-1">Description</label>
          <textarea 
            v-model="form.description" 
            rows="4" 
            required 
            class="w-full rounded-2xl border border-stone-200 px-4 py-2 text-sm bg-white/60 focus:outline-none focus:border-stone-300 placeholder:text-stone-400 resize-none"
            placeholder="Description"
          />
        </div>

        <div>
          <label class="block text-xs font-medium text-stone-500 mb-1">Opening Time</label>
          <input 
            v-model="form.opening_time" 
            type="time" 
            required 
            class="w-full rounded-full border border-stone-200 px-4 py-2 text-sm bg-white/60 focus:outline-none focus:border-stone-300"
          />
        </div>

        <div>
          <label class="block text-xs font-medium text-stone-500 mb-1">Closing Time</label>
          <input 
            v-model="form.closing_time" 
            type="time" 
            required 
            class="w-full rounded-full border border-stone-200 px-4 py-2 text-sm bg-white/60 focus:outline-none focus:border-stone-300"
          />
        </div>

        <div class="md:col-span-2">
          <label class="block text-xs font-medium text-stone-500 mb-1">Working Days</label>
          <input 
            v-model="form.working_days" 
            required 
            class="w-full rounded-full border border-stone-200 px-4 py-2 text-sm bg-white/60 focus:outline-none focus:border-stone-300 placeholder:text-stone-400"
            placeholder="e.g., monday,tuesday,wednesday"
          />
          <p class="mt-1 text-xs text-stone-400">Comma-separated list of working days</p>
        </div>

        <div class="md:col-span-2 pt-2">
          <BaseButton type="submit" :disabled="saving">
            {{ saving ? 'Saving...' : 'Save Changes' }}
          </BaseButton>
        </div>
      </form>
    </BaseCard>
  </div>
</template>