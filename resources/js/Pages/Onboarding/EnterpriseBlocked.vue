<script setup>
import { Head } from '@inertiajs/vue3'
defineProps({
  blocked_reason: { type: String, default: null },
  enterprise:     { type: Object, default: () => ({}) },
})
</script>

<template>
  <Head title="Tài khoản bị khóa" />
  <div class="text-center py-4">
    <div class="w-20 h-20 rounded-full bg-white/10 border-2 border-white/20 flex items-center justify-center mx-auto mb-5">
      <svg class="w-9 h-9 text-white/40" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
        <path stroke-linecap="round" stroke-linejoin="round"
          d="M12 15v2m0 0v2m0-2h2m-2 0H10m2-6V9a4 4 0 00-8 0v3M5 21h14a2 2 0 002-2v-7a2 2 0 00-2-2H5a2 2 0 00-2 2v7a2 2 0 002 2z" />
      </svg>
    </div>
    <div class="text-2xl font-black text-white/70 mb-2">Tài khoản đã bị khóa</div>
    <div class="text-sm text-white/40 mb-4">
      Doanh nghiệp <span class="text-white/70 font-semibold">{{ enterprise?.name }}</span>
      đã bị tạm khóa bởi quản trị viên.
    </div>
    <div v-if="blocked_reason"
      class="mx-auto max-w-sm rounded-xl border border-white/10 bg-white/5 px-4 py-3 text-sm text-white/60 mb-6">
      <div class="text-xs text-white/30 mb-1">Lý do:</div>
      {{ blocked_reason }}
    </div>
    <div class="text-sm text-white/30">Vui lòng liên hệ quản trị viên để được hỗ trợ.</div>
    <a href="/logout"
      onclick="event.preventDefault(); fetch('/logout',{method:'POST',headers:{'X-CSRF-TOKEN':document.querySelector('meta[name=csrf-token]').content}}).then(()=>location.href='/')"
      class="mt-6 inline-block px-5 py-2 rounded-xl border border-glass text-white/30 hover:bg-white/5 text-sm transition">
      Đăng xuất
    </a>
  </div>
</template>