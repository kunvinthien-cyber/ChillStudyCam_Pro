<script setup>
import { ref, onMounted, computed } from 'vue'
import apiClient from '../services/api'
import { useUser } from '../composables/useUser'

const { addReward } = useUser()

const tasks = ref([])
const newTaskTitle = ref('')
const newTaskSubject = ref('')
const activeFilter = ref('all') // 'all', 'pending', 'completed'
const isLoading = ref(true)
const isSubmitting = ref(false)

const subjects = ['ទូទៅ', 'គណិតវិទ្យា', 'រូបវិទ្យា', 'ភាសាអង់គ្លេស', 'Programming', 'IELTS']

const fetchTasks = async () => {
  try {
    isLoading.value = true
    const res = await apiClient.get('/tasks')
    tasks.value = res.data
  } catch (error) {
    console.error('Fetch tasks error:', error)
  } finally {
    isLoading.value = false
  }
}

const addTask = async () => {
  if (!newTaskTitle.value.trim() || isSubmitting.value) return

  try {
    isSubmitting.value = true
    const res = await apiClient.post('/tasks', {
      title: newTaskTitle.value,
      subject: newTaskSubject.value || 'ទូទៅ'
    })
    tasks.value.unshift(res.data)
    newTaskTitle.value = ''
    newTaskSubject.value = ''
  } catch {
    alert('មិនអាចបង្កើតកិច្ចការបានទេ!')
  } finally {
    isSubmitting.value = false
  }
}

const toggleTask = async (task) => {
  try {
    const res = await apiClient.patch(`/tasks/${task.id}/toggle`)
    task.is_completed = res.data.task.is_completed

    // បើបាន 5 PTS បូកចូល State ភ្លាម
    if (res.data.earned_coins > 0) {
      addReward(res.data.earned_coins)
    }
  } catch (error) {
    console.error('Toggle task error:', error)
  }
}

const deleteTask = async (taskId) => {
  try {
    await apiClient.delete(`/tasks/${taskId}`)
    tasks.value = tasks.value.filter(t => t.id !== taskId)
  } catch {
    alert('មិនអាចលុបកិច្ចការបានទេ!')
  }
}

const filteredTasks = computed(() => {
  if (activeFilter.value === 'pending') return tasks.value.filter(t => !t.is_completed)
  if (activeFilter.value === 'completed') return tasks.value.filter(t => t.is_completed)
  return tasks.value
})

const completedCount = computed(() => tasks.value.filter(t => t.is_completed).length)

onMounted(() => {
  fetchTasks()
})
</script>

