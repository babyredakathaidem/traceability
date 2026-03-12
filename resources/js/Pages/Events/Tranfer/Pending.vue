<script setup>
import { Head, router, useForm } from '@inertiajs/vue3'
import { ref } from 'vue'

const props = defineProps({
  outgoing: { type: Array, default: () => [] }, // lệnh mình gửi đi — đang pending
  incoming: { type: Array, default: () => [] }, // lệnh người ta gửi cho mình — chờ confirm
})

// ── Accept ────────────────────────────────────────────────
function accept(ev) {
  if (!confirm(`Xác nhận nhận ${ev.batch_count} lô từ ${ev.from_name}?`)) return
  router.post(route('events.transfer.accept', ev.id), {}, {
    preserveScroll: true,
  })
}

// ── Reject modal ──────────────────────────────────────────
const showRejectModal  = ref(false)
const rejectingEvent   = ref(null)
const rejectForm       = useForm({ rejection_reason: '' })

function openReject(ev) {
  rejectingEvent.value = ev
  rejectForm.reset()
  showRejectModal.value = true
}

function submitReject() {
  rejectForm.post(route('events.transfer.reject', rejectingEvent.value.id), {
    onSuccess: () => { showRejectModal.value = false },
    preserveScroll: true,
  })
}

function formatTime(str) {
  if (!str) return '—'
  return new Date(str).toLocaleString('vi-VN', {
    day: '2-digit', month: '2-digit', year: 'numeric',
    hour: '2-digit', minute: '2-digit',
  })
}
</script>

