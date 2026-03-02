<script setup>
import { Head } from '@inertiajs/vue3'
import { ref } from 'vue'
import GuestLayout from '@/Layouts/GuestLayout.vue'
defineOptions({ layout: GuestLayout })

const props = defineProps({
  mode:       { type: String, default: 'public' },
  batch:      { type: Object, default: () => ({}) },
  events:     { type: Array,  default: () => [] },
  place_name: { type: String, default: null },
  expires_at: { type: String, default: null },
})

const open = ref(null)
const toggle = (id) => { open.value = open.value === id ? null : id }

const cteLabel = {
  land_prep: 'Chuẩn bị đất', seeding: 'Gieo sạ', crop_care: 'Chăm sóc',
  pesticide: 'Phun thuốc BVTV', harvest: 'Thu hoạch', sorting: 'Phân loại & Sơ chế',
  drying: 'Sấy / Phơi khô', milling: 'Xay xát', quality_check: 'Kiểm tra chất lượng',
  packaging: 'Đóng gói', warehousing: 'Nhập kho', distribution: 'Xuất kho / Phân phối',
  planting: 'Gieo trồng', transport: 'Vận chuyển', processing: 'Chế biến',
  production: 'Sản xuất', raw_material_import: 'Nhập nguyên liệu', custom: 'Sự kiện tùy chỉnh',
}
const getLabel = (code) => cteLabel[code] || code || 'Sự kiện'

const kdeHidden = new Set(['where_lat', 'where_lng', 'who_name', 'where_address'])
const kdeLabels = {
  what_variety: 'Giống', what_area: 'Diện tích', what_quantity: 'Số lượng',
  what_unit: 'Đơn vị', what_fertilizer: 'Phân bón', what_pesticide: 'Thuốc BVTV',
  what_dosage: 'Liều lượng', what_phi: 'Cách ly (ngày)', what_method: 'Phương pháp',
  what_pack_type: 'Bao bì', what_grade: 'Phân loại', what_result: 'Kết quả',
  what_cert: 'Số chứng nhận', what_warehouse: 'Kho', what_invoice: 'Số chứng từ',
  what_vehicle: 'Phương tiện', what_activity: 'Nội dung', who_receiver: 'Đơn vị nhận',
  who_supervisor: 'Giám sát', who_shipper: 'Đơn vị giao', where_from: 'Xuất phát',
  where_to: 'Điểm đến', why_standard: 'Tiêu chuẩn', why_note: 'Ghi chú',
}
const kdeRows = (data) => Object.entries(data || {})
  .filter(([k, v]) => !kdeHidden.has(k) && v !== null && v !== '' && v !== undefined)
  .map(([k, v]) => ({ label: kdeLabels[k] || k, value: v }))
</script>

