<script setup>
import { ref } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import LeftSidebar from './components/LeftSidebar.vue'
import RightSidebar from './components/RightSidebar.vue'
import FloatingMusicPlayer from './components/FloatingMusicPlayer.vue'
import InviteNotificationBanner from './components/InviteNotificationBanner.vue'

const router = useRouter()
const route = useRoute()
const isMobileLeftOpen = ref(false)
const isMobileRightOpen = ref(false)
</script>

<template>
  <!-- ១. បើកំពុងនៅទំព័រ Login៖ បង្ហាញតែផ្ទាំង Login ពេញអេក្រង់ (គ្មាន Sidebar) -->
  <div v-if="route.name === 'login'" class="min-h-screen bg-[#070b14]">
    <router-view />
  </div>

  <!-- ២. បើបាន Login ហើយ៖ បង្ហាញផ្ទាំង ChillStudy Workspace ពេញលេញ -->
  <div v-else class="min-h-screen bg-[#070b14] text-slate-100 flex flex-col xl:flex-row selection:bg-amber-400 selection:text-slate-950 relative overflow-x-hidden">

    <!-- Mobile Top Header -->
    <header class="xl:hidden flex items-center justify-between px-4 py-3 bg-slate-950/80 backdrop-blur-md border-b border-slate-800 sticky top-0 z-30">
      <button @click="isMobileLeftOpen = true" class="w-9 h-9 rounded-xl bg-slate-800/80 text-amber-400 flex items-center justify-center border border-slate-700">
        <i class="fa-solid fa-bars text-sm"></i>
      </button>

      <div @click="router.push('/')" class="flex items-center gap-2 cursor-pointer">
        <i class="fa-solid fa-mug-hot text-amber-400 text-base"></i>
        <span class="text-sm font-bold text-white">ChillStudy KH</span>
      </div>

      <button @click="isMobileRightOpen = true" class="w-9 h-9 rounded-xl bg-slate-800/80 text-emerald-400 flex items-center justify-center border border-slate-700">
        <i class="fa-solid fa-robot text-sm"></i>
      </button>
    </header>

    <!-- Desktop Left Sidebar -->
    <LeftSidebar />

    <!-- Mobile Left Drawer -->
    <div v-if="isMobileLeftOpen" class="fixed inset-0 z-50 xl:hidden bg-black/70 backdrop-blur-sm flex">
      <div class="w-72 bg-[#0a0f1d] h-full shadow-2xl flex flex-col relative border-r border-slate-800">
        <button @click="isMobileLeftOpen = false" class="absolute top-4 right-4 w-8 h-8 rounded-full bg-slate-800 text-slate-400 flex items-center justify-center">
          <i class="fa-solid fa-xmark"></i>
        </button>
        <LeftSidebar class="flex! w-full! border-none" />
      </div>
      <div class="flex-1" @click="isMobileLeftOpen = false"></div>
    </div>

    <!-- MAIN CONTENT VIEW -->
    <main class="min-w-0 flex-1 px-4 py-5 sm:p-6 md:p-8 overflow-y-auto max-w-5xl mx-auto w-full pb-24 xl:pb-8">
      <router-view />
    </main>

    <!-- Desktop Right Sidebar -->
    <RightSidebar />

    <!-- Mobile Right Drawer -->
    <div v-if="isMobileRightOpen" class="fixed inset-0 z-50 xl:hidden bg-black/70 backdrop-blur-sm flex justify-end">
      <div class="flex-1" @click="isMobileRightOpen = false"></div>
      <div class="w-80 bg-[#0a0f1d] h-full shadow-2xl flex flex-col relative border-l border-slate-800 overflow-y-auto p-4">
        <button @click="isMobileRightOpen = false" class="self-end w-8 h-8 rounded-full bg-slate-800 text-slate-400 flex items-center justify-center mb-2">
          <i class="fa-solid fa-xmark"></i>
        </button>
        <RightSidebar class="block! !w-full border-none !p-0" />
      </div>
    </div>

    <!-- Mobile Bottom Nav -->
    <nav class="xl:hidden fixed bottom-0 left-0 right-0 z-30 bg-slate-950/90 backdrop-blur-lg border-t border-slate-800 px-6 py-2 flex items-center justify-between">
      <button @click="router.push('/')" class="flex flex-col items-center gap-1 text-amber-400">
        <i class="fa-solid fa-landmark text-base"></i>
        <span class="text-[10px]">Home</span>
      </button>
      <button @click="isMobileLeftOpen = true" class="flex flex-col items-center gap-1 text-slate-400">
        <i class="fa-solid fa-compass text-base"></i>
        <span class="text-[10px]">Explore</span>
      </button>
      <button @click="isMobileRightOpen = true" class="flex flex-col items-center gap-1 text-slate-400">
        <i class="fa-solid fa-robot text-base"></i>
        <span class="text-[10px]">AI Tutor</span>
      </button>
    </nav>

  </div>
    <!-- 🎵 Global Floating Music Player (ចាក់ជាប់រហូត មិនដាច់ពេលប្តូរ Page) -->
  <FloatingMusicPlayer />
  <!-- 🔔 Invite Notification Banner -->
  <InviteNotificationBanner />
</template>
