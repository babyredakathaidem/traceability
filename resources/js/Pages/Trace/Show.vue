<script setup>
import { Head } from '@inertiajs/vue3'
import { ref, computed } from 'vue'
import GuestLayout from '@/Layouts/GuestLayout.vue'
defineOptions({ layout: GuestLayout })

const props = defineProps({
  mode:       { type: String, default: 'public' },
  batch:      { type: Object, default: () => ({}) },
  events:     { type: Array,  default: () => [] },
  place_name: { type: String, default: null },
  expires_at: { type: String, default: null },
})

const open = ref(null)
const toggle = (id) => { open.value = open.value === id ? null : id }

// ── OG / SEO meta ─────────────────────────────────────────
const pageTitle = computed(() => {
  const name = props.batch?.product?.name ?? props.batch?.product_name ?? 'Sản phẩm'
  return `${name} — Truy xuất nguồn gốc`
})

const pageDesc = computed(() => {
  const ent  = props.batch?.enterprise?.name ?? ''
  const code = props.batch?.code ?? ''
  const n    = props.events?.length ?? 0
  return `Lô ${code}${ent ? ' — ' + ent : ''}. ${n} sự kiện truy xuất đã được xác nhận trên IPFS theo TCVN 12850:2019.`
})

const ogImage = computed(() => {
  const img = props.batch?.product?.image_path
  if (!img) return null
  return img.startsWith('http') ? img : `/storage/${img}`
})

// ── Label CTE code ─────────────────────────────────────────
const CTE_LABELS = {
  harvest:           '🌾 Thu hoạch',
  processing:        '🏭 Chế biến',
  packaging:         '📦 Đóng gói',
  warehousing:       '🏬 Lưu kho',
  transportation:    '🚚 Vận chuyển',
  distribution:      '📦 Phân phối',
  retail:            '🏪 Bán lẻ',
  transfer_received: '🔄 Nhận hàng',
  inspection:        '🔍 Kiểm tra',
  custom:            '📋 Sự kiện',
}

function cteLabel(code) {
  return CTE_LABELS[code] ?? (code ? `📋 ${code}` : '📋 Sự kiện')
}

// ── Helpers ────────────────────────────────────────────────
function kdeRows(kdeData) {
  if (!kdeData || typeof kdeData !== 'object') return []
  return Object.entries(kdeData)
    .filter(([k, v]) => v !== null && v !== '' && typeof v !== 'object' && !k.endsWith('_lat') && !k.endsWith('_lng'))
    .map(([k, v]) => ({ label: kdeLabel(k), value: String(v) }))
}

function kdeLabel(key) {
  const map = {
    who_performer: 'Người thực hiện',
    what_quantity: 'Số lượng',
    what_unit: 'Đơn vị',
    what_warehouse: 'Kho',
    what_temp: 'Nhiệt độ (°C)',
    what_humidity: 'Độ ẩm (%)',
    where_address: 'Địa điểm',
    why_note: 'Ghi chú',
    invoice_no: 'Số hóa đơn',
    from_enterprise: 'Từ DN',
    to_enterprise: 'Đến DN',
    action: 'Hành động',
  }
  return map[key] ?? key.replace(/_/g, ' ').replace(/\b\w/g, c => c.toUpperCase())
}

