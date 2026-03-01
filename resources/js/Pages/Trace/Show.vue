<script setup>
import { Head } from "@inertiajs/vue3";
defineProps({ mode: String, batch: Object, events: Array, place_name: String, expires_at: String });
</script>

<template>
  <Head title="Truy xuất" />
  <div class="p-6 max-w-4xl mx-auto space-y-4">
    <div class="bg-white border rounded-2xl p-5">
      <div class="flex items-start justify-between gap-3">
        <div>
          <h1 class="text-xl font-semibold">Thông tin lô</h1>
          <p class="text-sm text-gray-600">
            Mã lô: <b>{{ batch.code }}</b> — {{ batch.product_name }}
          </p>
          <p v-if="mode==='public' && place_name" class="text-sm text-gray-600">
            Điểm phát hành: <b>{{ place_name }}</b>
          </p>
          <p v-if="mode==='private' && expires_at" class="text-sm text-gray-600">
            Hết hiệu lực lúc: <b>{{ expires_at }}</b>
          </p>
        </div>
        <span class="text-xs px-2 py-1 rounded-full border">
          {{ mode === 'public' ? 'PUBLIC' : 'PRIVATE' }}
        </span>
      </div>
    </div>

    <div class="bg-white border rounded-2xl p-5">
      <h2 class="font-semibold mb-3">Timeline (Published)</h2>

      <div v-if="!events || !events.length" class="text-sm text-gray-600">
        Chưa có sự kiện published.
      </div>

      <div v-else class="space-y-3">
        <div v-for="(e, idx) in events" :key="idx" class="border rounded-xl p-3">
          <div class="flex justify-between">
            <div class="font-medium">{{ e.event_type }}</div>
            <div class="text-sm text-gray-600">{{ e.event_time }}</div>
          </div>
          <div class="text-sm text-gray-700" v-if="e.location">Địa điểm: {{ e.location }}</div>
          <div class="text-sm text-gray-700" v-if="e.note">Ghi chú: {{ e.note }}</div>
          <div class="text-xs text-gray-500" v-if="e.tx_hash">Tx: {{ e.tx_hash }}</div>
        </div>
      </div>
    </div>
  </div>
</template>
