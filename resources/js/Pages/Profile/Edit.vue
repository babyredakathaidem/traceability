<script setup>
import { Head, useForm, usePage } from '@inertiajs/vue3'
import { computed, ref } from 'vue'
import UiButton from '@/Components/ui/UiButton.vue'
import UiInput  from '@/Components/ui/UiInput.vue'

const page       = usePage()
const user       = computed(() => page.props.auth?.user)
const enterprise = computed(() => page.props.auth?.enterprise)
const isStaff    = computed(() => user.value?.role === 'enterprise_staff')

const roleInfo = computed(() => {
  if (user.value?.is_super_admin)              return { label: 'Super Admin',      icon: '👑', color: 'text-yellow-300' }
  if (user.value?.role === 'enterprise_admin') return { label: 'Quản trị viên DN', icon: '', color: 'text-brand-300' }
  if (user.value?.role === 'enterprise_staff') return { label: 'Nhân viên DN',      icon: '👤', color: 'text-white/70' }
  return { label: 'Người dùng', icon: '👤', color: 'text-white/50' }
})

const enterpriseStatus = computed(() => ({
  approved: { label: 'Đã duyệt', cls: 'bg-green-500/10 text-green-300 border-green-500/30' },
  pending:  { label: 'Chờ duyệt', cls: 'bg-amber-500/10 text-amber-300 border-amber-500/30' },
  rejected: { label: 'Bị từ chối', cls: 'bg-red-500/10 text-red-300 border-red-500/30' },
}[enterprise.value?.status] ?? { label: '—', cls: 'bg-white/5 text-white/30 border-glass' }))

const initials = computed(() => {
  const n = user.value?.name ?? ''
  return n.split(' ').map(w => w[0]).slice(-2).join('').toUpperCase() || 'U'
})

const showPw = ref(false)
const pwForm = useForm({
  current_password:      '',
  password:              '',
  password_confirmation: '',
})
function submitPw() {
  pwForm.put(route('password.update'), {
    onSuccess: () => { pwForm.reset(); showPw.value = false },
  })
}

const PERM_LABELS = {
  'enterprise.products.view':       { label: 'Xem sản phẩm',    icon: '📦' },
  'enterprise.products.manage':     { label: 'Quản lý sản phẩm', icon: '📦' },
  'enterprise.batches.view':        { label: 'Xem lô hàng',      icon: '🗂️' },
  'enterprise.batches.manage':      { label: 'Quản lý lô hàng',  icon: '🗂️' },
  'enterprise.trace_events.view':   { label: 'Xem sự kiện',      icon: '🔍' },
  'enterprise.trace_events.create': { label: 'Tạo sự kiện',      icon: '➕' },
  'enterprise.trace_events.manage': { label: 'Quản lý sự kiện',  icon: '✏️' },
  'enterprise.qrcodes.view':        { label: 'Xem QR Codes',     icon: '📱' },
  'enterprise.qrcodes.manage':      { label: 'Quản lý QR',       icon: '📱' },
}
</script>

