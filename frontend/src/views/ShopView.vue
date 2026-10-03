<script setup>
import { ref, computed, onMounted } from 'vue'
import apiClient from '../services/api'
import { useUser } from '../composables/useUser'
import BakongKhqrModal from '../components/BakongKhqrModal.vue'

const showBakongModal = ref(false)
const { user } = useUser()

const products = ref([])
const isLoading = ref(true)
const selectedProduct = ref(null)
const fulfillmentType = ref('pickup')
const usePts = ref(true)
const deliveryAddress = ref('')
const phoneNumber = ref('')

const fetchProducts = async () => {
  try {
    isLoading.value = true
    const res = await apiClient.get('/products')
    products.value = res.data
  } catch (err) {
    console.error('Fetch products error:', err)
  } finally {
    isLoading.value = false
  }
}

const deliveryFee = computed(() => fulfillmentType.value === 'delivery' ? 0.80 : 0.00)

const finalPrice = computed(() => {
  if (!selectedProduct.value) return 0
  let total = Number(selectedProduct.value.price)
  if (usePts.value && user.value.coins >= selectedProduct.value.pts_price) {
    total -= Number(selectedProduct.value.pts_discount)
  }
  total += deliveryFee.value
  return Math.max(0, total).toFixed(2)
})

const openCheckout = (product) => {
  selectedProduct.value = product
  fulfillmentType.value = 'pickup'
}

const confirmOrder = () => {
  if (fulfillmentType.value === 'delivery' && (!deliveryAddress.value || !phoneNumber.value)) {
    alert('សូមបំពេញអាសយដ្ឋាន និងលេខទូរស័ព្ទ!')
    return
  }
  // This preview is not connected to Bakong and must not create a paid order.
  showBakongModal.value = true
}

onMounted(() => {
  fetchProducts()
})
// ក្នុង array products ឬពេលទាញចេញពី backend យើងអាចបន្ថែមព័ត៌មានទីតាំង៖
const getShopLocation = (partnerShop) => {
  const shopLocations = {
    'Tube Cafe (សាខា RUPP)': {
      query: 'Tube Cafe RUPP Phnom Penh',
      landmark: '៣០០ម ពីខ្លោងទ្វារធំ RUPP (តាមបណ្តោយមហាវិថីសហព័ន្ធរុស្ស៊ី)',
      hours: '7:00 AM - 8:30 PM'
    },
    'Brown Coffee (សាខា IFL)': {
      query: 'Brown Coffee IFL Phnom Penh',
      landmark: 'ទល់មុខវិទ្យាស្ថានភាសាបរទេស (IFL)',
      hours: '6:30 AM - 9:00 PM'
    },
    'Bakery Corner (ក្បែរ ITC)': {
      query: 'Institute of Technology of Cambodia',
      landmark: 'ក្បែរខ្លោងទ្វារក្រោយវិទ្យាស្ថានតិចណូ (ITC)',
      hours: '7:00 AM - 7:00 PM'
    }
  }

  return shopLocations[partnerShop] || {
    query: partnerShop + ' Phnom Penh',
    landmark: 'ក្បែរតំបន់សាកលវិទ្យាល័យ',
    hours: '7:00 AM - 8:00 PM'
  }
}
</script>

