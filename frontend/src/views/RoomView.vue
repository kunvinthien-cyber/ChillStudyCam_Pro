<script setup>
import { ref, computed, onMounted, onUnmounted, nextTick } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import apiClient from '../services/api'
import { useUser } from '../composables/useUser'

const route = useRoute()
const router = useRouter()
const { user, addReward } = useUser()
const roomId = route.params.id

const room = ref(null)
const isLoading = ref(true)

// ==========================================
// 🛡️ ADMIN, PERMISSIONS & INVITE SYSTEM
// ==========================================
// បើអ្នកបង្កើតបន្ទប់ = User កំពុង Login នោះគេជា Admin បន្ទប់
const isAdmin = computed(() => {
  return room.value?.creator_id === user.value?.id || user.value?.id === 1
})

const showInviteModal = ref(false)
const searchUserQuery = ref('')
const searchResults = ref([])
const isSearchingUser = ref(false)

const searchUsersToInvite = async () => {
  if (!searchUserQuery.value.trim()) return
  try {
    isSearchingUser.value = true
    const res = await apiClient.get(`/users/search?q=${encodeURIComponent(searchUserQuery.value)}`)
    searchResults.value = res.data
  } catch (err) {
    console.error('Search user error:', err)
  } finally {
    isSearchingUser.value = false
  }
}

const inviteUser = async (targetUser) => {
  try {
    await apiClient.post(`/rooms/${roomId}/invite`, { invitee_id: targetUser.id })
    alert(`បានផ្ញើលិខិតអញ្ជើញទៅកាន់ ${targetUser.name} រួចរាល់! 📩`)
    showInviteModal.value = false
    searchUserQuery.value = ''
    searchResults.value = []
  } catch (err) {
    alert('មិនអាចផ្ញើការអញ្ជើញបានទេ!')
  }
}

const handleApproval = async (participant, action) => {
  try {
    const res = await apiClient.post(`/rooms/${roomId}/approve/${participant.id}`, { action })
    alert(res.data.message)
    syncParticipants()
  } catch (err) {
    alert('មិនអាចដំណើរការបានទេ!')
  }
}

// ==========================================
// 📑 MULTI-TAB DRAWER ('chat', 'docs', 'members')
// ==========================================
const activeTab = ref('chat')
const showDrawer = ref(true)

// ==========================================
// 🎯 PERSONAL INTENTION / GOAL (Sync)
// ==========================================
const myGoal = ref('រៀនផ្ដោតអារម្មណ៍ ២៥ នាទី')
const isEditingGoal = ref(false)
const goalInput = ref('')

const saveGoal = async () => {
  if (goalInput.value.trim()) {
    myGoal.value = goalInput.value.trim()
    if (myPeerId.value) {
      await apiClient.post(`/rooms/${roomId}/goal`, {
        peer_id: myPeerId.value,
        goal: myGoal.value
      }).catch(() => {})
    }
  }
  isEditingGoal.value = false
}

// ==========================================
// 📄 ROOM DOCUMENTS (ទាញពី ChillLibrary)
// ==========================================
const roomDocs = ref([])
const fetchRoomDocs = async () => {
  try {
    const res = await apiClient.get('/documents')
    roomDocs.value = res.data
  } catch (err) {}
}

// ==========================================
// 🌧️ IN-ROOM AMBIENT AUDIO
// ==========================================
const currentAmbient = ref(null)
let ambientAudio = null
const ambientTracks = [
  { id: 'rain', name: 'ភ្លៀងធ្លាក់', icon: 'fa-cloud-rain', url: 'https://cdn.pixabay.com/download/audio/2022/05/16/audio_db6591201e.mp3' },
  { id: 'cafe', name: 'ហាងកាហ្វេ', icon: 'fa-mug-saucer', url: 'https://cdn.pixabay.com/download/audio/2021/09/06/audio_27318042fa.mp3' }
]

const toggleAmbient = (track) => {
  if (currentAmbient.value?.id === track.id) {
    if (ambientAudio) { ambientAudio.pause(); ambientAudio = null }
    currentAmbient.value = null
  } else {
    if (ambientAudio) ambientAudio.pause()
    ambientAudio = new Audio(track.url)
    ambientAudio.loop = true
    ambientAudio.volume = 0.4
    ambientAudio.play().catch(() => {})
    currentAmbient.value = track
  }
}

// ==========================================
// 🖥️ SCREEN SHARING
// ==========================================
const isScreenSharing = ref(false)
const screenStream = ref(null)
const screenVideoRef = ref(null)

const toggleScreenShare = async () => {
  if (!isScreenSharing.value) {
    try {
      const stream = await navigator.mediaDevices.getDisplayMedia({ video: true, audio: false })
      screenStream.value = stream
      isScreenSharing.value = true
      await nextTick()
      if (screenVideoRef.value) screenVideoRef.value.srcObject = stream
      stream.getVideoTracks()[0].onended = () => stopScreenShare()
    } catch (err) {}
  } else {
    stopScreenShare()
  }
}

const stopScreenShare = () => {
  if (screenStream.value) {
    screenStream.value.getTracks().forEach(track => track.stop())
    screenStream.value = null
  }
  isScreenSharing.value = false
}

