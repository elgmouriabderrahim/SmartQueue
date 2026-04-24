import { apiClient } from '@/api/client'
import type {
  ApiEnvelope,
  Appointment,
  AuthPayload,
  Institution,
  Paginated,
  QueuePositionPayload,
  ServiceEntity,
  User,
} from '@/api/types'

type AnyRecord = Record<string, unknown>

export const smartQueueApi = {
  home() {
    return apiClient.get<ApiEnvelope<{ app: string; date: string }>>('/home')
  },

  login(payload: { email: string; password: string }) {
    return apiClient.post<ApiEnvelope<AuthPayload>>('/auth/login', payload)
  },

  register(payload: {
    first_name: string
    last_name: string
    email: string
    password: string
    password_confirmation: string
  }) {
    return apiClient.post<ApiEnvelope<AuthPayload>>('/auth/register', payload)
  },

  institutionRequests(params?: { per_page?: number; page?: number }) {
    return apiClient.get<ApiEnvelope<Paginated<AnyRecord>>>('/institution-requests', { params })
  },

  profile() {
    return apiClient.get<ApiEnvelope<AnyRecord>>('/profile')
  },

  updateProfile(payload: AnyRecord) {
    return apiClient.put<ApiEnvelope<AnyRecord>>('/profile', payload)
  },

  createInstitutionRequest(payload: AnyRecord) {
    return apiClient.post<ApiEnvelope<AnyRecord>>('/institution-requests', payload)
  },

  approveInstitutionRequest(id: number) {
    return apiClient.patch<ApiEnvelope<AnyRecord>>(`/institution-requests/${id}/approve`)
  },

  rejectInstitutionRequest(id: number, payload: { reason: string }) {
    return apiClient.patch<ApiEnvelope<AnyRecord>>(`/institution-requests/${id}/reject`, payload)
  },

  institutionStaff(institutionId: number) {
    return apiClient.get<ApiEnvelope<AnyRecord[]>>(`/institutions/${institutionId}/staff`)
  },

  inviteInstitutionEmployee(institutionId: number, payload: { email: string }) {
    return apiClient.post<ApiEnvelope<AnyRecord>>(`/institutions/${institutionId}/staff/invite`, payload)
  },

  removeInstitutionEmployee(institutionId: number, userId: number) {
    return apiClient.delete<ApiEnvelope<AnyRecord>>(`/institutions/${institutionId}/staff/${userId}`)
  },

  leaveInstitution(institutionId: number, payload?: { new_manager_user_id?: number }) {
    return apiClient.post<ApiEnvelope<AnyRecord>>(`/institutions/${institutionId}/staff/leave`, payload)
  },

  transferInstitutionManager(institutionId: number, payload: { new_manager_user_id: number }) {
    return apiClient.post<ApiEnvelope<AnyRecord>>(`/institutions/${institutionId}/staff/transfer-manager`, payload)
  },

  logout() {
    return apiClient.post<ApiEnvelope<null>>('/auth/logout')
  },

  dashboard() {
    return apiClient.get<ApiEnvelope<AnyRecord>>('/dashboard')
  },

  institutions(params?: { per_page?: number; page?: number; city?: string; q?: string }) {
    return apiClient.get<ApiEnvelope<Paginated<Institution>>>('/institutions', { params })
  },

  institutionsMap(params?: { status?: string }) {
    return apiClient.get<ApiEnvelope<Institution[]>>('/institutions/map', { params })
  },

  institution(id: number) {
    return apiClient.get<ApiEnvelope<Institution>>(`/institutions/${id}`)
  },

  createInstitution(payload: AnyRecord) {
    return apiClient.post<ApiEnvelope<Institution>>('/institutions', payload)
  },

  updateInstitution(id: number, payload: AnyRecord) {
    return apiClient.put<ApiEnvelope<Institution>>(`/institutions/${id}`, payload)
  },

  approveInstitution(id: number) {
    return apiClient.patch<ApiEnvelope<Institution>>(`/institutions/${id}/approve`)
  },

  deleteInstitution(id: number) {
    return apiClient.delete<ApiEnvelope<null>>(`/institutions/${id}`)
  },

  services(params?: { per_page?: number; page?: number }) {
    return apiClient.get<ApiEnvelope<Paginated<ServiceEntity>>>('/services', { params })
  },

  service(id: number) {
    return apiClient.get<ApiEnvelope<ServiceEntity>>(`/services/${id}`)
  },

  createService(payload: AnyRecord) {
    return apiClient.post<ApiEnvelope<ServiceEntity>>('/services', payload)
  },

  updateService(id: number, payload: AnyRecord) {
    return apiClient.put<ApiEnvelope<ServiceEntity>>(`/services/${id}`, payload)
  },

  deleteService(id: number) {
    return apiClient.delete<ApiEnvelope<null>>(`/services/${id}`)
  },

  departments(params?: { per_page?: number; page?: number }) {
    return apiClient.get<ApiEnvelope<Paginated<AnyRecord>>>('/departments', { params })
  },

  department(id: number) {
    return apiClient.get<ApiEnvelope<AnyRecord>>(`/departments/${id}`)
  },

  createDepartment(payload: AnyRecord) {
    return apiClient.post<ApiEnvelope<AnyRecord>>('/departments', payload)
  },

  updateDepartment(id: number, payload: AnyRecord) {
    return apiClient.put<ApiEnvelope<AnyRecord>>(`/departments/${id}`, payload)
  },

  deleteDepartment(id: number) {
    return apiClient.delete<ApiEnvelope<null>>(`/departments/${id}`)
  },

  users(params?: { per_page?: number; page?: number }) {
    return apiClient.get<ApiEnvelope<Paginated<User>>>('/users', { params })
  },

  user(id: number) {
    return apiClient.get<ApiEnvelope<User>>(`/users/${id}`)
  },

  createUser(payload: AnyRecord) {
    return apiClient.post<ApiEnvelope<User>>('/users', payload)
  },

  updateUser(id: number, payload: AnyRecord) {
    return apiClient.put<ApiEnvelope<User>>(`/users/${id}`, payload)
  },

  deleteUser(id: number) {
    return apiClient.delete<ApiEnvelope<null>>(`/users/${id}`)
  },

  appointments(params?: { per_page?: number; page?: number }) {
    return apiClient.get<ApiEnvelope<Paginated<Appointment>>>('/appointments', { params })
  },

  appointment(id: number) {
    return apiClient.get<ApiEnvelope<Appointment>>(`/appointments/${id}`)
  },

  createAppointment(payload: AnyRecord) {
    return apiClient.post<ApiEnvelope<Appointment>>('/appointments', payload)
  },

  updateAppointment(id: number, payload: AnyRecord) {
    return apiClient.put<ApiEnvelope<Appointment>>(`/appointments/${id}`, payload)
  },

  deleteAppointment(id: number) {
    return apiClient.delete<ApiEnvelope<Appointment>>(`/appointments/${id}`)
  },

  completeAppointment(id: number) {
    return apiClient.patch<ApiEnvelope<Appointment>>(`/appointments/${id}/complete`)
  },

  appointmentQueuePosition(id: number) {
    return apiClient.get<ApiEnvelope<QueuePositionPayload>>(`/appointments/${id}/queue-position`)
  },

  queues(params?: { per_page?: number; page?: number }) {
    return apiClient.get<ApiEnvelope<Paginated<AnyRecord>>>('/queues', { params })
  },

  queue(id: number) {
    return apiClient.get<ApiEnvelope<AnyRecord>>(`/queues/${id}`)
  },

  createQueue(payload: AnyRecord) {
    return apiClient.post<ApiEnvelope<AnyRecord>>('/queues', payload)
  },

  updateQueue(id: number, payload: AnyRecord) {
    return apiClient.put<ApiEnvelope<AnyRecord>>(`/queues/${id}`, payload)
  },

  deleteQueue(id: number) {
    return apiClient.delete<ApiEnvelope<null>>(`/queues/${id}`)
  },

  notifications() {
    return apiClient.get<ApiEnvelope<Paginated<AnyRecord>>>('/notifications')
  },

  markNotificationRead(id: number) {
    return apiClient.patch<ApiEnvelope<null>>(`/notifications/${id}/read`)
  },

  messages(params?: { per_page?: number; page?: number }) {
    return apiClient.get<ApiEnvelope<Paginated<AnyRecord>>>('/messages', { params })
  },

  message(id: number) {
    return apiClient.get<ApiEnvelope<AnyRecord>>(`/messages/${id}`)
  },

  sendMessage(payload: {
    recipient_id?: number
    institution_id?: number
    content: string
    appointment_id?: number | null
  }) {
    return apiClient.post<ApiEnvelope<AnyRecord>>('/messages', payload)
  },

  updateMessage(id: number, payload: { status: string }) {
    return apiClient.put<ApiEnvelope<AnyRecord>>(`/messages/${id}`, payload)
  },

  deleteMessage(id: number) {
    return apiClient.delete<ApiEnvelope<null>>(`/messages/${id}`)
  },

  ratings(params?: { per_page?: number; page?: number }) {
    return apiClient.get<ApiEnvelope<Paginated<AnyRecord>>>('/ratings', { params })
  },

  rating(id: number) {
    return apiClient.get<ApiEnvelope<AnyRecord>>(`/ratings/${id}`)
  },

  createRating(payload: {
    appointment_id: number
    service_id: number
    score: number
    comment?: string
  }) {
    return apiClient.post<ApiEnvelope<AnyRecord>>('/ratings', payload)
  },

  updateRating(id: number, payload: AnyRecord) {
    return apiClient.put<ApiEnvelope<AnyRecord>>(`/ratings/${id}`, payload)
  },

  deleteRating(id: number) {
    return apiClient.delete<ApiEnvelope<null>>(`/ratings/${id}`)
  },

  analytics(params?: { service_id?: number }) {
    return apiClient.get<ApiEnvelope<AnyRecord>>('/analytics', { params })
  },

  syncAnalytics(payload: { date: string }) {
    return apiClient.post<ApiEnvelope<null>>('/analytics/sync', payload)
  },

  activityLogs(params?: { per_page?: number; page?: number }) {
    return apiClient.get<ApiEnvelope<Paginated<AnyRecord>>>('/activity-logs', { params })
  },

  activityLog(id: number) {
    return apiClient.get<ApiEnvelope<AnyRecord>>(`/activity-logs/${id}`)
  },

  deleteActivityLog(id: number) {
    return apiClient.delete<ApiEnvelope<null>>(`/activity-logs/${id}`)
  },

  serviceCounters(params?: { per_page?: number; page?: number }) {
    return apiClient.get<ApiEnvelope<Paginated<AnyRecord>>>('/service-counters', { params })
  },

  serviceCounter(id: number) {
    return apiClient.get<ApiEnvelope<AnyRecord>>(`/service-counters/${id}`)
  },

  createServiceCounter(payload: AnyRecord) {
    return apiClient.post<ApiEnvelope<AnyRecord>>('/service-counters', payload)
  },

  updateServiceCounter(id: number, payload: AnyRecord) {
    return apiClient.put<ApiEnvelope<AnyRecord>>(`/service-counters/${id}`, payload)
  },

  deleteServiceCounter(id: number) {
    return apiClient.delete<ApiEnvelope<null>>(`/service-counters/${id}`)
  },

  queueEntries(params?: { per_page?: number; page?: number }) {
    return apiClient.get<ApiEnvelope<Paginated<AnyRecord>>>('/queue-entries', { params })
  },

  queueEntry(id: number) {
    return apiClient.get<ApiEnvelope<AnyRecord>>(`/queue-entries/${id}`)
  },

  createQueueEntry(payload: AnyRecord) {
    return apiClient.post<ApiEnvelope<AnyRecord>>('/queue-entries', payload)
  },

  updateQueueEntry(id: number, payload: AnyRecord) {
    return apiClient.put<ApiEnvelope<AnyRecord>>(`/queue-entries/${id}`, payload)
  },

  deleteQueueEntry(id: number) {
    return apiClient.delete<ApiEnvelope<null>>(`/queue-entries/${id}`)
  },

  settings(params?: { per_page?: number; page?: number }) {
    return apiClient.get<ApiEnvelope<Paginated<AnyRecord>>>('/settings', { params })
  },

  setting(id: number) {
    return apiClient.get<ApiEnvelope<AnyRecord>>(`/settings/${id}`)
  },

  createSetting(payload: AnyRecord) {
    return apiClient.post<ApiEnvelope<AnyRecord>>('/settings', payload)
  },

  updateSetting(id: number, payload: AnyRecord) {
    return apiClient.put<ApiEnvelope<AnyRecord>>(`/settings/${id}`, payload)
  },

  deleteSetting(id: number) {
    return apiClient.delete<ApiEnvelope<null>>(`/settings/${id}`)
  },
}
