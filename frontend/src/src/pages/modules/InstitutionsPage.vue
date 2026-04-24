<script setup lang="ts">
import { computed, onMounted, reactive, ref } from 'vue'
import EmptyState from '@/components/EmptyState.vue'
import LoadingState from '@/components/LoadingState.vue'
import PageHeader from '@/components/PageHeader.vue'
import { smartQueueApi } from '@/services/smartQueueApi'
import { useAuthStore } from '@/stores/auth'
import { toApiError } from '@/utils/http'

const authStore = useAuthStore()
const isAdmin = computed(() => authStore.role === 'admin')
const isManager = computed(() => authStore.user?.role === 'manager')

const loading = ref(false)
const saving = ref(false)
const error = ref('')
const institutions = ref<any[]>([])
const mapData = ref<any[]>([])
const filters = reactive({ city: '', q: '' })

const form = reactive({
  name: '',
  slug: '',
  city: '',
  adress: '',
  description: '',
  opening_time: '08:00',
  closing_time: '16:00',
  working_days: 'monday,tuesday,wednesday,thursday,friday',
  max_appointments_per_day: 100,
  status: 'inactive',
})

const managerForm = reactive({
  id: 0,
  name: '',
  city: '',
  adress: '',
  description: '',
  opening_time: '08:00',
  closing_time: '16:00',
  working_days: 'monday,tuesday,wednesday,thursday,friday',
  max_appointments_per_day: 100,
})

async function loadData() {
  loading.value = true
  error.value = ''

  try {
    const [listResponse, mapResponse] = await Promise.all([smartQueueApi.institutions(), smartQueueApi.institutionsMap()])
    institutions.value = listResponse.data.data.data
    mapData.value = mapResponse.data.data

    if (isManager.value && authStore.user?.institution_id) {
      const mine = institutions.value.find((item) => Number(item.id) === Number(authStore.user?.institution_id))
      if (mine) {
        managerForm.id = Number(mine.id)
        managerForm.name = String(mine.name || '')
        managerForm.city = String(mine.city || '')
        managerForm.adress = String(mine.adress || '')
        managerForm.description = String(mine.description || '')
        managerForm.opening_time = String(mine.opening_time || '08:00').slice(0, 5)
        managerForm.closing_time = String(mine.closing_time || '16:00').slice(0, 5)
        managerForm.working_days = Array.isArray(mine.working_days) ? mine.working_days.join(',') : 'monday,tuesday,wednesday,thursday,friday'
        managerForm.max_appointments_per_day = Number(mine.max_appointments_per_day || 100)
      }
    }
  } catch (err) {
    error.value = toApiError(err).message
  } finally {
    loading.value = false
  }
}

async function updateMyInstitution() {
  if (!managerForm.id) {
    return
  }

  saving.value = true
  error.value = ''

  try {
    await smartQueueApi.updateInstitution(managerForm.id, {
      name: managerForm.name,
      city: managerForm.city,
      adress: managerForm.adress,
      description: managerForm.description,
      opening_time: managerForm.opening_time,
      closing_time: managerForm.closing_time,
      working_days: managerForm.working_days.split(',').map((item) => item.trim()).filter(Boolean),
      max_appointments_per_day: Number(managerForm.max_appointments_per_day),
    })
    await loadData()
  } catch (err) {
    error.value = toApiError(err).message
  } finally {
    saving.value = false
  }
}

async function applyFilters() {
  loading.value = true
  error.value = ''

  try {
    const response = await smartQueueApi.institutions({
      city: filters.city || undefined,
      q: filters.q || undefined,
    })
    institutions.value = response.data.data.data
  } catch (err) {
    error.value = toApiError(err).message
  } finally {
    loading.value = false
  }
}

async function createInstitution() {
  saving.value = true
  error.value = ''

  try {
    await smartQueueApi.createInstitution({
      ...form,
      working_days: form.working_days.split(',').map((day) => day.trim()).filter(Boolean),
      max_appointments_per_day: Number(form.max_appointments_per_day),
    })
    await loadData()
  } catch (err) {
    error.value = toApiError(err).message
  } finally {
    saving.value = false
  }
}

async function approveInstitution(id: number) {
  error.value = ''
  try {
    await smartQueueApi.approveInstitution(id)
    await loadData()
  } catch (err) {
    error.value = toApiError(err).message
  }
}

onMounted(loadData)
</script>

