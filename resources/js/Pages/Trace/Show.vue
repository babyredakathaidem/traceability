<script setup>
import { computed, ref } from 'vue'
import { Head } from '@inertiajs/vue3'

// ── Props từ controller ───────────────────────────────────────────
const props = defineProps({
  mode:       String,   // 'public' | 'private'
  batch:      Object,
  events:     Array,
  place_name: String,
  expires_at: String,
})

// ── Certificate lightbox ──────────────────────────────────────────
const lightboxUrl = ref(null)

// ── SEO meta ─────────────────────────────────────────────────────
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

// ── Helpers ───────────────────────────────────────────────────────
const CTE_ICON = {
  planting: '🌱', growing: '🌿', harvest: '🌾', harvesting: '🌾',
  processing: '⚙️', packaging: '📦', transport: '🚚', shipping: '🚚',
  quality_check: '🔬', inspection: '🔍', storage: '🏭', warehousing: '🏭',
  distribution: '🏪', retail: '🛒', recall: '🚨', split: '✂️',
  merge: '🔗', transfer: '🔄', transfer_sent: '📤', transfer_received: '📥', custom: '📋',
}
function cteIcon(code) { return CTE_ICON[code] ?? '📋' }
function kdeLabel(key) {
  return key.replace(/_/g, ' ').replace(/\b\w/g, c => c.toUpperCase())
}
const enterpriseCount = computed(() => {
  const codes = new Set(props.events.map(e => e.enterprise?.code).filter(Boolean))
  return codes.size
})

// ── Recall helpers ─────────────────────────────────────────────────
const isRecalled = computed(() => props.batch?.status === 'recalled')
const recallDetails = computed(() => props.batch?.recall_details ?? null)
const isCascadeRecalled = computed(() => props.batch?.is_cascade_recalled === true)

// Trích xuất mã lô cha từ reason của cascade recall
const cascadeParentCode = computed(() => {
  if (!isCascadeRecalled.value) return null
  const reason = recallDetails.value?.reason ?? ''
  const match = reason.match(/\[Cascade từ lô ([^\]]+)\]/)
  return match ? match[1] : (props.batch?.parent_batch_code ?? null)
})

const cleanRecallReason = computed(() => {
  const reason = recallDetails.value?.reason ?? ''
  return reason.replace(/^\[Cascade từ lô [^\]]+\]\s*/, '')
})

function ipfsVerifyUrl(cid) { return `/verify/ipfs/${cid}` }
function shortCid(cid) {
  if (!cid) return ''
  return cid.length > 16 ? cid.slice(0, 8) + '...' + cid.slice(-6) : cid
}

// ── Certificate helpers ────────────────────────────────────────────
const activeCerts = computed(() =>
  (props.batch?.certificates ?? []).filter(c => !c.is_expired)
)
const expiredCerts = computed(() =>
  (props.batch?.certificates ?? []).filter(c => c.is_expired)
)
</script>

