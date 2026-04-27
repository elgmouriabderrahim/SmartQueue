<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import AppSidebar from '@/components/layout/AppSidebar.vue'
import AppTopbar from '@/components/layout/AppTopbar.vue'
import { smartQueueApi } from '@/services/smartQueueApi'
import { toApiError } from '@/utils/http'

const authStore = useAuthStore()
const router = useRouter()
const notificationsOpen = ref(false)
const sidebarOpen = ref(true)
const notifications = ref<any[]>([])
const notificationsError = ref('')

const navItems = computed(() => {
  const role = authStore.userRole

  if (role === 'admin') {
    return [
      { to: '/app/admin/dashboard', label: 'Dashboard' },
      { to: '/app/admin/profile', label: 'Profile' },
      { to: '/app/admin/institution-requests', label: 'Requests' },
      { to: '/app/admin/users', label: 'Users' },
      { to: '/app/admin/logs', label: 'Logs' },
      { to: '/app/admin/analytics', label: 'Analytics' },
    ]
  }

  if (role === 'manager') {
    return [
      { to: '/app/manager/dashboard', label: 'Dashboard' },
      { to: '/app/manager/services', label: 'Services' },
      { to: '/app/manager/departments', label: 'Departments' },
      { to: '/app/manager/employees', label: 'Team' },
      { to: '/app/manager/appointments', label: 'Appointments' },
      { to: '/app/manager/queues', label: 'Queues' },
      { to: '/app/manager/service-counters', label: 'Counters' },
      { to: '/app/manager/queue-entries', label: 'Queue Entries' },
      { to: '/app/manager/messages', label: 'Messages' },
      { to: '/app/manager/settings', label: 'Settings' },
      { to: '/app/manager/analytics', label: 'Analytics' },
    ]
  }

  if (role === 'employee') {
    return [
      { to: '/app/employee/dashboard', label: 'Dashboard' },
      { to: '/app/employee/appointments', label: 'Appointments' },
      { to: '/app/employee/queues', label: 'Queues' },
      { to: '/app/employee/service-counters', label: 'Counters' },
      { to: '/app/employee/queue-entries', label: 'Queue Entries' },
      { to: '/app/employee/messages', label: 'Messages' },
      { to: '/app/employee/profile', label: 'Profile' },
    ]
  }

  return [
    { to: '/app/citizen/dashboard', label: 'Dashboard' },
    { to: '/app/citizen/appointments', label: 'Appointments' },
    { to: '/app/citizen/appointments-history', label: 'Appointments History' },
    { to: '/app/citizen/institution-requests', label: 'Institution Request' },
    { to: '/app/citizen/messages', label: 'Messages' },
    { to: '/app/citizen/notifications', label: 'Alerts' },
    { to: '/app/citizen/profile', label: 'Profile' },
  ]
})

const roleLabel = computed(() => authStore.userRole || authStore.role || 'user')
const userName = computed(() => `${authStore.user?.first_name || ''} ${authStore.user?.last_name || ''}`.trim())

const dashboardTitle = computed(() => {
  if (authStore.userRole === 'admin') {
    return { title: 'Admin', subtitle: 'Control Center' }
  }
  if (authStore.userRole === 'manager') {
    return { title: 'Manager', subtitle: 'Institution Hub' }
  }
  if (authStore.userRole === 'employee') {
    return { title: 'Staff', subtitle: 'Service Desk' }
  }
  return { title: 'Citizen', subtitle: 'Your Dashboard' }
})

const unreadCount = computed(() => notifications.value.filter((item) => !item.is_read).length)

function toggleSidebar() {
  sidebarOpen.value = !sidebarOpen.value
}

async function loadNotifications(): Promise<void> {
  notificationsError.value = ''
  try {
    const response = await smartQueueApi.notifications()
    notifications.value = (response.data.data.data || []).slice(0, 5)
  } catch (err) {
    notificationsError.value = toApiError(err).message
  }
}

async function handleLogout() {
  await authStore.logout()
  await router.replace('/auth/login')
}

function openProfile(): void {
  const role = authStore.userRole
  if (role === 'admin') router.push('/app/admin/profile')
  else if (role === 'manager') router.push('/app/manager/dashboard')
  else if (role === 'employee') router.push('/app/employee/profile')
  else router.push('/app/citizen/profile')
}

function closeMobileSidebar(): void {
  sidebarOpen.value = false
}

onMounted(loadNotifications)
</script>

