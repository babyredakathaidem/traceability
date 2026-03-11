<script setup>
import { Head, useForm } from '@inertiajs/vue3'
import { computed, ref } from 'vue'
import UiCard   from '@/Components/ui/UiCard.vue'
import UiButton from '@/Components/ui/UiButton.vue'
import UiInput  from '@/Components/ui/UiInput.vue'

const props = defineProps({
  availableBatches: { type: Array, default: () => [] },
  products:         { type: Array, default: () => [] },
})

const selectedIds = ref([])

const form = useForm({
  input_batch_ids:  [],
  output_product_id: null,
  loss_rate:         0,
  note:              '',
})

// ── Computed ──────────────────────────────────────────────

const selectedBatches = computed(() =>
  props.availableBatches.filter(b => selectedIds.value.includes(b.id))
)

const totalInputQty = computed(() =>
  selectedBatches.value.reduce((s, b) => s + (b.current_quantity ?? 0), 0)
)

const outputQty = computed(() => {
  const rate = parseFloat(form.loss_rate) || 0
  return Math.round(totalInputQty.value * (1 - rate / 100) * 100) / 100
})

// Đơn vị
const commonUnit = computed(() => {
  const units = [...new Set(selectedBatches.value.map(b => b.unit).filter(Boolean))]
  return units.length === 1 ? units[0] : ''
})

// ── Kiểm tra chứng chỉ ────────────────────────────────────
const certSets = computed(() =>
  selectedBatches.value.map(b => (b.certifications ?? []).slice().sort().join(','))
)

const certsMatch = computed(() => {
  if (selectedBatches.value.length < 1) return true
  const first = certSets.value[0]
  return certSets.value.every(c => c === first)
})

const commonCerts = computed(() => {
  if (selectedBatches.value.length < 1) return []
  return selectedBatches.value
    .map(b => b.certifications ?? [])
    .reduce((a, b) => a.filter(c => b.includes(c)), selectedBatches.value[0]?.certifications ?? [])
})

const certError = computed(() => {
  if (selectedBatches.value.length < 1) return null
  if (!certsMatch.value)
    return 'Các lô phải có CÙNG chứng chỉ/tiêu chuẩn mới được chế biến chung.'
  return null
})

// Sản phẩm đầu ra đã chọn
const selectedProduct = computed(() =>
  props.products.find(p => p.id === form.output_product_id)
)

const canSubmit = computed(() =>
  !form.processing &&
  selectedIds.value.length >= 1 &&
  certsMatch.value &&
  form.output_product_id &&
  parseFloat(form.loss_rate) >= 0 &&
  parseFloat(form.loss_rate) < 100 &&
  outputQty.value > 0
)

// ── Methods ───────────────────────────────────────────────

function toggleBatch(batch) {
  const idx = selectedIds.value.indexOf(batch.id)
  if (idx >= 0) selectedIds.value.splice(idx, 1)
  else selectedIds.value.push(batch.id)
}

function submit() {
  form.input_batch_ids = [...selectedIds.value]
  form.post(route('batches.transform.store'))
}
</script>

