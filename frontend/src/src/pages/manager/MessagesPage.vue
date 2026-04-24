<script setup lang="ts">
import { computed, onMounted, onUnmounted, ref } from 'vue'
import { useAuthStore } from '@/stores/auth'
import { smartQueueApi } from '@/services/smartQueueApi'
import { getEcho } from '@/services/realtime'
import { toApiError } from '@/utils/http'
import ChatBubble from '@/chat/ChatBubble.vue'

const authStore = useAuthStore()

const loading = ref(false)
const sending = ref(false)
const error = ref('')
const allMessages = ref<any[]>([])
const selectedUserId = ref<number | null>(null)
const content = ref('')

const me = computed(() => Number(authStore.user?.id || 0))

const conversationUsers = computed(() => {
  const map = new Map<number, any>()

  for (const message of allMessages.value) {
    const senderId = Number(message.sender_id)
    const recipientId = Number(message.recipient_id)
    const isMineSender = senderId === me.value
    const other = isMineSender ? message.recipient : message.sender
    const otherId = isMineSender ? recipientId : senderId

    if (!otherId || !other) {
      continue
    }

    const current = map.get(otherId)
    const currentDate = current ? new Date(current.lastAt).getTime() : 0
    const nextDate = new Date(message.created_at || Date.now()).getTime()

    if (!current || nextDate > currentDate) {
      map.set(otherId, {
        id: otherId,
        name: `${other.first_name || ''} ${other.last_name || ''}`.trim() || other.email || `User ${otherId}`,
        email: other.email || '',
        lastMessage: message.content || '',
        lastAt: message.created_at || new Date().toISOString(),
      })
    }
  }

  return Array.from(map.values()).sort((a, b) => new Date(b.lastAt).getTime() - new Date(a.lastAt).getTime())
})

const activeMessages = computed(() => {
  if (!selectedUserId.value) {
    return []
  }

  return allMessages.value
    .filter((message) => {
      const senderId = Number(message.sender_id)
      const recipientId = Number(message.recipient_id)
      return (
        (senderId === me.value && recipientId === selectedUserId.value) ||
        (senderId === selectedUserId.value && recipientId === me.value)
      )
    })
    .sort((a, b) => new Date(a.created_at || 0).getTime() - new Date(b.created_at || 0).getTime())
})

function formatTime(value: string): string {
  const date = new Date(value)
  if (Number.isNaN(date.getTime())) {
    return ''
  }
  return date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })
}

async function loadMessages(): Promise<void> {
  loading.value = true
  error.value = ''

  try {
    const response = await smartQueueApi.messages({ per_page: 200 })
    allMessages.value = response.data.data.data || []

    if (!selectedUserId.value && conversationUsers.value.length > 0) {
      selectedUserId.value = Number(conversationUsers.value[0].id)
    }
  } catch (err) {
    error.value = toApiError(err).message
  } finally {
    loading.value = false
  }
}

async function sendMessage(): Promise<void> {
  if (!selectedUserId.value || !content.value.trim()) {
    return
  }

  sending.value = true
  error.value = ''

  try {
    const response = await smartQueueApi.sendMessage({
      recipient_id: Number(selectedUserId.value),
      content: content.value.trim(),
    })

    allMessages.value = [...allMessages.value, response.data.data]
    content.value = ''
  } catch (err) {
    error.value = toApiError(err).message
  } finally {
    sending.value = false
  }
}

function setupRealtime(): void {
  const echo = getEcho()
  if (!echo || !me.value) {
    return
  }

  echo
    .private(`conversation.${me.value}`)
    .listen('.message.sent', (event: any) => {
      const incoming = event?.message
      if (!incoming) {
        return
      }

      const incomingId = Number(incoming.id)
      const hasAlready = allMessages.value.some((item) => Number(item.id) === incomingId)
      if (hasAlready) {
        return
      }

      allMessages.value = [...allMessages.value, incoming]

      if (!selectedUserId.value) {
        const otherId = Number(incoming.sender_id) === me.value ? Number(incoming.recipient_id) : Number(incoming.sender_id)
        selectedUserId.value = otherId
      }
    })
}

function teardownRealtime(): void {
  const echo = getEcho()
  if (!echo || !me.value) {
    return
  }

  echo.leave(`private-conversation.${me.value}`)
}

