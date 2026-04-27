<script setup lang="ts">
import { computed, onMounted, ref, watch, nextTick } from 'vue'
import { useRoute } from 'vue-router'
import EmptyState from '@/components/EmptyState.vue'
import LoadingState from '@/components/LoadingState.vue'
import PageHeader from '@/components/PageHeader.vue'
import { smartQueueApi } from '@/services/smartQueueApi'
import { useAuthStore } from '@/stores/auth'
import { toApiError } from '@/utils/http'

const authStore = useAuthStore()
const route = useRoute()
const isCitizen = computed(() => authStore.role === 'citizen')
const currentUserId = computed(() => Number(authStore.user?.id || 0))

// State
const loading = ref(false)
const sending = ref(false)
const error = ref('')
const allMessages = ref<any[]>([])
const selectedInstId = ref<number | null>(null)
const newMessage = ref('')
const messagesContainer = ref<HTMLElement | null>(null)
const seededInstitution = ref<any | null>(null)
const pendingAppointmentId = ref<number | null>(null)
const pendingRecipientId = ref<number | null>(null)

function resolveInstitutionFromMessage(message: any): any | null {
  if (message?.institution?.id) {
    return message.institution
  }

  const sender = message?.sender
  const recipient = message?.recipient

  if (sender?.role && ['manager', 'employee'].includes(String(sender.role)) && sender?.institution_id) {
    return {
      id: Number(sender.institution_id),
      name: String(sender.institution?.name || 'Institution'),
      slug: String(sender.institution?.slug || ''),
    }
  }

  if (recipient?.role && ['manager', 'employee'].includes(String(recipient.role)) && recipient?.institution_id) {
    return {
      id: Number(recipient.institution_id),
      name: String(recipient.institution?.name || 'Institution'),
      slug: String(recipient.institution?.slug || ''),
    }
  }

  return null
}

const institutions = computed(() => {
  const instMap = new Map<number, any>()

  for (const msg of allMessages.value) {
    const institution = resolveInstitutionFromMessage(msg)

    if (institution && institution.id && !instMap.has(institution.id)) {
      instMap.set(institution.id, {
        id: institution.id,
        name: institution.name,
        slug: institution.slug,
        lastMessage: msg.content,
        lastMessageDate: msg.created_at,
        unread: msg.recipient_id === currentUserId.value && msg.status !== 'read',
      })
    } else if (institution && institution.id) {
      const existing = instMap.get(institution.id)
      if (new Date(msg.created_at) > new Date(existing.lastMessageDate)) {
        existing.lastMessage = msg.content
        existing.lastMessageDate = msg.created_at
      }
      if (msg.recipient_id === currentUserId.value && msg.status !== 'read') {
        existing.unread = true
      }
    }
  }

  if (seededInstitution.value && seededInstitution.value.id && !instMap.has(seededInstitution.value.id)) {
    instMap.set(seededInstitution.value.id, {
      id: seededInstitution.value.id,
      name: seededInstitution.value.name,
      slug: seededInstitution.value.slug,
      lastMessage: 'No messages yet.',
      lastMessageDate: '',
      unread: false,
    })
  }

  return Array.from(instMap.values()).sort((a, b) =>
    new Date(b.lastMessageDate).getTime() - new Date(a.lastMessageDate).getTime()
  )
})

const conversationMessages = computed(() => {
  if (!selectedInstId.value) return []

  return allMessages.value
    .filter((msg) => Number(resolveInstitutionFromMessage(msg)?.id || 0) === selectedInstId.value)
    .sort((a, b) => new Date(a.created_at).getTime() - new Date(b.created_at).getTime())
})

const selectedInstitution = computed(() => {
  const fromList = institutions.value.find((i) => i.id === selectedInstId.value)
  if (fromList) return fromList
  if (seededInstitution.value && seededInstitution.value.id === selectedInstId.value) {
    return seededInstitution.value
  }

  return null
})

const hasConversationShell = computed(() => institutions.value.length > 0 || !!selectedInstId.value)

