<script setup>
import { computed, ref } from 'vue'
import { Head } from '@inertiajs/vue3'
import VerifyIntegrityBtn from '@/Components/VerifyIntegrityBtn.vue'

// ── Props từ LineageService::formatEventForPublic() + formatBatch() ──
const props = defineProps({
  mode:       String,   // 'public' | 'private'
  batch:      Object,
  events:     Array,
  place_name: String,
  expires_at: String,
})

// ── UI state ──────────────────────────────────────────────────────
const lightboxUrl       = ref(null)
const lineageExpanded   = ref(false)
const certSectionExpanded = ref(true)

// ── SEO ───────────────────────────────────────────────────────────
const pageTitle = computed(() =>
  props.batch?.product?.name
    ? `${props.batch.product.name} — Truy xuất nguồn gốc`
    : 'Truy xuất nguồn gốc'
)
const pageDesc = computed(() =>
  `Thông tin truy xuất nguồn gốc lô ${props.batch?.code ?? ''} — ${props.batch?.enterprise?.name ?? ''}`
)
const ogImage = computed(() => {
  const p = props.batch?.product?.image_path
  if (!p) return null
  return p.startsWith('http') ? p : `/storage/${p}`
})

// ── CTE icon & category mapping ───────────────────────────────────
const CTE_ICON = {
  planting: '🌱', growing: '🌿', spraying: '💦',
  harvesting: '🌾', harvest: '🌾',
  processing: '⚙️', milling: '⚙️', packaging: '📦', split: '✂️', merge: '🔀',
  storage: '🏭', warehousing: '🏭', inspection: '🔍', quality_check: '🔬',
  shipping: '🚚', transport: '🚚', distribution: '🏪',
  transfer_out: '📤', transfer_in: '📥', transfer: '🔄',
  recall: '🚨', custom: '📋',
}

const CTE_CATEGORY = {
  planting: 'observation', growing: 'observation', spraying: 'observation',
  storage: 'observation', inspection: 'observation', quality_check: 'observation', warehousing: 'observation',
  harvesting: 'transformation', harvest: 'transformation', milling: 'transformation',
  processing: 'transformation', packaging: 'transformation', split: 'transformation', merge: 'transformation',
  transfer_out: 'transfer', transfer_in: 'transfer', transfer: 'transfer',
}

function cteIcon(code) { return CTE_ICON[code] ?? '📋' }
function cteCategory(ev) {
  return ev.event_category ?? CTE_CATEGORY[ev.cte_code] ?? 'observation'
}

const CATEGORY_STYLE = {
  observation:    { label: 'QUAN SÁT', border: 'border-sky-500/20',    bg: 'bg-sky-500/5',    dot: 'bg-sky-400',    text: 'text-sky-300',    badge: 'border-sky-500/30 bg-sky-500/10 text-sky-300' },
  transformation: { label: 'BIẾN ĐỔI', border: 'border-orange-500/25', bg: 'bg-orange-500/5', dot: 'bg-orange-400', text: 'text-orange-300', badge: 'border-orange-500/30 bg-orange-500/10 text-orange-300' },
  transfer:       { label: 'CHUYỂN GIAO', border: 'border-purple-500/30', bg: 'bg-purple-500/5', dot: 'bg-purple-400', text: 'text-purple-300', badge: 'border-purple-500/30 bg-purple-500/10 text-purple-300' },
}

function catStyle(ev) {
  return CATEGORY_STYLE[cteCategory(ev)] ?? CATEGORY_STYLE.observation
}

