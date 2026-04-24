<script setup lang="ts">
import { onMounted, reactive, ref } from 'vue'
import { smartQueueApi } from '@/services/smartQueueApi'
import { toApiError } from '@/utils/http'
import BaseBadge from '@/components/ui/BaseBadge.vue'
import BaseButton from '@/components/ui/BaseButton.vue'
import BaseCard from '@/components/ui/BaseCard.vue'
import BaseModal from '@/components/ui/BaseModal.vue'
import BaseTable from '@/components/ui/BaseTable.vue'

const loading = ref(false)
const saving = ref(false)
const error = ref('')
const entries = ref<any[]>([])
const queues = ref<any[]>([])
const appointments = ref<any[]>([])
const editId = ref<number | null>(null)
const showForm = ref(false)
const detailLoading = ref(false)
const detailError = ref('')
const entryDetail = ref<any | null>(null)

const form = reactive({
  queue_id: 0,
  appointment_id: 0,
  position: 1,
  estimated_wait_time: 0,
  status: 'waiting',
})

function resetForm(): void {
  form.queue_id = 0
  form.appointment_id = 0
  form.position = 1
  form.estimated_wait_time = 0
  form.status = 'waiting'
  editId.value = null
}

function openCreate(): void {
  resetForm()
  showForm.value = true
}

function openEdit(entry: any): void {
  editId.value = Number(entry.id)
  form.queue_id = Number(entry.queue_id)
  form.appointment_id = Number(entry.appointment_id)
  form.position = Number(entry.position || 1)
  form.estimated_wait_time = Number(entry.estimated_wait_time || 0)
  form.status = String(entry.status || 'waiting')
  showForm.value = true
}

async function loadData(): Promise<void> {
  loading.value = true
  error.value = ''

  try {
    const [entriesRes, queuesRes, appointmentsRes] = await Promise.all([
      smartQueueApi.queueEntries({ per_page: 100 }),
      smartQueueApi.queues({ per_page: 100 }),
      smartQueueApi.appointments({ per_page: 100 }),
    ])

    entries.value = entriesRes.data.data.data || []
    queues.value = queuesRes.data.data.data || []
    appointments.value = appointmentsRes.data.data.data || []
  } catch (err) {
    error.value = toApiError(err).message
  } finally {
    loading.value = false
  }
}

