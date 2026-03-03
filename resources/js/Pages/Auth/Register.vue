<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3'

const form = useForm({
  name:                  '',
  email:                 '',
  password:              '',
  password_confirmation: '',
})

const submit = () => {
  form.post(route('register'), {
    onFinish: () => form.reset('password', 'password_confirmation'),
  })
}

const inputCls =
  'mt-2 w-full rounded-lg border border-glass bg-black/25 px-4 py-2.5 text-white/90 placeholder:text-white/40 ' +
  'focus:border-brand-500/60 focus:ring-brand-500/20 hover:border-brand-500/30 transition'
</script>

<template>
  <Head title="Đăng ký tài khoản" />

  <div class="mb-6">
    <div class="text-2xl font-black text-white/90">Đăng ký tài khoản</div>
    <div class="text-sm text-white/50 mt-1">
      Tạo tài khoản để đăng nhập hệ thống
      <span class="font-semibold text-brand-400">AGU Traceability</span>
    </div>
  </div>

  <form class="space-y-4" @submit.prevent="submit">
    <div>
      <label class="block text-sm font-medium text-white/70">Họ tên</label>
      <input v-model="form.name" type="text" autocomplete="name" :class="inputCls" placeholder="Nhập họ tên" />
      <div v-if="form.errors.name" class="text-sm text-red-400 mt-1">{{ form.errors.name }}</div>
    </div>

    <div>
      <label class="block text-sm font-medium text-white/70">Email</label>
      <input v-model="form.email" type="email" autocomplete="email" :class="inputCls" placeholder="Nhập email" />
      <div v-if="form.errors.email" class="text-sm text-red-400 mt-1">{{ form.errors.email }}</div>
    </div>

    <div>
      <label class="block text-sm font-medium text-white/70">Mật khẩu</label>
      <input v-model="form.password" type="password" autocomplete="new-password" :class="inputCls" placeholder="Tối thiểu 8 ký tự" />
      <div v-if="form.errors.password" class="text-sm text-red-400 mt-1">{{ form.errors.password }}</div>
    </div>

    <div>
      <label class="block text-sm font-medium text-white/70">Nhập lại mật khẩu</label>
      <input v-model="form.password_confirmation" type="password" autocomplete="new-password" :class="inputCls" placeholder="Nhập lại mật khẩu" />
      <div v-if="form.errors.password_confirmation" class="text-sm text-red-400 mt-1">{{ form.errors.password_confirmation }}</div>
    </div>

    <button
      type="submit"
      :disabled="form.processing"
      class="w-full rounded-lg bg-brand-500 text-cosmic-950 font-extrabold py-2.5 hover:bg-brand-600 transition disabled:opacity-60"
    >
      {{ form.processing ? 'Đang tạo tài khoản...' : 'TẠO TÀI KHOẢN' }}
    </button>

    <div class="text-center text-sm text-white/60">
      Đã có tài khoản?
      <Link :href="route('login')" class="text-brand-300 hover:underline font-semibold">Đăng nhập</Link>
    </div>
  </form>
</template>