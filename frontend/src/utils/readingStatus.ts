import type { ReadingStatus } from '@/types/api'

export const STATUS_ORDER: ReadingStatus[] = ['to_read', 'reading', 'read', 'abandoned']

export const STATUS_LABELS: Record<ReadingStatus, string> = {
  to_read: 'À lire',
  reading: 'En cours',
  read: 'Lu',
  abandoned: 'Abandonné',
}
