<script setup>
import { Head, Link, useForm, usePage } from '@inertiajs/vue3'
import InputError from '@/Components/InputError.vue'
import InputLabel from '@/Components/InputLabel.vue'
import PrimaryButton from '@/Components/PrimaryButton.vue'
import TextInput from '@/Components/TextInput.vue'

const page = usePage()

const form = useForm({
  name: '',
  email: '',
  password: '',
  password_confirmation: '',
})

const submit = () => {
  form.post(route('register'), {
    onFinish: () => form.reset('password', 'password_confirmation'),
  })
}
</script>

<template>
  <Head title="Đăng ký tài khoản" />

  <div>
    <div class="mb-6">
      <div class="text-2xl font-black text-black">Đăng ký tài khoản</div>
      <div class="text-sm text-gray-600 mt-1">
        Tạo tài khoản để đăng nhập hệ thống <span class="font-semibold text-orange-600">AGU Traceability</span>
      </div>
    </div>

    <div v-if="page.props?.flash?.success" class="mb-4 rounded-2xl border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">
      {{ page.props.flash.success }}
    </div>

    <form @submit.prevent="submit" class="space-y-4">
      <div>
        <InputLabel for="name" value="Họ tên" />
        <TextInput id="name" type="text" class="mt-1 block w-full rounded-xl"
          v-model="form.name" required autofocus autocomplete="name" />
        <InputError class="mt-2" :message="form.errors.name" />
      </div>

      <div>
        <InputLabel for="email" value="Email" />
        <TextInput id="email" type="email" class="mt-1 block w-full rounded-xl"
          v-model="form.email" required autocomplete="username" />
        <InputError class="mt-2" :message="form.errors.email" />
      </div>

      <div>
        <InputLabel for="password" value="Mật khẩu" />
        <TextInput id="password" type="password" class="mt-1 block w-full rounded-xl"
          v-model="form.password" required autocomplete="new-password" />
        <InputError class="mt-2" :message="form.errors.password" />
      </div>

      <div>
        <InputLabel for="password_confirmation" value="Nhập lại mật khẩu" />
        <TextInput id="password_confirmation" type="password" class="mt-1 block w-full rounded-xl"
          v-model="form.password_confirmation" required autocomplete="new-password" />
        <InputError class="mt-2" :message="form.errors.password_confirmation" />
      </div>

      <div class="pt-2">
        <PrimaryButton
          class="w-full justify-center rounded-2xl bg-orange-500 hover:bg-orange-400 active:bg-orange-600 focus:ring-orange-300"
          :class="{ 'opacity-25': form.processing }"
          :disabled="form.processing"
        >
          Tạo tài khoản
        </PrimaryButton>
      </div>

      <div class="text-center text-sm text-gray-600">
        Đã có tài khoản?
        <Link :href="route('login')" class="font-semibold text-orange-600 hover:text-orange-500">
          Đăng nhập
        </Link>
      </div>
    </form>
  </div>
</template>
