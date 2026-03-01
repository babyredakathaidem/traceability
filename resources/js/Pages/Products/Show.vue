<script setup>
import { Head, Link, router } from '@inertiajs/vue3'
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
            <a :href="route('batches.qrs', b.id)">
              <UiButton size="sm" variant="outline">QR</UiButton>
            </a>
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