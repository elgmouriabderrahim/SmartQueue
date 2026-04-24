<script setup lang="ts">
import { computed } from 'vue'

const props = defineProps<{
  title: string
  subtitle: string
  items: Array<{ to: string; label: string }>
  collapsed?: boolean
}>()

const emit = defineEmits<{
  (e: 'navigate'): void
}>()

const primaryLinks = computed(() => ['Dashboard', 'Appointments', 'Queues', 'Services', 'Users'])

function iconFor(label: string): string {
  const key = label.toLowerCase()

  // Main navigation icons
  if (key.includes('dashboard')) return 'M3 3h7v7H3V3zm11 0h7v7h-7V3zM3 14h7v7H3v-7zm11 0h7v7h-7v-7z'
  if (key.includes('appointment')) return 'M9 2v3m6-3v3m-11 5h14M6 3h12a2 2 0 0 1 2 2v11a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2z'
  if (key.includes('message')) return 'M3 5c0-1.1.9-2 2-2h14c1.1 0 2 .9 2 2v10c0 1.1-.9 2-2 2h-2l-4 3-4-3H5c-1.1 0-2-.9-2-2V5z'
  if (key.includes('queue')) return 'M3 6h18M3 12h18M3 18h12M3 3h18v18H3z'
  if (key.includes('alert') || key.includes('notification')) return 'M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z'
  
  // Staff/Employee related
  if (key.includes('profile')) return 'M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z'
  if (key.includes('team') || key.includes('employee')) return 'M15 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm-9-2c1.66 0 3-1.34 3-3s-1.34-3-3-3-3 1.34-3 3 1.34 3 3 3zm0 2c-2.33 0-7 1.17-7 3.5V19h14v-2.5c0-2.33-4.67-3.5-7-3.5z'

  // Services/Operations
  if (key.includes('service')) return 'M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z'
  if (key.includes('department')) return 'M3 3h8v8H3V3zm10 0h8v8h-8V3zM3 13h8v8H3v-8zm10 0h8v8h-8v-8z'
  if (key.includes('counter')) return 'M3 9h18v2H3V9zm0 5h18v2H3v-2zM3 3h18v2H3V3zm1 7h4V7H4v3zm6 0h4V7h-4v3zm6 0h4V7h-4v3z'
  if (key.includes('queue-entries') || key.includes('queue entry')) return 'M3 4h18v2H3V4zm0 7h18v2H3v-2zm0 7h18v2H3v-2zM7 6v2h2V6H7zm0 7v2h2v-2H7zm0 7v2h2v-2H7z'
  
  // Admin/Management
  if (key.includes('request')) return 'M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z'
  if (key.includes('user')) return 'M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm7.5-2c1.66 0 3-1.34 3-3s-1.34-3-3-3-3 1.34-3 3 1.34 3 3 3zM18 19v-2c0-1.5-2.49-2.5-5.5-2.5-1 0-1.96.11-2.82.31.97.92 1.82 2.15 1.82 3.69v2h6.5z'
  
  // Reporting/Insights
  if (key.includes('analytic')) return 'M5 9.2h3V19H5zM10.6 5h2.8v14h-2.8zm5.6 8H19v6h-2.8z'
  if (key.includes('log')) return 'M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8l-6-6z'
  
  // Settings/Admin
  if (key.includes('setting')) return 'M19.14 12.94c.04-.3.06-.61.06-.94 0-.32-.02-.64-.07-.94l2.03-1.58c.18-.14.23-.41.12-.62l-1.92-3.32c-.12-.22-.37-.29-.59-.22l-2.39.96c-.5-.38-1.03-.7-1.62-.94l-.36-2.54c-.04-.24-.24-.41-.48-.41h-3.84c-.24 0-.43.17-.47.41l-.36 2.54c-.59.24-1.13.57-1.62.94l-2.39-.96c-.22-.08-.47 0-.59.22L2.74 8.87c-.12.21-.08.48.12.62l2.03 1.58c-.05.3-.07.62-.07.94s.02.64.07.94l-2.03 1.58c-.18.14-.23.41-.12.62l1.92 3.32c.12.22.37.29.59.22l2.39-.96c.5.38 1.03.7 1.62.94l.36 2.54c.05.24.24.41.48.41h3.84c.24 0 .44-.17.47-.41l.36-2.54c.59-.24 1.13-.56 1.62-.94l2.39.96c.22.08.47 0 .59-.22l1.92-3.32c.12-.22.07-.47-.12-.62l-2.01-1.58zM12 15.6c-1.98 0-3.6-1.62-3.6-3.6s1.62-3.6 3.6-3.6 3.6 1.62 3.6 3.6-1.62 3.6-3.6 3.6z'
  
  // Default: menu icons
  return 'M3 6h18M3 12h18M3 18h18'
}
</script>

