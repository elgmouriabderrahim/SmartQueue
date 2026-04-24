<script setup lang="ts">
import { computed, onMounted, reactive, ref } from 'vue'
import EmptyState from '@/components/EmptyState.vue'
import LoadingState from '@/components/LoadingState.vue'
import PageHeader from '@/components/PageHeader.vue'
import { smartQueueApi } from '@/services/smartQueueApi'
import { useAuthStore } from '@/stores/auth'
import { toApiError } from '@/utils/http'

const authStore = useAuthStore()
const canUpdateStatus = computed(() => authStore.role === 'institution')
const isCitizen = computed(() => authStore.role === 'citizen')

const loading = ref(false)
const sending = ref(false)
const error = ref('')
const messages = ref<any[]>([])
const institutions = ref<any[]>([])
const detailLoading = ref(false)
const detailError = ref('')
const messageDetail = ref<any | null>(null)

const form = reactive({
  institution_id: 0,
  recipient_id: 0,
  content: '',
  appointment_id: '',
})

const statusForm = reactive<Record<number, string>>({})

async function loadData() {
  loading.value = true
  error.value = ''

  try {
    const [messagesResponse, institutionsResponse] = await Promise.all([
      smartQueueApi.messages(),
      isCitizen.value ? smartQueueApi.institutions({ per_page: 100 }) : Promise.resolve(null),
    ])

    messages.value = messagesResponse.data.data.data
    institutions.value = institutionsResponse?.data.data.data || []

    for (const message of messages.value) {
      statusForm[Number(message.id)] = String(message.status || 'new')
    }
  } catch (err) {
    error.value = toApiError(err).message
  } finally {
    loading.value = false
  }
}

async function sendMessage() {
  sending.value = true
  error.value = ''

  try {
    const payload: {
      recipient_id?: number
      institution_id?: number
      content: string
      appointment_id?: number | null
    } = {
      content: form.content,
      appointment_id: form.appointment_id ? Number(form.appointment_id) : null,
    }

    if (isCitizen.value) {
      payload.institution_id = Number(form.institution_id)
    } else {
      payload.recipient_id = Number(form.recipient_id)
    }

    await smartQueueApi.sendMessage({
      ...payload,
    })

    form.institution_id = 0
    form.recipient_id = 0
    form.content = ''
    form.appointment_id = ''

    await loadData()
  } catch (err) {
    error.value = toApiError(err).message
  } finally {
    sending.value = false
  }
}

async function updateStatus(id: number) {
  try {
    await smartQueueApi.updateMessage(id, { status: statusForm[id] || 'new' })
    await loadData()
  } catch (err) {
    error.value = toApiError(err).message
  }
}

async function deleteMessage(id: number) {
  try {
    await smartQueueApi.deleteMessage(id)
    await loadData()
  } catch (err) {
    error.value = toApiError(err).message
  }
}

async function loadMessageDetail(id: number) {
  detailLoading.value = true
  detailError.value = ''

  try {
    const response = await smartQueueApi.message(id)
    messageDetail.value = response.data.data
  } catch (err) {
    detailError.value = toApiError(err).message
  } finally {
    detailLoading.value = false
  }
}

function pickReplyRecipient(message: any): void {
  const me = Number(authStore.user?.id || 0)
  const senderId = Number(message.sender_id || 0)
  const recipientId = Number(message.recipient_id || 0)
  form.recipient_id = senderId === me ? recipientId : senderId
}

onMounted(loadData)
</script>

