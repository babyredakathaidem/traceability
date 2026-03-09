<script setup>
import { ref, computed } from 'vue'
import { Head, router, useForm } from '@inertiajs/vue3'
import UiCard   from '@/Components/ui/UiCard.vue'
import UiButton from '@/Components/ui/UiButton.vue'
import UiInput  from '@/Components/ui/UiInput.vue'
import UiModal  from '@/Components/ui/UiModal.vue'
import UiTable  from '@/Components/ui/UiTable.vue'
import UiBadge  from '@/Components/ui/UiBadge.vue'

const props = defineProps({
  certificates:  { type: Array,  default: () => [] },
  standardNames: { type: Array,  default: () => [] },
})

const headers = [
  { key: 'name',           label: 'Tên chứng chỉ' },
  { key: 'organization',   label: 'Tổ chức cấp',   class: 'w-48' },
  { key: 'certificate_no', label: 'Số chứng chỉ',  class: 'w-40' },
  { key: 'expiry',         label: 'Hiệu lực',       class: 'w-44' },
  { key: 'status',         label: 'Trạng thái',     class: 'w-32' },
  { key: 'batches',        label: 'Lô áp dụng',    class: 'w-24' },
  { key: 'actions',        label: 'Thao tác',       class: 'w-36' },
]

const statusVariant = (s) => ({ active: 'success', expired: 'warning', revoked: 'danger' }[s] ?? 'default')
const statusLabel   = (s) => ({ active: 'Còn hiệu lực', expired: 'Hết hạn', revoked: 'Thu hồi' }[s] ?? s)

const selectCls = 'w-full rounded-xl border border-glass bg-cosmic-900 px-3 py-2 text-sm text-white/90 focus:outline-none focus:border-brand-500/60'

// ── Create ────────────────────────────────────────────
const showCreate = ref(false)

const createForm = useForm({
  name:           '',
  organization:   '',
  certificate_no: '',
  scope:          '',
  issue_date:     '',
  expiry_date:    '',
  image:          null,
  status:         'active',
  note:           '',
})

function submitCreate() {
  createForm.post(route('certificates.store'), {
    forceFormData: true,
    onSuccess: () => { showCreate.value = false; createForm.reset() },
  })
}

// ── Edit ──────────────────────────────────────────────
const showEdit  = ref(false)
const editing   = ref(null)

const editForm = useForm({
  name:           '',
  organization:   '',
  certificate_no: '',
  scope:          '',
  issue_date:     '',
  expiry_date:    '',
  image:          null,
  status:         'active',
  note:           '',
})

function openEdit(cert) {
  editing.value           = cert
  editForm.name           = cert.name
  editForm.organization   = cert.organization ?? ''
  editForm.certificate_no = cert.certificate_no ?? ''
  editForm.scope          = cert.scope ?? ''
  editForm.issue_date     = cert.issue_date_raw ?? ''
  editForm.expiry_date    = cert.expiry_date_raw ?? ''
  editForm.status         = cert.status
  editForm.note           = cert.note ?? ''
  editForm.image          = null
  showEdit.value          = true
}

function submitEdit() {
  editForm.post(route('certificates.update', editing.value.id), {
    forceFormData: true,
    onSuccess: () => { showEdit.value = false; editing.value = null },
  })
}

// ── Delete ────────────────────────────────────────────
function removeCert(cert) {
  if (!confirm(`Xóa chứng chỉ "${cert.name}"?`)) return
  router.delete(route('certificates.destroy', cert.id))
}

// ── View image ────────────────────────────────────────
const viewingImage = ref(null)
</script>

