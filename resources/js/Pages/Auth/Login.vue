<script setup>
import { Head, useForm } from '@inertiajs/vue3'

const form = useForm({
  email: '',
  password: '',
  remember: false,
})

const submit = () => {
  form.post('/login', { preserveScroll: true })
}

const inputCls =
  'mt-2 w-full rounded-lg border border-glass bg-black/25 px-4 py-2.5 text-white/90 placeholder:text-white/40 ' +
  'focus:border-brand-500/60 focus:ring-brand-500/20 hover:border-brand-500/30 transition'
</script>

<template>
  <Head title="Đăng nhập" />

  <form class="space-y-5" @submit.prevent="submit">
    <div>
      <label class="block text-sm font-medium text-white/70">Email</label>
      <input v-model="form.email" type="email" autocomplete="email" :class="inputCls" placeholder="Nhập email" />
      <div v-if="form.errors.email" class="text-sm text-red-400 mt-1">{{ form.errors.email }}</div>
    </div>

    <div>
      <label class="block text-sm font-medium text-white/70">Mật khẩu</label>
      <input v-model="form.password" type="password" autocomplete="current-password" :class="inputCls" placeholder="Nhập mật khẩu" />
      <div v-if="form.errors.password" class="text-sm text-red-400 mt-1">{{ form.errors.password }}</div>
    </div>

    <div class="flex items-center justify-between text-sm">
      <label class="flex items-center gap-2 text-white/70">
        <input type="checkbox" v-model="form.remember" class="rounded border-glass bg-black/30 text-brand-500 focus:ring-brand-500/20" />
        Ghi nhớ
      </label>
      <a href="/forgot-password" class="text-brand-300 hover:underline">Quên mật khẩu?</a>
    </div>

    <button
      type="submit"
      :disabled="form.processing"
      class="w-full rounded-lg bg-brand-500 text-cosmic-950 font-extrabold py-2.5 hover:bg-brand-600 transition disabled:opacity-60"
    >
      {{ form.processing ? 'Đang đăng nhập...' : 'ĐĂNG NHẬP' }}
    </button>

    <div class="text-center text-sm text-white/60">
      Chưa có tài khoản?
      <a href="/onboarding/enterprise" class="text-brand-300 hover:underline font-semibold">Đăng ký ngay</a>
    </div>
  </form>
</template>