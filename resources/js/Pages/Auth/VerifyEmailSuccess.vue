<script setup>
import { Head } from '@inertiajs/vue3'
</script>

<template>
  <Head title="Email đã xác minh" />

  <div class="text-center py-4">
    <!-- Tick cam -->
    <div class="w-20 h-20 rounded-full bg-brand-500/20 border-2 border-brand-500/60 flex items-center justify-center mx-auto mb-5">
      <svg class="w-10 h-10 text-brand-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
      </svg>
    </div>

    <div class="text-2xl font-black text-white/90 mb-2">Email đã xác minh!</div>
    <div class="text-sm text-white/50 mb-8 leading-relaxed">
      Địa chỉ email của bạn đã được xác minh thành công.<br />
      Bạn có thể đăng nhập và sử dụng hệ thống.
    </div>

    <a href="/login"
      class="inline-block px-8 py-2.5 rounded-lg bg-brand-500 text-cosmic-950 font-extrabold hover:bg-brand-600 transition">
      ĐĂNG NHẬP NGAY
    </a>
  </div>
</template>