<template>
  <Head title="Chuyển giao hàng hoá" />

  <div class="space-y-8 max-w-3xl mx-auto py-6">

    <!-- ── Header ─────────────────────────────────────── -->
    <div class="rounded-2xl border border-brand-500/20 bg-brand-500/5 p-6 flex items-center justify-between gap-4">
      <div>
        <div class="text-brand-400 text-sm font-semibold mb-1">Chuỗi cung ứng</div>
        <h1 class="text-2xl font-bold text-white/90">Chuyển giao hàng hoá</h1>
        <p class="text-white/40 text-sm mt-1">Quản lý các lệnh chuyển giao đi và nhận về.</p>
      </div>
      <button
        @click="router.visit(route('transfer.out.create'))"
        class="shrink-0 px-4 py-2.5 rounded-xl bg-purple-500/20 border border-purple-500/30 text-purple-300 text-sm font-semibold hover:bg-purple-500/30 transition"
      >
        + Tạo lệnh chuyển giao
      </button>
    </div>

    <!-- ── INCOMING — lệnh chờ mình xác nhận ─────────── -->
    <section>
      <div class="flex items-center gap-2 mb-3 px-1">
        <span class="text-sm font-semibold text-white/50 uppercase tracking-wider">📥 Chờ xác nhận nhận</span>
        <span v-if="incoming.length"
          class="text-xs px-2 py-0.5 rounded-full bg-amber-500/15 text-amber-400 border border-amber-500/25">
          {{ incoming.length }}
        </span>
      </div>

      <div v-if="incoming.length" class="space-y-3">
        <div
          v-for="ev in incoming" :key="ev.id"
          class="rounded-xl border border-amber-500/20 bg-amber-500/5 p-4 flex items-start justify-between gap-4"
        >
          <!-- Info -->
          <div class="flex-1 min-w-0 space-y-1.5">
            <div class="flex items-center gap-2 flex-wrap">
              <span class="font-mono text-amber-300 font-bold text-sm">{{ ev.event_code }}</span>
              <span class="text-xs px-2 py-0.5 rounded-full bg-amber-500/10 border border-amber-500/20 text-amber-400">
                {{ ev.batch_count }} lô
              </span>
            </div>

            <div class="text-white/60 text-sm">
              Từ:
              <span class="text-white/85 font-semibold">{{ ev.from_name }}</span>
              <span v-if="ev.from_code" class="text-white/30 ml-1 text-xs">({{ ev.from_code }})</span>
            </div>

            <div v-if="ev.batch_summary" class="text-white/35 text-xs font-mono">
              {{ ev.batch_summary }}
            </div>

            <div v-if="ev.gs1_document_ref" class="text-white/35 text-xs">
              HĐ: <span class="text-white/55">{{ ev.gs1_document_ref }}</span>
            </div>

            <div class="text-white/25 text-xs">{{ formatTime(ev.event_time) }}</div>
          </div>

          <!-- Actions -->
          <div class="flex flex-col gap-2 shrink-0">
            <button
              @click="accept(ev)"
              class="px-4 py-2 rounded-xl bg-green-500/15 border border-green-500/25 text-green-400 text-sm font-semibold hover:bg-green-500/25 transition"
            >
              ✓ Nhận hàng
            </button>
            <button
              @click="openReject(ev)"
              class="px-4 py-2 rounded-xl bg-red-500/10 border border-red-500/15 text-red-400 text-sm hover:bg-red-500/20 transition"
            >
              ✕ Từ chối
            </button>
          </div>
        </div>
      </div>

      <div v-else
        class="rounded-xl border border-white/5 bg-white/2 px-6 py-10 text-center text-white/20 text-sm">
        Không có lệnh nào đang chờ bạn xác nhận.
      </div>
    </section>

    <!-- ── OUTGOING — lệnh mình gửi đang chờ ─────────── -->
    <section>
      <div class="flex items-center gap-2 mb-3 px-1">
        <span class="text-sm font-semibold text-white/50 uppercase tracking-wider">📤 Đã gửi — chờ đối tác xác nhận</span>
        <span v-if="outgoing.length"
          class="text-xs px-2 py-0.5 rounded-full bg-purple-500/15 text-purple-400 border border-purple-500/25">
          {{ outgoing.length }}
        </span>
      </div>

      <div v-if="outgoing.length" class="space-y-3">
        <div
          v-for="ev in outgoing" :key="ev.id"
          class="rounded-xl border border-purple-500/15 bg-purple-500/5 p-4"
        >
          <div class="flex items-center gap-2 flex-wrap mb-1.5">
            <span class="font-mono text-purple-300 font-bold text-sm">{{ ev.event_code }}</span>
            <span class="text-xs px-2 py-0.5 rounded-full bg-purple-500/10 border border-purple-500/20 text-purple-400">
              {{ ev.batch_count }} lô
            </span>
            <span class="text-xs px-2 py-0.5 rounded-full bg-amber-500/10 border border-amber-500/15 text-amber-400">
              ⏳ pending
            </span>
          </div>

          <div class="text-white/55 text-sm">
            Gửi đến:
            <span class="text-white/80 font-semibold">{{ ev.to_name }}</span>
            <span v-if="ev.to_code" class="text-white/30 ml-1 text-xs">({{ ev.to_code }})</span>
          </div>

          <div v-if="ev.batch_summary" class="text-white/30 text-xs font-mono mt-1">{{ ev.batch_summary }}</div>
          <div v-if="ev.gs1_document_ref" class="text-white/30 text-xs mt-0.5">HĐ: {{ ev.gs1_document_ref }}</div>
          <div class="text-white/20 text-xs mt-1">{{ formatTime(ev.event_time) }}</div>
        </div>
      </div>

      <div v-else
        class="rounded-xl border border-white/5 bg-white/2 px-6 py-10 text-center text-white/20 text-sm">
        Không có lệnh gửi nào đang chờ xác nhận.
      </div>
    </section>

  </div>

  <!-- ── Reject Modal ───────────────────────────────── -->
  <Teleport to="body">
    <Transition name="modal-fade">
      <div v-if="showRejectModal"
        class="fixed inset-0 z-50 flex items-center justify-center p-4"
        @click.self="showRejectModal = false">
        <div class="absolute inset-0 bg-black/70 backdrop-blur-sm" />

        <div class="relative w-full max-w-md rounded-2xl border border-red-500/20 bg-[#0d1117] p-6 space-y-4 shadow-2xl">
          <h3 class="text-lg font-bold text-white/90">Từ chối lệnh chuyển giao</h3>
          <p class="text-white/40 text-sm">
            Lý do sẽ được thông báo đến
            <span class="text-white/70 font-semibold">{{ rejectingEvent?.from_name }}</span>.
          </p>

          <textarea
            v-model="rejectForm.rejection_reason"
            rows="3"
            placeholder="Nhập lý do từ chối (tối thiểu 10 ký tự)..."
            class="w-full bg-black/30 border border-white/10 rounded-xl px-3 py-2.5 text-sm text-white/80 placeholder:text-white/25 outline-none focus:border-red-500/40 resize-none transition"
          />

          <p v-if="rejectForm.errors.rejection_reason" class="text-red-400 text-xs -mt-2">
            {{ rejectForm.errors.rejection_reason }}
          </p>

          <div class="flex gap-3 justify-end">
            <button
              @click="showRejectModal = false"
              class="px-4 py-2 rounded-xl border border-white/10 text-white/40 text-sm hover:bg-white/5 transition"
            >
              Huỷ
            </button>
            <button
              @click="submitReject"
              :disabled="rejectForm.processing || rejectForm.rejection_reason.length < 10"
              class="px-4 py-2 rounded-xl bg-red-500/20 border border-red-500/25 text-red-400 text-sm font-semibold hover:bg-red-500/30 disabled:opacity-40 transition"
            >
              {{ rejectForm.processing ? 'Đang gửi...' : 'Xác nhận từ chối' }}
            </button>
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<style scoped>
.modal-fade-enter-active, .modal-fade-leave-active { transition: opacity .2s ease; }
.modal-fade-enter-from, .modal-fade-leave-to { opacity: 0; }
</style>