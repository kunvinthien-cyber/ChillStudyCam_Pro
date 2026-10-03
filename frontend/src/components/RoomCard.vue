<script setup>
defineProps({
  room: {
    type: Object,
    required: true
  }
})

const emit = defineEmits(['join', 'quickPeek'])
</script>

<template>
  <div class="bg-slate-900/80 border border-slate-800/80 hover:border-slate-700/80 transition-all duration-300 rounded-3xl overflow-hidden group shadow-xl mb-6">

    <!-- Thumbnail -->
    <div class="relative h-56 w-full overflow-hidden bg-slate-950">
      <img
        :src="room.thumbnail"
        :alt="room.title"
        class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500 ease-out brightness-[0.85]"
      />

      <!-- Overlay Badges -->
      <div class="absolute top-4 left-4 right-4 flex items-center justify-between">
        <div class="flex items-center gap-2">
          <span class="px-3 py-1 rounded-full text-xs font-medium bg-black/60 backdrop-blur-md text-emerald-300 border border-emerald-500/20 flex items-center gap-1.5">
            <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
            {{ room.activeStudents }} Students Studying
          </span>

          <span class="px-3 py-1 rounded-full text-xs font-medium bg-black/60 backdrop-blur-md text-amber-200 border border-amber-500/20">
            {{ room.categoryTag }}
          </span>
        </div>

        <button class="w-9 h-9 rounded-full bg-black/50 backdrop-blur-md border border-white/10 flex items-center justify-center text-slate-300 hover:text-rose-400 hover:scale-110 transition-all">
          <i class="fa-regular fa-heart"></i>
        </button>
      </div>

      <!-- Audio Pill -->
      <div class="absolute bottom-4 left-4">
        <div class="px-3 py-1.5 rounded-xl bg-slate-950/80 backdrop-blur-md border border-slate-700/50 text-xs text-slate-200 flex items-center gap-2">
          <i class="fa-solid fa-music text-amber-400"></i>
          <span class="font-medium tracking-wide">{{ room.ambientTitle }}</span>
        </div>
      </div>
    </div>

    <!-- Content -->
    <div class="p-6">
      <div class="flex flex-wrap items-center justify-between gap-2 mb-2">
        <h3 class="text-xl font-bold text-white tracking-wide group-hover:text-amber-300 transition-colors">
          {{ room.title }}
        </h3>
        <span v-if="room.badgeTag" class="text-xs px-2.5 py-1 rounded-lg bg-slate-800 text-amber-300/90 font-medium border border-amber-500/20 flex items-center gap-1.5">
          <i class="fa-solid fa-graduation-cap text-xs"></i> {{ room.badgeTag }}
        </span>
      </div>

      <p class="text-xs text-slate-400 font-khmer mb-3 font-light">
        {{ room.subtitleKhmer }}
      </p>

      <p class="text-xs text-slate-300/80 leading-relaxed mb-6 font-light line-clamp-2">
        {{ room.description }}
      </p>

      <!-- Bottom Actions -->
      <!-- ប៊ូតុង Join Room / Enter Sanctuary -->
<div class="flex items-center gap-2">
  <button
    @click="emit('quickPeek', room)"
    class="px-4 py-2 text-xs font-semibold rounded-xl text-slate-300 bg-slate-800/80 hover:bg-slate-700 hover:text-white border border-slate-700/60 transition flex items-center gap-2"
  >
    <i class="fa-solid fa-volume-high"></i> Quick Peek
  </button>

  <button
    @click="emit('join', room)"
    :class="room.isSanctuary ? 'bg-emerald-400 hover:bg-emerald-300 text-slate-950' : 'bg-amber-400 hover:bg-amber-300 text-slate-950'"
    class="px-5 py-2 text-xs font-bold rounded-xl transition shadow-lg hover:scale-105 flex items-center gap-2"
  >
    <i :class="room.isSanctuary ? 'fa-solid fa-door-open' : 'fa-solid fa-arrow-right-to-bracket'"></i>
    <span>{{ room.isSanctuary ? 'Enter Sanctuary' : 'Join Room' }}</span>
  </button>
</div>
    </div>
  </div>
</template>
