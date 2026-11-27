<script setup lang="ts">
import { onMounted } from 'vue'
import { RouterLink, RouterView } from 'vue-router'
import { useReadingsStore } from '@/stores/readings'

const readings = useReadingsStore()

onMounted(() => readings.fetchAll())
</script>

<template>
  <header class="nav">
    <div class="container nav-inner">
      <RouterLink :to="{ name: 'search' }" class="brand">Lectures</RouterLink>
      <nav class="nav-links" aria-label="Navigation principale">
        <RouterLink
          :to="{ name: 'search' }"
          class="nav-link"
          active-class=""
          exact-active-class="is-active"
        >
          Rechercher
        </RouterLink>
        <RouterLink :to="{ name: 'readings' }" class="nav-link" active-class="is-active">
          Mes lectures
          <span v-if="readings.items.length > 0" class="nav-count">{{ readings.items.length }}</span>
        </RouterLink>
      </nav>
    </div>
  </header>

  <main class="container">
    <RouterView />
  </main>
</template>

<style scoped>
.nav {
  position: sticky;
  top: 0;
  z-index: 10;
  background: var(--nav-bg);
  backdrop-filter: saturate(180%) blur(20px);
  -webkit-backdrop-filter: saturate(180%) blur(20px);
  border-bottom: 1px solid var(--separator);
}

.nav-inner {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 16px;
  height: 52px;
}

.brand {
  font-family: var(--font-display);
  font-size: 19px;
  font-weight: 600;
  color: var(--text);
}

.brand:hover {
  text-decoration: none;
}

.nav-links {
  display: flex;
  gap: 4px;
}

.nav-link {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 6px 12px;
  border-radius: 980px;
  font-size: 14px;
  color: var(--text-secondary);
  transition:
    color 0.2s,
    background-color 0.2s;
}

.nav-link:hover {
  color: var(--text);
  text-decoration: none;
}

.nav-link.is-active {
  color: var(--text);
  background: var(--surface-muted);
}

.nav-count {
  min-width: 20px;
  padding: 0 6px;
  border-radius: 980px;
  background: var(--accent);
  color: #ffffff;
  font-size: 12px;
  font-weight: 600;
  line-height: 20px;
  text-align: center;
  font-variant-numeric: tabular-nums;
}
</style>
