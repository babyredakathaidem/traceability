<script setup>
import { Head, useForm } from '@inertiajs/vue3'
import { computed, ref, watch } from 'vue'
import UiCard   from '@/Components/ui/UiCard.vue'
import UiButton from '@/Components/ui/UiButton.vue'
import UiInput  from '@/Components/ui/UiInput.vue'

const props = defineProps({
  availableBatches: { type: Array, default: () => [] },
})

const selectedIds = ref([])

const form = useForm({
  input_batch_ids:     [],
  output_product_name: '',
})

// ── Computed ──────────────────────────────────────────────

const selectedBatches = computed(() =>
  props.availableBatches.filter(b => selectedIds.value.includes(b.id))
)

const totalQty = computed(() =>
  selectedBatches.value.reduce((s, b) => s + (b.current_quantity ?? 0), 0)
)

// Đơn vị — phải đồng nhất
const units = computed(() =>
  [...new Set(selectedBatches.value.map(b => b.unit).filter(Boolean))]
)
const commonUnit = computed(() => units.value.length === 1 ? units.value[0] : '')
const unitError  = computed(() => {
  if (selectedBatches.value.length < 2) return null
  if (units.value.length > 1)
    return `Các lô có đơn vị khác nhau: ${units.value.join(', ')}. Chỉ gộp được lô cùng đơn vị.`
  return null
})

// Chứng chỉ chung (giao của tất cả lô được chọn)
const commonCerts = computed(() => {
  if (selectedBatches.value.length < 2) return []
  return selectedBatches.value
    .map(b => b.certifications ?? [])
    .reduce((a, b) => a.filter(c => b.includes(c)))
})

// Chứng chỉ của từng lô (để hiển thị, không phải chứng chỉ chung)
const allUniqueCerts = computed(() => {
  const s = new Set()
  selectedBatches.value.forEach(b => (b.certifications ?? []).forEach(c => s.add(c)))
  return [...s]
})

const certWarning = computed(() => {
  if (selectedBatches.value.length < 2) return null
  const hasCerts = selectedBatches.value.some(b => (b.certifications ?? []).length > 0)
  if (!hasCerts) return null
  if (commonCerts.value.length === 0)
    return 'Các lô không có chứng chỉ chung. Lô mới sẽ không được kế thừa chứng chỉ nào.'
  return null
})

const canSubmit = computed(() =>
  !form.processing &&
  selectedIds.value.length >= 2 &&
  !unitError.value &&
  form.output_product_name.trim() !== ''
)

// ── Methods ───────────────────────────────────────────────

function toggleBatch(batch) {
  const idx = selectedIds.value.indexOf(batch.id)
  if (idx >= 0) selectedIds.value.splice(idx, 1)
  else selectedIds.value.push(batch.id)
}

function submit() {
  form.input_batch_ids = [...selectedIds.value]
  form.post(route('batches.merge.store'))
}
</script>

