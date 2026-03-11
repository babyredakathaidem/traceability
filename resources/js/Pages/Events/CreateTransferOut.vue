<script setup>
import { ref, computed } from 'vue'
import { Head, useForm, router } from '@inertiajs/vue3'

const props = defineProps({
  batches:     { type: Array, default: () => [] },
  enterprises: { type: Array, default: () => [] },
})

// ── Form ─────────────────────────────────────────────────────────
const form = useForm({
  input_batch_ids:  [],
  to_enterprise_id: null,
  event_time:       new Date().toISOString().slice(0, 16),
  who_name:         '',
  gs1_document_ref: '',   // AI(400)
  sscc_code:        '',   // AI(00)
  kde_data:         {},
  note:             '',
})

// ── Batch multi-select ────────────────────────────────────────────
const batchSearch       = ref('')
const showBatchDropdown = ref(false)

const filteredBatches = computed(() =>
  props.batches.filter(b =>
    !form.input_batch_ids.includes(b.id) &&
    b.status === 'active' &&
    (b.code.toLowerCase().includes(batchSearch.value.toLowerCase()) ||
     (b.product_name ?? '').toLowerCase().includes(batchSearch.value.toLowerCase()))
  )
)

const selectedBatches = computed(() =>
  props.batches.filter(b => form.input_batch_ids.includes(b.id))
)

function addBatch(batch) {
  form.input_batch_ids.push(batch.id)
  batchSearch.value = ''
}

function removeBatch(id) {
  form.input_batch_ids = form.input_batch_ids.filter(x => x !== id)
}

// ── Receiver enterprise ───────────────────────────────────────────
const receiverSearch = ref('')
const filteredEnterprises = computed(() =>
  props.enterprises.filter(e =>
    (e.name ?? '').toLowerCase().includes(receiverSearch.value.toLowerCase()) ||
    (e.code ?? '').toLowerCase().includes(receiverSearch.value.toLowerCase())
  )
)
const selectedReceiver = computed(() =>
  props.enterprises.find(e => e.id === form.to_enterprise_id)
)

// ── SSCC auto-generate hint ───────────────────────────────────────
function generateSsccHint() {
  // Gợi ý format (thực tế cần backend)
  const ts = Date.now().toString().slice(-6)
  form.sscc_code = `(00)0893000000${ts}0`
}

// ── Submit ────────────────────────────────────────────────────────
function submit() {
  form.post(route('transfer.out'), {
    onSuccess: () => router.visit(route('transfer.pending')),
  })
}

// ── Styles ────────────────────────────────────────────────────────
const inputCls   = 'w-full bg-black/20 border border-white/10 rounded-xl px-3 py-2.5 text-sm text-white/90 placeholder:text-white/30 outline-none focus:border-brand-500/60 transition'
const fieldLabel = 'block text-xs font-medium text-white/50 mb-1.5'
</script>

