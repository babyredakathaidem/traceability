<script setup>
import { Head, router, useForm } from '@inertiajs/vue3'
import { computed, ref, watch } from 'vue'

import UiCard   from '@/Components/ui/UiCard.vue'
import UiButton from '@/Components/ui/UiButton.vue'
import UiInput  from '@/Components/ui/UiInput.vue'
import UiTable  from '@/Components/ui/UiTable.vue'
import UiModal  from '@/Components/ui/UiModal.vue'
import {
  PencilSquareIcon,
  QrCodeIcon,
  ScissorsIcon,
  TruckIcon,
  ExclamationTriangleIcon,
  CheckCircleIcon,
  TrashIcon,
  ArchiveBoxIcon,
} from '@heroicons/vue/24/outline'

const props = defineProps({
  batches:      { type: [Array, Object], default: () => [] },
  products:     { type: Array,           default: () => [] },
  certificates: { type: Array,           default: () => [] },
  filters:      { type: Object,          default: () => ({}) },
})

const list      = computed(() => Array.isArray(props.batches) ? props.batches : props.batches?.data ?? [])
const paginator = computed(() => Array.isArray(props.batches) ? null : props.batches)

const selectCls = 'w-full rounded-xl border border-glass bg-cosmic-900 px-3 py-2 text-sm text-white/90 focus:outline-none focus:border-brand-500/60'

// ── Prefix map ────────────────────────────────────────────
const PREFIX_MAP = {
  lua_gao:      'LG',
  rau_qua:      'RQ',
  thuy_san:     'TS',
  chan_nuoi:    'CN',
  thuc_pham_cb: 'TP',
  khac:         'KH',
}

const headers = [
  { key: 'code',         label: 'Mã lô',      class: 'w-44' },
  { key: 'product_name', label: 'Sản phẩm' },
  { key: 'certs',        label: 'Chứng chỉ',  class: 'w-52' },
  { key: 'quantity',     label: 'Số lượng',   class: 'w-32' },
  { key: 'status',       label: 'Trạng thái', class: 'w-36' },
  { key: 'events',       label: 'Sự kiện',    class: 'w-20' },
  { key: 'actions',      label: 'Thao tác',   class: 'w-52' },
]

const statusLabel = (s) => ({
  active:    'Hoạt động',
  completed: 'Hoàn thành',
  recalled:  'Thu hồi',
  split:     'Đã tách',
  consumed:  'Đã dùng',
  received:  'Đã nhận',
  transferred: 'Đã chuyển',
}[s] ?? s)

const statusCls = (s) => ({
  active:    'text-green-400',
  completed: 'text-white/50',
  recalled:  'text-red-400',
  split:     'text-amber-400',
  consumed:  'text-white/30',
  received:  'text-blue-400',
  transferred: 'text-purple-400',
}[s] ?? 'text-white/50')

const batchTypeLabel = (t) => ({
  merged:   '⊕ Gộp',
  split:    '⊘ Tách',
  received: '↓ Nhận',
}[t] ?? '')

function getCodePrefix(productId) {
  const p = props.products.find(p => p.id == productId)
  if (!p) return '??'
  return PREFIX_MAP[p.category_code] ?? 'KH'
}

// ── Search ────────────────────────────────────────────────
const q = ref(props.filters?.q ?? '')
watch(q, (val) => {
  router.get(route('batches.index'), { q: val || undefined }, { preserveState: true, replace: true })
})

// ── Create ────────────────────────────────────────────────
const showCreate = ref(false)

const createForm = useForm({
  product_id:      '',
  description:     '',
  production_date: '',
  expiry_date:     '',
  quantity:        '',
  unit:            '',
  certificate_ids: [],
})

function toggleCreateCert(certId) {
  const idx = createForm.certificate_ids.indexOf(certId)
  if (idx >= 0) createForm.certificate_ids.splice(idx, 1)
  else createForm.certificate_ids.push(certId)
}

function submitCreate() {
  createForm.post(route('batches.store'), {
    onSuccess: () => { showCreate.value = false; createForm.reset() },
  })
}

// ── Edit ──────────────────────────────────────────────────
const showEdit = ref(false)
const editing  = ref(null)

const editForm = useForm({
  description:     '',
  production_date: '',
  expiry_date:     '',
  quantity:        '',
  unit:            '',
  certificate_ids: [],
})

function openEdit(b) {
  editing.value            = b
  editForm.description     = b.description     ?? ''
  editForm.production_date = b.production_date ?? ''
  editForm.expiry_date     = b.expiry_date     ?? ''
  editForm.quantity        = b.quantity        ?? ''
  editForm.unit            = b.unit            ?? ''
  editForm.certificate_ids = b.certificates?.map(c => c.id) ?? []
  showEdit.value           = true
}

