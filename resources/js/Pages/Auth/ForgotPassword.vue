<script setup>
import { Head, useForm } from '@inertiajs/vue3'

defineProps({
  status: { type: String, default: null },
})

const form = useForm({ email: '' })
const submit = () => form.post(route('password.email'))

const inputCls =
  'mt-2 w-full rounded-lg border border-glass bg-black/25 px-4 py-2.5 text-white/90 placeholder:text-white/40 ' +
  'focus:border-brand-500/60 focus:ring-brand-500/20 hover:border-brand-500/30 transition'
</script>

<template>
  <Head title="Quên mật khẩu" />

  <div class="mb-6">
    <div class="text-2xl font-black text-white/90">Quên mật khẩu?</div>
    <div class="text-sm text-white/50 mt-1">
      Nhập email và chúng tôi sẽ gửi link đặt lại mật khẩu cho bạn.
    </div>
  </div>

  <div v-if="status" class="mb-4 rounded-xl border border-green-500/30 bg-green-500/10 px-4 py-3 text-sm text-green-400">
    {{ status }}
  </div>

  <form class="space-y-5" @submit.prevent="submit">
    <div>
      <label class="block text-sm font-medium text-white/70">Email</label>
      <input v-model="form.email" type="email" autocomplete="email"
        :class="inputCls" placeholder="Nhập email tài khoản" autofocus />
      <div v-if="form.errors.email" class="text-sm text-red-400 mt-1">{{ form.errors.email }}</div>
    </div>

    <button type="submit" :disabled="form.processing"
      class="w-full rounded-lg bg-brand-500 text-cosmic-950 font-extrabold py-2.5 hover:bg-brand-600 transition disabled:opacity-60">
      {{ form.processing ? 'Đang gửi...' : 'GỬI LINK ĐẶT LẠI MẬT KHẨU' }}
    </button>

    <div class="text-center text-sm text-white/60">
      Nhớ mật khẩu rồi?
      <a href="/login" class="text-brand-300 hover:underline font-semibold">Đăng nhập</a>
    </div>
  </form>
</template>