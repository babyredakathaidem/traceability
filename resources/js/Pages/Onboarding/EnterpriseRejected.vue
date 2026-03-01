<script setup>
import { Head } from '@inertiajs/vue3'

const props = defineProps({
  rejection_reason: String,
})
</script>

<template>
  <Head title="Doanh nghiệp bị từ chối" />
  <div class="min-h-screen flex items-center justify-center bg-gray-50 p-4">
    <div class="max-w-xl w-full bg-white rounded-2xl shadow p-6">
      <h1 class="text-xl font-semibold text-red-600">Hồ sơ doanh nghiệp bị từ chối</h1>
      <p class="text-gray-600 mt-2">Vui lòng kiểm tra lý do và đăng ký lại hoặc liên hệ quản trị.</p>

      <div v-if="rejection_reason" class="mt-4 p-3 rounded-lg bg-red-50 text-red-700 text-sm">
        Lý do: {{ rejection_reason }}
      </div>
    </div>
  </div>
</template>
