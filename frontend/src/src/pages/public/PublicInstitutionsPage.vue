<script setup lang="ts">
import { computed, onMounted, reactive, ref } from 'vue'
import LoadingState from '@/components/LoadingState.vue'
import EmptyState from '@/components/EmptyState.vue'
import { smartQueueApi } from '@/services/smartQueueApi'
import { toApiError } from '@/utils/http'

const loading = ref(false)
const error = ref('')
const institutions = ref<any[]>([])
const ratings = ref<any[]>([])

const filters = reactive({
  q: '',
  city: '',
})

const visibleInstitutions = computed(() => {
  const q = filters.q.trim().toLowerCase()
  const city = filters.city.trim().toLowerCase()

  return institutions.value.filter((institution) => {
    const matchesQuery =
      q.length === 0 ||
      String(institution.name || '').toLowerCase().includes(q) ||
      String(institution.slug || '').toLowerCase().includes(q)

    const matchesCity = city.length === 0 || String(institution.city || '').toLowerCase().includes(city)

    return matchesQuery && matchesCity
  })
})

function institutionStats(institutionId: number): { average: number; count: number; servicesCount: number } {
  const institutionServicesCount = Number((institutions.value.find((item) => Number(item.id) === Number(institutionId))?.services?.length) || 0)
  const institutionRatings = ratings.value.filter((rating) => Number(rating.institution_id) === Number(institutionId))

  const count = institutionRatings.length
  const average = count > 0
    ? institutionRatings.reduce((sum, item) => sum + Number(item.score || 0), 0) / count
    : 0

  return {
    average,
    count,
    servicesCount: institutionServicesCount,
  }
}

async function loadData() {
  loading.value = true
  error.value = ''

  try {
    const [institutionsResponse, ratingsResponse] = await Promise.all([
      smartQueueApi.institutions({ per_page: 100 }),
      smartQueueApi.ratings({ per_page: 100 }),
    ])

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
    <!-- Hero Section - Minimal -->
    <div class="text-center max-w-3xl mx-auto">
      <p class="text-[11px] font-semibold uppercase tracking-[0.3em] text-stone-400">Directory</p>
      <h1 class="mt-4 text-4xl font-light tracking-tight text-stone-800 sm:text-5xl">
        Find The Right Institution
      </h1>
      <p class="mt-4 text-lg text-stone-500 leading-relaxed">
        Browse institutions by city, compare available services, and check institution ratings before booking.
      </p>
    </div>

    <!-- Filters - Clean and minimal -->
    <div class="max-w-3xl mx-auto w-full">
      <div class="flex flex-col gap-3 sm:flex-row">
        <input
          v-model="filters.q"
          placeholder="Search by institution name"
          class="flex-1 rounded-full border border-stone-200 bg-white/80 px-5 py-3 text-sm text-stone-800 placeholder:text-stone-400 transition-all duration-200 focus:border-stone-300 focus:outline-none focus:ring-1 focus:ring-stone-200"
        />
        <input
          v-model="filters.city"
          placeholder="Filter by city"
          class="flex-1 rounded-full border border-stone-200 bg-white/80 px-5 py-3 text-sm text-stone-800 placeholder:text-stone-400 transition-all duration-200 focus:border-stone-300 focus:outline-none focus:ring-1 focus:ring-stone-200"
        />
        <div class="rounded-full bg-stone-50 px-5 py-3 text-center text-sm text-stone-500">
          {{ visibleInstitutions.length }} institutions
        </div>
      </div>
    </div>

    <!-- Loading & Error States -->
    <LoadingState v-if="loading" />
    <p v-else-if="error" class="text-center text-sm text-rose-500">{{ error }}</p>
    <EmptyState v-else-if="visibleInstitutions.length === 0" message="No institutions found for your search." />

    <!-- Institutions Grid - Minimal cards -->
    <div v-else class="grid gap-8 md:grid-cols-2 lg:grid-cols-3">
      <router-link
        v-for="institution in visibleInstitutions"
        :key="Number(institution.id)"
        :to="`/institutions/${institution.id}`"
        class="group block transition-all duration-300 hover:-translate-y-1"
      >
        <!-- Card - No shadow, just border -->
        <div class="overflow-hidden rounded-2xl border border-stone-100 bg-white transition-all duration-300 group-hover:border-stone-200">
          <!-- Image -->
          <div class="relative h-48 overflow-hidden bg-stone-100">
            <img 
              src="/images/auth-img.webp" 
              :alt="institution.name" 
              class="h-full w-full object-cover transition-transform duration-500 group-hover:scale-105" 
              loading="lazy" 
            />
            <div class="absolute inset-0 bg-gradient-to-t from-black/20 via-transparent to-transparent" />
            <span class="absolute bottom-4 left-4 rounded-full bg-white/90 backdrop-blur-sm px-3 py-1 text-xs font-medium text-stone-700">
              {{ institution.city }}
            </span>
          </div>

          <!-- Content -->
          <div class="p-6">
            <h2 class="text-xl font-medium tracking-tight text-stone-800">
              {{ institution.name }}
            </h2>
            <p class="mt-2 line-clamp-2 text-sm text-stone-500 leading-relaxed">
              {{ institution.description }}
            </p>

            <!-- Stats - Minimal inline -->
            <div class="mt-5 flex items-center gap-4 text-sm">
              <div class="flex items-center gap-1">
                <span class="text-stone-400">Services</span>
                <span class="font-medium text-stone-600">{{ institutionStats(Number(institution.id)).servicesCount }}</span>
              </div>
              <div class="h-3 w-px bg-stone-200" />
              <div class="flex items-center gap-1">
                <span class="text-stone-400">Rating</span>
                <span class="font-medium text-stone-600">{{ institutionStats(Number(institution.id)).average.toFixed(1) }}</span>
              </div>
              <div class="h-3 w-px bg-stone-200" />
              <div class="flex items-center gap-1">
                <span class="text-stone-400">Ratings</span>
                <span class="font-medium text-stone-600">{{ institutionStats(Number(institution.id)).count }}</span>
              </div>
            </div>

            <!-- View link -->
            <div class="mt-4 inline-flex items-center gap-1 text-sm text-stone-400 transition-all duration-200 group-hover:text-stone-600">
              View details
              <svg class="h-3 w-3 transition-transform duration-200 group-hover:translate-x-1" viewBox="0 0 24 24" fill="none">
                <path d="M5 12h14M13 6l6 6-6 6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
              </svg>
            </div>
          </div>
        </div>
      </router-link>
    </div>
  </div>
</template>