<template>
  <div class="min-h-screen bg-white">
    <AppTopbar
      :role-label="roleLabel"
      :user-name="userName"
      :unread-count="unreadCount"
      @toggle-menu="toggleSidebar"
      @toggle-notifications="notificationsOpen = !notificationsOpen"
      @profile="openProfile"
      @logout="handleLogout"
    />

    <!-- Main Layout - Sidebar + Content -->
    <div class="flex">
      <!-- Desktop Sidebar - Collapsible -->
      <aside 
        class="hidden lg:block border-r border-stone-100 bg-white/50 backdrop-blur-sm transition-all duration-300"
        :class="sidebarOpen ? 'w-64' : 'w-20'"
      >
        <div class="sticky top-[73px] h-[calc(100vh-73px)] overflow-y-auto py-6">
          <!-- Collapse Toggle Button inside sidebar -->
          <div class="mb-6 flex justify-end px-4">
            <button
              @click="toggleSidebar"
              class="rounded-full p-2 text-stone-400 hover:bg-stone-100 transition-colors"
            >
              <svg 
                class="h-5 w-5 transition-transform duration-300"
                :class="sidebarOpen ? 'rotate-0' : 'rotate-180'"
                viewBox="0 0 24 24" fill="none"
              >
                <path d="M15 6l-6 6 6 6" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
              </svg>
            </button>
          </div>
          
          <AppSidebar 
            :title="dashboardTitle.title" 
            :subtitle="dashboardTitle.subtitle" 
            :items="navItems"
            :collapsed="!sidebarOpen"
          />
        </div>
      </aside>

      <!-- Main Content - Expands to full width when sidebar collapsed -->
      <main class="flex-1 min-w-0">
        <!-- Welcome Bar -->
        <div class="border-b border-stone-100 bg-white/50">
          <div class="px-6 py-8 lg:px-8">
            <div class="flex items-center justify-between">
              <div>
                <h1 class="text-3xl font-light tracking-tight text-stone-800">
                  {{ dashboardTitle.title }}
                </h1>
                <p class="mt-1 text-stone-500">{{ dashboardTitle.subtitle }}</p>
              </div>
              <div class="hidden sm:block">
                <div class="text-right">
                  <p class="text-sm text-stone-400">Welcome back</p>
                  <p class="text-sm font-medium text-stone-600">{{ userName || 'User' }}</p>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Content Area -->
        <div class="px-6 py-8 lg:px-8">
          <div class="flex gap-8">
            <!-- Main Router View -->
            <div class="flex-1 min-w-0">
              <router-view />
            </div>

            <!-- Right Panel -->
            <aside class="hidden w-80 shrink-0 xl:block">
              <div class="sticky top-24 space-y-6">
                <!-- Quick Stats -->
                <div class="rounded-2xl bg-white/60 backdrop-blur-sm p-5">
                  <p class="text-xs font-semibold uppercase tracking-wider text-stone-400">Today</p>
                  <div class="mt-4 space-y-3">
                    <div class="flex justify-between border-b border-stone-100 pb-2">
                      <span class="text-sm text-stone-500">Notifications</span>
                      <span class="text-sm font-semibold text-stone-700">{{ unreadCount }}</span>
                    </div>
                    <div class="flex justify-between border-b border-stone-100 pb-2">
                      <span class="text-sm text-stone-500">Role</span>
                      <span class="text-sm font-semibold text-stone-700 capitalize">{{ roleLabel }}</span>
                    </div>
                    <div class="flex justify-between">
                      <span class="text-sm text-stone-500">Status</span>
                      <span class="text-sm font-semibold text-emerald-600">Active</span>
                    </div>
                  </div>
                </div>

                <!-- Notifications Dropdown -->
                <transition
                  enter-active-class="duration-300 ease-out"
                  enter-from-class="translate-y-2 opacity-0"
                  enter-to-class="translate-y-0 opacity-100"
                  leave-active-class="duration-200 ease-in"
                  leave-from-class="translate-y-0 opacity-100"
                  leave-to-class="translate-y-2 opacity-0"
                >
                  <div v-if="notificationsOpen" class="rounded-2xl bg-white/60 backdrop-blur-sm p-5">
                    <p class="text-xs font-semibold uppercase tracking-wider text-stone-400">Latest Updates</p>
                    <div class="mt-4 space-y-3">
                      <p v-if="notificationsError" class="text-sm text-rose-500">{{ notificationsError }}</p>
                      <div v-else-if="notifications.length > 0" class="space-y-3">
                        <div v-for="item in notifications" :key="Number(item.id)" class="border-b border-stone-100 pb-2 last:border-0">
                          <p class="text-sm font-medium text-stone-700">{{ item.title }}</p>
                          <p class="mt-0.5 text-xs text-stone-400">{{ item.message }}</p>
                        </div>
                      </div>
                      <p v-else class="text-sm text-stone-400">No new updates</p>
                    </div>
                  </div>
                </transition>

                <!-- Tip -->
                <div class="rounded-2xl bg-amber-50/50 p-5">
                  <p class="text-xs font-semibold uppercase tracking-wider text-amber-600">Pro Tip</p>
                  <p class="mt-2 text-sm text-stone-600">Click the arrow to collapse the sidebar for more space.</p>
                </div>
              </div>
            </aside>
          </div>
        </div>
      </main>
    </div>

    <!-- Mobile Sidebar (Slide-in) -->
    <transition
      enter-active-class="duration-300 ease-out"
      enter-from-class="-translate-x-full"
      enter-to-class="translate-x-0"
      leave-active-class="duration-250 ease-in"
      leave-from-class="translate-x-0"
      leave-to-class="-translate-x-full"
    >
      <div v-if="sidebarOpen" class="fixed inset-0 z-50 bg-white lg:hidden">
        <div class="flex flex-col h-full">
          <div class="flex justify-between items-center p-4 border-b border-stone-100">
            <span class="font-medium text-stone-600">Menu</span>
            <button @click="closeMobileSidebar" class="p-2 text-stone-400">
              <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none">
                <path d="M6 18L18 6M6 6l12 12" stroke="currentColor" stroke-width="1.5"/>
              </svg>
            </button>
          </div>
          <div class="flex-1 overflow-y-auto p-4">
            <AppSidebar
              :title="dashboardTitle.title"
              :subtitle="dashboardTitle.subtitle"
              :items="navItems"
              @navigate="closeMobileSidebar"
            />
          </div>
        </div>
      </div>
    </transition>
  </div>
</template>