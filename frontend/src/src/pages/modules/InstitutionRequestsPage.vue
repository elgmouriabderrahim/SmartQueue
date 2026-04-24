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

const loading = ref(false)
const saving = ref(false)
const error = ref('')
const requests = ref<any[]>([])
const rejectReasons = reactive<Record<number, string>>({})

const form = reactive({
  name: '',
  slug: '',
  city: '',
  adress: '',
  description: '',
})

async function loadRequests() {
  loading.value = true
  error.value = ''

  try {
    const response = await smartQueueApi.institutionRequests()
    requests.value = response.data.data.data
  } catch (err) {
    error.value = toApiError(err).message
  } finally {
    loading.value = false
  }
}

async function submitRequest() {
  saving.value = true
  error.value = ''

  try {
    await smartQueueApi.createInstitutionRequest({ ...form })
    await loadRequests()
  } catch (err) {
    error.value = toApiError(err).message
  } finally {
    saving.value = false
  }
}

async function approve(id: number) {
  try {
    await smartQueueApi.approveInstitutionRequest(id)
    await loadRequests()
  } catch (err) {
    error.value = toApiError(err).message
  }
}

async function reject(id: number) {
  const reason = rejectReasons[id]?.trim()
  if (!reason) {
    error.value = 'Provide a rejection reason before rejecting.'
    return
  }

  try {
    await smartQueueApi.rejectInstitutionRequest(id, { reason })
    await loadRequests()
  } catch (err) {
    error.value = toApiError(err).message
  }
}

onMounted(loadRequests)
</script>

<template>
  <section>
    <PageHeader
      title="Institution Requests"
      :subtitle="isAdmin ? 'Review and process citizen institution applications.' : 'Submit your institution request to become a manager.'"
    />

    <div v-if="!isAdmin" class="mb-6 rounded-3xl border border-[#f7c780] bg-white p-5 shadow-[0_12px_30px_-20px_rgba(219,119,6,0.9)]">
      <h2 class="mb-3 text-xs font-semibold uppercase tracking-[0.2em] text-[#cb7a16]">Apply for Institution Creation</h2>
      <form class="grid grid-cols-1 gap-3 md:grid-cols-2" @submit.prevent="submitRequest">
        <input v-model="form.name" placeholder="Institution name" required class="input-shell" />
        <input v-model="form.slug" placeholder="institution-slug" required class="input-shell" />
        <input v-model="form.city" placeholder="City" required class="input-shell" />
        <input v-model="form.adress" placeholder="Address" required class="input-shell" />
        <textarea v-model="form.description" placeholder="Describe institution services" required class="input-shell min-h-28 md:col-span-2" />
        <button :disabled="saving" class="rounded-xl bg-black px-4 py-3 text-sm font-semibold text-white transition hover:opacity-90 md:col-span-2">
          {{ saving ? 'Submitting...' : 'Submit request' }}
        </button>
      </form>
    </div>

    <LoadingState v-if="loading" />
    <p v-else-if="error" class="mb-4 rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-700">{{ error }}</p>
    <EmptyState v-else-if="requests.length === 0" message="No institution requests found." />

    <div v-else class="space-y-4">
      <article
        v-for="item in requests"
        :key="Number(item.id)"
        class="rounded-3xl border border-slate-200 bg-white p-5 shadow-[0_12px_24px_-20px_rgba(15,23,42,0.8)]"
      >
        <div class="flex flex-wrap items-start justify-between gap-3">
          <div>
            <h3 class="text-lg font-semibold text-slate-900">{{ item.name }}</h3>
            <p class="text-sm text-slate-500">{{ item.city }} • {{ item.adress }}</p>
            <p class="mt-1 text-xs uppercase tracking-wide text-slate-400">Status: {{ item.status }}</p>
          </div>
          <span
            class="rounded-full px-3 py-1 text-xs font-semibold"
            :class="item.status === 'approved' ? 'bg-emerald-100 text-emerald-800' : item.status === 'rejected' ? 'bg-red-100 text-red-800' : 'bg-amber-100 text-amber-800'"
          >
            {{ item.status }}
          </span>
        </div>

        <p class="mt-3 text-sm text-slate-700">{{ item.description }}</p>

        <div v-if="isAdmin && item.status === 'pending'" class="mt-4 grid gap-2 md:grid-cols-[1fr,auto,auto]">
          <input v-model="rejectReasons[Number(item.id)]" placeholder="Rejection reason" class="input-shell" />
          <button class="rounded-xl bg-emerald-600 px-4 py-2 text-sm font-semibold text-white" @click="approve(Number(item.id))">Approve</button>
          <button class="rounded-xl bg-red-600 px-4 py-2 text-sm font-semibold text-white" @click="reject(Number(item.id))">Reject</button>
        </div>

        <p v-if="item.rejection_reason" class="mt-3 text-sm text-red-700">Reason: {{ item.rejection_reason }}</p>
      </article>
    </div>
  </section>
</template>
