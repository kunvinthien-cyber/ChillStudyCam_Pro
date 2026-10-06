<script setup>
defineProps({
  show: Boolean,
  amount: [Number, String],
  productName: String,
  orderType: String,
  orderId: [Number, String]
})

const emit = defineEmits(['close'])
</script>

<template>
  <div
    v-if="show"
    class="fixed inset-0 z-50 bg-black/85 backdrop-blur-md flex items-center justify-center p-4"
    role="dialog"
    aria-modal="true"
    aria-labelledby="khqr-demo-title"
  >
    <div class="bg-white text-slate-900 rounded-3xl max-w-xs w-full shadow-2xl overflow-hidden relative">
      <div class="bg-[#E1251B] p-4 text-white text-center relative">
        <button
          @click="emit('close')"
          class="absolute top-3 right-3 w-7 h-7 rounded-full bg-black/20 text-white flex items-center justify-center text-xs"
          aria-label="បិទ"
        >
          ✕
        </button>
        <span class="text-base font-black tracking-wider">KHQR • DEMO</span>
        <p class="text-[10px] text-white/90 mt-1">គំរូបង្ហាញប៉ុណ្ណោះ</p>
      </div>

      <div class="p-5 text-center bg-white space-y-4">
        <div>
          <h2 id="khqr-demo-title" class="text-xs font-bold text-slate-800">CHILLSTUDY STORE</h2>
          <p v-if="orderId" class="text-[10px] text-emerald-700 font-bold mt-1">Order #{{ orderId }} · Pending payment</p>
          <p class="text-[10px] text-slate-500 font-khmer mt-1">{{ productName }}</p>
          <p class="text-[10px] text-slate-500 font-khmer mt-1">
            {{ orderType === 'delivery' ? 'ដឹកជញ្ជូន' : 'ទៅយកផ្ទាល់' }}
          </p>
          <div class="text-2xl font-black text-[#E1251B] font-mono mt-2">
            ${{ Number(amount).toFixed(2) }}
          </div>
        </div>

        <div class="rounded-2xl border border-amber-300 bg-amber-50 p-4 text-left">
          <div class="flex items-center gap-2 text-amber-800 font-bold text-xs">
            <i class="fa-solid fa-triangle-exclamation"></i>
            <span>មិនមែនការទូទាត់ពិតទេ</span>
          </div>
          <p class="text-[11px] leading-relaxed text-amber-900 mt-2">
            No live Bakong QR is configured. This order is pending payment; no cash payment was processed.
          </p>
        </div>

        <button
          @click="emit('close')"
          class="w-full py-2.5 rounded-xl bg-slate-900 hover:bg-slate-800 text-white font-bold text-xs transition"
        >
          បិទ
        </button>
      </div>
    </div>
  </div>
</template>
