import { createRouter, createWebHistory } from 'vue-router'
import SearchView from '@/views/SearchView.vue'

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes: [
    {
      path: '/',
      name: 'search',
      component: SearchView,
    },
    {
      path: '/books/:id(\\d+)',
      name: 'book',
      component: () => import('@/views/BookView.vue'),
      props: (route) => ({ id: Number(route.params.id) }),
    },
    {
      path: '/readings',
      name: 'readings',
      component: () => import('@/views/ReadingsView.vue'),
    },
    {
      path: '/:pathMatch(.*)*',
      redirect: { name: 'search' },
    },
  ],
  scrollBehavior(to, from, savedPosition) {
    return savedPosition ?? { top: 0 }
  },
})

export default router
