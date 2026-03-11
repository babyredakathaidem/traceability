<script setup>
import { ref, computed, watch } from 'vue'
import { Head, useForm, router } from '@inertiajs/vue3'
import KdeFields from '@/Components/events/KdeFields.vue'

const props = defineProps({
  batches:      { type: Array, default: () => [] },
  locations:    { type: Array, default: () => [] },
  cte_options:  { type: Array, default: () => [] },
  certificates: { type: Array, default: () => [] },
})

// ── Form ─────────────────────────────────────────────────────────
const form = useForm({
  cte_code:          '',
  event_time:        new Date().toISOString().slice(0, 16),
  who_name:          '',
  trace_location_id: null,
  input_batch_ids:   [],
  kde_data:          {},
  why_reason:        '',
  note:              '',
  has_certification: false,
  certification: {
    certificate_id: null,
    result:         '',
    reference_no:   '',
    issued_date:    '',
    expiry_date:    '',
  },
})

// ── CTE options ───────────────────────────────────────────────────
const DEFAULT_CTE = [
  { code: 'planting',   label: '🌱 Gieo hạt / Trồng' },
  { code: 'growing',    label: '🧴 Bón phân / Chăm sóc' },
  { code: 'spraying',   label: '💦 Phun thuốc BVTV' },
  { code: 'harvesting', label: '🚜 Thu hoạch' },
  { code: 'storage',    label: '🏪 Lưu kho' },
  { code: 'inspection', label: '🔍 Kiểm định / Kiểm nghiệm' },
  { code: 'shipping',   label: '🚚 Vận chuyển' },
  { code: 'custom',     label: '📝 Sự kiện tuỳ chỉnh' },
]
const cteOptions = computed(() => props.cte_options?.length ? props.cte_options : DEFAULT_CTE)

// ── Batch multi-select ────────────────────────────────────────────
const batchSearch     = ref('')
const showBatchDropdown = ref(false)

const filteredBatches = computed(() =>
  props.batches.filter(b =>
    !form.input_batch_ids.includes(b.id) &&
    (b.code.toLowerCase().includes(batchSearch.value.toLowerCase()) ||
     (b.product_name ?? '').toLowerCase().includes(batchSearch.value.toLowerCase()))
  )
)

const selectedBatches = computed(() =>
  props.batches.filter(b => form.input_batch_ids.includes(b.id))
)

function toggleBatch(batch) {
  const idx = form.input_batch_ids.indexOf(batch.id)
  if (idx >= 0) form.input_batch_ids.splice(idx, 1)
  else form.input_batch_ids.push(batch.id)
}

function removeBatch(id) {
  form.input_batch_ids = form.input_batch_ids.filter(x => x !== id)
}

// ── Location search ───────────────────────────────────────────────
const locationSearch = ref('')
const filteredLocations = computed(() =>
  props.locations.filter(l =>
    (l.name ?? '').toLowerCase().includes(locationSearch.value.toLowerCase()) ||
    (l.gln ?? '').includes(locationSearch.value)
  )
)

// ── Submit ────────────────────────────────────────────────────────
function submit() {
  form.post(route('observation-events.store'), {
    onSuccess: () => router.visit(route('events.index')),
  })
}

// ── Styles ────────────────────────────────────────────────────────
const inputCls = 'w-full bg-black/20 border border-white/10 rounded-xl px-3 py-2.5 text-sm text-white/90 placeholder:text-white/30 outline-none focus:border-brand-500/60 transition'
const sectionLabel = 'text-[10px] font-semibold text-white/30 uppercase tracking-widest mb-3'
const fieldLabel = 'block text-xs font-medium text-white/50 mb-1.5'
</script>