<template>
  <section>
    <PageHeader title="Institutions" subtitle="Browse institutions and map-ready data." />

    <form class="mb-4 grid gap-3 rounded-xl border border-slate-200 bg-white p-4 shadow-sm md:grid-cols-3" @submit.prevent="applyFilters">
      <input v-model="filters.q" placeholder="Search by name or slug" class="rounded-md border border-slate-300 px-3 py-2" />
      <input v-model="filters.city" placeholder="Filter by city" class="rounded-md border border-slate-300 px-3 py-2" />
      <button class="rounded-md bg-slate-900 px-4 py-2 font-medium text-white">Apply filters</button>
    </form>

    <div v-if="isAdmin" class="mb-6 rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
      <h2 class="mb-3 text-sm font-semibold uppercase tracking-wide text-slate-500">Create Institution</h2>
      <form class="grid grid-cols-1 gap-3 md:grid-cols-2" @submit.prevent="createInstitution">
        <input v-model="form.name" placeholder="Name" required class="rounded-md border border-slate-300 px-3 py-2" />
        <input v-model="form.slug" placeholder="Slug" required class="rounded-md border border-slate-300 px-3 py-2" />
        <input v-model="form.city" placeholder="City" required class="rounded-md border border-slate-300 px-3 py-2" />
        <input v-model="form.adress" placeholder="Address" required class="rounded-md border border-slate-300 px-3 py-2" />
        <input v-model="form.opening_time" type="time" required class="rounded-md border border-slate-300 px-3 py-2" />
        <input v-model="form.closing_time" type="time" required class="rounded-md border border-slate-300 px-3 py-2" />
        <input v-model="form.working_days" placeholder="working days csv" required class="rounded-md border border-slate-300 px-3 py-2" />
        <input v-model.number="form.max_appointments_per_day" type="number" min="0" required class="rounded-md border border-slate-300 px-3 py-2" />
        <textarea v-model="form.description" placeholder="Description" required class="rounded-md border border-slate-300 px-3 py-2 md:col-span-2" />
        <button :disabled="saving" class="rounded-md bg-brand-600 px-4 py-2 font-medium text-white hover:bg-brand-700 md:col-span-2">
          {{ saving ? 'Saving...' : 'Create institution' }}
        </button>
      </form>
    </div>

    <div v-if="isManager && authStore.user?.institution_id" class="mb-6 rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
      <h2 class="mb-3 text-sm font-semibold uppercase tracking-wide text-slate-500">Update My Institution</h2>
      <form class="grid grid-cols-1 gap-3 md:grid-cols-2" @submit.prevent="updateMyInstitution">
        <input v-model="managerForm.name" placeholder="Name" required class="rounded-md border border-slate-300 px-3 py-2" />
        <input v-model="managerForm.city" placeholder="City" required class="rounded-md border border-slate-300 px-3 py-2" />
        <input v-model="managerForm.adress" placeholder="Address" required class="rounded-md border border-slate-300 px-3 py-2" />
        <input v-model="managerForm.opening_time" type="time" required class="rounded-md border border-slate-300 px-3 py-2" />
        <input v-model="managerForm.closing_time" type="time" required class="rounded-md border border-slate-300 px-3 py-2" />
        <input v-model="managerForm.working_days" placeholder="working days csv" required class="rounded-md border border-slate-300 px-3 py-2" />
        <input v-model.number="managerForm.max_appointments_per_day" type="number" min="0" required class="rounded-md border border-slate-300 px-3 py-2" />
        <textarea v-model="managerForm.description" placeholder="Description" required class="rounded-md border border-slate-300 px-3 py-2 md:col-span-2" />
        <button :disabled="saving" class="rounded-md bg-black px-4 py-2 font-medium text-white md:col-span-2">
          {{ saving ? 'Saving...' : 'Save my institution' }}
        </button>
      </form>
    </div>

    <LoadingState v-if="loading" />
    <p v-else-if="error" class="mb-4 rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-700">{{ error }}</p>

    <div v-else class="grid gap-4 lg:grid-cols-2">
      <div class="rounded-xl border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-100 px-4 py-3 text-sm font-semibold text-slate-700">Institutions List</div>
        <div v-if="institutions.length === 0" class="p-4">
          <EmptyState message="No institutions available." />
        </div>
        <ul v-else class="divide-y divide-slate-100">
          <li v-for="institution in institutions" :key="Number(institution.id)" class="flex items-center justify-between px-4 py-3">
            <div>
              <p class="font-medium text-slate-900">{{ institution.name }}</p>
              <p class="text-sm text-slate-500">{{ institution.city }} • {{ institution.status }}</p>
            </div>
            <div class="flex items-center gap-2">
              <router-link :to="`/institutions/${institution.id}`" class="text-xs font-semibold text-slate-700 hover:underline">Details</router-link>
              <button
                v-if="isAdmin && institution.status !== 'active'"
                class="rounded-md bg-emerald-600 px-3 py-1.5 text-xs font-medium text-white"
                @click="approveInstitution(Number(institution.id))"
              >
                Approve
              </button>
            </div>
          </li>
        </ul>
      </div>

      <div class="rounded-xl border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-100 px-4 py-3 text-sm font-semibold text-slate-700">Map Data</div>
        <div v-if="mapData.length === 0" class="p-4">
          <EmptyState message="No institutions map data found." />
        </div>
        <ul v-else class="divide-y divide-slate-100">
          <li v-for="institution in mapData" :key="Number(institution.id)" class="px-4 py-3">
            <p class="font-medium text-slate-900">{{ institution.name }}</p>
            <p class="text-sm text-slate-500">{{ institution.adress }}, {{ institution.city }}</p>
            <p class="text-xs text-slate-400">{{ institution.opening_time }} - {{ institution.closing_time }}</p>
          </li>
        </ul>
      </div>
    </div>
  </section>
</template>
