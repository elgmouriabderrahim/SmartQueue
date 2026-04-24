import type { User } from '@/api/types'

export function getHomeByRole(user: User | null): string {
  if (!user) {
    return '/auth/login'
  }

  if (user.role === 'admin') {
    return '/app/admin/dashboard'
  }

  if (user.role === 'manager') {
    return '/app/manager/dashboard'
  }

  if (user.role === 'employee') {
    return '/app/employee/dashboard'
  }

  return '/app/citizen/dashboard'
}
