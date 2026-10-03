<script setup>
import { ref, computed, onMounted, nextTick } from 'vue'
import apiClient from '../services/api'
import AiVoiceCallModal from './AiVoiceCallModal.vue'

const showVoiceCallModal = ref(false)
const showHistoryDrawer = ref(false)
const isFullScreen = ref(false)

const conversations = ref([])
const activeConversationId = ref(null)
const activeConversationTitle = ref('')

const messages = ref([])
const userInput = ref('')
const isLoading = ref(false)
const chatContainer = ref(null)
const fullChatContainer = ref(null)

// 📸 MULTIMODAL IMAGE ATTACHMENT STATES
const attachedImage = ref(null) // Base64 Data
const fileInputRef = ref(null)
const isVoiceRecording = ref(false)

// Markdown Parser
const formatMarkdown = (text) => {
  if (!text) return ''
  return text
    .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
    .replace(/```([\s\S]*?)```/g, '<pre class="bg-slate-950 p-3 rounded-2xl border border-slate-800 my-2 overflow-x-auto text-[11px] font-mono text-emerald-400"><code>$1</code></pre>')
    .replace(/`([^`]+)`/g, '<code class="bg-slate-950 px-1.5 py-0.5 rounded-md text-amber-300 font-mono text-[10px] border border-slate-800">$1</code>')
    .replace(/^### (.*$)/gim, '<h3 class="text-xs font-bold text-amber-400 mt-2 mb-1">$1</h3>')
    .replace(/^## (.*$)/gim, '<h2 class="text-sm font-extrabold text-white mt-3 mb-1.5">$1</h2>')
    .replace(/\*\*(.*?)\*\*/g, '<strong class="text-white font-bold">$1</strong>')
    .replace(/\*(.*?)\*/g, '<em class="text-slate-300 italic">$1</em>')
    .replace(/^\s*-\s+(.*$)/gim, '<li class="ml-4 list-disc text-slate-200 my-0.5">$1</li>')
    .replace(/\n/g, '<br/>')
}

const scrollToBottom = async () => {
  await nextTick()
  if (chatContainer.value) chatContainer.value.scrollTop = chatContainer.value.scrollHeight
  if (fullChatContainer.value) fullChatContainer.value.scrollTop = fullChatContainer.value.scrollHeight
}

// ==========================================
// 📸 1. COPY-PASTE (CTRL+V) & UPLOAD IMAGE
// ==========================================
const handlePaste = (e) => {
  const items = e.clipboardData?.items
  if (!items) return

  for (let i = 0; i < items.length; i++) {
    if (items[i].type.indexOf('image') !== -1) {
      const blob = items[i].getAsFile()
      convertImageToBase64(blob)
      break
    }
  }
}

const handleFileUpload = (e) => {
  const file = e.target.files?.[0]
  if (file) {
    convertImageToBase64(file)
  }
}

const convertImageToBase64 = (file) => {
  const reader = new FileReader()
  reader.onload = (event) => {
    attachedImage.value = event.target.result // Base64 Data URL
  }
  reader.readAsDataURL(file)
}

const removeAttachedImage = () => {
  attachedImage.value = null
  if (fileInputRef.value) fileInputRef.value.value = ''
}

// ==========================================
// 🎙️ 2. VOICE-TO-TEXT INPUT (ចុចនិយាយជំនួសវាយ)
// ==========================================
const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition
let dictationRecognition = null

const toggleVoiceDictation = () => {
  if (!SpeechRecognition) {
    alert('Browser របស់អ្នកមិនទាន់គាំទ្រ Speech Recognition ទេ។ សូមប្រើ Google Chrome!')
    return
  }

  if (isVoiceRecording.value) {
    if (dictationRecognition) dictationRecognition.stop()
    isVoiceRecording.value = false
    return
  }

  dictationRecognition = new SpeechRecognition()
  dictationRecognition.lang = 'km-KH' // ស្តាប់ភាសាខ្មែរ
  dictationRecognition.interimResults = false
  dictationRecognition.continuous = false

  isVoiceRecording.value = true

  dictationRecognition.onresult = (event) => {
    const transcript = event.results[0][0].transcript
    userInput.value = userInput.value ? `${userInput.value} ${transcript}` : transcript
    isVoiceRecording.value = false
  }

  dictationRecognition.onerror = () => {
    isVoiceRecording.value = false
  }

  dictationRecognition.onend = () => {
    isVoiceRecording.value = false
  }

  dictationRecognition.start()
}

// ==========================================
// 💬 3. CONVERSATIONS & CHAT LOGIC
// ==========================================
const fetchConversations = async () => {
  try {
    const res = await apiClient.get('/ai/conversations')
    conversations.value = res.data
    if (conversations.value.length > 0 && !activeConversationId.value) {
      loadConversation(conversations.value[0])
    } else if (conversations.value.length === 0) {
      startNewChat()
    }
  } catch (err) {
    console.error('Error fetching conversations:', err)
  }
}

const loadConversation = async (conv) => {
  try {
    isLoading.value = true
    activeConversationId.value = conv.id
    activeConversationTitle.value = conv.title
    showHistoryDrawer.value = false

    const res = await apiClient.get(`/ai/conversations/${conv.id}`)
    messages.value = res.data.messages
    scrollToBottom()
  } catch (err) {
    console.error('Error loading conversation:', err)
  } finally {
    isLoading.value = false
  }
}

const startNewChat = () => {
  activeConversationId.value = null
  activeConversationTitle.value = 'ការសន្ទនាថ្មី'
  showHistoryDrawer.value = false
  removeAttachedImage()
  messages.value = [
    { role: 'model', text: 'សួស្តី! ខ្ញុំជា **ChillAI Tutor** 🤖✨\nអ្នកអាចវាយអក្សរ, **ចុច Mic និយាយ (🎙️)**, ឬ **Paste រូបភាពលំហាត់ (Ctrl+V)** មកឱ្យខ្ញុំជួយដោះស្រាយបានភ្លាមៗ!' }
  ]
}

const sendMessage = async () => {
  const textToSend = userInput.value.trim()
  const imageToSend = attachedImage.value

  if (!textToSend && !imageToSend) return
  if (isLoading.value) return

  // បន្ថែមសារ User ចូលក្នុង Chatbox
  messages.value.push({
    role: 'user',
    text: textToSend || (imageToSend ? '📷 បានផ្ញើរូបភាពលំហាត់' : ''),
    image: imageToSend, // រក្សាទុករូបភាពដើម្បីបង្ហាញក្នុង Chat Bubble
    created_at: new Date().toISOString()
  })

  userInput.value = ''
  removeAttachedImage()
  isLoading.value = true
  scrollToBottom()

  try {
    const payload = messages.value.map(m => ({ role: m.role, text: m.text }))

    const res = await apiClient.post('/ai/ask', {
      messages: payload,
      image: imageToSend, // ផ្ញើ Base64 រូបភាពទៅ Backend
      conversation_id: activeConversationId.value
    })

    if (res.data.conversation_id && !activeConversationId.value) {
      activeConversationId.value = res.data.conversation_id
      activeConversationTitle.value = res.data.conversation_title
      conversations.value.unshift({
        id: res.data.conversation_id,
        title: res.data.conversation_title,
        created_at: new Date().toISOString(),
        updated_at: new Date().toISOString()
      })
    }

    messages.value.push({
      id: res.data.message_id,
      role: 'model',
      text: res.data.reply,
      created_at: new Date().toISOString()
    })
  } catch (error) {
     console.error('Chat error:', error)

    // បង្ហាញ Error ពិតប្រាកដមកលើ Chatbox តែម្តង
    const realError =
      error.response?.status === 401
        ? 'សម័យចូលគណនីមិនមានសុពលភាពទេ។ សូមចូលគណនីម្ដងទៀត ដើម្បីបន្តប្រើ AI។'
        : error.response?.data?.reply
          || error.response?.data?.message
          || error.message
          || 'Network Error'

    messages.value.push({
      role: 'model',
      text: `⚠️ កំហុស៖ ${realError}`
    })
   } finally {
    isLoading.value = false
    scrollToBottom()
  }
}

const toggleStarMessage = async (msg) => {
  if (!msg.id) return
  try {
    const res = await apiClient.patch(`/ai/messages/${msg.id}/star`)
    msg.is_starred = res.data.is_starred
  } catch (err) {
    console.error('Error toggling star message:', err)
  }
}

const deleteConv = async (conv, e) => {
  e.stopPropagation()
  if (confirm(`តើអ្នកចង់លុបការសន្ទនា «${conv.title}» នេះមែនទេ?`)) {
    try {
      await apiClient.delete(`/ai/conversations/${conv.id}`)
      conversations.value = conversations.value.filter(c => c.id !== conv.id)
      if (activeConversationId.value === conv.id) startNewChat()
    } catch (err) {
      console.error('Error deleting conversation:', err)
    }
  }
}

const handleVoiceMessage = (msg) => {
  messages.value.push({ ...msg, created_at: new Date().toISOString() })
  scrollToBottom()
}

// 📅 Grouped History
const groupedConversations = computed(() => {
  const groups = { today: [], yesterday: [], last7Days: [], older: [] }
  const now = new Date()
  const todayStr = now.toDateString()
  const yest = new Date(now)
  yest.setDate(yest.getDate() - 1)
  const yestStr = yest.toDateString()
  const sevenDaysAgo = new Date(now)
  sevenDaysAgo.setDate(sevenDaysAgo.getDate() - 7)

  conversations.value.forEach(c => {
    const cDate = new Date(c.updated_at || c.created_at)
    const cDateStr = cDate.toDateString()
    if (cDateStr === todayStr) groups.today.push(c)
    else if (cDateStr === yestStr) groups.yesterday.push(c)
    else if (cDate > sevenDaysAgo) groups.last7Days.push(c)
    else groups.older.push(c)
  })
  return groups
})

onMounted(() => {
  fetchConversations()
})
</script>

<template>
  <aside class="w-72 xl:w-80 border-l border-slate-800/80 p-4 xl:p-5 space-y-6 hidden xl:flex flex-col shrink-0 min-h-screen relative">

    <!-- ============================================== -->
    <!-- 🤖 CHILLAI INTERACTIVE MULTIMODAL CHATBOX      -->
    <!-- ============================================== -->
    <div class="bg-slate-900/95 border border-slate-800 rounded-3xl p-4 shadow-xl flex flex-col h-[530px] relative overflow-hidden">

      <!-- Top Header Bar -->
      <div class="flex items-center justify-between pb-3 border-b border-slate-800 shrink-0">
        <div class="flex items-center gap-2 min-w-0 flex-1">
          <div class="w-7 h-7 rounded-xl bg-amber-400/20 text-amber-400 flex items-center justify-center text-xs shrink-0">
            <i class="fa-solid fa-robot"></i>
          </div>
          <div class="min-w-0">
            <h3 class="text-xs font-bold text-white truncate" :title="activeConversationTitle">
              {{ activeConversationTitle || 'ChillAI Tutor' }}
            </h3>
            <p class="text-[9px] text-emerald-400">● Multimodal Vision AI</p>
          </div>
        </div>

        <div class="flex items-center gap-1 shrink-0">
          <button @click="isFullScreen = true" class="text-[10px] text-slate-400 hover:text-amber-400 bg-slate-800/80 p-1.5 rounded-lg" title="ពង្រីកធំ">
            <i class="fa-solid fa-expand text-[10px]"></i>
          </button>

          <button
            @click="showHistoryDrawer = !showHistoryDrawer"
            :class="showHistoryDrawer ? 'bg-amber-400 text-slate-950 font-bold' : 'bg-slate-800 text-slate-300 hover:text-white'"
            class="px-2 py-1 rounded-lg text-[10px] transition flex items-center gap-1"
            title="មើលប្រធានបទទាំងអស់"
          >
            <i class="fa-solid fa-clock-rotate-left text-[9px]"></i>
            <span>{{ conversations.length }}</span>
          </button>

          <button
            @click="startNewChat"
            class="px-2 py-1 rounded-lg bg-amber-400/10 text-amber-400 hover:bg-amber-400 hover:text-slate-950 border border-amber-400/20 text-[10px] font-bold transition flex items-center gap-1"
            title="ចាប់ផ្តើមជជែកថ្មី (+ New Chat)"
          >
            <i class="fa-solid fa-plus text-[9px]"></i>
            <span>ថ្មី</span>
          </button>

          <button @click="showVoiceCallModal = true" class="px-2 py-1 rounded-lg bg-emerald-500/20 text-emerald-400 hover:bg-emerald-400 hover:text-slate-950 border border-emerald-500/30 text-[10px] font-bold transition flex items-center gap-1">
            <i class="fa-solid fa-phone text-[9px] animate-pulse"></i>
          </button>
        </div>
      </div>

      <!-- History Drawer -->
      <div v-if="showHistoryDrawer" class="absolute inset-x-0 bottom-0 top-[52px] bg-slate-950/98 backdrop-blur-xl z-20 p-3.5 flex flex-col space-y-3 animate-fade-in">
        <div class="flex items-center justify-between">
          <span class="text-xs font-bold text-white flex items-center gap-1.5">
            <i class="fa-solid fa-comments text-amber-400"></i> ប្រវត្តិការសន្ទនា (Threads)
          </span>
          <button @click="showHistoryDrawer = false" class="text-slate-400 hover:text-white text-xs">✕</button>
        </div>

        <button @click="startNewChat" class="w-full py-2 rounded-xl bg-amber-400 text-slate-950 font-bold text-xs flex items-center justify-center gap-2 shadow">
          <i class="fa-solid fa-plus"></i> ចាប់ផ្តើមជជែកថ្មី (+ New Chat)
        </button>

        <div class="flex-1 overflow-y-auto space-y-3 pr-1 text-xs scrollbar-thin">
          <div v-if="conversations.length === 0" class="text-center py-10 text-slate-500 text-[11px] font-khmer">
            មិនទាន់មានប្រវត្តិសន្ទនានៅឡើយទេ។
          </div>

          <div v-if="groupedConversations.today.length">
            <span class="text-[10px] font-bold text-amber-400 uppercase tracking-wider block mb-1.5">📅 ថ្ងៃនេះ (Today)</span>
            <div class="space-y-1">
              <div v-for="c in groupedConversations.today" :key="c.id" @click="loadConversation(c)" :class="activeConversationId === c.id ? 'bg-amber-400/15 border-amber-400/40 text-amber-300' : 'bg-slate-900/80 border-slate-800 text-slate-300 hover:bg-slate-850'" class="p-2 rounded-xl border flex items-center justify-between cursor-pointer transition group">
                <span class="truncate text-[11px] font-khmer flex-1">{{ c.title }}</span>
                <button @click="deleteConv(c, $event)" class="text-slate-500 hover:text-rose-400 text-[10px] p-1 ml-2 opacity-0 group-hover:opacity-100 transition"><i class="fa-solid fa-trash-can"></i></button>
              </div>
            </div>
          </div>

          <div v-if="groupedConversations.yesterday.length">
            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block mb-1.5">📅 ម្សិលមិញ (Yesterday)</span>
            <div class="space-y-1">
              <div v-for="c in groupedConversations.yesterday" :key="c.id" @click="loadConversation(c)" :class="activeConversationId === c.id ? 'bg-amber-400/15 border-amber-400/40 text-amber-300' : 'bg-slate-900/80 border-slate-800 text-slate-300 hover:bg-slate-850'" class="p-2 rounded-xl border flex items-center justify-between cursor-pointer transition group">
                <span class="truncate text-[11px] font-khmer flex-1">{{ c.title }}</span>
                <button @click="deleteConv(c, $event)" class="text-slate-500 hover:text-rose-400 text-[10px] p-1 ml-2 opacity-0 group-hover:opacity-100 transition"><i class="fa-solid fa-trash-can"></i></button>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Messages Scroll Area -->
      <div ref="chatContainer" class="flex-1 overflow-y-auto space-y-3 py-3 pr-1 text-xs scrollbar-thin">
        <div
          v-for="(msg, idx) in messages"
          :key="msg.id || idx"
          :class="msg.role === 'user' ? 'justify-end' : 'justify-start'"
          class="flex gap-2"
        >
          <div v-if="msg.role === 'model'" class="w-6 h-6 rounded-lg bg-amber-400/10 text-amber-400 flex items-center justify-center shrink-0 mt-0.5 text-[10px]">
            <i class="fa-solid fa-robot"></i>
          </div>

          <div
            :class="msg.role === 'user'
              ? 'bg-amber-400 text-slate-950 rounded-2xl rounded-tr-sm font-medium shadow'
              : 'bg-slate-800/80 border border-slate-700/60 text-slate-200 rounded-2xl rounded-tl-sm relative'"
            class="p-2.5 px-3 max-w-[85%] leading-relaxed font-khmer text-[11px]"
          >
            <!-- 📸 បង្ហាញរូបភាពដែលបានផ្ញើក្នុង Chat Bubble -->
            <div v-if="msg.image" class="mb-2 rounded-xl overflow-hidden border border-black/20 max-w-[180px] shadow">
              <img :src="msg.image" alt="Uploaded problem" class="w-full h-auto object-cover" />
            </div>

            <span v-if="msg.is_voice" class="text-[9px] opacity-75 block mb-0.5 font-mono">
              <i class="fa-solid fa-microphone text-[8px]"></i> Voice
            </span>

            <div v-html="formatMarkdown(msg.text)"></div>

            <button
              v-if="msg.role === 'model' && msg.id"
              @click="toggleStarMessage(msg)"
              class="absolute -top-2 -right-2 w-5 h-5 rounded-full bg-slate-900 border border-slate-700 flex items-center justify-center text-[9px] hover:scale-110 shadow"
            >
              <i :class="msg.is_starred ? 'fa-solid fa-star text-amber-400' : 'fa-regular fa-star text-slate-500'"></i>
            </button>
          </div>
        </div>

        <div v-if="isLoading" class="flex items-center gap-2">
          <div class="w-6 h-6 rounded-lg bg-amber-400/10 text-amber-400 flex items-center justify-center text-[10px]">
            <i class="fa-solid fa-robot"></i>
          </div>
          <div class="bg-slate-800/80 border border-slate-700/60 rounded-2xl p-2.5 px-3 flex items-center gap-1">
            <span class="w-1.5 h-1.5 rounded-full bg-amber-400 animate-bounce"></span>
            <span class="w-1.5 h-1.5 rounded-full bg-amber-400 animate-bounce [animation-delay:0.2s]"></span>
            <span class="w-1.5 h-1.5 rounded-full bg-amber-400 animate-bounce [animation-delay:0.4s]"></span>
          </div>
        </div>
      </div>

      <!-- ============================================== -->
      <!-- 🖼️ PREVIEW THUMBNAIL (ពេល PASTE ឬ UPLOAD រូប)  -->
      <!-- ============================================== -->
      <div v-if="attachedImage" class="relative mb-2 p-1.5 bg-slate-950 border border-amber-400/40 rounded-2xl inline-flex items-center gap-2 self-start animate-scale-up">
        <div class="w-12 h-12 rounded-xl overflow-hidden bg-black shrink-0 border border-white/10">
          <img :src="attachedImage" alt="Preview" class="w-full h-full object-cover" />
        </div>
        <div class="pr-2">
          <span class="text-[10px] text-amber-400 font-bold block">📷 រូបថតលំហាត់</span>
          <span class="text-[9px] text-slate-400">ត្រៀមផ្ញើទៅកាន់ AI</span>
        </div>
        <button @click="removeAttachedImage" class="w-5 h-5 rounded-full bg-slate-800 hover:bg-rose-500 text-slate-400 hover:text-white flex items-center justify-center text-[10px] transition ml-1">
          ✕
        </button>
      </div>

      <!-- ============================================== -->
      <!-- INPUT BAR WITH ATTACHMENT & VOICE DICTATION    -->
      <!-- ============================================== -->
      <form @submit.prevent="sendMessage()" class="relative shrink-0 pt-2 border-t border-slate-800 flex items-center gap-1.5">

        <!-- Hidden File Input -->
        <input
          type="file"
          ref="fileInputRef"
          accept="image/*"
          class="hidden"
          @change="handleFileUpload"
        />

        <!-- 📎 Upload / Attach Image Button -->
        <button
          type="button"
          @click="fileInputRef?.click()"
          class="w-8 h-8 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-400 hover:text-amber-400 flex items-center justify-center text-xs transition shrink-0"
          title="ដាក់រូបភាពលំហាត់ (ឬចុច Ctrl+V)"
        >
          <i class="fa-solid fa-paperclip"></i>
        </button>

        <!-- 🎙️ Voice Dictation Button (ចុចនិយាយជំនួសវាយ) -->
        <button
          type="button"
          @click="toggleVoiceDictation"
          :class="isVoiceRecording ? 'bg-rose-500 text-white animate-pulse' : 'bg-slate-800 text-slate-400 hover:text-emerald-400'"
          class="w-8 h-8 rounded-xl flex items-center justify-center text-xs transition shrink-0"
          :title="isVoiceRecording ? 'កំពុងស្តាប់... ចុចម្តងទៀតដើម្បីឈប់' : 'ចុចនិយាយជាភាសាខ្មែរ'"
        >
          <i class="fa-solid fa-microphone"></i>
        </button>

        <!-- Text Input with Ctrl+V Paste Detection -->
        <input
          v-model="userInput"
          @paste="handlePaste"
          :disabled="isLoading"
          type="text"
          :placeholder="isVoiceRecording ? 'កំពុងស្តាប់អ្នកនិយាយ...' : 'សួរ AI ឬចុច Ctrl+V ដាក់រូប...'"
          class="flex-1 bg-slate-950 border border-slate-700 rounded-xl px-3 py-2 text-xs text-white placeholder-slate-500 focus:outline-none focus:border-amber-400 transition disabled:opacity-50"
        />

        <!-- Send Button -->
        <button
          type="submit"
          :disabled="isLoading || (!userInput.trim() && !attachedImage)"
          class="w-8 h-8 rounded-xl bg-amber-400 hover:bg-amber-300 text-slate-950 flex items-center justify-center text-xs transition disabled:opacity-30 shrink-0 font-bold"
        >
          <i class="fa-solid fa-paper-plane"></i>
        </button>
      </form>
    </div>

    <!-- ============================================== -->
    <!-- ⛶ FULLSCREEN STUDIO WORKSPACE                  -->
    <!-- ============================================== -->
    <Teleport to="body">
      <div v-if="isFullScreen" class="fixed inset-0 z-[90] bg-[#070b14] text-slate-100 flex flex-col animate-scale-up">
        <header class="px-6 py-3 border-b border-slate-800 bg-slate-950/80 backdrop-blur-md flex items-center justify-between">
          <div class="flex items-center gap-3">
            <div class="w-8 h-8 rounded-xl bg-amber-400/20 text-amber-400 flex items-center justify-center text-sm font-bold">
              <i class="fa-solid fa-robot"></i>
            </div>
            <div>
              <h2 class="text-sm font-bold text-white">{{ activeConversationTitle || 'ChillAI Workspace' }}</h2>
              <p class="text-[10px] text-slate-400">របៀបសិក្សាធំទូលាយដូច ChatGPT (គាំទ្ររូបភាព & សំឡេង)</p>
            </div>
          </div>
          <div class="flex items-center gap-2">
            <button @click="showVoiceCallModal = true" class="px-3 py-1.5 rounded-xl bg-emerald-500/20 text-emerald-400 border border-emerald-500/30 text-xs font-bold flex items-center gap-1.5">
              <i class="fa-solid fa-phone"></i> Call AI
            </button>
            <button @click="isFullScreen = false" class="px-3 py-1.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 text-xs font-semibold">
              ✕ បង្រួមតូចវិញ
            </button>
          </div>
        </header>

        <div class="flex-1 flex overflow-hidden">
          <aside class="w-72 border-r border-slate-800 bg-slate-950/60 p-4 overflow-y-auto hidden md:flex flex-col space-y-4">
            <button @click="startNewChat" class="w-full py-2.5 rounded-2xl bg-amber-400 text-slate-950 font-bold text-xs flex items-center justify-center gap-2 shadow">
              <i class="fa-solid fa-plus"></i> ចាប់ផ្តើមជជែកថ្មី
            </button>
            <div class="space-y-3 text-xs">
              <div v-for="c in conversations" :key="c.id" @click="loadConversation(c)" :class="activeConversationId === c.id ? 'bg-amber-400/15 border-amber-400 text-amber-300' : 'bg-slate-900 border-slate-800 text-slate-300'" class="p-3 rounded-xl border cursor-pointer transition flex items-center justify-between group">
                <span class="truncate text-[11px] font-khmer flex-1">{{ c.title }}</span>
                <button @click="deleteConv(c, $event)" class="text-slate-500 hover:text-rose-400 text-[10px] p-1 ml-2 opacity-0 group-hover:opacity-100"><i class="fa-solid fa-trash-can"></i></button>
              </div>
            </div>
          </aside>

          <main class="flex-1 flex flex-col justify-between max-w-4xl mx-auto w-full p-4 sm:p-6 overflow-hidden">
            <div ref="fullChatContainer" class="flex-1 overflow-y-auto space-y-4 pr-2 text-sm scrollbar-thin">
              <div v-for="(msg, idx) in messages" :key="msg.id || idx" :class="msg.role === 'user' ? 'justify-end' : 'justify-start'" class="flex gap-3">
                <div v-if="msg.role === 'model'" class="w-8 h-8 rounded-xl bg-amber-400/20 text-amber-400 flex items-center justify-center shrink-0 text-sm">
                  <i class="fa-solid fa-robot"></i>
                </div>
                <div :class="msg.role === 'user' ? 'bg-amber-400 text-slate-950 rounded-3xl rounded-tr-sm font-medium shadow-lg' : 'bg-slate-900 border border-slate-800 text-slate-100 rounded-3xl rounded-tl-sm relative'" class="p-4 px-5 max-w-[80%] leading-relaxed font-khmer text-xs sm:text-sm">
                  <div v-if="msg.image" class="mb-2 rounded-2xl overflow-hidden border border-black/20 max-w-xs shadow">
                    <img :src="msg.image" alt="Homework" class="w-full h-auto object-cover" />
                  </div>
                  <div v-html="formatMarkdown(msg.text)"></div>
                </div>
              </div>
            </div>

            <!-- Fullscreen Form -->
            <form @submit.prevent="sendMessage()" class="relative pt-4 max-w-3xl mx-auto w-full">
              <!-- Attached Image Preview in Fullscreen -->
              <div v-if="attachedImage" class="relative mb-2 p-1.5 bg-slate-950 border border-amber-400/40 rounded-2xl inline-flex items-center gap-2 self-start">
                <img :src="attachedImage" alt="Preview" class="w-12 h-12 rounded-xl object-cover" />
                <span class="text-xs text-amber-400 font-bold">📷 រូបថតលំហាត់</span>
                <button @click="removeAttachedImage" class="w-5 h-5 rounded-full bg-slate-800 text-slate-400 hover:text-white flex items-center justify-center text-xs ml-2">✕</button>
              </div>

              <div class="relative flex items-center gap-2 bg-slate-900 border border-slate-700 rounded-2xl p-2 px-3 shadow-2xl">
                <button type="button" @click="fileInputRef?.click()" class="text-slate-400 hover:text-amber-400 text-base p-2"><i class="fa-solid fa-paperclip"></i></button>
                <button type="button" @click="toggleVoiceDictation" :class="isVoiceRecording ? 'text-rose-400 animate-pulse' : 'text-slate-400 hover:text-emerald-400'" class="text-base p-2"><i class="fa-solid fa-microphone"></i></button>
                <input v-model="userInput" @paste="handlePaste" :disabled="isLoading" type="text" placeholder="សួរ AI, ចុច Mic និយាយ, ឬចុច Ctrl+V ដាក់រូប..." class="flex-1 bg-transparent border-none text-white text-xs sm:text-sm focus:outline-none" />
                <button type="submit" :disabled="isLoading || (!userInput.trim() && !attachedImage)" class="text-amber-400 hover:scale-110 p-2 text-base transition disabled:opacity-30"><i class="fa-solid fa-paper-plane"></i></button>
              </div>
            </form>
          </main>
        </div>
      </div>
    </Teleport>

    <!-- Voice Call Modal -->
    <AiVoiceCallModal :show="showVoiceCallModal" :messages="messages" @new-message="handleVoiceMessage" @close="showVoiceCallModal = false" />

  </aside>
</template>
