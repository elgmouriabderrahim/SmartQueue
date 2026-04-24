<script setup lang="ts">
import { onMounted, reactive, ref } from 'vue'
import PageHeader from '@/components/PageHeader.vue'
import { smartQueueApi } from '@/services/smartQueueApi'
import { useAuthStore } from '@/stores/auth'
import { toApiError } from '@/utils/http'

const authStore = useAuthStore()
const loading = ref(false)
const saving = ref(false)
const error = ref('')

const form = reactive({
  first_name: '',
  last_name: '',
  email: '',
  identity_number: '',
  password: '',
  password_confirmation: '',
})

async function loadProfile() {
  loading.value = true
  error.value = ''

  try {
    const response = await smartQueueApi.profile()
    const profile: any = response.data.data
    form.first_name = profile.first_name || ''
    form.last_name = profile.last_name || ''
    form.email = profile.email || ''
    form.identity_number = profile.identity_number || ''
  } catch (err) {
    error.value = toApiError(err).message
  } finally {
    loading.value = false
  }
}

async function saveProfile() {
  saving.value = true
  error.value = ''

  try {
    const payload: Record<string, unknown> = {
      first_name: form.first_name,
      last_name: form.last_name,
      email: form.email,
      identity_number: form.identity_number || null,
    }

    if (form.password) {
      payload.password = form.password
      payload.password_confirmation = form.password_confirmation
    }

    const response = await smartQueueApi.updateProfile(payload)
    authStore.setCurrentUser(response.data.data as any)

    form.password = ''
    form.password_confirmation = ''
  } catch (err) {
    error.value = toApiError(err).message
  } finally {
    saving.value = false
  }
}

onMounted(loadProfile)
</script>

<template>
  <section>
    <PageHeader title="Profile" subtitle="Manage your account information." />

    <p v-if="error" class="mb-4 rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-700">{{ error }}</p>

    <form class="grid grid-cols-1 gap-3 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm md:grid-cols-2" @submit.prevent="saveProfile">
      <input v-model="form.first_name" required placeholder="First name" class="input-shell" :disabled="loading" />
      <input v-model="form.last_name" required placeholder="Last name" class="input-shell" :disabled="loading" />
      <input v-model="form.email" type="email" required placeholder="Email" class="input-shell" :disabled="loading" />
      <input v-model="form.identity_number" placeholder="Identity number" class="input-shell" :disabled="loading" />
      <input v-model="form.password" type="password" minlength="8" placeholder="New password (optional)" class="input-shell" />
      <input v-model="form.password_confirmation" type="password" minlength="8" placeholder="Confirm new password" class="input-shell" />
      <button :disabled="saving || loading" class="rounded-xl bg-black px-4 py-3 text-sm font-semibold text-white md:col-span-2">
        {{ saving ? 'Saving...' : 'Save profile' }}
      </button>
    </form>
  </section>
</template>
