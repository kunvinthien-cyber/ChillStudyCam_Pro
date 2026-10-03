<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import apiClient from '../services/api'
import { useUser } from '../composables/useUser'

const router = useRouter()
const { user } = useUser()

const isLoginTab = ref(true) // true = Login, false = Register
const name = ref('')
const email = ref('')
const password = ref('')
const errorMessage = ref('')
const isLoading = ref(false)

const handleAuth = async () => {
  errorMessage.value = ''
  isLoading.value = true

  try {
    const endpoint = isLoginTab.value ? '/login' : '/register'
    const payload = isLoginTab.value
      ? { email: email.value, password: password.value }
      : { name: name.value, email: email.value, password: password.value }

    const res = await apiClient.post(endpoint, payload)

    // រក្សាទុក Token និងទិន្នន័យ User
    localStorage.setItem('auth_token', res.data.token)
    user.value = res.data.user

    // នាំសិស្សចូលទៅកាន់ទំព័រដើម Dashboard ភ្លាមៗ
    router.push('/')
  } catch (err) {
    errorMessage.value = err.response?.data?.message || 'អ៊ីមែល ឬពាក្យសម្ងាត់មិនត្រឹមត្រូវឡើយ!'
  } finally {
    isLoading.value = false
  }
}
</script>

<template>
  <div class="min-h-screen bg-[#070b14] text-slate-100 flex items-center justify-center p-4 relative overflow-hidden">

    <!-- Background Glow Effects -->
    <div class="absolute -top-32 -left-32 w-96 h-96 bg-amber-500/10 rounded-full blur-3xl pointer-events-none"></div>
    <div class="absolute -bottom-32 -right-32 w-96 h-96 bg-emerald-500/10 rounded-full blur-3xl pointer-events-none"></div>

    <div class="bg-slate-900/90 border border-slate-800 backdrop-blur-2xl rounded-3xl p-6 sm:p-10 max-w-md w-full shadow-2xl relative z-10">

      <!-- Brand Logo & Greeting -->
      <div class="text-center mb-6">
        <div class="w-14 h-14 rounded-2xl bg-amber-400/20 text-amber-400 flex items-center justify-center text-2xl mx-auto mb-3 shadow-inner">
          ☕
        </div>
        <h1 class="text-2xl font-black text-white tracking-wide">ChillStudy KH</h1>
        <p class="text-xs text-amber-400 font-khmer mt-1">បណ្តាញសង្គម និងការសិក្សាដោយគ្មានសម្ពាធ 🇰🇭</p>
      </div>

      <!-- Tab Switcher (Login vs Sign Up) -->
      <div class="grid grid-cols-2 gap-1 bg-slate-950 p-1.5 rounded-2xl border border-slate-800 mb-6">
        <button
          @click="isLoginTab = true; errorMessage = ''"
          :class="isLoginTab ? 'bg-amber-400 text-slate-950 font-bold shadow-md' : 'text-slate-400 hover:text-white'"
          class="py-2.5 rounded-xl text-xs transition font-medium"
        >
          ចូលគណនី (Login)
        </button>
        <button
          @click="isLoginTab = false; errorMessage = ''"
          :class="!isLoginTab ? 'bg-amber-400 text-slate-950 font-bold shadow-md' : 'text-slate-400 hover:text-white'"
          class="py-2.5 rounded-xl text-xs transition font-medium"
        >
          ចុះឈ្មោះថ្មី (Sign Up)
        </button>
      </div>

      <!-- Error Alert -->
      <div v-if="errorMessage" class="mb-4 p-3 rounded-2xl bg-rose-500/10 border border-rose-500/20 text-rose-300 text-xs font-khmer text-center animate-shake">
        {{ errorMessage }}
      </div>

      <!-- Auth Form -->
      <form @submit.prevent="handleAuth" class="space-y-4">

        <div v-if="!isLoginTab">
          <label class="text-[11px] text-slate-400 block mb-1">ឈ្មោះរបស់អ្នក (Full Name)៖</label>
          <input
            v-model="name"
            type="text"
            placeholder="ឧ. ធាន សុខា"
            required
            class="w-full bg-slate-950 border border-slate-700/80 rounded-2xl px-4 py-3 text-xs text-white placeholder-slate-500 focus:outline-none focus:border-amber-400 transition"
          />
        </div>

        <div>
          <label class="text-[11px] text-slate-400 block mb-1">អ៊ីមែល (Email)៖</label>
          <input
            v-model="email"
            type="email"
            placeholder="student@example.com"
            required
            class="w-full bg-slate-950 border border-slate-700/80 rounded-2xl px-4 py-3 text-xs text-white placeholder-slate-500 focus:outline-none focus:border-amber-400 transition"
          />
        </div>

        <div>
          <label class="text-[11px] text-slate-400 block mb-1">ពាក្យសម្ងាត់ (Password)៖</label>
          <input
            v-model="password"
            type="password"
            placeholder="យ៉ាងហោចណាស់ ៦ ខ្ទង់..."
            required
            class="w-full bg-slate-950 border border-slate-700/80 rounded-2xl px-4 py-3 text-xs text-white placeholder-slate-500 focus:outline-none focus:border-amber-400 transition"
          />
        </div>

        <button
          type="submit"
          :disabled="isLoading"
          class="w-full py-3.5 rounded-2xl bg-amber-400 hover:bg-amber-300 text-slate-950 font-bold text-xs transition shadow-lg shadow-amber-400/20 flex items-center justify-center gap-2 mt-2 disabled:opacity-50"
        >
          <i v-if="isLoading" class="fa-solid fa-spinner fa-spin"></i>
          <span>{{ isLoginTab ? 'ចូលរៀនឥឡូវនេះ 🚀' : 'បង្កើតគណនី និងទទួល 100 PTS 🪙' }}</span>
        </button>

      </form>

      <!-- Security Notice -->
      <p class="text-[10px] text-slate-500 text-center font-khmer mt-6">
        🔒 ទិន្នន័យការសិក្សារបស់អ្នកនឹងត្រូវបានរក្សាទុកដោយសុវត្ថិភាព។
      </p>

    </div>
  </div>
</template>
