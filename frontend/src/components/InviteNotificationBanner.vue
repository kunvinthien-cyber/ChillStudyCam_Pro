<script setup>
import { ref, onMounted, onUnmounted } from 'vue'
import { useRouter } from 'vue-router'
import apiClient from '../services/api'

const router = useRouter()
const currentInvite = ref(null)
const isResponding = ref(false)
let pollTimer = null

// ១. ឆែកមើលថាតើមានអ្នកណា Invite ខ្លួនឯងដែរឬទេ? (Check My Invites)
const checkInvites = async () => {
  // បើមិនទាន់ Login ទេ មិនបាច់សួរ Server ឡើយ
  const token = localStorage.getItem('auth_token')
  if (!token) return

  try {
    const res = await apiClient.get('/my-invites')
    if (res.data && res.data.length > 0) {
      currentInvite.value = res.data[0] // យក Invite ចុងក្រោយគេបង្អស់
    } else {
      currentInvite.value = null
    }
  } catch (err) {
    // មិនបាច់បង្ហាញ error ពេល polling ទេ
  }
}

// ២. ចុច "យល់ព្រម" ឬ "បដិសេធ" ការ Invite
const respond = async (action) => {
  if (!currentInvite.value || isResponding.value) return

  try {
    isResponding.value = true
    const inviteId = currentInvite.value.id
    const roomId = currentInvite.value.room_id

    await apiClient.post(`/invites/${inviteId}/respond`, { action })

    currentInvite.value = null

    // បើចុច "យល់ព្រម" នាំសិស្សចូលទៅក្នុងបន្ទប់រៀននោះភ្លាមៗ!
    if (action === 'accept') {
      router.push({ name: 'room', params: { id: roomId } })
    }
  } catch (err) {
    alert('មិនអាចឆ្លើយតបការអញ្ជើញបានទេ!')
  } finally {
    isResponding.value = false
  }
}

onMounted(() => {
  checkInvites()
  // ឆែកមើលរៀងរាល់ ៣ វិនាទីម្តង
  pollTimer = setInterval(checkInvites, 3000)
})

onUnmounted(() => {
  if (pollTimer) clearInterval(pollTimer)
})
</script>

<template>
  <!-- 🛡️ ប្រើ Teleport to="body" ដើម្បីឱ្យ Notification លោតលើគេបង្អស់នៃអេក្រង់ជានិច្ច -->
  <Teleport to="body">
    <div
      v-if="currentInvite"
      class="fixed top-5 right-5 z-[99999] max-w-sm w-full bg-slate-900/98 border-2 border-amber-400 p-4 rounded-3xl shadow-2xl backdrop-blur-xl animate-slide-down"
    >
      <div class="flex items-start gap-3">
        <!-- Icon សំបុត្រ -->
        <div class="w-10 h-10 rounded-2xl bg-amber-400/20 text-amber-400 flex items-center justify-center text-lg shrink-0 border border-amber-400/30">
          <i class="fa-solid fa-envelope-open-text animate-pulse"></i>
        </div>

        <div class="flex-1 min-w-0">
          <div class="flex items-center justify-between mb-0.5">
            <h4 class="text-xs font-bold text-white tracking-wide">លិខិតអញ្ជើញចូលរៀន! 📩</h4>
            <span class="w-2 h-2 rounded-full bg-emerald-400 animate-ping"></span>
          </div>

          <p class="text-[11px] text-slate-300 font-khmer leading-relaxed">
            <strong class="text-amber-400">{{ currentInvite.inviter?.name || 'មិត្តភក្តិ' }}</strong> បានអញ្ជើញអ្នកឱ្យចូលរួមរៀនក្នុងបន្ទប់ <strong>«{{ currentInvite.room?.title }}»</strong>
          </p>

          <!-- ប៊ូតុងសកម្មភាព -->
          <div class="flex items-center gap-2 mt-3">
            <button
              @click="respond('accept')"
              :disabled="isResponding"
              class="px-3.5 py-1.5 rounded-xl bg-amber-400 hover:bg-amber-300 text-slate-950 font-bold text-xs shadow-lg transition active:scale-95 disabled:opacity-50 flex items-center gap-1.5"
            >
              <i class="fa-solid fa-check text-xs"></i>
              <span>យល់ព្រមចូលរៀន</span>
            </button>

            <button
              @click="respond('reject')"
              :disabled="isResponding"
              class="px-3 py-1.5 rounded-xl bg-slate-800 text-slate-400 hover:text-white text-xs transition active:scale-95 disabled:opacity-50"
            >
              ✕ បដិសេធ
            </button>
          </div>
        </div>
      </div>
    </div>
  </Teleport>
</template>

<style scoped>
@keyframes slideDown {
  from {
    transform: translateY(-40px);
    opacity: 0;
  }
  to {
    transform: translateY(0);
    opacity: 1;
  }
}
.animate-slide-down {
  animation: slideDown 0.3s ease-out forwards;
}
</style>