<template>
  <div class="space-y-6">
    <!-- Top Header -->
    <div class="flex flex-wrap items-center justify-between gap-4 bg-linear-to-r from-amber-500/10 via-slate-900 to-slate-900 border border-amber-500/20 p-6 rounded-3xl">
      <div>
        <span class="text-[10px] uppercase font-bold tracking-wider text-amber-400 bg-amber-400/10 px-3 py-1 rounded-full border border-amber-400/20">
          🛒 ChillShop & Rewards
        </span>
        <h1 class="text-2xl font-black text-white mt-2">ហាងទំនិញ និងរង្វាន់សិស្សានុសិស្ស</h1>
        <p class="text-xs text-slate-400 font-khmer mt-1">ប្រើពិន្ទុ PTS ដែលខំរៀនបាន ដើម្បីប្តូរយកការបញ្ចុះតម្លៃលើកាហ្វេ និងអាហារសម្រន់!</p>
      </div>
      <div class="bg-slate-950/80 border border-slate-800 p-3 px-5 rounded-2xl flex items-center gap-3">
        <span class="text-2xl">🪙</span>
        <div>
          <span class="text-[10px] text-slate-400 uppercase font-semibold">ពិន្ទុរបស់អ្នក</span>
          <p class="text-base font-bold text-amber-400">{{ user.coins }} PTS</p>
        </div>
      </div>
    </div>

    <!-- Loading -->
    <div v-if="isLoading" class="text-center py-16 text-slate-400 text-xs">
      <div class="w-8 h-8 border-4 border-amber-400 border-t-transparent rounded-full animate-spin mx-auto mb-3"></div>
      កំពុងទាញយកទំនិញពី Database...
    </div>

    <!-- Product Grid -->
    <div v-else class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
      <div
        v-for="product in products"
        :key="product.id"
        class="bg-slate-900/90 border border-slate-800 rounded-3xl overflow-hidden hover:border-slate-700 transition duration-300 flex flex-col justify-between shadow-lg"
      >
        <div>
          <div class="relative h-44 w-full overflow-hidden bg-slate-950">
            <img :src="product.image" :alt="product.name" class="w-full h-full object-cover hover:scale-105 transition duration-500" />
            <span class="absolute top-3 left-3 px-2.5 py-1 rounded-full text-[10px] font-semibold bg-black/60 backdrop-blur-md text-amber-300 border border-amber-500/20">
              {{ product.category }}
            </span>
          </div>

          <div class="p-4">
            <h3 class="text-sm font-bold text-white mb-1">{{ product.name }}</h3>
            <p class="text-[11px] text-slate-400 mb-3 flex items-center gap-1.5">
              <i class="fa-solid fa-store text-amber-400/80 text-[10px]"></i> {{ product.partner_shop }}
            </p>

            <div class="flex items-center gap-2 text-xs bg-slate-950/60 p-2 rounded-xl border border-slate-800/80 mb-2">
              <span class="text-slate-400">តម្លៃដើម៖ <strong class="text-white">${{ Number(product.price).toFixed(2) }}</strong></span>
              <span class="text-emerald-400 font-medium">| បញ្ចុះ -${{ Number(product.pts_discount).toFixed(2) }}</span>
            </div>
            <p class="text-[10px] text-amber-400 font-khmer">⚡ ប្រើត្រឹមតែ {{ product.pts_price }} PTS ប៉ុណ្ណោះ</p>
          </div>
        </div>

        <div class="p-4 pt-0">
          <button
            @click="openCheckout(product)"
            class="w-full py-2.5 rounded-xl bg-amber-400 hover:bg-amber-300 text-slate-950 font-bold text-xs transition shadow-md flex items-center justify-center gap-2"
          >
            <i class="fa-solid fa-bag-shopping"></i> កុម្ម៉ង់ទិញឥឡូវនេះ
          </button>
        </div>
      </div>
    </div>

    <!-- Checkout Modal -->
    <div v-if="selectedProduct" class="fixed inset-0 z-50 bg-black/80 backdrop-blur-sm flex items-center justify-center p-4">
      <div class="bg-slate-900 border border-slate-800 rounded-3xl p-6 max-w-md w-full shadow-2xl relative">
        <div class="flex items-center justify-between pb-3 border-b border-slate-800 mb-4">
          <h3 class="text-sm font-bold text-white flex items-center gap-2">
            <i class="fa-solid fa-cart-shopping text-amber-400"></i> បញ្ជាក់ការកុម្ម៉ង់
          </h3>
          <button @click="selectedProduct = null" class="text-slate-400 hover:text-white">✕</button>
        </div>

        <!-- Toggle Pickup/Delivery -->
        <div class="mb-4">
          <label class="text-[11px] text-slate-400 block mb-2 font-khmer">វិធីសាស្ត្រទទួលទំនិញ៖</label>
          <div class="grid grid-cols-2 gap-2 p-1 bg-slate-950 rounded-2xl border border-slate-800">
            <button
              @click="fulfillmentType = 'pickup'"
              :class="fulfillmentType === 'pickup' ? 'bg-amber-400 text-slate-950 font-bold' : 'text-slate-400 hover:text-white'"
              class="py-2 rounded-xl text-xs flex items-center justify-center gap-1.5 transition"
            >
              <i class="fa-solid fa-store"></i> ទៅយកផ្ទាល់ ($0)
            </button>
            <button
              @click="fulfillmentType = 'delivery'"
              :class="fulfillmentType === 'delivery' ? 'bg-amber-400 text-slate-950 font-bold' : 'text-slate-400 hover:text-white'"
              class="py-2 rounded-xl text-xs flex items-center justify-center gap-1.5 transition"
            >
              <i class="fa-solid fa-motorcycle"></i> ដឹកដល់កន្លែង (+$0.80)
            </button>
          </div>
        </div>

        <div v-if="fulfillmentType === 'delivery'" class="space-y-2.5 mb-4 p-3 rounded-2xl bg-slate-950/60 border border-slate-800 text-xs">
          <input
            v-model="deliveryAddress"
            type="text"
            placeholder="អាសយដ្ឋានដឹកជញ្ជូន..."
            class="w-full bg-slate-900 border border-slate-700 rounded-xl p-2 text-white text-xs focus:outline-none focus:border-amber-400"
          />
          <input
            v-model="phoneNumber"
            type="tel"
            placeholder="លេខទូរស័ព្ទ..."
            class="w-full bg-slate-900 border border-slate-700 rounded-xl p-2 text-white text-xs focus:outline-none focus:border-amber-400"
          />
        </div>

      <!-- ============================================== -->
