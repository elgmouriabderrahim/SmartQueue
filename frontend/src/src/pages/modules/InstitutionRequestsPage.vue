<script setup lang="ts">
import { computed, onMounted, reactive, ref } from 'vue'
import PageHeader from '@/components/PageHeader.vue'
import { smartQueueApi } from '@/services/smartQueueApi'
import { useAuthStore } from '@/stores/auth'
import { toApiError } from '@/utils/http'

const authStore = useAuthStore()
const isAdmin = computed(() => authStore.userRole === 'admin')

const loading = ref(false)
const submitting = ref(false)
const cancelling = ref(false)
const approvingId = ref<number | null>(null)
const rejectingId = ref<number | null>(null)
const error = ref('')
const requests = ref<any[]>([])
const rejectReason = ref('')
const rejectTarget = ref<any | null>(null)

const form = reactive({
  name: '',
  slug: '',
  city: '',
  adress: '',
  description: '',
})

const canResubmit = computed(() => {
  const lastRequest = requests.value.find((r) => r.status === 'rejected' || r.status === 'cancelled')
  return lastRequest && !requests.value.find((r) => r.status === 'pending')
})

const hasPendingRequest = computed(() => requests.value.some((r) => r.status === 'pending'))
const pendingRequest = computed(() => requests.value.find((r) => r.status === 'pending'))
const pendingRequests = computed(() => requests.value.filter((r) => r.status === 'pending'))

async function loadRequests() {
  loading.value = true
  error.value = ''

  try {
    const response = await smartQueueApi.institutionRequests()
    const data = response.data.data as { data: any[] }
    requests.value = data.data || []
  } catch (err) {
    error.value = toApiError(err).message
  } finally {
    loading.value = false
  }
}

async function submitRequest() {
  submitting.value = true
  error.value = ''

  try {
    await smartQueueApi.createInstitutionRequest(form)
    form.name = ''
    form.slug = ''
    form.city = ''
    form.adress = ''
    form.description = ''
    await loadRequests()
  } catch (err) {
    error.value = toApiError(err).message
  } finally {
    submitting.value = false
  }
}

async function cancelRequest() {
  if (!pendingRequest.value) return

  cancelling.value = true
  error.value = ''

  try {
    await smartQueueApi.cancelInstitutionRequest(pendingRequest.value.id)
    await loadRequests()
  } catch (err) {
    error.value = toApiError(err).message
  } finally {
    cancelling.value = false
  }
}

async function approveRequest(requestId: number) {
  approvingId.value = requestId
  error.value = ''

  try {
    await smartQueueApi.approveInstitutionRequest(requestId)
    await loadRequests()
  } catch (err) {
    error.value = toApiError(err).message
  } finally {
    approvingId.value = null
  }
}

function openRejectDialog(item: any) {
  rejectTarget.value = item
  rejectReason.value = ''
}

function closeRejectDialog() {
  rejectTarget.value = null
  rejectReason.value = ''
}

async function rejectRequest() {
  if (!rejectTarget.value) return

  rejectingId.value = rejectTarget.value.id
  error.value = ''

  try {
    await smartQueueApi.rejectInstitutionRequest(rejectTarget.value.id, { reason: rejectReason.value.trim() })
    closeRejectDialog()
    await loadRequests()
  } catch (err) {
    error.value = toApiError(err).message
  } finally {
    rejectingId.value = null
  }
}

function statusBadgeClass(status: string) {
  if (status === 'pending') return 'bg-amber-100 text-amber-800 border-amber-300'
  if (status === 'approved') return 'bg-emerald-100 text-emerald-800 border-emerald-300'
  if (status === 'rejected') return 'bg-rose-100 text-rose-800 border-rose-300'
  if (status === 'cancelled') return 'bg-slate-100 text-slate-700 border-slate-300'
  return 'bg-slate-100 text-slate-700 border-slate-300'
}

onMounted(loadRequests)
</script>

