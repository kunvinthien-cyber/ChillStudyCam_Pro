<script setup>
import { ref, watch, onUnmounted } from 'vue'
import apiClient from '../services/api'

const props = defineProps({
  show: {
    type: Boolean,
    default: false
  },
  messages: {
    type: Array,
    default: () => []
  },
  conversationId: {
    type: [Number, String],
    default: null
  }
})

const emit = defineEmits(['close', 'new-message', 'conversation-updated'])

const callStatus = ref('connected')
const userSpeech = ref('')
const aiReplyText = ref('')
const callError = ref('')
const voiceEngine = ref('google_khmer')
const voiceSpeed = ref(1.0)
const showSettings = ref(false)

const SpeechRecognition =
  typeof window !== 'undefined'
    ? window.SpeechRecognition || window.webkitSpeechRecognition || null
    : null

let recognition = null
let audioPlayer = typeof window !== 'undefined' ? new Audio() : null
let currentAudioPlayer = audioPlayer
let currentAudioUrl = null
let callEnabled = false
let restartTimer = null

const synth = typeof window !== 'undefined' ? window.speechSynthesis : null

const getVoiceErrorMessage = (error) => {
  const status = error?.response?.status
  const responseBody = error?.response?.data
  const serverMessage =
    typeof responseBody === 'string'
      ? responseBody
      : responseBody?.reply || responseBody?.message || ''
  const message = typeof serverMessage === 'string' ? serverMessage : error?.message || ''

  if (status === 429 || message.includes('(429)') || message.includes('RESOURCE_EXHAUSTED')) {
    return 'AI quota is currently exceeded. Please check the Gemini API quota and try again later.'
  }
  if (
    status === 401 ||
    status === 403 ||
    message.includes('(401)') ||
    message.includes('(403)') ||
    message.includes('API_KEY_INVALID') ||
    message.includes('PERMISSION_DENIED')
  ) {
    return 'Gemini rejected the API key or this key does not have permission to use the API. Check the backend GEMINI_API_KEY and API access.'
  }
  if (!error?.response && !error?.message) {
    return 'Cannot reach the AI server. Check that the backend is running and try again.'
  }
  if (message) return message
  return `AI request failed (HTTP ${status}). Please check the backend logs and try again.`
}

const scheduleListening = (delay = 500) => {
  clearTimeout(restartTimer)
  if (!callEnabled || !props.show) return

  restartTimer = setTimeout(() => {
    if (callEnabled && props.show && callStatus.value === 'connected') {
      startListening()
    }
  }, delay)
}

// ======================================================
// START LISTENING
// ======================================================
const startListening = () => {
  if (!callEnabled || !props.show || callStatus.value !== 'connected') return

  if (!SpeechRecognition) {
    callError.value = 'Voice input is not supported in this browser. Please use Chrome or Edge and allow microphone access.'
    callEnabled = false
    return
  }

  if (synth) synth.cancel()

  const activeRecognition = new SpeechRecognition()
  recognition = activeRecognition
  activeRecognition.lang = 'km-KH'
  activeRecognition.interimResults = false
  activeRecognition.continuous = false
  callStatus.value = 'listening'
  callError.value = ''

  if (audioPlayer) {
    audioPlayer.onended = null
    audioPlayer.onerror = null
    audioPlayer.pause()
    audioPlayer.src =
      'data:audio/wav;base64,UklGRigAAABXQVZFZm10IBIAAAABAAEARKwAAIhYAQACABAAAABkYXRhAgAAAAEA'
    audioPlayer.play().catch(() => {})
  }

  activeRecognition.onresult = async (event) => {
    const transcript = event.results?.[0]?.[0]?.transcript?.trim()
    if (!transcript) {
      callStatus.value = 'connected'
      return
    }

    userSpeech.value = transcript
    callStatus.value = 'thinking'
    emit('new-message', { role: 'user', text: transcript, is_voice: true })
    await getAiVoiceReply(transcript)
  }

  activeRecognition.onerror = (event) => {
    console.error('Speech error:', event.error)
    const guidance = {
      'audio-capture': 'No microphone was found. Connect a microphone and reopen the call.',
      'not-allowed': 'Microphone access is blocked. Allow microphone access in your browser, then reopen the call.',
      'service-not-allowed': 'Microphone access is blocked. Allow microphone access in your browser, then reopen the call.',
      'language-not-supported': 'Khmer speech recognition is not available in this browser.',
      network: 'Speech recognition needs an internet connection. Reconnecting...',
      aborted: ''
    }
    callError.value = guidance[event.error] ?? 'Voice input stopped unexpectedly. Reconnecting...'
    callStatus.value = 'connected'
    if (['audio-capture', 'not-allowed', 'service-not-allowed', 'language-not-supported'].includes(event.error)) {
      callEnabled = false
    }
  }

  activeRecognition.onend = () => {
    if (recognition === activeRecognition) {
      recognition = null
    }
    if (callStatus.value === 'listening') {
      callStatus.value = 'connected'
    }
    scheduleListening(800)
  }

  try {
    activeRecognition.start()
  } catch (error) {
    console.error('Could not start speech recognition:', error)
    callError.value = 'Could not start microphone. Allow microphone access in your browser, then reopen the call.'
    callStatus.value = 'connected'
    recognition = null
    callEnabled = false
  }
}

