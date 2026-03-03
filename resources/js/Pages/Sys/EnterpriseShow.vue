<script setup>
import { Head, Link, router } from '@inertiajs/vue3'
import { computed } from 'vue'
import UiCard from '@/Components/ui/UiCard.vue'
import UiButton from '@/Components/ui/UiButton.vue'
import UiBadge from '@/Components/ui/UiBadge.vue'

const props = defineProps({
  enterprise: Object,
  fileUrl: String,
})

const ext = computed(() => {
  if (!props.fileUrl) return ''
  const clean = String(props.fileUrl).split('?')[0]
  const parts = clean.split('.')
  return (parts.length > 1 ? parts.pop() : '').toLowerCase()
})

const isPdf = computed(() => ext.value === 'pdf')
const isImage = computed(() => ['jpg', 'jpeg', 'png', 'webp'].includes(ext.value))

const back = () => window.history.back()
const approve = () => router.post(route('sys.enterprises.approve', props.enterprise.id))

const badgeVariant = (s) => {
  if (s === 'approved') return 'green'
  if (s === 'pending') return 'orange'
  if (s === 'rejected') return 'red'
  return 'gray'
}

const v = (val) => (val === null || val === undefined || val === '' ? '-' : val)
</script>

<template>
  <Head :title="`Chi tiết DN - ${enterprise?.name || ''}`" />

  <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Info -->
    <UiCard class="lg:col-span-2" title="Thông tin doanh nghiệp" subtitle="Xem đầy đủ thông tin đăng ký">
      <div class="flex items-center justify-between mb-4">
        <div class="text-sm text-white/60">
          ID: <b class="text-white/90">{{ enterprise.id }}</b>
          • Code: <b class="text-white/90">{{ enterprise.code }}</b>
        </div>
        <UiBadge :variant="badgeVariant(enterprise.status)">{{ enterprise.status }}</UiBadge>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
        <div>
          <div class="text-white/50">Tên</div>
          <div class="font-extrabold text-white/90">{{ v(enterprise.name) }}</div>
        </div>

        <div>
          <div class="text-white/50">Email</div>
          <div class="font-extrabold text-white/90">{{ v(enterprise.email) }}</div>
        </div>

        <div>
          <div class="text-white/50">Phone</div>
          <div class="font-extrabold text-white/90">{{ v(enterprise.phone) }}</div>
        </div>

        <div>
          <div class="text-white/50">Tax code / Business code</div>
          <div class="font-extrabold text-white/90">{{ v(enterprise.tax_code || enterprise.business_code) }}</div>
        </div>

        <div class="md:col-span-2">
        <div class="text-white/50">Địa chỉ</div>
        <div class="font-extrabold text-white/90">
          {{ [enterprise.address_detail, enterprise.district, enterprise.province].filter(Boolean).join(', ') || '-' }}
        </div>
      </div>

        <div>
          <div class="text-white/50">Người đại diện</div>
          <div class="font-extrabold text-white/90">{{ v(enterprise.representative_name) }}</div>
        </div>

        <div>
          <div class="text-white/50">CCCD/Hộ chiếu</div>
          <div class="font-extrabold text-white/90">{{ v(enterprise.representative_id) }}</div>
        </div>

        <div>
          <div class="text-white/50">Created at</div>
          <div class="font-extrabold text-white/90">{{ v(enterprise.created_at) }}</div>
        </div>

        <div>
          <div class="text-white/50">Approved at</div>
          <div class="font-extrabold text-white/90">{{ v(enterprise.approved_at) }}</div>
        </div>

        <div class="md:col-span-2 flex gap-2 mt-2 flex-wrap">
          <UiButton variant="outline" size="sm" @click="back">Quay lại</UiButton>

          <UiButton v-if="enterprise.status !== 'approved'" size="sm" @click="approve">
            Approve
          </UiButton>

          <Link :href="route('sys.enterprises.index')">
            <UiButton variant="outline" size="sm">Về danh sách</UiButton>
          </Link>
        </div>
      </div>
    </UiCard>

    <!-- Attach -->
    <UiCard title="Tài liệu đính kèm" subtitle="Preview trực tiếp trên web">
      <div v-if="fileUrl" class="space-y-3">
        <div class="flex gap-3 text-sm">
          <a :href="fileUrl" target="_blank" class="text-brand-300 hover:underline">Mở tab mới</a>
          <a :href="fileUrl" download class="text-brand-300 hover:underline">Tải xuống</a>
        </div>

        <div>
          <iframe
            v-if="isPdf"
            :src="fileUrl"
            class="w-full rounded-xl border border-glass bg-black/30"
            style="height: 520px"
          ></iframe>

          <img
            v-else-if="isImage"
            :src="fileUrl"
            class="w-full rounded-xl border border-glass"
            alt="Tài liệu đính kèm"
          />

          <div v-else class="text-sm text-white/60">
            Không hỗ trợ preview loại file <b class="text-white/90">.{{ ext }}</b>. Vui lòng bấm “Mở tab mới”.
          </div>
        </div>

        <div class="text-xs text-white/50 break-all">URL: {{ fileUrl }}</div>
      </div>

      <div v-else class="text-sm text-white/60">Không có file đính kèm.</div>
    </UiCard>
  </div>
</template>