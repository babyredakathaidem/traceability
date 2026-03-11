<script setup>
import { ref, computed, watch } from 'vue'
import { Head, useForm, router } from '@inertiajs/vue3'
import KdeFields from '@/Components/events/KdeFields.vue'

const props = defineProps({
  batches:     { type: Array, default: () => [] },
  locations:   { type: Array, default: () => [] },
  products:    { type: Array, default: () => [] },
  cte_options: { type: Array, default: () => [] },
})

// ── Form ─────────────────────────────────────────────────────────
const form = useForm({
  cte_code:               '',
  event_time:             new Date().toISOString().slice(0, 16),
  who_name:               '',
  trace_location_id:      null,
  input_batches:          [],   // [{ id, quantity, unit }]
  kde_data:               {},
  why_reason:             '',
  note:                   '',
  output_product_id:      null,
  output_quantity:        '',
  output_unit:            '',
  output_batch_type:      'finished',
  output_production_date: '',
  output_expiry_date:     '',
})

// ── CTE options ───────────────────────────────────────────────────
const DEFAULT_CTE = [
  { code: 'harvesting',   label: '🚜 Thu hoạch' },
  { code: 'milling',      label: '⚙️ Xay xát / Chế biến' },
  { code: 'packaging',    label: '📦 Đóng gói' },
  { code: 'split',        label: '✂️ Tách lô' },
  { code: 'merge',        label: '🔀 Gộp lô' },
  { code: 'processing',   label: '🏭 Sản xuất / Biến đổi' },
]
const cteOptions = computed(() => props.cte_options?.length ? props.cte_options : DEFAULT_CTE)

// ── Batch multi-select ────────────────────────────────────────────
const batchSearch       = ref('')
const showBatchDropdown = ref(false)

const selectedBatchIds = computed(() => form.input_batches.map(b => b.id))

const filteredBatches = computed(() =>
  props.batches.filter(b =>
    !selectedBatchIds.value.includes(b.id) &&
    (b.code.toLowerCase().includes(batchSearch.value.toLowerCase()) ||
     (b.product_name ?? '').toLowerCase().includes(batchSearch.value.toLowerCase()))
  )
)

function addBatch(batch) {
  form.input_batches.push({
    id:       batch.id,
    quantity: batch.current_quantity ?? batch.quantity,
    unit:     batch.unit,
    _meta:    batch,   // for display (not sent)
  })
  batchSearch.value = ''
}

function removeBatch(idx) {
  form.input_batches.splice(idx, 1)
}

const totalInputQty = computed(() =>
  form.input_batches.reduce((sum, b) => sum + Number(b.quantity || 0), 0)
)

// ── Output product preview ────────────────────────────────────────
const selectedProduct = computed(() =>
  props.products.find(p => p.id === form.output_product_id)
)

const outputBatchPreview = computed(() => {
  if (!form.output_product_id || !form.output_quantity) return null
  const p = selectedProduct.value
  return {
    product: p?.name ?? '—',
    quantity: `${form.output_quantity} ${form.output_unit}`,
    type: form.output_batch_type,
  }
})

// ── Submit ────────────────────────────────────────────────────────
function submit() {
  // Strip _meta before sending
  const payload = {
    ...form.data(),
    input_batches: form.input_batches.map(({ id, quantity, unit }) => ({ id, quantity, unit })),
  }
  form.transform(() => payload).post(route('transformation-events.store'), {
    onSuccess: () => router.visit(route('batches.index')),
  })
}

// ── Styles ────────────────────────────────────────────────────────
const inputCls    = 'w-full bg-black/20 border border-white/10 rounded-xl px-3 py-2.5 text-sm text-white/90 placeholder:text-white/30 outline-none focus:border-brand-500/60 transition'
const sectionLabel = 'text-[10px] font-semibold text-white/30 uppercase tracking-widest mb-3'
const fieldLabel  = 'block text-xs font-medium text-white/50 mb-1.5'
</script>