// ==========================================
// 🔔 POMODORO ZEN BELL SOUND
// ==========================================
const playZenBell = () => {
  try {
    const audioCtx = new (window.AudioContext || window.webkitAudioContext)()
    const osc = audioCtx.createOscillator()
    const gain = audioCtx.createGain()
    osc.type = 'sine'
    osc.frequency.setValueAtTime(587.33, audioCtx.currentTime)
    osc.frequency.exponentialRampToValueAtTime(880, audioCtx.currentTime + 0.1)
    gain.gain.setValueAtTime(0.8, audioCtx.currentTime)
    gain.gain.exponentialRampToValueAtTime(0.001, audioCtx.currentTime + 3.5)
    osc.connect(gain)
    gain.connect(audioCtx.destination)
    osc.start()
    osc.stop(audioCtx.currentTime + 3.5)
  } catch (e) {}
}

// ==========================================
// 🎙️ WEBRTC VOICE CALL (PEERJS)
// ==========================================
const isInVoice = ref(false)
const isMuted = ref(false)
const myPeerId = ref('')
const voiceUsers = ref([])
const calledPeers = new Set()
let localStream = null
let peer = null

const initPeerVoice = async () => {
  try {
    localStream = await navigator.mediaDevices.getUserMedia({ audio: true, video: false })
    const randomSuffix = Math.random().toString(36).substring(2, 7)
    myPeerId.value = `cs-room${roomId}-${user.value?.id || 'guest'}-${randomSuffix}`

    peer = new window.Peer(myPeerId.value, { debug: 1 })

    peer.on('open', async (id) => {
      isInVoice.value = true
      isMuted.value = false
      await apiClient.post(`/rooms/${roomId}/voice/join`, { peer_id: id })
      syncParticipants()
    })

    peer.on('call', (call) => {
      call.answer(localStream)
      call.on('stream', (remoteStream) => {
        playRemoteAudio(call.peer, remoteStream)
      })
    })
  } catch (err) {
    alert('សូមអនុញ្ញាតសិទ្ធិ Microphone ក្នុង Browser!')
  }
}

const callOtherPeers = (participants) => {
  if (!isInVoice.value || !peer || !localStream) return
  participants.forEach(p => {
    if (p.peer_id && p.peer_id !== myPeerId.value && !calledPeers.has(p.peer_id)) {
      calledPeers.add(p.peer_id)
      const call = peer.call(p.peer_id, localStream)
      call.on('stream', (remoteStream) => {
        playRemoteAudio(p.peer_id, remoteStream)
      })
    }
  })
}

const playRemoteAudio = (peerId, stream) => {
  let audioEl = document.getElementById(`audio-${peerId}`)
  if (!audioEl) {
    audioEl = document.createElement('audio')
    audioEl.id = `audio-${peerId}`
    audioEl.autoplay = true
    document.body.appendChild(audioEl)
  }
  audioEl.srcObject = stream
}

const toggleVoiceJoin = async () => {
  if (!isInVoice.value) {
    await initPeerVoice()
  } else {
    if (myPeerId.value) {
      await apiClient.post(`/rooms/${roomId}/voice/leave`, { peer_id: myPeerId.value }).catch(() => {})
    }
    if (localStream) localStream.getTracks().forEach(t => t.stop())
    if (peer) peer.destroy()
    document.querySelectorAll('[id^="audio-cs-room"]').forEach(el => el.remove())

    calledPeers.clear()
    isInVoice.value = false
    isMuted.value = true
    myPeerId.value = ''
    syncParticipants()
  }
}

const toggleMute = () => {
  if (!localStream) return
  isMuted.value = !isMuted.value
  localStream.getAudioTracks().forEach(t => t.enabled = !isMuted.value)
}

const allParticipants = ref([])
const pendingRequests = computed(() => {
  return allParticipants.value.filter(p => p.study_goal === 'pending_approval')
})

const syncParticipants = async () => {
  try {
    const res = await apiClient.get(`/rooms/${roomId}/participants`)
    allParticipants.value = res.data
    voiceUsers.value = res.data.filter(p => p.is_in_voice)
    if (isInVoice.value) {
      callOtherPeers(voiceUsers.value)
    }
  } catch (err) {}
}

// ==========================================
// 💬 CHAT & STICKERS & TIMER
// ==========================================
const chatMessages = ref([])
const newChatMessage = ref('')
const isSendingMessage = ref(false)
const chatContainer = ref(null)
const floatingStickers = ref([])
const displayedReactionIds = new Set()
let syncTimer = null

const stickerOptions = [
  { key: 'clap', emoji: '👏', icon: 'fa-solid fa-hands-clapping', label: 'ស៊ូៗ' },
  { key: 'coffee', emoji: '☕', icon: 'fa-solid fa-mug-hot', label: 'កាហ្វេ' },
  { key: 'power', emoji: '💪', icon: 'fa-solid fa-dumbbell', label: 'តស៊ូ' },
  { key: 'love', emoji: '❤️', icon: 'fa-solid fa-heart', label: 'ចូលចិត្ត' }
]

const sendSticker = async (stickerKey) => {
  const selectedSticker = stickerOptions.find((item) => item.key === stickerKey) || stickerOptions[0]

  try {
    animateSticker(selectedSticker, user.value?.name || 'You')
    await apiClient.post(`/rooms/${roomId}/reactions`, { emoji: selectedSticker.emoji })
  } catch (err) {}
}