// Số DN tham gia chuỗi
const enterpriseCount = computed(() => {
  const codes = new Set(props.events.map(e => e.enterprise?.code).filter(Boolean))
  return codes.size
})
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

  <div class="max-w-2xl mx-auto px-4 py-8 space-y-5">

    <!-- Batch header -->
    <div class="rounded-2xl border border-glass bg-white/5 p-5">

      <!-- Recalled banner -->
      <div v-if="batch?.status === 'recalled'"
        class="mb-4 px-4 py-3 bg-red-500/15 border border-red-500/40 rounded-xl text-sm text-red-300 font-semibold">
        ⚠️ Lô hàng này đã bị thu hồi. Vui lòng không sử dụng.
      </div>

      <!-- Private TTL -->
      <div v-if="mode === 'private' && expires_at"
        class="mb-4 px-4 py-2 bg-amber-500/10 border border-amber-500/30 rounded-xl text-xs text-amber-300">
        QR riêng tư — hết hiệu lực lúc {{ new Date(expires_at).toLocaleString('vi-VN') }}
      </div>

      <!-- Product image + name -->
      <div class="flex gap-4 items-start">
        <img v-if="batch?.product?.image_path"
          :src="batch.product.image_path.startsWith('http') ? batch.product.image_path : `/storage/${batch.product.image_path}`"
          class="w-16 h-16 rounded-xl object-cover border border-glass shrink-0" />
        <div class="flex-1 min-w-0">
          <div class="text-[10px] text-white/30 uppercase tracking-wider">
            {{ batch?.product?.category?.name_vi ?? 'Sản phẩm' }}
          </div>
          <h1 class="text-lg font-bold text-white leading-tight mt-0.5">
            {{ batch?.product?.name ?? batch?.product_name }}
          </h1>
          <div v-if="batch?.enterprise?.name" class="text-sm text-white/50 mt-0.5">
            {{ batch.enterprise.name }}
          </div>
        </div>
      </div>

      <!-- Detail grid -->
      <div class="mt-4 grid grid-cols-2 gap-3 text-sm">
        <div v-if="batch?.code">
          <div class="text-[10px] text-white/30 uppercase tracking-wider">Mã lô</div>
          <div class="font-mono text-white/80">{{ batch.code }}</div>
        </div>
        <div v-if="batch?.product?.gtin">
          <div class="text-[10px] text-white/30 uppercase tracking-wider">GTIN</div>
          <div class="font-mono text-white/80">{{ batch.product.gtin }}</div>
        </div>
        <div v-if="batch?.production_date">
          <div class="text-[10px] text-white/30 uppercase tracking-wider">Ngày sản xuất</div>
          <div class="text-white/80">{{ batch.production_date }}</div>
        </div>
        <div v-if="batch?.expiry_date">
          <div class="text-[10px] text-white/30 uppercase tracking-wider">Hạn sử dụng</div>
          <div class="text-white/80">{{ batch.expiry_date }}</div>
        </div>
        <div v-if="batch?.quantity">
          <div class="text-[10px] text-white/30 uppercase tracking-wider">Số lượng</div>
          <div class="text-white/80">{{ batch.quantity }} {{ batch.unit }}</div>
        </div>
        <div v-if="place_name">
          <div class="text-[10px] text-white/30 uppercase tracking-wider">Điểm phát hành</div>
          <div class="text-white/80">{{ place_name }}</div>
        </div>
      </div>
    </div>

    <!-- Events timeline -->
    <div class="rounded-2xl border border-glass bg-white/5 overflow-hidden">
      <div class="px-5 py-4 border-b border-white/5 flex items-center justify-between">
        <div>
          <div class="font-semibold text-white/80 text-sm">Chuỗi sự kiện truy xuất</div>
          <div class="text-xs text-white/40 mt-0.5">
            {{ events.length }} sự kiện
            <span v-if="enterpriseCount > 1"> · {{ enterpriseCount }} doanh nghiệp tham gia</span>
            · TCVN 12850:2019
          </div>
        </div>
        <!-- Tổng số DN badge -->
        <div v-if="enterpriseCount > 1"
          class="text-[10px] px-2 py-1 rounded-full border border-brand-400/30 text-brand-400 bg-brand-400/5 shrink-0">
          {{ enterpriseCount }} DN
        </div>
      </div>

      <div v-if="events.length === 0" class="px-5 py-8 text-center text-sm text-white/30">
        Chưa có sự kiện được công bố.
      </div>

      <div v-for="(ev, idx) in events" :key="ev.id" class="border-b border-white/5 last:border-0">

        <!-- DN separator: hiển thị tên DN khi đổi sang DN mới -->
        <div
          v-if="ev.enterprise?.name && (idx === 0 || ev.enterprise?.code !== events[idx-1]?.enterprise?.code)"
          class="px-5 pt-3 pb-1 flex items-center gap-2">
          <div class="h-px flex-1 bg-white/10"></div>
          <span class="text-[10px] text-white/30 font-medium shrink-0">
            {{ ev.enterprise.name }}
          </span>
          <div class="h-px flex-1 bg-white/10"></div>
        </div>

        <!-- Row header -->
        <button
          class="w-full flex items-center gap-3 px-5 py-4 text-left hover:bg-white/5 transition"
          @click="toggle(ev.id)"
        >
          <!-- Timeline dot -->
          <div class="relative shrink-0 flex flex-col items-center">
            <div class="w-2.5 h-2.5 rounded-full"
              :class="ev.tx_hash ? 'bg-blue-400' : ev.ipfs_cid ? 'bg-green-400' : 'bg-white/20'">
            </div>
          </div>

          <div class="flex-1 min-w-0">
            <div class="font-semibold text-white/80 text-sm">{{ cteLabel(ev.cte_code) }}</div>
            <div class="flex gap-3 text-xs text-white/40 mt-0.5 flex-wrap">
              <span v-if="ev.event_time">{{ ev.event_time }}</span>
              <span v-if="ev.who_name">{{ ev.who_name }}</span>
              <span v-if="ev.where_address" class="truncate max-w-40">📍 {{ ev.where_address }}</span>
            </div>
          </div>

          <!-- Badges -->
          <div class="flex gap-1 shrink-0">
            <span v-if="ev.tx_hash"
              class="text-[10px] text-blue-400 border border-blue-400/30 bg-blue-400/5 px-2 py-0.5 rounded">
              BC
            </span>
            <span v-if="ev.ipfs_cid"
              class="text-[10px] text-green-400 border border-green-400/30 bg-green-400/5 px-2 py-0.5 rounded">
              IPFS
            </span>
          </div>

          <span class="text-white/30 text-xs shrink-0">{{ open === ev.id ? '▲' : '▼' }}</span>
        </button>

        <!-- Detail panel -->
        <div v-if="open === ev.id" class="px-5 pb-5 space-y-3">

          <!-- KDE rows -->
          <div v-if="kdeRows(ev.kde_data).length" class="bg-white/5 rounded-xl p-3 space-y-2">
            <div v-for="row in kdeRows(ev.kde_data)" :key="row.label" class="flex gap-3 text-sm">
              <span class="text-white/40 w-36 shrink-0 text-xs leading-5">{{ row.label }}</span>
              <span class="text-white/80 flex-1">{{ row.value }}</span>
            </div>
          </div>

          <!-- GPS -->
          <a v-if="ev.where_lat && ev.where_lng"
            :href="`https://maps.google.com/?q=${ev.where_lat},${ev.where_lng}`"
            target="_blank"
            class="block text-xs text-brand-400 underline">
            <i class="fi fi-ts-land-layer-location"></i> Xem vị trí trên bản đồ
          </a>

          <!-- Note -->
          <p v-if="ev.note" class="text-xs text-white/50 bg-white/5 rounded-xl px-3 py-2">
            {{ ev.note }}
          </p>

          <!-- Attachments -->
          <div v-if="ev.attachments?.length" class="grid grid-cols-1 gap-2">
            <a v-for="att in ev.attachments" :key="att.cid"
              :href="att.url" target="_blank"
              class="flex items-center gap-2 px-3 py-2 bg-white/5 border border-glass rounded-xl text-xs text-white/60 hover:bg-white/10 transition">
              <span>📎</span>
              <span class="truncate">{{ att.name }}</span>
            </a>
          </div>

          <!-- IPFS block -->
          <div v-if="ev.ipfs_cid"
            class="rounded-xl border border-green-500/20 bg-green-500/5 p-3 space-y-1">
            <p class="text-xs text-green-400 font-semibold">✅ Dữ liệu đã ghi bất biến trên IPFS</p>
            <p class="font-mono text-[10px] text-white/30 break-all">{{ ev.ipfs_cid }}</p>
            <div class="flex gap-3 pt-1 flex-wrap">
              <a v-if="ev.ipfs_url" :href="ev.ipfs_url" target="_blank"
                class="text-xs text-green-400/80 underline">Xem trên IPFS</a>
              <a v-if="ev.content_hash" :href="`/verify/ipfs/${ev.ipfs_cid}?hash=${ev.content_hash}`"
                target="_blank" class="text-xs text-brand-400/80 underline">Xác minh toàn vẹn</a>
            </div>
          </div>

          <!-- Blockchain block -->
          <div v-if="ev.tx_hash"
            class="rounded-xl border border-blue-500/20 bg-blue-500/5 p-3 space-y-1">
            <p class="text-xs text-blue-400 font-semibold">🔗 Đã ghi lên Hyperledger Fabric</p>
            <p class="font-mono text-[10px] text-white/40 break-all">{{ ev.tx_hash }}</p>
          </div>

        </div>
      </div>
    </div>

    <p class="text-center text-xs text-white/20 pb-4">AGU Traceability · TCVN 12850:2019</p>
  </div>
</template>