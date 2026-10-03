<script setup>
import { computed, onMounted } from 'vue'
import { useUser } from '../composables/useUser'

const { user, fetchUserProfile } = useUser()

const userStats = computed(() => {
  const currentUser = user.value || {}

  return {
  name: currentUser.name || 'Chamroeun',
  streakDays: currentUser.streak_days ?? currentUser.streakDays ?? 7,
  rankTitle: currentUser.rank_title ?? currentUser.rankTitle ?? 'Hanuman IV',
  studiedHours: currentUser.studied_hours ?? currentUser.studiedHours ?? 2.5,
  targetHours: currentUser.target_hours ?? currentUser.targetHours ?? 4.0,
  activeStudentsLive: currentUser.active_students_live ?? currentUser.activeStudentsLive ?? 142
  }
})

onMounted(() => {
  fetchUserProfile()
})
</script>

<template>
  <div class="mb-8">
    <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-slate-800/80 border border-slate-700/60 text-xs text-slate-300 mb-4">
      <span class="flex items-center gap-1.5">
        <i class="fa-solid fa-mug-saucer text-amber-400"></i> Phnom Penh Virtual Hub • សាលាសិក្សា
      </span>
      <span class="inline-block w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
    <span class="text-emerald-400 font-medium">{{ userStats.activeStudentsLive }} Students Live</span>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-center">
      <div class="lg:col-span-6">
        <h1 class="text-3xl lg:text-4xl font-extrabold text-white tracking-tight leading-tight">
          Welcome back,<br />
        {{ userStats.name }}! Ready to study today?
          <i class="fa-solid fa-mug-hot text-amber-400 text-2xl ml-1"></i>
        </h1>
        <p class="mt-3 text-sm text-slate-400 leading-relaxed font-light">
          <span class="text-slate-300 font-medium font-khmer">ស្វាគមន៍ការត្រឡប់មកវិញ! តោះចាប់ផ្តើមការសិក្សាថ្ងៃនេះ</span>
          — Choose your cozy sanctuary and dive into flow state.
        </p>
      </div>

      <div class="lg:col-span-6 grid grid-cols-1 sm:grid-cols-3 gap-3">
        <!-- Streak -->
        <div class="bg-slate-900/90 border border-slate-800 rounded-2xl p-4 flex items-center gap-3">
          <div class="w-10 h-10 rounded-xl bg-amber-500/10 border border-amber-500/20 flex items-center justify-center text-amber-400">
            <i class="fa-solid fa-fire text-lg"></i>
          </div>
          <div>
            <span class="text-[10px] uppercase tracking-wider text-slate-400 font-semibold block">Consistency</span>
          <div class="text-lg font-bold text-white leading-none mt-1">{{ userStats.streakDays }} Days</div>
            <span class="text-xs text-amber-400/90 font-medium">Streak</span>
          </div>
        </div>

        <!-- Rank -->
        <div class="bg-slate-900/90 border border-slate-800 rounded-2xl p-4 flex items-center gap-3">
          <div class="w-10 h-10 rounded-xl bg-blue-500/10 border border-blue-500/20 flex items-center justify-center text-blue-400">
            <i class="fa-solid fa-shield-halved text-lg"></i>
          </div>
          <div>
            <span class="text-[10px] uppercase tracking-wider text-slate-400 font-semibold block">Rank & Title</span>
          <div class="text-base font-bold text-white leading-none mt-1">{{ userStats.rankTitle }}</div>
          </div>
        </div>

        <!-- Target -->
        <div class="bg-slate-900/90 border border-slate-800 rounded-2xl p-4 flex items-center gap-3">
          <div class="w-10 h-10 rounded-xl bg-emerald-500/10 border border-emerald-500/20 flex items-center justify-center text-emerald-400">
            <i class="fa-solid fa-bullseye text-lg"></i>
          </div>
          <div>
            <span class="text-[10px] uppercase tracking-wider text-slate-400 font-semibold block">Daily Target</span>
          <div class="text-sm font-bold text-white mt-1">{{ userStats.studiedHours }} / {{ userStats.targetHours }} hrs</div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
