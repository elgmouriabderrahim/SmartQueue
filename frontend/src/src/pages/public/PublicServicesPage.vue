<script setup lang="ts">
import { computed, onMounted, reactive, ref } from 'vue'
import LoadingState from '@/components/LoadingState.vue'
import EmptyState from '@/components/EmptyState.vue'
import { smartQueueApi } from '@/services/smartQueueApi'
import { toApiError } from '@/utils/http'

const loading = ref(false)
const error = ref('')
const services = ref<any[]>([])
const institutions = ref<any[]>([])
const ratings = ref<any[]>([])

const filters = reactive({
  q: '',
  minScore: 0,
})

function serviceRating(serviceId: number): { average: number; count: number } {
  const items = ratings.value.filter((rating) => Number(rating.service_id) === Number(serviceId))
  const count = items.length
  const average = count > 0 ? items.reduce((sum, item) => sum + Number(item.score || 0), 0) / count : 0
  return { average, count }
}

const visibleServices = computed(() => {
  const q = filters.q.trim().toLowerCase()

  return services.value.filter((service) => {
    const stats = serviceRating(Number(service.id))

    const matchesQuery =
      q.length === 0 ||
      String(service.name || '').toLowerCase().includes(q) ||
      String(service.description || '').toLowerCase().includes(q)

    const matchesScore = Number(filters.minScore) <= 0 || stats.average >= Number(filters.minScore)

    return matchesQuery && matchesScore
  })
})

function institutionName(institutionId: number): string {
  const institution = institutions.value.find((item) => Number(item.id) === Number(institutionId))
  return institution?.name || `Institution ${institutionId}`
}

async function loadData() {
  loading.value = true
  error.value = ''

  try {
    const [servicesResponse, institutionsResponse, ratingsResponse] = await Promise.all([
      smartQueueApi.services({ per_page: 100 }),
      smartQueueApi.institutions({ per_page: 100 }),
      smartQueueApi.ratings({ per_page: 100 }),
    ])

    services.value = servicesResponse.data.data.data || []
    institutions.value = institutionsResponse.data.data.data || []
    ratings.value = ratingsResponse.data.data.data || []
  } catch (err) {
    error.value = toApiError(err).message
  } finally {
    loading.value = false
  }
}

onMounted(loadData)
</script>

<template>
  <div class="space-y-12">
    <!-- Hero Section - Minimal centered -->
    <div class="text-center max-w-3xl mx-auto">
      <p class="text-[11px] font-semibold uppercase tracking-[0.3em] text-stone-400">Catalog</p>
      <h1 class="mt-4 text-4xl font-light tracking-tight text-stone-800 sm:text-5xl">
        Explore Available Services
      </h1>
      <p class="mt-4 text-lg text-stone-500 leading-relaxed">
        Compare service duration, capacity, and ratings before choosing an appointment.
      </p>
    </div>

    <!-- Filters - Clean inline -->
    <div class="max-w-2xl mx-auto w-full">
      <div class="flex flex-col gap-3 sm:flex-row">
        <input
          v-model="filters.q"
          placeholder="Search services"
          class="flex-1 rounded-full border border-stone-200 bg-white/80 px-5 py-3 text-sm text-stone-800 placeholder:text-stone-400 transition-all duration-200 focus:border-stone-300 focus:outline-none focus:ring-1 focus:ring-stone-200"
        />
        <select
          v-model.number="filters.minScore"
          class="rounded-full border border-stone-200 bg-white/80 px-5 py-3 text-sm text-stone-600 transition-all duration-200 focus:border-stone-300 focus:outline-none focus:ring-1 focus:ring-stone-200"
        >
          <option :value="0">Any rating</option>
          <option :value="3">3.0+ rating</option>
          <option :value="4">4.0+ rating</option>
          <option :value="4.5">4.5+ rating</option>
        </select>
        <div class="rounded-full bg-stone-50 px-5 py-3 text-center text-sm text-stone-500">
          {{ visibleServices.length }} services
        </div>
      </div>
    </div>

    <!-- Loading & Error States -->
    <LoadingState v-if="loading" />
    <p v-else-if="error" class="text-center text-sm text-rose-500">{{ error }}</p>
    <EmptyState v-else-if="visibleServices.length === 0" message="No services available." icon="neutral" />

    <!-- Services Grid - Minimal cards -->
    <div v-else class="grid gap-8 md:grid-cols-2 lg:grid-cols-3">
      <router-link
        v-for="service in visibleServices"
        :key="Number(service.id)"
        :to="`/services/${service.id}`"
        class="group block transition-all duration-300 hover:-translate-y-1"
      >
        <div class="overflow-hidden rounded-2xl border border-stone-100 bg-white p-6 transition-all duration-300 group-hover:border-stone-200">
          <!-- Institution Name -->
          <p class="text-[11px] font-semibold uppercase tracking-[0.2em] text-stone-400">
            {{ institutionName(Number(service.institution_id)) }}
          </p>
          
          <!-- Service Title -->
          <h2 class="mt-2 text-xl font-medium tracking-tight text-stone-800">
            {{ service.name }}
          </h2>
          
          <!-- Description -->
          <p class="mt-2 line-clamp-2 text-sm text-stone-500 leading-relaxed">
            {{ service.description }}
          </p>

          <!-- Stats - Minimal inline chips -->
          <div class="mt-5 flex flex-wrap gap-3">
            <span class="text-xs text-stone-400">⏱️ {{ service.duration }} min</span>
            <span class="text-xs text-stone-400">👥 capacity {{ service.capacity }}</span>
            <span class="text-xs text-amber-500">⭐ {{ serviceRating(Number(service.id)).average.toFixed(1) }} / 5</span>
          </div>

          <!-- View link -->
          <div class="mt-4 inline-flex items-center gap-1 text-sm text-stone-400 transition-all duration-200 group-hover:text-stone-600">
            View details
            <svg class="h-3 w-3 transition-transform duration-200 group-hover:translate-x-1" viewBox="0 0 24 24" fill="none">
              <path d="M9 6l6 6-6 6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
            </svg>
          </div>
        </div>
      </router-link>
    </div>
  </div>
</template>