<template>
  <aside class="sidebar" :class="{ 'sidebar-collapsed': collapsed }">
    <!-- Header -->
    <div class="sidebar-header" :class="{ 'justify-center': collapsed }">
      <div v-if="!collapsed" class="header-text">
        <p class="subtitle">{{ subtitle }}</p>
        <h1 class="title">{{ title }}</h1>
      </div>
    </div>

    <!-- Navigation -->
    <div v-if="!collapsed" class="nav-label">Navigation</div>
    
    <nav class="nav">
      <router-link
        v-for="item in items"
        :key="item.to"
        :to="item.to"
        class="nav-item"
        active-class="active"
        @click="emit('navigate')"
      >
        <div class="nav-icon" :class="primaryLinks.includes(item.label) ? 'icon-primary' : 'icon-secondary'">
          <svg class="icon-svg" viewBox="0 0 24 24" fill="none">
            <path :d="iconFor(item.label)" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" />
          </svg>
        </div>
        <span v-if="!collapsed" class="nav-label-text">{{ item.label }}</span>
      </router-link>
    </nav>

    <!-- Footer -->
    <div v-if="!collapsed" class="footer">
      <div class="footer-badge">
        <div class="footer-dot"></div>
        <p class="footer-label">Workspace ready</p>
      </div>
      <p class="footer-text">
        Role-filtered navigation
      </p>
    </div>
    
    <!-- Collapsed tooltip indicator -->
    <div v-if="collapsed" class="collapsed-hint">
      <div class="hint-dot"></div>
    </div>
  </aside>
</template>

<style scoped lang="postcss">
.sidebar {
  @apply flex flex-col transition-all duration-300;
  background: rgba(255, 255, 255, 0.96);
  backdrop-filter: blur(16px);
  border-radius: 32px;
  height: fit-content;
  min-height: 500px;
}

.sidebar-collapsed {
  @apply items-center px-3;
}

/* Header */
.sidebar-header {
  @apply flex items-center mb-8 pb-4 border-b border-stone-100 px-4 pt-5;
}

.sidebar-collapsed .sidebar-header {
  @apply px-0 pt-5 pb-4 mb-6 border-b border-stone-100;
}

.header-text {
  @apply flex-1 text-center;
}

.subtitle {
  @apply text-[10px] font-medium uppercase tracking-[0.2em];
  color: #a8a29e;
}

.title {
  @apply text-sm font-semibold tracking-tight;
  color: #292524;
}

/* Navigation Label */
.nav-label {
  @apply mb-3 px-4 text-[10px] font-semibold uppercase tracking-[0.2em];
  color: #a8a29e;
}

/* Navigation */
.nav {
  @apply flex flex-col gap-1 px-2;
}

.sidebar-collapsed .nav {
  @apply px-0;
}

.nav-item {
  @apply flex items-center gap-3 rounded-xl transition-all duration-200 cursor-pointer;
  color: #78716c;
}

.nav-item:not(.sidebar-collapsed .nav-item) {
  @apply px-3 py-2.5;
}

.sidebar-collapsed .nav-item {
  @apply justify-center p-2;
}

.nav-item:hover {
  background: rgba(0, 0, 0, 0.03);
  color: #292524;
  transform: translateX(2px);
}

.sidebar-collapsed .nav-item:hover {
  transform: scale(1.05);
  background: rgba(0, 0, 0, 0.05);
}

.nav-item.active {
  background: rgba(44, 95, 110, 0.08);
  color: #2c5f6e;
}

.nav-icon {
  @apply flex h-8 w-8 items-center justify-center rounded-xl transition-all duration-200;
  background: rgba(0, 0, 0, 0.02);
}

.nav-item:hover .nav-icon {
  transform: scale(1.02);
  background: rgba(0, 0, 0, 0.04);
}

.icon-primary {
  color: #e07830;
}

.icon-secondary {
  color: #2c5f6e;
}

.icon-svg {
  @apply h-4 w-4;
}

.nav-label-text {
  @apply text-sm font-medium;
}

/* Footer */
.footer {
  @apply mt-8 pt-4 px-4 border-t border-stone-100;
}

.footer-badge {
  @apply flex items-center gap-2 mb-2;
}

.footer-dot {
  @apply h-1.5 w-1.5 rounded-full;
  background: #2c5f6e;
  animation: pulse 2s ease-in-out infinite;
}

.footer-label {
  @apply text-[10px] font-semibold uppercase tracking-[0.16em];
  color: #a8a29e;
}

.footer-text {
  @apply text-[11px] leading-relaxed;
  color: #a8a29e;
}

/* Collapsed Hint */
.collapsed-hint {
  @apply mt-8 pt-4 border-t border-stone-100 flex justify-center;
}

.hint-dot {
  @apply h-1 w-1 rounded-full;
  background: #a8a29e;
}

/* Animations */
@keyframes pulse {
  0%, 100% {
    opacity: 0.4;
    transform: scale(1);
  }
  50% {
    opacity: 1;
    transform: scale(1.2);
  }
}

/* Responsive */
@media (max-width: 768px) {
  .sidebar {
    border-radius: 24px;
  }
  
  .sidebar-header {
    @apply px-3 pb-3 mb-4;
  }

  .title {
    @apply text-xs;
  }
}
</style>