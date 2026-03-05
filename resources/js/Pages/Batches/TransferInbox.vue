<script setup>
import { Head, router, useForm, usePage } from '@inertiajs/vue3'
import { ref } from 'vue'
import UiCard   from '@/Components/ui/UiCard.vue'
import UiButton from '@/Components/ui/UiButton.vue'
import UiModal  from '@/Components/ui/UiModal.vue'

const props = defineProps({
  transfers: { type: Array, default: () => [] },
})

const flash = usePage().props.flash || {}

// ── Accept ───────────────────────────────────────────────
function accept(transfer) {
  if (!confirm(`Xác nhận nhận lô ${transfer.batch?.code} từ ${transfer.from_enterprise?.name}?`)) return
  router.post(route('batch-transfers.accept', transfer.id))
}

// ── Reject ───────────────────────────────────────────────
const showRejectModal = ref(false)
const rejectingId     = ref(null)
const rejectingBatch  = ref(null)

const rejectForm = useForm({
  rejection_reason: '',
})

function openReject(transfer) {
  rejectingId.value    = transfer.id
  rejectingBatch.value = transfer.batch?.code
  rejectForm.reset()
  showRejectModal.value = true
}

function submitReject() {
  rejectForm.post(route('batch-transfers.reject', rejectingId.value), {
    onSuccess: () => { showRejectModal.value = false },
  })
}
</script>

<template>
  <Head title="Nhận hàng" />

  <div class="space-y-6">

    <!-- Header -->
    <div class="rounded-2xl border border-glass bg-black/40 p-6 flex items-center justify-between">
      <div>
        <div class="text-brand-300 text-sm font-semibold">Chuỗi cung ứng</div>
        <div class="text-2xl font-bold mt-1 text-white/90">Yêu cầu chuyển giao đến</div>
        <div class="text-white/50 text-sm mt-1">Các lô hàng đang chờ bạn xác nhận nhận.</div>
      </div>
      <div v-if="flash.success" class="text-sm text-green-300">{{ flash.success }}</div>
    </div>

    <!-- Danh sách -->
    <UiCard :title="`${transfers.length} yêu cầu đang chờ`">
      <div class="space-y-3">
        <div
          v-for="t in transfers"
          :key="t.id"
          class="p-4 rounded-xl border border-glass bg-white/5 flex items-start justify-between gap-4"
        >
          <!-- Info -->
          <div class="space-y-1 flex-1">
            <div class="flex items-center gap-2">
              <span class="font-mono text-brand-300 font-bold">{{ t.batch?.code }}</span>
              <span class="text-white/40 text-xs">—</span>
              <span class="text-white/70 text-sm">{{ t.batch?.product_name }}</span>
            </div>
            <div class="text-white/50 text-sm">
              Từ: <span class="text-white/80 font-semibold">{{ t.from_enterprise?.name }}</span>
              <span class="text-white/30 mx-2">•</span>
              Mã DN: <span class="text-white/60">{{ t.from_enterprise?.code }}</span>
            </div>
            <div class="text-white/50 text-sm">
              Số lượng: <span class="text-white/80 font-bold">{{ t.quantity }} {{ t.unit }}</span>
              <span v-if="t.invoice_no" class="ml-3 text-white/40">
                HĐ: <span class="text-white/60">{{ t.invoice_no }}</span>
              </span>
            </div>
            <div v-if="t.note" class="text-white/40 text-xs italic">{{ t.note }}</div>
            <div class="text-white/30 text-xs">
              Gửi lúc: {{ new Date(t.transferred_at).toLocaleString('vi-VN') }}
            </div>
          </div>

          <!-- Actions -->
          <div class="flex flex-col gap-2 shrink-0">
            <UiButton size="sm" @click="accept(t)">✓ Nhận hàng</UiButton>
            <UiButton size="sm" variant="danger" @click="openReject(t)">✕ Từ chối</UiButton>
          </div>
        </div>

        <div v-if="!transfers.length" class="text-center text-white/40 py-8">
          Không có yêu cầu chuyển giao nào đang chờ.
        </div>
      </div>
    </UiCard>

  </div>

  <!-- Modal từ chối -->
  <UiModal :show="showRejectModal" :title="`Từ chối lô ${rejectingBatch}`" @close="showRejectModal = false">
    <div class="space-y-4">
      <p class="text-white/60 text-sm">Vui lòng nhập lý do từ chối để thông báo cho bên giao hàng.</p>
      <UiInput
        label="Lý do từ chối *"
        v-model="rejectForm.rejection_reason"
        :error="rejectForm.errors.rejection_reason"
        placeholder="VD: Hàng không đúng quy cách..."
      />
    </div>
    <template #actions>
      <UiButton variant="outline" size="sm" @click="showRejectModal = false">Huỷ</UiButton>
      <UiButton
        variant="danger"
        size="sm"
        :disabled="rejectForm.processing || !rejectForm.rejection_reason"
        @click="submitReject"
      >
        Xác nhận từ chối
      </UiButton>
    </template>
  </UiModal>
</template>