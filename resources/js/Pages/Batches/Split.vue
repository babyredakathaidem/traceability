<script setup>
import { Head, useForm } from '@inertiajs/vue3'
import { computed, ref } from 'vue'
import UiCard   from '@/Components/ui/UiCard.vue'
import UiButton from '@/Components/ui/UiButton.vue'
import UiInput  from '@/Components/ui/UiInput.vue'

const props = defineProps({
  batch: { type: Object, required: true },
})

const available = computed(() => props.batch.current_quantity ?? props.batch.quantity ?? 0)

const form = useForm({
  children: [
    { quantity: '', note: '' },
    { quantity: '', note: '' },
  ],
  reason: '',
})

const totalSplit  = computed(() => form.children.reduce((s, c) => s + (parseInt(c.quantity) || 0), 0))
const remaining   = computed(() => available.value - totalSplit.value)
const isOverLimit = computed(() => totalSplit.value > available.value)

function addRow() {
  form.children.push({ quantity: '', note: '' })
}

function removeRow(i) {
  if (form.children.length <= 2) return
  form.children.splice(i, 1)
}

function submit() {
  form.post(route('batches.split.store', props.batch.id))
}
</script>

<template>
  <Head title="Tách lô" />

  <div class="max-w-2xl mx-auto space-y-6">

    <!-- Header -->
    <div class="rounded-2xl border border-glass bg-black/40 p-6">
      <div class="text-brand-300 text-sm font-semibold">Lô hàng</div>
      <div class="text-2xl font-bold mt-1 text-white/90">Tách lô {{ batch.code }}</div>
      <div class="text-white/50 text-sm mt-1">
        Sản phẩm: <span class="text-white/80">{{ batch.product_name }}</span> —
        Hiện có: <span class="text-brand-300 font-bold">{{ available }} {{ batch.unit }}</span>
      </div>
    </div>

    <UiCard title="Các lô con sau khi tách">
      <div class="space-y-3">
        <div
          v-for="(child, i) in form.children"
          :key="i"
          class="flex items-start gap-3 p-3 rounded-xl bg-white/5 border border-glass"
        >
          <div class="text-white/40 text-sm w-6 pt-2 shrink-0">S{{ String(i+1).padStart(2,'0') }}</div>
          <div class="flex-1 grid grid-cols-2 gap-3">
            <UiInput
              :label="`Số lượng (${batch.unit ?? ''})`"
              type="number"
              v-model="child.quantity"
              :error="form.errors[`children.${i}.quantity`]"
            />
            <UiInput
              label="Ghi chú (tuỳ chọn)"
              v-model="child.note"
            />
          </div>
          <button
            v-if="form.children.length > 2"
            @click="removeRow(i)"
            class="mt-7 text-red-400 hover:text-red-300 text-lg shrink-0"
          >✕</button>
        </div>
      </div>

      <button
        @click="addRow"
        class="mt-3 text-sm text-brand-300 hover:text-brand-200 transition"
      >+ Thêm lô con</button>

      <!-- Summary -->
      <div class="mt-4 p-4 rounded-xl border border-glass bg-white/5 space-y-1 text-sm">
        <div class="flex justify-between">
          <span class="text-white/50">Tổng tách</span>
          <span class="font-bold" :class="isOverLimit ? 'text-red-400' : 'text-white/90'">
            {{ totalSplit }} {{ batch.unit }}
          </span>
        </div>
        <div class="flex justify-between">
          <span class="text-white/50">Còn lại trong lô gốc</span>
          <span class="font-bold" :class="remaining < 0 ? 'text-red-400' : 'text-green-400'">
            {{ remaining }} {{ batch.unit }}
          </span>
        </div>
      </div>

      <div v-if="isOverLimit" class="mt-2 text-sm text-red-400">
        ⚠ Tổng số lượng tách vượt quá số lượng hiện có ({{ available }}).
      </div>
    </UiCard>

    <UiCard title="Lý do tách lô (tuỳ chọn)">
      <UiInput
        label="Lý do"
        v-model="form.reason"
        :error="form.errors.reason"
      />
    </UiCard>

    <div class="flex gap-3 justify-end">
      <a :href="route('batches.index')">
        <UiButton variant="outline">Huỷ</UiButton>
      </a>
      <UiButton
        :disabled="form.processing || isOverLimit || totalSplit <= 0"
        @click="submit"
      >
        {{ form.processing ? 'Đang xử lý...' : 'Xác nhận tách lô' }}
      </UiButton>
    </div>

  </div>
</template>