// ======================================================
// GET AI VOICE REPLY
// ======================================================
const getAiVoiceReply = async (question) => {
  try {
    const payload = (props.messages || []).map(({ role, text }) => ({ role, text }))
    const lastMessage = payload[payload.length - 1]
    if (lastMessage?.role !== 'user' || lastMessage.text !== question) {
      payload.push({ role: 'user', text: question })
    }

    const response = await apiClient.post('/ai/ask', {
      messages: payload,
      conversation_id: props.conversationId,
      voice_mode: true
    })

    const reply = response?.data?.reply

    if (!reply || reply.startsWith('⚠️')) {
      throw new Error(reply || 'AI returned an empty response')
    }

    if (response.data.conversation_id) {
      emit('conversation-updated', {
        id: response.data.conversation_id,
        title: response.data.conversation_title,
        messageId: response.data.message_id
      })
    }

    aiReplyText.value = reply

    emit('new-message', {
      role: 'model',
      text: reply,
      is_voice: true
    })

    if (voiceEngine.value === 'browser') {
      fallbackBrowserSpeech(reply)
    } else {
      playVoiceOutput(reply)
    }
  } catch (error) {
    console.error('AI Voice Error:', error)
    aiReplyText.value = getVoiceErrorMessage(error)
    callError.value = aiReplyText.value
    callStatus.value = 'connected'
    scheduleListening()
  }
}

