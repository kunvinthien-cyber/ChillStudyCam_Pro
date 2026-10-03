<script setup>
import { ref } from 'vue'
import apiClient from '../services/api'
import { useUser } from '../composables/useUser'

defineProps({
  show: Boolean
})

const emit = defineEmits(['close'])

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

    // រក្សាទុក Token ក្នុង LocalStorage
    localStorage.setItem('auth_token', res.data.token)
    user.value = res.data.user

    // Reset Form & បិទ Modal
    email.value = ''
    password.value = ''
    name.value = ''
    emit('close')
    alert(res.data.message)
  } catch (err) {
    errorMessage.value = err.response?.data?.message || 'អ៊ីមែល ឬពាក្យសម្ងាត់មិនត្រឹមត្រូវឡើយ!'
  } finally {
    isLoading.value = false
  }
}

// ចូលតាម Telegram (Shortcut ងាយស្រួល)
const loginWithTelegram = () => {
  alert('មុខងារ Telegram នឹងភ្ជាប់ជាមួយ Bot នៅពេល Deploy ឡើងអ៊ីនធឺណិតពិតប្រាកដ!')
}
</script>

<template>
  <div v-if="show" class="fixed inset-0 z-50 bg-black/85 backdrop-blur-md flex items-center justify-center p-4">
    <div class="bg-slate-900 border border-slate-800 rounded-3xl p-6 sm:p-8 max-w-md w-full shadow-2xl relative">

      <!-- Close Button -->
      <button @click="emit('close')" class="absolute top-5 right-5 text-slate-400 hover:text-white text-sm">
        ✕
      </button>

      <!-- Logo Header -->
      <div class="text-center mb-6">
        <div class="w-12 h-12 rounded-2xl bg-amber-400/20 text-amber-400 flex items-center justify-center text-xl mx-auto mb-3">
          ☕
        </div>
        <h2 class="text-xl font-bold text-white tracking-wide">ChillStudy Cambodia</h2>
        <p class="text-xs text-slate-400 font-khmer mt-1">បណ្តាញសង្គម និងការសិក្សាដោយគ្មានសម្ពាធ</p>
      </div>

      <!-- Tab Switcher (Login vs Register) -->
      <div class="grid grid-cols-2 gap-1 bg-slate-950 p-1 rounded-2xl border border-slate-800 mb-6">
        <button
          @click="isLoginTab = true; errorMessage = ''"
          :class="isLoginTab ? 'bg-amber-400 text-slate-950 font-bold' : 'text-slate-400 hover:text-white'"
          class="py-2 rounded-xl text-xs transition"
        >
          ចូលគណនី (Login)
        </button>
        <button
          @click="isLoginTab = false; errorMessage = ''"
          :class="!isLoginTab ? 'bg-amber-400 text-slate-950 font-bold' : 'text-slate-400 hover:text-white'"
          class="py-2 rounded-xl text-xs transition"
        >
          ចុះឈ្មោះថ្មី (Sign Up)
        </button>
      </div>

      <!-- Error Message -->
      <div v-if="errorMessage" class="mb-4 p-3 rounded-2xl bg-rose-500/10 border border-rose-500/20 text-rose-300 text-xs font-khmer text-center">
        {{ errorMessage }}
      </div>

      <!-- Form Inputs -->
      <form @submit.prevent="handleAuth" class="space-y-3.5">

        <!-- Name (បង្ហាញតែពេល Register) -->
        <div v-if="!isLoginTab">
          <label class="text-[10px] text-slate-400 block mb-1">ឈ្មោះរបស់អ្នក (Full Name)៖</label>
          <input
            v-model="name"
            type="text"
            placeholder="ឧ. ធាន សុខា"
            required
            class="w-full bg-slate-950 border border-slate-700 rounded-xl px-3.5 py-2.5 text-xs text-white placeholder-slate-500 focus:outline-none focus:border-amber-400 transition"
          />
        </div>

        <div>
          <label class="text-[10px] text-slate-400 block mb-1">អ៊ីមែល (Email)៖</label>
          <input
            v-model="email"
            type="email"
            placeholder="student@example.com"
            required
            class="w-full bg-slate-950 border border-slate-700 rounded-xl px-3.5 py-2.5 text-xs text-white placeholder-slate-500 focus:outline-none focus:border-amber-400 transition"
          />
        </div>

        <div>
          <label class="text-[10px] text-slate-400 block mb-1">ពាក្យសម្ងាត់ (Password)៖</label>
          <input
            v-model="password"
            type="password"
            placeholder="យ៉ាងហោចណាស់ ៦ ខ្ទង់..."
            required
            class="w-full bg-slate-950 border border-slate-700 rounded-xl px-3.5 py-2.5 text-xs text-white placeholder-slate-500 focus:outline-none focus:border-amber-400 transition"
          />
        </div>

        <button
          type="submit"
          :disabled="isLoading"
          class="w-full py-3 rounded-xl bg-amber-400 hover:bg-amber-300 text-slate-950 font-bold text-xs transition shadow-lg flex items-center justify-center gap-2 mt-4 disabled:opacity-50"
        >
          <i v-if="isLoading" class="fa-solid fa-spinner fa-spin"></i>
          <span>{{ isLoginTab ? 'ចូលរៀនឥឡូវនេះ' : 'បង្កើតគណនី និងទទួល 100 PTS 🪙' }}</span>
        </button>

      </form>

      <!-- Telegram Shortcut -->
      <div class="mt-4 pt-4 border-t border-slate-800 text-center">
        <button
          @click="loginWithTelegram"
          type="button"
          class="w-full py-2.5 rounded-xl bg-[#229ED9]/15 text-[#229ED9] border border-[#229ED9]/30 hover:bg-[#229ED9]/25 text-xs font-semibold transition flex items-center justify-center gap-2"
        >
          <i class="fa-brands fa-telegram text-sm"></i>
          <span>ចូលរហ័សតាម Telegram</span>
        </button>
      </div>

    </div>
  </div>
</template>
