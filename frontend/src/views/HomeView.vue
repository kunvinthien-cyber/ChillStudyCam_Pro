<script setup>
import { ref, onMounted, computed } from 'vue'
import { useRouter } from 'vue-router'
import apiClient from '../services/api'
import HeroStats from '../components/HeroStats.vue'
import RoomCard from '../components/RoomCard.vue'

const router = useRouter()
const rooms = ref([])
const isLoading = ref(true)
const selectedGrade = ref('all')

// State បង្កើតបន្ទប់
const showCreateModal = ref(false)
const isCreating = ref(false)
const newRoom = ref({
  title: '',
  subtitle_khmer: '',
  description: '',
  grade_level: 'all',
  category_tag: '☕ Cafe Vibes',
  access_mode: 'public',
  passcode: ''
})

// State ផ្ទៀងផ្ទាត់ PIN
const showPinModal = ref(false)
const targetRoom = ref(null)
const inputPin = ref('')
const pinError = ref('')
const isVerifyingPin = ref(false)

const gradeTabs = [
  { id: 'all', name: 'បន្ទប់ទាំងអស់', icon: 'fa-solid fa-shapes' },
  { id: 'grade_12', name: 'ថ្នាក់ទី ១២ (បាក់ឌុប)', icon: 'fa-solid fa-graduation-cap' },
  { id: 'university', name: 'និស្សិតសាកលវិទ្យាល័យ', icon: 'fa-solid fa-building-columns' },
  { id: 'language', name: 'ភាសាបរទេស / IELTS', icon: 'fa-solid fa-language' },
  { id: 'high_school', name: 'វិទ្យាល័យ (ទី ១០-១១)', icon: 'fa-solid fa-book-open' }
]

const fetchRooms = async () => {
  try {
    isLoading.value = true
    const response = await apiClient.get('/rooms')
    rooms.value = response.data
  } catch (error) {
    console.error('Fetch rooms error:', error)
  } finally {
    isLoading.value = false
  }
}

const filteredRooms = computed(() => {
  if (selectedGrade.value === 'all') return rooms.value
  return rooms.value.filter(r => r.grade_level === selectedGrade.value)
})

const handleJoin = async (room) => {
  const accessMode = room.access_mode || (room.is_private ? 'pin' : 'public')
  if (accessMode === 'pin') {
    targetRoom.value = room
    inputPin.value = ''
    pinError.value = ''
    showPinModal.value = true
  } else if (accessMode === 'approval') {
    try {
      await apiClient.post(`/rooms/${room.id}/request-join`)
      router.push({ name: 'room', params: { id: room.id } })
    } catch (error) {
      alert(error.response?.data?.message || 'Could not request access to this room.')
    }
  } else {
    router.push({ name: 'room', params: { id: room.id } })
  }
}

const verifyAndEnter = async () => {
  if (!inputPin.value.trim()) return

  try {
    isVerifyingPin.value = true
    pinError.value = ''
    const res = await apiClient.post(`/rooms/${targetRoom.value.id}/verify-passcode`, {
      passcode: inputPin.value
    })

    if (res.data.valid) {
      showPinModal.value = false
      router.push({ name: 'room', params: { id: targetRoom.value.id } })
    }
  } catch (err) {
    pinError.value = err.response?.data?.message || 'លេខកូដសម្ងាត់ PIN មិនត្រឹមត្រូវឡើយ!'
  } finally {
    isVerifyingPin.value = false
  }
}

