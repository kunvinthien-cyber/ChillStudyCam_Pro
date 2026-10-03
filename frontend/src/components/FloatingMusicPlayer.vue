<script setup>
import { ref, onMounted } from 'vue'

const isExpanded = ref(false)
const showInputModal = ref(false)
const inputUrl = ref('')
const currentEmbedUrl = ref('')
const playerType = ref('')
const inputError = ref('')

// Controller States
const ytIframeRef = ref(null)
const volume = ref(70)
const isPlaying = ref(true)

// Function ផ្ញើបញ្ជាទៅកាន់ YouTube Iframe
const sendYtCommand = (command, args = []) => {
  if (ytIframeRef.value && ytIframeRef.value.contentWindow) {
    ytIframeRef.value.contentWindow.postMessage(
      JSON.stringify({
        event: 'command',
        func: command,
        args: args
      }),
      '*'
    )
  }
}

// ១. បន្ថយ / តម្លើងសំឡេង
const changeVolume = (e) => {
  volume.value = parseInt(e.target.value)
  sendYtCommand('setVolume', [volume.value])
}

// ២. ប្តូរបទបន្ទាប់ (Next Track)
const nextTrack = () => {
  sendYtCommand('nextVideo')
}

// ៣. ប្តូរបទមុន (Prev Track)
const prevTrack = () => {
  sendYtCommand('previousVideo')
}

// ៤. ចុច Play / Pause
const togglePlay = () => {
  if (isPlaying.value) {
    sendYtCommand('pauseVideo')
    isPlaying.value = false
  } else {
    sendYtCommand('playVideo')
    isPlaying.value = true
  }
}

