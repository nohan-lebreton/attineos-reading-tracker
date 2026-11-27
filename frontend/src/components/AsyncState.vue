<script setup lang="ts">
import { computed } from 'vue'
import type { ApiError } from '@/api/client'

const props = withDefaults(
  defineProps<{
    loading: boolean
    error: ApiError | null
    empty?: boolean
    emptyTitle?: string
    emptyText?: string
  }>(),
  { empty: false, emptyTitle: 'Rien à afficher', emptyText: '' },
)

const emit = defineEmits<{ retry: [] }>()

const errorTitle = computed(() => {
  switch (props.error?.status) {
    case 0:
      return 'Serveur injoignable'
    case 404:
      return 'Introuvable'
    case 503:
      return 'Catalogue indisponible'
    default:
      return 'Une erreur est survenue'
  }
})
</script>

<template>
  <div v-if="loading" class="state" role="status">
    <span class="spinner" aria-hidden="true"></span>
    <span class="state-text">Chargement…</span>
  </div>
  <div v-else-if="error" class="state" role="alert">
    <p class="state-title">{{ errorTitle }}</p>
    <p class="state-text">{{ error.message }}</p>
    <button v-if="error.status !== 404" type="button" class="btn btn-secondary" @click="emit('retry')">
      Réessayer
    </button>
    <slot name="error-action" />
  </div>
  <div v-else-if="empty" class="state">
    <p class="state-title">{{ emptyTitle }}</p>
    <p v-if="emptyText" class="state-text">{{ emptyText }}</p>
    <slot name="empty-action" />
  </div>
  <slot v-else />
</template>

<style scoped>
.state {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 10px;
  padding: 64px 24px;
  text-align: center;
}

.state-title {
  font-family: var(--font-display);
  font-size: 21px;
  font-weight: 600;
}

.state-text {
  max-width: 46ch;
  color: var(--text-secondary);
}

.spinner {
  width: 28px;
  height: 28px;
  border: 3px solid var(--separator);
  border-top-color: var(--text-secondary);
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
}

@keyframes spin {
  to {
    transform: rotate(360deg);
  }
}
</style>
