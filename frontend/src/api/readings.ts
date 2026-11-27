import { request } from './client'
import type { CreateReadingPayload, Reading, ReadingStatus, UpdateReadingPayload } from '@/types/api'

export function listReadings(status?: ReadingStatus) {
  return request<Reading[]>('/api/readings', { query: { status } })
}

export function createReading(payload: CreateReadingPayload) {
  return request<Reading>('/api/readings', { method: 'POST', body: payload })
}

export function updateReading(id: number, payload: UpdateReadingPayload) {
  return request<Reading>(`/api/readings/${id}`, { method: 'PATCH', body: payload })
}

export function deleteReading(id: number) {
  return request<void>(`/api/readings/${id}`, { method: 'DELETE' })
}
