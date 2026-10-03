import { createRouter, createWebHistory } from 'vue-router'
import HomeView from '../views/HomeView.vue'
import RoomView from '../views/RoomView.vue'
import ShopView from '../views/ShopView.vue'
import TasksView from '../views/TasksView.vue'
import SoundboardView from '../views/SoundboardView.vue'
import ScholarsView from '../views/ScholarsView.vue'
import AnalyticsView from '../views/AnalyticsView.vue'
import LoginView from '../views/LoginView.vue' // 👈 Import LoginView
import ProfileView from '../views/ProfileView.vue'
import LibraryView from '../views/LibraryView.vue'

const routes = [
  { path: '/login', name: 'login', component: LoginView },
  { path: '/', name: 'home', component: HomeView },
  { path: '/room/:id', name: 'room', component: RoomView, props: true },
  { path: '/shop', name: 'shop', component: ShopView },
  { path: '/tasks', name: 'tasks', component: TasksView },
  { path: '/soundboard', name: 'soundboard', component: SoundboardView },
  { path: '/scholars', name: 'scholars', component: ScholarsView },
  { path: '/analytics', name: 'analytics', component: AnalyticsView },
  { path: '/profile', name: 'profile', component: ProfileView },
  { path: '/library', name: 'library', component: LibraryView },
]

const router = createRouter({
  history: createWebHistory(),
  routes
})

// 🛡️ ROUTE GUARD: បើមិនទាន់ Login ទេ មិនឱ្យចូលប្រើប្រាស់ឡើយ!
router.beforeEach((to, from, next) => {
  const token = localStorage.getItem('auth_token')

  // បើមិនទាន់ Login ហើយព្យាយាមចូលទំព័រផ្សេង ត្រូវរុញទៅ /login
  if (to.name !== 'login' && !token) {
    next({ name: 'login' })
  }
  // បើ Login រួចហើយ តែព្យាយាមចូលទៅ /login វិញ ត្រូវរុញមក /
  else if (to.name === 'login' && token) {
    next({ name: 'home' })
  }
  else {
    next()
  }
})

if (typeof window !== 'undefined') {
  window.addEventListener('auth:unauthorized', () => {
    if (router.currentRoute.value.name !== 'login') {
      router.replace({ name: 'login' })
    }
  })
}

export default router
