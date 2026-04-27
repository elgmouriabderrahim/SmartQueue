<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import LoadingState from '@/components/LoadingState.vue'
import BaseModal from '@/components/ui/BaseModal.vue'
import { smartQueueApi } from '@/services/smartQueueApi'
import { useAuthStore } from '@/stores/auth'
import { toApiError } from '@/utils/http'

const route = useRoute()
const router = useRouter()
const authStore = useAuthStore()
const loading = ref(false)
const error = ref('')
const service = ref<any | null>(null)
const ratings = ref<any[]>([])
const serviceCounters = ref<any[]>([])
const bookingDate = ref('')
const selectedCounterId = ref(0)
const bookingLoading = ref(false)
const bookingError = ref('')
const messageError = ref('')
const bookingOpen = ref(false)
const ratingSubmitting = ref(false)
const ratingError = ref('')

const isCitizen = computed(() => authStore.userRole === 'citizen')
const isLoggedIn = computed(() => authStore.isAuthenticated)

async function loadService() {
  loading.value = true
  error.value = ''

  try {
    const response = await smartQueueApi.service(Number(route.params.id))
    service.value = response.data.data
    serviceCounters.value = Array.isArray(service.value?.service_counters)
      ? service.value.service_counters
      : Array.isArray(service.value?.counters)
        ? service.value.counters
        : []

    const ratingsResponse = await smartQueueApi.ratings({ per_page: 100 })
    ratings.value = (ratingsResponse.data.data.data || []).filter((item: any) => Number(item.service_id) === Number(service.value?.id))
  } catch (err) {
    error.value = toApiError(err).message
  } finally {
    loading.value = false
  }
}

function averageRating(): number {
  const count = ratings.value.length
  if (count === 0) return 0
  return ratings.value.reduce((sum: number, item: any) => sum + Number(item.score || 0), 0) / count
}

const myRating = computed(() => {
  const me = Number(authStore.user?.id || 0)
  if (!me) return null
  return ratings.value.find((item: any) => Number(item.user_id) === me) || null
})

function goBack(): void {
  if (window.history.length > 1) {
    router.back()
    return
  }
  router.push('/services')
}

function formattedWorkingDays(days: unknown): string {
  if (!Array.isArray(days) || days.length === 0) return 'Not set'
  return days.map((day) => String(day)).join(', ')
}

function formatTime(value: unknown): string {
  if (!value) return 'Not set'
  return String(value).slice(0, 5)
}

function openBooking(): void {
  bookingError.value = ''
  bookingOpen.value = true
}

function closeBooking(): void {
  bookingOpen.value = false
}

async function bookAppointment(): Promise<void> {
  bookingError.value = ''

  if (!isLoggedIn.value) {
    const serviceId = Number(service.value?.id || route.params.id || 0)
    const target = `/services/${serviceId}?openBooking=1`
    router.push({ path: '/auth/login', query: { redirect: target } })
    return
  }

  if (!isCitizen.value) {
    bookingError.value = 'Only citizens can book appointments.'
    return
  }

  if (!service.value?.id || !bookingDate.value) {
    bookingError.value = 'Please select date and time.'
    return
  }

  bookingLoading.value = true

  try {
    await smartQueueApi.createAppointment({
      service_id: Number(service.value.id),
      service_counter_id: selectedCounterId.value ? Number(selectedCounterId.value) : null,
      appointment_date: bookingDate.value,
    })

    closeBooking()
    router.push('/app/citizen/appointments')
  } catch (err) {
    bookingError.value = toApiError(err).message
  } finally {
    bookingLoading.value = false
  }
}

function startMessage(): void {
  messageError.value = ''
  const institutionId = Number(service.value?.institution_id || 0)

  if (!institutionId) {
    messageError.value = 'Institution is not available for this service.'
    return
  }

  const target = `/app/citizen/messages?institution_id=${institutionId}`

  if (!isLoggedIn.value) {
    router.push({ path: '/auth/login', query: { redirect: target } })
    return
  }

  if (!isCitizen.value) {
    messageError.value = 'Only citizens can send messages from this page.'
    return
  }

  router.push(target)
}

async function rateService(score: number): Promise<void> {
  ratingError.value = ''

  if (!isLoggedIn.value) {
    const serviceId = Number(service.value?.id || route.params.id || 0)
    const target = `/services/${serviceId}`
    router.push({ path: '/auth/login', query: { redirect: target } })
    return
  }

  if (!isCitizen.value || !service.value?.id) {
    ratingError.value = 'Only citizens can rate services.'
    return
  }

  ratingSubmitting.value = true
  try {
    await smartQueueApi.createRating({
      service_id: Number(service.value.id),
      score,
    })
    await loadService()
  } catch (err) {
    ratingError.value = toApiError(err).message
  } finally {
    ratingSubmitting.value = false
  }
}

onMounted(async () => {
  await loadService()

  if (route.query.openBooking === '1' && isLoggedIn.value && isCitizen.value) {
    openBooking()
  }
})
</script>