<template>
  <Head title="Chế biến nguyên liệu" />

  <div class="max-w-2xl mx-auto space-y-6">

    <!-- Header -->
    <div class="rounded-2xl border border-glass bg-black/40 p-6">
      <div class="text-brand-300 text-sm font-semibold">Lô hàng</div>
      <div class="text-2xl font-bold mt-1 text-white/90">Chế biến nguyên liệu → Thành phẩm</div>
      <div class="text-white/50 text-sm mt-1">
        Chọn lô nguyên liệu (cùng chứng chỉ) → nhập tỷ lệ hao hụt → hệ thống tự tạo lô thành phẩm mới.
      </div>
    </div>

    <!-- ── Bước 1: Chọn lô nguyên liệu ──────────────────── -->
    <UiCard title="1. Chọn lô nguyên liệu">
      <div class="space-y-2 max-h-80 overflow-y-auto pr-1">
        <button
          v-for="b in availableBatches"
          :key="b.id"
          type="button"
          @click="toggleBatch(b)"
          class="w-full flex items-start justify-between p-3 rounded-xl border transition text-left"
          :class="selectedIds.includes(b.id)
            ? 'border-brand-500/60 bg-brand-500/10'
            : 'border-glass bg-white/5 hover:bg-white/8'"
        >
          <div class="space-y-1.5 flex-1 min-w-0">
            <div class="flex items-center gap-2">
              <span class="font-mono text-brand-300 font-bold text-sm">{{ b.code }}</span>
              <span v-if="selectedIds.includes(b.id)" class="text-xs text-brand-300">✓</span>
            </div>
            <div class="text-white/60 text-xs">{{ b.product_name }}</div>

            <!-- Chứng chỉ -->
            <div v-if="b.certifications?.length" class="flex flex-wrap gap-1 mt-1">
              <span
                v-for="cert in b.certifications"
                :key="cert"
                class="text-[10px] px-1.5 py-0.5 rounded border"
                :class="selectedIds.includes(b.id) && commonCerts.includes(cert)
                  ? 'border-green-500/40 bg-green-500/10 text-green-400'
                  : 'border-brand-500/30 bg-brand-500/10 text-brand-300'"
              >{{ cert }}</span>
            </div>
            <div v-else class="text-[10px] text-white/25 italic">Chưa có chứng chỉ</div>
          </div>

          <div class="text-right shrink-0 ml-4 pt-0.5">
            <div class="text-white/80 text-sm font-bold">{{ b.current_quantity }}</div>
            <div class="text-white/40 text-xs">{{ b.unit }}</div>
          </div>
        </button>

        <div v-if="!availableBatches.length" class="text-white/40 text-sm text-center py-6">
          Không có lô nguyên liệu nào khả dụng.
        </div>
      </div>

      <!-- Cảnh báo chứng chỉ không trùng -->
      <div v-if="certError"
        class="mt-3 p-3 rounded-xl bg-red-500/10 border border-red-500/30 text-sm text-red-400"
      >🚫 {{ certError }}</div>

      <!-- Error từ server -->
      <div v-if="form.errors.input_batch_ids" class="mt-2 text-sm text-red-400">
        {{ form.errors.input_batch_ids }}
      </div>
    </UiCard>

    <!-- ── Bước 2: Tỷ lệ hao hụt & Sản phẩm đầu ra ────── -->
    <UiCard title="2. Thông tin chế biến">
      <div class="space-y-5">

        <!-- Sản phẩm đầu ra -->
        <div>
          <label class="block text-xs text-white/50 mb-1.5">Sản phẩm đầu ra *</label>
          <select
            v-model.number="form.output_product_id"
            class="w-full rounded-xl border border-white/10 bg-black/30 px-3 py-2.5 text-sm text-white/90 focus:border-brand-500/60 focus:ring-brand-500/20 transition"
          >
            <option :value="null" disabled>Chọn sản phẩm thành phẩm...</option>
            <option v-for="p in products" :key="p.id" :value="p.id">
              {{ p.name }} {{ p.gtin ? `(${p.gtin})` : '' }}
            </option>
          </select>
          <div v-if="form.errors.output_product_id" class="text-sm text-red-400 mt-1">
            {{ form.errors.output_product_id }}
          </div>
        </div>

        <!-- Tỷ lệ hao hụt + tính toán -->
        <div class="grid grid-cols-3 gap-4">
          <div>
            <label class="block text-xs text-white/50 mb-1.5">Tổng nguyên liệu</label>
            <div class="w-full rounded-xl border border-white/10 bg-white/5 px-3 py-2.5 text-sm text-white/90 font-bold">
              {{ selectedBatches.length ? `${totalInputQty} ${commonUnit}` : '—' }}
            </div>
          </div>
          <div>
            <label class="block text-xs text-white/50 mb-1.5">Tỷ lệ hao hụt (%) *</label>
            <input
              v-model.number="form.loss_rate"
              type="number"
              step="0.1"
              min="0"
              max="99.99"
              class="w-full rounded-xl border border-white/10 bg-black/30 px-3 py-2.5 text-sm text-white/90 font-bold focus:border-brand-500/60 focus:ring-brand-500/20 transition"
              placeholder="VD: 20"
            />
            <div v-if="form.errors.loss_rate" class="text-sm text-red-400 mt-1">
              {{ form.errors.loss_rate }}
            </div>
          </div>
          <div>
            <label class="block text-xs text-white/50 mb-1.5">Sản lượng thành phẩm</label>
            <div
              class="w-full rounded-xl border px-3 py-2.5 text-sm font-bold"
              :class="outputQty > 0
                ? 'border-green-500/40 bg-green-500/10 text-green-400'
                : 'border-white/10 bg-white/5 text-white/30'"
            >
              {{ outputQty > 0 ? `${outputQty} ${commonUnit}` : '—' }}
            </div>
          </div>
        </div>

        <!-- Ghi chú -->
        <UiInput
          label="Ghi chú (tuỳ chọn)"
          v-model="form.note"
          placeholder="VD: Xay xát tại nhà máy ABC, nhiệt độ 40°C..."
        />

        <!-- Chứng chỉ kế thừa -->
        <div>
          <label class="block text-xs text-white/50 mb-1.5">Chứng chỉ kế thừa</label>
          <div v-if="selectedBatches.length >= 1 && certsMatch"
            class="w-full rounded-xl border border-white/10 bg-white/5 px-3 py-2.5 min-h-10"
          >
            <div v-if="commonCerts.length" class="flex flex-wrap gap-1.5">
              <span
                v-for="cert in commonCerts"
                :key="cert"
                class="text-xs px-2 py-1 rounded-lg border border-green-500/40 bg-green-500/10 text-green-400"
              >✓ {{ cert }}</span>
            </div>
            <div v-else class="text-white/30 text-sm">Không có chứng chỉ</div>
          </div>
          <div v-else class="w-full rounded-xl border border-white/10 bg-white/5 px-3 py-2.5 text-white/30 text-sm">
            Chọn lô để xem chứng chỉ
          </div>
        </div>

        <!-- Summary -->
        <div
          v-if="selectedBatches.length >= 1 && certsMatch && form.output_product_id && outputQty > 0"
          class="p-4 rounded-xl bg-brand-500/5 border border-brand-500/20 text-sm space-y-1.5"
        >
          <div class="text-brand-300 text-xs font-semibold uppercase tracking-wider mb-2">Tóm tắt chế biến</div>
          <div class="flex justify-between text-white/60">
            <span>Lô nguyên liệu</span>
            <span class="text-white/90 font-semibold">{{ selectedBatches.length }} lô → {{ totalInputQty }} {{ commonUnit }}</span>
          </div>
          <div class="flex justify-between text-white/60">
            <span>Hao hụt</span>
            <span class="text-amber-400 font-semibold">{{ form.loss_rate }}% (−{{ Math.round((totalInputQty - outputQty) * 100) / 100 }} {{ commonUnit }})</span>
          </div>
          <div class="flex justify-between text-white/60">
            <span>Thành phẩm</span>
            <span class="text-green-400 font-bold">{{ selectedProduct?.name }} — {{ outputQty }} {{ commonUnit }}</span>
          </div>
          <div class="flex justify-between text-white/60">
            <span>Chứng chỉ</span>
            <span class="text-white/90">{{ commonCerts.length ? commonCerts.join(', ') : 'Không có' }}</span>
          </div>
          <div class="border-t border-white/10 pt-2 mt-2 text-xs text-white/30">
            Các lô nguyên liệu sẽ bị đánh dấu <span class="text-amber-400">consumed</span> sau khi chế biến.
          </div>
        </div>
      </div>
    </UiCard>

    <!-- ── Buttons ─────────────────────────────────────────── -->
    <div class="flex gap-3 justify-end">
      <a :href="route('batches.index')">
        <UiButton variant="outline">Huỷ</UiButton>
      </a>
      <UiButton :disabled="!canSubmit" @click="submit">
        {{ form.processing ? 'Đang xử lý...' : 'Xác nhận chế biến' }}
      </UiButton>
    </div>

  </div>
</template>