<template>
  <section>
    <PageHeader :title="isAdmin ? 'Institution Requests' : 'Institution Request'" :subtitle="isAdmin ? 'Review citizen requests and approve or reject them.' : 'Submit a request to create an institution.'" />

    <p v-if="error" class="mb-4 rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-700">{{ error }}</p>

    <div v-if="isAdmin && loading" class="rounded-2xl border border-slate-200 bg-white p-5 text-center text-slate-500">Loading...</div>

    <div v-else-if="isAdmin" class="space-y-4">
      <div class="rounded-2xl border border-slate-200 bg-white p-4">
        <p class="text-sm text-slate-600">
          Pending: <span class="font-semibold text-slate-900">{{ pendingRequests.length }}</span>
          <span class="mx-2 text-slate-300">|</span>
          Total: <span class="font-semibold text-slate-900">{{ requests.length }}</span>
        </p>
      </div>

      <div v-if="requests.length === 0" class="rounded-2xl border border-slate-200 bg-white p-5 text-center text-slate-500">
        No institution requests yet.
      </div>

      <div v-for="item in requests" :key="item.id" class="rounded-2xl border border-slate-200 bg-white p-5">
        <div class="flex flex-wrap items-start justify-between gap-3">
          <div>
            <h3 class="text-lg font-semibold text-slate-900">{{ item.name }}</h3>
            <p class="mt-1 text-sm text-slate-600">{{ item.city }} • {{ item.adress }}</p>
            <p class="mt-1 text-sm text-slate-500">Slug: {{ item.slug }}</p>
          </div>
          <span class="rounded-full border px-3 py-1 text-xs font-semibold" :class="statusBadgeClass(item.status)">
            {{ item.status }}
          </span>
        </div>

        <p class="mt-3 text-sm text-slate-700">{{ item.description }}</p>

        <p class="mt-3 text-sm text-slate-600">
          Requested by:
          <span class="font-medium text-slate-800">
            {{ item.user?.first_name }} {{ item.user?.last_name }}
          </span>
          <span v-if="item.user?.email">({{ item.user.email }})</span>
        </p>

        <p v-if="item.rejection_reason" class="mt-2 rounded-lg border border-rose-200 bg-rose-50 px-3 py-2 text-sm text-rose-800">
          Rejection reason: {{ item.rejection_reason }}
        </p>

        <div v-if="item.status === 'pending'" class="mt-4 flex flex-wrap gap-2">
          <button
            :disabled="approvingId === item.id || rejectingId === item.id"
            class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-700 disabled:opacity-50"
            @click="approveRequest(item.id)"
          >
            {{ approvingId === item.id ? 'Approving...' : 'Approve' }}
          </button>

          <button
            :disabled="approvingId === item.id || rejectingId === item.id"
            class="rounded-lg bg-rose-600 px-4 py-2 text-sm font-semibold text-white hover:bg-rose-700 disabled:opacity-50"
            @click="openRejectDialog(item)"
          >
            Reject
          </button>
        </div>
      </div>
    </div>

    <div v-else-if="loading" class="rounded-2xl border border-slate-200 bg-white p-5 text-center text-slate-500">Loading...</div>

    <div v-else-if="hasPendingRequest" class="rounded-2xl border border-amber-200 bg-amber-50 p-5">
      <div class="flex items-start justify-between gap-4">
        <div>
          <h3 class="font-semibold text-amber-900">{{ pendingRequest.name }}</h3>
          <p class="mt-1 text-sm text-amber-800">{{ pendingRequest.city }} • {{ pendingRequest.adress }}</p>
          <p class="mt-2 text-sm text-amber-700">{{ pendingRequest.description }}</p>
          <p class="mt-3 inline-block rounded-full bg-amber-200 px-3 py-1 text-xs font-semibold text-amber-900">Pending</p>
        </div>
        <button
          :disabled="cancelling"
          class="whitespace-nowrap rounded-lg bg-rose-600 px-4 py-2 text-sm font-semibold text-white hover:bg-rose-700 disabled:opacity-50"
          @click="cancelRequest"
        >
          {{ cancelling ? 'Cancelling...' : 'Cancel' }}
        </button>
      </div>
    </div>

    <form v-else class="rounded-2xl border border-slate-200 bg-white p-5" @submit.prevent="submitRequest">
      <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
        <input v-model="form.name" type="text" placeholder="Institution name" required class="input-shell" />
        <input v-model="form.slug" type="text" placeholder="institution-slug" required class="input-shell" />
        <input v-model="form.city" type="text" placeholder="City" required class="input-shell" />
        <input v-model="form.adress" type="text" placeholder="Address" required class="input-shell" />
        <textarea v-model="form.description" placeholder="Description" required class="input-shell md:col-span-2" />
      </div>
      <button :disabled="submitting" class="mt-4 rounded-lg bg-black px-4 py-2 text-sm font-semibold text-white hover:opacity-90 disabled:opacity-50">
        {{ submitting ? 'Submitting...' : 'Submit request' }}
      </button>
    </form>

    <div v-if="rejectTarget" class="fixed inset-0 z-40 flex items-center justify-center bg-black/40 p-4" @click.self="closeRejectDialog">
      <div class="w-full max-w-lg rounded-2xl bg-white p-5 shadow-xl">
        <h3 class="text-lg font-semibold text-slate-900">Reject request</h3>
        <p class="mt-1 text-sm text-slate-600">
          Provide a reason for rejecting <span class="font-medium">{{ rejectTarget.name }}</span>.
        </p>

        <textarea
          v-model="rejectReason"
          class="input-shell mt-4 min-h-28 w-full"
          placeholder="Reason for rejection"
          maxlength="1000"
        />

        <div class="mt-4 flex justify-end gap-2">
          <button class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50" @click="closeRejectDialog">
            Cancel
          </button>
          <button
            :disabled="rejectReason.trim().length === 0 || rejectingId === rejectTarget.id"
            class="rounded-lg bg-rose-600 px-4 py-2 text-sm font-semibold text-white hover:bg-rose-700 disabled:opacity-50"
            @click="rejectRequest"
          >
            {{ rejectingId === rejectTarget.id ? 'Rejecting...' : 'Confirm reject' }}
          </button>
        </div>
      </div>
    </div>
  </section>
</template>
