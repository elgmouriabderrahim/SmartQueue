<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { smartQueueApi } from '@/services/smartQueueApi'

const institutions = ref<Array<{ id: number; name: string; city: string; servicesCount: number }>>([])

onMounted(async () => {
  try {
    const institutionsResponse = await smartQueueApi.institutions({ per_page: 6 })
    const fetchedInstitutions = institutionsResponse.data.data.data || []

    institutions.value = fetchedInstitutions.slice(0, 3).map((institution: any) => ({
      id: Number(institution.id),
      name: String(institution.name || 'Institution'),
      city: String(institution.city || 'Morocco'),
      servicesCount: Array.isArray(institution.services) ? institution.services.length : 0,
    }))
  } catch {
    institutions.value = []
  }
})
</script>

<template>
  <div class="space-y-24">
    <!-- Hero Section with Panoramic Image -->
    <div class="relative h-[70vh] min-h-[550px] w-full overflow-hidden rounded-3xl">
      <!-- Hero Image -->
      <img 
        src="/images/hero.jpg" 
        alt="Panoramic Glass Residence - Modern architecture with glass walls"
        class="h-full w-full object-cover"
      />
      
      <!-- Overlay for text readability -->
      <div class="absolute inset-0 bg-gradient-to-t from-black/60 via-black/20 to-transparent" />
      <div class="absolute inset-0 bg-gradient-to-r from-black/30 to-transparent" />
      
      <!-- Text Overlay -->
      <div class="absolute bottom-0 left-0 right-0 p-8 md:p-12">
        <p class="text-[11px] font-semibold uppercase tracking-[0.3em] text-amber-300">Smart Appointment & Queue Management System</p>
        <h1 class="mt-4 max-w-3xl text-4xl font-light tracking-tight text-white sm:text-5xl lg:text-6xl">
          Skip the Queue.
          <span class="block">Save Your Time.</span>
        </h1>
        <p class="mt-4 max-w-xl text-base text-white/80 leading-relaxed sm:text-lg">
          SmartQueue helps you book appointments, avoid long waiting lines, and manage your time efficiently.
        </p>
        <div class="mt-8 flex flex-wrap gap-4">
          <router-link to="/auth/register" class="rounded-full bg-white px-8 py-3 text-sm font-medium text-stone-800 transition-all duration-200 hover:bg-stone-100 hover:-translate-y-0.5">
            Get Started
          </router-link>
          <router-link to="/services" class="rounded-full border border-white/30 bg-white/10 backdrop-blur-sm px-8 py-3 text-sm font-medium text-white transition-all duration-200 hover:bg-white/20 hover:-translate-y-0.5">
            Explore Services
          </router-link>
        </div>
      </div>
    </div>

    <!-- Rest of the sections remain the same -->
    <!-- Problem Section -->
    <div>
      <div class="text-center max-w-2xl mx-auto mb-12">
        <p class="text-[11px] font-semibold uppercase tracking-[0.3em] text-stone-400">The Problem</p>
        <h2 class="mt-4 text-3xl font-light tracking-tight text-stone-800">Public Service Queues Are Still Painful</h2>
      </div>

      <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-4">
        <div class="border-b border-stone-100 pb-6">
          <div class="text-stone-400 mb-4">
            <svg class="h-8 w-8" viewBox="0 0 24 24" fill="none">
              <path d="M12 6v6l4 2" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
              <circle cx="12" cy="12" r="8" stroke="currentColor" stroke-width="1.5"/>
            </svg>
          </div>
          <h3 class="text-lg font-medium text-stone-800">Long Waiting Lines</h3>
          <p class="mt-2 text-sm text-stone-500 leading-relaxed">Citizens spend hours waiting for simple procedures.</p>
        </div>

        <div class="border-b border-stone-100 pb-6">
          <div class="text-stone-400 mb-4">
            <svg class="h-8 w-8" viewBox="0 0 24 24" fill="none">
              <path d="M8 7h12M8 12h8M8 17h12M4 7h.01M4 12h.01M4 17h.01" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
            </svg>
          </div>
          <h3 class="text-lg font-medium text-stone-800">No Organization</h3>
          <p class="mt-2 text-sm text-stone-500 leading-relaxed">Manual queue flow creates confusion and unfair service order.</p>
        </div>

        <div class="border-b border-stone-100 pb-6">
          <div class="text-stone-400 mb-4">
            <svg class="h-8 w-8" viewBox="0 0 24 24" fill="none">
              <path d="M12 4v8l3 3" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
              <circle cx="12" cy="12" r="8" stroke="currentColor" stroke-width="1.5"/>
            </svg>
          </div>
          <h3 class="text-lg font-medium text-stone-800">Wasted Time</h3>
          <p class="mt-2 text-sm text-stone-500 leading-relaxed">People arrive too early because they cannot predict wait times.</p>
        </div>

        <div class="border-b border-stone-100 pb-6">
          <div class="text-stone-400 mb-4">
            <svg class="h-8 w-8" viewBox="0 0 24 24" fill="none">
              <circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="1.5"/>
              <circle cx="9" cy="10" r="1" fill="currentColor"/>
              <circle cx="15" cy="10" r="1" fill="currentColor"/>
              <path d="M8 15s1.5 2 4 2 4-2 4-2" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
            </svg>
          </div>
          <h3 class="text-lg font-medium text-stone-800">Citizen Frustration</h3>
          <p class="mt-2 text-sm text-stone-500 leading-relaxed">Service experience feels stressful and lacks transparency.</p>
        </div>
      </div>
    </div>

    <!-- Solution Section -->
    <div>
      <div class="text-center max-w-2xl mx-auto mb-12">
        <p class="text-[11px] font-semibold uppercase tracking-[0.3em] text-stone-400">Our Solution</p>
        <h2 class="mt-4 text-3xl font-light tracking-tight text-stone-800">A Smarter Way To Access Public Services</h2>
      </div>

      <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-4">
        <div class="border-b border-stone-100 pb-6">
          <div class="text-stone-400 mb-4">
            <svg class="h-8 w-8" viewBox="0 0 24 24" fill="none">
              <rect x="4" y="6" width="16" height="14" rx="2" stroke="currentColor" stroke-width="1.5"/>
              <path d="M8 4v4M16 4v4M4 10h16" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
            </svg>
          </div>
          <h3 class="text-lg font-medium text-stone-800">Online Booking</h3>
          <p class="mt-2 text-sm text-stone-500 leading-relaxed">Book your appointment online in seconds.</p>
        </div>

        <div class="border-b border-stone-100 pb-6">
          <div class="text-stone-400 mb-4">
            <svg class="h-8 w-8" viewBox="0 0 24 24" fill="none">
              <path d="M5 7h14M5 12h10M5 17h6" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
            </svg>
          </div>
          <h3 class="text-lg font-medium text-stone-800">Smart Queue System</h3>
          <p class="mt-2 text-sm text-stone-500 leading-relaxed">Automated queue organization for fair and smooth flow.</p>
        </div>

        <div class="border-b border-stone-100 pb-6">
          <div class="text-stone-400 mb-4">
            <svg class="h-8 w-8" viewBox="0 0 24 24" fill="none">
              <circle cx="12" cy="12" r="3" stroke="currentColor" stroke-width="1.5"/>
              <path d="M12 3v4M12 17v4M3 12h4M17 12h4M6.5 6.5l2.8 2.8M14.7 14.7l2.8 2.8M17.5 6.5l-2.8 2.8M9.3 14.7l-2.8 2.8" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
            </svg>
          </div>
          <h3 class="text-lg font-medium text-stone-800">Real-Time Tracking</h3>
          <p class="mt-2 text-sm text-stone-500 leading-relaxed">Know your queue position and estimated wait time live.</p>
        </div>

        <div class="border-b border-stone-100 pb-6">
          <div class="text-stone-400 mb-4">
            <svg class="h-8 w-8" viewBox="0 0 24 24" fill="none">
              <path d="M15 17H5a2 2 0 01-2-2V7a2 2 0 012-2h14a2 2 0 012 2v8" stroke="currentColor" stroke-width="1.5"/>
              <path d="M15 21l6-6h-4v-4h-4v4h-4l6 6z" fill="currentColor"/>
            </svg>
          </div>
          <h3 class="text-lg font-medium text-stone-800">Instant Notifications</h3>
          <p class="mt-2 text-sm text-stone-500 leading-relaxed">Receive updates before your turn to avoid idle waiting.</p>
        </div>
      </div>
    </div>

    <!-- How It Works -->
    <div class="text-center">
      <p class="text-[11px] font-semibold uppercase tracking-[0.3em] text-stone-400">How It Works</p>
      <h2 class="mt-4 text-3xl font-light tracking-tight text-stone-800">Four Simple Steps</h2>
    </div>

    <div class="grid gap-8 md:grid-cols-4">
      <div class="text-center">
        <div class="mx-auto w-12 h-12 rounded-full border border-stone-200 flex items-center justify-center text-stone-400 font-light text-xl">1</div>
        <h3 class="mt-4 font-medium text-stone-800">Choose a service</h3>
        <p class="mt-2 text-sm text-stone-500">Browse institutions and select the service you need.</p>
      </div>
      <div class="text-center">
        <div class="mx-auto w-12 h-12 rounded-full border border-stone-200 flex items-center justify-center text-stone-400 font-light text-xl">2</div>
        <h3 class="mt-4 font-medium text-stone-800">Book an appointment</h3>
        <p class="mt-2 text-sm text-stone-500">Reserve your slot online with just a few clicks.</p>
      </div>
      <div class="text-center">
        <div class="mx-auto w-12 h-12 rounded-full border border-stone-200 flex items-center justify-center text-stone-400 font-light text-xl">3</div>
        <h3 class="mt-4 font-medium text-stone-800">Get queue position</h3>
        <p class="mt-2 text-sm text-stone-500">Track your live position and estimated waiting time.</p>
      </div>
      <div class="text-center">
        <div class="mx-auto w-12 h-12 rounded-full border border-stone-200 flex items-center justify-center text-stone-400 font-light text-xl">4</div>
        <h3 class="mt-4 font-medium text-stone-800">Arrive on time</h3>
        <p class="mt-2 text-sm text-stone-500">Reach the institution when your turn is near.</p>
      </div>
    </div>

    <!-- Institutions Preview -->
    <div>
      <div class="flex flex-wrap items-end justify-between gap-3 mb-8">
        <div>
          <p class="text-[11px] font-semibold uppercase tracking-[0.3em] text-stone-400">Institutions Preview</p>
          <h2 class="mt-2 text-2xl font-light tracking-tight text-stone-800">Discover Service Providers</h2>
        </div>
        <router-link to="/institutions" class="text-sm text-stone-400 transition-colors hover:text-stone-600">
          View all →
        </router-link>
      </div>

      <div v-if="institutions.length" class="grid gap-6 md:grid-cols-3">
        <router-link
          v-for="institution in institutions"
          :key="institution.id"
          :to="`/institutions/${institution.id}`"
          class="group block"
        >
          <div class="overflow-hidden rounded-2xl border border-stone-100 bg-white transition-all duration-200 group-hover:border-stone-200">
            <div class="h-40 overflow-hidden bg-stone-100">
              <img src="/images/auth-img.webp" alt="Institution" class="h-full w-full object-cover transition-transform duration-300 group-hover:scale-105" loading="lazy" />
            </div>
            <div class="p-5">
              <h3 class="font-medium text-stone-800">{{ institution.name }}</h3>
              <p class="mt-1 text-sm text-stone-400">{{ institution.city }}</p>
              <p class="mt-3 text-xs text-stone-400">{{ institution.servicesCount }} services</p>
            </div>
          </div>
        </router-link>
      </div>
      <p v-else class="text-sm text-stone-500">No institutions available yet.</p>
    </div>

    <!-- Testimonials -->
    <div class="grid gap-8 md:grid-cols-2">
      <div class="border-l-2 border-stone-200 pl-6">
        <p class="text-sm text-stone-500 italic">"This platform saved me hours. I arrived exactly when my turn was close."</p>
        <p class="mt-4 text-sm font-medium text-stone-600">— Fatima, Casablanca</p>
      </div>
      <div class="border-l-2 border-stone-200 pl-6">
        <p class="text-sm text-stone-500 italic">"Queue flow became organized, and citizen complaints decreased significantly."</p>
        <p class="mt-4 text-sm font-medium text-stone-600">— Service Manager, Rabat</p>
      </div>
    </div>

    <!-- CTA Section -->
    <div class="text-center py-8">
      <h2 class="text-3xl font-light tracking-tight text-stone-800">Start Saving Time Today</h2>
      <p class="mt-3 text-stone-500">Join SmartQueue and experience better appointment planning from day one.</p>
      <div class="mt-6 flex flex-wrap justify-center gap-4">
        <router-link to="/auth/register" class="rounded-full bg-stone-800 px-8 py-3 text-sm font-medium text-white transition-all duration-200 hover:bg-stone-700 hover:-translate-y-0.5">
          Register
        </router-link>
        <router-link to="/auth/login" class="rounded-full border border-stone-200 px-8 py-3 text-sm font-medium text-stone-600 transition-all duration-200 hover:border-stone-300 hover:bg-stone-50 hover:-translate-y-0.5">
          Login
        </router-link>
      </div>
    </div>
  </div>
</template>