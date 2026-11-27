<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import { RouterLink, useRouter } from 'vue-router'
import { getBook } from '@/api/books'
import { ApiError, toApiError } from '@/api/client'
import type { BookDetail, ReadingStatus } from '@/types/api'
import { useReadingsStore } from '@/stores/readings'
import { STATUS_LABELS, STATUS_ORDER } from '@/utils/readingStatus'
import { formatAuthors, formatLanguage, numberFormat } from '@/utils/format'
import AsyncState from '@/components/AsyncState.vue'
import BookCover from '@/components/BookCover.vue'
import StatusBadge from '@/components/StatusBadge.vue'

const props = defineProps<{ id: number }>()

const router = useRouter()
const readings = useReadingsStore()

const detail = ref<BookDetail | null>(null)
const loading = ref(false)
const error = ref<ApiError | null>(null)

const newStatus = ref<ReadingStatus>('to_read')
const adding = ref(false)
const addError = ref<string | null>(null)

const tracked = computed(() => readings.findByGutendexId(props.id))
const currentStatus = computed(() => tracked.value?.status ?? detail.value?.reading?.status ?? null)
const showOfflineFallback = computed(() => error.value?.status === 503 && tracked.value !== undefined)
const gutenbergUrl = computed(() => `https://www.gutenberg.org/ebooks/${props.id}`)

async function load() {
  loading.value = true
  error.value = null
  try {
    detail.value = await getBook(props.id)
  } catch (e) {
    detail.value = null
    error.value = toApiError(e)
  } finally {
    loading.value = false
  }
}

watch(() => props.id, load, { immediate: true })

async function add() {
  adding.value = true
  addError.value = null
  try {
    await readings.add(props.id, newStatus.value)
  } catch (e) {
    const err = toApiError(e)
    if (err.status === 409) {
      await readings.fetchAll(true)
    } else {
      addError.value = err.message
    }
  } finally {
    adding.value = false
  }
}

function goBack() {
  if (window.history.state?.back) {
    router.back()
  } else {
    router.push({ name: 'search' })
  }
}
</script>

<template>
  <section class="page">
    <div>
      <button type="button" class="btn btn-plain back" @click="goBack">‹ Retour</button>
    </div>

    <article v-if="showOfflineFallback && tracked" class="book">
      <BookCover :src="tracked.book.coverUrl" :title="tracked.book.title" size="lg" />
      <div class="book-info">
        <p class="notice" role="status">
          Le catalogue ne répond pas pour le moment. Voici les informations enregistrées dans vos
          lectures.
        </p>
        <header class="book-header">
          <h1 class="page-title">{{ tracked.book.title }}</h1>
          <p class="book-authors">{{ formatAuthors(tracked.book.authors) }}</p>
        </header>
        <div class="panel surface">
          <div class="panel-row">
            <span>Dans vos lectures</span>
            <StatusBadge :status="tracked.status" />
          </div>
          <div class="panel-actions">
            <RouterLink :to="{ name: 'readings' }" class="btn btn-secondary">Voir mes lectures</RouterLink>
            <button type="button" class="btn btn-plain" @click="load">Réessayer</button>
          </div>
        </div>
      </div>
    </article>

    <AsyncState v-else :loading="loading" :error="error" @retry="load">
      <article v-if="detail" class="book">
        <BookCover :src="detail.book.coverUrl" :title="detail.book.title" size="lg" />

        <div class="book-info">
          <header class="book-header">
            <h1 class="page-title">{{ detail.book.title }}</h1>
            <p class="book-authors">{{ formatAuthors(detail.book.authors) }}</p>
          </header>

          <dl class="meta">
            <div v-if="detail.book.languages.length">
              <dt>Langue</dt>
              <dd>{{ detail.book.languages.map(formatLanguage).join(', ') }}</dd>
            </div>
            <div>
              <dt>Téléchargements</dt>
              <dd>{{ numberFormat.format(detail.book.downloadCount) }}</dd>
            </div>
          </dl>

          <div class="panel surface">
            <template v-if="currentStatus">
              <div class="panel-row">
                <span>Dans vos lectures</span>
                <StatusBadge :status="currentStatus" />
              </div>
              <div class="panel-actions">
                <RouterLink :to="{ name: 'readings' }" class="btn btn-secondary">
                  Voir mes lectures
                </RouterLink>
              </div>
            </template>
            <form v-else class="add-form" @submit.prevent="add">
              <div class="field">
                <label for="new-status" class="field-label">Statut</label>
                <select id="new-status" v-model="newStatus" class="input">
                  <option v-for="status in STATUS_ORDER" :key="status" :value="status">
                    {{ STATUS_LABELS[status] }}
                  </option>
                </select>
              </div>
              <button type="submit" class="btn btn-primary" :disabled="adding">
                {{ adding ? 'Ajout…' : 'Ajouter à mes lectures' }}
              </button>
              <p v-if="addError" class="field-error" role="alert">{{ addError }}</p>
            </form>
          </div>

          <section v-if="detail.book.summaries.length" class="block">
            <h2 class="section-title">Résumé</h2>
            <p v-for="(summary, index) in detail.book.summaries" :key="index" class="summary">
              {{ summary }}
            </p>
          </section>

          <section v-if="detail.book.subjects.length" class="block">
            <h2 class="section-title">Thèmes</h2>
            <ul class="chips">
              <li v-for="subject in detail.book.subjects" :key="subject" class="chip">{{ subject }}</li>
            </ul>
          </section>

          <a :href="gutenbergUrl" target="_blank" rel="noopener noreferrer" class="external">
            Lire gratuitement sur le projet Gutenberg ↗
          </a>
        </div>
      </article>
    </AsyncState>
  </section>