<!-- 🗺️ ផែនទី និងទីតាំងហាង (បង្ហាញពេលរើស Pickup)      -->
<!-- ============================================== -->
<div v-else class="mb-4 space-y-3">

  <!-- កាតព័ត៌មានហាង -->
  <div class="p-3.5 rounded-2xl bg-slate-950 border border-slate-800 flex items-start justify-between gap-3">
    <div>
      <span class="text-[10px] font-bold text-amber-400 uppercase tracking-wider block mb-1">
        <i class="fa-solid fa-location-dot"></i> ទីតាំងទៅទទួលទំនិញ
      </span>
      <h4 class="text-xs font-bold text-white mb-1">{{ selectedProduct.partner_shop }}</h4>
      <p class="text-[11px] text-slate-400 font-khmer leading-relaxed">
        {{ getShopLocation(selectedProduct.partner_shop).landmark }}
      </p>
      <p class="text-[10px] text-emerald-400 mt-1.5 flex items-center gap-1 font-medium">
        <i class="fa-regular fa-clock text-[9px]"></i> បើកលក់៖ {{ getShopLocation(selectedProduct.partner_shop).hours }}
      </p>
    </div>

    <!-- ប៊ូតុងបើក Google Maps ផ្ទាល់ -->
    <a
      :href="`https://www.google.com/maps/search/?api=1&query=${encodeURIComponent(getShopLocation(selectedProduct.partner_shop).query)}`"
      target="_blank"
      class="px-3 py-2 rounded-xl bg-amber-400/10 text-amber-400 border border-amber-400/20 hover:bg-amber-400 hover:text-slate-950 transition text-[11px] font-bold flex items-center gap-1.5 shrink-0"
    >
      <i class="fa-solid fa-diamond-turn-right text-xs"></i>
      <span>មើលផ្លូវ</span>
    </a>
  </div>

  <!-- ផែនទី Interactive Map Embed (Google Maps) -->
  <div class="relative w-full h-36 rounded-2xl overflow-hidden border border-slate-700/60 shadow-inner bg-slate-950">
    <iframe
      class="w-full h-full border-0 filter invert-[0.88] hue-rotate-180 brightness-90 contrast-125"
      loading="lazy"
      allowfullscreen
      referrerpolicy="no-referrer-when-downgrade"
      :src="`https://maps.google.com/maps?q=${encodeURIComponent(getShopLocation(selectedProduct.partner_shop).query)}&t=&z=16&ie=UTF8&iwloc=&output=embed`"
    ></iframe>
    <div class="absolute bottom-2 left-2 px-2 py-0.5 rounded-md bg-slate-900/90 text-[9px] text-slate-400 border border-slate-700 pointer-events-none">
      📍 ទីតាំងជាក់ស្តែង
    </div>
  </div>

</div>

        <!-- Use PTS -->
        <div class="flex items-center justify-between p-3 rounded-2xl bg-slate-950 border border-slate-800 mb-4 text-xs">
          <div class="flex items-center gap-2">
            <input type="checkbox" v-model="usePts" id="ptsToggle" class="accent-amber-400 w-4 h-4 cursor-pointer" />
            <label for="ptsToggle" class="text-slate-300 cursor-pointer font-khmer">
              ប្រើ {{ selectedProduct.pts_price }} PTS (បញ្ចុះ -${{ Number(selectedProduct.pts_discount).toFixed(2) }})
            </label>
          </div>
          <span class="text-amber-400 font-bold">🪙 សល់ {{ user.coins }}</span>
        </div>

        <!-- Summary -->
        <div class="space-y-1.5 text-xs text-slate-400 border-t border-slate-800 pt-3 mb-4 font-khmer">
          <div class="flex justify-between">
            <span>តម្លៃដើម៖</span>
            <span class="text-white">${{ Number(selectedProduct.price).toFixed(2) }}</span>
          </div>
          <div v-if="usePts" class="flex justify-between text-emerald-400">
            <span>បញ្ចុះតម្លៃ PTS៖</span>
            <span>-${{ Number(selectedProduct.pts_discount).toFixed(2) }}</span>
          </div>
          <div class="flex justify-between">
            <span>ថ្លៃដឹកជញ្ជូន៖</span>
            <span class="text-white">${{ deliveryFee.toFixed(2) }}</span>
          </div>
          <div class="flex justify-between text-sm font-bold text-white pt-2 border-t border-slate-800">
            <span>សរុបត្រូវបង់៖</span>
            <span class="text-amber-400">${{ finalPrice }}</span>
          </div>
        </div>

        <button
          @click="confirmOrder"
          class="w-full py-3 rounded-xl bg-amber-400 hover:bg-amber-300 text-slate-950 font-bold text-xs transition shadow-lg flex items-center justify-center gap-2"
        >
          <i class="fa-solid fa-circle-info"></i>
          <span>មើល KHQR Demo (${{ finalPrice }})</span>
        </button>
        <p class="text-[10px] text-amber-300 text-center mt-2">
          Demo ប៉ុណ្ណោះ៖ មិនអាចទូទាត់ ឬកត់ត្រា Order ពិតបានទេ។
        </p>
      </div>
    </div>

<BakongKhqrModal
  :show="showBakongModal"
  :amount="finalPrice"
  :product-name="selectedProduct?.name"
  :order-type="fulfillmentType"
  @close="showBakongModal = false"
/>
  </div>
</template>
