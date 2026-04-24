import { computed, ref } from 'vue'
import { defineStore } from 'pinia'
import type { User } from '@/api/types'
import { smartQueueApi } from '@/services/smartQueueApi'
import { disconnectEcho, initEcho } from '@/services/realtime'
import { clearAuthStorage, getStoredToken, getStoredUser, setStoredToken, setStoredUser } from '@/utils/storage'

export const useAuthStore = defineStore('auth', () => {
  const user = ref<User | null>(getStoredUser<User>())
  const token = ref<string | null>(getStoredToken())

  const isAuthenticated = computed(() => Boolean(token.value && user.value))
  const role = computed(() => user.value?.api_role ?? null)
  const userRole = computed(() => user.value?.role ?? null)

  function setSession(nextUser: User, nextToken: string): void {
    user.value = nextUser
    token.value = nextToken
    setStoredUser(nextUser)
    setStoredToken(nextToken)
    initEcho(nextToken)
  }

  function clearSession(): void {
    user.value = null
    token.value = null
    disconnectEcho()
    clearAuthStorage()
  }

  function setCurrentUser(nextUser: User): void {
    user.value = nextUser
    setStoredUser(nextUser)
  }

  async function login(payload: { email: string; password: string }): Promise<string> {
    const response = await smartQueueApi.login(payload)
    setSession(response.data.data.user, response.data.data.token)
    return response.data.message
  }

  async function register(payload: {
    first_name: string
    last_name: string
    email: string
    password: string
    password_confirmation: string
  }): Promise<string> {
    const response = await smartQueueApi.register(payload)
    setSession(response.data.data.user, response.data.data.token)
    return response.data.message
  }

  async function logout(): Promise<void> {
    try {
      await smartQueueApi.logout()
    } finally {
      clearSession()
    }
  }

  return {
    user,
    token,
    role,
    userRole,
    isAuthenticated,
    login,
    register,
    logout,
    clearSession,
    setCurrentUser,
  }
})
