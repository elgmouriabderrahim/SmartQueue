<script setup lang="ts">
import { reactive, ref } from 'vue'
import { computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { getHomeByRole } from '@/utils/roles'
import { toApiError } from '@/utils/http'

const authStore = useAuthStore()
const router = useRouter()
const route = useRoute()

function isAllowedRedirectPath(path: string): boolean {
  return (
    path.startsWith('/app/citizen/') ||
    path === '/services' ||
    path.startsWith('/services/') ||
    path === '/institutions' ||
    path.startsWith('/institutions/')
  )
}

const redirectTarget = computed(() => {
  const value = route.query.redirect
  if (typeof value !== 'string' || value.length === 0) {
    return null
  }

  return isAllowedRedirectPath(value) ? value : null
})

const form = reactive({
  first_name: '',
  last_name: '',
  email: '',
  password: '',
  password_confirmation: '',
  identity_number: '',
})

const loading = ref(false)
const errorMessage = ref('')

async function submit() {
  loading.value = true
  errorMessage.value = ''

  try {
    await authStore.register(form)
    await router.replace(redirectTarget.value || getHomeByRole(authStore.user))
  } catch (error) {
    errorMessage.value = toApiError(error).message
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <section class="space-y-6">
    <div class="space-y-2">
      <p class="inline-block text-xs font-bold uppercase tracking-[0.22em] bg-gradient-to-r from-amber-600 to-orange-600 bg-clip-text text-transparent">
        Citizen onboarding
      </p>
      <h2 class="text-3xl font-bold bg-gradient-to-r from-gray-900 to-gray-700 bg-clip-text text-transparent">
        Create your account
      </h2>
      <p class="text-sm text-gray-500 leading-relaxed">
        All new users start as citizens. You can later request institution creation from your dashboard.
      </p>
    </div>

    <form class="space-y-5" @submit.prevent="submit">
      <div class="grid grid-cols-2 gap-4">
        <div class="space-y-1.5">
          <label class="block text-sm font-semibold text-gray-700">
            First name
          </label>
          <input 
            v-model="form.first_name" 
            required 
            class="w-full rounded-xl border-2 border-gray-200 bg-white px-4 py-3 text-gray-900 placeholder:text-gray-400 focus:border-amber-500 focus:outline-none focus:ring-2 focus:ring-amber-500/20 transition duration-200"
            placeholder="Enter your first name"
          />
        </div>
        <div class="space-y-1.5">
          <label class="block text-sm font-semibold text-gray-700">
            Last name
          </label>
          <input 
            v-model="form.last_name" 
            required 
            class="w-full rounded-xl border-2 border-gray-200 bg-white px-4 py-3 text-gray-900 placeholder:text-gray-400 focus:border-amber-500 focus:outline-none focus:ring-2 focus:ring-amber-500/20 transition duration-200"
            placeholder="Enter your last name"
          />
        </div>
      </div>

      <div class="space-y-1.5">
        <label class="block text-sm font-semibold text-gray-700">
          Email address
        </label>
        <input 
          v-model="form.email" 
          type="email" 
          required 
          class="w-full rounded-xl border-2 border-gray-200 bg-white px-4 py-3 text-gray-900 placeholder:text-gray-400 focus:border-amber-500 focus:outline-none focus:ring-2 focus:ring-amber-500/20 transition duration-200"
          placeholder="name@example.com"
        />
      </div>

      <div class="space-y-1.5">
        <label class="block text-sm font-semibold text-gray-700">
          Identity Number <span class="text-gray-400 font-normal">(optional)</span>
        </label>
        <input 
          v-model="form.identity_number" 
          type="text"
          class="w-full rounded-xl border-2 border-gray-200 bg-white px-4 py-3 text-gray-900 placeholder:text-gray-400 focus:border-amber-500 focus:outline-none focus:ring-2 focus:ring-amber-500/20 transition duration-200"
          placeholder="e.g., National ID or Passport number"
        />
        <p class="text-xs text-gray-500 mt-1">Used for verification purposes (optional)</p>
      </div>

      <div class="space-y-1.5">
        <label class="block text-sm font-semibold text-gray-700">
          Password
        </label>
        <input 
          v-model="form.password" 
          type="password" 
          minlength="8" 
          required 
          class="w-full rounded-xl border-2 border-gray-200 bg-white px-4 py-3 text-gray-900 placeholder:text-gray-400 focus:border-amber-500 focus:outline-none focus:ring-2 focus:ring-amber-500/20 transition duration-200"
          placeholder="Minimum 8 characters"
        />
      </div>

      <div class="space-y-1.5">
        <label class="block text-sm font-semibold text-gray-700">
          Confirm password
        </label>
        <input 
          v-model="form.password_confirmation" 
          type="password" 
          minlength="8" 
          required 
          class="w-full rounded-xl border-2 border-gray-200 bg-white px-4 py-3 text-gray-900 placeholder:text-gray-400 focus:border-amber-500 focus:outline-none focus:ring-2 focus:ring-amber-500/20 transition duration-200"
          placeholder="Confirm your password"
        />
      </div>

      <p v-if="errorMessage" class="rounded-lg bg-red-50 px-4 py-2 text-sm text-red-600 border border-red-200">
        {{ errorMessage }}
      </p>

      <button
        type="submit"
        :disabled="loading"
        class="relative w-full overflow-hidden rounded-xl bg-gradient-to-r from-gray-900 to-gray-800 px-4 py-3.5 font-bold text-white transition-all duration-300 hover:scale-[1.02] hover:shadow-lg hover:from-gray-800 hover:to-gray-900 disabled:cursor-not-allowed disabled:opacity-60 disabled:hover:scale-100"
      >
        <span class="relative z-10">
          {{ loading ? 'Creating account...' : 'Create account' }}
        </span>
        <div class="absolute inset-0 -translate-x-full bg-gradient-to-r from-transparent via-white/20 to-transparent transition-transform duration-500 group-hover:translate-x-full"></div>
      </button>
    </form>

    <p class="text-center text-sm text-gray-500">
      Already have an account?
      <router-link :to="redirectTarget ? { path: '/auth/login', query: { redirect: redirectTarget } } : '/auth/login'" class="font-bold text-gray-900 hover:text-amber-600 transition-colors duration-200 underline decoration-2 decoration-transparent hover:decoration-amber-600 underline-offset-2">
        Sign in
      </router-link>
    </p>
  </section>
</template>