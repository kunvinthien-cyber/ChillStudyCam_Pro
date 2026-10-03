<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import apiClient from '../services/api'
import { useUser } from '../composables/useUser'

const router = useRouter()
const { user, fetchUserProfile } = useUser()

const activeTab = ref('overview') // 'overview', 'edit', 'orders'
const editName = ref('')
const editTargetHours = ref(4.0)
const isSaving = ref(false)
const saveSuccessMessage = ref('')
const userOrders = ref([])
const isLoadingOrders = ref(false)

const badges = [
  { name: 'Hanuman Streak', rank: 'Hanuman IV', icon: 'fa-shield-halved', color: 'text-blue-400 bg-blue-500/10 border-blue-500/20', desc: 'រៀនជាប់គ្នា ៧ ថ្ងៃ', unlocked: true },
  { name: 'Angkor Scholar', rank: 'Level 5', icon: 'fa-landmark', color: 'text-amber-400 bg-amber-500/10 border-amber-500/20', desc: 'សន្សំម៉ោងរៀនលើសពី ៥០ ម៉ោង', unlocked: true },
  { name: 'Apsara Focus', rank: 'Special', icon: 'fa-award', color: 'text-emerald-400 bg-emerald-500/10 border-emerald-500/20', desc: 'បញ្ចប់ Pomodoro ៥០ ជុំ', unlocked: false },
  { name: 'Night Owl Scholar', rank: 'Special', icon: 'fa-moon', color: 'text-purple-400 bg-purple-500/10 border-purple-500/20', desc: 'រៀនពេលយប់ក្រោយម៉ោង ១០', unlocked: true }
]

const handleUpdateProfile = async () => {
  try {
    isSaving.value = true
    saveSuccessMessage.value = ''
    const res = await apiClient.put('/user/profile', {
      name: editName.value,
      target_hours: editTargetHours.value
    })
    user.value = res.data.user
    saveSuccessMessage.value = res.data.message
    setTimeout(() => { saveSuccessMessage.value = '' }, 3000)
  } catch {
    alert('មិនអាចកែប្រែព័ត៌មានបានទេ!')
  } finally {
    isSaving.value = false
  }
}

const fetchOrders = async () => {
  try {
    isLoadingOrders.value = true
    const res = await apiClient.get('/user/orders')
    userOrders.value = res.data
  } catch (err) {
    console.error('Fetch orders error:', err)
  } finally {
    isLoadingOrders.value = false
  }
}

const handleLogout = () => {
  localStorage.removeItem('auth_token')
  router.push('/login')
}

onMounted(async () => {
  await fetchUserProfile()
  editName.value = user.value.name
  editTargetHours.value = user.value.target_hours || 4.0
  fetchOrders()
})
</script>

