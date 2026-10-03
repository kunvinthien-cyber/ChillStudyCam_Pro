<script setup>
import { ref, onMounted, onUnmounted, computed } from 'vue'

// បញ្ជីបទចម្រៀង VannDa (និង Lofi Chill)
const playlist = [
  {
    id: 1,
    title: 'Time to Rise (feat. Master Kong Nay)',
    artist: 'VannDa (វណ្ណដា)',
    cover: 'https://images.unsplash.com/photo-1511671782779-c97d3d27a1d4?q=80&w=300&auto=format&fit=crop',
    // បើអ្នកមាន file ក្នុង public/songs/ ដាក់ '/songs/time-to-rise.mp3'
    // ខាងក្រោមនេះជា link គំរូសម្រាប់តេស្តសិន
    url: '/songs/song1.mp3'
  },
  {
    id: 2,
    title: 'Solo Gal 2 (Lofi Chill Edit)',
    artist: 'VannDa',
    cover: 'https://images.unsplash.com/photo-1493225457124-a3eb161ffa5f?q=80&w=300&auto=format&fit=crop',
    url: '/songs/song2.mp3'
  },
  {
    id: 3,
    title: 'Song Sa Kou Sne (Acoustic Chill)',
    artist: 'VannDa',
    cover: 'https://images.unsplash.com/photo-1470225620780-dba8ba36b745?q=80&w=300&auto=format&fit=crop',
    url: 'https://www.youtube.com/watch?v=tTWE1oqRm-s&list=RDtTWE1oqRm-s&start_radio=1'
  },
  {
    id: 4,
    title: 'Midnight Focus Study Beat',
    artist: 'ChillStudy x VannDa Vibe',
    cover: 'https://images.unsplash.com/photo-1518495973542-4542c06a5843?q=80&w=300&auto=format&fit=crop',
    url: 'https://cdn.pixabay.com/download/audio/2022/05/27/audio_1808fbf07a.mp3'
  }
]

const currentSongIndex = ref(0)
const isPlaying = ref(false)
const volume = ref(0.6)
const currentTime = ref(0)
const duration = ref(0)
const showPlaylist = ref(false)

const currentSong = computed(() => playlist[currentSongIndex.value])

// បង្កើត Audio Element តែមួយគត់ (ជៀសវាង Leak Memory)
let audio = new Audio()
audio.volume = volume.value

const formatTime = (secs) => {
  if (isNaN(secs)) return '00:00'
  const m = Math.floor(secs / 60)
  const s = Math.floor(secs % 60)
  return `${String(m).padStart(2, '0')}:${String(s).padStart(2, '0')}`
}

const loadAndPlaySong = (index) => {
  currentSongIndex.value = index
  audio.src = playlist[index].url
  audio.load()

  if (isPlaying.value) {
    audio.play().catch(err => console.log('Audio autoplay prevented:', err))
  }
}

const togglePlay = () => {
  if (!audio.src || audio.src === '') {
    audio.src = currentSong.value.url
  }

  if (isPlaying.value) {
    audio.pause()
    isPlaying.value = false
  } else {
    audio.play().then(() => {
      isPlaying.value = true
    }).catch(e => {
      console.warn('មិនទាន់មាន File ចម្រៀងក្នុង public/songs/ នៅឡើយទេ:', e)
      alert(`សូមដាក់ File "${currentSong.value.url}" ចូលក្នុង Folder frontend/public/songs/ ជាមុនសិន!`)
      isPlaying.value = false
    })
  }
}

const nextSong = () => {
  const nextIdx = (currentSongIndex.value + 1) % playlist.length
  loadAndPlaySong(nextIdx)
  if (!isPlaying.value) togglePlay()
}

const prevSong = () => {
  const prevIdx = (currentSongIndex.value - 1 + playlist.length) % playlist.length
  loadAndPlaySong(prevIdx)
  if (!isPlaying.value) togglePlay()
}

const selectSong = (index) => {
  loadAndPlaySong(index)
  togglePlay()
  showPlaylist.value = false
}

const onSeek = (e) => {
  const seekTo = (e.target.value / 100) * duration.value
  audio.currentTime = seekTo
}

const updateVolume = (e) => {
  volume.value = e.target.value
  audio.volume = volume.value
}

onMounted(() => {
  audio.src = currentSong.value.url

  // Update នាទីរត់តាមចម្រៀង
  audio.ontimeupdate = () => {
    currentTime.value = audio.currentTime
  }

  // ពេលដឹងរយៈពេលសរុបនៃបទចម្រៀង
  audio.onloadedmetadata = () => {
    duration.value = audio.duration
  }

  // ចប់មួយបទ រត់ទៅបទបន្ទាប់ស្វ័យប្រវត្ត
  audio.onended = () => {
    nextSong()
  }
})

onUnmounted(() => {
  audio.pause()
  audio.src = ''
})
</script>

