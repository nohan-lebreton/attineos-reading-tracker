<script setup lang="ts">
import { computed, onBeforeUnmount, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { searchBooks } from '@/api/books'
import { ApiError, toApiError } from '@/api/client'
import type { CatalogSearchResult } from '@/types/api'
import { useDebouncedRef } from '@/composables/useDebounce'
import { numberFormat } from '@/utils/format'
import AsyncState from '@/components/AsyncState.vue'
import BookCard from '@/components/BookCard.vue'
import PaginationNav from '@/components/PaginationNav.vue'

const route = useRoute()
const router = useRouter()

const query = computed(() => (typeof route.query.q === 'string' ? route.query.q.trim() : ''))
const page = computed(() => {
  const value = Number(route.query.page)
  return Number.isInteger(value) && value > 0 ? value : 1
})

const term = ref(query.value)
const debouncedTerm = useDebouncedRef(term, 400)

const result = ref<CatalogSearchResult | null>(null)
const loading = ref(false)
const error = ref<ApiError | null>(null)
let controller: AbortController | null = null

watch(debouncedTerm, (value) => {
  const q = value.trim()
  if (q !== query.value) {
    router.replace({ query: q ? { q } : {} })
  }
})

watch(query, (q) => {
  if (q !== term.value.trim()) {
    term.value = q
  }
})

async function load() {
  controller?.abort()
  const current = new AbortController()
  controller = current

  loading.value = true
  error.value = null
  try {
    result.value = await searchBooks(query.value, page.value, current.signal)
  } catch (e) {
    if (current.signal.aborted) {
      return
    }
    result.value = null
    error.value = toApiError(e)
  } finally {
    if (controller === current) {
      loading.value = false
    }
  }
}

watch([query, page], load, { immediate: true })

onBeforeUnmount(() => controller?.abort())

function goToPage(value: number) {
  router.push({ query: { ...route.query, page: String(value) } })
}
</script>

<template>
  <section class="page">
    <header class="page-header">
      <h1 class="page-title">Rechercher un livre</h1>
      <p class="page-subtitle">Plus de 70 000 œuvres du domaine public, issues du projet Gutenberg.</p>
    </header>

    <div class="search" role="search">
      <svg class="search-icon" viewBox="0 0 20 20" aria-hidden="true">
        <circle cx="8.5" cy="8.5" r="6" fill="none" stroke="currentColor" stroke-width="2" />
        <path d="M13 13l5 5" stroke="currentColor" stroke-width="2" stroke-linecap="round" />
      </svg>
      <label for="search" class="visually-hidden">Titre ou auteur</label>
      <input
        id="search"
        v-model="term"
        type="search"
        class="search-input"
        placeholder="Titre ou auteur"
        autocomplete="off"
        enterkeyhint="search"
      />
    </div>

    <div v-if="result && !loading && !error" class="results-header">
      <h2 class="section-title">
        {{ query ? `Résultats pour « ${query} »` : 'Les plus téléchargés' }}
      </h2>
      <span class="results-count">{{ numberFormat.format(result.count) }} livres</span>
    </div>

    <AsyncState
      :loading="loading"
      :error="error"
      :empty="result?.books.length === 0"
      empty-title="Aucun livre trouvé"
      empty-text="Essayez un autre titre ou un nom d’auteur."
      @retry="load"
    >
      <template v-if="result">
        <ul class="grid">
          <li v-for="book in result.books" :key="book.id">
            <BookCard :book="book" />
          </li>
        </ul>
        <PaginationNav
          :page="page"
          :has-previous="result.hasPrevious"
          :has-next="result.hasNext"
          @change="goToPage"
        />
      </template>
    </AsyncState>
  </section>
</template>

<style scoped>
.search {
  position: relative;
  max-width: 640px;
}

.search-icon {
  position: absolute;
  left: 16px;
  top: 50%;
  width: 18px;
  height: 18px;
  transform: translateY(-50%);
  color: var(--text-tertiary);
  pointer-events: none;
}

.search-input {
  width: 100%;
  height: 52px;
  padding: 0 18px 0 46px;
  border: 1px solid transparent;
  border-radius: var(--radius);
  background: var(--surface);
  box-shadow: var(--shadow);
  color: var(--text);
  font: inherit;
  font-size: 17px;
}

.search-input:focus {
  outline: none;
  border-color: var(--accent);
  box-shadow: 0 0 0 4px var(--accent-soft);
}

.results-header {
  display: flex;
  align-items: baseline;
  justify-content: space-between;
  gap: 12px;
  flex-wrap: wrap;
}

.results-count {
  font-size: 14px;
  color: var(--text-secondary);
  font-variant-numeric: tabular-nums;
}

.grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
  gap: 20px;
  margin: 0;
  padding: 0;
  list-style: none;
}

@media (max-width: 480px) {
  .grid {
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 12px;
  }
}
</style>