// Function បម្លែង Link (បន្ថែម enablejsapi=1 ដើម្បីឱ្យបញ្ជាពីក្រៅបាន)
const parseMusicUrl = (url) => {
  if (!url) return null

  // Spotify
  const spotifyMatch = url.match(/open\.spotify\.com\/(track|playlist|album)\/([a-zA-Z0-9]+)/i)
  if (spotifyMatch) {
    playerType.value = 'spotify'
    return `https://open.spotify.com/embed/${spotifyMatch[1]}/${spotifyMatch[2]}?utm_source=generator&theme=0`
  }

  // YouTube Playlist
  const ytPlaylistMatch = url.match(/[?&]list=([^#&?]+)/i)
  if (ytPlaylistMatch) {
    playerType.value = 'youtube'
    return `https://www.youtube.com/embed/videoseries?list=${ytPlaylistMatch[1]}&autoplay=1&enablejsapi=1`
  }

  // YouTube Video
  const ytVideoMatch = url.match(/(?:youtube\.com\/(?:[^/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/)([^"&?/\s]{11})/i)
  if (ytVideoMatch) {
    playerType.value = 'youtube'
    return `https://www.youtube.com/embed/${ytVideoMatch[1]}?autoplay=1&enablejsapi=1`
  }

  return null
}

const saveCustomMusic = () => {
  inputError.value = ''
  const embedUrl = parseMusicUrl(inputUrl.value.trim())

  if (!embedUrl) {
    inputError.value = 'Link មិនត្រឹមត្រូវទេ! សូមដាក់ Link YouTube ឬ Spotify ឱ្យបានត្រឹមត្រូវ។'
    return
  }

  currentEmbedUrl.value = embedUrl
  localStorage.setItem('chill_custom_embed', embedUrl)
  localStorage.setItem('chill_custom_type', playerType.value)
  showInputModal.value = false
  isExpanded.value = true
  isPlaying.value = true
}

const removeCustomMusic = () => {
  currentEmbedUrl.value = ''
  inputUrl.value = ''
  inputError.value = ''
  playerType.value = ''
  localStorage.removeItem('chill_custom_embed')
  localStorage.removeItem('chill_custom_type')
  isExpanded.value = false
  showInputModal.value = false
}

onMounted(() => {
  const savedEmbed = localStorage.getItem('chill_custom_embed')
  const savedType = localStorage.getItem('chill_custom_type')
  if (savedEmbed) {
    currentEmbedUrl.value = savedEmbed
    playerType.value = savedType || 'youtube'
  }
})
</script>

<template>
  <div class="fixed bottom-16 xl:bottom-6 right-6 z-40 flex flex-col items-end">

    <!-- ============================================== -->
    <!-- 1. ផ្ទាំង EXPANDED PLAYER                       -->
    <!-- ============================================== -->
    <div
      v-if="currentEmbedUrl && isExpanded"
      class="mb-3 w-60 sm:w-90 bg-slate-900/95 border border-slate-700/80 rounded-3xl p-4 shadow-2xl backdrop-blur-xl animate-scale-up"
    >
      <!-- Header Bar -->
      <div class="flex items-center justify-between pb-2.5 mb-2.5 border-b border-slate-800">
        <div class="flex items-center gap-2">
          <i :class="playerType === 'spotify' ? 'fa-brands fa-spotify text-emerald-400' : 'fa-brands fa-youtube text-rose-500'" class="text-base"></i>
          <span class="text-xs font-bold text-white">Study Music Deck</span>
        </div>

        <div class="flex items-center gap-1.5">
          <button
            @click="showInputModal = true"
            class="text-[10px] text-slate-400 hover:text-amber-400 bg-slate-800 px-2 py-1 rounded-lg transition"
            title="ប្តូរ Link ថ្មី"
          >
            <i class="fa-solid fa-link"></i> ដូរ Link
          </button>

          <button
            @click="removeCustomMusic"
            class="w-6 h-6 rounded-lg bg-slate-800 text-slate-400 hover:text-rose-400 hover:bg-rose-500/10 flex items-center justify-center text-xs transition"
            title="លុបចម្រៀង"
          >
            <i class="fa-solid fa-trash-can"></i>
          </button>

          <button
            @click="isExpanded = false"
            class="w-6 h-6 rounded-lg bg-slate-800 text-slate-400 hover:text-white flex items-center justify-center text-xs transition"
            title="បង្រួមតូច"
          >
            <i class="fa-solid fa-minus"></i>
          </button>
        </div>
      </div>

      <!-- Iframe Display -->
      <div class="w-full rounded-2xl overflow-hidden bg-black shadow-inner mb-3">
        <iframe
          v-if="playerType === 'spotify'"
          :src="currentEmbedUrl"
          width="100%"
          height="152"
          frameBorder="0"
          allowfullscreen=""
          allow="autoplay; clipboard-write; encrypted-media; fullscreen; picture-in-picture"
          loading="lazy"
        ></iframe>

        <iframe
          v-else
          ref="ytIframeRef"
          :src="currentEmbedUrl"
          width="100%"
          height="170"
          frameborder="0"
          allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
          allowfullscreen
        ></iframe>
      </div>

      <!-- ============================================== -->
      <!-- 🎛️ CONTROLS: ប៊ូតុងប្តូរបទ & បន្ថយសំឡេង (YOUTUBE) -->
      <!-- ============================================== -->
      <div v-if="playerType === 'youtube'" class="bg-slate-950/80 border border-slate-800/80 rounded-2xl p-2.5 px-3 space-y-2">

        <!-- Controls Buttons (Prev, Play/Pause, Next) -->
        <div class="flex items-center justify-between">
          <div class="flex items-center gap-2">
            <button
              @click="prevTrack"
              class="w-8 h-8 rounded-xl bg-slate-800/80 hover:bg-slate-700 text-slate-300 hover:text-white flex items-center justify-center text-xs transition"
              title="បទមុន (Previous)"
            >
              <i class="fa-solid fa-backward-step"></i>
            </button>

            <button
              @click="togglePlay"
              class="w-8 h-8 rounded-xl bg-amber-400 hover:bg-amber-300 text-slate-950 flex items-center justify-center text-xs font-bold transition shadow"
              :title="isPlaying ? 'ផ្អាក (Pause)' : 'ចាក់ (Play)'"
            >
              <i :class="isPlaying ? 'fa-solid fa-pause' : 'fa-solid fa-play ml-0.5'"></i>
            </button>

            <button
              @click="nextTrack"
              class="w-8 h-8 rounded-xl bg-slate-800/80 hover:bg-slate-700 text-slate-300 hover:text-white flex items-center justify-center text-xs transition"
              title="បទបន្ទាប់ (Next)"
            >
              <i class="fa-solid fa-forward-step"></i>
            </button>
          </div>

          <!-- Volume Controls -->
          <div class="flex items-center gap-2">
            <i :class="volume === 0 ? 'fa-solid fa-volume-xmark text-rose-400' : 'fa-solid fa-volume-high text-amber-400'" class="text-xs"></i>
            <input
              type="range"
              min="0"
              max="100"
              step="5"
              :value="volume"
              @input="changeVolume"
              class="w-20 h-1 bg-slate-800 rounded-lg appearance-none cursor-pointer accent-amber-400"
              title="បន្ថយ/តម្លើងសំឡេង"
            />
            <span class="text-[10px] font-mono text-slate-400 w-7 text-right">{{ volume }}%</span>
          </div>
        </div>

      </div>

      <!-- Spotify Notice -->
      <div v-else class="text-[10px] text-slate-400 font-khmer text-center bg-slate-950/60 p-2 rounded-xl border border-slate-800">
        💡 Spotify មានប៊ូតុងប្តូរបទ និងកម្រិតសំឡេងស្រាប់ក្នុងផ្ទាំងខាងលើ។
      </div>

    </div>

    <!-- ============================================== -->
    <!-- 2. FLOATING ACTION BUTTONS                     -->
    <!-- ============================================== -->
    <div class="flex items-center gap-2">
      <button
        v-if="currentEmbedUrl"
        @click="isExpanded = !isExpanded"
        class="px-4 py-2.5 rounded-full bg-slate-900/90 border border-amber-500/30 text-white hover:border-amber-400 shadow-xl backdrop-blur-md flex items-center gap-2.5 transition group"
      >
        <div class="w-2.5 h-2.5 rounded-full bg-emerald-400 animate-ping"></div>
        <i :class="playerType === 'spotify' ? 'fa-brands fa-spotify text-emerald-400' : 'fa-brands fa-youtube text-rose-500'"></i>
        <span class="text-xs font-semibold group-hover:text-amber-300">
          {{ isExpanded ? 'បង្រួម Player' : 'បញ្ជាចម្រៀង' }}
        </span>
      </button>

      <button
        @click="showInputModal = true"
        class="w-10 h-10 rounded-full bg-gradient-to-r from-amber-400 to-amber-500 text-slate-950 flex items-center justify-center shadow-lg hover:scale-110 active:scale-95 transition"
        title="ដាក់ Link ចម្រៀងផ្ទាល់ខ្លួន"
      >
        <i class="fa-solid fa-music text-sm"></i>
      </button>
    </div>

    <!-- ============================================== -->
    <!-- 3. INPUT MODAL                                 -->
    <!-- ============================================== -->
    <div v-if="showInputModal" class="fixed inset-0 z-50 bg-black/85 backdrop-blur-md flex items-center justify-center p-4">
      <div class="bg-slate-900 border border-slate-800 rounded-3xl p-6 sm:p-8 max-w-md w-full shadow-2xl relative">
        <button @click="showInputModal = false" class="absolute top-5 right-5 text-slate-400 hover:text-white">✕</button>

        <div class="flex items-center gap-3 mb-4">
          <div class="w-10 h-10 rounded-2xl bg-amber-400/20 text-amber-400 flex items-center justify-center text-lg">
            <i class="fa-solid fa-headphones"></i>
          </div>
          <div>
            <h3 class="text-sm font-bold text-white">ដាក់ចម្រៀងផ្ទាល់ខ្លួន (Custom Music)</h3>
            <p class="text-[10px] text-slate-400 font-khmer">គាំទ្រ Link ពី YouTube ឬ Spotify</p>
          </div>
        </div>

        <div v-if="inputError" class="mb-3 p-2.5 rounded-xl bg-rose-500/10 border border-rose-500/20 text-rose-300 text-xs font-khmer">
          {{ inputError }}
        </div>

        <form @submit.prevent="saveCustomMusic" class="space-y-3 mb-4">
          <input
            v-model="inputUrl"
            type="url"
            placeholder="https://www.youtube.com/watch?v=... ឬ Spotify"
            required
            class="w-full bg-slate-950 border border-slate-700 rounded-xl px-3.5 py-2.5 text-xs text-white placeholder-slate-500 focus:outline-none focus:border-amber-400 transition"
          />

          <button
            type="submit"
            class="w-full py-2.5 rounded-xl bg-amber-400 hover:bg-amber-300 text-slate-950 font-bold text-xs transition shadow-md flex items-center justify-center gap-2"
          >
            <i class="fa-solid fa-play"></i> ចាប់ផ្តើមចាក់ចម្រៀង
          </button>
        </form>

        <div v-if="currentEmbedUrl" class="text-center pt-3 border-t border-slate-800">
          <button
            @click="removeCustomMusic"
            type="button"
            class="text-xs text-rose-400 hover:text-rose-300 hover:underline font-khmer flex items-center justify-center gap-1.5 mx-auto transition"
          >
            <i class="fa-solid fa-trash-can text-xs"></i>
            <span>លុប Playlist នេះចេញ</span>
          </button>
        </div>
      </div>
    </div>

  </div>
</template>

<style scoped>
@keyframes scaleUp {
  from { transform: scale(0.9); opacity: 0; }
  to { transform: scale(1); opacity: 1; }
}
.animate-scale-up {
  animation: scaleUp 0.25s ease-out forwards;
}
</style>
អាចរៀនជាមួយគ្នាបានដូចជាអាចcallជាសម្លេងឬឆាត