</template>

<style scoped>
.back {
  margin-left: -4px;
  font-size: 15px;
}

.book {
  display: grid;
  grid-template-columns: auto minmax(0, 1fr);
  gap: 48px;
  align-items: start;
}

.book-info {
  display: flex;
  flex-direction: column;
  gap: 24px;
  min-width: 0;
}

.book-header {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.book-authors {
  font-size: 21px;
  color: var(--text-secondary);
}

.meta {
  display: flex;
  flex-wrap: wrap;
  gap: 32px;
  margin: 0;
}

.meta dt {
  font-size: 12px;
  text-transform: uppercase;
  letter-spacing: 0.04em;
  color: var(--text-tertiary);
}

.meta dd {
  margin: 2px 0 0;
  font-weight: 500;
  font-variant-numeric: tabular-nums;
}

.panel {
  display: flex;
  flex-direction: column;
  gap: 14px;
  padding: 20px;
}

.panel-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  font-weight: 500;
}

.panel-actions {
  display: flex;
  gap: 10px;
  flex-wrap: wrap;
}

.add-form {
  display: flex;
  align-items: flex-end;
  gap: 12px;
  flex-wrap: wrap;
}

.add-form .field {
  flex: 1 1 180px;
}

.add-form .field-error {
  flex-basis: 100%;
}

.block {
  display: flex;
  flex-direction: column;
  gap: 10px;
}

.summary {
  color: var(--text-secondary);
  max-width: 68ch;
}

.chips {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  margin: 0;
  padding: 0;
  list-style: none;
}

.chip {
  padding: 4px 12px;
  border-radius: 980px;
  background: var(--surface-muted);
  font-size: 13px;
  color: var(--text-secondary);
}

.notice {
  padding: 12px 16px;
  border-radius: var(--radius);
  background: var(--accent-soft);
  font-size: 15px;
}

.external {
  font-size: 15px;
  font-weight: 500;
}

@media (max-width: 720px) {
  .book {
    grid-template-columns: minmax(0, 1fr);
    gap: 24px;
    justify-items: center;
  }

  .book-info {
    width: 100%;
  }
}
</style>