<template>
  <div class="space-y-8">
    <!-- Back Button - Minimal -->
    <button
      type="button"
      class="group inline-flex items-center gap-2 text-sm font-medium text-stone-400 transition-all duration-200 hover:text-stone-600"
      @click="goBack"
    >
      <svg class="h-4 w-4 transition-transform duration-200 group-hover:-translate-x-1" viewBox="0 0 24 24" fill="none">
        <path d="M15 6l-6 6 6 6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
      </svg>
      Back to Services
    </button>

    <LoadingState v-if="loading" />
    <p v-else-if="error" class="text-sm text-rose-500">{{ error }}</p>

    <template v-else-if="service">
      <!-- Hero Section -->
      <div class="relative -mx-6 h-[50vh] min-h-[350px] overflow-hidden rounded-none sm:rounded-2xl">
        <img 
          src="/images/auth-img.webp" 
          alt="Service cover" 
          class="h-full w-full object-cover"
        />
        <div class="absolute inset-0 bg-gradient-to-t from-black/60 via-black/20 to-transparent" />
        <div class="absolute bottom-0 left-0 right-0 p-6 sm:p-8">
          <p class="text-[11px] font-semibold uppercase tracking-[0.2em] text-amber-300">Service Details</p>
          <h1 class="mt-2 text-4xl font-light tracking-tight text-white sm:text-5xl">
            {{ service.name }}
          </h1>
        </div>
      </div>

      <div class="grid gap-6 lg:grid-cols-[minmax(0,1.45fr),minmax(280px,1fr)]">
        <section class="rounded-3xl border border-stone-100 bg-white/75 p-6">
          <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-stone-400">Service Overview</p>
          <p class="mt-3 text-lg leading-relaxed text-stone-600">{{ service.description }}</p>

          <div class="mt-6 grid gap-4 sm:grid-cols-2">
            <div class="rounded-2xl bg-stone-50/80 p-4">
              <p class="text-xs uppercase tracking-wider text-stone-400">Institution</p>
              <p class="mt-1 text-sm font-medium text-stone-700">{{ service.institution?.name || 'Not set' }}</p>
            </div>
            <div class="rounded-2xl bg-stone-50/80 p-4">
              <p class="text-xs uppercase tracking-wider text-stone-400">Service Name</p>
              <p class="mt-1 text-sm font-medium text-stone-700">{{ service.name || 'Not set' }}</p>
            </div>
            <div class="rounded-2xl bg-stone-50/80 p-4">
              <p class="text-xs uppercase tracking-wider text-stone-400">Capacity</p>
              <p class="mt-1 text-sm font-medium text-stone-700">{{ service.capacity || 0 }} people</p>
            </div>
            <div class="rounded-2xl bg-stone-50/80 p-4">
              <p class="text-xs uppercase tracking-wider text-stone-400">Duration</p>
              <p class="mt-1 text-sm font-medium text-stone-700">{{ service.duration || 0 }} minutes</p>
            </div>
            <div class="rounded-2xl bg-stone-50/80 p-4">
              <p class="text-xs uppercase tracking-wider text-stone-400">Opening Time</p>
              <p class="mt-1 text-sm font-medium text-stone-700">{{ formatTime(service.opening_time) }}</p>
            </div>
            <div class="rounded-2xl bg-stone-50/80 p-4">
              <p class="text-xs uppercase tracking-wider text-stone-400">Closing Time</p>
              <p class="mt-1 text-sm font-medium text-stone-700">{{ formatTime(service.closing_time) }}</p>
            </div>
            <div class="rounded-2xl bg-stone-50/80 p-4 sm:col-span-2">
              <p class="text-xs uppercase tracking-wider text-stone-400">Working Days</p>
              <p class="mt-1 text-sm font-medium text-stone-700">{{ formattedWorkingDays(service.working_days) }}</p>
            </div>
          </div>
        </section>

        <section class="rounded-3xl border border-stone-100 bg-white/75 p-6">
          <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-stone-400">Status & Actions</p>
          <div class="mt-4 space-y-3">
            <div class="flex items-center justify-between rounded-xl bg-stone-50/80 px-4 py-3">
              <span class="text-xs uppercase tracking-wider text-stone-400">Status</span>
              <span class="text-sm font-medium text-stone-700">{{ service.status || 'unknown' }}</span>
            </div>
            <div class="flex items-center justify-between rounded-xl bg-stone-50/80 px-4 py-3">
              <span class="text-xs uppercase tracking-wider text-stone-400">Rating</span>
              <span class="text-sm font-medium text-amber-600">{{ averageRating().toFixed(1) }} / 5</span>
            </div>
            <div class="flex items-center justify-between rounded-xl bg-stone-50/80 px-4 py-3">
              <span class="text-xs uppercase tracking-wider text-stone-400">Total Ratings</span>
              <span class="text-sm font-medium text-stone-700">{{ ratings.length }}</span>
            </div>
          </div>

          <div class="mt-5 space-y-3">
            <button
              type="button"
              class="inline-flex w-full items-center justify-center gap-2 rounded-full bg-stone-800 px-5 py-2 text-sm font-medium text-white transition hover:bg-stone-700"
              @click="openBooking"
            >
              <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                <path d="M7 4v4M17 4v4M4 10h16" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/>
                <rect x="4" y="6" width="16" height="14" rx="2" stroke="currentColor" stroke-width="1.6"/>
              </svg>
              Book Appointment
            </button>
            <button
              type="button"
              class="inline-flex w-full items-center justify-center gap-2 rounded-full border border-stone-200 bg-white px-5 py-2 text-sm font-medium text-stone-700 transition hover:bg-stone-50 disabled:opacity-50"
              :disabled="isLoggedIn && !isCitizen"
              @click="startMessage"
            >
              <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                <path d="M4 6h16v10H7l-3 3V6z" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>
              </svg>
              Message Institution
            </button>
            <p v-if="messageError" class="text-xs text-rose-500">{{ messageError }}</p>
            <p v-else-if="!isLoggedIn" class="text-xs text-stone-500">Log in to start messaging.</p>
            <p v-if="!isLoggedIn" class="text-xs text-stone-500">Log in to complete booking.</p>

            <div class="rounded-xl border border-stone-200 bg-white p-3">
              <p class="text-xs uppercase tracking-wider text-stone-400">Rate This Service</p>
              <div class="mt-2 flex items-center gap-1">
                <button
                  v-for="star in 5"
                  :key="star"
                  type="button"
                  :disabled="ratingSubmitting"
                  class="text-xl leading-none disabled:opacity-50"
                  :class="star <= Number(myRating?.score || 0) ? 'text-amber-500' : 'text-stone-300'"
                  @click="rateService(star)"
                >
                  ★
                </button>
              </div>
              <p class="mt-1 text-xs text-stone-500">Your rating: {{ myRating ? Number(myRating.score).toFixed(1) : 'Not rated yet' }}</p>
              <p v-if="ratingError" class="mt-1 text-xs text-rose-500">{{ ratingError }}</p>
            </div>
          </div>
        </section>
      </div>

      <BaseModal :open="bookingOpen" title="Book Appointment" size="lg" @close="closeBooking">
        <p class="text-sm text-stone-500">Select your date and preferred counter.</p>

        <p v-if="bookingError" class="mt-3 text-sm text-rose-500">{{ bookingError }}</p>

        <div class="mt-4 grid gap-3 sm:grid-cols-2">
          <div class="sm:col-span-2">
            <label class="mb-1 block text-xs font-medium text-stone-500">Date & Time</label>
            <input
              v-model="bookingDate"
              type="datetime-local"
              class="w-full rounded-full border border-stone-200 bg-white px-4 py-2 text-sm focus:border-stone-300 focus:outline-none"
            />
          </div>

          <div class="sm:col-span-2" v-if="serviceCounters.length > 0">
            <label class="mb-1 block text-xs font-medium text-stone-500">Preferred Counter (optional)</label>
            <select
              v-model.number="selectedCounterId"
              class="w-full rounded-full border border-stone-200 bg-white px-4 py-2 text-sm focus:border-stone-300 focus:outline-none"
            >
              <option :value="0">Any available counter</option>
              <option v-for="counter in serviceCounters" :key="Number(counter.id)" :value="Number(counter.id)">
                {{ counter.name }} (Counter #{{ counter.counter_number }})
              </option>
            </select>
          </div>
        </div>

        <div class="mt-5 flex items-center gap-3">
          <button
            type="button"
            class="rounded-full bg-stone-800 px-5 py-2 text-sm font-medium text-white transition hover:bg-stone-700 disabled:opacity-50"
            :disabled="bookingLoading"
            @click="bookAppointment"
          >
            {{ bookingLoading ? 'Booking...' : 'Confirm Booking' }}
          </button>
          <button
            type="button"
            class="rounded-full border border-stone-200 px-5 py-2 text-sm font-medium text-stone-600 transition hover:bg-stone-50"
            @click="closeBooking"
          >
            Cancel
          </button>
        </div>
      </BaseModal>

      <!-- Ratings Section -->
      <div>
        <div class="mb-6">
          <h2 class="text-2xl font-light tracking-tight text-stone-800">Recent Ratings</h2>
          <div class="mt-2 h-px w-12 bg-amber-300" />
        </div>
        
        <div class="space-y-4">
          <div 
            v-for="rating in ratings.slice(0, 6)" 
            :key="Number(rating.id)" 
            class="border-b border-stone-100 pb-4"
          >
            <div class="flex items-center gap-2">
              <div class="flex gap-0.5">
                <svg v-for="i in 5" :key="i" class="h-4 w-4" :class="i <= Math.floor(Number(rating.score)) ? 'text-amber-400' : 'text-stone-200'" viewBox="0 0 20 20" fill="currentColor">
                  <path d="M10 15l-5.878 3.09 1.123-6.545L.489 6.91l6.572-.955L10 0l2.939 5.955 6.572.955-4.756 4.635 1.123 6.545z"/>
                </svg>
              </div>
              <span class="text-sm font-medium text-amber-600">{{ Number(rating.score).toFixed(1) }}</span>
            </div>
          </div>
          <p v-if="ratings.length === 0" class="text-sm text-stone-400 py-8 text-center">
            No ratings yet.
          </p>
        </div>
      </div>
    </template>
  </div>
</template>