function toggleEditCert(certId) {
  const idx = editForm.certificate_ids.indexOf(certId)
  if (idx >= 0) editForm.certificate_ids.splice(idx, 1)
  else editForm.certificate_ids.push(certId)
}

function submitEdit() {
  if (!editing.value) return
  editForm.put(route('batches.update', editing.value.id), {
    onSuccess: () => { showEdit.value = false; editing.value = null },
  })
}

// ── Delete ────────────────────────────────────────────────
function removeBatch(b) {
  if (!confirm(`Xóa lô "${b.code}"?`)) return
  router.delete(route('batches.destroy', b.id))
}

// ── Recall ────────────────────────────────────────────────
const showRecall  = ref(false)
const showResolve = ref(false)
const recalling   = ref(null)

const recallForm  = useForm({ reason: '', notice_content: '' })
const resolveForm = useForm({ resolved_note: '' })

function openRecall(b) {
  recalling.value = b
  recallForm.reset()
  showRecall.value = true
}

function submitRecall() {
  recallForm.post(route('batches.recall.store', recalling.value.id), {
    onSuccess: () => { showRecall.value = false; recalling.value = null },
  })
}

function openResolve(b) {
  recalling.value = b
  resolveForm.reset()
  showResolve.value = true
}

function submitResolve() {
  resolveForm.patch(route('batches.recall.resolve', recalling.value.id), {
    onSuccess: () => { showResolve.value = false; recalling.value = null },
  })
}

// ── Pagination ────────────────────────────────────────────
const prevPage = () => { if (paginator.value?.prev_page_url) router.visit(paginator.value.prev_page_url, { preserveState: true }) }
const nextPage = () => { if (paginator.value?.next_page_url) router.visit(paginator.value.next_page_url, { preserveState: true }) }

const canOperate = (b) => ['active', 'received'].includes(b.status)
const isArchived = (b) => ['consumed', 'split', 'recalled'].includes(b.status)
</script>

