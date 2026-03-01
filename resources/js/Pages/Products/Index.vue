<script setup>
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3'
import { computed, ref } from 'vue'

import UiCard   from '@/Components/ui/UiCard.vue'
import UiButton from '@/Components/ui/UiButton.vue'
import UiInput  from '@/Components/ui/UiInput.vue'
import UiTable  from '@/Components/ui/UiTable.vue'
import UiModal  from '@/Components/ui/UiModal.vue'

const props = defineProps({
  products:   { type: Object, default: () => ({}) },
  categories: { type: Array,  default: () => [] },
  filters:    { type: Object, default: () => ({}) },
})

const flash = usePage().props.flash || {}

const list      = computed(() => props.products?.data ?? [])
const paginator = computed(() => props.products)

// ── Search ───────────────────────────────────────────────
const q = ref(props.filters?.q ?? '')
const search = () => {
  router.get(route('products.index'), { q: q.value }, { preserveState: true, replace: true })
}

// ── Helpers ──────────────────────────────────────────────
const selectCls = 'w-full rounded-xl border border-glass bg-cosmic-900 px-3 py-2 text-sm text-white/90 focus:outline-none focus:border-brand-500/60'

const statusLabel = (s) => s === 'active' ? 'Hoạt động' : 'Ẩn'
const statusCls   = (s) => s === 'active' ? 'text-green-400 font-semibold' : 'text-white/40'

const khacId = computed(() => props.categories.find(c => c.code === 'khac')?.id ?? null)

const headers = [
  { key: 'name',     label: 'Tên sản phẩm' },
  { key: 'category', label: 'Danh mục' },
  { key: 'gtin',     label: 'GTIN',       class: 'w-40' },
  { key: 'unit',     label: 'Đơn vị',     class: 'w-28' },
  { key: 'status',   label: 'Trạng thái', class: 'w-28' },
  { key: 'batches',  label: 'Lô',         class: 'w-16' },
  { key: 'actions',  label: 'Thao tác',   class: 'w-48' },
]

// ── Create ───────────────────────────────────────────────
const showCreate = ref(false)

const createForm = useForm({
  name:           '',
  category_id:    '',
  category_other: '',
  gtin:           '',
  description:    '',
  unit:           '',
  status:         'active',
  image:          null,
})

const submitCreate = () => {
  // Nếu chọn "Khác", ghi tên tự điền vào description
  const form = { ...createForm }
  if (createForm.category_id == khacId.value && createForm.category_other) {
    createForm.description = `[Danh mục: ${createForm.category_other}]${createForm.description ? ' ' + createForm.description : ''}`
  }
  createForm.post(route('products.store'), {
    forceFormData: true,
    onSuccess: () => { showCreate.value = false; createForm.reset(); createForm.clearErrors() },
  })
}

// ── Edit ─────────────────────────────────────────────────
const showEdit = ref(false)
const editing  = ref(null)

const editForm = useForm({
  name:           '',
  category_id:    '',
  category_other: '',
  gtin:           '',
  description:    '',
  unit:           '',
  status:         'active',
  image:          null,
})

const openEdit = (p) => {
  editing.value          = p
  editForm.name          = p.name
  editForm.category_id   = p.category_id ?? ''
  editForm.category_other= ''
  editForm.gtin          = p.gtin ?? ''
  editForm.description   = p.description ?? ''
  editForm.unit          = p.unit ?? ''
  editForm.status        = p.status
  editForm.image         = null
  showEdit.value         = true
}

const submitEdit = () => {
  if (!editing.value) return
  if (editForm.category_id == khacId.value && editForm.category_other) {
    editForm.description = `[Danh mục: ${editForm.category_other}]${editForm.description ? ' ' + editForm.description : ''}`
  }
  editForm.post(route('products.update', editing.value.id), {
    forceFormData: true,
    onSuccess: () => { showEdit.value = false; editing.value = null; editForm.clearErrors() },
  })
}

// ── Delete ───────────────────────────────────────────────
const removeProduct = (p) => {
  if (!confirm(`Xóa sản phẩm "${p.name}"?`)) return
  router.delete(route('products.destroy', p.id))
}

