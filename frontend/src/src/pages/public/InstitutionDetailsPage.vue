<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import LoadingState from '@/components/LoadingState.vue'
import { smartQueueApi } from '@/services/smartQueueApi'
import { useAuthStore } from '@/stores/auth'
import { toApiError } from '@/utils/http'

const route = useRoute()
const router = useRouter()
const authStore = useAuthStore()
const loading = ref(false)
const error = ref('')
const institution = ref<any | null>(null)
const services = ref<any[]>([])
const ratings = ref<any[]>([])
const ratingSubmitting = ref(false)
const ratingError = ref('')

const isCitizen = computed(() => authStore.userRole === 'citizen')
const isAuthenticated = computed(() => authStore.isAuthenticated)

async function loadInstitution() {
  loading.value = true
  error.value = ''

  try {
    const response = await smartQueueApi.institution(Number(route.params.id))
    institution.value = response.data.data

    const serviceItems = institution.value?.services || []
    services.value = serviceItems

    const ratingsResponse = await smartQueueApi.ratings({ per_page: 100, institution_id: Number(route.params.id) })
    ratings.value = ratingsResponse.data.data.data || []
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

function formattedWorkingDays(days: unknown): string {
  if (!Array.isArray(days) || days.length === 0) return 'Not set'
  return days.map((day) => String(day)).join(', ')
}

function formatTime(value: unknown): string {
  if (!value) return 'Not set'
  return String(value).slice(0, 5)
}

function goBack(): void {
  if (window.history.length > 1) {
    router.back()
    return
  }
  router.push('/institutions')
}

function startMessage(): void {
  const id = Number(institution.value?.id || 0)
  if (!id) return

  const target = `/app/citizen/messages?institution_id=${id}`

  if (!isAuthenticated.value) {
    router.push({ path: '/auth/login', query: { redirect: target } })
    return
  }

  if (isCitizen.value) {
    router.push(target)
  }
}

async function rateInstitution(score: number): Promise<void> {
  ratingError.value = ''

  const institutionId = Number(institution.value?.id || 0)
  if (!institutionId) return

  if (!isAuthenticated.value) {
    router.push({ path: '/auth/login', query: { redirect: `/institutions/${institutionId}` } })
    return
  }

  if (!isCitizen.value) {
    ratingError.value = 'Only citizens can rate institutions.'
    return
  }

  ratingSubmitting.value = true
  try {
    await smartQueueApi.createRating({
      institution_id: institutionId,
      score,
    })
    await loadInstitution()
  } catch (err) {
    ratingError.value = toApiError(err).message
  } finally {
    ratingSubmitting.value = false
  }
}

onMounted(loadInstitution)
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
      Back to Institutions
    </button>

    <LoadingState v-if="loading" />
    <p v-else-if="error" class="text-sm text-rose-500">{{ error }}</p>

    <template v-else-if="institution">
      <!-- Hero Section - Like the residence image -->
      <div class="relative -mx-6 h-[60vh] min-h-[400px] overflow-hidden rounded-none sm:rounded-2xl">
        <!-- Hero Image -->
        <img 
          src="/images/auth-img.webp" 
          :alt="institution.name"
          class="h-full w-full object-cover"
        />
        
        <!-- Gradient overlay for text readability -->
        <div class="absolute inset-0 bg-gradient-to-t from-black/70 via-black/20 to-transparent" />
        <div class="absolute inset-0 bg-gradient-to-r from-black/30 to-transparent" />
        
        <!-- Text Overlay -->
        <div class="absolute bottom-0 left-0 right-0 p-6 sm:p-8">
          <p class="text-[11px] font-semibold uppercase tracking-[0.2em] text-amber-300">
            Institution Profile
          </p>
          <h1 class="mt-2 text-4xl font-light tracking-tight text-white sm:text-5xl lg:text-6xl">
            {{ institution.name }}
          </h1>
          <div class="mt-4 flex flex-wrap gap-4 text-white/80">
            <span class="flex items-center gap-1 text-sm">
              <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none">
                <path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7z" stroke="currentColor" stroke-width="1.5"/>
                <circle cx="12" cy="9" r="3" stroke="currentColor" stroke-width="1.5"/>
              </svg>
              {{ institution.city }}
            </span>
            <span class="flex items-center gap-1 text-sm">
              <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none">
                <path d="M12 8v4M12 16h.01" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="1.5"/>
              </svg>
              {{ averageRating().toFixed(1) }} / 5 rating
            </span>
          </div>
        </div>
      </div>

      <div class="grid gap-6 lg:grid-cols-[minmax(0,1.4fr),minmax(280px,1fr)]">
        <section class="rounded-3xl border border-stone-100 bg-white/75 p-6">
          <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-stone-400">Overview</p>
          <p class="mt-3 text-lg leading-relaxed text-stone-600">{{ institution.description }}</p>

          <div class="mt-6 grid gap-4 sm:grid-cols-2">
            <div class="rounded-2xl bg-stone-50/80 p-4">
              <p class="text-xs uppercase tracking-wider text-stone-400">City</p>
              <p class="mt-1 text-sm font-medium text-stone-700">{{ institution.city || 'Not set' }}</p>
            </div>
            <div class="rounded-2xl bg-stone-50/80 p-4">
              <p class="text-xs uppercase tracking-wider text-stone-400">Slug</p>
              <p class="mt-1 text-sm font-medium text-stone-700">{{ institution.slug || 'Not set' }}</p>
            </div>
            <div class="rounded-2xl bg-stone-50/80 p-4 sm:col-span-2">
              <p class="text-xs uppercase tracking-wider text-stone-400">Address</p>
              <p class="mt-1 text-sm font-medium text-stone-700">{{ institution.adress || institution.address || 'Not set' }}</p>
            </div>
          </div>
        </section>

        <section class="rounded-3xl border border-stone-100 bg-white/75 p-6">
          <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-stone-400">Schedule & Limits</p>
          <div class="mt-4 space-y-3">
            <div class="flex items-center justify-between rounded-xl bg-stone-50/80 px-4 py-3">
              <span class="text-xs uppercase tracking-wider text-stone-400">Opening</span>
              <span class="text-sm font-medium text-stone-700">{{ formatTime(institution.opening_time) }}</span>
            </div>
            <div class="flex items-center justify-between rounded-xl bg-stone-50/80 px-4 py-3">
              <span class="text-xs uppercase tracking-wider text-stone-400">Closing</span>
              <span class="text-sm font-medium text-stone-700">{{ formatTime(institution.closing_time) }}</span>
            </div>
            <div class="rounded-xl bg-stone-50/80 px-4 py-3">
              <p class="text-xs uppercase tracking-wider text-stone-400">Working Days</p>
              <p class="mt-1 text-sm font-medium text-stone-700">{{ formattedWorkingDays(institution.working_days) }}</p>
            </div>
            <div class="flex items-center justify-between rounded-xl bg-stone-50/80 px-4 py-3">
              <span class="text-xs uppercase tracking-wider text-stone-400">Status</span>
              <span class="text-sm font-medium text-stone-700">{{ institution.status || 'unknown' }}</span>
            </div>
            <div class="flex items-center justify-between rounded-xl bg-stone-50/80 px-4 py-3">
              <span class="text-xs uppercase tracking-wider text-stone-400">Max/Day</span>
              <span class="text-sm font-medium text-stone-700">{{ institution.max_appointments_per_day || 'Not set' }}</span>
            </div>
          </div>

          <div class="mt-5">
            <button
              type="button"
              class="inline-flex items-center gap-2 rounded-full bg-stone-800 px-5 py-2 text-sm font-medium text-white transition hover:bg-stone-700 disabled:opacity-50"
              :disabled="isAuthenticated && !isCitizen"
              @click="startMessage"
            >
              <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                <path d="M4 6h16v10H7l-3 3V6z" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>
              </svg>
              Message Institution
            </button>
            <p v-if="!isAuthenticated" class="mt-2 text-xs text-stone-500">Log in to start conversation.</p>

            <div class="mt-3 rounded-xl border border-stone-200 bg-white p-3">
              <p class="text-xs uppercase tracking-wider text-stone-400">Rate This Institution</p>
              <div class="mt-2 flex items-center gap-1">
                <button
                  v-for="star in 5"
                  :key="star"
                  type="button"
                  :disabled="ratingSubmitting"
                  class="text-xl leading-none disabled:opacity-50"
                  :class="star <= Number(myRating?.score || 0) ? 'text-amber-500' : 'text-stone-300'"
                  @click="rateInstitution(star)"
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

      <!-- Two Column Layout -->
      <div class="grid gap-8 lg:grid-cols-[minmax(0,1.25fr),minmax(0,1fr)]">
        <!-- Services Section -->
        <div>
          <div class="mb-6">
            <h2 class="text-2xl font-light tracking-tight text-stone-800">Services</h2>
            <div class="mt-2 h-px w-12 bg-amber-300" />
          </div>
          <p class="text-sm text-stone-500">Available services at this institution.</p>
          
          <div class="mt-6 grid gap-4 sm:grid-cols-2">
            <router-link
              v-for="service in services"
              :key="Number(service.id)"
              :to="`/services/${service.id}`"
              class="group rounded-2xl border border-stone-100 bg-white/70 p-4 transition-all duration-200 hover:-translate-y-0.5 hover:border-stone-200"
            >
              <h3 class="font-medium text-stone-800">{{ service.name }}</h3>
              <p class="mt-1 line-clamp-2 text-sm text-stone-500 leading-relaxed">{{ service.description }}</p>
              <div class="mt-2 flex flex-wrap gap-3 text-xs text-stone-500">
                <span class="inline-flex items-center gap-1">
                  <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none"><path d="M12 8v4M12 16h.01" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/><circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="1.6"/></svg>
                  {{ service.status || 'unknown' }}
                </span>
                <span class="inline-flex items-center gap-1">
                  <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none"><path d="M7 4v4M17 4v4M4 10h16" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/><rect x="4" y="6" width="16" height="14" rx="2" stroke="currentColor" stroke-width="1.6"/></svg>
                  {{ formattedWorkingDays(service.working_days) }}
                </span>
              </div>
              <p class="mt-2 text-xs font-medium text-stone-500 group-hover:text-stone-700">View service details</p>
            </router-link>
            <p v-if="services.length === 0" class="text-sm text-stone-400 py-8 text-center">
              No services available yet.
            </p>
          </div>
        </div>

        <!-- Ratings Section -->
        <div>
          <div class="mb-6">
            <h2 class="text-2xl font-light tracking-tight text-stone-800">Recent Ratings</h2>
            <div class="mt-2 h-px w-12 bg-amber-300" />
          </div>
          <p class="text-sm text-stone-500">Overall ratings for this institution.</p>
          
          <div class="mt-6 space-y-4">
            <div 
              v-for="rating in ratings.slice(0, 6)" 
              :key="Number(rating.id)" 
              class="border-b border-stone-100 pb-4"
            >
              <div class="flex items-center justify-between">
                <div class="flex gap-1">
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
      </div>
    </template>
  </div>
</template>

<style scoped>
.rounded-none {
  border-radius: 0;
}

@media (min-width: 640px) {
  .rounded-none {
    border-radius: 1rem;
  }
}
</style>