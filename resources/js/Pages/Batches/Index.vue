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

// ── Prefix map (khớp với category code trong DB) ─────────
const PREFIX_MAP = {
  lua_gao:      'LG',
  rau_qua:      'RQ',
  thuy_san:     'TS',
  chan_nuoi:     'CN',
  thuc_pham_cb: 'TP',
  khac:         'KH',
}

const headers = [
  { key: 'code',         label: 'Mã lô',      class: 'w-36' },
  { key: 'product_name', label: 'Sản phẩm' },
  { key: 'quantity',     label: 'Số lượng',   class: 'w-28' },
  { key: 'status',       label: 'Trạng thái', class: 'w-28' },
  { key: 'events',       label: 'Sự kiện',    class: 'w-20' },
  { key: 'actions',      label: 'Thao tác',   class: 'w-40' },
]

const statusLabel = (s) => ({ active: 'Hoạt động', completed: 'Hoàn thành', recalled: 'Thu hồi' }[s] ?? s)
const statusCls   = (s) => ({
  active:    'text-green-400',
  completed: 'text-white/50',
  recalled:  'text-red-400',
}[s] ?? 'text-white/50')

// ── Tính prefix từ product đã chọn ───────────────────────
function getCodePrefix(productId) {
  const p = props.products.find(p => p.id == productId)
  if (!p) return '??'
  // products từ controller có category_id nhưng không load category relation
  // nên dùng category_code được pass kèm (xem controller update bên dưới)
  return PREFIX_MAP[p.category_code] ?? 'KH'
}

// ── Create ───────────────────────────────────────────────
const showCreate   = ref(false)
const codePreview  = ref('')

const createForm = useForm({
  product_id:      '',
  description:     '',
  production_date: '',
  expiry_date:     '',
  quantity:        '',
  unit:            '',
})

// Cập nhật preview mã lô khi chọn sản phẩm
watch(() => createForm.product_id, (val) => {
  if (!val) { codePreview.value = ''; return }
  const prefix = getCodePrefix(val)
  const p = props.products.find(p => p.id == val)
  codePreview.value = `${prefix}__...` // backend sẽ sinh chính xác
})

const submitCreate = () => {
  createForm.post(route('batches.store'), {
    onSuccess: () => { showCreate.value = false; createForm.reset(); codePreview.value = '' },
  })
}

// ── Edit ─────────────────────────────────────────────────
const showEdit = ref(false)
const editing  = ref(null)

const editForm = useForm({
  description:     '',
  production_date: '',
  expiry_date:     '',
  quantity:        '',
  unit:            '',
})

const openEdit = (b) => {
  editing.value             = b
  editForm.description      = b.description ?? ''
  editForm.production_date  = b.production_date ?? ''
  editForm.expiry_date      = b.expiry_date ?? ''
  editForm.quantity         = b.quantity ?? ''
  editForm.unit             = b.unit ?? ''
  showEdit.value            = true
}

const submitEdit = () => {
  if (!editing.value) return
  editForm.put(route('batches.update', editing.value.id), {
    onSuccess: () => { showEdit.value = false; editing.value = null },
  })
}

// ── Delete ───────────────────────────────────────────────
const removeBatch = (b) => {
  if (!confirm(`Xóa lô "${b.code}"?`)) return
  router.delete(route('batches.destroy', b.id))
}

// ── Pagination ───────────────────────────────────────────
const prevPage = () => { if (paginator.value?.prev_page_url) router.visit(paginator.value.prev_page_url, { preserveState: true }) }
const nextPage = () => { if (paginator.value?.next_page_url) router.visit(paginator.value.next_page_url, { preserveState: true }) }
</script>

