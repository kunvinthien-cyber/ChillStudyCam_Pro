<script setup>
import { ref, onMounted } from 'vue'
import apiClient from '../services/api'
import { useUser } from '../composables/useUser'
import StudyDiagnosticModal from '../components/StudyDiagnosticModal.vue'

const { user } = useUser()

const documents = ref([])
const isLoading = ref(true)
const selectedGrade = ref('all')
const showDiagnosticModal = ref(false)
const downloadingId = ref(null)

const gradeFilters = [
  { id: 'all', name: 'ទាំងអស់' },
  { id: 'grade_12', name: 'ថ្នាក់ទី ១២ (បាក់ឌុប)' },
  { id: 'university', name: 'និស្សិតសាកលវិទ្យាល័យ' },
  { id: 'language', name: 'ភាសាបរទេស / IELTS' },
]

const fetchDocuments = async () => {
  try {
    isLoading.value = true
    let url = '/documents'
    const params = []
    if (selectedGrade.value !== 'all') params.push(`grade_level=${selectedGrade.value}`)
    if (params.length) url += `?${params.join('&')}`

    const res = await apiClient.get(url)
    documents.value = res.data
  } catch (err) {
    console.error('Fetch docs error:', err)
  } finally {
    isLoading.value = false
  }
}

const handleDownload = async (doc) => {
  if (doc.pts_cost > 0 && user.value.coins < doc.pts_cost) {
    alert(`អ្នកត្រូវការកាក់ ${doc.pts_cost} PTS ដើម្បីដោះសោរឯកសារនេះ! (អ្នកមានត្រឹមតែ ${user.value.coins} PTS)`)
    return
  }

  try {
    downloadingId.value = doc.id
    const res = await apiClient.post(`/documents/${doc.id}/download`)

    // កាត់កាក់បើជាឯកសារគិត PTS
    user.value.coins = res.data.remaining_coins

    doc.downloads_count++
    alert(res.data.message)
    // បើក File PDF ក្នុង Tab ថ្មី
    window.open(res.data.file_url, '_blank')
  } catch (err) {
    alert(err.response?.data?.message || 'មិនអាចទាញយកឯកសារបានទេ!')
  } finally {
    downloadingId.value = null
  }
}

onMounted(() => {
  fetchDocuments()
})
</script>

