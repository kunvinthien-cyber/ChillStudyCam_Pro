<script setup>
import { ref, onMounted, onUnmounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useUser } from '../composables/useUser'
import AuthModal from './AuthModal.vue'

const route = useRoute()
const router = useRouter()
const { user, fetchUserProfile } = useUser()

const ppTime = ref('')
const showAuthModal = ref(false)
const isLoggedIn = ref(Boolean(localStorage.getItem('auth_token')))

const updateTime = () => {
  const now = new Date()
  ppTime.value = now.toLocaleTimeString('en-US', {
    timeZone: 'Asia/Phnom_Penh',
    hour: '2-digit',
    minute: '2-digit',
    hour12: true
  })
}

const handleLogout = () => {
  localStorage.removeItem('auth_token')
  isLoggedIn.value = false
  router.push('/login')
}

let timer
onMounted(() => {
  updateTime()
  timer = setInterval(updateTime, 1000)

  if (isLoggedIn.value) {
    fetchUserProfile()
  }
})

onUnmounted(() => clearInterval(timer))

// បញ្ជី Menu ទាំងអស់ដែលភ្ជាប់ជាមួយ Route ពិតប្រាកដ
const navItems = [
  { name: 'Study Sanctuary', path: '/room/1', icon: 'fa-landmark' },
  { name: 'Explore Cafes / Lobby', path: '/', icon: 'fa-compass' },
  { name: 'ChillLibrary (ឯកសារ)', path: '/library', icon: 'fa-book-bookmark' }, // 👈 បន្ថែមត្រង់នេះ
  { name: 'Lofi Soundboard', path: '/soundboard', icon: 'fa-headphones' },
  { name: 'Focus Tasks', path: '/tasks', icon: 'fa-list-check' },
  { name: 'Angkor Scholars', path: '/scholars', icon: 'fa-award' },
  { name: 'Analytics', path: '/analytics', icon: 'fa-chart-pie' },
  { name: 'ChillShop & Rewards', path: '/shop', icon: 'fa-store' }
]
</script>

<template>
  <aside class="w-64 border-r border-slate-800/80 p-5 flex flex-col justify-between max-xl:hidden xl:flex shrink-0 min-h-screen bg-[#0a0f1d]/50">

    <!-- ============================================== -->
    <!-- ផ្នែកខាងលើ៖ LOGO & NAVIGATION MENU            -->
    <!-- ============================================== -->
    <div>
      <!-- Brand Logo -->
      <router-link to="/" class="flex items-center gap-3 mb-8 cursor-pointer group">
        <div class="w-10 h-10 rounded-2xl bg-amber-500/10 border border-amber-500/20 flex items-center justify-center text-amber-400 group-hover:scale-105 transition">
          <i class="fa-solid fa-mug-hot text-lg"></i>
        </div>
        <div>
          <h1 class="text-base font-bold text-white tracking-wide leading-none group-hover:text-amber-300 transition">ChillStudy KH</h1>
          <p class="text-[10px] text-amber-400 font-khmer mt-1">និស្សិតកម្ពុជា • Virtual Study</p>
        </div>
      </router-link>

      <!-- Navigation Links -->
      <div class="space-y-6">
        <div>
          <span class="text-[10px] font-semibold tracking-wider text-slate-500 uppercase px-3 block mb-2">Sanctuary Workspace</span>
          <nav class="space-y-1">
            <router-link
              v-for="item in navItems"
              :key="item.path"
              :to="item.path"
              :class="route.path === item.path
                ? 'bg-amber-400/10 text-amber-300 border border-amber-400/20 font-semibold'
                : 'text-slate-400 hover:text-white hover:bg-slate-800/50 border border-transparent font-medium'"
              class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs transition duration-200"
            >
              <i :class="['fa-solid w-4 text-center', item.icon]"></i>
              <span>{{ item.name }}</span>
            </router-link>
          </nav>
        </div>
      </div>
    </div>

    <!-- ============================================== -->
    <!-- ផ្នែកខាងក្រោម៖ USER ACCOUNT & PHNOM PENH CLOCK   -->
    <!-- ============================================== -->
    <div class="space-y-3 pt-4 border-t border-slate-800/80">

      <!-- បើបាន Login ហើយ៖ បង្ហាញ Profile Link & ប៊ូតុង Logout -->
      <div v-if="isLoggedIn" class="bg-slate-900 border border-slate-800 p-2.5 rounded-2xl flex items-center justify-between group">
        <router-link to="/profile" class="flex items-center gap-2.5 min-w-0 flex-1 cursor-pointer">
          <div class="w-8 h-8 rounded-full bg-amber-400/20 text-amber-400 font-bold flex items-center justify-center text-xs shrink-0 group-hover:scale-105 transition">
            {{ user?.name ? user.name.charAt(0) : 'U' }}
          </div>
          <div class="min-w-0">
            <p class="text-xs font-bold text-white truncate group-hover:text-amber-300 transition">{{ user?.name || 'Student' }}</p>
            <p class="text-[10px] text-amber-400 font-mono">🪙 {{ user?.coins ?? 0 }} PTS</p>
          </div>
        </router-link>

        <button
          @click="handleLogout"
          class="w-7 h-7 rounded-lg text-slate-400 hover:text-rose-400 hover:bg-slate-800 transition flex items-center justify-center text-xs shrink-0 ml-1"
          title="ចាកចេញពីគណនី (Logout)"
        >
          <i class="fa-solid fa-right-from-bracket"></i>
        </button>
      </div>

      <!-- បើមិនទាន់ Login ទេ៖ បង្ហាញប៊ូតុង "ចូលគណនី / ចុះឈ្មោះ" -->
      <button
        v-else
        @click="showAuthModal = true"
        class="w-full py-2.5 rounded-xl bg-amber-400 hover:bg-amber-300 text-slate-950 font-bold text-xs transition shadow-md flex items-center justify-center gap-2"
      >
        <i class="fa-solid fa-user-plus text-xs"></i>
        <span>ចូលគណនី / ចុះឈ្មោះ</span>
      </button>

      <!-- Phnom Penh Real-time Clock (ត្រឹមត្រូវ តែមួយគត់) -->
      <div class="bg-slate-900/90 border border-slate-800 rounded-2xl p-4">
        <div class="flex items-center gap-2 mb-1.5">
          <span class="w-2 h-2 rounded-full bg-emerald-400 animate-ping"></span>
          <span class="text-[10px] uppercase tracking-wider text-slate-400 font-semibold">Phnom Penh Time</span>
        </div>
        <div class="text-2xl font-black text-white font-mono tracking-tight">{{ ppTime }}</div>
        <p class="text-[11px] text-slate-400 mt-1 font-khmer flex items-center gap-1.5">
          <i class="fa-solid fa-moon text-amber-300/80 text-xs"></i> បរិយាកាសស្ងប់ស្ងាត់ពេលយប់
        </p>
      </div>

    </div>

    <!-- Auth Modal Popup -->
    <AuthModal
      :show="showAuthModal"
      @close="showAuthModal = false; isLoggedIn = Boolean(localStorage.getItem('auth_token'))"
    />

  </aside>
</template>
