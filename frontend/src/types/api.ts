export type ReadingStatus = 'to_read' | 'reading' | 'read' | 'abandoned'

export interface CatalogBook {
  id: number
  title: string
  authors: string[]
  languages: string[]
  coverUrl: string | null
  subjects: string[]
  summaries: string[]
  downloadCount: number
}

export interface CatalogSearchResult {
  count: number
  page: number
  hasNext: boolean
  hasPrevious: boolean
  books: CatalogBook[]
}

export interface BookDetail {
  book: CatalogBook
  reading: { id: number; status: ReadingStatus } | null
}

export interface TrackedBook {
  gutendexId: number
  title: string
  authors: string[]
  coverUrl: string | null
  languages: string[]
}

export interface Reading {
  id: number
  book: TrackedBook
  status: ReadingStatus
  startedAt: string | null
  finishedAt: string | null
  rating: number | null
  comment: string | null
  createdAt: string
  updatedAt: string
}

export interface CreateReadingPayload {
  gutendexId: number
  status?: ReadingStatus
}

export interface UpdateReadingPayload {
  status?: ReadingStatus
  startedAt?: string | null
  finishedAt?: string | null
  rating?: number | null
  comment?: string | null
}

export interface ApiErrorBody {
  error: {
    status: number
    message: string
    details?: Record<string, string>
  }
}
