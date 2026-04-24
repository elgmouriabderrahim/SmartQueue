export type Role = 'admin' | 'institution' | 'citizen' | 'manager' | 'employee'

export interface ApiEnvelope<T> {
  success: boolean
  message: string
  data: T
}

export interface ApiError {
  message: string
  errors?: Record<string, string[]>
  status?: number
}

export interface Paginated<T> {
  current_page: number
  data: T[]
  first_page_url: string | null
  from: number | null
  last_page: number
  last_page_url: string | null
  links: Array<{ url: string | null; label: string; active: boolean }>
  next_page_url: string | null
  path: string
  per_page: number
  prev_page_url: string | null
  to: number | null
  total: number
}

export interface User {
  id: number
  first_name: string
  last_name: string
  email: string
  role: Role
  api_role: 'admin' | 'institution' | 'citizen'
  institution_id?: number | null
  department_id?: number | null
}

export interface AuthPayload {
  user: User
  token: string
}

export interface Institution {
  id: number
  name: string
  slug: string
  city: string
  adress: string
  description?: string
  status: string
  opening_time: string
  closing_time: string
}

export interface ServiceEntity {
  id: number
  institution_id: number
  department_id?: number | null
  name: string
  description: string
  duration: number
  capacity: number
  opening_time: string
  closing_time: string
  status: string
}

export interface Appointment {
  id: number
  user_id: number
  service_id: number
  queue_id?: number | null
  appointment_date: string
  status: string
  queue_position?: number | null
  estimated_waiting_minutes?: number
  reference_code?: string
}

export interface QueuePositionPayload {
  appointment_id: number
  status: string
  queue_id: number | null
  queue_current_position: number | null
  queue_position: number | null
  estimated_waiting_minutes: number
}
