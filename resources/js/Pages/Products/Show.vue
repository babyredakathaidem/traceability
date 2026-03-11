<script setup>
import { Head, Link, router } from '@inertiajs/vue3'
import { ref, reactive } from 'vue'
import axios from 'axios'
import UiCard   from '@/Components/ui/UiCard.vue'
import UiButton from '@/Components/ui/UiButton.vue'
import UiBadge  from '@/Components/ui/UiBadge.vue'

const props = defineProps({
  product: { type: Object, required: true },
})

const p = props.product

const statusLabel = (s) => s === 'active' ? 'Hoạt động' : 'Ẩn'
const batchStatus = (s) => ({ active: 'Hoạt động', completed: 'Hoàn thành', recalled: 'Thu hồi' }[s] ?? s)
const batchBadge  = (s) => ({ active: 'green', completed: 'gray', recalled: 'red' }[s] ?? 'gray')

// ── Quy trình sản xuất ────────────────────────────────────
const steps = ref((p.processes ?? []).map((s, i) => ({ ...s, _tmp_id: i })))
const saving = ref(false)
const saveMsg = ref('')

const defaultStep = () => ({
  _tmp_id: Date.now(),
  id: null,
  step_order: steps.value.length + 1,
  name_vi: '',
  cte_code: '',
  description: '',
  is_required: true,
})

function addStep() {
  steps.value.push(defaultStep())
  reorder()
}

function removeStep(idx) {
  steps.value.splice(idx, 1)
  reorder()
}

function moveUp(idx) {
  if (idx === 0) return
  ;[steps.value[idx - 1], steps.value[idx]] = [steps.value[idx], steps.value[idx - 1]]
  reorder()
}

function moveDown(idx) {
  if (idx === steps.value.length - 1) return
  ;[steps.value[idx], steps.value[idx + 1]] = [steps.value[idx + 1], steps.value[idx]]
  reorder()
}

function reorder() {
  steps.value.forEach((s, i) => (s.step_order = i + 1))
}

async function saveProcesses() {
  saving.value = true
  saveMsg.value = ''
  try {
    await axios.post(route('products.processes.sync', p.id), { steps: steps.value })
    saveMsg.value = 'Đã lưu quy trình thành công!'
    setTimeout(() => (saveMsg.value = ''), 3000)
  } catch (e) {
    saveMsg.value = 'Lỗi khi lưu: ' + (e.response?.data?.message ?? e.message)
  } finally {
    saving.value = false
  }
}

// In QR cho từng bước (mở tab mới)
function printStepQr(step) {
  if (!step.id) {
    alert('Hãy lưu quy trình trước khi in QR bước này.')
    return
  }
  // QR sẽ link đến /step/{event_token} — cần tạo TraceEvent draft trước khi có token
  // Tạm thời hiện URL hướng dẫn, sau có thể thêm flow auto-create event
  alert(`QR cho bước "${step.name_vi}" sẽ được tạo khi lô hàng được khởi tạo từ sản phẩm này.\n\nMỗi lô sẽ tự động tạo sự kiện draf cho từng bước, và mỗi sự kiện có URL: /step/{event_token}`)
}
</script>

