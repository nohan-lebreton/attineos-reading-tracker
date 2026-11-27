<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import { safeUrl } from '@/utils/safeUrl'

const props = withDefaults(
  defineProps<{
    src: string | null
    title: string
    size?: 'sm' | 'md' | 'lg'
  }>(),
  { size: 'md' },
)

const failed = ref(false)
const url = computed(() => (failed.value ? undefined : safeUrl(props.src)))

watch(
  () => props.src,
  () => {
    failed.value = false
  },
)
</script>

<template>
  <div class="cover" :class="`cover-${size}`">
    <img
      v-if="url"
      :src="url"
      :alt="`Couverture de ${title}`"
      loading="lazy"
      @error="failed = true"
    />
    <div v-else class="cover-placeholder" role="img" :aria-label="`Pas de couverture pour ${title}`">
      <span>{{ title }}</span>
    </div>
  </div>
</template>

<style scoped>
.cover {
  aspect-ratio: 2 / 3;
  max-width: 100%;
  border-radius: var(--radius-sm);
  overflow: hidden;
  background: var(--surface-muted);
  flex-shrink: 0;
}

.cover-sm {
  width: 56px;
  border-radius: 6px;
}

.cover-md {
  width: 100%;
}

.cover-lg {
  width: 260px;
  border-radius: var(--radius);
  box-shadow: var(--shadow-hover);
}

.cover img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.cover-placeholder {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 100%;
  height: 100%;
  padding: 12%;
  background: linear-gradient(160deg, var(--surface-muted), var(--separator));
  color: var(--text-secondary);
  font-family: var(--font-display);
  font-weight: 600;
  text-align: center;
  line-height: 1.2;
  overflow: hidden;
}

.cover-md .cover-placeholder span {
  font-size: 15px;
  display: -webkit-box;
  -webkit-line-clamp: 5;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.cover-sm .cover-placeholder span {
  font-size: 0;
}

.cover-lg .cover-placeholder span {
  font-size: 22px;
}

@media (max-width: 640px) {
  .cover-lg {
    width: 180px;
  }
}
</style>
