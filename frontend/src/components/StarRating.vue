<script setup lang="ts">
const props = withDefaults(
  defineProps<{
    modelValue: number | null
    readonly?: boolean
  }>(),
  { readonly: false },
)

const emit = defineEmits<{ 'update:modelValue': [value: number | null] }>()

const stars = [1, 2, 3, 4, 5]

function select(value: number) {
  emit('update:modelValue', props.modelValue === value ? null : value)
}
</script>

<template>
  <span
    v-if="readonly"
    class="stars"
    role="img"
    :aria-label="modelValue ? `Note : ${modelValue} sur 5` : 'Pas de note'"
  >
    <span v-for="star in stars" :key="star" class="star" :class="{ on: (modelValue ?? 0) >= star }">★</span>
  </span>
  <span v-else class="stars" role="group" aria-label="Note sur 5">
    <button
      v-for="star in stars"
      :key="star"
      type="button"
      class="star star-button"
      :class="{ on: (modelValue ?? 0) >= star }"
      :aria-label="`${star} sur 5`"
      :aria-pressed="modelValue === star"
      @click="select(star)"
    >
      ★
    </button>
  </span>
</template>

<style scoped>
.stars {
  display: inline-flex;
  gap: 2px;
}

.star {
  color: var(--separator);
  font-size: 15px;
  line-height: 1;
}

.star.on {
  color: var(--star);
}

.star-button {
  padding: 4px;
  border: none;
  background: none;
  font-size: 24px;
  cursor: pointer;
  border-radius: 6px;
  transition: transform 0.15s;
}

.star-button:hover {
  transform: scale(1.15);
}
</style>
