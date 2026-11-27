<script setup lang="ts">
import { RouterLink } from 'vue-router'
import type { CatalogBook } from '@/types/api'
import { formatAuthors } from '@/utils/format'
import BookCover from './BookCover.vue'

defineProps<{ book: CatalogBook }>()
</script>

<template>
  <RouterLink :to="{ name: 'book', params: { id: book.id } }" class="card">
    <BookCover :src="book.coverUrl" :title="book.title" />
    <div class="card-body">
      <h3 class="card-title">{{ book.title }}</h3>
      <p class="card-authors">{{ formatAuthors(book.authors) }}</p>
    </div>
  </RouterLink>
</template>

<style scoped>
.card {
  display: flex;
  flex-direction: column;
  gap: 12px;
  height: 100%;
  padding: 12px 12px 16px;
  border-radius: var(--radius-lg);
  background: var(--surface);
  box-shadow: var(--shadow);
  color: var(--text);
  transition:
    transform 0.25s ease,
    box-shadow 0.25s ease;
}

.card:hover {
  text-decoration: none;
  transform: translateY(-2px);
  box-shadow: var(--shadow-hover);
}

.card-body {
  display: flex;
  flex-direction: column;
  gap: 2px;
  min-width: 0;
}

.card-title {
  font-size: 15px;
  font-weight: 600;
  line-height: 1.3;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.card-authors {
  font-size: 13px;
  color: var(--text-secondary);
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}
</style>
