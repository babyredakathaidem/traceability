<script setup>
import { Head, router, useForm, usePage } from '@inertiajs/vue3'
import { computed, ref } from 'vue'

import UiCard from '@/Components/ui/UiCard.vue'
import UiButton from '@/Components/ui/UiButton.vue'
import UiInput from '@/Components/ui/UiInput.vue'
import UiTable from '@/Components/ui/UiTable.vue'
import UiModal from '@/Components/ui/UiModal.vue'

const props = defineProps({
  batches: { type: [Array, Object], default: () => [] }, // array OR paginator
  filters: { type: Object, default: () => ({}) },
})

const flash = usePage().props.flash || {}

const list = computed(() => {
  if (Array.isArray(props.batches)) return props.batches
  return props.batches?.data ?? []
})

const paginator = computed(() => (Array.isArray(props.batches) ? null : props.batches))

const headers = [
  { key: 'id', label: 'ID', class: 'w-20' },
  { key: 'code', label: 'Code', class: 'w-48' },
  { key: 'product_name', label: 'Sản phẩm' },
  { key: 'actions', label: 'Thao tác', class: 'w-56' },
]

/** Create */
const createForm = useForm({
  code: '',
  product_name: '',
})

const create = () => {
  createForm.post(route('batches.store'), {
    onSuccess: () => {
      createForm.reset()
      createForm.clearErrors()
    },
  })
}

/** Edit */
const showEdit = ref(false)
const editing = ref(null)

const editForm = useForm({
  code: '',
  product_name: '',
})

const openEdit = (b) => {
  editing.value = b
  editForm.code = b.code
  editForm.product_name = b.product_name
  showEdit.value = true
}

const update = () => {
  if (!editing.value) return
  editForm.put(route('batches.update', editing.value.id), {
    onSuccess: () => {
      showEdit.value = false
      editing.value = null
      editForm.clearErrors()
    },
  })
}

/** Delete */
const removeBatch = (b) => {
  if (!confirm(`Xóa lô "${b.code}" nha?`)) return
  router.delete(route('batches.destroy', b.id))
}

/** Pagination (nếu controller trả paginator) */
const prevPage = () => {
  if (!paginator.value?.prev_page_url) return
  router.visit(paginator.value.prev_page_url, { preserveState: true })
}
const nextPage = () => {
  if (!paginator.value?.next_page_url) return
  router.visit(paginator.value.next_page_url, { preserveState: true })
}
</script>

<template>
  <Head title="Batches" />

  <div class="space-y-6">
    <!-- Header -->
    <div class="rounded-2xl border bg-black text-white p-6">
      <div class="flex items-center justify-between gap-4">
        <div>
          <div class="text-orange-300 text-sm font-semibold">Batches</div>
          <div class="text-2xl font-bold mt-1">Quản lý lô hàng</div>
          <div class="text-white/70 text-sm mt-2">
            Tạo lô theo doanh nghiệp. Mỗi lô sẽ dùng để ghi nhận sự kiện và phát hành QR.
          </div>
        </div>

        <div v-if="flash.success" class="text-sm text-green-200">
          {{ flash.success }}
        </div>
      </div>
    </div>

    <!-- Create -->
    <UiCard title="Tạo lô mới" subtitle="Nhập code + tên sản phẩm">
      <form class="grid grid-cols-1 md:grid-cols-2 gap-4" @submit.prevent="create">
        <UiInput
          label="Mã lô (code)"
          v-model="createForm.code"
          placeholder="VD: LO-0001"
          :error="createForm.errors.code"
        />
        <UiInput
          label="Tên sản phẩm"
          v-model="createForm.product_name"
          placeholder="VD: Gạo ST25"
          :error="createForm.errors.product_name"
        />

        <div class="md:col-span-2">
          <UiButton :disabled="createForm.processing">Tạo lô</UiButton>
        </div>
      </form>
    </UiCard>

    <!-- List -->
    <UiCard title="Danh sách lô" subtitle="Sửa / xóa / phát hành QR từ lô">
      <UiTable :headers="headers">
        <tr v-for="b in list" :key="b.id" class="hover:bg-orange-50/50">
          <td class="p-3">{{ b.id }}</td>
          <td class="p-3 font-semibold text-black">{{ b.code }}</td>
          <td class="p-3">{{ b.product_name }}</td>
          <td class="p-3">
            <div class="flex gap-2 flex-wrap">
              <UiButton size="sm" variant="outline" @click="openEdit(b)">Sửa</UiButton>
              <UiButton size="sm" variant="danger" @click="removeBatch(b)">Xóa</UiButton>

              <!-- nếu ba muốn đi qua QR admin -->
              <a v-if="b?.id" :href="route('batches.qrs', b.id)">
                <UiButton size="sm" variant="outline">QR</UiButton>
              </a>
            </div>
          </td>
        </tr>

        <tr v-if="!list.length">
          <td colspan="4" class="p-6 text-center text-gray-500">Chưa có lô nào.</td>
        </tr>
      </UiTable>

      <div v-if="paginator" class="flex items-center justify-between mt-4 text-sm">
        <div class="text-gray-600">
          Hiển thị {{ paginator.from ?? 0 }} - {{ paginator.to ?? 0 }} / {{ paginator.total ?? 0 }}
        </div>
        <div class="flex gap-2">
          <UiButton variant="outline" size="sm" :disabled="!paginator.prev_page_url" @click="prevPage">Trước</UiButton>
          <UiButton variant="outline" size="sm" :disabled="!paginator.next_page_url" @click="nextPage">Sau</UiButton>
        </div>
      </div>
    </UiCard>
  </div>

  <!-- Edit modal -->
  <UiModal :show="showEdit" title="Sửa lô" @close="showEdit = false">
    <div class="space-y-4">
      <UiInput label="Mã lô (code)" v-model="editForm.code" :error="editForm.errors.code" />
      <UiInput label="Tên sản phẩm" v-model="editForm.product_name" :error="editForm.errors.product_name" />
    </div>

    <template #actions>
      <UiButton :disabled="editForm.processing" @click="update">Lưu</UiButton>
    </template>
  </UiModal>
</template>