<template>
  <Head title="Tạo sự kiện quan sát" />

  <div class="max-w-3xl mx-auto space-y-6 py-6">

    <!-- Header -->
    <div class="rounded-2xl border border-white/10 bg-black/40 p-6" data-aos="fade-right">
      <div class="text-brand-300 text-sm font-semibold mb-1">Sự kiện quan sát</div>
      <h1 class="text-2xl font-bold text-white/90">Ghi nhận hoạt động sản xuất</h1>
      <p class="text-white/40 text-sm mt-1">Observation event — ghi nhận trạng thái, không thay đổi lô hàng.</p>
    </div>

    <!-- Form card -->
    <form @submit.prevent="submit" class="space-y-5" data-aos="fade-up" data-aos-delay="100">

      <!-- ── 1. Loại sự kiện ── -->
      <div class="rounded-2xl border border-white/10 bg-black/20 p-5 space-y-4">
        <div :class="sectionLabel">
          <span class="inline-flex items-center gap-1.5">
            <svg class="w-3.5 h-3.5 text-brand-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" /></svg>
            Loại công đoạn (CTE)
          </span>
        </div>
        <div>
          <label :class="fieldLabel">Loại sự kiện <span class="text-red-400">*</span></label>
          <select v-model="form.cte_code" :class="inputCls">
            <option value="">— Chọn loại sự kiện —</option>
            <option v-for="opt in cteOptions" :key="opt.code" :value="opt.code">{{ opt.label ?? opt.name_vi }}</option>
          </select>
          <div v-if="form.errors.cte_code" class="text-red-400 text-xs mt-1">{{ form.errors.cte_code }}</div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label :class="fieldLabel">Thời gian thực hiện <span class="text-red-400">*</span></label>
            <input v-model="form.event_time" type="datetime-local" :class="inputCls" />
            <div v-if="form.errors.event_time" class="text-red-400 text-xs mt-1">{{ form.errors.event_time }}</div>
          </div>
          <div>
            <label :class="fieldLabel">Người thực hiện <span class="text-red-400">*</span></label>
            <input v-model="form.who_name" type="text" :class="inputCls" placeholder="Họ tên người thực hiện" />
            <div v-if="form.errors.who_name" class="text-red-400 text-xs mt-1">{{ form.errors.who_name }}</div>
          </div>
        </div>
      </div>

      <!-- ── 2. Lô hàng đầu vào ── -->
      <div class="rounded-2xl border border-white/10 bg-black/20 p-5 space-y-4">
        <div :class="sectionLabel">
          <span class="inline-flex items-center gap-1.5">
            <svg class="w-3.5 h-3.5 text-brand-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10" /></svg>
            Lô hàng áp dụng
          </span>
        </div>

        <!-- Search input -->
        <div class="relative">
          <label :class="fieldLabel">Chọn lô hàng <span class="text-red-400">*</span></label>
          <input
            v-model="batchSearch"
            type="text"
            :class="inputCls"
            placeholder="Tìm theo mã lô hoặc tên sản phẩm..."
            @focus="showBatchDropdown = true"
            @blur="setTimeout(() => showBatchDropdown = false, 200)"
          />
          <!-- Dropdown -->
          <div
            v-if="showBatchDropdown && filteredBatches.length"
            class="absolute z-30 top-full mt-1 w-full rounded-xl border border-white/10 bg-[#0d1117] shadow-2xl max-h-60 overflow-y-auto divide-y divide-white/5"
          >
            <button
              v-for="b in filteredBatches.slice(0, 20)"
              :key="b.id"
              type="button"
              @click="toggleBatch(b)"
              class="w-full px-4 py-2.5 text-left hover:bg-white/5 transition"
            >
              <div class="text-sm font-medium text-white/80 font-mono">{{ b.code }}</div>
              <div class="text-xs text-white/40 mt-0.5">{{ b.product_name }} · {{ b.current_quantity }} {{ b.unit }} · {{ b.status }}</div>
            </button>
          </div>
        </div>
        <div v-if="form.errors.input_batch_ids" class="text-red-400 text-xs">{{ form.errors.input_batch_ids }}</div>

        <!-- Selected batch mini cards -->
        <div v-if="selectedBatches.length" class="grid grid-cols-1 sm:grid-cols-2 gap-2" v-auto-animate>
          <div
            v-for="b in selectedBatches"
            :key="b.id"
            class="rounded-xl border border-brand-500/20 bg-brand-500/5 px-3 py-2.5 flex items-start justify-between gap-2"
          >
            <div class="min-w-0">
              <div class="text-xs font-mono font-semibold text-brand-300 truncate">{{ b.code }}</div>
              <div class="text-xs text-white/50 truncate">{{ b.product_name }}</div>
              <div class="text-xs text-white/30 mt-0.5">{{ b.current_quantity }} {{ b.unit }} · {{ b.enterprise?.name ?? b.enterprise_name }}</div>
            </div>
            <button type="button" @click="removeBatch(b.id)" class="text-white/20 hover:text-red-400 transition text-lg shrink-0 leading-none mt-0.5">×</button>
          </div>
        </div>
      </div>

      <!-- ── 3. Địa điểm ── -->
      <div class="rounded-2xl border border-white/10 bg-black/20 p-5 space-y-4">
        <div :class="sectionLabel">
          <span class="inline-flex items-center gap-1.5">
            <svg class="w-3.5 h-3.5 text-brand-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
            Địa điểm thực hiện (WHERE)
          </span>
        </div>
        <div>
          <label :class="fieldLabel">Địa điểm truy vết (tùy chọn)</label>
          <select v-model="form.trace_location_id" :class="inputCls">
            <option :value="null">— Không chọn —</option>
            <option v-for="loc in locations" :key="loc.id" :value="loc.id">
              ({{ loc.ai_type }}) {{ loc.name }} — {{ loc.gln ?? 'Chưa có GLN' }}
            </option>
          </select>
        </div>
        <div>
          <label :class="fieldLabel">Lý do / Mục đích (WHY)</label>
          <input v-model="form.why_reason" type="text" :class="inputCls" placeholder="VD: Theo quy trình VietGAP, kiểm tra định kỳ..." />
        </div>
      </div>

      <!-- ── 4. KDE Data ── -->
      <div v-if="form.cte_code" class="rounded-2xl border border-white/10 bg-black/20 p-5 space-y-4">
        <div :class="sectionLabel">
          <span class="inline-flex items-center gap-1.5">
            <svg class="w-3.5 h-3.5 text-brand-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
            Dữ liệu KDE — {{ form.cte_code }}
          </span>
        </div>
        <KdeFields v-model="form.kde_data" :cte-code="form.cte_code" />
      </div>

      <!-- ── 5. Chứng nhận ── -->
      <div class="rounded-2xl border border-white/10 bg-black/20 p-5 space-y-4">
        <div :class="sectionLabel">
          <span class="inline-flex items-center gap-1.5">
            <svg class="w-3.5 h-3.5 text-brand-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z" /></svg>
            Chứng nhận / Kiểm tra chất lượng
          </span>
        </div>

        <!-- Toggle has_certification -->
        <label class="flex items-center gap-3 cursor-pointer group">
          <div class="relative shrink-0">
            <input v-model="form.has_certification" type="checkbox" class="sr-only peer" />
            <div class="w-10 h-5 rounded-full bg-white/10 peer-checked:bg-brand-500 transition-colors"></div>
            <div class="absolute left-0.5 top-0.5 w-4 h-4 rounded-full bg-white transition-transform peer-checked:translate-x-5 shadow"></div>
          </div>
          <span class="text-sm text-white/60 group-hover:text-white/80 transition">Sự kiện này có kèm chứng nhận / kết quả kiểm tra</span>
        </label>

        <!-- Certification fields -->
        <Transition name="slide-down">
          <div v-if="form.has_certification" class="grid grid-cols-1 md:grid-cols-2 gap-3 pt-1">
            <div class="md:col-span-2">
              <label :class="fieldLabel">Chứng chỉ áp dụng <span class="text-red-400">*</span></label>
              <select v-model="form.certification.certificate_id" :class="inputCls">
                <option :value="null">— Chọn chứng chỉ —</option>
                <option v-for="c in certificates" :key="c.id" :value="c.id">{{ c.name }} — {{ c.organization }}</option>
              </select>
            </div>
            <div>
              <label :class="fieldLabel">Kết quả <span class="text-red-400">*</span></label>
              <select v-model="form.certification.result" :class="inputCls">
                <option value="">— Chọn kết quả —</option>
                <option value="pass">✅ Đạt</option>
                <option value="fail">❌ Không đạt</option>
                <option value="conditional">⚠️ Có điều kiện</option>
              </select>
            </div>
            <div>
              <label :class="fieldLabel">Số tham chiếu / Số phiếu</label>
              <input v-model="form.certification.reference_no" type="text" :class="inputCls" placeholder="VD: PKKT-2026-001" />
            </div>
            <div>
              <label :class="fieldLabel">Ngày cấp</label>
              <input v-model="form.certification.issued_date" type="date" :class="inputCls" />
            </div>
            <div>
              <label :class="fieldLabel">Ngày hết hạn</label>
              <input v-model="form.certification.expiry_date" type="date" :class="inputCls" />
            </div>
          </div>
        </Transition>
      </div>

      <!-- ── 6. Ghi chú ── -->
      <div class="rounded-2xl border border-white/10 bg-black/20 p-5">
        <label :class="fieldLabel">Ghi chú thêm</label>
        <textarea v-model="form.note" :class="inputCls" rows="3" placeholder="Thông tin bổ sung..."></textarea>
      </div>

      <!-- ── Actions ── -->
      <div class="flex items-center gap-3">
        <button
          type="submit"
          :disabled="form.processing"
          class="flex items-center gap-2 px-6 py-2.5 rounded-xl bg-brand-500 hover:bg-brand-400 text-black text-sm font-bold transition disabled:opacity-50"
        >
          <svg v-if="form.processing" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
          <svg v-else class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
          {{ form.processing ? 'Đang lưu...' : 'Lưu sự kiện' }}
        </button>
        <button
          type="button"
          @click="router.visit(route('events.index'))"
          class="px-5 py-2.5 rounded-xl border border-white/10 text-white/50 hover:bg-white/5 text-sm transition"
        >Huỷ bỏ</button>
        <div v-if="Object.keys(form.errors).length" class="ml-2 text-xs text-red-400">
          Có {{ Object.keys(form.errors).length }} lỗi cần sửa.
        </div>
      </div>

    </form>
  </div>
</template>

<style scoped>
.slide-down-enter-active, .slide-down-leave-active {
  transition: all 0.25s ease;
  overflow: hidden;
}
.slide-down-enter-from, .slide-down-leave-to {
  opacity: 0;
  max-height: 0;
}
.slide-down-enter-to, .slide-down-leave-from {
  max-height: 600px;
}
</style>
