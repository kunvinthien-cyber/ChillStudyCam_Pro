<script setup>
import { ref, onUnmounted } from 'vue'

const sounds = ref([
  { id: 'rain', name: 'Monsoon Rain (ភ្លៀងធ្លាក់)', icon: 'fa-cloud-showers-heavy', url: 'https://cdn.pixabay.com/download/audio/2022/05/16/audio_db6591201e.mp3', playing: false, volume: 0.5, audio: null },
  { id: 'cafe', name: 'Phnom Penh Cafe (សំឡេងកាហ្វេ)', icon: 'fa-mug-saucer', url: 'https://cdn.pixabay.com/download/audio/2021/09/06/audio_27318042fa.mp3', playing: false, volume: 0.5, audio: null },
  { id: 'waves', name: 'Sea Waves (រលកសមុទ្រ)', icon: 'fa-water', url: 'https://cdn.pixabay.com/download/audio/2022/03/10/audio_c35272a24c.mp3', playing: false, volume: 0.5, audio: null },
  { id: 'fire', name: 'Campfire (ភ្លើងគប់កក់ក្តៅ)', icon: 'fa-fire', url: 'https://cdn.pixabay.com/download/audio/2022/01/18/audio_2a433d964f.mp3', playing: false, volume: 0.5, audio: null }
])

const toggleSound = (s) => {
  if (!s.audio) {
    s.audio = new Audio(s.url)
    s.audio.loop = true
    s.audio.volume = s.volume
  }

  if (s.playing) {
    s.audio.pause()
    s.playing = false
  } else {
    s.audio.play().catch(e => console.log('Audio error:', e))
    s.playing = true
  }
}

const updateVolume = (s, e) => {
  s.volume = parseFloat(e.target.value)
  if (s.audio) s.audio.volume = s.volume
}

// Function បិទសំឡេងទាំងអស់ និងសម្អាត Memory
const stopAll = () => {
  sounds.value.forEach(s => {
    if (s.audio) {
      s.audio.pause()
      s.audio.currentTime = 0
      s.audio.src = '' // ដោះលែង Resource ចេញពី Browser
      s.audio = null
    }
    s.playing = false
  })
}

// 🛡️ សំខាន់បំផុត៖ ពេលចាកចេញពីទំព័រ Soundboard បិទសំឡេងភ្លាម កុំឱ្យលាន់ជាន់គ្នា
onUnmounted(() => {
  stopAll()
})
</script>

<template>
  <div class="space-y-6 max-w-4xl mx-auto">
    <div class="flex flex-wrap items-center justify-between gap-4 bg-gradient-to-r from-amber-500/10 via-slate-900 to-slate-900 border border-amber-500/20 p-6 rounded-3xl">
      <div>
        <span class="text-[10px] uppercase font-bold tracking-wider text-amber-400 bg-amber-400/10 px-3 py-1 rounded-full border border-amber-400/20">
          🎵 Ambient Soundboard
        </span>
        <h1 class="text-2xl font-black text-white mt-2">ឧបករណ៍លាយសំឡេង Ambient សម្រាកខួរក្បាល</h1>
        <p class="text-xs text-slate-400 font-khmer mt-1">អ្នកអាចចុចចាក់សំឡេងច្រើនបញ្ចូលគ្នា និងសារ៉េកម្រិតសំឡេងតាមចិត្ត!</p>
      </div>
      <button @click="stopAll" class="px-4 py-2 rounded-xl bg-slate-800 text-rose-400 border border-rose-500/20 hover:bg-rose-500/10 text-xs font-semibold transition flex items-center gap-1.5">
        <i class="fa-solid fa-stop text-xs"></i>
        <span>បិទសំឡេងទាំងអស់</span>
      </button>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
      <div
        v-for="s in sounds"
        :key="s.id"
        :class="s.playing ? 'bg-amber-400/5 border-amber-400/40 shadow-lg shadow-amber-400/5' : 'bg-slate-900/90 border-slate-800'"
        class="border p-5 rounded-3xl transition duration-300 flex flex-col justify-between space-y-4"
      >
        <div class="flex items-center justify-between">
          <div class="flex items-center gap-3">
            <div :class="s.playing ? 'bg-amber-400 text-slate-950' : 'bg-slate-800 text-slate-300'" class="w-10 h-10 rounded-2xl flex items-center justify-center transition text-sm">
              <i :class="['fa-solid', s.icon]"></i>
            </div>
            <div>
              <h3 class="text-xs font-bold text-white">{{ s.name }}</h3>
              <span class="text-[10px] text-slate-400">{{ s.playing ? '🟢 កំពុងចាក់...' : '⚪ បានផ្អាក' }}</span>
            </div>
          </div>
          <button
            @click="toggleSound(s)"
            :class="s.playing ? 'bg-amber-400 text-slate-950 hover:bg-amber-300' : 'bg-slate-800 text-white hover:bg-slate-700'"
            class="w-9 h-9 rounded-xl flex items-center justify-center transition shadow-md"
          >
            <i :class="s.playing ? 'fa-solid fa-pause' : 'fa-solid fa-play'" class="text-xs"></i>
          </button>
        </div>

        <div class="space-y-1">
          <div class="flex justify-between text-[10px] text-slate-400">
            <span>កម្រិតសំឡេង៖</span>
            <span>{{ Math.round(s.volume * 100) }}%</span>
          </div>
          <input
            type="range"
            min="0"
            max="1"
            step="0.05"
            :value="s.volume"
            @input="updateVolume(s, $event)"
            class="w-full h-1 bg-slate-800 rounded-lg appearance-none cursor-pointer accent-amber-400"
          />
        </div>
      </div>
    </div>
  </div>
</template>

