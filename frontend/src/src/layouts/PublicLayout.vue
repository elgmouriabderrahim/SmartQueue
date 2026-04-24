<script setup lang="ts">
import LandingNavbar from '@/components/landing/LandingNavbar.vue'
import LandingFooter from '@/components/landing/LandingFooter.vue'
</script>

<template>
  <div class="min-h-screen bg-gradient-to-b from-white via-amber-50/30 to-white">
    <div class="pointer-events-none fixed inset-0 -z-10 overflow-hidden">
      <div class="absolute -left-16 top-24 h-96 w-96 rounded-full bg-gradient-to-br from-amber-400 to-orange-500 opacity-20 blur-3xl animate-float" />
      <div class="absolute right-0 top-0 h-[30rem] w-[30rem] rounded-full bg-gradient-to-bl from-sky-400 to-blue-500 opacity-15 blur-3xl animate-float-delayed" />
      <div class="absolute bottom-0 left-1/3 h-80 w-80 rounded-full bg-gradient-to-tr from-purple-400 to-pink-500 opacity-10 blur-3xl animate-float-slow" />
    </div>

    <LandingNavbar />

    <main class="mx-auto max-w-7xl px-4 py-8 sm:px-6 sm:py-12 lg:px-8">
      <router-view />
    </main>

    <LandingFooter />
  </div>
</template>

<style scoped>
@keyframes float {
  0%, 100% {
    transform: translateY(0px) scale(1);
    opacity: 0.15;
  }
  50% {
    transform: translateY(-20px) scale(1.05);
    opacity: 0.25;
  }
}

@keyframes float-delayed {
  0%, 100% {
    transform: translateY(0px) scale(1);
    opacity: 0.1;
  }
  50% {
    transform: translateY(-30px) scale(1.08);
    opacity: 0.2;
  }
}

@keyframes float-slow {
  0%, 100% {
    transform: translateY(0px) scale(1);
    opacity: 0.08;
  }
  50% {
    transform: translateY(-15px) scale(1.03);
    opacity: 0.15;
  }
}

.animate-float {
  animation: float 8s ease-in-out infinite;
}

.animate-float-delayed {
  animation: float-delayed 10s ease-in-out infinite;
}

.animate-float-slow {
  animation: float-slow 12s ease-in-out infinite;
}
</style>