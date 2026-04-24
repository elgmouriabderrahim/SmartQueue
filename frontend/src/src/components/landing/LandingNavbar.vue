<script setup lang="ts">
import { computed, ref } from 'vue'
import { useAuthStore } from '@/stores/auth'
import { getHomeByRole } from '@/utils/roles'

const authStore = useAuthStore()
const isAuthenticated = computed(() => authStore.isAuthenticated)
const homeAfterAuth = computed(() => getHomeByRole(authStore.user))
const mobileOpen = ref(false)

function closeMobileMenu(): void {
  mobileOpen.value = false
}
</script>

<template>
  <header class="glass-header sticky top-0 z-50">
    <div class="mx-auto flex max-w-7xl items-center justify-between gap-3 px-6 py-4 lg:px-8">
      <!-- Logo Area -->
      <router-link to="/" class="logo-group inline-flex items-center gap-3" @click="closeMobileMenu">
        <img 
          src="/images/logo-black.png" 
          alt="SmartQueue logo" 
          class="logo-image h-11 w-11 rounded-2xl object-cover transition-all duration-300" 
          loading="lazy" 
        />
        <div class="flex flex-col justify-center leading-tight">
          <span class="text-base font-semibold tracking-tight text-stone-800">SmartQueue</span>
          <span class="text-[10px] font-medium uppercase tracking-[0.2em] text-stone-400">Queue Intelligence Platform</span>
        </div>
      </router-link>

      <!-- Mobile Menu Button -->
      <button
        class="mobile-menu-btn inline-flex h-11 w-11 items-center justify-center rounded-2xl md:hidden"
        type="button"
        aria-label="Toggle menu"
        @click="mobileOpen = !mobileOpen"
      >
        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" aria-hidden="true">
          <path d="M4 7h16M4 12h16M4 17h16" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" />
        </svg>
      </button>

      <!-- Desktop Navigation -->
      <nav class="hidden items-center gap-1 text-sm md:flex">
        <router-link to="/" class="nav-link">Home</router-link>
        <router-link to="/institutions" class="nav-link">Institutions</router-link>
        <router-link to="/services" class="nav-link">Services</router-link>
        
        <div class="ml-4 flex items-center gap-2">
          <template v-if="isAuthenticated">
            <router-link :to="homeAfterAuth" class="btn-primary">
              <svg class="mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
              </svg>
              Dashboard
            </router-link>
          </template>

          <template v-else>
            <router-link to="/auth/login" class="btn-secondary">Login</router-link>
            <router-link to="/auth/register" class="btn-primary">Register</router-link>
          </template>
        </div>
      </nav>
    </div>

    <!-- Mobile Navigation Dropdown -->
    <transition
      enter-active-class="duration-300 ease-out"
      enter-from-class="-translate-y-4 opacity-0"
      enter-to-class="translate-y-0 opacity-100"
      leave-active-class="duration-200 ease-in"
      leave-from-class="translate-y-0 opacity-100"
      leave-to-class="-translate-y-4 opacity-0"
    >
      <nav v-if="mobileOpen" class="mobile-nav glass-dropdown md:hidden">
        <router-link to="/" class="mobile-nav-link" @click="closeMobileMenu">Home</router-link>
        <router-link to="/institutions" class="mobile-nav-link" @click="closeMobileMenu">Institutions</router-link>
        <router-link to="/services" class="mobile-nav-link" @click="closeMobileMenu">Services</router-link>
        
        <div class="mt-4 flex flex-col gap-2 pt-2 border-t border-white/20">
          <router-link 
            v-if="isAuthenticated" 
            :to="homeAfterAuth" 
            class="mobile-btn-primary" 
            @click="closeMobileMenu"
          >
            Dashboard
          </router-link>
          <template v-else>
            <router-link to="/auth/login" class="mobile-btn-secondary" @click="closeMobileMenu">Login</router-link>
            <router-link to="/auth/register" class="mobile-btn-primary" @click="closeMobileMenu">Register</router-link>
          </template>
        </div>
      </nav>
    </transition>
  </header>
</template>