<template>
  <div class="space-y-6 max-w-4xl mx-auto">

    <!-- Header -->
    <div class="flex flex-wrap items-center justify-between gap-4 bg-gradient-to-r from-amber-500/10 via-slate-900 to-slate-900 border border-amber-500/20 p-6 rounded-3xl">
      <div>
        <span class="text-[10px] uppercase font-bold tracking-wider text-amber-400 bg-amber-400/10 px-3 py-1 rounded-full border border-amber-400/20">
          📋 Focus Tasks
        </span>
        <h1 class="text-2xl font-black text-white mt-2">បញ្ជីកិច្ចការ និងមេរៀនប្រចាំថ្ងៃ</h1>
        <p class="text-xs text-slate-400 font-khmer mt-1">កត់ត្រា និងបំពេញកិច្ចការដើម្បីទទួលបាន +5 PTS Coins បន្ថែមរាល់ពេលសម្រេចបានមួយ!</p>
      </div>
      <div class="bg-slate-950/80 border border-slate-800 p-3 px-5 rounded-2xl flex items-center gap-3">
        <span class="text-2xl">✅</span>
        <div>
          <span class="text-[10px] text-slate-400 uppercase font-semibold">បានសម្រេច</span>
          <p class="text-base font-bold text-emerald-400">{{ completedCount }} / {{ tasks.length }}</p>
        </div>
      </div>
    </div>

    <!-- Add Task Card -->
    <form @submit.prevent="addTask" class="bg-slate-900/90 border border-slate-800 p-4 rounded-3xl shadow-lg space-y-3">
      <div class="flex flex-col sm:flex-row gap-3">
        <input
          v-model="newTaskTitle"
          type="text"
          placeholder="តើអ្នកត្រូវរៀន ឬធ្វើកិច្ចការអ្វីថ្ងៃនេះ? (ឧ. ធ្វើលំហាត់គណិតវិទ្យា...)"
          class="flex-1 bg-slate-950 border border-slate-800 rounded-2xl px-4 py-3 text-xs text-white placeholder-slate-500 focus:outline-none focus:border-amber-400 transition"
        />

        <select
          v-model="newTaskSubject"
          class="bg-slate-950 border border-slate-800 rounded-2xl px-3 py-3 text-xs text-slate-300 focus:outline-none focus:border-amber-400 transition"
        >
          <option value="" disabled selected>ជ្រើសរើសមុខវិជ្ជា</option>
          <option v-for="sub in subjects" :key="sub" :value="sub">{{ sub }}</option>
        </select>

        <button
          type="submit"
          :disabled="isSubmitting || !newTaskTitle.trim()"
          class="px-6 py-3 rounded-2xl bg-amber-400 hover:bg-amber-300 text-slate-950 font-bold text-xs transition shadow-md flex items-center justify-center gap-2 disabled:opacity-50 shrink-0"
        >
          <i class="fa-solid fa-plus text-xs"></i>
          <span>បន្ថែម</span>
        </button>
      </div>
    </form>

    <!-- Filters -->
    <div class="flex items-center gap-2">
      <button
        @click="activeFilter = 'all'"
        :class="activeFilter === 'all' ? 'bg-amber-400 text-slate-950 font-bold' : 'bg-slate-900 text-slate-400 border border-slate-800'"
        class="px-4 py-1.5 rounded-xl text-xs transition"
      >
        ទាំងអស់ ({{ tasks.length }})
      </button>
      <button
        @click="activeFilter = 'pending'"
        :class="activeFilter === 'pending' ? 'bg-amber-400 text-slate-950 font-bold' : 'bg-slate-900 text-slate-400 border border-slate-800'"
        class="px-4 py-1.5 rounded-xl text-xs transition"
      >
        កំពុងធ្វើ ({{ tasks.length - completedCount }})
      </button>
      <button
        @click="activeFilter = 'completed'"
        :class="activeFilter === 'completed' ? 'bg-amber-400 text-slate-950 font-bold' : 'bg-slate-900 text-slate-400 border border-slate-800'"
        class="px-4 py-1.5 rounded-xl text-xs transition"
      >
        សម្រេចរួចរាល់ ({{ completedCount }})
      </button>
    </div>

    <!-- Task List -->
    <div v-if="isLoading" class="text-center py-12 text-slate-400 text-xs">
      <div class="w-8 h-8 border-4 border-amber-400 border-t-transparent rounded-full animate-spin mx-auto mb-3"></div>
      កំពុងទាញយកកិច្ចការ...
    </div>

    <div v-else-if="filteredTasks.length === 0" class="bg-slate-900/40 border border-slate-800/60 rounded-3xl p-8 text-center text-slate-500 text-xs font-khmer">
      <i class="fa-solid fa-clipboard-check text-3xl text-slate-600 mb-2 block"></i>
      គ្មានកិច្ចការនៅក្នុងបញ្ជីនេះទេ! តោះចាប់ផ្តើមបន្ថែមមួយឥឡូវនេះ។
    </div>

    <div v-else class="space-y-2.5">
      <div
        v-for="task in filteredTasks"
        :key="task.id"
        :class="task.is_completed ? 'bg-slate-900/40 border-slate-800/40 opacity-70' : 'bg-slate-900/90 border-slate-800'"
        class="border p-4 rounded-2xl flex items-center justify-between gap-3 transition shadow-md"
      >
        <div class="flex items-center gap-3 flex-1 min-w-0">
          <input
            type="checkbox"
            :checked="task.is_completed"
            @change="toggleTask(task)"
            class="w-5 h-5 rounded-lg accent-emerald-400 cursor-pointer shrink-0"
          />
          <div class="min-w-0">
            <p
              :class="task.is_completed ? 'line-through text-slate-500' : 'text-white'"
              class="text-xs font-medium truncate"
            >
              {{ task.title }}
            </p>
            <span class="inline-block px-2 py-0.5 mt-1 rounded-md text-[9px] bg-slate-800 text-amber-300/80 border border-slate-700 font-khmer">
              {{ task.subject }}
            </span>
          </div>
        </div>

        <div class="flex items-center gap-3 shrink-0">
          <span v-if="task.is_completed" class="text-[10px] text-emerald-400 font-bold flex items-center gap-1">
            +5 PTS 🪙
          </span>
          <button
            @click="deleteTask(task.id)"
            class="w-7 h-7 rounded-lg text-slate-500 hover:text-rose-400 hover:bg-slate-800 transition flex items-center justify-center text-xs"
            title="លុបកិច្ចការ"
          >
            <i class="fa-solid fa-trash-can"></i>
          </button>
        </div>
      </div>
    </div>

  </div>
</template>