async function loadMessages() {
  loading.value = true
  error.value = ''
  try {
    const res = await smartQueueApi.messages({ per_page: 200 })
    allMessages.value = res.data.data.data || []
  } catch (err) {
    error.value = toApiError(err).message
  } finally {
    loading.value = false
  }
}

async function hydrateRouteContext() {
  const institutionId = Number(route.query.institution_id || 0)
  const appointmentId = Number(route.query.appointment_id || 0)
  const recipientId = Number(route.query.recipient_id || 0)

  selectedInstId.value = institutionId > 0 ? institutionId : null
  pendingAppointmentId.value = appointmentId > 0 ? appointmentId : null
  pendingRecipientId.value = recipientId > 0 ? recipientId : null

  if (institutionId <= 0) {
    seededInstitution.value = null
    return
  }

  const existing = institutions.value.find((item) => Number(item.id) === institutionId)
  if (existing) {
    seededInstitution.value = existing
    return
  }

  try {
    const response = await smartQueueApi.institution(institutionId)
    const institution = response.data.data
    seededInstitution.value = {
      id: Number(institution.id),
      name: String(institution.name || 'Institution'),
      slug: String(institution.slug || ''),
      lastMessage: 'No messages yet.',
      lastMessageDate: '',
      unread: false,
    }
  } catch {
    seededInstitution.value = null
  }
}

async function sendMessage() {
  if (!newMessage.value.trim() || !selectedInstId.value) return

  sending.value = true
  error.value = ''

  try {
    const payload: {
      content: string
      institution_id?: number
      recipient_id?: number
      appointment_id?: number
    } = {
      content: newMessage.value,
    }

    if (isCitizen.value) {
      payload.institution_id = selectedInstId.value
      if (pendingAppointmentId.value) {
        payload.appointment_id = pendingAppointmentId.value
      }
    } else {
      if (pendingRecipientId.value) {
        payload.recipient_id = pendingRecipientId.value
      } else {
        const lastMsg = conversationMessages.value[conversationMessages.value.length - 1]
        if (lastMsg) {
        const citizenId = lastMsg.sender_id === currentUserId.value ? lastMsg.recipient_id : lastMsg.sender_id
        payload.recipient_id = citizenId
        }
      }
    }

    await smartQueueApi.sendMessage(payload)
    newMessage.value = ''
    pendingAppointmentId.value = null
    pendingRecipientId.value = null
    await loadMessages()

    await nextTick()
    if (messagesContainer.value) {
      messagesContainer.value.scrollTop = messagesContainer.value.scrollHeight
    }
  } catch (err) {
    error.value = toApiError(err).message
  } finally {
    sending.value = false
  }
}

function formatTime(dateStr: string) {
  if (!dateStr) return ''
  const date = new Date(dateStr)
  const now = new Date()
  const isToday = date.toDateString() === now.toDateString()
  
  if (isToday) {
    return date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })
  }
  return date.toLocaleDateString([], { month: 'short', day: 'numeric' })
}

watch(conversationMessages, async () => {
  await nextTick()
  if (messagesContainer.value) {
    messagesContainer.value.scrollTop = messagesContainer.value.scrollHeight
  }
})

onMounted(async () => {
  await loadMessages()
  await hydrateRouteContext()
})

watch(
  () => [route.query.institution_id, route.query.appointment_id],
  async () => {
    await hydrateRouteContext()
  }
)
</script>

