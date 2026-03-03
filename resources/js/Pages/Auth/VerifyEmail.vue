<script setup>
import { Head,Link, useForm } from '@inertiajs/vue3'
import { computed, onMounted, onBeforeUnmount, ref } from 'vue'

const props = defineProps({
  status: { type: String, default: null },
})

const form       = useForm({})
const justSent   = computed(() => props.status === 'verification-link-sent')
const verified   = ref(false)
let   pollTimer  = null

async function checkVerified() {
  try {
    const res  = await fetch('/auth/email-status', { headers: { Accept: 'application/json' }, credentials: 'same-origin' })
    const data = await res.json()
    if (data.verified) {
      verified.value = true
      clearInterval(pollTimer)
      // Tự redirect về login sau 2 giây
      setTimeout(() => { window.location.href = '/login' }, 2000)
    }
  } catch {}
}

onMounted(() => {
  pollTimer = setInterval(checkVerified, 3000)
})
onBeforeUnmount(() => clearInterval(pollTimer))

const submit = () => form.post(route('verification.send'))
</script>

<template>
  <Head title="Xác minh email" />

  <!-- Trạng thái đã verify (polling phát hiện) -->
  <div v-if="verified" class="text-center py-4">
    <div class="w-16 h-16 rounded-full bg-green-500/20 border-2 border-green-500/60 flex items-center justify-center mx-auto mb-4">
      <svg class="w-8 h-8 text-green-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
      </svg>
    </div>
    <div class="text-lg font-bold text-white/90 mb-1">Email đã xác minh!</div>
    <div class="text-sm text-white/40">Đang chuyển về trang đăng nhập...</div>
  </div>

  <!-- Trạng thái chờ verify -->
  <div v-else>
    <div class="mb-6">
      <div class="text-2xl font-black text-white/90">Xác minh email</div>
      <div class="text-sm text-white/50 mt-2 leading-relaxed">
        Chúng tôi đã gửi link xác minh đến email của bạn. Vui lòng kiểm tra hộp thư
        và bấm vào link để kích hoạt tài khoản.
      </div>
    </div>

    <div v-if="justSent"
      class="mb-4 rounded-xl border border-green-500/30 bg-green-500/10 px-4 py-3 text-sm text-green-400">
      Link xác minh mới đã được gửi. Vui lòng kiểm tra email.
    </div>

    <form @submit.prevent="submit" class="space-y-4">
      <button type="submit" :disabled="form.processing"
        class="w-full rounded-lg bg-brand-500 text-cosmic-950 font-extrabold py-2.5 hover:bg-brand-600 transition disabled:opacity-60">
        {{ form.processing ? 'Đang gửi...' : 'GỬI LẠI EMAIL XÁC MINH' }}
      </button>

      <div class="text-center text-xs text-white/30 animate-pulse">
        Tự động phát hiện khi email được xác minh...
      </div>

      <div class="text-center text-sm text-white/60">
        Nhầm tài khoản?
        <Link :href="route('logout')" method="post" as="button"
            class="text-brand-300 hover:underline font-semibold">
            Đăng nhập bằng tài khoản khác
        </Link>
      </div>
    </form>
  </div>
</template>