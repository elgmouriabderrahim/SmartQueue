<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import LoadingState from '@/components/LoadingState.vue'
import { smartQueueApi } from '@/services/smartQueueApi'
import { toApiError } from '@/utils/http'

const route = useRoute()
const router = useRouter()
const loading = ref(false)
const error = ref('')
const institution = ref<any | null>(null)
const services = ref<any[]>([])
const ratings = ref<any[]>([])

async function loadInstitution() {
  loading.value = true
  error.value = ''

  try {
    const response = await smartQueueApi.institution(Number(route.params.id))
    institution.value = response.data.data

    const serviceItems = institution.value?.services || []
    services.value = serviceItems

    const serviceIds = new Set(serviceItems.map((item: any) => Number(item.id)))
    const ratingsResponse = await smartQueueApi.ratings({ per_page: 100 })
    ratings.value = (ratingsResponse.data.data.data || []).filter((item: any) => serviceIds.has(Number(item.service_id)))
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

function goBack(): void {
  if (window.history.length > 1) {
    router.back()
    return
  }
  router.push('/institutions')
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

      <!-- Description - Clean, minimal -->
      <div class="max-w-2xl">
        <p class="text-lg leading-relaxed text-stone-600">
          {{ institution.description }}
        </p>
      </div>

      <!-- Two Column Layout -->
      <div class="grid gap-12 lg:grid-cols-2">
        <!-- Services Section -->
        <div>
          <div class="mb-6">
            <h2 class="text-2xl font-light tracking-tight text-stone-800">Services</h2>
            <div class="mt-2 h-px w-12 bg-amber-300" />
          </div>
          <p class="text-sm text-stone-500">Available services at this institution.</p>
          
          <div class="mt-6 space-y-4">
            <div 
              v-for="service in services" 
              :key="Number(service.id)" 
              class="group border-b border-stone-100 pb-4 transition-all duration-200 hover:border-stone-200 hover:pl-2"
            >
              <h3 class="font-medium text-stone-800">{{ service.name }}</h3>
              <p class="mt-1 text-sm text-stone-500 leading-relaxed">{{ service.description }}</p>
            </div>
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
          <p class="text-sm text-stone-500">What citizens say about this institution's services.</p>
          
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
              <p class="mt-2 text-sm text-stone-600 leading-relaxed">{{ rating.comment || 'No comment provided.' }}</p>
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