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
const service = ref<any | null>(null)
const ratings = ref<any[]>([])

async function loadService() {
  loading.value = true
  error.value = ''

  try {
    const response = await smartQueueApi.service(Number(route.params.id))
    service.value = response.data.data

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

function goBack(): void {
  if (window.history.length > 1) {
    router.back()
    return
  }
  router.push('/services')
}

onMounted(loadService)
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

      <!-- Service Info - Clean stats -->
      <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
        <div class="border-b border-stone-100 pb-4">
          <p class="text-xs uppercase tracking-wider text-stone-400">Duration</p>
          <p class="mt-1 text-2xl font-light text-stone-700">{{ service.duration }} <span class="text-sm font-normal text-stone-400">minutes</span></p>
        </div>
        <div class="border-b border-stone-100 pb-4">
          <p class="text-xs uppercase tracking-wider text-stone-400">Capacity</p>
          <p class="mt-1 text-2xl font-light text-stone-700">{{ service.capacity }} <span class="text-sm font-normal text-stone-400">people</span></p>
        </div>
        <div class="border-b border-stone-100 pb-4">
          <p class="text-xs uppercase tracking-wider text-stone-400">Rating</p>
          <p class="mt-1 text-2xl font-light text-amber-500">{{ averageRating().toFixed(1) }} <span class="text-sm font-normal text-stone-400">/ 5</span></p>
        </div>
        <div class="border-b border-stone-100 pb-4">
          <p class="text-xs uppercase tracking-wider text-stone-400">Reviews</p>
          <p class="mt-1 text-2xl font-light text-stone-700">{{ ratings.length }} <span class="text-sm font-normal text-stone-400">total</span></p>
        </div>
      </div>

      <!-- Description -->
      <div class="max-w-2xl">
        <p class="text-lg leading-relaxed text-stone-600">
          {{ service.description }}
        </p>
      </div>

      <!-- Ratings Section -->
      <div>
        <div class="mb-6">
          <h2 class="text-2xl font-light tracking-tight text-stone-800">Recent Comments</h2>
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
            <p class="mt-2 text-sm text-stone-600 leading-relaxed">{{ rating.comment || 'No comment provided.' }}</p>
          </div>
          <p v-if="ratings.length === 0" class="text-sm text-stone-400 py-8 text-center">
            No ratings yet.
          </p>
        </div>
      </div>
    </template>
  </div>
</template>