<template>
  <Head title="Hồ sơ cá nhân" />

  <div class="max-w-2xl mx-auto space-y-5">

    <!-- Hero card -->
    <div class="rounded-2xl border border-glass bg-gradient-to-br from-brand-500/10 to-black/40 p-6 flex items-center gap-5">
      <div class="h-16 w-16 rounded-2xl bg-brand-500/20 border border-brand-500/30 flex items-center justify-center text-2xl font-black text-brand-300 shrink-0 select-none">
        {{ initials }}
      </div>
      <div class="flex-1 min-w-0">
        <div class="text-xl font-extrabold text-white/90 truncate">{{ user?.name }}</div>
        <div class="text-sm text-white/50 truncate">{{ user?.email }}</div>
        <div class="flex items-center gap-2 mt-1.5">
          <span :class="['text-sm font-semibold', roleInfo.color]">
            {{ roleInfo.icon }} {{ roleInfo.label }}
          </span>
          <span v-if="enterprise" class="text-white/20">•</span>
          <span v-if="enterprise" class="text-sm text-white/40 truncate">{{ enterprise.name }}</span>
        </div>
      </div>
      <UiButton variant="outline" size="sm" @click="showPw = !showPw" class="shrink-0">
         Đổi Mật Khẩu
      </UiButton>
    </div>

    <!-- Thông tin DN -->
    <div v-if="enterprise" class="rounded-2xl border border-glass bg-black/30 p-5">
      <div class="text-xs font-semibold text-white/30 uppercase tracking-widest mb-4">Doanh nghiệp</div>
      <div class="grid grid-cols-3 gap-4 text-sm">
        <div>
          <div class="text-white/40 text-xs mb-1">Tên</div>
          <div class="text-white/90 font-semibold leading-snug">{{ enterprise.name }}</div>
        </div>
        <div>
          <div class="text-white/40 text-xs mb-1">Mã DN</div>
          <div class="font-mono text-brand-300 font-bold">{{ enterprise.code ?? '—' }}</div>
        </div>
        <div>
          <div class="text-white/40 text-xs mb-1">Trạng thái</div>
          <span :class="['px-2 py-0.5 rounded-lg border text-xs font-semibold', enterpriseStatus.cls]">
            {{ enterpriseStatus.label }}
          </span>
        </div>
      </div>
    </div>

    <!-- Quyền hạn (staff only) -->
    <div v-if="isStaff" class="rounded-2xl border border-glass bg-black/30 p-5">
      <div class="text-xs font-semibold text-white/30 uppercase tracking-widest mb-4">Quyền hạn được cấp</div>
      <div v-if="user?.permissions?.length" class="flex flex-wrap gap-2">
        <span
          v-for="perm in user.permissions"
          :key="perm"
          class="flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs bg-brand-500/10 border border-brand-500/25 text-brand-300"
        >
          <span>{{ PERM_LABELS[perm]?.icon ?? '•' }}</span>
          <span>{{ PERM_LABELS[perm]?.label ?? perm }}</span>
        </span>
      </div>
      <div v-else class="text-white/30 text-sm italic">
        Chưa được cấp quyền nào — liên hệ quản trị viên DN.
      </div>
    </div>

    <!-- Form đổi mật khẩu (slide in/out) -->
    <Transition
      enter-active-class="transition-all duration-300 ease-out"
      enter-from-class="opacity-0 -translate-y-2"
      enter-to-class="opacity-100 translate-y-0"
      leave-active-class="transition-all duration-150 ease-in"
      leave-from-class="opacity-100"
      leave-to-class="opacity-0"
    >
      <div v-if="showPw" class="rounded-2xl border border-brand-500/20 bg-black/40 p-5 space-y-4">
        <div class="text-xs font-semibold text-white/30 uppercase tracking-widest">Đổi mật khẩu</div>
        <UiInput
          label="Mật khẩu hiện tại *"
          type="password"
          v-model="pwForm.current_password"
          :error="pwForm.errors.current_password"
          autocomplete="current-password"
        />
        <div class="grid grid-cols-2 gap-3">
          <UiInput
            label="Mật khẩu mới *"
            type="password"
            v-model="pwForm.password"
            :error="pwForm.errors.password"
          />
          <UiInput
            label="Xác nhận mật khẩu *"
            type="password"
            v-model="pwForm.password_confirmation"
            :error="pwForm.errors.password_confirmation"
          />
        </div>
        <div v-if="pwForm.recentlySuccessful" class="text-sm text-green-400 font-semibold">
          ✅ Đổi mật khẩu thành công!
        </div>
        <div class="flex gap-2 justify-end">
          <UiButton variant="outline" size="sm" @click="showPw = false; pwForm.reset()">Huỷ</UiButton>
          <UiButton size="sm" :disabled="pwForm.processing" @click="submitPw">
            {{ pwForm.processing ? 'Đang lưu...' : 'Lưu mật khẩu mới' }}
          </UiButton>
        </div>
      </div>
    </Transition>

  </div>
</template>