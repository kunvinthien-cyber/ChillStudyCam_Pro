<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'

defineProps({
  show: Boolean
})

const emit = defineEmits(['close'])
const router = useRouter()

const step = ref(1)
const selectedGrade = ref('')
const selectedStruggle = ref('')
const selectedHabit = ref('')

const isAnalyzing = ref(false)
const recommendation = ref(null)

const runAnalysis = () => {
  isAnalyzing.value = true
  setTimeout(() => {
    // ក្បួនវិភាគ និងណែនាំផ្លូវរៀន (Smart Recommendation Logic)
    if (selectedGrade.value === 'grade_12') {
      recommendation.value = {
        title: 'ផ្លូវឆ្ពោះទៅកាន់និទ្ទេស A បាក់ឌុប 🎓',
        diagnosis: `អ្នកកំពុងមានសម្ពាធលើមុខវិជ្ជា «${selectedStruggle.value}» សម្រាប់ត្រៀមប្រឡងបាក់ឌុបខាងមុខ។`,
        recommendedRoom: { id: 1, name: 'បន្ទប់បាក់ឌុប A+ (គណិត & រូបវិទ្យា)' },
        recommendedDoc: 'វិញ្ញាសាគណិតវិទ្យាប្រឡងបាក់ឌុប + អត្រាកំណែផ្លូវការ',
        studyTip: 'គួររៀន Pomodoro ២៥ នាទី x ៣ ជុំរាល់យប់ ហើយទន្ទេញសន្លឹកសង្ខេបរូបមន្តមុនចូលគេង។'
      }
    } else if (selectedGrade.value === 'language') {
      recommendation.value = {
        title: 'ផែនការដណ្តើមយក IELTS Band 7.0+ 🗣️',
        diagnosis: `អ្នកត្រូវការបង្កើនកម្រិតពាក្យគន្លឹះ និងការសរសេរ Essay ឱ្យត្រូវទម្រង់ស្តង់ដារ។`,
        recommendedRoom: { id: 3, name: 'IELTS 7.0+ Sanctuary' },
        recommendedDoc: 'IELTS Academic Writing Task 2 Templates (Band 7.5+)',
        studyTip: 'អនុវត្តសរសេរ Task 2 មួយថ្ងៃមួយប្រធានបទ និងចូលបន្ទប់ English-Only ជជែកជាមួយមិត្តភក្តិ។'
      }
    } else {
      recommendation.value = {
        title: 'ផែនការបង្កើនជំនាញបច្ចេកវិទ្យា & IT 💻',
        diagnosis: `អ្នកចង់ផ្តោតលើការសរសេរកូដ និងធ្វើ Project ឱ្យទាន់ទីផ្សារការងារ។`,
        recommendedRoom: { id: 2, name: 'Toul Kork Tech Hub (RUPP & ITC)' },
        recommendedDoc: 'Fullstack Web Development Roadmap & Git Cheatsheet',
        studyTip: 'សរសេរកូដ Coding ជាប់គ្នា ៥០ នាទី រួចពិភាក្សា Error ជាមួយមិត្តភក្តិក្នុង Voice Lounge។'
      }
    }
    isAnalyzing.value = false
    step.value = 4
  }, 1200)
}

const goToRoom = (roomId) => {
  emit('close')
  router.push(`/room/${roomId}`)
}

const resetQuiz = () => {
  step.value = 1
  selectedGrade.value = ''
  selectedStruggle.value = ''
  selectedHabit.value = ''
  recommendation.value = null
}
</script>

