import { request } from './client'
import type { BookDetail, CatalogSearchResult } from '@/types/api'

export function searchBooks(search: string, page: number, signal?: AbortSignal) {
  return request<CatalogSearchResult>('/api/books', { query: { search, page }, signal })
}

export function getBook(gutendexId: number) {
  return request<BookDetail>(`/api/books/${gutendexId}`)
}
