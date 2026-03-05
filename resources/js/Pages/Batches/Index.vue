<script setup>
import { Head, router, useForm, usePage } from '@inertiajs/vue3'
import { computed, ref, watch } from 'vue'

import UiCard   from '@/Components/ui/UiCard.vue'
import UiButton from '@/Components/ui/UiButton.vue'
import UiInput  from '@/Components/ui/UiInput.vue'
import UiTable  from '@/Components/ui/UiTable.vue'
import UiModal  from '@/Components/ui/UiModal.vue'

const props = defineProps({
  batches:  { type: [Array, Object], default: () => [] },
  products: { type: Array,           default: () => [] },
  filters:  { type: Object,          default: () => ({}) },
})

const flash = usePage().props.flash || {}

const list      = computed(() => Array.isArray(props.batches) ? props.batches : props.batches?.data ?? [])
const paginator = computed(() => Array.isArray(props.batches) ? null : props.batches)

const selectCls = 'w-full rounded-xl border border-glass bg-cosmic-900 px-3 py-2 text-sm text-white/90 focus:outline-none focus:border-brand-500/60'

// ── Danh sách chứng chỉ ──────────────────────────────────
const CERT_OPTIONS = [
  { value: 'VietGAP',   label: 'VietGAP',         desc: 'Thực hành nông nghiệp tốt Việt Nam' },
  { value: 'GlobalGAP', label: 'GlobalGAP',        desc: 'Thực hành nông nghiệp tốt quốc tế' },
  { value: 'ISO 22000', label: 'ISO 22000',        desc: 'Hệ thống quản lý ATTP' },
  { value: 'HACCP',     label: 'HACCP',            desc: 'Phân tích mối nguy & điểm kiểm soát tới hạn' },
  { value: 'ISO 9001',  label: 'ISO 9001',         desc: 'Hệ thống quản lý chất lượng' },
  { value: 'Organic',   label: 'Hữu cơ (Organic)', desc: 'Sản xuất nông nghiệp hữu cơ' },
  { value: 'OCOP',      label: 'OCOP',             desc: 'Chương trình mỗi xã một sản phẩm' },
  { value: 'FDA',       label: 'FDA',              desc: 'Cục quản lý Thực phẩm & Dược phẩm Hoa Kỳ' },
  { value: 'BRC',       label: 'BRC',              desc: 'Tiêu chuẩn toàn cầu về ATTP' },
  { value: 'SQF',       label: 'SQF',              desc: 'Safe Quality Food' },
]

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
  { key: 'code',         label: 'Mã lô',       class: 'w-44' },
  { key: 'product_name', label: 'Sản phẩm' },
  { key: 'certs',        label: 'Chứng chỉ',   class: 'w-52' },
  { key: 'quantity',     label: 'Số lượng',    class: 'w-32' },
  { key: 'status',       label: 'Trạng thái',  class: 'w-36' },
  { key: 'events',       label: 'Sự kiện',     class: 'w-20' },
  { key: 'actions',      label: 'Thao tác',    class: 'w-52' },
]

const statusLabel = (s) => ({
  active:   'Hoạt động',
  completed:'Hoàn thành',
  recalled: 'Thu hồi',
  split:    'Đã tách (lưu trữ)',
  consumed: 'Đã dùng (lưu trữ)',
  received: 'Đã nhận',
}[s] ?? s)

const statusCls = (s) => ({
  active:   'text-green-400',
  completed:'text-white/50',
  recalled: 'text-red-400',
  split:    'text-amber-400',
  consumed: 'text-white/30',
  received: 'text-blue-400',
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
  certifications:  [],
})

function toggleCreateCert(cert) {
  const idx = createForm.certifications.indexOf(cert)
  if (idx >= 0) createForm.certifications.splice(idx, 1)
  else createForm.certifications.push(cert)
}

const submitCreate = () => {
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
  certifications:  [],
})

function openEdit(b) {
  editing.value            = b
  editForm.description     = b.description ?? ''
  editForm.production_date = b.production_date ?? ''
  editForm.expiry_date     = b.expiry_date ?? ''
  editForm.quantity        = b.quantity ?? ''
  editForm.unit            = b.unit ?? ''
  editForm.certifications  = b.certifications ?? []
  showEdit.value           = true
}

function toggleEditCert(cert) {
  const idx = editForm.certifications.indexOf(cert)
  if (idx >= 0) editForm.certifications.splice(idx, 1)
  else editForm.certifications.push(cert)
}