// ======================================================
// CLEAN TEXT FOR TTS
// ======================================================
const cleanTextForVoice = (text) => {
  if (!text) return ''

  return text
    .replace(/[\u{1F300}-\u{1FAFF}\u{2600}-\u{27BF}]/gu, '')
    .replace(/\*\*(.*?)\*\*/g, '$1')
    .replace(/__(.*?)__/g, '$1')
    .replace(/[*_#`~]/g, '')
    .replace(/^[\s]*[-*]\s*/gm, '')
    .replace(/\s+/g, ' ')
    .trim()
}

// ======================================================
// PLAY BACKEND TTS
// ======================================================
const playVoiceOutput = async (text) => {
  const cleanText = String(text ?? '')
    .replace(/[\u{1F300}-\u{1FAFF}\u{2600}-\u{27BF}]/gu, '')
    .replace(/[*_#-]/g, '')
    .trim()

  if (!cleanText) {
    callStatus.value = 'connected'
    scheduleListening()
    return
  }

  callStatus.value = 'speaking'

  if (!audioPlayer) {
    fallbackBrowserSpeech(cleanText)
    return
  }

  callStatus.value = 'speaking'

  try {
    const response = await apiClient.get('/ai/tts', {
      params: { text: cleanText.slice(0, 100), t: Date.now() },
      responseType: 'blob'
    })
    if (!callEnabled || !props.show) return

    const audioUrl = URL.createObjectURL(response.data)
    currentAudioUrl = audioUrl
    currentAudioPlayer = audioPlayer
    audioPlayer.pause()
    audioPlayer.src = audioUrl

    let fallbackStarted = false
    const fallback = (error) => {
      if (fallbackStarted) return
      fallbackStarted = true
      if (currentAudioUrl === audioUrl) {
        URL.revokeObjectURL(audioUrl)
        currentAudioUrl = null
      }
      currentAudioPlayer = null
      console.error('Khmer voice playback failed:', error)
      fallbackBrowserSpeech(cleanText)
    }

    audioPlayer.onended = () => {
      if (currentAudioUrl === audioUrl) {
        URL.revokeObjectURL(audioUrl)
        currentAudioUrl = null
      }
      callStatus.value = 'connected'
      scheduleListening()
    }
    audioPlayer.onerror = fallback

    try {
      await audioPlayer.play()
    } catch (error) {
      fallback(error)
    }
  } catch (error) {
    console.error('Khmer voice request failed:', error)
    if (callEnabled && props.show) fallbackBrowserSpeech(cleanText)
  }
}

// ======================================================
// BROWSER SPEECH FALLBACK
// ======================================================
const fallbackBrowserSpeech = (text) => {
  if (!synth) {
    callError.value = 'AI replied, but browser voice output is unavailable. Check audio support in this browser.'
    callStatus.value = 'connected'
    scheduleListening()
    return
  }

  synth.cancel()

  const cleanText = cleanTextForVoice(text)

  if (!cleanText) {
    callStatus.value = 'connected'
    scheduleListening()
    return
  }

  const utterance = new SpeechSynthesisUtterance(cleanText)

  utterance.lang = 'km-KH'
  utterance.rate = voiceSpeed.value
  utterance.pitch = 1
  utterance.volume = 1

  utterance.onstart = () => {
    callError.value = ''
    callStatus.value = 'speaking'
  }

  utterance.onend = () => {
    callStatus.value = 'connected'
    scheduleListening()
  }

  utterance.onerror = (error) => {
    console.error('Browser Speech Error:', error)
    callError.value = 'AI replied, but this browser could not play the voice. Check your audio output or choose another voice in Settings.'
    callStatus.value = 'connected'
    scheduleListening()
  }

  synth.speak(utterance)
}

// ======================================================
// STOP ALL SPEECH
// ======================================================
const stopAllSpeech = () => {
  const activePlayer = currentAudioPlayer ?? audioPlayer

  if (activePlayer) {
    activePlayer.onended = null
    activePlayer.onerror = null
    activePlayer.pause()
    activePlayer.currentTime = 0
    activePlayer.src = ''
  }

  if (currentAudioUrl) {
    URL.revokeObjectURL(currentAudioUrl)
    currentAudioUrl = null
  }

  if (synth) {
    synth.cancel()
  }

  currentAudioPlayer = null
}

// ======================================================
// END CALL
// ======================================================
const endCall = () => {
  callEnabled = false
  clearTimeout(restartTimer)

  if (recognition) {
    const activeRecognition = recognition
    recognition = null
    try {
      activeRecognition.stop()
    } catch {
      console.error('Could not stop speech recognition')
    }
  }

  stopAllSpeech()

  callStatus.value = 'connected'
  userSpeech.value = ''
  aiReplyText.value = ''
  callError.value = ''

  emit('close')
}

watch(() => props.show, (isVisible) => {
  if (isVisible) {
    callEnabled = true
    callError.value = ''
    callStatus.value = 'connected'
    startListening()
    return
  }

  callEnabled = false
  clearTimeout(restartTimer)
  if (recognition) {
    const activeRecognition = recognition
    recognition = null
    try {
      activeRecognition.stop()
    } catch (error) {
      console.error('Could not stop speech recognition:', error)
    }
  }
  stopAllSpeech()
  callStatus.value = 'connected'
})

// ======================================================
// CLEANUP
// ======================================================
onUnmounted(() => {
  callEnabled = false
  clearTimeout(restartTimer)
  if (recognition) {
    const activeRecognition = recognition
    recognition = null
    try {
      activeRecognition.stop()
    } catch (error) {
      console.error('Speech recognition cleanup failed:', error)
    }
  }

  stopAllSpeech()
})
</script>

<template>
  <Teleport to="body">
    <div
      v-if="show"
      class="fixed inset-0 z-[100] bg-black/90 backdrop-blur-xl flex items-center justify-center overflow-y-auto p-4"
    >
      <div
        class="bg-gradient-to-b from-slate-900 via-slate-950 to-[#070b14]
        border border-slate-800 rounded-3xl p-6 sm:p-8
        max-w-md w-full text-center shadow-2xl relative
        flex flex-col items-center justify-between
        min-h-[min(530px,calc(100dvh-2rem))] max-h-[calc(100dvh-2rem)] overflow-y-auto animate-scale-up"
      >
        <div class="w-full flex items-center justify-between border-b border-slate-800/80 pb-3">
          <div class="flex items-center gap-2">
            <span class="w-2.5 h-2.5 rounded-full bg-emerald-400 animate-ping"></span>
            <span class="text-xs font-bold text-white tracking-wide">ChillAI Voice Call</span>
          </div>

          <div class="flex items-center gap-2">
            <button
              @click="showSettings = !showSettings"
              class="px-2 py-1 rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-300 text-[11px] flex items-center gap-1.5 transition"
            >
              <i class="fa-solid fa-gear text-amber-400 text-[10px]"></i>
              <span>Settings</span>
            </button>

            <button
              @click="endCall"
              class="text-slate-400 hover:text-white text-xs ml-1"
              title="Close call"
            >
              X
            </button>
          </div>
        </div>

        <div
          v-if="showSettings"
          class="w-full bg-slate-950/95 border border-slate-800 p-3.5 rounded-2xl my-2 text-left text-xs space-y-3 shadow-xl"
        >
          <div>
            <label class="text-[10px] text-slate-400 font-khmer block mb-1.5 font-bold">
              Voice output
            </label>

            <div class="grid grid-cols-2 gap-2">
              <button
                @click="voiceEngine = 'google_khmer'"
                :class="
                  voiceEngine === 'google_khmer'
                    ? 'bg-amber-400 text-slate-950 font-bold'
                    : 'bg-slate-900 text-slate-300 border border-slate-800'
                "
                class="py-2 rounded-xl text-[11px] transition"
              >
                Khmer voice
              </button>

              <button
                @click="voiceEngine = 'browser'"
                :class="
                  voiceEngine === 'browser'
                    ? 'bg-amber-400 text-slate-950 font-bold'
                    : 'bg-slate-900 text-slate-300 border border-slate-800'
                "
                class="py-2 rounded-xl text-[11px] transition"
              >
                Browser voice
              </button>
            </div>
          </div>

          <div>
            <label class="text-[10px] text-slate-400 font-khmer block mb-1.5 font-bold">
              Voice speed
              <span class="text-amber-400">{{ voiceSpeed }}x</span>
            </label>

            <input
              v-model.number="voiceSpeed"
              type="range"
              min="0.6"
              max="1.4"
              step="0.1"
              class="w-full accent-amber-400"
            />
          </div>
        </div>

        <div class="my-auto flex flex-col items-center justify-center">
          <div class="relative flex items-center justify-center mb-5">
            <div
              v-if="callStatus === 'speaking' || callStatus === 'listening'"
              class="absolute w-36 h-36 rounded-full bg-amber-400/20 animate-ping"
            ></div>

            <div class="w-28 h-28 rounded-full bg-gradient-to-tr from-amber-500 to-emerald-400 p-1 shadow-2xl relative z-10 flex items-center justify-center">
              <div class="w-full h-full rounded-full bg-slate-950 flex items-center justify-center text-4xl text-amber-400">
                <i v-if="callStatus === 'thinking'" class="fa-solid fa-spinner fa-spin text-3xl"></i>
                <i v-else-if="callStatus === 'speaking'" class="fa-solid fa-volume-high text-3xl animate-bounce text-emerald-400"></i>
                <i v-else-if="callStatus === 'listening'" class="fa-solid fa-microphone text-3xl animate-pulse text-emerald-400"></i>
                <i v-else class="fa-solid fa-robot"></i>
              </div>
            </div>
          </div>

          <h3 class="text-base font-bold text-white mb-1">ChillAI Study Mentor</h3>

          <p class="text-xs font-khmer min-h-[20px]" :class="{
            'text-emerald-400 font-bold animate-pulse': callStatus === 'listening',
            'text-amber-400': callStatus === 'thinking',
            'text-emerald-400 font-bold': callStatus === 'speaking',
            'text-slate-400': callStatus === 'connected'
          }">
            <span v-if="callStatus === 'listening'">Listening... Speak now</span>
            <span v-else-if="callStatus === 'thinking'">Thinking...</span>
            <span v-else-if="callStatus === 'speaking'">Speaking...</span>
            <span v-else>{{ callError || 'Speak naturally. ChillAI will listen automatically.' }}</span>
          </p>

          <div class="mt-4 p-3 rounded-2xl bg-slate-950/70 border border-slate-800/80 max-w-xs w-full text-center min-h-[50px] flex items-center justify-center shadow-inner">
            <p class="text-[11px] text-slate-300 font-khmer line-clamp-2 leading-relaxed">
              {{ userSpeech || 'Your microphone stays on during the call. Click End Call when you are done.' }}
            </p>
          </div>

          <div v-if="aiReplyText" class="mt-2 max-w-xs w-full">
            <p class="text-[10px] text-slate-500 line-clamp-2 font-khmer">
              {{ aiReplyText }}
            </p>
          </div>
        </div>

        <div class="w-full pt-4 border-t border-slate-800/80 flex items-center justify-center">
          <button
            @click="endCall"
            class="w-14 h-14 rounded-full bg-rose-500 hover:bg-rose-600 text-white flex items-center justify-center text-xl transition-all shadow-lg shadow-rose-500/20 active:scale-95"
            title="End call"
          >
            <i class="fa-solid fa-phone-slash"></i>
          </button>
        </div>
      </div>
    </div>
  </Teleport>
</template>