<template>
  <Head :title="`Truy xuất: ${batch?.product?.name || batch?.product_name || 'Sản phẩm'}`" />

  <div class="max-w-md mx-auto px-4 py-5 space-y-4">

    <!-- Thu hồi warning -->
    <div v-if="batch?.status === 'recalled'"
      class="rounded-2xl border border-red-500/50 bg-red-500/10 px-4 py-3">
      <p class="text-red-400 font-bold text-sm">Lô này đã bị thu hồi</p>
      <p class="text-red-300/70 text-xs mt-0.5">Vui lòng không sử dụng sản phẩm từ lô này</p>
    </div>

    <!-- Thông tin sản phẩm -->
    <div class="rounded-2xl border border-glass bg-black/40 overflow-hidden">
      <div v-if="batch?.product?.image_path" class="h-44 bg-black/20">
        <img :src="`/storage/${batch.product.image_path}`"
          class="w-full h-full object-cover" :alt="batch.product?.name" />
      </div>

      <div class="p-4 space-y-3">
        <div class="flex items-start justify-between gap-2">
          <div>
            <p class="text-xs text-white/40 mb-0.5">
              {{ batch?.product?.category?.name_vi || 'Sản phẩm' }}
            </p>
            <h1 class="text-lg font-bold text-white/95 leading-tight">
              {{ batch?.product?.name || batch?.product_name }}
            </h1>
          </div>
          <span class="shrink-0 text-xs px-2 py-0.5 rounded-full border font-semibold"
            :class="batch?.status === 'recalled'
              ? 'border-red-500/40 text-red-400 bg-red-500/10'
              : batch?.status === 'completed'
                ? 'border-green-500/40 text-green-400 bg-green-500/10'
                : 'border-brand-500/40 text-brand-300 bg-brand-500/10'">
            {{ batch?.status === 'recalled' ? 'Thu hồi' : batch?.status === 'completed' ? 'Hoàn thành' : 'Đang lưu hành' }}
          </span>
        </div>

        <p v-if="batch?.product?.description" class="text-sm text-white/50 leading-relaxed">
          {{ batch.product.description }}
        </p>

        <div class="grid grid-cols-2 gap-2">
          <div class="bg-white/5 rounded-xl px-3 py-2">
            <p class="text-white/40 text-xs">Mã lô</p>
            <p class="font-mono font-bold text-brand-300">{{ batch?.code }}</p>
          </div>
          <div v-if="batch?.product?.gtin" class="bg-white/5 rounded-xl px-3 py-2">
            <p class="text-white/40 text-xs">GTIN</p>
            <p class="font-mono text-white/80 text-sm">{{ batch.product.gtin }}</p>
          </div>
          <div v-if="batch?.production_date" class="bg-white/5 rounded-xl px-3 py-2">
            <p class="text-white/40 text-xs">Ngày sản xuất</p>
            <p class="text-white/90 font-medium text-sm">{{ batch.production_date }}</p>
          </div>
          <div v-if="batch?.expiry_date" class="bg-white/5 rounded-xl px-3 py-2">
            <p class="text-white/40 text-xs">Hạn sử dụng</p>
            <p class="text-white/90 font-medium text-sm">{{ batch.expiry_date }}</p>
          </div>
          <div v-if="batch?.quantity" class="bg-white/5 rounded-xl px-3 py-2">
            <p class="text-white/40 text-xs">Số lượng</p>
            <p class="text-white/90 font-medium text-sm">{{ batch.quantity }} {{ batch.unit }}</p>
          </div>
        </div>
      </div>
    </div>

    <!-- Thông tin doanh nghiệp -->
    <div v-if="batch?.enterprise" class="rounded-2xl border border-glass bg-black/30 p-4">
      <p class="text-xs text-white/40 uppercase tracking-wider mb-2">Doanh nghiệp sản xuất</p>
      <p class="font-bold text-white/90 text-sm">{{ batch.enterprise.name }}</p>
      <p v-if="batch.enterprise.address" class="text-xs text-white/50 mt-0.5">{{ batch.enterprise.address }}</p>
      <p v-if="batch.enterprise.phone" class="text-xs text-white/50">{{ batch.enterprise.phone }}</p>
    </div>

    <!-- QR context -->
    <div v-if="mode === 'public' && place_name"
      class="rounded-2xl border border-white/15 bg-white/5 px-4 py-3">
      <p class="text-white/70 text-sm font-semibold">Da xac thuc vi tri</p>
      <p class="text-white/40 text-xs mt-0.5">Diem phat hanh: {{ place_name }}</p>
    </div>
    <div v-if="mode === 'private' && expires_at"
      class="rounded-2xl border border-white/15 bg-white/5 px-4 py-3">
      <p class="text-white/70 text-sm font-semibold">QR rieng tu</p>
      <p class="text-white/40 text-xs mt-0.5">Het hieu luc: {{ expires_at }}</p>
    </div>

    <!-- Timeline sự kiện -->
    <div class="rounded-2xl border border-glass bg-black/30 overflow-hidden">
      <div class="px-4 py-3 border-b border-white/5 flex items-center justify-between">
        <div>
          <p class="text-xs text-white/40 uppercase tracking-wider">Lich su truy xuat</p>
          <p class="text-sm font-bold text-white/90 mt-0.5">{{ events.length }} moc da xac nhan</p>
        </div>
        <span class="text-xs px-2 py-1 rounded-lg border border-glass text-white/30">TCVN 12850</span>
      </div>

      <p v-if="!events.length" class="p-6 text-center text-white/30 text-sm">
        Chua co moc truy xuat nao duoc xac nhan.
      </p>

      <div v-else>
        <div v-for="(ev, idx) in events" :key="ev.id" class="border-b border-white/5 last:border-0">

          <!-- Header row -->
          <button class="w-full text-left px-4 py-3 flex items-center gap-3 active:bg-white/5"
            @click="toggle(ev.id)">
            <div class="w-6 h-6 rounded-full border-2 flex items-center justify-center text-xs font-bold shrink-0"
              :class="ev.ipfs_cid
                ? 'border-green-500/60 bg-green-500/10 text-green-400'
                : 'border-brand-500/50 bg-brand-500/10 text-brand-400'">
              {{ idx + 1 }}
            </div>
            <div class="flex-1 min-w-0">
              <div class="flex items-center justify-between gap-2">
                <p class="font-semibold text-white/90 text-sm truncate">{{ getLabel(ev.cte_code) }}</p>
                <span v-if="ev.ipfs_cid"
                  class="shrink-0 text-xs px-1.5 py-0.5 rounded bg-green-500/15 text-green-400 border border-green-500/25">
                  IPFS
                </span>
              </div>
              <p class="text-xs text-white/40 mt-0.5 truncate">
                {{ ev.event_time }}
                <template v-if="ev.who_name"> · {{ ev.who_name }}</template>
                <template v-if="ev.where_address"> · {{ ev.where_address }}</template>
              </p>
            </div>
            <span class="text-white/25 text-xs shrink-0">{{ open === ev.id ? '▲' : '▼' }}</span>
          </button>

          <!-- Detail panel -->
          <div v-if="open === ev.id" class="px-4 pb-4 space-y-3">

            <!-- KDE rows -->
            <div v-if="kdeRows(ev.kde_data).length" class="bg-white/5 rounded-xl p-3 space-y-2">
              <div v-for="row in kdeRows(ev.kde_data)" :key="row.label" class="flex gap-3 text-sm">
                <span class="text-white/40 w-28 shrink-0 text-xs leading-5">{{ row.label }}</span>
                <span class="text-white/80 flex-1">{{ row.value }}</span>
              </div>
            </div>

            <!-- GPS -->
            <a v-if="ev.where_lat && ev.where_lng"
              :href="`https://maps.google.com/?q=${ev.where_lat},${ev.where_lng}`"
              target="_blank"
              class="block text-xs text-brand-400 underline">
              Xem vi tri tren ban do
            </a>

            <!-- Note -->
            <p v-if="ev.note" class="text-xs text-white/50 bg-white/5 rounded-xl px-3 py-2">
              {{ ev.note }}
            </p>

            <!-- Attachments -->
            <div v-if="ev.attachments?.length" class="grid grid-cols-1 gap-2">
              <a v-for="att in ev.attachments" :key="att.cid"
                :href="att.url" target="_blank"
                class="flex items-center gap-2 px-3 py-2 bg-white/5 border border-glass rounded-xl text-xs text-white/60 hover:bg-white/10 transition">
                <span class="truncate">{{ att.name }}</span>
              </a>
            </div>

            <!-- IPFS -->
            <div v-if="ev.ipfs_cid"
              class="rounded-xl border border-green-500/20 bg-green-500/5 p-3 space-y-1">
              <p class="text-xs text-green-400 font-semibold">Du lieu da xac nhan tren IPFS</p>
              <p class="font-mono text-xs text-white/30 break-all">{{ ev.ipfs_cid }}</p>
              <div class="flex gap-3 pt-1 flex-wrap">
                <a v-if="ev.ipfs_url" :href="ev.ipfs_url" target="_blank"
                  class="text-xs text-green-400/80 underline">Xem tren IPFS</a>
                <a v-if="ev.content_hash" :href="`/verify/ipfs/${ev.ipfs_cid}?hash=${ev.content_hash}`"
                  target="_blank" class="text-xs text-brand-400/80 underline">Xac minh toan ven</a>
              </div>
            </div>

            <!-- Blockchain -->
            <div v-if="ev.tx_hash"
              class="rounded-xl border border-blue-500/20 bg-blue-500/5 p-3">
              <p class="text-xs text-blue-400 font-semibold">Da ghi len Blockchain</p>
              <p class="font-mono text-xs text-white/30 break-all mt-1">{{ ev.tx_hash }}</p>
            </div>
          </div>
        </div>
      </div>
    </div>

    <p class="text-center text-xs text-white/20 pb-4">AGU Traceability · TCVN 12850:2019</p>
  </div>
</template>