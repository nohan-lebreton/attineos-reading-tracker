<script setup lang="ts">
defineProps<{
  page: number
  hasPrevious: boolean
  hasNext: boolean
}>()

const emit = defineEmits<{ change: [page: number] }>()
</script>

<template>
  <nav v-if="hasPrevious || hasNext" class="pagination" aria-label="Pagination">
    <button
      type="button"
      class="btn btn-secondary"
      :disabled="!hasPrevious"
      @click="emit('change', page - 1)"
    >
      ‹ Précédent
    </button>
    <span class="pagination-page">Page {{ page }}</span>
    <button
      type="button"
      class="btn btn-secondary"
      :disabled="!hasNext"
      @click="emit('change', page + 1)"
    >
      Suivant ›
    </button>
  </nav>
</template>

<style scoped>
.pagination {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 16px;
  padding-top: 12px;
}

.pagination-page {
  font-size: 14px;
  color: var(--text-secondary);
  font-variant-numeric: tabular-nums;
}
</style>