<template>
  <div v-if="show" class="fixed inset-0 z-50 bg-black/85 backdrop-blur-md flex items-center justify-center p-4">
    <div class="bg-slate-900 border border-slate-800 rounded-3xl p-6 sm:p-8 max-w-lg w-full shadow-2xl relative">
      <button @click="emit('close'); resetQuiz()" class="absolute top-5 right-5 text-slate-400 hover:text-white">✕</button>

      <!-- Step Header -->
      <div class="flex items-center gap-2 mb-4">
        <span class="w-8 h-8 rounded-xl bg-amber-400/20 text-amber-400 flex items-center justify-center text-sm font-bold">
          <i class="fa-solid fa-wand-magic-sparkles"></i>
        </span>
        <div>
          <h3 class="text-sm font-bold text-white">ប្រព័ន្ធវិភាគស្ទង់កម្រិត & ណែនាំផ្លូវរៀន</h3>
          <p class="text-[10px] text-slate-400 font-khmer">ឆ្លើយ ៣ សំណួរខ្លីៗ ដើម្បីឱ្យប្រព័ន្ធរៀបចំផែនការរៀនឱ្យអ្នក</p>
        </div>
      </div>

      <!-- STEP 1: កម្រិតថ្នាក់ -->
      <div v-if="step === 1" class="space-y-4">
        <label class="text-xs font-bold text-white block font-khmer">សំណួរទី ១៖ តើបច្ចុប្បន្នអ្នកកំពុងសិក្សានៅកម្រិតណា?</label>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
          <button
            @click="selectedGrade = 'grade_12'; step = 2"
            class="p-3.5 rounded-2xl bg-slate-950 border border-slate-800 hover:border-amber-400 text-left transition flex items-center gap-3"
          >
            <span class="text-xl">🎓</span>
            <div>
              <p class="text-xs font-bold text-white">ថ្នាក់ទី ១២ (បាក់ឌុប)</p>
              <p class="text-[10px] text-slate-400">ត្រៀមប្រឡងជាតិ</p>
            </div>
          </button>

          <button
            @click="selectedGrade = 'university'; step = 2"
            class="p-3.5 rounded-2xl bg-slate-950 border border-slate-800 hover:border-amber-400 text-left transition flex items-center gap-3"
          >
            <span class="text-xl">🏛️</span>
            <div>
              <p class="text-xs font-bold text-white">និស្សិតសាកលវិទ្យាល័យ</p>
              <p class="text-[10px] text-slate-400">IT, Business, វិស្វកម្ម...</p>
            </div>
          </button>

          <button
            @click="selectedGrade = 'language'; step = 2"
            class="p-3.5 rounded-2xl bg-slate-950 border border-slate-800 hover:border-amber-400 text-left transition flex items-center gap-3"
          >
            <span class="text-xl">🗣️</span>
            <div>
              <p class="text-xs font-bold text-white">ភាសាបរទេស / IELTS</p>
              <p class="text-[10px] text-slate-400">ត្រៀមយកអាហារូបករណ៍</p>
            </div>
          </button>

          <button
            @click="selectedGrade = 'high_school'; step = 2"
            class="p-3.5 rounded-2xl bg-slate-950 border border-slate-800 hover:border-amber-400 text-left transition flex items-center gap-3"
          >
            <span class="text-xl">📚</span>
            <div>
              <p class="text-xs font-bold text-white">វិទ្យាល័យ (ថ្នាក់ទី ១០-១១)</p>
              <p class="text-[10px] text-slate-400">ពង្រឹងមូលដ្ឋានគ្រឹះ</p>
            </div>
          </button>
        </div>
      </div>

      <!-- STEP 2: ចំណុចដែលបារម្ភជាងគេ -->
      <div v-else-if="step === 2" class="space-y-4">
        <label class="text-xs font-bold text-white block font-khmer">សំណួរទី ២៖ តើអ្នកកំពុងជួបការលំបាក ឬចង់ពង្រឹងលើអ្វីជាងគេ?</label>
        <div class="space-y-2">
          <button
            v-for="st in (selectedGrade === 'grade_12' ? ['លំហាត់គណិត (លីមីត & អាំងតេក្រាល)', 'រូបវិទ្យា & អគ្គិសនី', 'គីមីវិទ្យា & ជីវវិទ្យា', 'វិធីសរសេរតែងសេចក្តី'] : ['ការសរសេរកូដ & Algorithms', 'វេយ្យាករណ៍ និងការសរសេរ Essay IELTS', 'ការទន្ទេញរូបមន្ត និងពាក្យពិបាកៗ', 'កង្វះការផ្តោតអារម្មណ៍ (ងាយរំខាន)'])"
            :key="st"
            @click="selectedStruggle = st; step = 3"
            class="w-full p-3 rounded-2xl bg-slate-950 border border-slate-800 hover:border-amber-400 text-left text-xs text-white transition flex items-center justify-between"
          >
            <span>{{ st }}</span>
            <i class="fa-solid fa-chevron-right text-[10px] text-slate-500"></i>
          </button>
        </div>
      </div>

      <!-- STEP 3: ទម្លាប់នៃការរៀន -->
      <div v-else-if="step === 3" class="space-y-4">
        <label class="text-xs font-bold text-white block font-khmer">សំណួរទី ៣៖ តើអ្នកចូលចិត្តបរិយាកាសរៀនបែបណា?</label>
        <div class="space-y-2">
          <button
            v-for="hb in ['ស្ងាត់ជ្រងំ គ្មានអ្នកនិយាយរំខាន (Silent Focus)', 'មានសំឡេងភ្លៀង ឬសំឡេងហាងកាហ្វេ (Ambient)', 'ចូលចិត្តរៀនជជែកពិភាក្សាជាក្រុម (Voice Chat)']"
            :key="hb"
            @click="selectedHabit = hb; runAnalysis()"
            class="w-full p-3 rounded-2xl bg-slate-950 border border-slate-800 hover:border-amber-400 text-left text-xs text-white transition flex items-center justify-between"
          >
            <span>{{ hb }}</span>
            <i class="fa-solid fa-check text-xs text-amber-400"></i>
          </button>
        </div>

        <div v-if="isAnalyzing" class="text-center py-6 text-amber-400 text-xs">
          <i class="fa-solid fa-spinner fa-spin text-xl mb-2 block"></i>
          ChillAI កំពុងវិភាគ និងផ្គូផ្គងផ្លូវរៀនដែលល្អបំផុតសម្រាប់អ្នក...
        </div>
      </div>

      <!-- STEP 4: លទ្ធផលវិភាគ និងការណែនាំ (RESULTS) -->
      <div v-else-if="step === 4 && recommendation" class="space-y-4 animate-scale-up">
        <div class="p-4 rounded-2xl bg-gradient-to-r from-amber-500/10 to-slate-950 border border-amber-500/30">
          <span class="text-[10px] font-bold text-amber-400 uppercase tracking-wider block mb-1">
            ✓ ការវិភាគទទួលបានជោគជ័យ
          </span>
          <h4 class="text-sm font-bold text-white mb-2">{{ recommendation.title }}</h4>
          <p class="text-xs text-slate-300 font-khmer leading-relaxed">{{ recommendation.diagnosis }}</p>
        </div>

        <div class="space-y-2.5 text-xs">
          <div class="p-3 rounded-2xl bg-slate-950 border border-slate-800">
            <span class="text-[10px] text-slate-400 block mb-0.5">🏛️ បន្ទប់ដែលត្រូវនឹងអ្នកបំផុត៖</span>
            <span class="font-bold text-amber-400">{{ recommendation.recommendedRoom.name }}</span>
          </div>

          <div class="p-3 rounded-2xl bg-slate-950 border border-slate-800">
            <span class="text-[10px] text-slate-400 block mb-0.5">📄 ឯកសារដែលគួរទាញយកមើល៖</span>
            <span class="font-bold text-emerald-400">{{ recommendation.recommendedDoc }}</span>
          </div>

          <div class="p-3 rounded-2xl bg-slate-950 border border-slate-800 text-slate-300 font-khmer">
            <span class="text-[10px] text-slate-400 block mb-0.5">💡 គន្លឹះរៀនណែនាំ៖</span>
            {{ recommendation.studyTip }}
          </div>
        </div>

        <button
          @click="goToRoom(recommendation.recommendedRoom.id)"
          class="w-full py-3 rounded-2xl bg-amber-400 hover:bg-amber-300 text-slate-950 font-bold text-xs transition shadow-lg flex items-center justify-center gap-2"
        >
          <span>ចូលរៀនក្នុងបន្ទប់នេះភ្លាមៗ</span>
          <i class="fa-solid fa-arrow-right"></i>
        </button>
      </div>

    </div>
  </div>
</template>