// ── Pagination ───────────────────────────────────────────
const prevPage = () => { if (paginator.value?.prev_page_url) router.visit(paginator.value.prev_page_url, { preserveState: true }) }
const nextPage = () => { if (paginator.value?.next_page_url) router.visit(paginator.value.next_page_url, { preserveState: true }) }
</script>

<template>
  <Head title="Sản phẩm" />

  <div class="space-y-6">

    <!-- Header -->
    <div class="rounded-2xl border border-glass bg-black/40 p-6 flex items-center justify-between gap-4">
      <div>
        <div class="text-brand-300 text-sm font-semibold">Doanh nghiệp</div>
        <div class="text-2xl font-bold mt-1 text-white/90">Quản lý sản phẩm</div>
        <div class="text-white/50 text-sm mt-1">Khai báo sản phẩm để tạo lô và truy xuất nguồn gốc.</div>
      </div>
      <div class="flex items-center gap-3">
        <div v-if="flash.success" class="text-sm text-green-300">{{ flash.success }}</div>
        <UiButton @click="showCreate = true">+ Thêm sản phẩm</UiButton>
      </div>
    </div>

    <!-- Search -->
    <UiCard>
      <div class="flex gap-3">
        <input
          v-model="q" @keyup.enter="search"
          placeholder="Tìm theo tên, GTIN..."
          class="flex-1 rounded-xl border border-glass bg-white/5 px-4 py-2 text-white/90 placeholder:text-white/30 focus:outline-none focus:border-brand-500/60 text-sm"
        />
        <UiButton @click="search">Tìm</UiButton>
      </div>
    </UiCard>

    <!-- Table -->
    <UiCard title="Danh sách sản phẩm" :subtitle="`Tổng: ${paginator?.total ?? 0} sản phẩm`">
      <UiTable :headers="headers">
        <tr v-for="p in list" :key="p.id" class="hover:bg-white/5 transition">
          <td class="px-4 py-3">
            <div class="font-extrabold text-white/90">{{ p.name }}</div>
            <div v-if="p.description" class="text-xs text-white/40 truncate max-w-xs">{{ p.description }}</div>
          </td>
          <td class="px-4 py-3 text-white/70 text-sm">
            {{ p.category?.icon }} {{ p.category?.name_vi ?? '—' }}
          </td>
          <td class="px-4 py-3 text-white/70 text-sm font-mono">{{ p.gtin || '—' }}</td>
          <td class="px-4 py-3 text-white/70 text-sm">{{ p.unit || '—' }}</td>
          <td class="px-4 py-3 text-sm" :class="statusCls(p.status)">{{ statusLabel(p.status) }}</td>
          <td class="px-4 py-3 text-white/60 text-sm text-center">{{ p.batches_count }}</td>
          <td class="px-4 py-3">
            <div class="flex gap-2">
              <Link :href="route('products.show', p.id)">
                <UiButton size="sm" variant="outline">Xem</UiButton>
              </Link>
              <UiButton size="sm" variant="outline" @click="openEdit(p)">Sửa</UiButton>
              <UiButton size="sm" variant="danger"  @click="removeProduct(p)">Xóa</UiButton>
            </div>
          </td>
        </tr>
        <tr v-if="!list.length">
          <td colspan="7" class="p-8 text-center text-white/40">Chưa có sản phẩm nào.</td>
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

  <!-- Modal: Thêm sản phẩm -->
  <UiModal :show="showCreate" title="Thêm sản phẩm" @close="showCreate = false">
    <div class="space-y-4">
      <UiInput label="Tên sản phẩm *" v-model="createForm.name" :error="createForm.errors.name" placeholder="VD: Gạo ST25" />

      <div>
        <label class="block text-xs text-white/50 mb-1">Danh mục *</label>
        <select v-model="createForm.category_id" :class="selectCls">
          <option value="" disabled class="bg-cosmic-900">Chọn danh mục</option>
          <option v-for="c in categories" :key="c.id" :value="c.id" class="bg-cosmic-900">
            {{ c.icon }} {{ c.name_vi }}
          </option>
        </select>
        <div v-if="createForm.errors.category_id" class="text-red-400 text-xs mt-1">{{ createForm.errors.category_id }}</div>
      </div>

      <!-- Tự điền khi chọn Khác -->
      <UiInput
        v-if="createForm.category_id == khacId"
        label="Nhập tên danh mục cụ thể"
        v-model="createForm.category_other"
        placeholder="VD: Gia vị, Đồ uống..."
      />

      <UiInput label="GTIN (8–14 số)" v-model="createForm.gtin" :error="createForm.errors.gtin" placeholder="VD: 8934563000012" />
      <UiInput label="Đơn vị tính" v-model="createForm.unit" :error="createForm.errors.unit" placeholder="VD: kg, hộp, thùng" />
      <UiInput label="Mô tả" v-model="createForm.description" :error="createForm.errors.description" />

      <div>
        <label class="block text-xs text-white/50 mb-1">Trạng thái</label>
        <select v-model="createForm.status" :class="selectCls">
          <option value="active" class="bg-cosmic-900">Hoạt động</option>
          <option value="inactive" class="bg-cosmic-900">Ẩn</option>
        </select>
      </div>

      <div>
        <label class="block text-xs text-white/50 mb-1">Ảnh sản phẩm</label>
        <input type="file" accept="image/*" @change="e => createForm.image = e.target.files[0]" class="text-sm text-white/70" />
        <div v-if="createForm.errors.image" class="text-red-400 text-xs mt-1">{{ createForm.errors.image }}</div>
      </div>
    </div>
    <template #actions>
      <UiButton variant="outline" size="sm" @click="showCreate = false">Hủy</UiButton>
      <UiButton :disabled="createForm.processing" @click="submitCreate">Lưu</UiButton>
    </template>
  </UiModal>

  <!-- Modal: Sửa sản phẩm -->
  <UiModal :show="showEdit" title="Sửa sản phẩm" @close="showEdit = false">
    <div class="space-y-4">
      <UiInput label="Tên sản phẩm *" v-model="editForm.name" :error="editForm.errors.name" />

      <div>
        <label class="block text-xs text-white/50 mb-1">Danh mục *</label>
        <select v-model="editForm.category_id" :class="selectCls">
          <option value="" disabled class="bg-cosmic-900">Chọn danh mục</option>
          <option v-for="c in categories" :key="c.id" :value="c.id" class="bg-cosmic-900">
            {{ c.icon }} {{ c.name_vi }}
          </option>
        </select>
        <div v-if="editForm.errors.category_id" class="text-red-400 text-xs mt-1">{{ editForm.errors.category_id }}</div>
      </div>

      <!-- Tự điền khi chọn Khác -->
      <UiInput
        v-if="editForm.category_id == khacId"
        label="Nhập tên danh mục cụ thể"
        v-model="editForm.category_other"
        placeholder="VD: Gia vị, Đồ uống..."
      />

      <UiInput label="GTIN" v-model="editForm.gtin" :error="editForm.errors.gtin" />
      <UiInput label="Đơn vị tính" v-model="editForm.unit" :error="editForm.errors.unit" />
      <UiInput label="Mô tả" v-model="editForm.description" :error="editForm.errors.description" />

      <div>
        <label class="block text-xs text-white/50 mb-1">Trạng thái</label>
        <select v-model="editForm.status" :class="selectCls">
          <option value="active" class="bg-cosmic-900">Hoạt động</option>
          <option value="inactive" class="bg-cosmic-900">Ẩn</option>
        </select>
      </div>

      <div>
        <label class="block text-xs text-white/50 mb-1">Ảnh mới (bỏ trống nếu không đổi)</label>
        <input type="file" accept="image/*" @change="e => editForm.image = e.target.files[0]" class="text-sm text-white/70" />
        <div v-if="editForm.errors.image" class="text-red-400 text-xs mt-1">{{ editForm.errors.image }}</div>
      </div>
    </div>
    <template #actions>
      <UiButton variant="outline" size="sm" @click="showEdit = false">Hủy</UiButton>
      <UiButton :disabled="editForm.processing" @click="submitEdit">Lưu</UiButton>
    </template>
  </UiModal>
</template>