<template>
  <div class="space-y-6 max-w-4xl mx-auto pb-10">

    <!-- User Hero Card -->
    <div class="bg-gradient-to-r from-slate-900 via-slate-900 to-amber-500/10 border border-slate-800 p-6 sm:p-8 rounded-3xl relative overflow-hidden shadow-2xl">
      <div class="flex flex-col sm:flex-row items-center gap-6 relative z-10">

        <!-- Big Avatar with Rank Glow -->
        <div class="relative">
          <div class="w-24 h-24 rounded-3xl bg-gradient-to-br from-amber-400 to-amber-600 text-slate-950 font-black text-4xl flex items-center justify-center shadow-xl border-4 border-slate-900">
            {{ user.name ? user.name.charAt(0) : 'U' }}
          </div>
          <span class="absolute -bottom-2 -right-2 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-slate-950 text-amber-400 border border-amber-500/40 shadow">
            {{ user.rank_title }}
          </span>
        </div>

        <!-- Name & Info -->
        <div class="text-center sm:text-left flex-1">
          <div class="flex flex-wrap items-center justify-center sm:justify-start gap-2 mb-1">
            <h1 class="text-2xl font-black text-white tracking-wide">{{ user.name }}</h1>
            <span class="px-2.5 py-0.5 rounded-full bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 text-[10px] font-semibold">
              <i class="fa-solid fa-circle-check text-[9px]"></i> គណនីសិស្សផ្លូវការ
            </span>
          </div>

          <p class="text-xs text-slate-400 font-mono mb-4">{{ user.email }}</p>

          <!-- Quick Stats Pills -->
          <div class="flex flex-wrap items-center justify-center sm:justify-start gap-2">
            <span class="px-3 py-1 rounded-xl bg-slate-950/80 border border-slate-800 text-xs text-amber-400 font-bold flex items-center gap-1.5">
              <span>🪙</span> {{ user.coins }} PTS
            </span>
            <span class="px-3 py-1 rounded-xl bg-slate-950/80 border border-slate-800 text-xs text-orange-400 font-bold flex items-center gap-1.5">
              <span>🔥</span> {{ user.streak_days }} ថ្ងៃ Streak
            </span>
            <span class="px-3 py-1 rounded-xl bg-slate-950/80 border border-slate-800 text-xs text-emerald-400 font-bold flex items-center gap-1.5">
              <span>⏱️</span> {{ user.studied_hours }} ម៉ោងរៀន
            </span>
          </div>
        </div>

        <!-- Logout Button -->
        <button
          @click="handleLogout"
          class="px-4 py-2.5 rounded-2xl bg-rose-500/10 hover:bg-rose-500/20 text-rose-300 border border-rose-500/30 text-xs font-semibold transition flex items-center gap-2"
        >
          <i class="fa-solid fa-right-from-bracket"></i>
          <span>ចាកចេញ</span>
        </button>

      </div>
    </div>

    <!-- Navigation Tabs -->
    <div class="flex items-center gap-2 border-b border-slate-800 pb-3">
      <button
        @click="activeTab = 'overview'"
        :class="activeTab === 'overview' ? 'bg-amber-400 text-slate-950 font-bold' : 'text-slate-400 hover:text-white'"
        class="px-4 py-2 rounded-xl text-xs transition flex items-center gap-2"
      >
        <i class="fa-solid fa-chart-simple"></i> សមិទ្ធផល & Badges
      </button>

      <button
        @click="activeTab = 'edit'"
        :class="activeTab === 'edit' ? 'bg-amber-400 text-slate-950 font-bold' : 'text-slate-400 hover:text-white'"
        class="px-4 py-2 rounded-xl text-xs transition flex items-center gap-2"
      >
        <i class="fa-solid fa-user-pen"></i> កែប្រែព័ត៌មាន
      </button>

      <button
        @click="activeTab = 'orders'"
        :class="activeTab === 'orders' ? 'bg-amber-400 text-slate-950 font-bold' : 'text-slate-400 hover:text-white'"
        class="px-4 py-2 rounded-xl text-xs transition flex items-center gap-2"
      >
        <i class="fa-solid fa-receipt"></i> ប្រវត្តិកុម្ម៉ង់ ({{ userOrders.length }})
      </button>
    </div>

    <!-- ============================================== -->
    <!-- TAB 1: OVERVIEW & BADGES                       -->
    <!-- ============================================== -->
    <div v-if="activeTab === 'overview'" class="space-y-6">

      <!-- Stats 4-Grid -->
      <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
        <div class="bg-slate-900/90 border border-slate-800 p-4 rounded-2xl text-center">
          <span class="text-xl">🔥</span>
          <h4 class="text-lg font-bold text-amber-400 mt-1">{{ user.streak_days }} ថ្ងៃ</h4>
          <span class="text-[10px] text-slate-400 uppercase font-semibold">Streak បន្តបន្ទាប់</span>
        </div>
        <div class="bg-slate-900/90 border border-slate-800 p-4 rounded-2xl text-center">
          <span class="text-xl">🪙</span>
          <h4 class="text-lg font-bold text-amber-400 mt-1">{{ user.coins }}</h4>
          <span class="text-[10px] text-slate-400 uppercase font-semibold">PTS កាក់សរុប</span>
        </div>
        <div class="bg-slate-900/90 border border-slate-800 p-4 rounded-2xl text-center">
          <span class="text-xl">⏱️</span>
          <h4 class="text-lg font-bold text-white mt-1">{{ user.studied_hours }}h</h4>
          <span class="text-[10px] text-slate-400 uppercase font-semibold">ម៉ោងរៀនសរុប</span>
        </div>
        <div class="bg-slate-900/90 border border-slate-800 p-4 rounded-2xl text-center">
          <span class="text-xl">🎯</span>
          <h4 class="text-lg font-bold text-emerald-400 mt-1">{{ user.target_hours }}h/ថ្ងៃ</h4>
          <span class="text-[10px] text-slate-400 uppercase font-semibold">គោលដៅប្រចាំថ្ងៃ</span>
        </div>
      </div>

      <!-- Badges Showcase -->
      <div>
        <h3 class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-3">🎖️ Badges & Titles វប្បធម៌ខ្មែរ</h3>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
          <div
            v-for="b in badges"
            :key="b.name"
            :class="b.unlocked ? 'bg-slate-900/90 border-slate-800' : 'bg-slate-950/40 border-slate-800/40 opacity-50'"
            class="border p-4 rounded-2xl flex items-center gap-3.5 transition"
          >
            <div :class="b.color" class="w-12 h-12 rounded-2xl border flex items-center justify-center text-xl shrink-0">
              <i :class="['fa-solid', b.icon]"></i>
            </div>
            <div class="flex-1">
              <div class="flex items-center justify-between">
                <h4 class="text-xs font-bold text-white">{{ b.name }}</h4>
                <span class="text-[9px] px-2 py-0.5 rounded-full bg-slate-800 text-slate-400 font-mono">{{ b.rank }}</span>
              </div>
              <p class="text-[11px] text-slate-400 font-khmer mt-0.5">{{ b.desc }}</p>
              <span class="text-[9px] text-emerald-400 font-semibold mt-1 inline-block" v-if="b.unlocked">
                ✓ ដោះសោរួចរាល់
              </span>
              <span class="text-[9px] text-slate-500 font-semibold mt-1 inline-block" v-else>
                🔒 នៅជាប់សោ
              </span>
            </div>
          </div>
        </div>
      </div>

    </div>

    <!-- ============================================== -->
    <!-- TAB 2: EDIT PROFILE                            -->
    <!-- ============================================== -->
    <div v-else-if="activeTab === 'edit'" class="bg-slate-900/90 border border-slate-800 p-6 sm:p-8 rounded-3xl shadow-xl">
      <h3 class="text-sm font-bold text-white mb-1">កែប្រែព័ត៌មានគណនី</h3>
      <p class="text-xs text-slate-400 font-khmer mb-6">ធ្វើបច្ចុប្បន្នភាពឈ្មោះ និងគោលដៅសិក្សាប្រចាំថ្ងៃរបស់អ្នក។</p>

      <div v-if="saveSuccessMessage" class="mb-4 p-3 rounded-2xl bg-emerald-500/10 border border-emerald-500/20 text-emerald-300 text-xs font-khmer">
        {{ saveSuccessMessage }}
      </div>

      <form @submit.prevent="handleUpdateProfile" class="space-y-4 max-w-lg">
        <div>
          <label class="text-[11px] text-slate-400 block mb-1">ឈ្មោះពេញ (Full Name)៖</label>
          <input
            v-model="editName"
            type="text"
            required
            class="w-full bg-slate-950 border border-slate-700 rounded-xl px-4 py-2.5 text-xs text-white focus:outline-none focus:border-amber-400 transition"
          />
        </div>

        <div>
          <label class="text-[11px] text-slate-400 block mb-1">គោលដៅរៀនប្រចាំថ្ងៃ (Daily Target Hours)៖</label>
          <input
            v-model="editTargetHours"
            type="number"
            step="0.5"
            min="1"
            max="16"
            required
            class="w-full bg-slate-950 border border-slate-700 rounded-xl px-4 py-2.5 text-xs text-white focus:outline-none focus:border-amber-400 transition"
          />
          <span class="text-[10px] text-slate-500 mt-1 block">កំណត់ម៉ោងដែលអ្នកចង់រៀនក្នុងមួយថ្ងៃ (ឧ. ៤.០ ម៉ោង)</span>
        </div>

        <div>
          <label class="text-[11px] text-slate-400 block mb-1">អ៊ីមែល (មិនអាចកែបានទេ)៖</label>
          <input
            :value="user.email"
            disabled
            class="w-full bg-slate-950/50 border border-slate-800 rounded-xl px-4 py-2.5 text-xs text-slate-500 cursor-not-allowed"
          />
        </div>

        <button
          type="submit"
          :disabled="isSaving"
          class="px-6 py-2.5 rounded-xl bg-amber-400 hover:bg-amber-300 text-slate-950 font-bold text-xs transition shadow-md flex items-center gap-2 disabled:opacity-50"
        >
          <i v-if="isSaving" class="fa-solid fa-spinner fa-spin"></i>
          <span>រក្សាទុកព័ត៌មាន</span>
        </button>
      </form>
    </div>

    <!-- ============================================== -->
    <!-- TAB 3: ORDER HISTORY                           -->
    <!-- ============================================== -->
    <div v-else-if="activeTab === 'orders'" class="space-y-4">
      <h3 class="text-sm font-bold text-white mb-2">ប្រវត្តិកុម្ម៉ង់ភេសជ្ជៈ និងអាហារសម្រន់</h3>

      <div v-if="isLoadingOrders" class="text-center py-12 text-slate-400 text-xs">
        <div class="w-8 h-8 border-4 border-amber-400 border-t-transparent rounded-full animate-spin mx-auto mb-2"></div>
        កំពុងទាញយកប្រវត្តិកុម្ម៉ង់...
      </div>

      <div v-else-if="userOrders.length === 0" class="bg-slate-900/60 border border-slate-800 rounded-3xl p-8 text-center text-slate-500 text-xs font-khmer">
        <i class="fa-solid fa-box-open text-3xl mb-2 block text-slate-600"></i>
        មិនទាន់មានប្រវត្តិកុម្ម៉ង់ទំនិញនៅឡើយទេ។ ចូលទៅកាន់ ChillShop ដើម្បីកុម្ម៉ង់!
      </div>

      <div v-else class="space-y-3">
        <div
          v-for="order in userOrders"
          :key="order.id"
          class="bg-slate-900/90 border border-slate-800 p-4 rounded-2xl flex flex-wrap items-center justify-between gap-3"
        >
          <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-amber-400/10 text-amber-400 flex items-center justify-center text-base">
              <i class="fa-solid fa-mug-hot"></i>
            </div>
            <div>
              <h4 class="text-xs font-bold text-white">
                {{ order.product?.name || `ការកុម្ម៉ង់ #${order.id}` }}
              </h4>
              <p class="text-[10px] text-slate-400 font-khmer">
                {{ order.fulfillment_type === 'pickup' ? '🛍️ ទៅយកផ្ទាល់នៅហាង' : '🛵 ដឹកជញ្ជូនដល់កន្លែង' }} • {{ new Date(order.created_at).toLocaleDateString() }}
              </p>
            </div>
          </div>

          <div class="text-right">
            <span class="text-xs font-bold text-amber-400 block">${{ Number(order.final_cash_amount).toFixed(2) }}</span>
            <span class="text-[10px] text-emerald-400 font-mono" v-if="order.pts_used > 0">
              -{{ order.pts_used }} PTS
            </span>
          </div>
        </div>
      </div>
    </div>

  </div>
</template>
