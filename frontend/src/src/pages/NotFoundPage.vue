<template>
  <div class="min-h-screen flex items-center justify-center bg-gradient-to-br from-stone-50 via-white to-stone-100">
    <!-- Ambient Light -->
    <div class="pointer-events-none fixed inset-0 -z-10">
      <div class="absolute top-0 left-1/4 h-[500px] w-[500px] rounded-full bg-teal-400/10 blur-[100px]" />
      <div class="absolute bottom-0 right-1/4 h-[400px] w-[400px] rounded-full bg-amber-400/8 blur-[120px]" />
    </div>

    <div class="text-center max-w-md mx-auto px-6">
      <!-- Large 404 Text -->
      <div class="relative">
        <h1 class="text-8xl font-light tracking-tighter text-stone-800 sm:text-9xl">
          404
        </h1>
        <div class="absolute -bottom-2 left-1/2 w-16 h-px bg-amber-300 -translate-x-1/2" />
      </div>
      
      <!-- Message -->
      <h2 class="mt-8 text-2xl font-light tracking-tight text-stone-700">
        Page not found
      </h2>
      <p class="mt-2 text-stone-400">
        The page you requested does not exist.
      </p>
      
      <!-- Back Button -->
      <div class="mt-8">
        <router-link 
          to="/app" 
          class="inline-block rounded-full border border-stone-200 bg-white/60 backdrop-blur-sm px-6 py-2.5 text-sm font-medium text-stone-600 transition-all duration-200 hover:border-stone-300 hover:bg-white/80 hover:-translate-y-0.5"
        >
          Go to dashboard
        </router-link>
      </div>
    </div>
  </div>
</template>