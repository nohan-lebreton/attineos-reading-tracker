<script setup lang="ts" generic="T extends string | null">
defineProps<{
  options: { value: T; label: string; count?: number }[]
  label: string
}>()

const model = defineModel<T>({ required: true })
</script>

<template>
  <div class="segmented" role="group" :aria-label="label">
    <button
      v-for="option in options"
      :key="option.value ?? 'all'"
      type="button"
      class="segment"
      :class="{ active: model === option.value }"
      :aria-pressed="model === option.value"
      @click="model = option.value"
    >
      {{ option.label }}
      <span v-if="option.count !== undefined" class="segment-count">{{ option.count }}</span>
    </button>
  </div>
</template>

<style scoped>
.segmented {
  display: inline-flex;
  flex-wrap: wrap;
  gap: 2px;
  padding: 3px;
  border-radius: 10px;
  background: var(--surface-muted);
  max-width: 100%;
}

.segment {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 6px 14px;
  border: none;
  border-radius: 8px;
  background: none;
  color: var(--text);
  font: inherit;
  font-size: 14px;
  font-weight: 500;
  cursor: pointer;
  transition:
    background-color 0.2s,
    box-shadow 0.2s;
}

.segment.active {
  background: var(--surface);
  box-shadow:
    0 1px 3px rgba(0, 0, 0, 0.12),
    0 1px 1px rgba(0, 0, 0, 0.04);
}

.segment-count {
  color: var(--text-tertiary);
  font-size: 12px;
  font-variant-numeric: tabular-nums;
}
</style>
