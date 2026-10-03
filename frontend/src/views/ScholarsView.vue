<script setup>
import { useUser } from '../composables/useUser'

const { user } = useUser()

const badges = [
  { name: 'Hanuman Streak', rank: 'Hanuman IV', icon: 'fa-shield-halved', color: 'text-blue-400 bg-blue-500/10 border-blue-500/20', desc: 'សិក្សាជាប់គ្នាលើសពី ៧ ថ្ងៃ', unlocked: true },
  { name: 'Angkor Scholar', rank: 'Level 5', icon: 'fa-landmark', color: 'text-amber-400 bg-amber-500/10 border-amber-500/20', desc: 'សន្សំម៉ោងរៀនបានលើសពី ៥០ ម៉ោង', unlocked: true },
  { name: 'Apsara Explorer', rank: 'Special', icon: 'fa-award', color: 'text-emerald-400 bg-emerald-500/10 border-emerald-500/20', desc: 'ចូលរៀនគ្រប់បន្ទប់ Sanctuary ទាំងអស់', unlocked: false },
  { name: 'Master Focus', rank: 'Elite', icon: 'fa-crown', color: 'text-purple-400 bg-purple-500/10 border-purple-500/20', desc: 'បញ្ចប់ Pomodoro ១០០ ជុំ', unlocked: false }
]

const campuses = [
  { rank: 1, name: 'RUPP (សាកលវិទ្យាល័យភូមិន្ទភ្នំពេញ)', hours: '5,420 hrs', students: '1,240 នាក់' },
  { rank: 2, name: 'ITC (វិទ្យាស្ថានបច្ចេកវិទ្យាកម្ពុជា - តិចណូ)', hours: '4,890 hrs', students: '980 នាក់' },
  { rank: 3, name: 'PUC (សាកលវិទ្យាល័យបញ្ញាសាស្ត្រ)', hours: '3,890 hrs', students: '750 នាក់' },
  { rank: 4, name: 'NUM (សាកលវិទ្យាល័យជាតិគ្រប់គ្រង)', hours: '2,940 hrs', students: '620 នាក់' }
]
</script>

<template>
  <div class="space-y-6 max-w-4xl mx-auto">
    <div class="bg-gradient-to-r from-amber-500/10 via-slate-900 to-slate-900 border border-amber-500/20 p-6 rounded-3xl">
      <span class="text-[10px] uppercase font-bold tracking-wider text-amber-400 bg-amber-400/10 px-3 py-1 rounded-full border border-amber-400/20">
        🎖️ Angkor Scholars Hall
      </span>
      <h1 class="text-2xl font-black text-white mt-2">មហាសាលកិត្តិយសសិស្សខ្មែរ</h1>
      <p class="text-xs text-slate-400 font-khmer mt-1">ដោះសោរ Badge វប្បធម៌ខ្មែរ និងប្រកួតប្រជែងម៉ោងរៀនតំណាងឱ្យសាកលវិទ្យាល័យរបស់អ្នក!</p>
    </div>

    <!-- User Current Standing -->
    <div class="bg-slate-900/90 border border-slate-800 p-5 rounded-3xl flex items-center justify-between gap-4">
      <div class="flex items-center gap-4">
        <div class="w-14 h-14 rounded-2xl bg-amber-400/10 border border-amber-400/20 flex items-center justify-center text-amber-400 text-2xl">
          <i class="fa-solid fa-graduation-cap"></i>
        </div>
        <div>
          <span class="text-[10px] uppercase font-bold text-amber-400">{{ user.rank_title }}</span>
          <h3 class="text-base font-bold text-white">{{ user.name }}</h3>
          <p class="text-xs text-slate-400">🔥 {{ user.streak_days }} Days Streak • 🪙 {{ user.coins }} PTS</p>
        </div>
      </div>
    </div>

    <!-- Badges Grid -->
    <div>
      <h3 class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-3">Badges & Titles</h3>
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
        <div
          v-for="b in badges"
          :key="b.name"
          :class="b.unlocked ? 'bg-slate-900/90 border-slate-800' : 'bg-slate-950/40 border-slate-800/40 opacity-60'"
          class="border p-4 rounded-2xl flex items-center gap-3.5"
        >
          <div :class="b.color" class="w-11 h-11 rounded-xl border flex items-center justify-center text-lg shrink-0">
            <i :class="['fa-solid', b.icon]"></i>
          </div>
          <div class="flex-1">
            <div class="flex items-center justify-between">
              <h4 class="text-xs font-bold text-white">{{ b.name }}</h4>
              <span class="text-[9px] px-2 py-0.5 rounded-full bg-slate-800 text-slate-400 font-mono">{{ b.rank }}</span>
            </div>
            <p class="text-[11px] text-slate-400 font-khmer mt-0.5">{{ b.desc }}</p>
          </div>
        </div>
      </div>
    </div>

    <!-- Campus Cup Table -->
    <div>
      <h3 class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-3">🏆 Campus Cup Leaderboard (រដូវកាលទី ១)</h3>
      <div class="bg-slate-900/90 border border-slate-800 rounded-3xl overflow-hidden divide-y divide-slate-800/80">
        <div
          v-for="c in campuses"
          :key="c.rank"
          class="p-4 flex items-center justify-between text-xs hover:bg-slate-850 transition"
        >
          <div class="flex items-center gap-3">
            <span
              :class="c.rank === 1 ? 'bg-amber-400 text-slate-950' : 'bg-slate-800 text-slate-400'"
              class="w-6 h-6 rounded-full font-bold flex items-center justify-center text-[10px]"
            >
              {{ c.rank }}
            </span>
            <div>
              <p class="font-bold text-white">{{ c.name }}</p>
              <p class="text-[10px] text-slate-400">{{ c.students }} កំពុងចូលរួម</p>
            </div>
          </div>
          <span class="font-bold text-amber-400 font-mono">{{ c.hours }}</span>
        </div>
      </div>
    </div>
  </div>
</template>
