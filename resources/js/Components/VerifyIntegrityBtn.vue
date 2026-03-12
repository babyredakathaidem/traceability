<script setup>
import { ref } from 'vue'

const props = defineProps({
  eventId:     { type: Number, required: true },
  ipfsCid:     { type: String, default: null },
  shortCidFn:  { type: Function, default: (c) => c?.slice(0,8) + '…' + c?.slice(-6) },
})

// ── State machine per button ──────────────────────────────────────
// idle → loading → valid | valid_no_fabric | tampered_db |
//                          tampered_ipfs | ipfs_unavailable | error
const state   = ref('idle')
const details = ref(null)
const showTip = ref(false)

async function verify() {
  if (state.value === 'loading') return
  state.value   = 'loading'
  details.value = null
  showTip.value = false

  try {
    const res  = await fetch(`/verify/integrity/${props.eventId}`, {
      headers: {
        'Accept':           'application/json',
        'X-Requested-With': 'XMLHttpRequest',
      },
    })
    const data = await res.json()
    state.value   = data.verdict ?? 'error'
    details.value = data
    showTip.value = true
  } catch {
    state.value = 'error'
  }
}

// ── Button visual config per state ───────────────────────────────
const BTN = {
  idle: {
    cls:   'bg-green-500 text-black hover:bg-green-400 shadow-green-500/30',
    label: 'Xác thực',
    icon:  null,
  },
  loading: {
    cls:   'bg-green-500/40 text-black/50 cursor-not-allowed',
    label: 'Đang kiểm tra',
    icon:  'spinner',
  },
  valid: {
    cls:   'bg-green-500 text-black hover:bg-green-400 shadow-green-500/40',
    label: 'Toàn vẹn',
    icon:  'check',
  },
  valid_no_fabric: {
    cls:   'bg-amber-400 text-black hover:bg-amber-300 shadow-amber-500/30',
    label: 'Hợp lệ*',
    icon:  'check',
  },
  tampered_db: {
    cls:   'bg-red-600 text-white hover:bg-red-500 shadow-red-500/40 animate-pulse',
    label: 'Bị sửa!',
    icon:  'warn',
  },
  tampered_ipfs: {
    cls:   'bg-red-600 text-white hover:bg-red-500 shadow-red-500/40 animate-pulse',
    label: 'Bị sửa!',
    icon:  'warn',
  },
  ipfs_unavailable: {
    cls:   'bg-white/10 text-white/70 border border-white/20 hover:bg-white/15',
    label: 'Thử lại',
    icon:  'warn-amber',
  },
  error: {
    cls:   'bg-white/10 text-white/70 border border-white/20 hover:bg-white/15',
    label: 'Thử lại',
    icon:  'warn-amber',
  },
}

// ── Tooltip color per state ───────────────────────────────────────
const TIP_CLS = {
  valid:            'border-green-500/30 bg-[#080f08]/95',
  valid_no_fabric:  'border-amber-500/30 bg-[#0f0d00]/95',
  tampered_db:      'border-red-500/40   bg-[#1a0505]/95',
  tampered_ipfs:    'border-red-500/40   bg-[#1a0505]/95',
  ipfs_unavailable: 'border-white/15     bg-black/90',
  error:            'border-white/15     bg-black/90',
}
</script>

