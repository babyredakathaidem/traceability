<script setup>
import { Head } from '@inertiajs/vue3'
import { onMounted, onBeforeUnmount, ref } from 'vue'

defineProps({
  enterprise: { type: Object, default: () => ({}) },
})

const checking = ref(false)
const errorMsg  = ref(null)
let   timer     = null

async function checkStatus() {
  try {
    checking.value = true
    errorMsg.value = null
    const res  = await fetch(route('onboarding.enterprise.status'), {
      headers: { Accept: 'application/json' }, credentials: 'same-origin',
    })
    if (!res.ok) throw new Error()
    const data = await res.json()

    if (!data.has_enterprise) { window.location.href = route('onboarding.enterprise.create'); return }
    if (data.status === 'approved') { window.location.href = route('dashboard'); return }
    if (data.status === 'rejected') { window.location.href = route('onboarding.enterprise.rejected'); return }
  } catch {
    errorMsg.value = 'Không thể kiểm tra trạng thái. Vui lòng thử lại.'
  } finally {
    checking.value = false
  }
}

onMounted(async () => {
  await checkStatus()
  timer = setInterval(checkStatus, 3000)
})
onBeforeUnmount(() => clearInterval(timer))
</script>

<template>
  <Head title="Chờ duyệt doanh nghiệp" />

  <div class="text-center py-4">
    <!-- Spinner icon -->
    <div class="w-20 h-20 rounded-full bg-brand-500/10 border-2 border-brand-500/30 flex items-center justify-center mx-auto mb-5">
      <svg class="w-9 h-9 text-brand-400 animate-spin" fill="none" viewBox="0 0 24 24">
        <circle class="opacity-20" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3"/>
        <path class="opacity-80" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"/>
      </svg>
    </div>

    <div class="text-2xl font-black text-white/90 mb-2">Đang chờ duyệt</div>
    <div class="text-sm text-white/50 leading-relaxed mb-1">
      Hồ sơ doanh nghiệp <span class="text-white/80 font-semibold">{{ enterprise?.name }}</span>
      đã được gửi lên hệ thống.
    </div>
    <div class="text-sm text-white/40 mb-6">
      Quản trị viên sẽ xem xét và phản hồi qua email trong thời gian sớm nhất.
    </div>

    <!-- Status indicator -->
    <div class="inline-flex items-center gap-2 px-4 py-2 rounded-full border border-brand-500/30 bg-brand-500/10 text-brand-300 text-sm mb-6">
      <span class="w-2 h-2 rounded-full bg-brand-400 animate-pulse"></span>
      Tự động kiểm tra mỗi 3 giây...
    </div>

    <div v-if="errorMsg"
      class="mx-auto max-w-xs rounded-xl border border-red-500/30 bg-red-500/10 px-4 py-2 text-sm text-red-400 mb-4">
      {{ errorMsg }}
    </div>

    <div class="flex gap-3 justify-center">
      <button @click="checkStatus" :disabled="checking"
        class="px-5 py-2 rounded-xl border border-glass text-white/50 hover:bg-white/5 text-sm transition disabled:opacity-40">
        {{ checking ? 'Đang kiểm tra...' : 'Kiểm tra ngay' }}
      </button>
      <a href="/logout"
        onclick="event.preventDefault(); fetch('/logout',{method:'POST',headers:{'X-CSRF-TOKEN':document.querySelector('meta[name=csrf-token]').content}}).then(()=>location.href='/')"
        class="px-5 py-2 rounded-xl border border-glass text-white/30 hover:bg-white/5 text-sm transition">
        Đăng xuất
      </a>
    </div>
  </div>
</template>