<template>
  <Head title="Gộp lô" />

  <div class="max-w-2xl mx-auto space-y-6">

    <!-- Header -->
    <div class="rounded-2xl border border-glass bg-black/40 p-6">
      <div class="text-brand-300 text-sm font-semibold">Lô hàng</div>
      <div class="text-2xl font-bold mt-1 text-white/90">Gộp lô</div>
      <div class="text-white/50 text-sm mt-1">
        Chọn ít nhất 2 lô để gộp. Các lô phải cùng đơn vị tính.
      </div>
    </div>

    <!-- Chọn lô đầu vào -->
    <UiCard title="Chọn lô đầu vào">
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
          <!-- Trái: info -->
          <div class="space-y-1.5 flex-1 min-w-0">
            <div class="flex items-center gap-2">
              <span class="font-mono text-brand-300 font-bold text-sm">{{ b.code }}</span>
              <span
                v-if="selectedIds.includes(b.id)"
                class="text-xs text-brand-300"
              >✓</span>
            </div>
            <div class="text-white/60 text-xs">{{ b.product_name }}</div>

            <!-- Chứng chỉ của lô -->
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

          <!-- Phải: số lượng -->
          <div class="text-right shrink-0 ml-4 pt-0.5">
            <div class="text-white/80 text-sm font-bold">
              {{ b.current_quantity }}
            </div>
            <div class="text-white/40 text-xs">{{ b.unit }}</div>
          </div>
        </button>

        <div v-if="!availableBatches.length" class="text-white/40 text-sm text-center py-6">
          Không có lô nào khả dụng.
        </div>
      </div>

      <!-- Lỗi đơn vị -->
      <div v-if="unitError"
        class="mt-3 p-3 rounded-xl bg-red-500/10 border border-red-500/30 text-sm text-red-400"
      >⚠ {{ unitError }}</div>

      <!-- Cảnh báo chứng chỉ -->
      <div v-if="certWarning"
        class="mt-3 p-3 rounded-xl bg-amber-500/10 border border-amber-500/30 text-sm text-amber-300"
      >⚠ {{ certWarning }}</div>

      <!-- Error từ server -->
      <div v-if="form.errors.input_batch_ids" class="mt-2 text-sm text-red-400">
        {{ form.errors.input_batch_ids }}
      </div>
    </UiCard>

    <!-- Thông tin lô sau khi gộp -->
    <UiCard title="Lô đầu ra">

      <!-- Chỉ tên là nhập tay -->
      <div class="space-y-4">
        <UiInput
          label="Tên sản phẩm sau khi gộp *"
          v-model="form.output_product_name"
          :error="form.errors.output_product_name"
          placeholder="VD: Gạo ST25 xay xát tổng hợp"
        />

        <!-- Các field còn lại: readonly, tự tính -->
        <div class="grid grid-cols-2 gap-4">
          <div>
            <label class="block text-xs text-white/50 mb-1.5">Tổng số lượng</label>
            <div
              class="w-full rounded-xl border border-white/10 bg-white/5 px-3 py-2 text-sm flex items-center justify-between"
              :class="selectedBatches.length < 2 ? 'text-white/30' : 'text-white/90 font-bold'"
            >
              <span>{{ selectedBatches.length >= 2 ? totalQty : '—' }}</span>
              <span class="text-white/30 text-xs">tự động tính</span>
            </div>
          </div>

          <div>
            <label class="block text-xs text-white/50 mb-1.5">Đơn vị</label>
            <div
              class="w-full rounded-xl border border-white/10 bg-white/5 px-3 py-2 text-sm flex items-center justify-between"
              :class="!commonUnit ? 'text-white/30' : 'text-white/90 font-bold'"
            >
              <span>{{ commonUnit || '—' }}</span>
              <span class="text-white/30 text-xs">kế thừa từ lô</span>
            </div>
          </div>
        </div>

        <!-- Chứng chỉ kế thừa -->
        <div>
          <label class="block text-xs text-white/50 mb-1.5">Chứng chỉ kế thừa</label>
          <div
            v-if="selectedBatches.length >= 2"
            class="w-full rounded-xl border border-white/10 bg-white/5 px-3 py-2.5 min-h-10"
          >
            <div v-if="commonCerts.length" class="flex flex-wrap gap-1.5">
              <span
                v-for="cert in commonCerts"
                :key="cert"
                class="text-xs px-2 py-1 rounded-lg border border-green-500/40 bg-green-500/10 text-green-400"
              >✓ {{ cert }}</span>
            </div>
            <div v-else class="text-white/30 text-sm">
              Không có chứng chỉ chung giữa các lô
            </div>
          </div>
          <div v-else class="w-full rounded-xl border border-white/10 bg-white/5 px-3 py-2.5 text-white/30 text-sm">
            Chọn ít nhất 2 lô để xem
          </div>
          <div class="text-xs text-white/30 mt-1">
            Chỉ kế thừa chứng chỉ có ở <strong class="text-white/50">tất cả</strong> lô đầu vào
          </div>
        </div>

        <!-- Summary tổng hợp -->
        <div
          v-if="selectedBatches.length >= 2 && !unitError"
          class="p-3 rounded-xl bg-brand-500/5 border border-brand-500/20 text-sm space-y-1"
        >
          <div class="text-brand-300 text-xs font-semibold uppercase tracking-wider mb-2">Tóm tắt gộp lô</div>
          <div class="flex justify-between text-white/60">
            <span>Số lô gộp</span>
            <span class="text-white/90 font-semibold">{{ selectedBatches.length }} lô</span>
          </div>
          <div class="flex justify-between text-white/60">
            <span>Tổng số lượng</span>
            <span class="text-brand-300 font-bold">{{ totalQty }} {{ commonUnit }}</span>
          </div>
          <div class="flex justify-between text-white/60">
            <span>Chứng chỉ kế thừa</span>
            <span class="text-white/90">
              {{ commonCerts.length ? commonCerts.join(', ') : 'Không có' }}
            </span>
          </div>
          <div class="border-t border-white/10 pt-2 mt-2 text-xs text-white/30">
            Các lô đầu vào sẽ bị đánh dấu <span class="text-amber-400">consumed</span> sau khi gộp.
          </div>
        </div>
      </div>
    </UiCard>

    <div class="flex gap-3 justify-end">
      <a :href="route('batches.index')">
        <UiButton variant="outline">Huỷ</UiButton>
      </a>
      <UiButton :disabled="!canSubmit" @click="submit">
        {{ form.processing ? 'Đang xử lý...' : `Xác nhận gộp ${selectedIds.length} lô` }}
      </UiButton>
    </div>

  </div>
</template>