<template>
  <Head title="Chứng chỉ & Tiêu chuẩn" />

  <div class="space-y-6">

    <!-- Header -->
    <div class="rounded-2xl border border-glass bg-black/40 p-6 flex flex-wrap items-center justify-between gap-4">
      <div>
        <div class="text-brand-300 text-sm font-semibold">Doanh nghiệp</div>
        <div class="text-2xl font-bold mt-1 text-white/90">Chứng chỉ &amp; Tiêu chuẩn</div>
        <div class="text-white/50 text-sm mt-1">
          Quản lý chứng chỉ (VietGAP, GlobalGAP, HACCP, ISO...) để gắn vào lô hàng khi truy xuất.
        </div>
      </div>
      <UiButton @click="showCreate = true">+ Thêm chứng chỉ</UiButton>
    </div>

    <!-- Bảng danh sách -->
    <UiCard :title="`${certificates.length} chứng chỉ`">
      <UiTable :headers="headers">
        <tr v-for="cert in certificates" :key="cert.id"
          class="hover:bg-white/3 transition"
          :class="cert.is_expired ? 'opacity-60' : ''">

          <!-- Tên -->
          <td class="px-4 py-3">
            <div class="flex items-center gap-2">
              <!-- Icon nếu có file -->
              <button v-if="cert.image_url" @click="viewingImage = cert.image_url"
                class="shrink-0 w-8 h-8 rounded-lg overflow-hidden border border-glass hover:border-brand-500/40 transition">
                <img :src="cert.image_url" class="w-full h-full object-cover" :alt="cert.name" />
              </button>
              <div v-else class="shrink-0 w-8 h-8 rounded-lg border border-glass bg-white/5 flex items-center justify-center text-white/20 text-xs">
                📄
              </div>
              <div>
                <div class="text-white/90 font-semibold text-sm">{{ cert.name }}</div>
                <div v-if="cert.scope" class="text-white/30 text-xs mt-0.5 truncate max-w-xs">{{ cert.scope }}</div>
              </div>
            </div>
          </td>

          <!-- Tổ chức cấp -->
          <td class="px-4 py-3 text-white/60 text-sm">{{ cert.organization ?? '—' }}</td>

          <!-- Số chứng chỉ -->
          <td class="px-4 py-3">
            <span v-if="cert.certificate_no" class="font-mono text-xs text-white/60 bg-white/5 px-2 py-0.5 rounded border border-glass">
              {{ cert.certificate_no }}
            </span>
            <span v-else class="text-white/25 text-xs">—</span>
          </td>

          <!-- Hiệu lực -->
          <td class="px-4 py-3 text-xs">
            <div v-if="cert.issue_date" class="text-white/50">
              Từ: <span class="text-white/70">{{ cert.issue_date }}</span>
            </div>
            <div v-if="cert.expiry_date"
              :class="cert.is_expired ? 'text-red-400' : 'text-white/50'">
              Đến: <span :class="cert.is_expired ? 'text-red-400 font-semibold' : 'text-white/70'">
                {{ cert.expiry_date }}
              </span>
            </div>
            <div v-if="!cert.issue_date && !cert.expiry_date" class="text-white/25">—</div>
          </td>

          <!-- Trạng thái -->
          <td class="px-4 py-3">
            <UiBadge :variant="statusVariant(cert.status)">
              {{ statusLabel(cert.status) }}
            </UiBadge>
          </td>

          <!-- Lô áp dụng -->
          <td class="px-4 py-3 text-center text-white/60 text-sm">
            {{ cert.batches_count ?? 0 }}
          </td>

          <!-- Thao tác -->
          <td class="px-4 py-3">
            <div class="flex gap-1.5">
              <UiButton size="sm" variant="outline" @click="openEdit(cert)">Sửa</UiButton>
              <UiButton size="sm" variant="danger"  @click="removeCert(cert)">Xóa</UiButton>
            </div>
          </td>
        </tr>

        <tr v-if="!certificates.length">
          <td colspan="7" class="p-10 text-center text-white/30">
            <div class="text-3xl mb-2">📋</div>
            <div>Chưa có chứng chỉ nào. Thêm ngay để gắn vào lô hàng.</div>
          </td>
        </tr>
      </UiTable>
    </UiCard>

  </div>

  <!-- ── Modal: Thêm chứng chỉ ─────────────────────── -->
  <UiModal :show="showCreate" title="Thêm chứng chỉ mới" @close="showCreate = false">
    <div class="space-y-4">

      <!-- Tên — có gợi ý -->
      <div>
        <label class="block text-xs text-white/50 mb-1.5">
          Tên chứng chỉ <span class="text-red-400">*</span>
        </label>
        <input v-model="createForm.name" list="cert-name-list"
          class="w-full bg-black/20 border border-glass rounded-xl px-3 py-2.5 text-sm text-white/90 placeholder:text-white/30 outline-none focus:border-brand-500/60"
          placeholder="VD: VietGAP, GlobalGAP, ISO 22000..." />
        <datalist id="cert-name-list">
          <option v-for="n in standardNames" :key="n" :value="n" />
        </datalist>
        <div v-if="createForm.errors.name" class="text-red-400 text-xs mt-1">{{ createForm.errors.name }}</div>
      </div>

      <div class="grid grid-cols-2 gap-4">
        <UiInput label="Tổ chức cấp"   v-model="createForm.organization"   placeholder="VD: Cục BVTV, TUV..." :error="createForm.errors.organization" />
        <UiInput label="Số chứng chỉ"  v-model="createForm.certificate_no" placeholder="VD: VG-001/2024"      :error="createForm.errors.certificate_no" />
      </div>

      <UiInput label="Phạm vi áp dụng" v-model="createForm.scope"
        placeholder="VD: Sản xuất gạo ST25 tại An Giang"
        :error="createForm.errors.scope" />

      <div class="grid grid-cols-2 gap-4">
        <UiInput label="Ngày cấp"     type="date" v-model="createForm.issue_date"  :error="createForm.errors.issue_date" />
        <UiInput label="Ngày hết hạn" type="date" v-model="createForm.expiry_date" :error="createForm.errors.expiry_date" />
      </div>

      <div>
        <label class="block text-xs text-white/50 mb-1.5">Trạng thái</label>
        <select v-model="createForm.status" :class="selectCls">
          <option value="active"  class="bg-cosmic-900">Còn hiệu lực</option>
          <option value="expired" class="bg-cosmic-900">Hết hạn</option>
          <option value="revoked" class="bg-cosmic-900">Thu hồi</option>
        </select>
      </div>

      <div>
        <label class="block text-xs text-white/50 mb-1.5">File chứng chỉ (ảnh / PDF)</label>
        <input type="file" accept=".jpg,.jpeg,.png,.pdf"
          @change="e => createForm.image = e.target.files[0]"
          class="text-sm text-white/60 file:mr-3 file:px-3 file:py-1.5 file:rounded-lg file:border file:border-glass file:bg-white/5 file:text-white/60 file:text-xs hover:file:bg-white/10 file:transition" />
        <div v-if="createForm.errors.image" class="text-red-400 text-xs mt-1">{{ createForm.errors.image }}</div>
      </div>

      <UiInput label="Ghi chú" v-model="createForm.note" placeholder="Tuỳ chọn..." :error="createForm.errors.note" />
    </div>

    <template #actions>
      <UiButton variant="outline" size="sm" @click="showCreate = false">Huỷ</UiButton>
      <UiButton :disabled="createForm.processing" @click="submitCreate">
        {{ createForm.processing ? 'Đang lưu...' : 'Thêm chứng chỉ' }}
      </UiButton>
    </template>
  </UiModal>

  <!-- ── Modal: Sửa chứng chỉ ──────────────────────── -->
  <UiModal :show="showEdit" :title="`Sửa: ${editing?.name ?? ''}`" @close="showEdit = false">
    <div class="space-y-4">

      <div>
        <label class="block text-xs text-white/50 mb-1.5">
          Tên chứng chỉ <span class="text-red-400">*</span>
        </label>
        <input v-model="editForm.name" list="cert-name-list-edit"
          class="w-full bg-black/20 border border-glass rounded-xl px-3 py-2.5 text-sm text-white/90 placeholder:text-white/30 outline-none focus:border-brand-500/60" />
        <datalist id="cert-name-list-edit">
          <option v-for="n in standardNames" :key="n" :value="n" />
        </datalist>
        <div v-if="editForm.errors.name" class="text-red-400 text-xs mt-1">{{ editForm.errors.name }}</div>
      </div>

      <div class="grid grid-cols-2 gap-4">
        <UiInput label="Tổ chức cấp"  v-model="editForm.organization"   :error="editForm.errors.organization" />
        <UiInput label="Số chứng chỉ" v-model="editForm.certificate_no" :error="editForm.errors.certificate_no" />
      </div>

      <UiInput label="Phạm vi áp dụng" v-model="editForm.scope" :error="editForm.errors.scope" />

      <div class="grid grid-cols-2 gap-4">
        <UiInput label="Ngày cấp"     type="date" v-model="editForm.issue_date"  :error="editForm.errors.issue_date" />
        <UiInput label="Ngày hết hạn" type="date" v-model="editForm.expiry_date" :error="editForm.errors.expiry_date" />
      </div>

      <div>
        <label class="block text-xs text-white/50 mb-1.5">Trạng thái</label>
        <select v-model="editForm.status" :class="selectCls">
          <option value="active"  class="bg-cosmic-900">Còn hiệu lực</option>
          <option value="expired" class="bg-cosmic-900">Hết hạn</option>
          <option value="revoked" class="bg-cosmic-900">Thu hồi</option>
        </select>
      </div>

      <!-- Ảnh hiện tại -->
      <div v-if="editing?.image_url" class="flex items-center gap-3">
        <img :src="editing.image_url" class="w-16 h-16 rounded-xl object-cover border border-glass" />
        <div class="text-xs text-white/40">File hiện tại. Upload file mới để thay thế.</div>
      </div>

      <div>
        <label class="block text-xs text-white/50 mb-1.5">File chứng chỉ mới (tuỳ chọn)</label>
        <input type="file" accept=".jpg,.jpeg,.png,.pdf"
          @change="e => editForm.image = e.target.files[0]"
          class="text-sm text-white/60 file:mr-3 file:px-3 file:py-1.5 file:rounded-lg file:border file:border-glass file:bg-white/5 file:text-white/60 file:text-xs hover:file:bg-white/10 file:transition" />
      </div>

      <UiInput label="Ghi chú" v-model="editForm.note" :error="editForm.errors.note" />
    </div>

    <template #actions>
      <UiButton variant="outline" size="sm" @click="showEdit = false">Huỷ</UiButton>
      <UiButton :disabled="editForm.processing" @click="submitEdit">
        {{ editForm.processing ? 'Đang lưu...' : 'Lưu thay đổi' }}
      </UiButton>
    </template>
  </UiModal>

  <!-- ── Lightbox xem file chứng chỉ ───────────────── -->
  <Teleport to="body">
    <Transition name="modal-fade">
      <div v-if="viewingImage" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-sm"
        @click.self="viewingImage = null">
        <div class="relative max-w-3xl w-full max-h-[90vh] rounded-2xl overflow-hidden border border-white/10 shadow-2xl">
          <img :src="viewingImage" class="w-full h-full object-contain bg-black" />
          <button @click="viewingImage = null"
            class="absolute top-3 right-3 w-8 h-8 rounded-full bg-black/60 hover:bg-black/80 flex items-center justify-center text-white/70 hover:text-white text-lg transition">
            ✕
          </button>
        </div>
      </div>
    </Transition>
  </Teleport>

</template>

<style scoped>
.modal-fade-enter-active, .modal-fade-leave-active { transition: opacity .2s ease; }
.modal-fade-enter-from, .modal-fade-leave-to { opacity: 0; }
</style>