// ── Enterprise colors (max 6) ─────────────────────────────────────
const ENTERPRISE_COLORS = [
  'text-brand-300 bg-brand-500/10 border-brand-500/30',
  'text-emerald-300 bg-emerald-500/10 border-emerald-500/30',
  'text-violet-300 bg-violet-500/10 border-violet-500/30',
  'text-amber-300 bg-amber-500/10 border-amber-500/30',
  'text-pink-300 bg-pink-500/10 border-pink-500/30',
  'text-cyan-300 bg-cyan-500/10 border-cyan-500/30',
]
const enterpriseIndex = computed(() => {
  const order = {}
  let idx = 0
  for (const ev of props.events ?? []) {
    const code = ev.enterprise?.code
    if (code && order[code] === undefined) order[code] = idx++
  }
  return order
})
function enterpriseColor(ev) {
  const code = ev.enterprise?.code
  const idx  = enterpriseIndex.value[code] ?? 0
  return ENTERPRISE_COLORS[idx % ENTERPRISE_COLORS.length]
}

// ── Timeline grouping ─────────────────────────────────────────────
const enterpriseCount = computed(() => {
  const codes = new Set((props.events ?? []).map(e => e.enterprise?.code).filter(Boolean))
  return codes.size
})

// ── Certificates summary (tổng hợp từ toàn chuỗi events) ─────────
const allCertificates = computed(() => {
  const seen = new Set()
  const out  = []
  // Batch-level certs
  for (const c of props.batch?.certificates ?? []) {
    const key = c.name + c.organization
    if (!seen.has(key)) { seen.add(key); out.push({ ...c, source: props.batch?.enterprise?.name ?? 'Lô hàng' }) }
  }
  // Event-level certs (from inspection events)
  for (const ev of props.events ?? []) {
    for (const c of ev.certificates ?? []) {
      const key = c.name + c.organization + ev.event_code
      if (!seen.has(key)) { seen.add(key); out.push({ ...c, source: ev.enterprise?.name ?? 'Chuỗi' }) }
    }
  }
  return out
})

// ── Lineage graph (từ edges nếu có, hoặc tự dựng từ events) ────
const lineageNodes = computed(() => {
  const batchMap = {}
  for (const ev of props.events ?? []) {
    for (const code of [...(ev.input_batch_codes ?? []), ...(ev.output_batch_codes ?? [])]) {
      if (!batchMap[code]) batchMap[code] = { code, events: [] }
      batchMap[code].events.push(ev.cte_code)
    }
  }
  return Object.values(batchMap)
})

// ── Recall helpers ────────────────────────────────────────────────
const isRecalled     = computed(() => props.batch?.status === 'recalled')
const recallDetails  = computed(() => props.batch?.recall_details ?? null)
const isCascadeRecalled = computed(() => props.batch?.is_cascade_recalled === true)
const cascadeParentCode = computed(() => props.batch?.parent_batch_code ?? null)
const cleanRecallReason = computed(() => {
  const reason = recallDetails.value?.reason ?? ''
  return reason.replace(/^\[Cascade từ lô [^\]]+\]\s*/, '')
})

// ── Helpers ───────────────────────────────────────────────────────
function shortCid(cid) {
  if (!cid) return ''
  return cid.length > 16 ? cid.slice(0, 8) + '…' + cid.slice(-6) : cid
}
function kdeLabel(key) {
  const MAP = {
    seed_variety: 'Giống', planting_density: 'Mật độ', method: 'Phương pháp',
    fertilizer_type: 'Loại phân', fertilizer_dose: 'Liều lượng phân', pesticide: 'Thuốc BVTV',
    yield_kg_ha: 'Năng suất', moisture: 'Độ ẩm', impurity: 'Tạp chất',
    vehicle: 'Phương tiện', output_ratio: 'Tỉ lệ TM', capacity_ton_h: 'Công suất',
    package_spec: 'Quy cách', package_qty: 'Số lượng', cert_no: 'Số phiếu',
    criteria: 'Chỉ tiêu', result: 'Kết quả', lab_name: 'Đơn vị',
    vehicle_plate: 'Biển số', driver_name: 'Tài xế', route_from: 'Xuất phát', route_to: 'Điểm đến',
    warehouse_name: 'Tên kho', temp_celsius: 'Nhiệt độ', humidity_pct: 'Độ ẩm',
  }
  return MAP[key] ?? key.replace(/_/g, ' ').replace(/\b\w/g, c => c.toUpperCase())
}
function cteName(ev) {
  const NAME = {
    planting: 'Gieo hạt / Trồng', growing: 'Bón phân / Chăm sóc', spraying: 'Phun thuốc',
    harvesting: 'Thu hoạch', harvest: 'Thu hoạch', milling: 'Xay xát', processing: 'Chế biến',
    packaging: 'Đóng gói', split: 'Tách lô', merge: 'Gộp lô',
    storage: 'Lưu kho', inspection: 'Kiểm định', quality_check: 'Kiểm tra CL',
    shipping: 'Vận chuyển', transport: 'Vận chuyển',
    transfer_out: 'Chuyển giao đi', transfer_in: 'Nhận hàng', transfer: 'Chuyển giao',
  }
  return NAME[ev.cte_code] ?? ev.cte_code?.replace(/_/g, ' ').replace(/\b\w/g, c => c.toUpperCase()) ?? 'Sự kiện'
}
</script>