<template>
  <Head :title="p.name" />

  <div class="space-y-6">

    <!-- Breadcrumb + Header -->
    <div class="rounded-2xl border border-glass bg-black/40 p-6">
      <div class="text-xs text-white/40 mb-3">
        <Link :href="route('products.index')" class="text-brand-300 hover:underline">Sản phẩm</Link>
        <span class="mx-2">/</span>
        <span class="text-white/70">{{ p.name }}</span>
      </div>

      <div class="flex items-start justify-between gap-4">
        <div>
          <div class="text-brand-300 text-sm font-semibold">
            {{ p.category?.icon }} {{ p.category?.name_vi ?? '—' }}
          </div>
          <h1 class="text-2xl font-extrabold text-white/90 mt-1">{{ p.name }}</h1>
          <div class="mt-2 flex items-center gap-3 text-sm">
            <span :class="p.status === 'active' ? 'text-green-400 font-semibold' : 'text-white/40'">
              {{ statusLabel(p.status) }}
            </span>
            <span class="text-white/20">|</span>
            <span class="text-white/50">{{ p.batches?.length ?? 0 }} lô hàng</span>
            <span class="text-white/20">|</span>
            <span class="text-brand-400 font-semibold">{{ steps.length }} bước quy trình</span>
          </div>
        </div>

        <div class="flex gap-2 shrink-0">
          <Link :href="route('products.index')">
            <UiButton variant="outline" size="sm">← Quay lại</UiButton>
          </Link>
          <Link :href="route('batches.index', { product_id: p.id })">
            <UiButton size="sm">+ Tạo lô</UiButton>
          </Link>
        </div>
      </div>
    </div>

    <!-- Info -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">

      <!-- Ảnh -->
      <UiCard>
        <div v-if="p.image_path" class="rounded-xl overflow-hidden">
          <img :src="`/storage/${p.image_path}`" class="w-full object-cover max-h-64" :alt="p.name" />
        </div>
        <div v-else class="flex items-center justify-center h-40 rounded-xl bg-white/5 text-white/20 text-sm">
          Chưa có ảnh
        </div>
      </UiCard>

      <!-- Thông tin chi tiết -->
      <UiCard title="Thông tin sản phẩm" class="md:col-span-2">
        <dl class="grid grid-cols-2 gap-x-6 gap-y-4 text-sm">
          <div>
            <dt class="text-white/40 text-xs mb-1">Tên sản phẩm</dt>
            <dd class="text-white/90 font-bold">{{ p.name }}</dd>
          </div>
          <div>
            <dt class="text-white/40 text-xs mb-1">Danh mục</dt>
            <dd class="text-white/90">{{ p.category?.icon }} {{ p.category?.name_vi ?? '—' }}</dd>
          </div>
          <div>
            <dt class="text-white/40 text-xs mb-1">GTIN</dt>
            <dd class="font-mono text-white/90">{{ p.gtin || '—' }}</dd>
          </div>
          <div>
            <dt class="text-white/40 text-xs mb-1">Đơn vị tính</dt>
            <dd class="text-white/90">{{ p.unit || '—' }}</dd>
          </div>
          <div>
            <dt class="text-white/40 text-xs mb-1">Trạng thái</dt>
            <dd :class="p.status === 'active' ? 'text-green-400 font-semibold' : 'text-white/40'">
              {{ statusLabel(p.status) }}
            </dd>
          </div>
          <div>
            <dt class="text-white/40 text-xs mb-1">Số lô liên kết</dt>
            <dd class="text-white/90 font-semibold">{{ p.batches?.length ?? 0 }}</dd>
          </div>
          <div v-if="p.description" class="col-span-2">
            <dt class="text-white/40 text-xs mb-1">Mô tả</dt>
            <dd class="text-white/80 leading-relaxed">{{ p.description }}</dd>
          </div>
        </dl>
      </UiCard>
    </div>

    <!-- ── Quy trình sản xuất ─────────────────────────────── -->
    <UiCard>
      <template #title>
        <div class="flex items-center justify-between w-full">
          <div>
            <div class="text-white/90 font-bold">Quy trình sản xuất</div>
            <div class="text-xs text-white/40 mt-0.5">Mỗi bước sẽ có QR riêng khi tạo lô từ sản phẩm này.</div>
          </div>
          <div class="flex gap-2">
            <button @click="addStep"
              class="text-xs px-3 py-1.5 rounded-xl bg-brand-500/10 border border-brand-500/20 text-brand-400 hover:bg-brand-500/20 transition font-bold">
              + Thêm bước
            </button>
            <button @click="saveProcesses" :disabled="saving"
              class="text-xs px-4 py-1.5 rounded-xl bg-brand-500 text-cosmic-950 font-black hover:bg-brand-400 transition disabled:opacity-50">
              {{ saving ? 'Đang lưu...' : 'Lưu quy trình' }}
            </button>
          </div>
        </div>
      </template>

      <div v-if="saveMsg" :class="saveMsg.startsWith('Lỗi') ? 'text-red-400' : 'text-green-400'"
        class="text-xs mb-3 font-semibold">{{ saveMsg }}</div>

      <div v-if="steps.length === 0" class="text-white/30 text-sm text-center py-8">
        Chưa có bước nào. Nhấn "+ Thêm bước" để bắt đầu định nghĩa quy trình.
      </div>

      <div v-else class="space-y-3">
        <div v-for="(step, idx) in steps" :key="step._tmp_id"
          class="rounded-2xl border border-white/10 bg-white/5 p-4">
          <div class="flex items-start gap-3">

            <!-- Số thứ tự + Di chuyển -->
            <div class="flex flex-col items-center gap-0.5 mt-1 shrink-0">
              <button @click="moveUp(idx)" :disabled="idx === 0"
                class="text-white/30 hover:text-white disabled:opacity-20 transition text-xs leading-none">▲</button>
              <div class="w-7 h-7 rounded-xl bg-brand-500/20 border border-brand-500/30 flex items-center justify-center text-brand-400 font-black text-xs">
                {{ step.step_order }}
              </div>
              <button @click="moveDown(idx)" :disabled="idx === steps.length - 1"
                class="text-white/30 hover:text-white disabled:opacity-20 transition text-xs leading-none">▼</button>
            </div>

            <!-- Fields -->
            <div class="flex-1 grid grid-cols-1 md:grid-cols-3 gap-3">
              <div class="md:col-span-2">
                <label class="text-[10px] text-white/30 uppercase font-black tracking-widest block mb-1">Tên bước *</label>
                <input v-model="step.name_vi"
                  class="w-full rounded-xl border border-white/10 bg-black/30 px-3 py-2 text-sm text-white/90 placeholder-white/20 focus:border-brand-500/60 transition"
                  placeholder="VD: Trồng mía, Thu hoạch, Ép mía..." required />
              </div>
              <div>
                <label class="text-[10px] text-white/30 uppercase font-black tracking-widest block mb-1">CTE Code</label>
                <input v-model="step.cte_code"
                  class="w-full rounded-xl border border-white/10 bg-black/30 px-3 py-2 text-sm text-white/90 placeholder-white/20 focus:border-brand-500/60 transition"
                  placeholder="VD: growing, harvesting..." />
              </div>
              <div class="md:col-span-3">
                <label class="text-[10px] text-white/30 uppercase font-black tracking-widest block mb-1">Mô tả (tuỳ chọn)</label>
                <input v-model="step.description"
                  class="w-full rounded-xl border border-white/10 bg-black/30 px-3 py-2 text-sm text-white/90 placeholder-white/20 focus:border-brand-500/60 transition"
                  placeholder="Ghi chú thêm về bước này..." />
              </div>
            </div>

            <!-- Actions -->
            <div class="flex flex-col gap-2 shrink-0">
              <label class="flex items-center gap-1.5 cursor-pointer">
                <input type="checkbox" v-model="step.is_required" class="accent-orange-500" />
                <span class="text-[10px] text-white/40 uppercase font-black">Bắt buộc</span>
              </label>
              <button @click="removeStep(idx)"
                class="text-[10px] text-white/20 hover:text-red-400 transition font-black uppercase tracking-widest">
                Xóa
              </button>
            </div>
          </div>
        </div>
      </div>
    </UiCard>

    <!-- Danh sách lô -->
    <UiCard title="Lô hàng liên kết" :subtitle="`${p.batches?.length ?? 0} lô`">
      <div v-if="p.batches?.length" class="divide-y divide-white/5">
        <div v-for="b in p.batches" :key="b.id"
          class="flex items-center justify-between py-3 hover:bg-white/5 px-2 rounded-xl transition">
          <div>
            <span class="font-mono text-brand-300 font-bold text-sm">{{ b.code }}</span>
            <span v-if="b.production_date" class="ml-3 text-xs text-white/40">SX: {{ b.production_date }}</span>
            <span v-if="b.expiry_date"     class="ml-2 text-xs text-white/40">HSD: {{ b.expiry_date }}</span>
          </div>
          <div class="flex items-center gap-3">
            <span class="text-xs text-white/40">{{ b.events_count }} sự kiện</span>
            <UiBadge :variant="batchBadge(b.status)">{{ batchStatus(b.status) }}</UiBadge>
            <div class="flex gap-1">
              <Link :href="route('events.index', { batch_id: b.id })">
                <UiButton size="sm" variant="outline" class="text-brand-400 border-brand-500/30 hover:bg-brand-500/10">Ghi dữ liệu</UiButton>
              </Link>
              <a :href="route('batches.qrs', b.id)">
                <UiButton size="sm" variant="outline">QR Lô</UiButton>
              </a>
            </div>
          </div>
        </div>
      </div>
      <div v-else class="text-white/40 text-sm py-6 text-center">
        Chưa có lô nào.
        <Link :href="route('batches.index', { product_id: p.id })" class="text-brand-300 hover:underline ml-1">
          Tạo lô đầu tiên →
        </Link>
      </div>
    </UiCard>

  </div>
</template>