<template>
  <Head title="Lô hàng" />

  <div class="space-y-6">

    <!-- Header -->
    <div class="rounded-2xl border border-glass bg-black/40 p-6 flex items-center justify-between gap-4">
      <div>
        <div class="text-brand-300 text-sm font-semibold">Doanh nghiệp</div>
        <div class="text-2xl font-bold mt-1 text-white/90">Quản lý lô hàng</div>
        <div class="text-white/50 text-sm mt-1">Mỗi lô gắn với sản phẩm, mã lô tự sinh theo danh mục.</div>
      </div>
      <div class="flex items-center gap-3">
        <div v-if="flash.success" class="text-sm text-green-300">{{ flash.success }}</div>
        <UiButton @click="showCreate = true">+ Tạo lô mới</UiButton>
      </div>
    </div>

    <!-- Table -->
    <UiCard title="Danh sách lô" :subtitle="`Tổng: ${paginator?.total ?? list.length} lô`">
      <UiTable :headers="headers">
        <tr v-for="b in list" :key="b.id" class="hover:bg-white/5 transition">
          <td class="px-4 py-3 font-mono text-brand-300 font-bold text-sm">{{ b.code }}</td>
          <td class="px-4 py-3">
            <div class="text-white/90 font-semibold">{{ b.product_name }}</div>
            <div v-if="b.product" class="text-xs text-white/40">{{ b.product.name }}</div>
          </td>
          <td class="px-4 py-3 text-white/70 text-sm">{{ b.quantity ? `${b.quantity} ${b.unit ?? ''}` : '—' }}</td>
          <td class="px-4 py-3 text-sm" :class="statusCls(b.status)">{{ statusLabel(b.status) }}</td>
          <td class="px-4 py-3 text-white/60 text-sm text-center">{{ b.events_count }}</td>
          <td class="px-4 py-3">
            <div class="flex gap-2">
              <UiButton size="sm" variant="outline" @click="openEdit(b)">Sửa</UiButton>
              <a :href="route('batches.qrs', b.id)">
                <UiButton size="sm" variant="outline">QR</UiButton>
              </a>
              <UiButton size="sm" variant="danger" @click="removeBatch(b)">Xóa</UiButton>
            </div>
          </td>
        </tr>
        <tr v-if="!list.length">
          <td colspan="6" class="p-8 text-center text-white/40">Chưa có lô nào.</td>
        </tr>
      </UiTable>

      <div v-if="paginator && paginator.last_page > 1" class="flex items-center justify-between mt-4 text-sm">
        <div class="text-white/50">Hiển thị {{ paginator.from ?? 0 }}–{{ paginator.to ?? 0 }} / {{ paginator.total ?? 0 }}</div>
        <div class="flex gap-2">
          <UiButton variant="outline" size="sm" :disabled="!paginator.prev_page_url" @click="prevPage">Trước</UiButton>
          <UiButton variant="outline" size="sm" :disabled="!paginator.next_page_url" @click="nextPage">Sau</UiButton>
        </div>
      </div>
    </UiCard>

  </div>

  <!-- Modal: Tạo lô mới -->
  <UiModal :show="showCreate" title="Tạo lô mới" @close="showCreate = false">
    <div class="space-y-4">

      <!-- Chọn sản phẩm -->
      <div>
        <label class="block text-xs text-white/50 mb-1">Sản phẩm *</label>
        <select v-model="createForm.product_id" :class="selectCls">
          <option value="" disabled class="bg-cosmic-900">Chọn sản phẩm</option>
          <option v-for="p in products" :key="p.id" :value="p.id" class="bg-cosmic-900">
            {{ p.name }}{{ p.gtin ? ` — ${p.gtin}` : '' }}
          </option>
        </select>
        <div v-if="createForm.errors.product_id" class="text-red-400 text-xs mt-1">{{ createForm.errors.product_id }}</div>
      </div>

      <!-- Preview mã lô -->
      <div v-if="createForm.product_id" class="rounded-xl bg-white/5 border border-glass px-4 py-3 flex items-center gap-3">
        <span class="text-xs text-white/40">Mã lô sẽ được tự sinh:</span>
        <span class="font-mono text-brand-300 font-bold">{{ getCodePrefix(createForm.product_id) }}{{ String(/* enterprise_id */0).padStart(2,'0') }}XXX</span>
        <span class="text-xs text-white/30">(backend tự tính số thứ tự)</span>
      </div>

      <div class="grid grid-cols-2 gap-4">
        <UiInput label="Ngày sản xuất" type="date" v-model="createForm.production_date" :error="createForm.errors.production_date" />
        <UiInput label="Hạn sử dụng"   type="date" v-model="createForm.expiry_date"     :error="createForm.errors.expiry_date" />
        <UiInput label="Số lượng"       type="number" v-model="createForm.quantity"      :error="createForm.errors.quantity" placeholder="VD: 500" />
        <UiInput label="Đơn vị"         v-model="createForm.unit"                        :error="createForm.errors.unit" placeholder="VD: kg, hộp" />
      </div>

      <UiInput label="Mô tả lô" v-model="createForm.description" :error="createForm.errors.description" />
    </div>
    <template #actions>
      <UiButton variant="outline" size="sm" @click="showCreate = false">Hủy</UiButton>
      <UiButton :disabled="createForm.processing" @click="submitCreate">Tạo lô</UiButton>
    </template>
  </UiModal>

  <!-- Modal: Sửa lô -->
  <UiModal :show="showEdit" title="Sửa thông tin lô" @close="showEdit = false">
    <div class="space-y-4">
      <div class="rounded-xl bg-white/5 border border-glass px-4 py-2 text-sm">
        <span class="text-white/40">Mã lô: </span>
        <span class="font-mono text-brand-300 font-bold">{{ editing?.code }}</span>
        <span class="text-white/40 ml-3">Sản phẩm: </span>
        <span class="text-white/80">{{ editing?.product_name }}</span>
      </div>
      <div class="grid grid-cols-2 gap-4">
        <UiInput label="Ngày sản xuất" type="date" v-model="editForm.production_date" :error="editForm.errors.production_date" />
        <UiInput label="Hạn sử dụng"   type="date" v-model="editForm.expiry_date"     :error="editForm.errors.expiry_date" />
        <UiInput label="Số lượng"       type="number" v-model="editForm.quantity"      :error="editForm.errors.quantity" />
        <UiInput label="Đơn vị"         v-model="editForm.unit"                        :error="editForm.errors.unit" />
      </div>
      <UiInput label="Mô tả" v-model="editForm.description" :error="editForm.errors.description" />
    </div>
    <template #actions>
      <UiButton variant="outline" size="sm" @click="showEdit = false">Hủy</UiButton>
      <UiButton :disabled="editForm.processing" @click="submitEdit">Lưu</UiButton>
    </template>
  </UiModal>
</template>