<template>
  <div class="relative inline-flex items-center gap-3">

    <!-- IPFS CID label -->
    <div v-if="ipfsCid" class="flex flex-col items-end">
      <span class="text-[8px] text-white/20 uppercase font-black leading-none">IPFS</span>
      <code class="text-[9px] font-mono text-green-400/60 font-bold">
        #{{ shortCidFn(ipfsCid) }}
      </code>
    </div>

    <!-- Button -->
    <button
      @click="verify"
      :disabled="state === 'loading'"
      :class="[
        'inline-flex items-center gap-1.5 text-[10px] font-black px-3 py-1.5 rounded-xl',
        'shadow-lg active:scale-95 transition-all uppercase tracking-tighter select-none',
        BTN[state]?.cls ?? BTN.idle.cls,
      ]"
    >
      <!-- Spinner -->
      <svg v-if="BTN[state]?.icon === 'spinner'"
        class="w-3 h-3 animate-spin shrink-0" viewBox="0 0 24 24" fill="none">
        <circle class="opacity-25" cx="12" cy="12" r="10"
          stroke="currentColor" stroke-width="4"/>
        <path class="opacity-75" fill="currentColor"
          d="M4 12a8 8 0 018-8v4l3-3-3-3v4a8 8 0 00-8 8h4z"/>
      </svg>

      <!-- Checkmark -->
      <svg v-else-if="BTN[state]?.icon === 'check'"
        class="w-3.5 h-3.5 shrink-0" viewBox="0 0 20 20" fill="currentColor">
        <path fill-rule="evenodd"
          d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
          clip-rule="evenodd"/>
      </svg>

      <!-- Warning red -->
      <svg v-else-if="BTN[state]?.icon === 'warn'"
        class="w-3.5 h-3.5 shrink-0" viewBox="0 0 20 20" fill="currentColor">
        <path fill-rule="evenodd"
          d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z"
          clip-rule="evenodd"/>
      </svg>

      <!-- Warning amber -->
      <svg v-else-if="BTN[state]?.icon === 'warn-amber'"
        class="w-3.5 h-3.5 shrink-0 text-amber-400" viewBox="0 0 20 20" fill="currentColor">
        <path fill-rule="evenodd"
          d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z"
          clip-rule="evenodd"/>
      </svg>

      {{ BTN[state]?.label ?? 'Xác thực' }}
    </button>

    <!-- Tooltip dropdown (xuất hiện sau khi verify xong) -->
    <transition
      enter-active-class="transition-all duration-200 ease-out"
      enter-from-class="opacity-0 scale-95 -translate-y-1"
      enter-to-class="opacity-100 scale-100 translate-y-0"
      leave-active-class="transition-all duration-150 ease-in"
      leave-from-class="opacity-100 scale-100 translate-y-0"
      leave-to-class="opacity-0 scale-95 -translate-y-1"
    >
      <div
        v-if="showTip && details && state !== 'loading'"
        class="absolute right-0 top-full mt-2 z-50 w-64 rounded-2xl border p-3 shadow-2xl backdrop-blur-md"
        :class="TIP_CLS[state] ?? 'border-white/15 bg-black/90'"
      >
        <!-- Close -->
        <button
          @click.stop="showTip = false"
          class="absolute top-2 right-2 text-white/20 hover:text-white/60 text-xs leading-none transition"
        >✕</button>

        <!-- 3 Layer rows -->
        <div class="space-y-2 text-[9px] font-mono pr-4">

          <!-- Layer 1: Fabric -->
          <div class="flex items-center gap-2">
            <span class="text-base leading-none shrink-0">
              {{ details.fabric?.found ? '✅' : details.fabric?.mock ? '🔶' : '⚠️' }}
            </span>
            <div class="flex-1 min-w-0">
              <div class="text-white/60 font-black text-[8px] uppercase tracking-wider">Hyperledger Fabric</div>
              <div :class="details.fabric?.found ? 'text-purple-300/70' : 'text-amber-300/60'">
                {{ details.fabric?.found ? 'Record found on ledger' : details.fabric?.mock ? 'Mock mode — no real Fabric' : 'Record not found' }}
              </div>
            </div>
          </div>

          <!-- Layer 2: IPFS -->
          <div class="flex items-center gap-2">
            <span class="text-base leading-none shrink-0">
              {{ details.ipfs?.valid ? '✅' : details.ipfs?.fetched === false ? '⚠️' : '❌' }}
            </span>
            <div class="flex-1 min-w-0">
              <div class="text-white/60 font-black text-[8px] uppercase tracking-wider">IPFS Content</div>
              <div :class="details.ipfs?.valid ? 'text-green-300/70' : 'text-red-300/70'">
                {{ details.ipfs?.valid ? 'Hash khớp ✓' : details.ipfs?.fetched === false ? 'Không kết nối được' : 'Hash KHÔNG khớp ✗' }}
              </div>
              <div v-if="details.ipfs?.cid" class="text-white/20 truncate">#{{ details.ipfs.cid.slice(0,12) }}…</div>
            </div>
          </div>

          <!-- Layer 3: DB -->
          <div class="flex items-center gap-2">
            <span class="text-base leading-none shrink-0">
              {{ details.db?.valid ? '✅' : '❌' }}
            </span>
            <div class="flex-1 min-w-0">
              <div class="text-white/60 font-black text-[8px] uppercase tracking-wider">Dữ liệu hiển thị</div>
              <div :class="details.db?.valid ? 'text-green-300/70' : 'text-red-300/70'">
                {{ details.db?.valid ? 'Khớp với bản gốc ✓' : 'LỆCH — có thể bị sửa ✗' }}
              </div>
            </div>
          </div>
        </div>

        <!-- Ground truth source -->
        <div class="mt-2.5 pt-2 border-t border-white/8 text-[8px] text-white/20">
          So sánh với:
          <span :class="details.ground_truth_source === 'fabric' ? 'text-purple-300/50' : 'text-amber-300/50'">
            {{ details.ground_truth_source === 'fabric' ? 'Hyperledger Fabric' : 'DB.content_hash (fallback)' }}
          </span>
        </div>
      </div>
    </transition>

  </div>
</template>