<template>
  <div class="space-y-8">
    <PageHeader title="Messages" subtitle="Communicate with users and institutions." />

    <!-- Send Message Form -->
    <div class="rounded-2xl bg-white/40 backdrop-blur-sm border border-stone-100 p-5">
      <h2 class="text-xs font-semibold uppercase tracking-wider text-stone-500">Send Message</h2>
      <p class="mt-1 text-sm text-stone-400">
        {{ isCitizen ? 'Choose an institution and send your message.' : 'Reply to citizens or send a direct message.' }}
      </p>
      
      <form class="mt-4 grid grid-cols-1 gap-3 md:grid-cols-3" @submit.prevent="sendMessage">
        <select
          v-if="isCitizen"
          v-model.number="form.institution_id"
          required
          class="rounded-full border border-stone-200 px-4 py-2 text-sm bg-white/60 focus:outline-none focus:border-stone-300"
        >
          <option :value="0" disabled>Select institution</option>
          <option v-for="institution in institutions" :key="Number(institution.id)" :value="Number(institution.id)">
            {{ institution.name }}
          </option>
        </select>
        <input
          v-else
          v-model.number="form.recipient_id"
          type="number"
          min="1"
          required
          placeholder="Recipient user ID"
          class="rounded-full border border-stone-200 px-4 py-2 text-sm bg-white/60 focus:outline-none focus:border-stone-300 placeholder:text-stone-400"
        />
        <input 
          v-model="form.appointment_id" 
          placeholder="Appointment ID (optional)" 
          class="rounded-full border border-stone-200 px-4 py-2 text-sm bg-white/60 focus:outline-none focus:border-stone-300 placeholder:text-stone-400"
        />
        <input 
          v-model="form.content" 
          placeholder="Message content" 
          required 
          class="rounded-full border border-stone-200 px-4 py-2 text-sm bg-white/60 focus:outline-none focus:border-stone-300 placeholder:text-stone-400"
        />
        <button 
          :disabled="sending" 
          class="rounded-full bg-stone-800 px-5 py-2 text-sm font-medium text-white transition-all duration-200 hover:bg-stone-700 hover:-translate-y-0.5 disabled:opacity-50 md:col-span-3"
        >
          {{ sending ? 'Sending...' : 'Send Message' }}
        </button>
      </form>
    </div>

    <!-- Loading & Error -->
    <LoadingState v-if="loading" />
    <p v-else-if="error" class="text-sm text-rose-500">{{ error }}</p>
    <EmptyState v-else-if="messages.length === 0" message="No messages yet." />

    <!-- Main Grid: Messages + Detail -->
    <div v-else class="grid gap-6 lg:grid-cols-[minmax(0,1fr),320px]">
      <!-- Messages List -->
      <div class="space-y-3">
        <div v-for="message in messages" :key="Number(message.id)" class="rounded-xl border border-stone-100 bg-white/30 p-4 transition-all duration-200 hover:border-stone-200">
          <div class="flex flex-wrap items-center justify-between gap-2">
            <p class="text-xs text-stone-500">
              From: <span class="font-medium text-stone-700">{{ message.sender?.email }}</span> 
              • To: <span class="font-medium text-stone-700">{{ message.recipient?.email }}</span>
            </p>
          </div>
          <p class="mt-2 text-sm text-stone-700 leading-relaxed">{{ message.content }}</p>
          <div class="mt-3 flex flex-wrap items-center gap-3">
            <button 
              class="text-xs text-stone-400 hover:text-stone-600 transition-colors" 
              @click="loadMessageDetail(Number(message.id))"
            >
              View
            </button>
            <button
              v-if="canUpdateStatus"
              class="text-xs text-sky-500 hover:text-sky-700 transition-colors"
              @click="pickReplyRecipient(message)"
            >
              Reply
            </button>
            <select 
              v-if="canUpdateStatus" 
              v-model="statusForm[Number(message.id)]" 
              class="rounded-full border border-stone-200 px-3 py-1 text-xs bg-white/60 focus:outline-none focus:border-stone-300"
            >
              <option value="new">New</option>
              <option value="read">Read</option>
              <option value="in_progress">In Progress</option>
              <option value="resolved">Resolved</option>
              <option value="closed">Closed</option>
            </select>
            <button 
              v-if="canUpdateStatus" 
              class="rounded-full bg-stone-800 px-3 py-1 text-xs font-medium text-white transition-all duration-200 hover:bg-stone-700 hover:-translate-y-0.5" 
              @click="updateStatus(Number(message.id))"
            >
              Update
            </button>
            <button 
              class="text-xs text-rose-400 hover:text-rose-600 transition-colors" 
              @click="deleteMessage(Number(message.id))"
            >
              Delete
            </button>
          </div>
        </div>
      </div>

      <!-- Detail Panel -->
      <div class="border border-stone-100 rounded-2xl bg-white/40 backdrop-blur-sm p-5">
        <p class="text-xs font-semibold uppercase tracking-wider text-stone-500 mb-4">Message Detail</p>
        
        <p v-if="detailLoading" class="text-sm text-stone-400">Loading detail...</p>
        <p v-else-if="detailError" class="text-sm text-rose-500">{{ detailError }}</p>
        <p v-else-if="!messageDetail" class="text-sm text-stone-400">Select a message and click View.</p>
        
        <div v-else class="space-y-3">
          <div class="border-b border-stone-100 pb-2">
            <p class="text-[10px] uppercase tracking-wider text-stone-400">ID</p>
            <p class="text-sm text-stone-600">{{ messageDetail.id }}</p>
          </div>
          <div class="border-b border-stone-100 pb-2">
            <p class="text-[10px] uppercase tracking-wider text-stone-400">From</p>
            <p class="text-sm text-stone-700">{{ messageDetail.sender?.email || messageDetail.sender_id }}</p>
          </div>
          <div class="border-b border-stone-100 pb-2">
            <p class="text-[10px] uppercase tracking-wider text-stone-400">To</p>
            <p class="text-sm text-stone-700">{{ messageDetail.recipient?.email || messageDetail.recipient_id }}</p>
          </div>
          <div class="border-b border-stone-100 pb-2">
            <p class="text-[10px] uppercase tracking-wider text-stone-400">Status</p>
            <p class="text-sm text-stone-600">{{ messageDetail.status || '-' }}</p>
          </div>
          <div class="border-b border-stone-100 pb-2">
            <p class="text-[10px] uppercase tracking-wider text-stone-400">Appointment</p>
            <p class="text-sm text-stone-600">{{ messageDetail.appointment_id || '-' }}</p>
          </div>
          <div>
            <p class="text-[10px] uppercase tracking-wider text-stone-400">Content</p>
            <p class="text-sm text-stone-600 leading-relaxed">{{ messageDetail.content }}</p>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>