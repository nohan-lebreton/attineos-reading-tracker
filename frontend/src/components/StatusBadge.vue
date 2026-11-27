<script setup lang="ts">
import type { ReadingStatus } from '@/types/api'
import { STATUS_LABELS } from '@/utils/readingStatus'

defineProps<{ status: ReadingStatus }>()
</script>

<template>
  <span class="badge" :class="`badge-${status}`">{{ STATUS_LABELS[status] }}</span>
</template>

<style scoped>
.badge {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 3px 10px;
  border-radius: 980px;
  font-size: 13px;
  font-weight: 500;
  color: var(--badge-color);
  background: color-mix(in srgb, var(--badge-color) 12%, transparent);
  white-space: nowrap;
}

.badge::before {
  content: '';
  width: 6px;
  height: 6px;
  border-radius: 50%;
  background: currentColor;
}

.badge-to_read {
  --badge-color: var(--status-to-read);
}

.badge-reading {
  --badge-color: var(--status-reading);
}

.badge-read {
  --badge-color: var(--status-read);
}

.badge-abandoned {
  --badge-color: var(--status-abandoned);
}
</style>
