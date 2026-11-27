<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { RouterLink } from 'vue-router'
import { toApiError } from '@/api/client'
import { useReadingsStore } from '@/stores/readings'
import type { Reading, ReadingStatus } from '@/types/api'
import { STATUS_LABELS, STATUS_ORDER } from '@/utils/readingStatus'
import { formatAuthors, formatDate } from '@/utils/format'
import AsyncState from '@/components/AsyncState.vue'
import BookCover from '@/components/BookCover.vue'
import ReadingForm from '@/components/ReadingForm.vue'
import SegmentedControl from '@/components/SegmentedControl.vue'
import StarRating from '@/components/StarRating.vue'
import StatusBadge from '@/components/StatusBadge.vue'

const readings = useReadingsStore()

onMounted(() => readings.fetchAll())

const filter = ref<ReadingStatus | null>(null)

const filterOptions = computed(() => [
  { value: null, label: 'Toutes', count: readings.items.length },
  ...STATUS_ORDER.map((status) => ({
    value: status,
    label: STATUS_LABELS[status],
    count: readings.counts[status],
  })),
])

const visible = computed(() => readings.byStatus(filter.value))

const emptyTitle = computed(() =>
  filter.value === null
    ? 'Aucune lecture pour le moment'
    : `Aucune lecture « ${STATUS_LABELS[filter.value]} »`,
)

const editingId = ref<number | null>(null)
const confirmingId = ref<number | null>(null)
const deletingId = ref<number | null>(null)
const deleteError = ref<string | null>(null)

function startEditing(reading: Reading) {
  confirmingId.value = null
  editingId.value = reading.id
}

function askDelete(reading: Reading) {
  editingId.value = null
  deleteError.value = null
  confirmingId.value = reading.id
}

async function remove(reading: Reading) {
  deletingId.value = reading.id
  deleteError.value = null
  try {
    await readings.remove(reading.id)
    confirmingId.value = null
  } catch (e) {
    deleteError.value = toApiError(e).message
  } finally {
    deletingId.value = null
  }
}

function dateLine(reading: Reading): string {
  const started = formatDate(reading.startedAt)
  const finished = formatDate(reading.finishedAt)
  if (started && finished) return `Du ${started} au ${finished}`
  if (started) return `Commencé le ${started}`
  if (finished) return `Terminé le ${finished}`
  return ''
}
</script>

<template>
  <section class="page">
    <header class="page-header">
      <h1 class="page-title">Mes lectures</h1>
      <p class="page-subtitle">Suivez vos livres, de la liste d’envies au dernier chapitre.</p>
    </header>

    <SegmentedControl v-model="filter" :options="filterOptions" label="Filtrer par statut" />

    <AsyncState
      :loading="readings.loading && !readings.loaded"
      :error="readings.error"
      :empty="visible.length === 0"
      :empty-title="emptyTitle"
      empty-text="Ajoutez un livre depuis la recherche pour commencer votre suivi."
      @retry="readings.fetchAll(true)"
    >
      <template #empty-action>
        <RouterLink :to="{ name: 'search' }" class="btn btn-primary">Rechercher un livre</RouterLink>
      </template>

      <ul class="list">
        <li v-for="reading in visible" :key="reading.id" class="item surface">
          <div class="item-main">
            <RouterLink
              :to="{ name: 'book', params: { id: reading.book.gutendexId } }"
              class="item-cover"
              tabindex="-1"
              aria-hidden="true"
            >
              <BookCover :src="reading.book.coverUrl" :title="reading.book.title" size="sm" />
            </RouterLink>

            <div class="item-body">
              <div class="item-heading">
                <RouterLink
                  :to="{ name: 'book', params: { id: reading.book.gutendexId } }"
                  class="item-title"
                >
                  {{ reading.book.title }}
                </RouterLink>
                <StatusBadge :status="reading.status" />
              </div>
              <p class="item-authors">{{ formatAuthors(reading.book.authors) }}</p>
              <div v-if="reading.rating || dateLine(reading)" class="item-meta">
                <StarRating v-if="reading.rating" :model-value="reading.rating" readonly />
                <span v-if="dateLine(reading)">{{ dateLine(reading) }}</span>
              </div>
              <p v-if="reading.comment" class="item-comment">{{ reading.comment }}</p>
            </div>

            <div v-if="editingId !== reading.id && confirmingId !== reading.id" class="item-actions">
              <button type="button" class="btn btn-secondary" @click="startEditing(reading)">
                Modifier
              </button>
              <button type="button" class="btn btn-plain danger" @click="askDelete(reading)">
                Supprimer
              </button>
            </div>
          </div>

          <div v-if="editingId === reading.id" class="item-panel">
            <ReadingForm
              :reading="reading"
              @saved="editingId = null"
              @cancel="editingId = null"
            />
          </div>

          <div v-if="confirmingId === reading.id" class="item-panel confirm" role="alertdialog">
            <p>Supprimer « {{ reading.book.title }} » de vos lectures ?</p>
            <p v-if="deleteError" class="field-error" role="alert">{{ deleteError }}</p>
            <div class="confirm-actions">
              <button type="button" class="btn btn-secondary" @click="confirmingId = null">
                Annuler
              </button>
              <button
                type="button"
                class="btn btn-danger"
                :disabled="deletingId === reading.id"
                @click="remove(reading)"
              >
                {{ deletingId === reading.id ? 'Suppression…' : 'Supprimer' }}
              </button>
            </div>
          </div>
        </li>
      </ul>
    </AsyncState>
  </section>
</template>

<style scoped>
.list {
  display: flex;
  flex-direction: column;
  gap: 14px;
  margin: 0;
  padding: 0;
  list-style: none;
}

.item {
  display: flex;
  flex-direction: column;
  padding: 16px;
}

.item-main {
  display: flex;
  gap: 16px;
  align-items: flex-start;
}

.item-cover {
  flex-shrink: 0;
}

.item-body {
  display: flex;
  flex-direction: column;
  gap: 4px;
  flex: 1;
  min-width: 0;
}

.item-heading {
  display: flex;
  align-items: center;
  gap: 10px;
  flex-wrap: wrap;
}

.item-title {
  font-family: var(--font-display);
  font-size: 17px;
  font-weight: 600;
  color: var(--text);
  overflow-wrap: anywhere;
}

.item-authors {
  font-size: 14px;
  color: var(--text-secondary);
}

.item-meta {
  display: flex;
  align-items: center;
  gap: 12px;
  flex-wrap: wrap;
  font-size: 13px;
  color: var(--text-tertiary);
}

.item-comment {
  margin-top: 4px;
  font-size: 14px;
  color: var(--text-secondary);
  white-space: pre-line;
  overflow-wrap: anywhere;
}

.item-actions {
  display: flex;
  align-items: center;
  gap: 4px;
  flex-shrink: 0;
}

.danger {
  color: var(--danger);
}

.item-panel {
  margin-top: 16px;
  padding-top: 16px;
  border-top: 1px solid var(--separator);
}

.confirm {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.confirm-actions {
  display: flex;
  justify-content: flex-end;
  gap: 10px;
}

@media (max-width: 640px) {
  .item-main {
    flex-wrap: wrap;
  }

  .item-actions {
    width: 100%;
    justify-content: flex-end;
  }
}
</style>
