import { computed, ref } from 'vue'
import { defineStore } from 'pinia'
import { createReading, deleteReading, listReadings, updateReading } from '@/api/readings'
import { ApiError, toApiError } from '@/api/client'
import type { Reading, ReadingStatus, UpdateReadingPayload } from '@/types/api'

export const useReadingsStore = defineStore('readings', () => {
  const items = ref<Reading[]>([])
  const loading = ref(false)
  const loaded = ref(false)
  const error = ref<ApiError | null>(null)

  const counts = computed(() => {
    const result: Record<ReadingStatus, number> = { to_read: 0, reading: 0, read: 0, abandoned: 0 }
    for (const reading of items.value) {
      result[reading.status]++
    }
    return result
  })

  async function fetchAll(force = false) {
    if (loading.value || (loaded.value && !force)) {
      return
    }
    loading.value = true
    error.value = null
    try {
      items.value = await listReadings()
      loaded.value = true
    } catch (e) {
      error.value = toApiError(e)
    } finally {
      loading.value = false
    }
  }

  function byStatus(status: ReadingStatus | null) {
    return status === null ? items.value : items.value.filter((r) => r.status === status)
  }

  function findByGutendexId(gutendexId: number) {
    return items.value.find((r) => r.book.gutendexId === gutendexId)
  }

  function putFirst(reading: Reading) {
    items.value = [reading, ...items.value.filter((r) => r.id !== reading.id)]
  }

  async function add(gutendexId: number, status: ReadingStatus) {
    const reading = await createReading({ gutendexId, status })
    putFirst(reading)
    return reading
  }

  async function update(id: number, payload: UpdateReadingPayload) {
    const reading = await updateReading(id, payload)
    putFirst(reading)
    return reading
  }

  async function remove(id: number) {
    await deleteReading(id)
    items.value = items.value.filter((r) => r.id !== id)
  }

  return { items, loading, loaded, error, counts, fetchAll, byStatus, findByGutendexId, add, update, remove }
})