<style scoped>
/* Glass Header */
.glass-header {
  background: rgba(255, 255, 255, 0.78);
  backdrop-filter: blur(16px);
  border-bottom: 1px solid rgba(255, 255, 255, 0.5);
  box-shadow: 0 4px 24px rgba(0, 0, 0, 0.02);
}

/* Logo Image */
.logo-image {
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.04);
  border: 1px solid rgba(255, 255, 255, 0.6);
}

.logo-group:hover .logo-image {
  transform: scale(1.02);
  box-shadow: 0 8px 20px rgba(0, 0, 0, 0.06);
}

/* Desktop Navigation Links */
.nav-link {
  @apply rounded-xl px-4 py-2 text-sm font-medium text-stone-600 transition-all duration-200;
}

.nav-link:hover {
  @apply text-stone-900;
  background: rgba(255, 255, 255, 0.6);
  transform: translateY(-1px);
}

.router-link-active {
  @apply text-stone-900;
  background: rgba(255, 255, 255, 0.7);
  backdrop-filter: blur(4px);
}

/* Buttons */
.btn-primary {
  @apply inline-flex items-center rounded-2xl px-4 py-2 text-sm font-semibold transition-all duration-200;
  background: linear-gradient(135deg, rgba(44, 95, 110, 0.95), rgba(44, 95, 110, 0.85));
  color: white;
  backdrop-filter: blur(4px);
  border: 1px solid rgba(255, 255, 255, 0.25);
  box-shadow: 0 2px 8px rgba(44, 95, 110, 0.15);
}

.btn-primary:hover {
  background: linear-gradient(135deg, rgba(44, 95, 110, 1), rgba(44, 95, 110, 0.9));
  transform: translateY(-2px);
  box-shadow: 0 8px 20px rgba(44, 95, 110, 0.2);
}

.btn-secondary {
  @apply rounded-2xl px-4 py-2 text-sm font-medium transition-all duration-200;
  background: rgba(255, 255, 255, 0.5);
  backdrop-filter: blur(4px);
  color: #4a4a4a;
  border: 1px solid rgba(0, 0, 0, 0.06);
}

.btn-secondary:hover {
  background: rgba(255, 255, 255, 0.8);
  transform: translateY(-1px);
  border-color: rgba(0, 0, 0, 0.1);
}

/* Mobile Menu Button */
.mobile-menu-btn {
  background: rgba(255, 255, 255, 0.5);
  backdrop-filter: blur(8px);
  color: #4a4a4a;
  border: 1px solid rgba(255, 255, 255, 0.4);
}

.mobile-menu-btn:hover {
  background: rgba(255, 255, 255, 0.8);
}

/* Mobile Navigation */
.mobile-nav {
  @apply flex flex-col px-6 pb-5 pt-2;
}

.glass-dropdown {
  background: rgba(255, 255, 255, 0.92);
  backdrop-filter: blur(20px);
  border-top: 1px solid rgba(255, 255, 255, 0.6);
}

.mobile-nav-link {
  @apply block rounded-xl px-4 py-3 text-base font-medium text-stone-700 transition-all duration-200;
}

.mobile-nav-link:hover {
  @apply text-stone-900;
  background: rgba(255, 255, 255, 0.5);
  transform: translateX(4px);
}

.mobile-btn-primary {
  @apply rounded-xl px-4 py-3 text-center font-semibold transition-all duration-200;
  background: rgba(44, 95, 110, 0.9);
  color: white;
  border: 1px solid rgba(255, 255, 255, 0.3);
}

.mobile-btn-primary:hover {
  background: rgba(44, 95, 110, 1);
  transform: translateX(4px);
}

.mobile-btn-secondary {
  @apply rounded-xl px-4 py-3 text-center font-medium transition-all duration-200;
  background: rgba(255, 255, 255, 0.5);
  backdrop-filter: blur(4px);
  color: #4a4a4a;
  border: 1px solid rgba(0, 0, 0, 0.06);
}

.mobile-btn-secondary:hover {
  background: rgba(255, 255, 255, 0.8);
  transform: translateX(4px);
}
</style>