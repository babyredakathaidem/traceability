<script setup>
import { Head, useForm } from '@inertiajs/vue3'
import UiCard   from '@/Components/ui/UiCard.vue'
import UiButton from '@/Components/ui/UiButton.vue'
import UiInput  from '@/Components/ui/UiInput.vue'

const props = defineProps({
  batch: { type: Object, required: true },
})


function generateInvoiceNo() {
  const now = new Date()
  const ymd = now.getFullYear().toString()
    + String(now.getMonth() + 1).padStart(2, '0')
    + String(now.getDate()).padStart(2, '0')
  const rand = Math.floor(1000 + Math.random() * 9000)
  return `HD-${ymd}-${rand}`
}

const form = useForm({
  to_enterprise_code: '',
  quantity:           props.batch.current_quantity ?? props.batch.quantity ?? '',
  invoice_no:         generateInvoiceNo(),
  note:               '',
})

function submit() {
  form.post(route('batches.transfer.store', props.batch.id))
}
</script>

<template>
  <Head title="Chuyển giao lô" />

  <div class="max-w-xl mx-auto space-y-6">

    <!-- Header -->
    <div class="rounded-2xl border border-glass bg-black/40 p-6">
      <div class="text-brand-300 text-sm font-semibold">Chuyển giao</div>
      <div class="text-2xl font-bold mt-1 text-white/90">Lô {{ batch.code }}</div>
      <div class="text-white/50 text-sm mt-1">
        {{ batch.product_name }} —
        Hiện có: <span class="text-brand-300 font-bold">
          {{ batch.current_quantity ?? batch.quantity }} {{ batch.unit }}
        </span>
      </div>
    </div>

    <UiCard title="Thông tin chuyển giao">
      <div class="space-y-4">
        <UiInput
          label="Mã doanh nghiệp nhận *"
          v-model="form.to_enterprise_code"
          :error="form.errors.to_enterprise_code"
          placeholder="VD: AGU-AG-0001"
        />
        <div class="grid grid-cols-2 gap-4">
          <UiInput
            label="Số lượng chuyển *"
            type="number"
            v-model="form.quantity"
            :error="form.errors.quantity"
          />
          <UiInput
            label="Đơn vị"
            :model-value="batch.unit"
            disabled
          />
        </div>

        <!-- Invoice số tự sinh + có thể sửa -->
        <div>
          <UiInput
            label="Số hóa đơn / chứng từ"
            v-model="form.invoice_no"
            :error="form.errors.invoice_no"
          />
          <button
            type="button"
            class="mt-1 text-xs text-brand-400 hover:text-brand-300 transition"
            @click="form.invoice_no = generateInvoiceNo()"
          >
             Tạo số mới
          </button>
        </div>

        <UiInput
          label="Ghi chú"
          v-model="form.note"
          :error="form.errors.note"
        />
      </div>

      <!-- Cảnh báo -->
      <div class="mt-4 p-3 rounded-xl bg-amber-500/10 border border-amber-500/30 text-sm text-amber-300">
        ⚠ Sau khi doanh nghiệp nhận xác nhận, lô sẽ chuyển sang họ quản lý.
        Nguồn gốc vẫn được lưu trên blockchain.
      </div>
    </UiCard>

    <div class="flex gap-3 justify-end">
      <a :href="route('batches.index')">
        <UiButton variant="outline">Huỷ</UiButton>
      </a>
      <UiButton
        :disabled="form.processing || !form.to_enterprise_code || !form.quantity"
        @click="submit"
      >
        {{ form.processing ? 'Đang gửi...' : 'Gửi yêu cầu chuyển giao' }}
      </UiButton>
    </div>

  </div>
</template>