<template>
  <Head title="Lô hàng" />

  <div class="space-y-6">

    <!-- Header -->
    <div class="rounded-2xl border border-glass bg-black/40 p-6 flex flex-wrap items-center justify-between gap-4"
      data-aos="fade-right">
      <div>
        <div class="text-brand-300 text-sm font-semibold">Doanh nghiệp</div>
        <div class="text-2xl font-bold mt-1 text-white/90">Quản lý lô hàng</div>
        <div class="text-white/50 text-sm mt-1">Mỗi lô gắn với sản phẩm, mã lô tự sinh theo danh mục.</div>
      </div>
      <div class="flex items-center gap-3 flex-wrap">
        <!-- ✅ FIX: đổi route('batch-transfers.inbox') → route('events.transfer.pending') -->
        <a :href="route('events.transfer.pending')">
          <UiButton variant="outline">📥 Nhận Lô</UiButton>
        </a>
        <a :href="route('batches.merge.show')">
          <UiButton variant="outline">⊕ Gộp lô</UiButton>
        </a>
        <UiButton @click="showCreate = true">+ Tạo lô mới</UiButton>
      </div>
    </div>

    <!-- Search -->
    <div class="flex gap-3" data-aos="fade-up" data-aos-delay="100">
      <UiInput v-model="q" placeholder="Tìm mã lô, sản phẩm..." class="flex-1" />
    </div>

    <!-- Table -->
    <UiCard
      title="Danh sách lô"
      :subtitle="`Tổng: ${paginator?.total ?? list.length} lô`"
      data-aos="fade-up" data-aos-delay="200">
      <UiTable :headers="headers">
        <tr v-for="(b, i) in list" :key="b.id"
          class="hover:bg-white/3 transition"
          :class="isArchived(b) ? 'opacity-60' : ''"
          data-aos="fade-up" :data-aos-delay="200 + (i * 30)">

          <!-- Mã lô -->
          <td class="px-4 py-3">
            <div class="font-mono text-brand-300 font-bold text-sm">{{ b.code }}</div>
            <div v-if="b.batch_type && b.batch_type !== 'original'"
              class="text-[10px] mt-0.5"
              :class="{
                'text-purple-400': b.batch_type === 'merged',
                'text-amber-400':  b.batch_type === 'split',
                'text-blue-400':   b.batch_type === 'received',
              }">
              {{ batchTypeLabel(b.batch_type) }}
            </div>
          </td>

          <!-- Sản phẩm -->
          <td class="px-4 py-3">
            <div class="text-white/90 font-semibold text-sm">{{ b.product_name }}</div>
          </td>

          <!-- Chứng chỉ -->
          <td class="px-4 py-3">
            <div v-if="b.certificates?.length" class="flex flex-wrap gap-1">
              <span v-for="cert in b.certificates" :key="cert.id"
                class="text-[10px] px-1.5 py-0.5 rounded border border-brand-500/30 bg-brand-500/10 text-brand-300">
                {{ cert.name }}
              </span>
            </div>
            <span v-else class="text-white/25 text-xs">—</span>
          </td>

          <!-- Số lượng -->
          <td class="px-4 py-3 text-sm">
            <div v-if="b.quantity" class="text-white/80">
              {{ b.current_quantity ?? b.quantity }}
              <span class="text-white/40 text-xs">{{ b.unit }}</span>
            </div>
            <div v-if="b.current_quantity != null && b.quantity && b.current_quantity !== b.quantity"
              class="text-[10px] text-white/30">
              gốc: {{ b.quantity }}
            </div>
            <span v-if="!b.quantity" class="text-white/25 text-xs">—</span>
          </td>

          <!-- Trạng thái -->
          <td class="px-4 py-3 text-sm" :class="statusCls(b.status)">
            {{ statusLabel(b.status) }}
          </td>

          <!-- Sự kiện -->
          <td class="px-4 py-3 text-white/60 text-sm text-center">
            {{ b.events_count ?? 0 }}
          </td>

          <!-- Thao tác -->
          <td class="px-4 py-3">
            <div class="flex items-center gap-1">

              <!-- Sửa -->
              <button @click="openEdit(b)" title="Chỉnh sửa"
                class="p-2 rounded-lg text-white/40 hover:text-white hover:bg-white/10 transition active:scale-90">
                <PencilSquareIcon class="w-5 h-5" />
              </button>

              <!-- QR -->
              <a :href="route('batches.qrs', b.id)" title="Quản lý mã QR">
                <div class="p-2 rounded-lg text-white/40 hover:text-brand-400 hover:bg-brand-500/10 transition active:scale-90">
                  <QrCodeIcon class="w-5 h-5" />
                </div>
              </a>

              <!-- Phả hệ -->
              <a :href="route('batches.lineage', b.id)" title="Xem phả hệ">
                <div class="p-2 rounded-lg text-white/40 hover:text-sky-400 hover:bg-sky-500/10 transition active:scale-90">
                  <ArchiveBoxIcon class="w-5 h-5" />
                </div>
              </a>

              <template v-if="canOperate(b)">
                <!-- Tách lô -->
                <a :href="route('batches.split.show', b.id)" title="Tách lô">
                  <div class="p-2 rounded-lg text-white/40 hover:text-amber-400 hover:bg-amber-500/10 transition active:scale-90">
                    <ScissorsIcon class="w-5 h-5" />
                  </div>
                </a>

                <!-- Chuyển giao — redirect sang events.transfer.out.create -->
                <a :href="route('batches.transfer.show', b.id)" title="Chuyển giao">
                  <div class="p-2 rounded-lg text-white/40 hover:text-blue-400 hover:bg-blue-500/10 transition active:scale-90">
                    <TruckIcon class="w-5 h-5" />
                  </div>
                </a>

                <!-- Thu hồi -->
                <button @click="openRecall(b)" title="Phát lệnh thu hồi"
                  class="p-2 rounded-lg text-white/40 hover:text-red-500 hover:bg-red-500/10 transition active:scale-90">
                  <ExclamationTriangleIcon class="w-5 h-5" />
                </button>
              </template>

              <!-- Xử lý thu hồi -->
              <button v-if="b.status === 'recalled'" @click="openResolve(b)" title="Xử lý thu hồi"
                class="p-2 rounded-lg text-white/40 hover:text-green-500 hover:bg-green-500/10 transition active:scale-90">
                <CheckCircleIcon class="w-5 h-5" />
              </button>

              <!-- Xóa -->
              <button @click="removeBatch(b)" title="Xóa lô"
                class="p-2 rounded-lg text-white/40 hover:text-red-400 hover:bg-red-500/10 transition active:scale-90">
                <TrashIcon class="w-5 h-5" />
              </button>
            </div>
          </td>
        </tr>
      </UiTable>

      <!-- Pagination -->
      <div v-if="paginator?.last_page > 1" class="flex items-center justify-between pt-4 border-t border-glass">
        <button @click="prevPage" :disabled="!paginator.prev_page_url"
          class="text-xs px-3 py-1.5 rounded-lg border border-glass text-white/50 disabled:opacity-30 hover:bg-white/5 transition">
          ‹ Trước
        </button>
        <span class="text-xs text-white/30">{{ paginator.current_page }} / {{ paginator.last_page }}</span>
        <button @click="nextPage" :disabled="!paginator.next_page_url"
          class="text-xs px-3 py-1.5 rounded-lg border border-glass text-white/50 disabled:opacity-30 hover:bg-white/5 transition">
          Sau ›
        </button>
      </div>
    </UiCard>
  </div>

  <!-- ── Modal: Thu hồi ────────────────────────────────── -->
  <UiModal :show="showRecall" title="⚠️ Phát lệnh thu hồi lô hàng" @close="showRecall = false">
    <div class="space-y-4">
      <div class="p-3 rounded-xl bg-red-500/10 border border-red-500/20 text-red-300 text-sm">
        Lô <span class="font-mono font-bold">{{ recalling?.code }}</span> sẽ bị đánh dấu
        <strong>thu hồi</strong> và không thể sử dụng tiếp.
      </div>
      <UiInput label="Lý do thu hồi *" v-model="recallForm.reason"
        :error="recallForm.errors.reason"
        placeholder="VD: Phát hiện dư lượng thuốc vượt ngưỡng..." />
      <UiInput label="Nội dung thông báo (tùy chọn)" v-model="recallForm.notice_content"
        :error="recallForm.errors.notice_content"
        placeholder="Nội dung email gửi đến chuỗi cung ứng..." />
    </div>
    <template #actions>
      <UiButton variant="outline" size="sm" @click="showRecall = false">Huỷ</UiButton>
      <UiButton :disabled="recallForm.processing"
        class="bg-red-600 hover:bg-red-700 text-white border-0"
        @click="submitRecall">
        {{ recallForm.processing ? 'Đang xử lý...' : 'Xác nhận thu hồi' }}
      </UiButton>
    </template>
  </UiModal>

  <!-- ── Modal: Xử lý thu hồi ──────────────────────────── -->
  <UiModal :show="showResolve" title="✅ Xử lý lô bị thu hồi" @close="showResolve = false">
    <div class="space-y-4">
      <div class="p-3 rounded-xl bg-white/5 border border-glass text-white/60 text-sm">
        Xác nhận đã xử lý xong lô
        <span class="font-mono text-brand-300 font-bold">{{ recalling?.code }}</span>.
      </div>
      <UiInput label="Ghi chú xử lý" v-model="resolveForm.resolved_note"
        :error="resolveForm.errors.resolved_note"
        placeholder="VD: Đã kiểm định lại, các chỉ số đạt an toàn..." />
    </div>
    <template #actions>
      <UiButton variant="outline" size="sm" @click="showResolve = false">Huỷ</UiButton>
      <UiButton :disabled="resolveForm.processing"
        class="bg-green-600 hover:bg-green-700 text-white border-0"
        @click="submitResolve">
        {{ resolveForm.processing ? 'Đang lưu...' : 'Xác nhận xử lý xong' }}
      </UiButton>
    </template>
  </UiModal>

  <!-- ── Modal: Tạo lô mới ─────────────────────────────── -->
  <UiModal :show="showCreate" title="Tạo lô mới" @close="showCreate = false">
    <div class="space-y-4">

      <div>
        <label class="block text-xs text-white/50 mb-1">Sản phẩm <span class="text-red-400">*</span></label>
        <select v-model="createForm.product_id" :class="selectCls">
          <option value="" disabled class="bg-cosmic-900">Chọn sản phẩm</option>
          <option v-for="p in products" :key="p.id" :value="p.id" class="bg-cosmic-900">
            {{ p.name }}{{ p.gtin ? ` (${p.gtin})` : '' }}
          </option>
        </select>
        <div v-if="createForm.errors.product_id" class="text-red-400 text-xs mt-1">
          {{ createForm.errors.product_id }}
        </div>
      </div>

      <div v-if="createForm.product_id" class="text-xs text-white/40">
        Mã lô sẽ có dạng:
        <span class="font-mono text-brand-300">{{ getCodePrefix(createForm.product_id) }}-XXXXXX</span>
      </div>

      <div class="grid grid-cols-2 gap-4">
        <UiInput label="Ngày sản xuất" type="date"   v-model="createForm.production_date" :error="createForm.errors.production_date" />
        <UiInput label="Hạn sử dụng"   type="date"   v-model="createForm.expiry_date"     :error="createForm.errors.expiry_date" />
        <UiInput label="Số lượng"       type="number" v-model="createForm.quantity"         :error="createForm.errors.quantity" />
        <UiInput label="Đơn vị"                       v-model="createForm.unit"             :error="createForm.errors.unit" placeholder="kg, hộp, thùng..." />
      </div>

      <UiInput label="Mô tả" v-model="createForm.description" :error="createForm.errors.description" />

      <!-- Chứng chỉ -->
      <div>
        <label class="block text-xs text-white/50 mb-1.5">Chứng chỉ / Tiêu chuẩn áp dụng</label>
        <div v-if="!certificates.length" class="text-[10px] text-white/30 italic">
          Chưa có chứng chỉ nào. Vào mục Chứng chỉ để thêm.
        </div>
        <div v-else class="flex flex-wrap gap-2">
          <button v-for="cert in certificates" :key="cert.id"
            type="button" @click="toggleCreateCert(cert.id)"
            class="flex flex-col px-3 py-2 rounded-xl border text-left transition"
            :class="createForm.certificate_ids.includes(cert.id)
              ? 'border-brand-500/60 bg-brand-500/15'
              : 'border-glass bg-white/5 hover:bg-white/8'">
            <span class="text-xs font-semibold"
              :class="createForm.certificate_ids.includes(cert.id) ? 'text-brand-300' : 'text-white/70'">
              {{ cert.name }}
            </span>
            <span class="text-[10px] text-white/30 mt-0.5">{{ cert.organization }}</span>
          </button>
        </div>
      </div>
    </div>
    <template #actions>
      <UiButton variant="outline" size="sm" @click="showCreate = false">Huỷ</UiButton>
      <UiButton :disabled="createForm.processing" @click="submitCreate">
        {{ createForm.processing ? 'Đang tạo...' : 'Tạo lô' }}
      </UiButton>
    </template>
  </UiModal>

  <!-- ── Modal: Sửa lô ─────────────────────────────────── -->
  <UiModal :show="showEdit" title="Sửa lô hàng" @close="showEdit = false">
    <div class="space-y-4">

      <div class="p-3 rounded-xl bg-white/5 border border-glass text-xs space-y-1">
        <div class="flex justify-between">
          <span class="text-white/40">Mã lô</span>
          <span class="font-mono text-brand-300 font-bold">{{ editing?.code }}</span>
        </div>
        <div class="flex justify-between">
          <span class="text-white/40">Sản phẩm</span>
          <span class="text-white/80">{{ editing?.product_name }}</span>
        </div>
        <div v-if="editing?.batch_type && editing.batch_type !== 'original'" class="flex justify-between">
          <span class="text-white/40">Loại lô</span>
          <span class="text-white/60">{{ batchTypeLabel(editing.batch_type) }}</span>
        </div>
      </div>

      <div class="grid grid-cols-2 gap-4">
        <UiInput label="Ngày sản xuất" type="date"   v-model="editForm.production_date" :error="editForm.errors.production_date" />
        <UiInput label="Hạn sử dụng"   type="date"   v-model="editForm.expiry_date"     :error="editForm.errors.expiry_date" />
        <UiInput label="Số lượng"       type="number" v-model="editForm.quantity"         :error="editForm.errors.quantity" />
        <UiInput label="Đơn vị"                       v-model="editForm.unit"             :error="editForm.errors.unit" />
      </div>

      <UiInput label="Mô tả" v-model="editForm.description" :error="editForm.errors.description" />

      <div>
        <label class="block text-xs text-white/50 mb-1.5">Chứng chỉ / Tiêu chuẩn áp dụng</label>
        <div v-if="!certificates.length" class="text-[10px] text-white/30 italic">
          Chưa có chứng chỉ nào.
        </div>
        <div v-else class="flex flex-wrap gap-2">
          <button v-for="cert in certificates" :key="cert.id"
            type="button" @click="toggleEditCert(cert.id)"
            class="flex flex-col px-3 py-2 rounded-xl border text-left transition"
            :class="editForm.certificate_ids.includes(cert.id)
              ? 'border-brand-500/60 bg-brand-500/15'
              : 'border-glass bg-white/5 hover:bg-white/8'">
            <span class="text-xs font-semibold"
              :class="editForm.certificate_ids.includes(cert.id) ? 'text-brand-300' : 'text-white/70'">
              {{ cert.name }}
            </span>
            <span class="text-[10px] text-white/30 mt-0.5">{{ cert.organization }}</span>
          </button>
        </div>
      </div>
    </div>
    <template #actions>
      <UiButton variant="outline" size="sm" @click="showEdit = false">Huỷ</UiButton>
      <UiButton :disabled="editForm.processing" @click="submitEdit">
        {{ editForm.processing ? 'Đang lưu...' : 'Lưu thay đổi' }}
      </UiButton>
    </template>
  </UiModal>

</template>