<template>
  <Head :title="pageTitle">
    <meta name="description" :content="pageDesc" />
    <meta property="og:title"       :content="pageTitle" />
    <meta property="og:description" :content="pageDesc" />
    <meta property="og:type"        content="website" />
    <meta v-if="ogImage" property="og:image" :content="ogImage" />
    <meta name="twitter:card"        content="summary" />
    <meta name="twitter:title"       :content="pageTitle" />
    <meta name="twitter:description" :content="pageDesc" />
    <meta v-if="ogImage" name="twitter:image" :content="ogImage" />
  </Head>

  <!-- Certificate Lightbox -->
  <Teleport to="body">
    <div v-if="lightboxUrl"
      class="fixed inset-0 z-50 flex items-center justify-center bg-black/80 p-4"
      @click.self="lightboxUrl = null">
      <div class="relative max-w-3xl w-full">
        <button @click="lightboxUrl = null"
          class="absolute -top-10 right-0 text-white/60 hover:text-white text-sm">
          ✕ Đóng
        </button>
        <img :src="lightboxUrl" class="w-full rounded-xl shadow-2xl object-contain max-h-[80vh]" />
      </div>
    </div>
  </Teleport>

  <div class="max-w-2xl mx-auto px-4 py-8 space-y-6">

    <!-- ══════════════════════════════════════════════════════════════
         RECALL BANNER
    ══════════════════════════════════════════════════════════════════ -->
    <div v-if="isRecalled"
      class="rounded-2xl border border-red-500/50 bg-red-950/40 overflow-hidden shadow-2xl shadow-red-900/20" data-aos="zoom-in">
      <div class="px-5 py-3 bg-red-600 flex items-center gap-2">
        <span class="text-xl animate-bounce">🚨</span>
        <div>
          <div class="font-bold text-white text-sm tracking-wide">SẢN PHẨM BỊ THU HỒI</div>
          <div class="text-red-100 text-[10px] uppercase font-bold opacity-80">Lệnh thu hồi khẩn cấp</div>
        </div>
      </div>
      <div class="p-5 space-y-4">
        <div v-if="isCascadeRecalled && cascadeParentCode"
          class="px-4 py-3 bg-orange-950/60 border border-orange-500/40 rounded-xl text-sm text-orange-200">
          ⚠️ <strong>Cảnh báo:</strong> Lô hàng này bị thu hồi do chứa thành phần từ
          <span class="font-mono font-bold text-orange-300 underline decoration-dotted underline-offset-4">lô cha [{{ cascadeParentCode }}]</span>.
        </div>
        <div v-if="recallDetails">
          <div class="text-[10px] text-red-400/70 uppercase tracking-widest mb-1 font-bold">Lý do thu hồi</div>
          <div class="text-red-100 font-bold text-sm leading-relaxed bg-red-500/10 p-3 rounded-xl border border-red-500/20">
            {{ cleanRecallReason }}
          </div>
        </div>
        <div v-if="recallDetails?.notice_content">
          <div class="text-[10px] text-red-400/70 uppercase tracking-widest mb-1 font-bold">Hướng dẫn xử lý</div>
          <div class="text-white/80 text-sm leading-relaxed whitespace-pre-line px-3 py-2">
            {{ recallDetails.notice_content }}
          </div>
        </div>
        <div v-if="recallDetails?.ipfs_cid"
          class="px-3 py-2.5 bg-black/30 border border-white/10 rounded-xl flex items-center justify-between">
          <div>
            <div class="text-[9px] text-white/30 uppercase tracking-widest mb-0.5">Proof trên IPFS</div>
            <code class="text-[10px] font-mono text-purple-300">{{ shortCid(recallDetails.ipfs_cid) }}</code>
          </div>
          <a :href="ipfsVerifyUrl(recallDetails.ipfs_cid)" target="_blank"
            class="text-[10px] font-bold text-brand-400 bg-brand-500/10 px-2 py-1 rounded-lg border border-brand-500/20">
            XÁC MINH →
          </a>
        </div>
      </div>
    </div>

    <!-- ══════════════════════════════════════════════════════════════
         BATCH HEADER
    ══════════════════════════════════════════════════════════════════ -->
    <div class="rounded-3xl border border-glass bg-white/5 p-6 shadow-xl relative overflow-hidden" data-aos="fade-up">
      <div class="absolute -right-8 -top-8 w-32 h-32 bg-brand-500/10 blur-3xl rounded-full"></div>
      
      <div v-if="mode === 'private' && expires_at"
        class="mb-5 px-4 py-2 bg-amber-500/10 border border-amber-500/20 rounded-xl text-xs text-amber-300 text-center font-bold">
        🛡️ QR bảo mật — Hết hạn sau {{ new Date(expires_at).toLocaleString('vi-VN') }}
      </div>

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
          <div class="text-sm text-white/40 mt-1 font-medium italic">
            Cung cấp bởi: <span class="text-white/70 non-italic">{{ batch?.enterprise?.name }}</span>
          </div>
        </div>
      </div>

      <div class="mt-6 grid grid-cols-2 gap-4 relative z-10 bg-black/20 p-4 rounded-2xl border border-white/5">
        <div v-if="batch?.code">
          <div class="text-[9px] text-white/25 uppercase font-bold tracking-widest">Mã định danh lô</div>
          <div class="font-mono text-brand-300 text-sm font-bold">{{ batch.code }}</div>
        </div>
        <div v-if="batch?.product?.gtin">
          <div class="text-[9px] text-white/25 uppercase font-bold tracking-widest">Mã GS1 (GTIN)</div>
          <div class="font-mono text-white/80 text-sm font-bold">{{ batch.product.gtin }}</div>
        </div>
        <div v-if="batch?.production_date">
          <div class="text-[9px] text-white/25 uppercase font-bold tracking-widest">Ngày sản xuất</div>
          <div class="text-white/80 text-sm font-semibold">{{ batch.production_date }}</div>
        </div>
        <div v-if="batch?.expiry_date">
          <div class="text-[9px] text-white/25 uppercase font-bold tracking-widest">Hạn sử dụng</div>
          <div class="text-white/80 text-sm font-semibold">{{ batch.expiry_date }}</div>
        </div>
      </div>
    </div>

    <!-- ══════════════════════════════════════════════════════════════
         CHỨNG CHỈ (Certificates)
    ══════════════════════════════════════════════════════════════════ -->
    <div v-if="batch?.certificates?.length" class="space-y-3" data-aos="fade-up">
      <h3 class="text-xs font-bold text-white/40 uppercase tracking-widest px-2">Chứng nhận chất lượng</h3>
      <div class="grid grid-cols-1 gap-3">
        <div v-for="cert in activeCerts" :key="cert.id"
          class="flex items-center gap-4 p-4 rounded-2xl bg-emerald-500/5 border border-emerald-500/20 hover:border-emerald-500/40 transition-all group">
          <div class="w-10 h-10 rounded-full bg-emerald-500/10 flex items-center justify-center text-emerald-400 shrink-0 shadow-inner">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-6 h-6">
              <path fill-rule="evenodd" d="M8.603 3.799A4.49 4.49 0 0 1 12 2.25c1.357 0 2.573.6 3.397 1.549a4.49 4.49 0 0 1 3.498 1.307 4.491 4.491 0 0 1 1.307 3.497A4.49 4.49 0 0 1 21.75 12a4.49 4.49 0 0 1-1.549 3.397 4.491 4.491 0 0 1-1.307 3.497 4.491 4.491 0 0 1-3.497 1.307A4.49 4.49 0 0 1 12 21.75a4.49 4.49 0 0 1-3.397-1.549a4.49 4.49 0 0 1-3.498-1.307 4.491 4.491 0 0 1-1.307-3.497A4.49 4.49 0 0 1 2.25 12a4.49 4.49 0 0 1 1.549-3.397 4.492 4.492 0 0 1 1.307-3.497 4.492 4.492 0 0 1 3.497-1.307Zm7.007 6.387a.75.75 0 1 0-1.22-.872l-3.236 4.53L9.53 12.22a.75.75 0 0 0-1.06 1.06l2.25 2.25a.75.75 0 0 0 1.14-.094l3.75-5.25Z" clip-rule="evenodd" />
            </svg>
          </div>
          <div class="flex-1 min-w-0">
            <div class="font-extrabold text-white text-sm">{{ cert.name }}</div>
            <div class="text-[10px] text-emerald-400/70 font-bold uppercase tracking-tighter">{{ cert.organization }}</div>
          </div>
          <button v-if="cert.image_url" @click="lightboxUrl = cert.image_url"
            class="text-[10px] font-bold text-white/40 border border-white/10 px-3 py-1.5 rounded-xl hover:bg-white/5 hover:text-white transition-all">
            XEM CHI TIẾT
          </button>
        </div>
      </div>
    </div>

    <!-- ══════════════════════════════════════════════════════════════
         VERTICAL JOURNEY TIMELINE
    ══════════════════════════════════════════════════════════════ -->
    <div class="space-y-4">
      <div class="flex items-center justify-between px-2">
        <h2 class="text-xs font-extrabold text-white/40 uppercase tracking-widest flex items-center gap-2">
          <span class="w-2 h-2 rounded-full bg-brand-500 animate-pulse"></span>
          Hành trình chuỗi cung ứng
        </h2>
        <div class="text-[10px] text-white/20 font-bold uppercase tracking-tighter">
          {{ events.length }} Events · {{ enterpriseCount }} Units
        </div>
      </div>

      <div v-if="events.length === 0" class="rounded-3xl border-2 border-dashed border-white/5 p-12 text-center text-white/10">
        <div class="text-5xl mb-4 opacity-10">🌍</div>
        <p class="text-sm font-bold uppercase tracking-widest">Dữ liệu đang được cập nhật</p>
      </div>

      <div v-else class="relative pl-2 pb-8" v-auto-animate>
        <!-- The Journey Line -->
        <div class="absolute left-7 top-4 bottom-0 w-0.5 bg-gradient-to-b from-brand-500/60 via-white/10 to-transparent"></div>

        <template v-for="(event, idx) in events" :key="event.id">
          
          <!-- Enterprise Gateway: Show when moving to a new unit -->
          <div v-if="idx === 0 || event.enterprise?.code !== events[idx-1]?.enterprise?.code"
            class="relative z-10 pl-14 py-8 first:pt-2" data-aos="fade-right">
            <div class="absolute left-5 top-1/2 -translate-y-1/2 w-4 h-4 rounded-full bg-cosmic-950 border-2 border-brand-500 flex items-center justify-center shadow-lg shadow-brand-500/20">
              <div class="w-1.5 h-1.5 rounded-full bg-brand-400"></div>
            </div>
            <div class="inline-flex flex-col">
              <span class="text-[9px] font-black text-brand-500 uppercase tracking-widest leading-none mb-1">Cơ sở vận hành:</span>
              <span class="text-sm font-black text-white bg-white/5 border border-white/10 px-3 py-1 rounded-xl shadow-xl">
                🏛️ {{ event.enterprise?.name }}
              </span>
            </div>
          </div>

          <!-- The Event Milestone -->
          <div class="relative pl-14 pb-12 last:pb-4 group" data-aos="fade-up">
            
            <!-- Floating Icon Circle -->
            <div class="absolute left-4 top-0 z-20 w-7 h-7 rounded-full bg-cosmic-900 border-2 border-white/10 group-hover:border-brand-500 flex items-center justify-center text-sm shadow-2xl transition-all duration-500 scale-100 group-hover:scale-110">
              {{ cteIcon(event.cte_code) }}
            </div>

            <!-- Milestone Card -->
            <div class="bg-white/5 border border-glass rounded-3xl p-5 hover:bg-white/8 transition-all duration-500 relative overflow-hidden shadow-lg hover:shadow-brand-500/5">
              
              <!-- Giant Background Watermark -->
              <div class="absolute -right-6 -top-6 text-6xl opacity-[0.03] grayscale pointer-events-none group-hover:opacity-[0.07] transition-opacity duration-700 rotate-12">
                {{ cteIcon(event.cte_code) }}
              </div>

              <!-- Header Area -->
              <div class="flex justify-between items-start gap-4 relative z-10">
                <div>
                  <h3 class="text-base font-black text-white/90 group-hover:text-brand-300 transition-colors">
                    {{ event.cte_code?.replace(/_/g, ' ').replace(/\b\w/g, c => c.toUpperCase()) ?? 'Sự kiện' }}
                  </h3>
                  <div class="inline-flex items-center gap-1.5 text-[10px] text-white/30 font-bold mt-1 bg-black/20 px-2 py-0.5 rounded-lg border border-white/5">
                    📅 {{ event.event_time }}
                  </div>
                </div>
                <div v-if="event.status === 'published'" class="shrink-0 pt-1">
                  <div class="flex items-center gap-1.5 px-2 py-1 rounded-lg bg-purple-500/10 border border-purple-500/20 text-[9px] font-black text-purple-300 uppercase tracking-widest shadow-inner">
                    <span class="w-1.5 h-1.5 rounded-full bg-purple-400 animate-pulse"></span>
                    Verified
                  </div>
                </div>
              </div>

              <!-- 5W Data Matrix -->
              <div class="mt-5 grid grid-cols-1 sm:grid-cols-2 gap-4 relative z-10">
                <div v-if="event.who_name" class="p-3 rounded-2xl bg-white/3 border border-white/5">
                  <div class="text-[8px] text-white/20 uppercase font-black tracking-tighter mb-1">Thực hiện bởi</div>
                  <div class="text-[11px] text-white/80 font-bold">{{ event.who_name }}</div>
                </div>
                <div v-if="event.where_address" class="p-3 rounded-2xl bg-white/3 border border-white/5">
                  <div class="text-[8px] text-white/20 uppercase font-black tracking-tighter mb-1">Vị trí địa lý</div>
                  <div class="text-[11px] text-white/80 font-bold truncate">{{ event.where_address }}</div>
                </div>
                <div v-if="event.why_reason" class="sm:col-span-2 p-3 rounded-2xl bg-brand-500/5 border border-brand-500/10 shadow-inner">
                  <div class="text-[8px] text-brand-400/50 uppercase font-black tracking-tighter mb-1">Mục đích / Tiêu chuẩn</div>
                  <div class="text-[11px] text-brand-200 font-medium italic">"{{ event.why_reason }}"</div>
                </div>
              </div>

              <!-- Detail Expander -->
              <details v-if="event.kde_data && Object.keys(event.kde_data).length" class="mt-4 border-t border-white/5 pt-4">
                <summary class="text-[9px] font-black text-white/25 cursor-pointer hover:text-white/50 select-none flex items-center gap-2 uppercase tracking-widest">
                  <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-3 h-3 text-brand-500">
                    <path fill-rule="evenodd" d="M5.22 8.22a.75.75 0 0 1 1.06 0L10 11.94l3.72-3.72a.75.75 0 1 1 1.06 1.06l-4.25 4.25a.75.75 0 0 1-1.06 0L5.22 9.28a.75.75 0 0 1 0-1.06Z" clip-rule="evenodd" />
                  </svg>
                  Thông số chi tiết (GS1 KDE)
                </summary>
                <div class="mt-3 grid grid-cols-2 gap-2 bg-black/40 rounded-2xl p-4 shadow-inner border border-white/5">
                  <div v-for="(val, key) in event.kde_data" :key="key" class="flex flex-col border-b border-white/5 last:border-0 pb-1 mb-1">
                    <span class="text-[8px] text-white/20 uppercase font-black">{{ kdeLabel(key) }}</span>
                    <span class="text-[10px] text-white/70 font-medium truncate" :title="val">{{ val ?? '—' }}</span>
                  </div>
                </div>
              </details>

              <!-- Footer Proofs -->
              <div class="mt-5 flex flex-wrap items-center justify-between gap-4 pt-4 border-t border-white/5">
                <div class="flex flex-wrap gap-2">
                  <a v-for="att in event.attachments" :key="att.cid"
                    :href="att.url" target="_blank"
                    class="inline-flex items-center gap-1.5 text-[10px] font-bold px-3 py-1.5 rounded-xl bg-white/5 border border-white/10 text-white/40 hover:bg-brand-500/10 hover:border-brand-500/20 hover:text-brand-300 transition-all shadow-sm">
                    📎 {{ att.name }}
                  </a>
                </div>
                
                <div v-if="event.ipfs_cid" class="flex items-center gap-3">
                  <div class="flex flex-col items-end">
                    <span class="text-[8px] text-white/20 uppercase font-black leading-none">IPFS Node</span>
                    <code class="text-[9px] font-mono text-purple-400/60 font-bold">#{{ shortCid(event.ipfs_cid) }}</code>
                  </div>
                  <a :href="`/verify/ipfs/${event.ipfs_cid}?hash=${event.content_hash}`"
                    target="_blank"
                    class="text-[10px] font-black text-brand-950 bg-brand-500 px-3 py-1.5 rounded-xl shadow-lg shadow-brand-500/30 hover:bg-brand-400 active:scale-95 transition-all uppercase tracking-tighter">
                    Xác thực
                  </a>
                </div>
              </div>

            </div>
          </div>
        </template>
      </div>
    </div>

    <!-- Final Footer -->
    <div class="text-center pt-8 border-t border-white/5">
      <div class="text-[10px] font-black text-white/10 uppercase tracking-[0.3em] mb-2">TCVN 12850:2019 COMPLIANT</div>
      <p class="text-[11px] text-white/20 font-bold italic opacity-50">"Minh bạch hóa hành trình — Bảo vệ người tiêu dùng Việt"</p>
    </div>
  </div>
</template>

<style scoped>
/* Custom animations if needed */
</style>
