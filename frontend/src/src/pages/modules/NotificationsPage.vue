<script setup lang="ts">
import { onMounted, ref } from 'vue'
import EmptyState from '@/components/EmptyState.vue'
import LoadingState from '@/components/LoadingState.vue'
import PageHeader from '@/components/PageHeader.vue'
import { smartQueueApi } from '@/services/smartQueueApi'
import { toApiError } from '@/utils/http'

const loading = ref(false)
const error = ref('')
const notifications = ref<any[]>([])

async function loadNotifications() {
  loading.value = true
  error.value = ''

  try {
    const response = await smartQueueApi.notifications()
    notifications.value = response.data.data.data
  } catch (err) {
    error.value = toApiError(err).message
  } finally {
    loading.value = false
  }
}

async function markRead(id: number) {
  error.value = ''

  try {
    await smartQueueApi.markNotificationRead(id)
    await loadNotifications()
  } catch (err) {
    error.value = toApiError(err).message
  }
}

onMounted(loadNotifications)
</script>

<template>
  <section>
    <PageHeader title="Notifications" subtitle="Read updates related to appointments and queue events." />

    <LoadingState v-if="loading" />
    <p v-else-if="error" class="mb-4 rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-700">{{ error }}</p>
    <EmptyState v-else-if="notifications.length === 0" message="No notifications available." />

    <ul v-else class="space-y-3">
      <li
        v-for="item in notifications"
        :key="String(item.id)"
        class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm"
      >
        <div class="flex items-start justify-between gap-3">
          <div>
            <p class="font-medium text-slate-900">{{ item.title }}</p>
            <p class="mt-1 text-sm text-slate-500">{{ item.message }}</p>
            <p class="mt-2 text-xs uppercase tracking-wide text-slate-400">{{ item.type }}</p>
          </div>
          <button
            v-if="!item.is_read"
            class="rounded-md bg-brand-600 px-3 py-1.5 text-xs font-medium text-white"
            @click="markRead(Number(item.id))"
          >
            Mark as read
          </button>
          <span v-else class="text-xs font-medium text-emerald-700">Read</span>
        </div>
      </li>
    </ul>
  </section>
</template>