// ✅ បង្កើតបន្ទប់រៀនថ្មី (ដំណើរការ ១០០%)
const createRoom = async () => {
  if (!newRoom.value.title || !newRoom.value.subtitle_khmer) {
    alert('សូមបំពេញឈ្មោះបន្ទប់ និងការពណ៌នាខ្លី!')
    return
  }

  try {
    isCreating.value = true

    // រៀបចំ Payload ឱ្យត្រឹមត្រូវ (កុំផ្ញើ passcode ទទេ)
    const payload = {
      title: newRoom.value.title,
      subtitle_khmer: newRoom.value.subtitle_khmer,
      description: newRoom.value.description,
      grade_level: newRoom.value.grade_level,
      category_tag: newRoom.value.category_tag,
      access_mode: newRoom.value.access_mode,
      is_private: newRoom.value.access_mode !== 'public',
      passcode: newRoom.value.access_mode === 'pin' ? newRoom.value.passcode : null
    }

    const res = await apiClient.post('/rooms', payload)

    // បន្ថែមបន្ទប់ថ្មីទៅលើគេបង្អស់
    rooms.value.unshift(res.data)
    showCreateModal.value = false

    // Reset Form
    newRoom.value.title = ''
    newRoom.value.subtitle_khmer = ''
    newRoom.value.access_mode = 'public'
    newRoom.value.passcode = ''

    alert('បង្កើតបន្ទប់រៀនបានជោគជ័យ! 🎉')
    router.push({ name: 'room', params: { id: res.data.id } })
  } catch (err) {
    console.error('Create room error:', err)
    const errorMsg = err.response?.data?.message || err.message || 'មិនអាចបង្កើតបន្ទប់បានទេ!'
    alert(`កំហុស៖ ${errorMsg}`)
  } finally {
    isCreating.value = false
  }
}

onMounted(() => {
  fetchRooms()
})
</script>

