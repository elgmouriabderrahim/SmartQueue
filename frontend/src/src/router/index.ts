import { createRouter, createWebHistory } from 'vue-router'
import AuthLayout from '@/layouts/AuthLayout.vue'
import DashboardLayout from '@/layouts/DashboardLayout.vue'
import PublicLayout from '@/layouts/PublicLayout.vue'
import HomePage from '@/pages/public/Home.vue'
import LoginPage from '@/pages/auth/LoginPage.vue'
import RegisterPage from '@/pages/auth/RegisterPage.vue'
import NotFoundPage from '@/pages/NotFoundPage.vue'
import { useAuthStore } from '@/stores/auth'
import { getHomeByRole } from '@/utils/roles'

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes: [
    {
      path: '/',
      component: PublicLayout,
      children: [
        { path: '', name: 'home', component: HomePage },
        { path: 'institutions', name: 'public-institutions', component: () => import('@/pages/public/PublicInstitutionsPage.vue') },
        { path: 'institutions/:id', name: 'institution-details', component: () => import('@/pages/public/InstitutionDetailsPage.vue') },
        { path: 'services', name: 'public-services', component: () => import('@/pages/public/PublicServicesPage.vue') },
        { path: 'services/:id', name: 'service-details', component: () => import('@/pages/public/ServiceDetailsPage.vue') },
      ],
    },
    {
      path: '/auth',
      component: AuthLayout,
      meta: { guestOnly: true },
      children: [
        { path: 'login', name: 'login', component: LoginPage },
        { path: 'register', name: 'register', component: RegisterPage },
      ],
    },
    {
      path: '/app',
      component: DashboardLayout,
      meta: { requiresAuth: true },
      children: [
        { path: '', redirect: '/app/citizen/dashboard' },

        { path: 'citizen/dashboard', name: 'citizen-dashboard', component: () => import('@/pages/citizen/DashboardPage.vue'), meta: { roles: ['citizen'] } },
        { path: 'citizen/profile', name: 'citizen-profile', component: () => import('@/pages/citizen/ProfilePage.vue'), meta: { roles: ['citizen'] } },
        { path: 'citizen/appointments', name: 'citizen-appointments', component: () => import('@/pages/citizen/AppointmentsPage.vue'), meta: { roles: ['citizen'] } },
        { path: 'citizen/messages', name: 'citizen-messages', component: () => import('@/pages/citizen/MessagesPage.vue'), meta: { roles: ['citizen'] } },
        { path: 'citizen/notifications', name: 'citizen-notifications', component: () => import('@/pages/citizen/NotificationsPage.vue'), meta: { roles: ['citizen'] } },

        { path: 'employee/dashboard', name: 'employee-dashboard', component: () => import('@/pages/employee/DashboardPage.vue'), meta: { roles: ['employee', 'manager'] } },
        { path: 'employee/profile', name: 'employee-profile', component: () => import('@/pages/employee/ProfilePage.vue'), meta: { roles: ['employee', 'manager'] } },
        { path: 'employee/appointments', name: 'employee-appointments', component: () => import('@/pages/employee/AppointmentsPage.vue'), meta: { roles: ['employee', 'manager'] } },
        { path: 'employee/queues', name: 'employee-queues', component: () => import('@/pages/employee/QueuesPage.vue'), meta: { roles: ['employee', 'manager'] } },
        { path: 'employee/messages', name: 'employee-messages', component: () => import('@/pages/employee/MessagesPage.vue'), meta: { roles: ['employee', 'manager'] } },

        { path: 'manager/dashboard', name: 'manager-dashboard', component: () => import('@/pages/manager/DashboardPage.vue'), meta: { roles: ['manager'] } },
        { path: 'manager/services', name: 'manager-services', component: () => import('@/pages/manager/ServicesPage.vue'), meta: { roles: ['manager'] } },
        { path: 'manager/departments', name: 'manager-departments', component: () => import('@/pages/manager/DepartmentsPage.vue'), meta: { roles: ['manager'] } },
        { path: 'manager/employees', name: 'manager-employees', component: () => import('@/pages/manager/EmployeesPage.vue'), meta: { roles: ['manager'] } },
        { path: 'manager/appointments', name: 'manager-appointments', component: () => import('@/pages/manager/AppointmentsPage.vue'), meta: { roles: ['manager'] } },
        { path: 'manager/queues', name: 'manager-queues', component: () => import('@/pages/manager/QueuesPage.vue'), meta: { roles: ['manager'] } },
        { path: 'manager/service-counters', name: 'manager-service-counters', component: () => import('@/pages/manager/ServiceCountersPage.vue'), meta: { roles: ['manager'] } },
        { path: 'manager/queue-entries', name: 'manager-queue-entries', component: () => import('@/pages/manager/QueueEntriesPage.vue'), meta: { roles: ['manager'] } },
        { path: 'manager/messages', name: 'manager-messages', component: () => import('@/pages/manager/MessagesPage.vue'), meta: { roles: ['manager'] } },
        { path: 'manager/settings', name: 'manager-settings', component: () => import('@/pages/manager/SettingsPage.vue'), meta: { roles: ['manager'] } },
        { path: 'manager/analytics', name: 'manager-analytics', component: () => import('@/pages/manager/AnalyticsPage.vue'), meta: { roles: ['manager'] } },

        { path: 'admin/dashboard', name: 'admin-dashboard', component: () => import('@/pages/admin/DashboardPage.vue'), meta: { roles: ['admin'] } },
        { path: 'admin/profile', name: 'admin-profile', component: () => import('@/pages/admin/ProfilePage.vue'), meta: { roles: ['admin'] } },
        { path: 'admin/users', name: 'admin-users', component: () => import('@/pages/admin/UsersPage.vue'), meta: { roles: ['admin'] } },
        { path: 'admin/institution-requests', name: 'admin-institution-requests', component: () => import('@/pages/admin/InstitutionRequestsPage.vue'), meta: { roles: ['admin'] } },
        { path: 'admin/logs', name: 'admin-logs', component: () => import('@/pages/admin/LogsPage.vue'), meta: { roles: ['admin'] } },
        { path: 'admin/analytics', name: 'admin-analytics', component: () => import('@/pages/admin/AnalyticsPage.vue'), meta: { roles: ['admin'] } },
        { path: 'admin/settings', name: 'admin-settings', component: () => import('@/pages/admin/SettingsPage.vue'), meta: { roles: ['admin'] } },
      ],
    },

    {
      path: '/dashboard',
      redirect: '/app',
    },
    {
      path: '/profile',
      redirect: '/app',
    },
    {
      path: '/appointments',
      redirect: '/app',
    },
    {
      path: '/messages',
      redirect: '/app',
    },
    {
      path: '/ratings',
      redirect: '/services',
    },
    {
      path: '/queues',
      redirect: '/app',
    },
    {
      path: '/notifications',
      redirect: '/app',
    },
    {
      path: '/analytics',
      redirect: '/app',
    },
    {
      path: '/users',
      redirect: '/app',
    },
    {
      path: '/institution-requests',
      redirect: '/app',
    },
    {
      path: '/manager',
      redirect: '/app',
    },
    {
      path: '/manager/:pathMatch(.*)*',
      redirect: '/app',
    },
    { path: '/:pathMatch(.*)*', name: 'not-found', component: NotFoundPage },
  ],
})

router.beforeEach((to) => {
  const authStore = useAuthStore()
  const requiresAuth = Boolean(to.meta.requiresAuth)
  const guestOnly = Boolean(to.meta.guestOnly)
  const roles = (to.meta.roles as Array<'admin' | 'citizen' | 'manager' | 'employee' | 'institution'> | undefined) ?? []

  if (requiresAuth && !authStore.isAuthenticated) {
    return '/auth/login'
  }

  if (guestOnly && authStore.isAuthenticated) {
    return getHomeByRole(authStore.user)
  }

  if (roles.length > 0 && authStore.userRole && !roles.includes(authStore.userRole)) {
    return getHomeByRole(authStore.user)
  }

  return true
})

export default router