<template>
  <Head :title="pageTitle">
    <meta name="description" :content="pageDesc" />
    <meta property="og:title"        :content="pageTitle" />
    <meta property="og:description"  :content="pageDesc" />
    <meta property="og:type"         content="website" />
    <meta v-if="ogImage" property="og:image" :content="ogImage" />
    <meta name="twitter:card"        content="summary_large_image" />
  </Head>

  <!-- Lightbox -->
  <Teleport to="body">
    <div v-if="lightboxUrl" class="fixed inset-0 z-50 flex items-center justify-center bg-black/85 p-4" @click.self="lightboxUrl = null">
      <div class="relative max-w-3xl w-full">
        <button @click="lightboxUrl = null" class="absolute -top-10 right-0 text-white/60 hover:text-white text-sm">✕ Đóng</button>
        <img :src="lightboxUrl" class="w-full rounded-xl shadow-2xl object-contain max-h-[80vh]" />
      </div>
    </div>
  </Teleport>

  <div class="max-w-2xl mx-auto px-4 py-8 space-y-6">

    <!-- ══ RECALL BANNER ══════════════════════════════════════════════ -->
    <div v-if="isRecalled" class="rounded-2xl border border-red-500/50 bg-red-950/40 overflow-hidden" data-aos="zoom-in">
      <div class="px-5 py-3 bg-red-600 flex items-center gap-2">
        <span class="text-xl animate-bounce">🚨</span>
        <div>
          <div class="font-bold text-white text-sm tracking-wide">SẢN PHẨM BỊ THU HỒI</div>
          <div class="text-red-100 text-[10px] uppercase font-bold opacity-80">Lệnh thu hồi khẩn cấp</div>
        </div>
      </div>
      <div class="p-5 space-y-3">
        <div v-if="isCascadeRecalled && cascadeParentCode" class="px-4 py-3 bg-orange-950/60 border border-orange-500/40 rounded-xl text-sm text-orange-200">
          ⚠️ <strong>Cảnh báo:</strong> Lô bị thu hồi do có thành phần từ lô cha
          <span class="font-mono font-bold text-orange-300">[{{ cascadeParentCode }}]</span>.
        </div>
        <div v-if="recallDetails" class="text-red-100 font-bold text-sm bg-red-500/10 p-3 rounded-xl border border-red-500/20">{{ cleanRecallReason }}</div>
        <div v-if="recallDetails?.notice_content" class="text-white/80 text-sm whitespace-pre-line px-3 py-2">{{ recallDetails.notice_content }}</div>
      </div>
    </div>

    <!-- ══ BATCH HEADER — GS1 full ════════════════════════════════════ -->
    <div class="rounded-3xl border border-glass bg-white/5 p-6 shadow-xl relative overflow-hidden" data-aos="fade-up">
      <div class="absolute -right-8 -top-8 w-32 h-32 bg-brand-500/10 blur-3xl rounded-full pointer-events-none"></div>

      <!-- Private expiry warning -->
      <div v-if="mode === 'private' && expires_at"
        class="mb-5 px-4 py-2 bg-amber-500/10 border border-amber-500/20 rounded-xl text-xs text-amber-300 text-center font-bold">
        🛡️ QR bảo mật — Hết hạn sau {{ new Date(expires_at).toLocaleString('vi-VN') }}
      </div>

      <!-- Product image + name -->
      <div class="flex gap-5 items-start relative z-10">
        <img v-if="batch?.product?.image_path"
          :src="batch.product.image_path.startsWith('http') ? batch.product.image_path : `/storage/${batch.product.image_path}`"
          class="w-20 h-20 rounded-2xl object-cover border-2 border-white/10 shadow-2xl shrink-0" />
        <div class="flex-1 min-w-0">
          <div class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-md bg-brand-500/10 border border-brand-500/20 text-[10px] font-bold text-brand-400 uppercase mb-1.5">
            {{ batch?.product?.category?.icon }} {{ batch?.product?.category?.name_vi ?? 'Sản phẩm' }}
          </div>
          <h1 class="text-xl font-black text-white leading-tight">
            {{ batch?.product?.name ?? batch?.product_name }}
          </h1>
          <div class="text-sm text-white/40 mt-1">
            Sở hữu bởi: <span class="text-white/70 font-semibold">{{ batch?.enterprise?.name }}</span>
          </div>
        </div>
      </div>

      <!-- GS1 identity grid -->
      <div class="mt-6 grid grid-cols-2 sm:grid-cols-4 gap-3 relative z-10 bg-black/20 p-4 rounded-2xl border border-white/5">
        <div v-if="batch?.code">
          <div class="text-[9px] text-white/25 uppercase font-bold tracking-widest mb-1">
            Mã lô <span class="font-mono text-white/15">(AI 10)</span>
          </div>
          <div class="font-mono text-brand-300 text-sm font-bold">{{ batch.code }}</div>
        </div>
        <div v-if="batch?.ai01_code || batch?.product?.gtin">
          <div class="text-[9px] text-white/25 uppercase font-bold tracking-widest mb-1">
            GTIN <span class="font-mono text-white/15">(AI 01)</span>
          </div>
          <div class="font-mono text-white/70 text-xs font-bold">{{ batch?.ai01_code ?? batch?.product?.gtin }}</div>
        </div>
        <div v-if="batch?.production_date">
          <div class="text-[9px] text-white/25 uppercase font-bold tracking-widest mb-1">Ngày SX</div>
          <div class="text-white/70 text-sm font-semibold">{{ batch.production_date }}</div>
        </div>
        <div v-if="batch?.expiry_date">
          <div class="text-[9px] text-white/25 uppercase font-bold tracking-widest mb-1">Hạn dùng</div>
          <div class="text-white/70 text-sm font-semibold">{{ batch.expiry_date }}</div>
        </div>
      </div>

      <!-- GS1-128 full string badge -->
      <div v-if="batch?.gs1_128_label" class="mt-3 relative z-10">
        <div class="text-[9px] text-white/20 uppercase font-bold tracking-widest mb-1.5">GS1-128 Label</div>
        <code class="block font-mono text-[10px] text-white/50 bg-black/30 border border-white/8 px-3 py-2 rounded-xl break-all leading-relaxed">
          {{ batch.gs1_128_label }}
        </code>
      </div>
    </div>

    <!-- ══ CERTIFICATES SUMMARY (toàn chuỗi) ══════════════════════════ -->
    <div v-if="allCertificates.length" class="space-y-3" data-aos="fade-up">
      <button @click="certSectionExpanded = !certSectionExpanded"
        class="w-full flex items-center justify-between px-2">
        <h3 class="text-xs font-bold text-white/40 uppercase tracking-widest flex items-center gap-2">
          <svg class="w-3.5 h-3.5 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z" />
          </svg>
          Chứng nhận toàn chuỗi
        </h3>
        <span class="text-[10px] text-white/20">{{ certSectionExpanded ? '▲' : '▼' }} {{ allCertificates.length }} chứng nhận</span>
      </button>

      <Transition name="slide-down">
        <div v-if="certSectionExpanded" class="grid grid-cols-1 gap-2">
          <div v-for="cert in allCertificates" :key="cert.name + cert.source"
            class="flex items-center gap-3 p-3.5 rounded-2xl bg-emerald-500/5 border border-emerald-500/20 hover:border-emerald-500/40 transition-all">
            <div class="w-8 h-8 rounded-full bg-emerald-500/10 flex items-center justify-center text-emerald-400 shrink-0">
              <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                <path fill-rule="evenodd" d="M8.603 3.799A4.49 4.49 0 0 1 12 2.25c1.357 0 2.573.6 3.397 1.549a4.49 4.49 0 0 1 3.498 1.307 4.491 4.491 0 0 1 1.307 3.497A4.49 4.49 0 0 1 21.75 12a4.49 4.49 0 0 1-1.549 3.397 4.491 4.491 0 0 1-1.307 3.497 4.491 4.491 0 0 1-3.497 1.307A4.49 4.49 0 0 1 12 21.75a4.49 4.49 0 0 1-3.397-1.549 4.49 4.49 0 0 1-3.498-1.307 4.491 4.491 0 0 1-1.307-3.497A4.49 4.49 0 0 1 2.25 12a4.49 4.49 0 0 1 1.549-3.397 4.492 4.492 0 0 1 1.307-3.497 4.492 4.492 0 0 1 3.497-1.307Zm7.007 6.387a.75.75 0 1 0-1.22-.872l-3.236 4.53L9.53 12.22a.75.75 0 0 0-1.06 1.06l2.25 2.25a.75.75 0 0 0 1.14-.094l3.75-5.25Z" clip-rule="evenodd" />
              </svg>
            </div>
            <div class="flex-1 min-w-0">
              <div class="font-bold text-white text-sm">{{ cert.name }}</div>
              <div class="text-[10px] text-emerald-400/70 font-bold uppercase tracking-tighter">
                {{ cert.organization }} <span class="text-white/20 normal-case font-normal">·</span>
                <span class="text-white/30 font-normal normal-case ml-1">{{ cert.source }}</span>
              </div>
              <div v-if="cert.result" class="text-[10px] mt-0.5" :class="cert.result === 'pass' ? 'text-emerald-400' : cert.result === 'fail' ? 'text-red-400' : 'text-amber-400'">
                {{ cert.result === 'pass' ? '✅ Đạt' : cert.result === 'fail' ? '❌ Không đạt' : '⚠️ Có điều kiện' }}
                <span v-if="cert.expiry_date" class="text-white/25 ml-1">· HH: {{ cert.expiry_date }}</span>
              </div>
            </div>
            <button v-if="cert.image_url" @click="lightboxUrl = cert.image_url"
              class="text-[10px] font-bold text-white/40 border border-white/10 px-2.5 py-1.5 rounded-xl hover:bg-white/5 hover:text-white transition shrink-0">
              Xem
            </button>
          </div>
        </div>
      </Transition>
    </div>

    <!-- ══ LINEAGE PANEL (collapsible) ════════════════════════════════ -->
    <div v-if="lineageNodes.length > 1" class="rounded-2xl border border-white/8 bg-black/20 overflow-hidden" data-aos="fade-up">
      <button @click="lineageExpanded = !lineageExpanded"
        class="w-full px-5 py-3.5 flex items-center justify-between hover:bg-white/3 transition">
        <div class="flex items-center gap-2">
          <svg class="w-4 h-4 text-brand-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" />
          </svg>
          <span class="text-xs font-bold text-white/50 uppercase tracking-widest">Cây nguồn gốc Lineage</span>
        </div>
        <span class="text-white/20 text-xs">{{ lineageExpanded ? '▲ Thu gọn' : '▼ Xem lineage' }}</span>
      </button>

      <Transition name="slide-down">
        <div v-if="lineageExpanded" class="px-5 pb-5 pt-2 overflow-x-auto">
          <!-- Horizontal chain: batch → [event] → batch → ... -->
          <div class="flex items-center gap-1 flex-nowrap min-w-0">
            <template v-for="(node, i) in lineageNodes" :key="node.code">
              <div class="flex flex-col items-center shrink-0">
                <div class="text-[9px] text-white/30 uppercase mb-1">Lô</div>
                <div class="px-2.5 py-1.5 rounded-xl border border-brand-500/20 bg-brand-500/5 font-mono text-[10px] text-brand-300 whitespace-nowrap max-w-[100px] truncate"
                  :title="node.code">
                  {{ node.code }}
                </div>
              </div>
              <template v-if="i < lineageNodes.length - 1">
                <div class="flex flex-col items-center shrink-0 px-1">
                  <div class="text-white/15 text-lg leading-none">→</div>
                  <div v-if="lineageNodes[i + 1]?.events?.[0]" class="text-[8px] text-white/20 mt-0.5 whitespace-nowrap">
                    {{ lineageNodes[i + 1].events[0] }}
                  </div>
                </div>
              </template>
            </template>
          </div>
        </div>
      </Transition>
    </div>

    <!-- ══ VERTICAL TIMELINE ═══════════════════════════════════════════ -->
    <div class="space-y-4">
      <div class="flex items-center justify-between px-2">
        <h2 class="text-xs font-extrabold text-white/40 uppercase tracking-widest flex items-center gap-2">
          <span class="w-2 h-2 rounded-full bg-brand-500 animate-pulse"></span>
          Hành trình chuỗi cung ứng
        </h2>
        <div class="text-[10px] text-white/20 font-bold uppercase tracking-tighter">
          {{ events?.length ?? 0 }} sự kiện · {{ enterpriseCount }} đơn vị
        </div>
      </div>

      <div v-if="!events?.length" class="rounded-3xl border-2 border-dashed border-white/5 p-12 text-center text-white/10">
        <div class="text-5xl mb-4 opacity-10">🌍</div>
        <p class="text-sm font-bold uppercase tracking-widest">Dữ liệu đang được cập nhật</p>
      </div>

      <div v-else class="relative pl-2 pb-8">
        <!-- Journey Line -->
        <div class="absolute left-7 top-4 bottom-0 w-0.5 bg-gradient-to-b from-brand-500/60 via-white/10 to-transparent"></div>

        <template v-for="(event, idx) in events" :key="event.id">

          <!-- Enterprise Gateway: hiện khi chuyển enterprise -->
          <div v-if="idx === 0 || event.enterprise?.code !== events[idx-1]?.enterprise?.code"
            class="relative z-10 pl-14 py-7 first:pt-2" data-aos="fade-right">
            <div class="absolute left-5 top-1/2 -translate-y-1/2 w-4 h-4 rounded-full bg-[#0d1117] border-2 border-brand-500 flex items-center justify-center shadow-lg shadow-brand-500/20">
              <div class="w-1.5 h-1.5 rounded-full bg-brand-400"></div>
            </div>
            <div :class="['inline-flex items-center gap-2 px-3 py-1.5 rounded-xl border text-xs font-bold', enterpriseColor(event)]">
              <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" /></svg>
              {{ event.enterprise?.name ?? 'Đơn vị không xác định' }}
            </div>
          </div>

          <!-- Event Milestone Card -->
          <div class="relative pl-14 pb-10 last:pb-4 group" data-aos="fade-up">

            <!-- Icon circle — color by category -->
            <div :class="[
              'absolute left-4 top-0 z-20 w-7 h-7 rounded-full border-2 flex items-center justify-center text-sm shadow-2xl transition-all duration-300 scale-100 group-hover:scale-110',
              cteCategory(event) === 'transfer'       ? 'bg-purple-950 border-purple-500/60 group-hover:border-purple-400' :
              cteCategory(event) === 'transformation' ? 'bg-orange-950 border-orange-500/60 group-hover:border-orange-400' :
              'bg-[#0d1117] border-white/15 group-hover:border-brand-500'
            ]">
              {{ cteIcon(event.cte_code) }}
            </div>

            <!-- Card -->
            <div :class="[
              'border rounded-3xl p-5 transition-all duration-300 relative overflow-hidden shadow-lg',
              catStyle(event).border, catStyle(event).bg,
              'hover:shadow-xl',
            ]">

              <!-- Watermark -->
              <div class="absolute -right-6 -top-6 text-6xl opacity-[0.04] grayscale pointer-events-none group-hover:opacity-[0.08] transition-opacity duration-700 rotate-12">
                {{ cteIcon(event.cte_code) }}
              </div>

              <!-- Header -->
              <div class="flex justify-between items-start gap-3 relative z-10 flex-wrap">
                <div class="min-w-0">
                  <div class="flex items-center gap-2 flex-wrap">
                    <h3 class="text-base font-black text-white/90">{{ cteName(event) }}</h3>
                    <!-- Category badge -->
                    <span :class="['text-[9px] font-black uppercase tracking-widest px-2 py-0.5 rounded-full border', catStyle(event).badge]">
                      {{ catStyle(event).label }}
                    </span>
                  </div>
                  <!-- event_code AI(251) -->
                  <div v-if="event.event_code" class="mt-1 inline-flex items-center gap-1 text-[9px] text-white/25 font-mono bg-black/20 px-2 py-0.5 rounded border border-white/5">
                    (251){{ event.event_code }}
                  </div>
                  <div class="inline-flex items-center gap-1.5 text-[10px] text-white/30 font-bold mt-1 bg-black/20 px-2 py-0.5 rounded-lg border border-white/5 ml-1">
                    📅 {{ event.event_time }}
                  </div>
                </div>
                <!-- IPFS verified badge -->
                <div v-if="event.ipfs_cid" class="shrink-0 pt-1">
                  <div class="flex items-center gap-1.5 px-2 py-1 rounded-lg bg-green-500/10 border border-green-500/20 text-[9px] font-black text-green-300 uppercase tracking-widest">
                    <span class="w-1.5 h-1.5 rounded-full bg-green-400 animate-pulse"></span>
                    IPFS Verified
                  </div>
                </div>
              </div>

              <!-- 5W Grid -->
              <div class="mt-4 grid grid-cols-1 sm:grid-cols-2 gap-3 relative z-10">
                <div v-if="event.who_name" class="p-3 rounded-2xl bg-white/3 border border-white/5">
                  <div class="text-[8px] text-white/20 uppercase font-black tracking-tighter mb-1">WHO — Thực hiện bởi</div>
                  <div class="text-[11px] text-white/80 font-bold">{{ event.who_name }}</div>
                </div>

                <div v-if="event.location" class="p-3 rounded-2xl bg-white/3 border border-white/5">
                  <div class="text-[8px] text-white/20 uppercase font-black tracking-tighter mb-1">
                    WHERE — {{ event.location.ai_type ? `(${event.location.ai_type})` : '' }} {{ event.location.name }}
                  </div>
                  <div class="text-[11px] text-white/70">{{ event.location.address ?? event.location.province }}</div>
                  <div v-if="event.location.gln" class="text-[9px] text-white/30 font-mono mt-0.5">GLN: {{ event.location.gln }}</div>
                </div>

                <div v-if="event.why_reason" class="sm:col-span-2 p-3 rounded-2xl bg-brand-500/5 border border-brand-500/10">
                  <div class="text-[8px] text-brand-400/50 uppercase font-black tracking-tighter mb-1">WHY — Mục đích</div>
                  <div class="text-[11px] text-brand-200 font-medium italic">"{{ event.why_reason }}"</div>
                </div>

                <!-- Transfer info -->
                <div v-if="cteCategory(event) === 'transfer'" class="sm:col-span-2 p-3 rounded-2xl bg-purple-500/5 border border-purple-500/15">
                  <div class="text-[8px] text-purple-400/50 uppercase font-black tracking-tighter mb-1.5">Chuyển giao</div>
                  <div class="flex flex-wrap gap-2">
                    <span v-for="code in event.output_batch_codes" :key="code"
                      class="text-[10px] font-mono px-2 py-0.5 rounded border border-purple-500/20 bg-purple-500/10 text-purple-300">
                      → {{ code }}
                    </span>
                  </div>
                </div>
              </div>

              <!-- KDE expander -->
              <details v-if="event.kde_data && Object.keys(event.kde_data).length" class="mt-4 border-t border-white/5 pt-4 relative z-10">
                <summary class="text-[9px] font-black text-white/25 cursor-pointer hover:text-white/50 select-none flex items-center gap-2 uppercase tracking-widest">
                  <svg class="w-3 h-3 text-brand-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
                  Thông số KDE chi tiết
                </summary>
                <div class="mt-3 grid grid-cols-2 gap-2 bg-black/40 rounded-2xl p-4 shadow-inner border border-white/5">
                  <div v-for="(val, key) in event.kde_data" :key="key" class="flex flex-col border-b border-white/5 last:border-0 pb-1 mb-1">
                    <span class="text-[8px] text-white/20 uppercase font-black">{{ kdeLabel(key) }}</span>
                    <span class="text-[10px] text-white/70 font-medium truncate" :title="String(val)">{{ val ?? '—' }}</span>
                  </div>
                </div>
              </details>

              <!-- Event certificates (inspection events) -->
              <div v-if="event.certificates?.length" class="mt-4 border-t border-white/5 pt-4 relative z-10">
                <div class="text-[9px] text-emerald-400/50 uppercase font-black tracking-widest mb-2">Kết quả kiểm nghiệm</div>
                <div class="space-y-1.5">
                  <div v-for="c in event.certificates" :key="c.name + c.reference_no"
                    class="flex items-center gap-2 text-[10px] px-2.5 py-1.5 rounded-xl border border-emerald-500/15 bg-emerald-500/5">
                    <span :class="c.result === 'pass' ? 'text-emerald-400' : c.result === 'fail' ? 'text-red-400' : 'text-amber-400'">
                      {{ c.result === 'pass' ? '✅' : c.result === 'fail' ? '❌' : '⚠️' }}
                    </span>
                    <span class="text-white/70 font-semibold">{{ c.name }}</span>
                    <span class="text-white/30">{{ c.organization }}</span>
                    <span v-if="c.reference_no" class="font-mono text-white/25 ml-auto">{{ c.reference_no }}</span>
                  </div>
                </div>
              </div>

              <!-- Footer: Attachments + IPFS proof -->
              <div class="mt-5 flex flex-wrap items-center justify-between gap-3 pt-4 border-t border-white/5 relative z-10">
                <div class="flex flex-wrap gap-2">
                  <a v-for="att in event.attachments" :key="att.url ?? att.name"
                    :href="att.url" target="_blank"
                    class="inline-flex items-center gap-1.5 text-[10px] font-bold px-3 py-1.5 rounded-xl bg-white/5 border border-white/10 text-white/40 hover:bg-brand-500/10 hover:border-brand-500/20 hover:text-brand-300 transition-all">
                    📎 {{ att.name }}
                  </a>
                </div>

                <VerifyIntegrityBtn
                  v-if="event.ipfs_cid"
                  :event-id="event.id"
                  :ipfs-cid="event.ipfs_cid"
                  :short-cid-fn="shortCid"
                />

            </div>
          </div>
        </template>
      </div>
    </div>

    <!-- Footer -->
    <div class="text-center pt-8 border-t border-white/5">
      <div class="text-[10px] font-black text-white/10 uppercase tracking-[0.3em] mb-2">TCVN 12850:2019 · TT02/2024/TT-BKHCN</div>
      <p class="text-[11px] text-white/20 font-bold italic opacity-50">"Minh bạch hóa hành trình — Bảo vệ người tiêu dùng Việt"</p>
    </div>

  </div>
</template>

<style scoped>
.slide-down-enter-active, .slide-down-leave-active { transition: all .25s ease; overflow: hidden; }
.slide-down-enter-from, .slide-down-leave-to { opacity: 0; max-height: 0; }
.slide-down-enter-to, .slide-down-leave-from { max-height: 800px; }
</style>