onMounted(async () => {
  await loadMessages()
  setupRealtime()
})

onUnmounted(teardownRealtime)
</script>

<template>
  <div class="space-y-8">
    <!-- Header -->
    <div>
      <p class="text-[11px] font-semibold uppercase tracking-[0.3em] text-stone-400">Communication</p>
      <h1 class="mt-2 text-3xl font-light tracking-tight text-stone-800">Conversations with Citizens</h1>
      <p class="mt-1 text-stone-500">Chat with institution staff and citizens</p>
      <div class="mt-3 h-px w-12 bg-amber-300" />
    </div>

    <!-- Error & Loading -->
    <p v-if="error" class="text-sm text-rose-500">{{ error }}</p>
    <p v-if="loading" class="text-sm text-stone-400">Loading messages...</p>

    <!-- Main Grid -->
    <div v-else class="grid gap-6 lg:grid-cols-[300px,1fr]">
      <!-- Conversations List -->
      <div class="border border-stone-100 rounded-2xl overflow-hidden bg-white/40 backdrop-blur-sm">
        <div class="border-b border-stone-100 px-5 py-3">
          <p class="text-xs font-semibold uppercase tracking-wider text-stone-500">Conversations</p>
        </div>
        <div class="divide-y divide-stone-50 max-h-[500px] overflow-y-auto">
          <button
            v-for="user in conversationUsers"
            :key="Number(user.id)"
            class="w-full px-5 py-3 text-left transition-all duration-200 hover:bg-stone-50/50"
            :class="selectedUserId === Number(user.id) ? 'bg-stone-100' : ''"
            @click="selectedUserId = Number(user.id)"
          >
            <p class="text-sm font-medium text-stone-800">{{ user.name }}</p>
            <p class="mt-0.5 text-xs text-stone-400 line-clamp-1">{{ user.lastMessage }}</p>
          </button>

          <div v-if="conversationUsers.length === 0" class="px-5 py-8 text-center">
            <p class="text-sm text-stone-400">No conversations yet.</p>
          </div>
        </div>
      </div>

      <!-- Chat Area -->
      <div class="border border-stone-100 rounded-2xl bg-white/40 backdrop-blur-sm overflow-hidden flex flex-col h-[550px]">
        <!-- Chat Header -->
        <div class="border-b border-stone-100 px-5 py-3">
          <p class="text-xs font-semibold uppercase tracking-wider text-stone-500">
            {{ selectedUserId ? 'Active Conversation' : 'Select a Conversation' }}
          </p>
        </div>

        <!-- Messages Container -->
        <div class="flex-1 overflow-y-auto p-4 space-y-4">
          <ChatBubble
            v-for="message in activeMessages"
            :key="Number(message.id)"
            :mine="Number(message.sender_id) === me"
            :content="String(message.content || '')"
            :author="Number(message.sender_id) === me ? 'You' : `${message.sender?.first_name || ''} ${message.sender?.last_name || ''}`"
            :time="formatTime(String(message.created_at || ''))"
          />
          <div v-if="activeMessages.length === 0" class="flex items-center justify-center h-full">
            <p class="text-sm text-stone-400">Select a conversation to start chatting.</p>
          </div>
        </div>

        <!-- Input Area -->
        <div class="border-t border-stone-100 p-4">
          <form class="flex gap-3" @submit.prevent="sendMessage">
            <input 
              v-model="content" 
              :disabled="!selectedUserId || sending" 
              class="flex-1 rounded-full border border-stone-200 px-4 py-2 text-sm bg-white/60 focus:outline-none focus:border-stone-300 placeholder:text-stone-400 disabled:opacity-50"
              placeholder="Type your message..."
            />
            <button 
              type="submit" 
              :disabled="!selectedUserId || sending" 
              class="rounded-full bg-stone-800 px-5 py-2 text-sm font-medium text-white transition-all duration-200 hover:bg-stone-700 hover:-translate-y-0.5 disabled:opacity-50 disabled:hover:translate-y-0"
            >
              {{ sending ? 'Sending...' : 'Send' }}
            </button>
          </form>
        </div>
      </div>
    </div>
  </div>
</template>