const fetchRemoteReactions = async () => {
  try {
    const res = await apiClient.get(`/rooms/${roomId}/reactions`)
    res.data.forEach(r => {
      if (!displayedReactionIds.has(r.id)) {
        displayedReactionIds.add(r.id)
        const remoteSticker = stickerOptions.find((item) => item.emoji === r.emoji) || {
          emoji: r.emoji,
          icon: 'fa-solid fa-star',
          label: 'reaction'
        }

        if (r.user_name !== (user.value?.name || 'You')) {
          animateSticker(remoteSticker, r.user_name)
        }
      }
    })
  } catch (err) {}
}

const animateSticker = (sticker, senderName) => {
  const id = Math.random()
  floatingStickers.value.push({
    id,
    emoji: sticker.emoji,
    iconClass: sticker.icon,
    senderName,
    x: Math.random() * 60 + 20
  })

  setTimeout(() => {
    floatingStickers.value = floatingStickers.value.filter(s => s.id !== id)
  }, 3000)
}

const fetchMessages = async () => {
  try {
    const res = await apiClient.get(`/rooms/${roomId}/messages`)
    chatMessages.value = res.data
    await nextTick()
    if (chatContainer.value) chatContainer.value.scrollTop = chatContainer.value.scrollHeight
  } catch (err) {}
}

const sendChatMessage = async () => {
  if (!newChatMessage.value.trim() || isSendingMessage.value) return
  const msg = newChatMessage.value
  newChatMessage.value = ''
  isSendingMessage.value = true

  try {
    const res = await apiClient.post(`/rooms/${roomId}/messages`, { message: msg })
    chatMessages.value.push(res.data)
    await nextTick()
    if (chatContainer.value) chatContainer.value.scrollTop = chatContainer.value.scrollHeight
  } catch (err) {
    alert('មិនអាចផ្ញើសារបានទេ!')
  } finally {
    isSendingMessage.value = false
  }
}

// Timer
const modes = { focus: 25 * 60, shortBreak: 5 * 60, longBreak: 15 * 60 }
const currentMode = ref('focus')
const timeLeft = ref(modes.focus)
const isRunning = ref(false)
let timerInterval = null
const showCelebration = ref(false)

const formattedTime = computed(() => {
  const m = Math.floor(timeLeft.value / 60)
  const s = timeLeft.value % 60
  return `${String(m).padStart(2, '0')}:${String(s).padStart(2, '0')}`
})

const startTimer = () => {
  if (isRunning.value) return
  isRunning.value = true
  timerInterval = setInterval(() => {
    if (timeLeft.value > 0) timeLeft.value--
    else {
      isRunning.value = false
      clearInterval(timerInterval)
      playZenBell()
      claimReward()
    }
  }, 1000)
}

const claimReward = async () => {
  try {
    const res = await apiClient.post('/study/complete', { room_id: roomId, duration_minutes: 25 })
    addReward(res.data.reward.earned_coins, res.data.reward.studied_hours)
    showCelebration.value = true
  } catch (err) {}
}

const fetchRoomDetail = async () => {
  try {
    isLoading.value = true
    const res = await apiClient.get(`/rooms/${roomId}`)
    room.value = res.data
  } finally {
    isLoading.value = false
  }
}

onMounted(() => {
  fetchRoomDetail()
  fetchMessages()
  syncParticipants()
  fetchRoomDocs()

  syncTimer = setInterval(() => {
    fetchMessages()
    fetchRemoteReactions()
    syncParticipants()
  }, 2500)
})

onUnmounted(async () => {
  clearInterval(timerInterval)
  clearInterval(syncTimer)
  stopScreenShare()
  if (ambientAudio) ambientAudio.pause()
  if (myPeerId.value) {
    await apiClient.post(`/rooms/${roomId}/voice/leave`, { peer_id: myPeerId.value }).catch(() => {})
  }
  if (localStream) localStream.getTracks().forEach(t => t.stop())
  if (peer) peer.destroy()
  document.querySelectorAll('[id^="audio-cs-room"]').forEach(el => el.remove())
})
</script>