<template>
  <div>
    <HeroStats />

    <!-- Grade Filter & Create Room Button -->
    <div class="mb-6 space-y-3">
      <div class="flex flex-wrap items-center justify-between gap-3">
        <label class="text-xs font-bold text-white uppercase tracking-wider flex items-center gap-1.5">
          <i class="fa-solid fa-layer-group text-amber-400"></i> ជ្រើសរើសកម្រិតថ្នាក់សិក្សា៖
        </label>

        <!-- ប៊ូតុងបង្កើតបន្ទប់ថ្មី -->
        <button
          @click="showCreateModal = true"
          class="px-4 py-2 rounded-xl bg-amber-400 hover:bg-amber-300 text-slate-950 font-bold text-xs transition shadow flex items-center gap-1.5"
        >
          <i class="fa-solid fa-plus text-xs"></i>
          <span>បង្កើតបន្ទប់ផ្ទាល់ខ្លួន (+ Create Room)</span>
        </button>
      </div>

      <!-- Grade Filter Buttons -->
      <div class="flex items-center gap-2 overflow-x-auto pb-2 scrollbar-none">
        <button
          v-for="tab in gradeTabs"
          :key="tab.id"
          @click="selectedGrade = tab.id"
          :class="selectedGrade === tab.id ? 'bg-amber-400 text-slate-950 font-bold shadow-lg shadow-amber-400/20 scale-[1.02]' : 'bg-slate-900/90 text-slate-400 hover:text-white border border-slate-800'"
          class="px-4 py-2.5 rounded-2xl text-xs flex items-center gap-2 shrink-0 transition duration-200"
        >
          <i :class="tab.icon"></i>
          <span>{{ tab.name }}</span>
        </button>
      </div>
    </div>

    <!-- Room Cards List -->
    <div>
      <div class="flex items-center justify-between mb-4">
        <h2 class="text-sm sm:text-base font-bold text-white">Live Virtual Sanctuaries</h2>
        <span class="text-[10px] px-2.5 py-0.5 rounded-full bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 font-medium">
          {{ filteredRooms.length }} បន្ទប់កំពុងបើក
        </span>
      </div>

      <div v-if="isLoading" class="text-center py-16 text-slate-400 text-xs">
        <div class="w-8 h-8 border-4 border-amber-400 border-t-transparent rounded-full animate-spin mx-auto mb-3"></div>
        កំពុងទាញយកបន្ទប់រៀន...
      </div>

      <div v-else class="space-y-5">
        <RoomCard
          v-for="room in filteredRooms"
          :key="room.id"
          :room="{
            id: room.id,
            title: room.title,
            subtitleKhmer: room.subtitle_khmer,
            description: room.description,
            categoryTag: room.category_tag,
            badgeTag: room.is_private ? '🔒 Private PIN' : room.badge_tag,
            ambientTitle: room.ambient_title,
            thumbnail: room.thumbnail,
            activeStudents: room.active_students,
            avatars: room.avatars || ['CS'],
            moreCount: room.more_count,
            isSanctuary: Boolean(room.is_sanctuary)
          }"
          @join="handleJoin(room)"
        />
      </div>
    </div>

    <!-- ➕ MODAL បង្កើតបន្ទប់រៀនថ្មី -->
    <div v-if="showCreateModal" class="fixed inset-0 z-50 bg-black/85 backdrop-blur-md flex items-center justify-center p-4">
      <div class="bg-slate-900 border border-slate-800 rounded-3xl p-6 sm:p-8 max-w-md w-full shadow-2xl relative">
        <button @click="showCreateModal = false" class="absolute top-5 right-5 text-slate-400 hover:text-white">✕</button>

        <h3 class="text-base font-bold text-white mb-1 flex items-center gap-2">
          <i class="fa-solid fa-chalkboard-user text-amber-400"></i> បង្កើតបន្ទប់សិក្សាផ្ទាល់ខ្លួន
        </h3>
        <p class="text-xs text-slate-400 font-khmer mb-4">បង្កើតកន្លែងរៀនសាធារណៈ ឬបន្ទប់ឯកជនជាមួយមិត្តភក្តិ។</p>

        <form @submit.prevent="createRoom" class="space-y-3 text-xs">
          <div>
            <label class="text-[10px] text-slate-400 block mb-1">ឈ្មោះបន្ទប់ (Room Title)៖</label>
            <input v-model="newRoom.title" type="text" placeholder="ឧ. ក្រុមស្រាវជ្រាវគណិត បាក់ឌុប A" required class="w-full bg-slate-950 border border-slate-700 rounded-xl p-2.5 text-white focus:outline-none focus:border-amber-400" />
          </div>

          <div>
            <label class="text-[10px] text-slate-400 block mb-1">ការពណ៌នាខ្លី (Subtitle)៖</label>
            <input v-model="newRoom.subtitle_khmer" type="text" placeholder="ឧ. ផ្តោតលើលំហាត់អាំងតេក្រាល និងរូបវិទ្យា" required class="w-full bg-slate-950 border border-slate-700 rounded-xl p-2.5 text-white focus:outline-none focus:border-amber-400" />
          </div>

          <div class="grid grid-cols-2 gap-2">
            <div>
              <label class="text-[10px] text-slate-400 block mb-1">កម្រិតថ្នាក់៖</label>
              <select v-model="newRoom.grade_level" class="w-full bg-slate-950 border border-slate-700 rounded-xl p-2.5 text-white focus:outline-none focus:border-amber-400">
                <option value="all">ទូទៅ (ទាំងអស់)</option>
                <option value="grade_12">ថ្នាក់ទី ១២ (បាក់ឌុប)</option>
                <option value="university">និស្សិតសាកលវិទ្យាល័យ</option>
                <option value="language">ភាសាបរទេស / IELTS</option>
                <option value="high_school">វិទ្យាល័យ (១០-១១)</option>
              </select>
            </div>

            <div>
              <label class="text-[10px] text-slate-400 block mb-1">ប្រភេទបរិយាកាស៖</label>
              <select v-model="newRoom.category_tag" class="w-full bg-slate-950 border border-slate-700 rounded-xl p-2.5 text-white focus:outline-none focus:border-amber-400">
                <option value="☕ Cafe Vibes">☕ Cafe Vibes</option>
                <option value="🤫 Silent Study">🤫 Silent Study</option>
                <option value="🌿 Calm & Mindful">🌿 Calm Nature</option>
              </select>
            </div>
          </div>

          <!-- Private PIN Toggle -->
          <div class="p-3 rounded-2xl bg-slate-950 border border-slate-800 space-y-2">
            <div class="flex items-center justify-between">
              <label for="privateToggle" class="text-[11px] text-slate-300 font-khmer cursor-pointer flex items-center gap-1.5">
                <i class="fa-solid fa-lock text-amber-400 text-xs"></i> ធ្វើជាបន្ទប់ឯកជន (Private Room)
              </label>
              <select id="privateToggle" v-model="newRoom.access_mode" class="bg-slate-900 border border-slate-700 rounded-xl px-2 py-1 text-xs text-white">
                <option value="public">Public room</option>
                <option value="pin">Private with PIN</option>
                <option value="approval">Private with admin approval</option>
              </select>
            </div>

            <div v-if="newRoom.access_mode === 'pin'">
              <label class="text-[10px] text-slate-400 block mb-1">កំណត់លេខកូដសម្ងាត់ PIN (៤ ខ្ទង់)៖</label>
              <input v-model="newRoom.passcode" type="password" maxlength="6" placeholder="ឧ. 1234" required class="w-full bg-slate-900 border border-amber-400 rounded-xl p-2 text-white font-mono tracking-widest text-center" />
            </div>
          </div>

          <button type="submit" :disabled="isCreating" class="w-full py-3 rounded-xl bg-amber-400 hover:bg-amber-300 text-slate-950 font-bold text-xs transition shadow-lg mt-3 disabled:opacity-50">
            {{ isCreating ? 'កំពុងបង្កើត...' : 'បង្កើតបន្ទប់រៀនឥឡូវនេះ 🚀' }}
          </button>
        </form>
      </div>
    </div>

    <!-- 🔒 MODAL វាយបញ្ចូល PIN សម្រាប់បន្ទប់ PRIVATE -->
    <div v-if="showPinModal" class="fixed inset-0 z-50 bg-black/85 backdrop-blur-md flex items-center justify-center p-4">
      <div class="bg-slate-900 border border-amber-500/30 rounded-3xl p-6 max-w-xs w-full text-center shadow-2xl relative animate-scale-up">
        <button @click="showPinModal = false" class="absolute top-4 right-4 text-slate-400 hover:text-white">✕</button>

        <div class="w-12 h-12 rounded-2xl bg-amber-400/20 text-amber-400 flex items-center justify-center text-xl mx-auto mb-3">
          <i class="fa-solid fa-lock"></i>
        </div>

        <h4 class="text-sm font-bold text-white mb-1">{{ targetRoom?.title }}</h4>
        <p class="text-[11px] text-slate-400 font-khmer mb-4">នេះជាបន្ទប់ឯកជន សូមវាយលេខកូដសម្ងាត់ PIN ដើម្បីចូលរៀន៖</p>

        <div v-if="pinError" class="mb-3 p-2 rounded-xl bg-rose-500/10 border border-rose-500/20 text-rose-300 text-[10px] font-khmer">
          {{ pinError }}
        </div>

        <form @submit.prevent="verifyAndEnter" class="space-y-3">
          <input
            v-model="inputPin"
            type="password"
            maxlength="6"
            placeholder="••••"
            required
            autofocus
            class="w-full bg-slate-950 border border-amber-400 rounded-2xl p-2.5 text-center text-lg font-mono tracking-widest text-white focus:outline-none"
          />

          <button type="submit" :disabled="isVerifyingPin" class="w-full py-2.5 rounded-xl bg-amber-400 hover:bg-amber-300 text-slate-950 font-bold text-xs transition shadow-lg disabled:opacity-50">
            {{ isVerifyingPin ? 'កំពុងផ្ទៀងផ្ទាត់...' : 'ចូលបន្ទប់ 🔓' }}
          </button>
        </form>
      </div>
    </div>

  </div>
</template>
