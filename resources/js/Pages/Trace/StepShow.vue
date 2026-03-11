<script setup>
import { Head } from '@inertiajs/vue3'
import {
  CheckCircleIcon,
  ClockIcon,
  MapPinIcon,
  UserIcon,
  LinkIcon,
  ArchiveBoxIcon,
} from '@heroicons/vue/24/outline'

const props = defineProps({
  event: { type: Object, required: true },
})

const statusColor = {
  published: 'text-green-400 bg-green-500/10 border-green-500/30',
  draft: 'text-amber-400 bg-amber-500/10 border-amber-500/30',
}

const statusLabel = {
  published: 'Đã xác thực & Lưu bất biến',
  draft: 'Chưa xác thực',
}
</script>

<template>
  <Head :title="`Bước: ${event.cte_code ?? 'Sự kiện truy xuất'}`" />

  <div class="min-h-screen bg-[#050505] text-white/90 font-sans">

    <!-- Header branding -->
    <div class="bg-black/60 border-b border-white/5 px-6 py-4 flex items-center gap-3">
      <div class="w-8 h-8 rounded-xl bg-orange-500 flex items-center justify-center">
        <CheckCircleIcon class="w-5 h-5 text-black" />
      </div>
      <span class="font-black text-white text-sm uppercase tracking-widest">AGU <span class="text-orange-400 font-medium lowercase">Trace</span></span>
      <span class="ml-auto text-[10px] text-white/20 uppercase tracking-widest">Bước sản xuất</span>
    </div>

    <div class="max-w-lg mx-auto px-4 py-8 space-y-5">

      <!-- Status badge -->
      <div class="flex justify-center">
        <span
          class="text-xs px-4 py-1.5 rounded-full border font-black uppercase tracking-widest"
          :class="statusColor[event.status] ?? statusColor.draft"
        >
          {{ statusLabel[event.status] ?? event.status }}
        </span>
      </div>

      <!-- Main card: step info -->
      <div class="rounded-3xl border border-white/10 bg-white/5 p-6 space-y-4">
        <div class="text-center space-y-1">
          <div class="text-[10px] text-white/30 uppercase tracking-widest font-black">Công đoạn sản xuất</div>
          <div class="text-2xl font-black text-white">{{ event.cte_code ?? 'Sự kiện truy xuất' }}</div>
        </div>

        <div class="divide-y divide-white/5">

          <!-- Batch info -->
          <div v-if="event.batch" class="flex items-start gap-3 py-3">
            <ArchiveBoxIcon class="w-5 h-5 text-white/30 shrink-0 mt-0.5" />
            <div>
              <div class="text-[10px] text-white/30 uppercase font-black tracking-widest">Lô hàng</div>
              <div class="font-mono text-sm text-orange-400 font-bold">{{ event.batch.code }}</div>
              <div class="text-xs text-white/50 mt-0.5">{{ event.batch.product_name }}</div>
              <div class="text-xs text-white/30 mt-0.5">{{ event.batch.enterprise?.name }}</div>
            </div>
          </div>

          <!-- Time -->
          <div class="flex items-start gap-3 py-3">
            <ClockIcon class="w-5 h-5 text-white/30 shrink-0 mt-0.5" />
            <div>
              <div class="text-[10px] text-white/30 uppercase font-black tracking-widest">Thời gian thực hiện</div>
              <div class="text-sm text-white/80 font-semibold">{{ event.event_time ?? '—' }}</div>
            </div>
          </div>

          <!-- Who -->
          <div v-if="event.who_name" class="flex items-start gap-3 py-3">
            <UserIcon class="w-5 h-5 text-white/30 shrink-0 mt-0.5" />
            <div>
              <div class="text-[10px] text-white/30 uppercase font-black tracking-widest">Người thực hiện</div>
              <div class="text-sm text-white/80 font-semibold">{{ event.who_name }}</div>
            </div>
          </div>

          <!-- Where -->
          <div v-if="event.where_address" class="flex items-start gap-3 py-3">
            <MapPinIcon class="w-5 h-5 text-white/30 shrink-0 mt-0.5" />
            <div>
              <div class="text-[10px] text-white/30 uppercase font-black tracking-widest">Địa điểm</div>
              <div class="text-sm text-white/80">{{ event.where_address }}</div>
            </div>
          </div>

          <!-- Note -->
          <div v-if="event.note" class="py-3">
            <div class="text-[10px] text-white/30 uppercase font-black tracking-widest mb-1">Ghi chú</div>
            <div class="text-sm text-white/60">{{ event.note }}</div>
          </div>

          <!-- KDE extra data -->
          <div v-if="event.kde_data && Object.keys(event.kde_data).length" class="py-3">
            <div class="text-[10px] text-white/30 uppercase font-black tracking-widest mb-2">Dữ liệu chi tiết</div>
            <div class="space-y-1.5">
              <div v-for="(val, key) in event.kde_data" :key="key"
                class="flex justify-between text-xs gap-4 py-1 border-b border-white/5 last:border-0">
                <span class="text-white/30 font-mono shrink-0">{{ key }}</span>
                <span class="text-white/70 text-right truncate">{{ typeof val === 'object' ? JSON.stringify(val) : val }}</span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Attachments -->
      <div v-if="event.attachments?.length" class="rounded-3xl border border-white/10 bg-white/5 p-5 space-y-3">
        <div class="text-[10px] text-white/30 uppercase font-black tracking-widest">Hình ảnh & tài liệu đính kèm</div>
        <div class="grid grid-cols-2 gap-3">
          <a v-for="att in event.attachments" :key="att.cid"
            :href="att.url" target="_blank"
            class="block rounded-2xl border border-white/10 bg-black/30 p-3 hover:border-orange-500/30 transition">
            <div class="text-[10px] text-white/50 font-mono truncate">{{ att.name || att.cid }}</div>
            <div class="text-[9px] text-white/20 mt-0.5">{{ att.mime_type }}</div>
          </a>
        </div>
      </div>

      <!-- IPFS verification -->
      <div v-if="event.ipfs_cid" class="rounded-3xl border border-green-500/20 bg-green-500/5 p-5 space-y-3">
        <div class="flex items-center gap-2">
          <CheckCircleIcon class="w-5 h-5 text-green-400 shrink-0" />
          <div class="text-sm font-black text-green-400">Dữ liệu lưu bất biến trên IPFS</div>
        </div>
        <div>
          <div class="text-[10px] text-white/30 mb-1">IPFS CID — Mã truy vết bất biến</div>
          <div class="font-mono text-[11px] text-green-300/60 break-all">{{ event.ipfs_cid }}</div>
        </div>
        <a v-if="event.ipfs_url" :href="event.ipfs_url" target="_blank"
          class="flex items-center gap-2 text-xs text-green-400 hover:text-green-300 transition font-semibold">
          <LinkIcon class="w-4 h-4" />
          Xem trên IPFS Gateway
        </a>
      </div>

      <!-- Not yet published warning -->
      <div v-else class="rounded-3xl border border-amber-500/20 bg-amber-500/5 p-5 text-sm text-amber-400">
        ⚠ Dữ liệu bước này chưa được công bố lên IPFS. Sẽ được xác thực sau khi DN publish sự kiện.
      </div>

      <div class="text-center text-[10px] text-white/20 pt-4">
        AGU Trace — Hệ thống truy xuất nguồn gốc chuẩn Thông tư 02/2024/TT-BKHCN
      </div>
    </div>
  </div>
</template>