<template>
  <Head title="Tạo sự kiện chế biến" />

  <div class="max-w-3xl mx-auto space-y-6 py-6">

    <!-- Header -->
    <div class="rounded-2xl border border-orange-500/20 bg-orange-500/5 p-6" data-aos="fade-right">
      <div class="text-orange-400 text-sm font-semibold mb-1">Sự kiện biến đổi / Chế biến</div>
      <h1 class="text-2xl font-bold text-white/90">Tạo sự kiện Transformation</h1>
      <p class="text-white/40 text-sm mt-1">Tiêu thụ lô input → sinh lô output mới theo chuẩn EPCIS TT02.</p>
    </div>

    <!-- ⚠️ Warning banner -->
    <div class="rounded-2xl border border-amber-500/30 bg-amber-500/5 px-5 py-3 flex gap-3 items-start" data-aos="fade-up">
      <svg class="w-5 h-5 text-amber-400 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
      </svg>
      <div>
        <div class="text-amber-400 text-sm font-semibold">Lưu ý quan trọng</div>
        <div class="text-amber-400/70 text-xs mt-0.5">
          Các lô input sẽ bị <strong>tiêu thụ (consumed)</strong> ngay sau khi sự kiện được tạo.
          Sau khi publish lên IPFS, thao tác này <strong>không thể hoàn tác</strong>.
        </div>
      </div>
    </div>

    <form @submit.prevent="submit" class="space-y-5" data-aos="fade-up" data-aos-delay="100">

      <!-- ── 1. CTE + Thời gian ── -->
      <div class="rounded-2xl border border-white/10 bg-black/20 p-5 space-y-4">
        <div :class="sectionLabel">Loại công đoạn & thời gian</div>
        <div>
          <label :class="fieldLabel">Loại sự kiện chế biến <span class="text-red-400">*</span></label>
          <select v-model="form.cte_code" :class="inputCls">
            <option value="">— Chọn loại chế biến —</option>
            <option v-for="opt in cteOptions" :key="opt.code" :value="opt.code">{{ opt.label }}</option>
          </select>
          <div v-if="form.errors.cte_code" class="text-red-400 text-xs mt-1">{{ form.errors.cte_code }}</div>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label :class="fieldLabel">Thời gian <span class="text-red-400">*</span></label>
            <input v-model="form.event_time" type="datetime-local" :class="inputCls" />
          </div>
          <div>
            <label :class="fieldLabel">Người thực hiện <span class="text-red-400">*</span></label>
            <input v-model="form.who_name" type="text" :class="inputCls" placeholder="Họ tên / đơn vị thực hiện" />
          </div>
        </div>
      </div>

      <!-- ── 2. Lô đầu vào ── -->
      <div class="rounded-2xl border border-red-500/20 bg-red-500/5 p-5 space-y-4">
        <div class="text-[10px] font-semibold text-red-400/60 uppercase tracking-widest mb-1">
          LÔ ĐẦU VÀO — SẼ BỊ TIÊU THỤ
        </div>

        <!-- Search -->
        <div class="relative">
          <input
            v-model="batchSearch"
            type="text"
            :class="inputCls"
            placeholder="Tìm lô theo mã hoặc tên sản phẩm..."
            @focus="showBatchDropdown = true"
            @blur="setTimeout(() => showBatchDropdown = false, 200)"
          />
          <div
            v-if="showBatchDropdown && filteredBatches.length"
            class="absolute z-30 top-full mt-1 w-full rounded-xl border border-white/10 bg-[#0d1117] shadow-2xl max-h-48 overflow-y-auto divide-y divide-white/5"
          >
            <button
              v-for="b in filteredBatches.slice(0, 15)"
              :key="b.id"
              type="button"
              @click="addBatch(b)"
              class="w-full px-4 py-2.5 text-left hover:bg-white/5 transition"
            >
              <div class="text-sm font-mono font-medium text-white/80">{{ b.code }}</div>
              <div class="text-xs text-white/40">{{ b.product_name }} · {{ b.current_quantity }} {{ b.unit }} · <span class="text-green-400">{{ b.status }}</span></div>
            </button>
          </div>
        </div>
        <div v-if="form.errors.input_batches" class="text-red-400 text-xs">{{ form.errors.input_batches }}</div>

        <!-- Selected batches với qty edit -->
        <div v-if="form.input_batches.length" class="space-y-2" v-auto-animate>
          <div
            v-for="(item, idx) in form.input_batches"
            :key="item.id"
            class="rounded-xl border border-red-500/20 bg-red-500/5 px-3 py-2.5 grid grid-cols-[1fr_auto_auto_auto] gap-2 items-center"
          >
            <div class="min-w-0">
              <div class="text-xs font-mono font-semibold text-red-300">{{ item._meta?.code ?? item.id }}</div>
              <div class="text-xs text-white/40 truncate">{{ item._meta?.product_name }}</div>
            </div>
            <input v-model="item.quantity" type="number" min="0.001" step="0.001"
              class="w-20 bg-black/30 border border-white/10 rounded-lg px-2 py-1 text-xs text-white/80 text-right outline-none focus:border-brand-500/40" />
            <span class="text-xs text-white/40">{{ item._meta?.unit ?? item.unit }}</span>
            <button type="button" @click="removeBatch(idx)" class="text-white/20 hover:text-red-400 transition text-lg leading-none">×</button>
          </div>
          <div class="text-xs text-white/30 text-right">Tổng input: {{ totalInputQty.toFixed(3) }}</div>
        </div>
      </div>

      <!-- ── 3. Lô output mới ── -->
      <div class="rounded-2xl border border-brand-500/20 bg-brand-500/5 p-5 space-y-4">
        <div class="text-[10px] font-semibold text-brand-400/60 uppercase tracking-widest mb-1">
          LÔ OUTPUT — TỰ ĐỘNG SINH SAU SỰ KIỆN
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div class="md:col-span-2">
            <label :class="fieldLabel">Sản phẩm output <span class="text-red-400">*</span></label>
            <select v-model="form.output_product_id" :class="inputCls">
              <option :value="null">— Chọn sản phẩm —</option>
              <option v-for="p in products" :key="p.id" :value="p.id">{{ p.name }} ({{ p.gtin }})</option>
            </select>
            <div v-if="form.errors.output_product_id" class="text-red-400 text-xs mt-1">{{ form.errors.output_product_id }}</div>
          </div>
          <div>
            <label :class="fieldLabel">Số lượng <span class="text-red-400">*</span></label>
            <input v-model="form.output_quantity" type="number" min="0.001" step="0.001" :class="inputCls" placeholder="0.000" />
          </div>
          <div>
            <label :class="fieldLabel">Đơn vị <span class="text-red-400">*</span></label>
            <select v-model="form.output_unit" :class="inputCls">
              <option value="">— Đơn vị —</option>
              <option v-for="u in ['kg', 'tấn', 'thùng', 'bao', 'lít', 'cái', 'chiếc']" :key="u" :value="u">{{ u }}</option>
            </select>
          </div>
          <div>
            <label :class="fieldLabel">Loại lô <span class="text-red-400">*</span></label>
            <select v-model="form.output_batch_type" :class="inputCls">
              <option value="raw_material">📦 Nguyên liệu thô</option>
              <option value="wip">⚙️ Bán thành phẩm (WIP)</option>
              <option value="finished">✅ Thành phẩm</option>
            </select>
          </div>
          <div>
            <label :class="fieldLabel">Ngày sản xuất</label>
            <input v-model="form.output_production_date" type="date" :class="inputCls" />
          </div>
          <div>
            <label :class="fieldLabel">Hạn sử dụng</label>
            <input v-model="form.output_expiry_date" type="date" :class="inputCls" />
          </div>
        </div>

        <!-- Preview lô output -->
        <Transition name="slide-down">
          <div v-if="outputBatchPreview" class="rounded-xl border border-brand-500/20 bg-black/20 px-4 py-3 space-y-1">
            <div class="text-[10px] text-brand-400/60 uppercase tracking-widest">Xem trước lô sinh ra</div>
            <div class="text-sm text-white/80">
              Sự kiện <span class="text-brand-300 font-mono">{{ form.cte_code || '...' }}</span>
              sẽ tiêu thụ <strong class="text-red-400">{{ form.input_batches.length }} lô input</strong>
              và tạo ra lô <span class="text-brand-300">{{ outputBatchPreview.product }}</span>
              với <strong class="text-white/90">{{ outputBatchPreview.quantity }}</strong>.
            </div>
          </div>
        </Transition>
      </div>

      <!-- ── 4. KDE Data ── -->
      <div v-if="form.cte_code" class="rounded-2xl border border-white/10 bg-black/20 p-5 space-y-4">
        <div :class="sectionLabel">Dữ liệu KDE — {{ form.cte_code }}</div>
        <KdeFields v-model="form.kde_data" :cte-code="form.cte_code" />
      </div>

      <!-- ── 5. Địa điểm + Ghi chú ── -->
      <div class="rounded-2xl border border-white/10 bg-black/20 p-5 space-y-4">
        <div :class="sectionLabel">Địa điểm & bổ sung</div>
        <div>
          <label :class="fieldLabel">Địa điểm thực hiện</label>
          <select v-model="form.trace_location_id" :class="inputCls">
            <option :value="null">— Không chọn —</option>
            <option v-for="loc in locations" :key="loc.id" :value="loc.id">
              ({{ loc.ai_type }}) {{ loc.name }}
            </option>
          </select>
        </div>
        <div>
          <label :class="fieldLabel">Ghi chú</label>
          <textarea v-model="form.note" :class="inputCls" rows="2" placeholder="Tuỳ chọn..."></textarea>
        </div>
      </div>

      <!-- ── Actions ── -->
      <div class="flex items-center gap-3">
        <button
          type="submit"
          :disabled="form.processing || !form.input_batches.length"
          class="flex items-center gap-2 px-6 py-2.5 rounded-xl bg-orange-500 hover:bg-orange-400 text-black text-sm font-bold transition disabled:opacity-50"
        >
          <svg v-if="form.processing" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
          {{ form.processing ? 'Đang tạo...' : 'Tạo sự kiện + Lô output' }}
        </button>
        <button type="button" @click="router.visit(route('batches.index'))"
          class="px-5 py-2.5 rounded-xl border border-white/10 text-white/50 hover:bg-white/5 text-sm transition">
          Huỷ bỏ
        </button>
      </div>

    </form>
  </div>
</template>

<style scoped>
.slide-down-enter-active, .slide-down-leave-active { transition: all .2s ease; overflow: hidden; }
.slide-down-enter-from, .slide-down-leave-to { opacity: 0; max-height: 0; }
.slide-down-enter-to, .slide-down-leave-from { max-height: 400px; }
</style>