<template>
  <div class="h-full flex flex-col bg-gray-50">
    <PageHeader title="Messages" subtitle="Communicate with institutions." />

    <div class="flex-1 flex overflow-hidden p-6 gap-6 min-h-0">
      <LoadingState v-if="loading" class="flex-1" />

      <div v-else-if="error" class="flex-1 text-sm text-rose-500">
        {{ error }}
      </div>

      <div v-else-if="!hasConversationShell" class="flex-1">
        <EmptyState message="No messages yet. Start from an institution page." />
      </div>

      <template v-else>
        <!-- LEFT COLUMN: Institutions List -->
        <div class="w-72 flex-shrink-0 bg-white rounded-xl border shadow-sm flex flex-col">
          <div class="p-4 border-b bg-gray-50">
            <h2 class="font-semibold text-gray-900">Institutions</h2>
            <p class="text-xs text-gray-500 mt-1">{{ institutions.length }} conversation{{ institutions.length !== 1 ? 's' : '' }}</p>
          </div>
          
          <div class="flex-1 overflow-y-auto">
            <button
              v-for="inst in institutions"
              :key="inst.id"
              @click="selectedInstId = inst.id"
              class="w-full text-left p-4 border-b hover:bg-gray-50 transition-all"
              :class="selectedInstId === inst.id ? 'bg-blue-50 border-l-4 border-l-blue-500' : ''"
            >
              <div class="flex items-start justify-between gap-2">
                <div class="flex-1">
                  <p class="font-medium text-gray-900 truncate">{{ inst.name }}</p>
                  <p class="text-xs text-gray-500 truncate mt-0.5">{{ inst.slug || 'Institution' }}</p>
                  <p class="text-xs text-gray-400 truncate mt-1">{{ inst.lastMessage }}</p>
                </div>
                <div class="flex flex-col items-end gap-1">
                  <span v-if="inst.lastMessageDate" class="text-xs text-gray-400">{{ formatTime(inst.lastMessageDate) }}</span>
                  <div v-if="inst.unread" class="w-2 h-2 bg-blue-500 rounded-full"></div>
                </div>
              </div>
            </button>
          </div>
        </div>

        <!-- RIGHT COLUMN: Conversation -->
        <div class="flex-1 flex flex-col bg-white rounded-xl border shadow-sm">
          <!-- Header -->
          <div class="p-4 border-b bg-gray-50">
            <div v-if="selectedInstitution">
              <h3 class="font-semibold text-gray-900">{{ selectedInstitution.name }}</h3>
              <p class="text-xs text-gray-500 mt-0.5">{{ selectedInstitution.slug || 'Institution' }}</p>
            </div>
            <p v-else class="text-gray-500">Select an institution to start messaging</p>
          </div>

          <!-- Messages Area -->
          <div class="flex-1 overflow-y-auto p-4 space-y-3" ref="messagesContainer">
            <div v-if="selectedInstitution && conversationMessages.length === 0" class="text-center text-gray-400 py-10">
              No messages yet. Send your first message.
            </div>
            
            <div
              v-for="msg in conversationMessages"
              :key="msg.id"
              class="flex"
              :class="msg.sender_id === currentUserId ? 'justify-end' : 'justify-start'"
            >
              <div
                class="max-w-[70%] rounded-lg px-4 py-2"
                :class="msg.sender_id === currentUserId
                  ? 'bg-blue-600 text-white'
                  : 'bg-gray-100 text-gray-900'"
              >
                <p class="text-sm break-words">{{ msg.content }}</p>
                <p class="text-xs opacity-70 mt-1 text-right">
                  {{ formatTime(msg.created_at) }}
                </p>
              </div>
            </div>
          </div>

          <!-- Message Input -->
          <div v-if="selectedInstitution" class="p-4 border-t">
            <form @submit.prevent="sendMessage" class="flex gap-2">
              <input
                v-model="newMessage"
                type="text"
                placeholder="Type a message..."
                class="flex-1 rounded-full border border-gray-300 px-4 py-2 text-sm focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500"
              />
              <button
                type="submit"
                :disabled="sending || !newMessage.trim()"
                class="px-5 py-2 bg-blue-600 text-white rounded-full text-sm font-medium hover:bg-blue-700 disabled:opacity-50 transition-colors"
              >
                {{ sending ? 'Sending...' : 'Send' }}
              </button>
            </form>
            <p v-if="pendingAppointmentId" class="mt-2 text-xs text-gray-500">
              This message will reference appointment #{{ pendingAppointmentId }}.
            </p>
          </div>
          <div v-else class="p-4 border-t bg-gray-50 text-center text-gray-400 text-sm">
            Select an institution to start messaging
          </div>
        </div>
      </template>
    </div>
  </div>
</template>