<template>
  <div class="relative">

    <!-- Main Player Bar -->
    <div class="flex flex-col sm:flex-row items-center gap-4 bg-slate-900/95 backdrop-blur-xl border border-slate-800 p-3.5 px-5 rounded-3xl shadow-2xl">

      <!-- Album Cover + Title -->
      <div class="flex items-center gap-3 w-full sm:w-60">
        <div class="relative w-11 h-11 rounded-2xl overflow-hidden shadow-md border border-white/10 shrink-0">
          <img
            :src="currentSong.cover"
            :alt="currentSong.title"
            class="w-full h-full object-cover"
            :class="{'animate-pulse': isPlaying}"
          />
          <div v-if="isPlaying" class="absolute inset-0 bg-black/20 flex items-center justify-center">
            <i class="fa-solid fa-music text-amber-400 text-xs animate-bounce"></i>
          </div>
        </div>

        <div class="overflow-hidden text-left">
          <p class="text-xs font-bold text-white truncate hover:text-amber-300 transition cursor-pointer">
            {{ currentSong.title }}
          </p>
          <p class="text-[11px] text-amber-400/90 font-medium truncate">
            {{ currentSong.artist }}
          </p>
        </div>
      </div>

      <!-- Controls & Progress Bar -->
      <div class="flex-1 flex flex-col items-center gap-1.5 w-full">

        <!-- Buttons (Prev, Play, Next, Playlist toggle) -->
        <div class="flex items-center gap-3">
          <button @click="prevSong" class="text-slate-400 hover:text-white transition active:scale-95">
            <i class="fa-solid fa-backward-step text-sm"></i>
          </button>

          <button
            @click="togglePlay"
            class="w-9 h-9 rounded-full bg-amber-400 hover:bg-amber-300 text-slate-950 flex items-center justify-center shadow-lg hover:scale-105 active:scale-95 transition"
          >
            <i :class="isPlaying ? 'fa-solid fa-pause' : 'fa-solid fa-play'" class="text-xs ml-0.5"></i>
          </button>

          <button @click="nextSong" class="text-slate-400 hover:text-white transition active:scale-95">
            <i class="fa-solid fa-forward-step text-sm"></i>
          </button>

          <!-- Playlist Toggle Button -->
          <button
            @click="showPlaylist = !showPlaylist"
            class="text-slate-400 hover:text-amber-400 transition ml-2 text-xs flex items-center gap-1"
            title="មើលបញ្ជីចម្រៀង"
          >
            <i class="fa-solid fa-list-ul"></i>
            <span class="text-[10px] hidden md:inline">Playlist</span>
          </button>
        </div>

        <!-- Progress Scrub Bar -->
        <div class="w-full flex items-center gap-2 text-[10px] font-mono text-slate-400 select-none">
          <span>{{ formatTime(currentTime) }}</span>
          <input
            type="range"
            min="0"
            max="100"
            :value="duration ? (currentTime / duration) * 100 : 0"
            @input="onSeek"
            class="flex-1 h-1 bg-slate-800 rounded-lg appearance-none cursor-pointer accent-amber-400"
          />
          <span>{{ formatTime(duration) }}</span>
        </div>

      </div>

      <!-- Volume Slider -->
      <div class="hidden lg:flex items-center gap-2 pl-3 border-l border-slate-800">
        <i class="fa-solid fa-volume-low text-slate-400 text-xs"></i>
        <input
          type="range"
          min="0"
          max="1"
          step="0.05"
          :value="volume"
          @input="updateVolume"
          class="w-16 h-1 bg-slate-800 rounded-lg appearance-none cursor-pointer accent-amber-400"
        />
      </div>

    </div>

    <!-- Dropdown Playlist Drawer -->
    <div
      v-if="showPlaylist"
      class="absolute bottom-full mb-3 left-0 right-0 bg-slate-900/95 backdrop-blur-2xl border border-slate-800 rounded-3xl p-4 shadow-2xl z-50 max-h-60 overflow-y-auto"
    >
      <div class="flex items-center justify-between mb-3 px-2">
        <span class="text-xs font-bold text-white uppercase tracking-wider">VannDa & Chill Study Beats</span>
        <button @click="showPlaylist = false" class="text-slate-400 hover:text-white text-xs">✕</button>
      </div>

      <div class="space-y-1">
        <div
          v-for="(song, idx) in playlist"
          :key="song.id"
          @click="selectSong(idx)"
          :class="currentSongIndex === idx ? 'bg-amber-400/10 border-amber-400/30 text-amber-300' : 'hover:bg-slate-800/60 text-slate-300 border-transparent'"
          class="flex items-center justify-between p-2.5 rounded-2xl border cursor-pointer transition text-xs"
        >
          <div class="flex items-center gap-3">
            <span class="text-[11px] font-mono text-slate-500 w-4">{{ idx + 1 }}</span>
            <div>
              <p class="font-semibold leading-tight">{{ song.title }}</p>
              <p class="text-[10px] text-slate-400">{{ song.artist }}</p>
            </div>
          </div>
          <i v-if="currentSongIndex === idx && isPlaying" class="fa-solid fa-volume-high text-amber-400 text-xs animate-pulse"></i>
        </div>
      </div>
    </div>

  </div>
</template>