<template>
  <div v-if="isLoading" class="text-center py-24 text-slate-400">
    <div class="w-8 h-8 border-4 border-amber-400 border-t-transparent rounded-full animate-spin mx-auto mb-3"></div>
    កំពុងចូលទៅកាន់បន្ទប់សិក្សា...
  </div>

  <div v-else-if="room" class="relative min-h-[88vh] flex flex-col justify-between rounded-3xl overflow-hidden border border-slate-800 bg-slate-950/70 p-4 sm:p-6 backdrop-blur-xl">

    <!-- Background Backdrop -->
    <div class="absolute inset-0 bg-cover bg-center opacity-15 pointer-events-none filter blur-sm scale-105" :style="{ backgroundImage: `url(${room.thumbnail})` }"></div>

    <!-- Floating Live Stickers -->
    <div class="absolute inset-0 pointer-events-none overflow-hidden z-30">
      <div v-for="s in floatingStickers" :key="s.id" :style="{ left: `${s.x}%` }" class="absolute bottom-20 flex flex-col items-center animate-float-up">
        <span class="text-xs px-2.5 py-0.5 rounded-full bg-slate-900/95 text-amber-300 font-bold border border-amber-500/30 mb-1 shadow-lg font-khmer">
          {{ s.senderName }}
        </span>
        <i :class="[s.iconClass, 'text-2xl text-amber-300 drop-shadow-md']"></i>
      </div>
    </div>

    <!-- ============================================== -->
    <!-- 1. TOP HEADER: TITLE, INVITE BUTTON & MODES     -->
    <!-- ============================================== -->
    <div class="relative z-10 flex flex-wrap items-center justify-between gap-3 border-b border-slate-800/80 pb-4">
      <div class="flex items-center gap-3">
        <button @click="router.push('/')" class="px-3.5 py-1.5 rounded-xl bg-slate-900 hover:bg-slate-800 text-slate-300 text-xs font-semibold flex items-center gap-2 border border-slate-700 transition">
          <i class="fa-solid fa-arrow-left"></i> ចាកចេញ
        </button>
        <div>
          <div class="flex items-center gap-2">
            <h2 class="text-sm font-bold text-white">{{ room.title }}</h2>
            <span class="px-2 py-0.5 rounded-full text-[9px] font-bold bg-amber-400/10 text-amber-400 border border-amber-400/20">
              {{ room.badge_tag || 'Community' }}
            </span>
            <span v-if="isAdmin" class="px-2 py-0.5 rounded-full text-[9px] font-bold bg-purple-500/20 text-purple-300 border border-purple-500/30">
              👑 អ្នកជា Admin
            </span>
          </div>
          <p class="text-[11px] text-slate-400 font-khmer mt-0.5">{{ room.subtitle_khmer }}</p>
        </div>
      </div>

      <div class="flex items-center gap-2">
        <!-- ប៊ូតុង Invite មិត្តភក្តិ -->
        <button
          @click="showInviteModal = true"
          class="px-3 py-1 rounded-full text-xs font-semibold bg-slate-900 hover:bg-slate-800 text-amber-400 border border-amber-500/30 transition flex items-center gap-1.5 shadow"
        >
          <i class="fa-solid fa-user-plus text-[10px]"></i>
          <span>អញ្ជើញ (Invite)</span>
        </button>

        <!-- ប៊ូតុង Share Screen -->
        <button
          @click="toggleScreenShare"
          :class="isScreenSharing ? 'bg-rose-500 text-white' : 'bg-slate-900 text-slate-300 border border-slate-700'"
          class="px-3.5 py-1 rounded-full text-xs transition flex items-center gap-1.5 shadow"
        >
          <i :class="isScreenSharing ? 'fa-solid fa-desktop' : 'fa-solid fa-arrow-up-from-bracket'"></i>
          <span>{{ isScreenSharing ? 'បិទ Screen' : 'Share Screen' }}</span>
        </button>

        <!-- Drawer Toggle -->
        <button
          @click="showDrawer = !showDrawer"
          :class="showDrawer ? 'bg-amber-400 text-slate-950 font-bold' : 'bg-slate-900 text-slate-300 border border-slate-700'"
          class="px-3 py-1 rounded-full text-xs transition flex items-center gap-1.5 shadow"
        >
          <i class="fa-solid fa-sidebar"></i>
          <span>{{ showDrawer ? 'បង្រួមផ្ទាំង' : 'បើកផ្ទាំងជំនួយ' }}</span>
        </button>
      </div>
    </div>

    <!-- ============================================== -->
    <!-- 🔔 ADMIN ALERT: មានសំណើសុំចូលបន្ទប់ (JOIN REQUESTS) -->
    <!-- ============================================== -->
    <div v-if="isAdmin && pendingRequests.length > 0" class="relative z-10 my-2 p-3 rounded-2xl bg-amber-500/15 border border-amber-500/40 flex items-center justify-between gap-3 animate-pulse">
      <div class="flex items-center gap-2 text-xs text-amber-300 font-khmer">
        <i class="fa-solid fa-bell"></i>
        <span>មានសិស្ស <strong>{{ pendingRequests.length }} នាក់</strong> កំពុងរង់ចាំការអនុញ្ញាតចូលបន្ទប់ពីអ្នក!</span>
      </div>
      <button @click="activeTab = 'members'; showDrawer = true" class="px-3 py-1 rounded-xl bg-amber-400 text-slate-950 font-bold text-xs hover:bg-amber-300 transition">
        ពិនិត្យសំណើ
      </button>
    </div>

    <!-- ============================================== -->
    <!-- 🎙️ VOICE CHANNEL DOCK                          -->
    <!-- ============================================== -->
    <div class="relative z-10 my-2.5 bg-slate-900/90 border border-slate-800 p-3 rounded-2xl flex flex-wrap items-center justify-between gap-3 shadow-lg">
      <div class="flex items-center gap-3">
        <div class="flex items-center gap-2">
          <span class="w-2.5 h-2.5 rounded-full" :class="isInVoice ? 'bg-emerald-400 animate-ping' : 'bg-slate-600'"></span>
          <span class="text-xs font-bold text-white flex items-center gap-1.5">
            <i class="fa-solid fa-headphones text-amber-400"></i> Voice Lounge
          </span>
        </div>

        <div class="flex items-center -space-x-2 overflow-hidden pl-1">
          <div
            v-for="vu in voiceUsers"
            :key="vu.id"
            class="w-7 h-7 rounded-full bg-slate-800 text-amber-300 ring-2 ring-emerald-400/80 text-[10px] font-bold flex items-center justify-center relative shadow"
            :title="vu.user_name"
          >
            {{ vu.user_name.charAt(0) }}
          </div>
        </div>

        <span class="text-[10px] text-slate-400 font-khmer">
          {{ voiceUsers.length > 0 ? `(${voiceUsers.length} នាក់ក្នុង Call)` : '(គ្មានអ្នកក្នុង Call ទេ)' }}
        </span>
      </div>

      <div class="flex items-center gap-2">
        <button
          @click="toggleVoiceJoin"
          :class="isInVoice ? 'bg-rose-500/20 text-rose-300 border-rose-500/40 hover:bg-rose-500/30' : 'bg-emerald-500/20 text-emerald-300 border-emerald-500/40 hover:bg-emerald-500/30'"
          class="px-3.5 py-1.5 rounded-xl border text-xs font-semibold flex items-center gap-1.5 transition"
        >
          <i :class="isInVoice ? 'fa-solid fa-phone-slash' : 'fa-solid fa-phone'"></i>
          <span>{{ isInVoice ? 'ចាកចេញពី Voice' : 'ចូលរួម Call សំឡេង' }}</span>
        </button>

        <button
          v-if="isInVoice"
          @click="toggleMute"
          :class="isMuted ? 'bg-rose-500 text-white' : 'bg-slate-800 text-emerald-400 border border-slate-700'"
          class="w-8 h-8 rounded-xl flex items-center justify-center text-xs transition"
          :title="isMuted ? 'បើក Mic' : 'បិទ Mic'"
        >
          <i :class="isMuted ? 'fa-solid fa-microphone-slash' : 'fa-solid fa-microphone'"></i>
        </button>
      </div>
    </div>

    <!-- 🖥️ SCREEN SHARE PREVIEW -->
    <div v-if="isScreenSharing" class="relative z-10 w-full max-w-2xl mx-auto my-2 rounded-3xl overflow-hidden border border-amber-400/30 bg-black shadow-2xl animate-scale-up">
      <video ref="screenVideoRef" autoplay playsinline class="w-full h-64 sm:h-72 object-contain"></video>
      <div class="absolute top-3 left-3 px-3 py-1 rounded-full bg-black/70 backdrop-blur-md text-amber-300 text-[10px] font-bold border border-amber-500/20 flex items-center gap-1.5">
        <span class="w-2 h-2 rounded-full bg-rose-500 animate-ping"></span>
        កំពុង Share អេក្រង់បន្តផ្ទាល់
      </div>
    </div>

    <!-- ============================================== -->
    <!-- 2. CENTER: POMODORO & MULTI-TAB WORKSPACE       -->
    <!-- ============================================== -->
    <div class="relative z-10 flex-1 grid grid-cols-1 lg:grid-cols-12 gap-6 items-center my-3">

      <!-- POMODORO TIMER -->
      <div :class="showDrawer ? 'lg:col-span-7 xl:col-span-8' : 'lg:col-span-12'" class="flex flex-col items-center justify-center transition-all">
        <div class="bg-slate-900/90 border border-slate-800 p-6 sm:p-8 rounded-3xl shadow-2xl max-w-sm w-full text-center relative">

          <div class="flex items-center justify-center gap-1 p-1 bg-slate-950/80 rounded-2xl border border-slate-800 mb-5">
            <button @click="currentMode = 'focus'; timeLeft = modes.focus" :class="currentMode === 'focus' ? 'bg-amber-400 text-slate-950 font-bold' : 'text-slate-400'" class="px-3 py-1.5 rounded-xl text-xs transition">
              Focus (25m)
            </button>
            <button @click="currentMode = 'shortBreak'; timeLeft = modes.shortBreak" :class="currentMode === 'shortBreak' ? 'bg-amber-400 text-slate-950 font-bold' : 'text-slate-400'" class="px-3 py-1.5 rounded-xl text-xs transition">
              Break (5m)
            </button>
          </div>

          <div class="text-6xl font-black font-mono tracking-tighter text-white mb-5 select-none">
            {{ formattedTime }}
          </div>

          <div class="flex items-center justify-center gap-3">
            <button
              @click="isRunning ? (isRunning = false) : startTimer()"
              :class="isRunning ? 'bg-amber-500 text-slate-950' : 'bg-emerald-400 text-slate-950'"
              class="px-6 py-2.5 rounded-2xl font-bold text-xs transition shadow-lg hover:scale-105 flex items-center gap-2"
            >
              <i :class="isRunning ? 'fa-solid fa-pause' : 'fa-solid fa-play'"></i>
              <span>{{ isRunning ? 'ផ្អាក (Pause)' : 'ចាប់ផ្តើមផ្តោត' }}</span>
            </button>
            <button @click="timeLeft = modes[currentMode]" class="w-10 h-10 rounded-2xl bg-slate-800 text-slate-300 flex items-center justify-center hover:bg-slate-700 transition" title="Reset">
              <i class="fa-solid fa-rotate-right text-xs"></i>
            </button>
          </div>

          <!-- 🎯 គោលដៅសិក្សារបស់ខ្ញុំ (Personal Goal) -->
          <div class="mt-6 p-3 rounded-2xl bg-slate-950 border border-amber-500/30">
            <div class="flex items-center justify-between mb-1">
              <span class="text-[10px] font-bold text-amber-400 uppercase tracking-wider flex items-center gap-1.5">
                <i class="fa-solid fa-bullseye"></i> គោលដៅជុំនេះរបស់ខ្ញុំ៖
              </span>
              <button v-if="!isEditingGoal" @click="isEditingGoal = true; goalInput = myGoal" class="text-[10px] text-slate-400 hover:text-amber-400 flex items-center gap-1 transition">
                <i class="fa-solid fa-pen-to-square"></i> កែប្រែ
              </button>
            </div>

            <div v-if="!isEditingGoal" class="text-xs font-bold text-white font-khmer flex items-center justify-between">
              <span class="truncate">« {{ myGoal }} »</span>
              <span class="text-[9px] px-2 py-0.5 rounded-full bg-emerald-500/10 text-emerald-400 font-normal shrink-0 ml-2">កំពុងធ្វើ</span>
            </div>

            <form v-else @submit.prevent="saveGoal" class="flex items-center gap-1.5 mt-1">
              <input v-model="goalInput" type="text" placeholder="ឧ. ធ្វើលំហាត់គណិត ៥..." autofocus class="flex-1 bg-slate-900 border border-amber-400 rounded-xl px-3 py-1.5 text-xs text-white focus:outline-none" />
              <button type="submit" class="px-3 py-1.5 rounded-xl bg-amber-400 text-slate-950 font-bold text-xs">រក្សា</button>
            </form>
          </div>

          <div class="mt-2">
            <button @click="claimReward" class="text-[9px] text-amber-400/80 hover:text-amber-300 underline font-khmer">
              ⚡ ចុចតេស្តទទួលរង្វាន់ +25 PTS
            </button>
          </div>
        </div>
      </div>

      <!-- ============================================== -->
      <!-- MULTI-TAB SIDE DRAWER (CHAT / DOCS / MEMBERS)   -->
      <!-- ============================================== -->
      <div v-if="showDrawer" class="lg:col-span-5 xl:col-span-4 bg-slate-900/95 border border-slate-800 rounded-3xl p-4 flex flex-col h-88 lg:h-96 shadow-xl animate-fade-in">

        <!-- Tab Selector Bar -->
        <div class="grid grid-cols-3 gap-1 bg-slate-950 p-1 rounded-2xl border border-slate-800 mb-3 shrink-0">
          <button @click="activeTab = 'chat'" :class="activeTab === 'chat' ? 'bg-amber-400 text-slate-950 font-bold' : 'text-slate-400 hover:text-white'" class="py-1.5 rounded-xl text-xs transition flex items-center justify-center gap-1">
            <i class="fa-solid fa-comments text-[11px]"></i> <span>ជជែក</span>
          </button>
          <button @click="activeTab = 'docs'" :class="activeTab === 'docs' ? 'bg-amber-400 text-slate-950 font-bold' : 'text-slate-400 hover:text-white'" class="py-1.5 rounded-xl text-xs transition flex items-center justify-center gap-1">
            <i class="fa-solid fa-file-pdf text-[11px]"></i> <span>ឯកសារ</span>
          </button>
          <button @click="activeTab = 'members'" :class="activeTab === 'members' ? 'bg-amber-400 text-slate-950 font-bold' : 'text-slate-400 hover:text-white'" class="py-1.5 rounded-xl text-xs transition flex items-center justify-center gap-1">
            <i class="fa-solid fa-users text-[11px]"></i> <span>សមាជិក</span>
          </button>
        </div>

        <!-- TAB 1: 💬 CHAT -->
        <div v-if="activeTab === 'chat'" class="flex-1 flex flex-col overflow-hidden">
          <div ref="chatContainer" class="flex-1 overflow-y-auto space-y-2.5 pr-1 text-xs scrollbar-thin">
            <div v-if="chatMessages.length === 0" class="text-center py-10 text-slate-500 text-[11px] font-khmer">
              មិនទាន់មានសារនៅឡើយទេ។ ចាប់ផ្តើមជជែកស្វាគមន៍គ្នា! 👋
            </div>
            <div v-for="msg in chatMessages" :key="msg.id" :class="msg.user_name === (user?.name || '') ? 'items-end' : 'items-start'" class="flex flex-col">
              <span class="text-[10px] text-slate-400 mb-0.5 font-bold text-amber-400">{{ msg.user_name }}</span>
              <div :class="msg.user_name === (user?.name || '') ? 'bg-amber-400 text-slate-950 font-medium' : 'bg-slate-800 text-slate-200'" class="p-2 px-3 rounded-2xl max-w-[90%] text-[11px] leading-relaxed break-words font-khmer shadow">
                {{ msg.message }}
              </div>
            </div>
          </div>

          <form @submit.prevent="sendChatMessage" class="relative pt-2 border-t border-slate-800 mt-2 flex items-center gap-1.5 shrink-0">
            <input v-model="newChatMessage" type="text" placeholder="សួរ ឬជជែកគ្នា..." class="flex-1 bg-slate-950 border border-slate-700 rounded-xl px-3 py-2 text-xs text-white placeholder-slate-500 focus:outline-none focus:border-amber-400" />
            <button type="submit" :disabled="isSendingMessage || !newChatMessage.trim()" class="w-8 h-8 rounded-xl bg-amber-400 text-slate-950 flex items-center justify-center text-xs transition disabled:opacity-30">
              <i class="fa-solid fa-paper-plane"></i>
            </button>
          </form>
        </div>

        <!-- TAB 2: 📄 IN-ROOM DOCS -->
        <div v-else-if="activeTab === 'docs'" class="flex-1 overflow-y-auto space-y-2 pr-1 scrollbar-thin">
          <div class="text-[10px] text-slate-400 font-khmer mb-2 flex items-center justify-between">
            <span>ឯកសារមេរៀន និងវិញ្ញាសាក្នុងបន្ទប់</span>
            <router-link to="/library" class="text-amber-400 hover:underline">ចូលបណ្ណាល័យធំ</router-link>
          </div>
          <div v-for="d in roomDocs" :key="d.id" class="p-2.5 rounded-2xl bg-slate-950 border border-slate-800 flex items-center justify-between gap-2">
            <div class="min-w-0">
              <h5 class="text-xs font-bold text-white truncate">{{ d.title }}</h5>
              <span class="text-[9px] text-amber-400 font-khmer">{{ d.subject }}</span>
            </div>
            <a :href="d.file_url" target="_blank" class="px-2.5 py-1 rounded-xl bg-slate-800 hover:bg-amber-400 hover:text-slate-950 text-slate-300 text-[10px] font-semibold transition shrink-0 flex items-center gap-1">
              <i class="fa-solid fa-arrow-up-right-from-square"></i> <span>បើកមើល</span>
            </a>
          </div>
        </div>

        <!-- TAB 3: 👥 MEMBERS & ADMIN APPROVALS -->
        <div v-else-if="activeTab === 'members'" class="flex-1 overflow-y-auto space-y-3 pr-1 scrollbar-thin">

          <!-- សំណើសុំចូលបន្ទប់ (បង្ហាញតែចំពោះ Admin) -->
          <div v-if="isAdmin && pendingRequests.length > 0" class="p-3 rounded-2xl bg-amber-500/10 border border-amber-500/30 space-y-2">
            <span class="text-[10px] font-bold text-amber-400 uppercase tracking-wider block">
              🔔 សំណើសុំចូលរៀន (Pending Requests)
            </span>
            <div v-for="req in pendingRequests" :key="req.id" class="flex items-center justify-between gap-2 p-2 rounded-xl bg-slate-950 border border-slate-800">
              <span class="text-xs font-bold text-white">{{ req.user_name }}</span>
              <div class="flex items-center gap-1">
                <button @click="handleApproval(req, 'approve')" class="px-2 py-1 rounded-lg bg-emerald-500 text-slate-950 font-bold text-[10px] hover:bg-emerald-400">
                  ✓ អនុញ្ញាត
                </button>
                <button @click="handleApproval(req, 'reject')" class="px-2 py-1 rounded-lg bg-rose-500/20 text-rose-300 text-[10px] hover:bg-rose-500/30">
                  ✕ បដិសេធ
                </button>
              </div>
            </div>
          </div>

          <!-- បញ្ជីសមាជិកកំពុងរៀន -->
          <div class="space-y-1.5">
            <span class="text-[10px] text-slate-400 font-khmer block mb-1">សមាជិកក្នុងបន្ទប់៖</span>

            <!-- ខ្លួនឯង -->
            <div class="p-2.5 rounded-2xl bg-slate-950 border border-amber-500/20 flex items-center justify-between">
              <div class="flex items-center gap-2 min-w-0">
                <span class="w-6 h-6 rounded-full bg-amber-400 text-slate-950 font-bold text-[10px] flex items-center justify-center shrink-0">
                  {{ user?.name ? user.name.charAt(0) : 'U' }}
                </span>
                <div class="min-w-0">
                  <p class="text-xs font-bold text-white truncate">{{ user?.name || 'You' }} (អ្នក)</p>
                  <p class="text-[10px] text-amber-300 font-khmer truncate">🎯 {{ myGoal }}</p>
                </div>
              </div>
              <span class="text-[9px] px-2 py-0.5 rounded-full bg-emerald-500/10 text-emerald-400 shrink-0">កំពុងរៀន</span>
            </div>

            <!-- សមាជិកដទៃ -->
            <div v-for="vu in voiceUsers.filter(u => u.user_name !== (user?.name || ''))" :key="vu.id" class="p-2.5 rounded-2xl bg-slate-950 border border-slate-800 flex items-center justify-between">
              <div class="flex items-center gap-2 min-w-0">
                <span class="w-6 h-6 rounded-full bg-slate-800 text-amber-400 font-bold text-[10px] flex items-center justify-center shrink-0">
                  {{ vu.user_name.charAt(0) }}
                </span>
                <div class="min-w-0">
                  <p class="text-xs font-bold text-white truncate">{{ vu.user_name }}</p>
                  <p class="text-[10px] text-slate-400 font-khmer truncate">🎯 {{ vu.study_goal || 'រៀនផ្ដោតអារម្មណ៍' }}</p>
                </div>
              </div>
              <span class="text-[9px] px-2 py-0.5 rounded-full bg-slate-800 text-slate-400 shrink-0">On Mic</span>
            </div>
          </div>
        </div>

      </div>

    </div>

    <!-- ============================================== -->
    <!-- 3. BOTTOM BAR: AMBIENT & STICKERS              -->
    <!-- ============================================== -->
    <div class="relative z-10 flex flex-wrap items-center justify-between gap-3 pt-3 border-t border-slate-800/80">

      <!-- Ambient Audio Toggles -->
      <div class="flex items-center gap-1.5 bg-slate-900/90 border border-slate-800 p-1 rounded-2xl">
        <span class="text-[10px] text-slate-400 font-khmer px-2 hidden sm:inline">សំឡេង Ambient:</span>
        <button
          v-for="at in ambientTracks"
          :key="at.id"
          @click="toggleAmbient(at)"
          :class="currentAmbient?.id === at.id ? 'bg-amber-400 text-slate-950 font-bold shadow' : 'bg-slate-800/60 text-slate-300 hover:text-white'"
          class="px-2.5 py-1 rounded-xl text-xs flex items-center gap-1.5 transition"
        >
          <i :class="['fa-solid text-[10px]', at.icon]"></i>
          <span>{{ at.name }}</span>
        </button>
      </div>

      <!-- Live Reactions -->
      <div class="flex items-center gap-2">
        <span class="text-xs text-slate-400 font-khmer hidden md:inline">ផ្ញើកម្លាំងចិត្ត៖</span>
        <button
          v-for="sticker in stickerOptions"
          :key="sticker.key"
          @click="sendSticker(sticker.key)"
          class="px-3.5 py-1.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-xs hover:scale-110 active:scale-95 transition flex items-center gap-1.5"
        >
          <i :class="sticker.icon"></i>
          <span class="text-slate-300 font-khmer">{{ sticker.label }}</span>
        </button>
      </div>

    </div>

    <!-- ============================================== -->
    <!-- 🔍 MODAL អញ្ជើញសមាជិក (INVITE MEMBERS)          -->
    <!-- ============================================== -->
    <div v-if="showInviteModal" class="fixed inset-0 z-50 bg-black/85 backdrop-blur-md flex items-center justify-center p-4">
      <div class="bg-slate-900 border border-slate-800 rounded-3xl p-6 sm:p-8 max-w-md w-full shadow-2xl relative">
        <button @click="showInviteModal = false" class="absolute top-5 right-5 text-slate-400 hover:text-white">✕</button>

        <h3 class="text-sm font-bold text-white mb-1 flex items-center gap-2">
          <i class="fa-solid fa-user-plus text-amber-400"></i> អញ្ជើញមិត្តភក្តិចូលរៀន
        </h3>
        <p class="text-xs text-slate-400 font-khmer mb-4">ស្វែងរកឈ្មោះគណនីមិត្តភក្តិដើម្បីផ្ញើលិខិតអញ្ជើញ៖</p>

        <!-- Search Input -->
        <div class="relative mb-3">
          <input
            v-model="searchUserQuery"
            @input="searchUsersToInvite"
            type="text"
            placeholder="វាយឈ្មោះគណនី (ឧ. ដារា, សុខា, vibul)..."
            class="w-full bg-slate-950 border border-slate-700 rounded-xl pl-3 pr-9 py-2.5 text-xs text-white placeholder-slate-500 focus:outline-none focus:border-amber-400"
          />
          <span class="absolute right-3 top-3 text-slate-500 text-xs">🔍</span>
        </div>

        <!-- Search Results -->
        <div class="space-y-2 max-h-56 overflow-y-auto pr-1">
          <div v-if="isSearchingUser" class="text-center py-4 text-xs text-slate-400">កំពុងស្វែងរក...</div>
          <div v-else-if="searchResults.length === 0 && searchUserQuery" class="text-center py-4 text-xs text-slate-500 font-khmer">
            រកមិនឃើញឈ្មោះនេះទេ។
          </div>

          <div
            v-for="u in searchResults"
            :key="u.id"
            class="p-2.5 rounded-2xl bg-slate-950 border border-slate-800 flex items-center justify-between"
          >
            <div>
              <p class="text-xs font-bold text-white">{{ u.name }}</p>
              <p class="text-[10px] text-amber-400 font-mono">{{ u.rank_title || 'Scholar' }} • {{ u.email }}</p>
            </div>
            <button
              @click="inviteUser(u)"
              class="px-3 py-1 rounded-xl bg-amber-400 hover:bg-amber-300 text-slate-950 font-bold text-[10px] transition"
            >
              + អញ្ជើញ
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Celebration Modal -->
    <div v-if="showCelebration" class="fixed inset-0 z-50 bg-black/80 backdrop-blur-md flex items-center justify-center p-4">
      <div class="bg-slate-900 border border-amber-500/30 rounded-3xl p-8 max-w-sm w-full text-center shadow-2xl animate-scale-up">
        <div class="w-16 h-16 rounded-full bg-amber-400/20 text-amber-400 flex items-center justify-center text-3xl mx-auto mb-4 animate-bounce">🏆</div>
        <h3 class="text-xl font-extrabold text-white mb-1">អបអរសាទរ! 🎉</h3>
        <p class="text-xs text-slate-400 font-khmer mb-6">អ្នកបានសម្រេចគោលដៅរៀន ២៥ នាទីពេញ!</p>
        <button @click="showCelebration = false" class="w-full py-2.5 rounded-xl bg-amber-400 text-slate-950 font-bold text-xs transition shadow-lg">បន្តរៀនទៀត 🔥</button>
      </div>
    </div>

  </div>
</template>

<style scoped>
@keyframes floatUp {
  0% { opacity: 1; transform: translateY(0) scale(0.8); }
  100% { opacity: 0; transform: translateY(-240px) scale(1.3); }
}
.animate-float-up {
  animation: floatUp 3s ease-out forwards;
}
@keyframes scaleUp {
  from { transform: scale(0.9); opacity: 0; }
  to { transform: scale(1); opacity: 1; }
}
.animate-scale-up {
  animation: scaleUp 0.25s ease-out forwards;
}
</style>