async function saveEntry(): Promise<void> {
  saving.value = true
  error.value = ''

  try {
    const payload = {
      queue_id: Number(form.queue_id),
      appointment_id: Number(form.appointment_id),
      position: Number(form.position),
      estimated_wait_time: Number(form.estimated_wait_time),
      status: form.status,
    }

    if (editId.value) {
      await smartQueueApi.updateQueueEntry(editId.value, payload)
    } else {
      await smartQueueApi.createQueueEntry(payload)
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

async function removeEntry(id: number): Promise<void> {
  error.value = ''

  try {
    await smartQueueApi.deleteQueueEntry(id)
    await loadData()
  } catch (err) {
    error.value = toApiError(err).message
  }
}

async function loadEntryDetail(id: number): Promise<void> {
  detailLoading.value = true
  detailError.value = ''

  try {
    const response = await smartQueueApi.queueEntry(id)
    entryDetail.value = response.data.data
  } catch (err) {
    detailError.value = toApiError(err).message
  } finally {
    detailLoading.value = false
  }
}

onMounted(loadData)
</script>

<template>
  <section class="space-y-5">
    <header class="flex flex-wrap items-center justify-between gap-3">
      <div>
        <p class="text-[11px] font-semibold uppercase tracking-[0.2em] text-orange-600">Queue Entries</p>
        <h1 class="text-2xl font-semibold tracking-tight text-slate-900">Queue Flow Control</h1>
      </div>
      <BaseButton @click="openCreate">Create Entry</BaseButton>
    </header>

    <p v-if="error" class="rounded-xl border border-red-200 bg-red-50 p-3 text-sm text-red-700">{{ error }}</p>
    <p v-else-if="loading" class="text-sm text-slate-500">Loading queue entries...</p>

    <BaseCard v-else-if="entries.length === 0" title="No Queue Entries" subtitle="Operations">
      <p class="text-sm text-slate-600">No queue entries available.</p>
    </BaseCard>

    <div v-else class="grid gap-4 xl:grid-cols-[minmax(0,1fr),320px]">
      <BaseTable :headers="['Queue', 'Appointment', 'Position', 'ETA', 'Status', 'Actions']">
        <tr v-for="entry in entries" :key="Number(entry.id)" class="border-t border-slate-100">
          <td class="px-4 py-3 text-slate-700">{{ entry.queue_id }}</td>
          <td class="px-4 py-3 text-slate-700">{{ entry.appointment_id }}</td>
          <td class="px-4 py-3 font-medium text-slate-900">{{ entry.position }}</td>
          <td class="px-4 py-3 text-slate-700">{{ entry.estimated_wait_time }} min</td>
          <td class="px-4 py-3"><BaseBadge tone="warning">{{ entry.status }}</BaseBadge></td>
          <td class="px-4 py-3">
            <div class="flex items-center gap-2">
              <BaseButton variant="ghost" @click="loadEntryDetail(Number(entry.id))">View</BaseButton>
              <BaseButton variant="secondary" @click="openEdit(entry)">Edit</BaseButton>
              <BaseButton variant="danger" @click="removeEntry(Number(entry.id))">Delete</BaseButton>
            </div>
          </td>
        </tr>
      </BaseTable>

      <BaseCard title="Entry Detail" subtitle="queue-entries.show">
        <p v-if="detailLoading" class="text-sm text-slate-500">Loading queue entry detail...</p>
        <p v-else-if="detailError" class="text-sm text-red-600">{{ detailError }}</p>
        <p v-else-if="!entryDetail" class="text-sm text-slate-500">Select an entry and click View.</p>
        <div v-else class="space-y-1 text-sm text-slate-700">
          <p><span class="font-medium">ID:</span> {{ entryDetail.id }}</p>
          <p><span class="font-medium">Queue:</span> {{ entryDetail.queue_id }}</p>
          <p><span class="font-medium">Appointment:</span> {{ entryDetail.appointment_id }}</p>
          <p><span class="font-medium">Position:</span> {{ entryDetail.position }}</p>
          <p><span class="font-medium">ETA:</span> {{ entryDetail.estimated_wait_time }} min</p>
          <p><span class="font-medium">Status:</span> {{ entryDetail.status }}</p>
        </div>
      </BaseCard>
    </div>

    <BaseModal :open="showForm" :title="editId ? 'Update Queue Entry' : 'Create Queue Entry'" @close="showForm = false">
      <form class="space-y-3" @submit.prevent="saveEntry">
        <select v-model.number="form.queue_id" required class="input-shell">
          <option :value="0" disabled>Select queue</option>
          <option v-for="queue in queues" :key="Number(queue.id)" :value="Number(queue.id)">
            Queue #{{ queue.id }} (service {{ queue.service_id }})
          </option>
        </select>

        <select v-model.number="form.appointment_id" required class="input-shell">
          <option :value="0" disabled>Select appointment</option>
          <option v-for="appointment in appointments" :key="Number(appointment.id)" :value="Number(appointment.id)">
            {{ appointment.reference_code || `Appointment #${appointment.id}` }}
          </option>
        </select>

        <input v-model.number="form.position" type="number" min="1" required class="input-shell" placeholder="Position" />
        <input v-model.number="form.estimated_wait_time" type="number" min="0" required class="input-shell" placeholder="Estimated wait time" />

        <select v-model="form.status" class="input-shell">
          <option value="waiting">waiting</option>
          <option value="called">called</option>
          <option value="serving">serving</option>
          <option value="served">served</option>
          <option value="skipped">skipped</option>
          <option value="transferred">transferred</option>
        </select>

        <div class="flex justify-end gap-2">
          <BaseButton variant="secondary" @click="showForm = false">Cancel</BaseButton>
          <BaseButton type="submit" :disabled="saving">{{ saving ? 'Saving...' : 'Save' }}</BaseButton>
        </div>
      </form>
    </BaseModal>
  </section>
</template>