<template>
  <Head title="Chuyển giao lô hàng" />

  <div class="max-w-2xl mx-auto space-y-6 py-6">

    <!-- Header -->
    <div class="rounded-2xl border border-purple-500/20 bg-purple-500/5 p-6" data-aos="fade-right">
      <div class="text-purple-400 text-sm font-semibold mb-1">Transfer Out — Chuyển giao</div>
      <h1 class="text-2xl font-bold text-white/90">Gửi lô hàng cho đơn vị khác</h1>
      <p class="text-white/40 text-sm mt-1">
        Lô sẽ chuyển sang trạng thái <span class="text-amber-400 font-mono">pending</span> — chờ bên nhận xác nhận.
      </p>
    </div>

    <form @submit.prevent="submit" class="space-y-5" data-aos="fade-up" data-aos-delay="100">

      <!-- ── 1. Chọn lô hàng ── -->
      <div class="rounded-2xl border border-white/10 bg-black/20 p-5 space-y-4">
        <div class="text-[10px] font-semibold text-white/30 uppercase tracking-widest mb-1">
          Lô hàng cần chuyển giao
        </div>

        <!-- Search -->
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
          <div
            v-if="showBatchDropdown && filteredBatches.length"
            class="absolute z-30 top-full mt-1 w-full rounded-xl border border-white/10 bg-[#0d1117] shadow-2xl max-h-60 overflow-y-auto divide-y divide-white/5"
          >
            <button
              v-for="b in filteredBatches.slice(0, 20)"
              :key="b.id"
              type="button"
              @click="addBatch(b)"
              class="w-full px-4 py-2.5 text-left hover:bg-white/5 transition"
            >
              <div class="text-sm font-mono font-medium text-white/80">{{ b.code }}</div>
              <div class="text-xs text-white/40">{{ b.product_name }} · {{ b.current_quantity }} {{ b.unit }}</div>
            </button>
          </div>
        </div>
        <div v-if="form.errors.input_batch_ids" class="text-red-400 text-xs">{{ form.errors.input_batch_ids }}</div>

        <!-- Selected mini cards -->
        <div v-if="selectedBatches.length" class="space-y-2" v-auto-animate>
          <div
            v-for="b in selectedBatches"
            :key="b.id"
            class="rounded-xl border border-purple-500/20 bg-purple-500/5 px-3 py-2.5 flex items-center justify-between gap-3"
          >
            <div class="min-w-0">
              <div class="text-xs font-mono font-semibold text-purple-300 truncate">{{ b.code }}</div>
              <div class="text-xs text-white/40">{{ b.product_name }} · {{ b.current_quantity }} {{ b.unit }}</div>
              <div class="text-xs text-white/30">{{ b.enterprise?.name }}</div>
            </div>
            <button type="button" @click="removeBatch(b.id)" class="text-white/20 hover:text-red-400 transition text-lg shrink-0">×</button>
          </div>
        </div>
      </div>

      <!-- ── 2. Đơn vị nhận ── -->
      <div class="rounded-2xl border border-white/10 bg-black/20 p-5 space-y-4">
        <div class="text-[10px] font-semibold text-white/30 uppercase tracking-widest mb-1">
          Đơn vị nhận hàng
        </div>

        <div>
          <label :class="fieldLabel">Doanh nghiệp nhận <span class="text-red-400">*</span></label>
          <select v-model="form.to_enterprise_id" :class="inputCls">
            <option :value="null">— Chọn đơn vị nhận —</option>
            <option v-for="e in enterprises" :key="e.id" :value="e.id">
              {{ e.name }} ({{ e.code }})
            </option>
          </select>
          <div v-if="form.errors.to_enterprise_id" class="text-red-400 text-xs mt-1">{{ form.errors.to_enterprise_id }}</div>
        </div>

        <!-- Receiver info card -->
        <Transition name="slide-down">
          <div v-if="selectedReceiver" class="rounded-xl border border-white/8 bg-white/5 px-4 py-3 space-y-1">
            <div class="text-xs text-white/30">Đơn vị nhận</div>
            <div class="text-sm font-semibold text-white/80">{{ selectedReceiver.name }}</div>
            <div class="text-xs text-white/40">{{ selectedReceiver.address }} · GLN: {{ selectedReceiver.gln ?? 'Chưa có' }}</div>
          </div>
        </Transition>
      </div>

      <!-- ── 3. Thông tin chuyển giao ── -->
      <div class="rounded-2xl border border-white/10 bg-black/20 p-5 space-y-4">
        <div class="text-[10px] font-semibold text-white/30 uppercase tracking-widest mb-1">
          Thông tin GS1 & chứng từ
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label :class="fieldLabel">Thời gian chuyển giao <span class="text-red-400">*</span></label>
            <input v-model="form.event_time" type="datetime-local" :class="inputCls" />
          </div>
          <div>
            <label :class="fieldLabel">Người thực hiện <span class="text-red-400">*</span></label>
            <input v-model="form.who_name" type="text" :class="inputCls" placeholder="Người ký / chịu trách nhiệm" />
          </div>

          <!-- AI(400) -->
          <div>
            <label :class="fieldLabel">
              Số chứng từ
              <span class="ml-1 text-[10px] text-white/30 font-mono">(AI 400)</span>
            </label>
            <input v-model="form.gs1_document_ref" type="text" :class="inputCls" placeholder="VD: HĐ-2026-0001" />
          </div>

          <!-- AI(00) SSCC -->
          <div>
            <label :class="fieldLabel">
              SSCC Container
              <span class="ml-1 text-[10px] text-white/30 font-mono">(AI 00)</span>
            </label>
            <div class="flex gap-2">
              <input v-model="form.sscc_code" type="text" :class="inputCls" placeholder="18 chữ số" />
              <button
                type="button"
                @click="generateSsccHint"
                class="shrink-0 px-3 py-2 rounded-xl border border-white/10 text-xs text-white/50 hover:bg-white/5 transition whitespace-nowrap"
              >Tự tạo</button>
            </div>
          </div>
        </div>

        <div>
          <label :class="fieldLabel">Ghi chú vận chuyển</label>
          <textarea v-model="form.note" :class="inputCls" rows="2" placeholder="Điều kiện vận chuyển, lưu ý đặc biệt..."></textarea>
        </div>
      </div>

      <!-- ── Summary box ── -->
      <div v-if="selectedBatches.length && selectedReceiver" class="rounded-2xl border border-purple-500/20 bg-purple-500/5 px-5 py-4 space-y-2">
        <div class="text-xs font-semibold text-purple-400">Tóm tắt lệnh chuyển giao</div>
        <div class="text-sm text-white/70">
          Gửi <strong class="text-white/90">{{ selectedBatches.length }} lô</strong>
          → <strong class="text-purple-300">{{ selectedReceiver.name }}</strong>
        </div>
        <div class="text-xs text-white/30">
          Trạng thái sau khi gửi: <span class="text-amber-400 font-mono">pending</span> — chờ
          <span class="text-purple-300">{{ selectedReceiver.name }}</span> xác nhận
        </div>
      </div>

      <!-- ── Actions ── -->
      <div class="flex items-center gap-3">
        <button
          type="submit"
          :disabled="form.processing || !form.input_batch_ids.length || !form.to_enterprise_id"
          class="flex items-center gap-2 px-6 py-2.5 rounded-xl bg-purple-600 hover:bg-purple-500 text-white text-sm font-bold transition disabled:opacity-50"
        >
          <svg v-if="form.processing" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
          <svg v-else class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" /></svg>
          {{ form.processing ? 'Đang gửi...' : 'Gửi lệnh chuyển giao' }}
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
.slide-down-enter-to, .slide-down-leave-from { max-height: 200px; }
</style>