const submitEdit = () => {
  if (!editing.value) return
  editForm.put(route('batches.update', editing.value.id), {
    onSuccess: () => { showEdit.value = false; editing.value = null },
  })
}

// ── Delete ────────────────────────────────────────────────
const removeBatch = (b) => {
  if (!confirm(`Xóa lô "${b.code}"?`)) return
  router.delete(route('batches.destroy', b.id))
}

// ── Pagination ────────────────────────────────────────────
const prevPage = () => { if (paginator.value?.prev_page_url) router.visit(paginator.value.prev_page_url, { preserveState: true }) }
const nextPage = () => { if (paginator.value?.next_page_url) router.visit(paginator.value.next_page_url, { preserveState: true }) }

// Lô có thể tách/chuyển
const canOperate = (b) => ['active', 'received'].includes(b.status)
// Lô đã kết thúc chuỗi
const isArchived = (b) => ['consumed', 'split', 'recalled'].includes(b.status)
</script>

<template>
  <Head title="Lô hàng" />

  <div class="space-y-6">

    <!-- Header -->
    <div class="rounded-2xl border border-glass bg-black/40 p-6 flex flex-wrap items-center justify-between gap-4">
      <div>
        <div class="text-brand-300 text-sm font-semibold">Doanh nghiệp</div>
        <div class="text-2xl font-bold mt-1 text-white/90">Quản lý lô hàng</div>
        <div class="text-white/50 text-sm mt-1">Mỗi lô gắn với sản phẩm, mã lô tự sinh theo danh mục.</div>
      </div>
      <div class="flex items-center gap-3 flex-wrap">
        <div v-if="flash.success" class="text-sm text-green-300 px-3 py-1 rounded-lg border border-green-500/30 bg-green-500/10">
          {{ flash.success }}
        </div>
        <a :href="route('batch-transfers.inbox')">
          <UiButton variant="outline">📥 Nhận hàng</UiButton>
        </a>
        <a :href="route('batches.merge.show')">
          <UiButton variant="outline">⊕ Gộp lô</UiButton>
        </a>
        <UiButton @click="showCreate = true">+ Tạo lô mới</UiButton>
      </div>
    </div>

    <!-- Search -->
    <div class="flex gap-3">
      <UiInput v-model="q" placeholder="Tìm mã lô, sản phẩm..." class="flex-1" />
    </div>

    <!-- Table -->
    <UiCard title="Danh sách lô" :subtitle="`Tổng: ${paginator?.total ?? list.length} lô`">
      <UiTable :headers="headers">
        <tr v-for="b in list" :key="b.id"
          class="hover:bg-white/3 transition"
          :class="isArchived(b) ? 'opacity-60' : ''">

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
            <div v-if="b.certifications?.length" class="flex flex-wrap gap-1">
              <span v-for="cert in b.certifications" :key="cert"
                class="text-[10px] px-1.5 py-0.5 rounded border border-brand-500/30 bg-brand-500/10 text-brand-300">
                {{ cert }}
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
          <td class="px-4 py-3 text-white/60 text-sm text-center">{{ b.events_count ?? 0 }}</td>

          <!-- Thao tác -->
          <td class="px-4 py-3">
            <div class="flex flex-wrap gap-1.5">
              <UiButton size="sm" variant="outline" @click="openEdit(b)">Sửa</UiButton>
              <a :href="route('batches.qrs', b.id)">
                <UiButton size="sm" variant="outline">QR</UiButton>
              </a>
              <!-- Chỉ lô active/received mới tách/chuyển -->
              <template v-if="canOperate(b)">
                <a :href="route('batches.split.show', b.id)">
                  <UiButton size="sm" variant="outline">Tách</UiButton>
                </a>
                <a :href="route('batches.transfer.show', b.id)">
                  <UiButton size="sm" variant="outline">Chuyển</UiButton>
                </a>
              </template>
              <!-- Lô archived: chỉ xem, không thao tác -->
              <span v-if="isArchived(b)" class="text-[10px] text-white/25 self-center">Lưu trữ</span>
              <UiButton size="sm" variant="danger" @click="removeBatch(b)">Xóa</UiButton>
            </div>
          </td>
        </tr>

        <tr v-if="!list.length">
          <td colspan="7" class="p-8 text-center text-white/40">Chưa có lô nào.</td>
        </tr>
      </UiTable>

      <!-- Pagination -->
      <div v-if="paginator && paginator.last_page > 1"
        class="flex items-center justify-between mt-4 text-sm">
        <div class="text-white/50">
          Hiển thị {{ paginator.from ?? 0 }}–{{ paginator.to ?? 0 }} / {{ paginator.total ?? 0 }}
        </div>
        <div class="flex gap-2">
          <UiButton variant="outline" size="sm" :disabled="!paginator.prev_page_url" @click="prevPage">Trước</UiButton>
          <UiButton variant="outline" size="sm" :disabled="!paginator.next_page_url" @click="nextPage">Sau</UiButton>
        </div>
      </div>
    </UiCard>

  </div>

  <!-- ── Modal: Tạo lô mới ─────────────────────────────── -->
  <UiModal :show="showCreate" title="Tạo lô mới" @close="showCreate = false">
    <div class="space-y-4">

      <!-- Sản phẩm -->
      <div>
        <label class="block text-xs text-white/50 mb-1">Sản phẩm <span class="text-red-400">*</span></label>
        <select v-model="createForm.product_id" :class="selectCls">
          <option value="" disabled class="bg-cosmic-900">Chọn sản phẩm</option>
          <option v-for="p in products" :key="p.id" :value="p.id" class="bg-cosmic-900">
            {{ p.name }}{{ p.gtin ? ` — GTIN: ${p.gtin}` : '' }}
          </option>
        </select>
        <div v-if="createForm.product_id" class="mt-1 text-xs text-white/30">
          Mã lô sẽ có dạng: <span class="text-brand-300 font-mono">{{ getCodePrefix(createForm.product_id) }}XX001</span>
        </div>
        <div v-if="createForm.errors.product_id" class="text-xs text-red-400 mt-1">{{ createForm.errors.product_id }}</div>
      </div>

      <!-- Ngày + số lượng -->
      <div class="grid grid-cols-2 gap-3">
        <UiInput label="Ngày sản xuất" type="date"   v-model="createForm.production_date" />
        <UiInput label="Hạn sử dụng"   type="date"   v-model="createForm.expiry_date" />
        <UiInput label="Số lượng"       type="number" v-model="createForm.quantity" />
        <UiInput label="Đơn vị"                       v-model="createForm.unit" placeholder="kg, tấn, thùng..." />
      </div>

      <UiInput label="Mô tả (tuỳ chọn)" v-model="createForm.description" />

      <!-- Chứng chỉ -->
      <div>
        <label class="block text-xs text-white/50 mb-1.5">
          Chứng chỉ / Tiêu chuẩn áp dụng
          <span class="text-white/25 ml-1">(dùng để gợi ý lý do sự kiện)</span>
        </label>
        <div class="flex flex-wrap gap-2">
          <button
            v-for="cert in CERT_OPTIONS" :key="cert.value"
            type="button"
            @click="toggleCreateCert(cert.value)"
            class="flex flex-col px-3 py-2 rounded-xl border text-left transition"
            :class="createForm.certifications.includes(cert.value)
              ? 'border-brand-500/60 bg-brand-500/15'
              : 'border-glass bg-white/5 hover:bg-white/8'"
          >
            <span class="text-xs font-semibold"
              :class="createForm.certifications.includes(cert.value) ? 'text-brand-300' : 'text-white/70'">
              {{ cert.label }}
            </span>
            <span class="text-[10px] text-white/30 mt-0.5">{{ cert.desc }}</span>
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

      <!-- Info readonly -->
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

      <!-- Chứng chỉ -->
      <div>
        <label class="block text-xs text-white/50 mb-1.5">Chứng chỉ / Tiêu chuẩn áp dụng</label>
        <div class="flex flex-wrap gap-2">
          <button
            v-for="cert in CERT_OPTIONS" :key="cert.value"
            type="button"
            @click="toggleEditCert(cert.value)"
            class="flex flex-col px-3 py-2 rounded-xl border text-left transition"
            :class="editForm.certifications.includes(cert.value)
              ? 'border-brand-500/60 bg-brand-500/15'
              : 'border-glass bg-white/5 hover:bg-white/8'"
          >
            <span class="text-xs font-semibold"
              :class="editForm.certifications.includes(cert.value) ? 'text-brand-300' : 'text-white/70'">
              {{ cert.label }}
            </span>
          </button>
        </div>
      </div>

    </div>
    <template #actions>
      <UiButton variant="outline" size="sm" @click="showEdit = false">Huỷ</UiButton>
      <UiButton :disabled="editForm.processing" @click="submitEdit">
        {{ editForm.processing ? 'Đang lưu...' : 'Lưu' }}
      </UiButton>
    </template>
  </UiModal>

</template>