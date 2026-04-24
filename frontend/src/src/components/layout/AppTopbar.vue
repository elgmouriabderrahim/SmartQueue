<script setup lang="ts">
defineProps<{
  roleLabel: string
  userName: string
  unreadCount: number
}>()

const emit = defineEmits<{
  (e: 'toggle-menu'): void
  (e: 'toggle-notifications'): void
  (e: 'profile'): void
  (e: 'logout'): void
}>()

const getGreeting = () => {
  return new Date().getHours() < 12 ? 'morning' : 'evening'
}
</script>

<template>
  <header class="w-full border-b border-black/10 sticky top-0 z-40 bg-white/80 backdrop-blur-[16px] border-b border-white/50 shadow-[0_4px_24px_rgba(0,0,0,0.02)]">
    <div class="flex items-center justify-between gap-3 px-4 py-3 md:px-6 md:py-4 max-w-[1600px] mx-auto lg:px-8">
      
      <div class="flex items-center gap-4">
        <button 
          @click="emit('toggle-menu')" 
          class="flex md:hidden h-9 w-9 md:h-10 md:w-10 items-center justify-center rounded-2xl bg-white/50 backdrop-blur-[8px] border border-white/40 text-gray-700 transition-all hover:bg-white/80 hover:-translate-y-px" 
          aria-label="Menu"
        >
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"><path d="M4 6h16M4 12h16M4 18h16"/></svg>
        </button>

        <router-link to="/" class="inline-flex items-center gap-3">
          <img src="/images/logo-black.png" alt="SmartQueue logo" class="h-9 w-9 md:h-11 md:w-11 rounded-xl md:rounded-2xl object-cover transition-transform duration-300 hover:scale-105 shadow-[0_4px_12px_rgba(0,0,0,0.04)] border border-white/60" />
          <div class="hidden sm:flex flex-col justify-center leading-tight">
            <span class="text-base font-semibold tracking-tight text-stone-900">SmartQueue</span>
            <span class="text-[10px] font-medium uppercase tracking-[0.2em] text-stone-400">Queue Intelligence</span>
          </div>
        </router-link>
      </div>

      <div class="hidden md:flex items-center gap-3">
        <div class="flex items-baseline gap-1">
          <span class="text-xs font-light tracking-wide text-stone-400">Good {{ getGreeting() }},</span>
          <span class="text-sm font-medium text-stone-700">{{ userName?.split(' ')[0] || 'User' }}</span>
        </div>
        <div class="flex items-center gap-1.5 px-2 py-0.5 rounded-full bg-white/70 border border-black/[0.05] text-[11px] text-stone-600">
          <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"><path d="M8 2v4M16 2v4M3 10h18M5 4h14a2 2 0 012 2v14a2 2 0 01-2 2H5a2 2 0 01-2-2V6a2 2 0 012-2z"/></svg>
          <span>{{ new Date().toLocaleDateString('en-US', { month: 'short', day: 'numeric' }) }}</span>
        </div>
      </div>

      <div class="flex items-center gap-2">
        <button @click="emit('toggle-notifications')" class="relative flex h-9 w-9 md:h-10 md:w-10 items-center justify-center rounded-2xl bg-white/50 border border-white/40 text-gray-700 transition-all hover:bg-white/80 hover:-translate-y-px" aria-label="Notifications">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"><path d="M12 4a4 4 0 0 0-4 4v3l-2 3v1h12v-1l-2-3V8a4 4 0 0 0-4-4zM10 18a2 2 0 1 0 4 0"/></svg>
          <span v-if="unreadCount > 0" class="absolute top-1.5 right-1.5 h-2 w-2 rounded-full bg-orange-500 shadow-[0_0_0_2px_white] animate-pulse"></span>
        </button>

        <button @click="emit('profile')" class="flex items-center gap-2.5 rounded-2xl p-1 bg-white/45 border border-white/35 transition-all hover:bg-white/75 hover:-translate-y-px">
          <div class="h-7 w-7 md:h-8 md:w-8 flex items-center justify-center rounded-full bg-gradient-to-br from-teal-800 to-teal-950 text-white text-xs font-medium">{{ userName?.charAt(0)?.toUpperCase() || 'U' }}</div>
          <div class="hidden lg:flex flex-col pr-1">
            <span class="text-sm font-medium text-stone-700">{{ userName?.split(' ')[0] || 'User' }}</span>
            <span class="text-[10px] font-medium uppercase tracking-[0.1em] text-stone-400">{{ roleLabel }}</span>
          </div>
        </button>

        <button @click="emit('logout')" class="flex h-9 w-9 md:h-10 md:w-10 items-center justify-center rounded-2xl bg-white/50 border border-white/40 text-gray-700 transition-all hover:bg-red-50 hover:text-orange-600 hover:-translate-y-px" aria-label="Logout">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"><path d="M9 21H5a2 2 0 01-2-2V5a2 2 0 012-2h4M16 17l4-4-4-4M12 13h8"/></svg>
        </button>
      </div>
    </div>
  </header>
</template>