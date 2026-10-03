<script setup>
import { useUser } from '../composables/useUser'

const { user } = useUser()

const weeklyDays = [
  { day: 'ច័ន្ទ', hours: 3.2, percent: 80 },
  { day: 'អង្គារ', hours: 4.0, percent: 100 },
  { day: 'ពុធ', hours: 2.5, percent: 62 },
  { day: 'ព្រហ', hours: 3.8, percent: 95 },
  { day: 'សុក្រ', hours: 4.5, percent: 100 },
  { day: 'សៅរ៍', hours: 1.5, percent: 38 },
  { day: 'អាទិត្យ', hours: 2.8, percent: 70 }
]
</script>

<template>
  <div class="space-y-6 max-w-4xl mx-auto">
    <div class="bg-linear-to-r from-amber-500/10 via-slate-900 to-slate-900 border border-amber-500/20 p-6 rounded-3xl">
      <span class="text-[10px] uppercase font-bold tracking-wider text-amber-400 bg-amber-400/10 px-3 py-1 rounded-full border border-amber-400/20">
        📊 Study Analytics
      </span>
      <h1 class="text-2xl font-black text-white mt-2">ស្ថិតិ និងទម្លាប់នៃការរៀនសូត្រ</h1>
      <p class="text-xs text-slate-400 font-khmer mt-1">តាមដានវឌ្ឍនភាព និងពេលវេលាដែលអ្នកបានខិតខំប្រឹងប្រែងរៀនសូត្រ!</p>
    </div>

    <!-- Summary Stats -->
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
      <div class="bg-slate-900/90 border border-slate-800 p-4 rounded-2xl text-center">
        <span class="text-xl">⏱️</span>
        <h4 class="text-lg font-bold text-white mt-1">{{ user.studied_hours }}h</h4>
        <span class="text-[10px] text-slate-400 uppercase font-semibold">ម៉ោងរៀនសរុប</span>
      </div>
      <div class="bg-slate-900/90 border border-slate-800 p-4 rounded-2xl text-center">
        <span class="text-xl">🔥</span>
        <h4 class="text-lg font-bold text-amber-400 mt-1">{{ user.streak_days }} ថ្ងៃ</h4>
        <span class="text-[10px] text-slate-400 uppercase font-semibold">Streak បន្តបន្ទាប់</span>
      </div>
      <div class="bg-slate-900/90 border border-slate-800 p-4 rounded-2xl text-center">
        <span class="text-xl">🪙</span>
        <h4 class="text-lg font-bold text-amber-400 mt-1">{{ user.coins }}</h4>
        <span class="text-[10px] text-slate-400 uppercase font-semibold">PTS កាក់រកបាន</span>
      </div>
      <div class="bg-slate-900/90 border border-slate-800 p-4 rounded-2xl text-center">
        <span class="text-xl">🎯</span>
        <h4 class="text-lg font-bold text-emerald-400 mt-1">84%</h4>
        <span class="text-[10px] text-slate-400 uppercase font-semibold">អត្រាសម្រេចគោលដៅ</span>
      </div>
    </div>

    <!-- Weekly Chart Bar -->
    <div class="bg-slate-900/90 border border-slate-800 p-6 rounded-3xl">
      <h3 class="text-xs font-bold text-white uppercase tracking-wider mb-6 flex items-center justify-between">
        <span>ម៉ោងរៀនប្រចាំសប្តាហ៍នេះ (Weekly Focus Hours)</span>
        <span class="text-[10px] text-emerald-400 font-mono">មធ្យម ៣.១ ម៉ោង/ថ្ងៃ</span>
      </h3>

      <div class="grid grid-cols-7 gap-2 items-end h-44 pt-4 border-b border-slate-800">
        <div v-for="d in weeklyDays" :key="d.day" class="flex flex-col items-center gap-2 h-full justify-end">
          <span class="text-[9px] text-slate-400 font-mono">{{ d.hours }}h</span>
          <div
            class="w-full max-w-8 bg-linear-to-t from-amber-500 to-amber-300 rounded-t-xl transition-all duration-500 hover:brightness-125"
            :style="{ height: `${d.percent}%` }"
          ></div>
          <span class="text-[10px] text-slate-400 mt-1 font-khmer">{{ d.day }}</span>
        </div>
      </div>
    </div>
  </div>
</template>