<template>
  <div class="space-y-6 max-w-5xl mx-auto pb-10">

    <!-- Top Hero Banner with Diagnostic Trigger -->
    <div class="flex flex-wrap items-center justify-between gap-4 bg-gradient-to-r from-amber-500/10 via-slate-900 to-slate-900 border border-amber-500/20 p-6 sm:p-8 rounded-3xl shadow-xl">
      <div class="max-w-xl">
        <span class="text-[10px] uppercase font-bold tracking-wider text-amber-400 bg-amber-400/10 px-3 py-1 rounded-full border border-amber-400/20">
          📚 ChillLibrary • បណ្ណាល័យឌីជីថល
        </span>
        <h1 class="text-2xl sm:text-3xl font-black text-white mt-2">បណ្តុំវិញ្ញាសា និងសន្លឹកសង្ខេបមេរៀន</h1>
        <p class="text-xs text-slate-400 font-khmer mt-1 leading-relaxed">
          ទាញយកវិញ្ញាសាបាក់ឌុបឆ្នាំចាស់ៗ អត្រាកំណែផ្លូវការ គំរូ IELTS និងសន្លឹកសង្ខេបកូដ Programming ដោយឥតគិតថ្លៃ!
        </p>
      </div>

      <!-- ប៊ូតុងចុចស្ទង់កម្រិត & វិភាគផ្លូវរៀន -->
      <button
        @click="showDiagnosticModal = true"
        class="px-5 py-3 rounded-2xl bg-gradient-to-r from-amber-400 to-amber-500 text-slate-950 font-bold text-xs transition shadow-lg hover:scale-105 active:scale-95 flex items-center gap-2"
      >
        <i class="fa-solid fa-wand-magic-sparkles"></i>
        <span>មិនដឹងរៀនអ្វី? ចុចស្ទង់កម្រិត</span>
      </button>
    </div>

    <!-- Filter Pills -->
    <div class="flex items-center gap-2 overflow-x-auto pb-2 scrollbar-none">
      <button
        v-for="g in gradeFilters"
        :key="g.id"
        @click="selectedGrade = g.id; fetchDocuments()"
        :class="selectedGrade === g.id ? 'bg-amber-400 text-slate-950 font-bold shadow' : 'bg-slate-900/90 text-slate-400 hover:text-white border border-slate-800'"
        class="px-4 py-2 rounded-2xl text-xs shrink-0 transition"
      >
        {{ g.name }}
      </button>
    </div>

    <!-- Loading State -->
    <div v-if="isLoading" class="text-center py-20 text-slate-400 text-xs">
      <div class="w-8 h-8 border-4 border-amber-400 border-t-transparent rounded-full animate-spin mx-auto mb-3"></div>
      កំពុងទាញយកឯកសារ...
    </div>

    <!-- Document Cards Grid -->
    <div v-else class="grid grid-cols-1 md:grid-cols-2 gap-4">
      <div
        v-for="doc in documents"
        :key="doc.id"
        class="bg-slate-900/90 border border-slate-800 hover:border-slate-700/80 p-5 rounded-3xl transition duration-300 flex flex-col justify-between shadow-lg group"
      >
        <div>
          <!-- Tags -->
          <div class="flex items-center justify-between gap-2 mb-3">
            <span class="text-[10px] px-2.5 py-0.5 rounded-full bg-slate-800 text-amber-300 border border-slate-700 font-khmer">
              {{ doc.subject }}
            </span>

            <span v-if="doc.pts_cost === 0" class="text-[10px] font-bold text-emerald-400 flex items-center gap-1">
              <i class="fa-solid fa-gift"></i> ឥតគិតថ្លៃ (Free)
            </span>
            <span v-else class="text-[10px] font-bold text-amber-400 font-mono flex items-center gap-1">
              <span>🪙</span> {{ doc.pts_cost }} PTS ដោះសោ
            </span>
          </div>

          <h3 class="text-sm font-bold text-white group-hover:text-amber-300 transition mb-1.5 leading-snug">
            {{ doc.title }}
          </h3>

          <p class="text-xs text-slate-400 font-khmer line-clamp-2 leading-relaxed mb-4">
            {{ doc.description }}
          </p>
        </div>

        <div class="flex items-center justify-between pt-4 border-t border-slate-800/80 text-xs">
          <span class="text-[11px] text-slate-500 font-mono flex items-center gap-1.5">
            <i class="fa-solid fa-download text-[10px]"></i> {{ doc.downloads_count }} ទាញយក
          </span>

          <button
            @click="handleDownload(doc)"
            :disabled="downloadingId === doc.id"
            class="px-4 py-2 rounded-xl bg-slate-800 hover:bg-amber-400 hover:text-slate-950 text-slate-200 font-semibold transition flex items-center gap-1.5 disabled:opacity-50 text-xs"
          >
            <i v-if="downloadingId === doc.id" class="fa-solid fa-spinner fa-spin text-xs"></i>
            <i v-else class="fa-solid fa-file-pdf text-rose-400 group-hover:text-slate-950 text-xs"></i>
            <span>{{ doc.pts_cost > 0 ? 'ដោះសោ & មើល PDF' : 'ទាញយក / មើល PDF' }}</span>
          </button>
        </div>
      </div>
    </div>

    <!-- Diagnostic Quiz Modal -->
    <StudyDiagnosticModal :show="showDiagnosticModal" @close="showDiagnosticModal = false" />

  </div>
</template>
