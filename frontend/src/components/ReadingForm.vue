<script setup lang="ts">
import { computed, reactive, ref } from 'vue'
import { toApiError } from '@/api/client'
import { useReadingsStore } from '@/stores/readings'
import type { Reading, ReadingStatus, UpdateReadingPayload } from '@/types/api'
import { STATUS_LABELS, STATUS_ORDER } from '@/utils/readingStatus'
import SegmentedControl from './SegmentedControl.vue'
import StarRating from './StarRating.vue'

const COMMENT_MAX = 2000

const props = defineProps<{ reading: Reading }>()
const emit = defineEmits<{ saved: [reading: Reading]; cancel: [] }>()

const readings = useReadingsStore()

const form = reactive({
  status: props.reading.status,
  startedAt: props.reading.startedAt ?? '',
  finishedAt: props.reading.finishedAt ?? '',
  rating: props.reading.rating,
  comment: props.reading.comment ?? '',
})

const saving = ref(false)
const errors = ref<Record<string, string>>({})
const generalError = ref<string | null>(null)

const statusOptions = STATUS_ORDER.map((value) => ({ value, label: STATUS_LABELS[value] }))

const changes = computed<UpdateReadingPayload>(() => {
  const payload: UpdateReadingPayload = {}
  const startedAt = form.startedAt || null
  const finishedAt = form.finishedAt || null
  const comment = form.comment.trim() || null

  if (form.status !== props.reading.status) payload.status = form.status
  if (startedAt !== props.reading.startedAt) payload.startedAt = startedAt
  if (finishedAt !== props.reading.finishedAt) payload.finishedAt = finishedAt
  if (form.rating !== props.reading.rating) payload.rating = form.rating
  if (comment !== props.reading.comment) payload.comment = comment

  return payload
})

const hasChanges = computed(() => Object.keys(changes.value).length > 0)

const dateError = computed(() =>
  form.startedAt && form.finishedAt && form.finishedAt < form.startedAt
    ? 'La date de fin doit être postérieure ou égale à la date de début.'
    : null,
)

async function submit() {
  if (!hasChanges.value) {
    emit('cancel')
    return
  }
  if (dateError.value) {
    return
  }
  saving.value = true
  errors.value = {}
  generalError.value = null
  try {
    emit('saved', await readings.update(props.reading.id, changes.value))
  } catch (e) {
    const error = toApiError(e)
    errors.value = error.details
    generalError.value = error.message
  } finally {
    saving.value = false
  }
}

function setStatus(status: ReadingStatus) {
  form.status = status
}
</script>

<template>
  <form class="form" novalidate @submit.prevent="submit">
    <div class="field">
      <span class="field-label">Statut</span>
      <SegmentedControl
        :model-value="form.status"
        :options="statusOptions"
        label="Statut de la lecture"
        @update:model-value="setStatus"
      />
      <p v-if="errors.status" class="field-error">{{ errors.status }}</p>
    </div>

    <div class="row">
      <div class="field">
        <label class="field-label" :for="`started-${reading.id}`">Début</label>
        <input :id="`started-${reading.id}`" v-model="form.startedAt" type="date" class="input" />
        <p v-if="errors.startedAt" class="field-error">{{ errors.startedAt }}</p>
      </div>
      <div class="field">
        <label class="field-label" :for="`finished-${reading.id}`">Fin</label>
        <input
          :id="`finished-${reading.id}`"
          v-model="form.finishedAt"
          type="date"
          class="input"
          :min="form.startedAt || undefined"
        />
        <p v-if="dateError || errors.finishedAt" class="field-error">
          {{ dateError ?? errors.finishedAt }}
        </p>
      </div>
    </div>

    <div class="field">
      <span class="field-label">Note</span>
      <StarRating v-model="form.rating" />
      <p v-if="errors.rating" class="field-error">{{ errors.rating }}</p>
    </div>

    <div class="field">
      <label class="field-label" :for="`comment-${reading.id}`">Commentaire</label>
      <textarea
        :id="`comment-${reading.id}`"
        v-model="form.comment"
        class="input"
        :maxlength="COMMENT_MAX"
        placeholder="Ce que vous en avez pensé…"
      ></textarea>
      <p class="counter">{{ form.comment.length }} / {{ COMMENT_MAX }}</p>
      <p v-if="errors.comment" class="field-error">{{ errors.comment }}</p>
    </div>

    <p v-if="generalError" class="field-error" role="alert">{{ generalError }}</p>

    <div class="actions">
      <button type="button" class="btn btn-secondary" @click="emit('cancel')">Annuler</button>
      <button type="submit" class="btn btn-primary" :disabled="saving || !!dateError">
        {{ saving ? 'Enregistrement…' : 'Enregistrer' }}
      </button>
    </div>
  </form>
</template>

<style scoped>
.form {
  display: flex;
  flex-direction: column;
  gap: 18px;
}

.row {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 12px;
}

.counter {
  align-self: flex-end;
  font-size: 12px;
  color: var(--text-tertiary);
  font-variant-numeric: tabular-nums;
}

.actions {
  display: flex;
  justify-content: flex-end;
  gap: 10px;
}

@media (max-width: 480px) {
  .row {
    grid-template-columns: minmax(0, 1fr);
  }
}
</style>
