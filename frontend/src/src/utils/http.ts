import axios from 'axios'
import type { ApiError } from '@/api/types'

export function toApiError(error: unknown): ApiError {
  if (axios.isAxiosError(error)) {
    const status = error.response?.status
    const payload = error.response?.data as { message?: string; data?: unknown } | undefined

    if (payload && typeof payload.data === 'object' && payload.data) {
      return {
        message: payload.message || error.message,
        errors: payload.data as Record<string, string[]>,
        status,
      }
    }

    return {
      message: payload?.message || error.message,
      status,
    }
  }

  if (error instanceof Error) {
    return { message: error.message }
  }

  return { message: 'An unexpected error occurred.' }
}
