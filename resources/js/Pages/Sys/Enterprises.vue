<script setup>
import { Head, Link, router } from '@inertiajs/vue3'
import { computed, ref } from 'vue'
import UiCard from '@/Components/ui/UiCard.vue'
import UiButton from '@/Components/ui/UiButton.vue'
import UiBadge from '@/Components/ui/UiBadge.vue'
import UiTable from '@/Components/ui/UiTable.vue'
import UiModal from '@/Components/ui/UiModal.vue'
import UiInput from '@/Components/ui/UiInput.vue'

const props = defineProps({
  status: String,
  enterprises: Object,
})

const blockingId     = ref(null)
const blockReason    = ref('')

const openBlock  = (id) => { blockingId.value = id; blockReason.value = '' }
const submitBlock = () => {
  router.post(route('sys.enterprises.block', blockingId.value), { reason: blockReason.value })
  blockingId.value = null
}
const unblock = (id) => router.post(route('sys.enterprises.unblock', id))

const activeStatus = computed(() => props.status || 'pending')

const headers = [
  { key: 'id', label: 'ID' },
  { key: 'name', label: 'Tên' },
  { key: 'code', label: 'Mã DN' },
  { key: 'email', label: 'Email' },
  { key: 'phone', label: 'SĐT' },
  { key: 'status', label: 'Trạng thái' },
  { key: 'actions', label: 'Hành động' },
]

const rejectingId = ref(null)
const rejectReason = ref('')

const setStatus = (s) => {
  router.get(route('sys.enterprises.index'), { status: s }, { preserveState: true, replace: true })
}

const approve = (id) => router.post(route('sys.enterprises.approve', id))

const openReject = (id) => {
  rejectingId.value = id
  rejectReason.value = ''
}

const submitReject = () => {
  router.post(route('sys.enterprises.reject', rejectingId.value), { reason: rejectReason.value })
  rejectingId.value = null
}

const badgeVariant = (s) => {
  if (s === 'approved') return 'green'
  if (s === 'pending') return 'orange'
  if (s === 'rejected') return 'red'
  return 'gray'
}

const pillCls = (s) =>
  `px-3 py-2 rounded-xl text-sm font-extrabold border transition ${
    activeStatus.value === s
      ? 'border-brand-500/50 bg-brand-500/10 text-brand-200'
      : 'border-glass bg-black/10 text-white/75 hover:bg-white/5 hover:text-white'
  }`
</script>

<template>
  <Head title="Duyệt doanh nghiệp" />

  <UiCard title="Doanh nghiệp" subtitle="Danh sách đăng ký • Duyệt / Từ chối">
    <template #headerRight>
      <div class="text-xs text-white/60">
        Trang: <span class="text-white/80 font-bold">{{ enterprises.current_page }}</span>
        / {{ enterprises.last_page }}
      </div>
    </template>

    <!-- Tabs -->
    <div class="flex gap-2 mb-5 flex-wrap">
      <button :class="pillCls('pending')" @click="setStatus('pending')">Pending</button>
      <button :class="pillCls('approved')" @click="setStatus('approved')">Approved</button>
      <button :class="pillCls('rejected')" @click="setStatus('rejected')">Rejected</button>
      <button :class="pillCls('all')" @click="setStatus('all')">All</button>
    </div>

    <UiTable :headers="headers">
      <tr
        v-for="e in enterprises.data"
        :key="e.id"
        class="hover:bg-white/5 transition"
      >
        <td class="px-4 py-3 text-white/80">{{ e.id }}</td>

        <td class="px-4 py-3">
          <div class="font-extrabold text-white/90">{{ e.name }}</div>
          <div class="text-xs text-white/50">{{ e.tax_code || e.business_code || '' }}</div>
        </td>

        <td class="px-4 py-3 text-white/80">{{ e.code }}</td>
        <td class="px-4 py-3 text-white/70">{{ e.email }}</td>
        <td class="px-4 py-3 text-white/70">{{ e.phone }}</td>

        <td class="px-4 py-3">
          <UiBadge :variant="badgeVariant(e.status)">{{ e.status }}</UiBadge>
        </td>

        <td class="px-4 py-3">
          <div class="flex gap-2 flex-wrap">
            <Link :href="route('sys.enterprises.show', e.id)">
              <UiButton variant="outline" size="sm">Xem</UiButton>
            </Link>

            <UiButton v-if="e.status === 'pending'" size="sm" @click="approve(e.id)">
              Approve
            </UiButton>
            <UiButton v-if="e.status === 'pending'" variant="danger" size="sm" @click="openReject(e.id)">
              Reject
            </UiButton>
            <UiButton v-if="e.status === 'approved'" variant="danger" size="sm" @click="openBlock(e.id)">
              Block
            </UiButton>
            <UiButton v-if="e.status === 'blocked'" size="sm" @click="unblock(e.id)">
              Unblock
            </UiButton>
          </div>
        </td>
      </tr>
    </UiTable>
  </UiCard>

  <UiModal :show="!!rejectingId" title="Lý do từ chối" @close="rejectingId=null">
    <UiInput label="Nhập lý do" v-model="rejectReason" placeholder="Ví dụ: Thiếu GCN, sai thông tin..." />
    <template #actions>
      <UiButton variant="outline" size="sm" @click="rejectingId=null">Hủy</UiButton>
      <UiButton variant="danger" size="sm" @click="submitReject">Gửi từ chối</UiButton>
    </template>
  </UiModal>
  <UiModal :show="!!blockingId" title="Lý do khóa doanh nghiệp" @close="blockingId=null">
    <UiInput label="Nhập lý do khóa" v-model="blockReason" placeholder="VD: Vi phạm điều khoản sử dụng..." />
    <template #actions>
      <UiButton variant="outline" size="sm" @click="blockingId=null">Hủy</UiButton>
      <UiButton variant="danger" size="sm" @click="submitBlock">Xác nhận khóa</UiButton>
    </template>
  </UiModal>
</template>