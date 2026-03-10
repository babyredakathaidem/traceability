<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { Head, useForm, router } from '@inertiajs/vue3'
import { ref, computed } from 'vue'
import Modal from '@/Components/Modal.vue'
import InputLabel from '@/Components/InputLabel.vue'
import InputError from '@/Components/InputError.vue'
import PrimaryButton from '@/Components/PrimaryButton.vue'
import SecondaryButton from '@/Components/SecondaryButton.vue'
import { 
  UserPlusIcon, 
  EnvelopeIcon, 
  ShieldCheckIcon, 
  PencilSquareIcon, 
  TrashIcon,
  IdentificationIcon,
  KeyIcon,
  CheckCircleIcon
} from '@heroicons/vue/24/outline'

const props = defineProps({
  staffList: Array,
  availablePermissions: Object, // { key: label }
})

// ── State ────────────────────────────────────────────────
const showingModal = ref(false)
const isEditing   = ref(false)
const editingId    = ref(null)

const form = useForm({
  name:        '',
  email:       '',
  password:    '',
  permissions: [],
})

const openCreate = () => {
  form.reset()
  form.permissions = []
  isEditing.value = false
  showingModal.value = true
}

const openEdit = (u) => {
  form.name        = u.name
  form.email       = u.email
  form.permissions = Array.isArray(u.permissions) ? u.permissions : []
  form.password    = ''
  editingId.value  = u.id
  isEditing.value  = true
  showingModal.value = true
}

const submit = () => {
  if (isEditing.value) {
    form.put(route('enterprise.users.update', editingId.value), {
      onSuccess: () => closeModal(),
    })
  } else {
    form.post(route('enterprise.users.store'), {
      onSuccess: () => closeModal(),
    })
  }
}

const remove = (u) => {
  if (!confirm(`Xóa tài khoản của "${u.name}"?`)) return
  router.delete(route('enterprise.users.destroy', u.id))
}

const closeModal = () => {
  showingModal.value = false
  form.reset()
}

const togglePerm = (key) => {
  const idx = form.permissions.indexOf(key)
  if (idx >= 0) form.permissions.splice(idx, 1)
  else form.permissions.push(key)
}

const inputCls = "w-full bg-black/40 border border-glass rounded-2xl px-4 py-3 text-sm text-white/90 placeholder:text-white/20 focus:border-brand-500 outline-none transition-all focus:ring-4 focus:ring-brand-500/5"
</script>

<template>
  <Head title="Quản lý nhân sự" />

  <AuthenticatedLayout>
    <div class="space-y-8 max-w-5xl mx-auto pb-12">
      
      <!-- Header -->
      <div class="flex flex-col md:flex-row md:items-end justify-between gap-6" data-aos="fade-down">
        <div class="space-y-1">
          <div class="flex items-center gap-2 text-brand-400 font-black text-[10px] uppercase tracking-[0.3em]">
            <span class="w-8 h-[1px] bg-brand-500/50"></span>
            Administration
          </div>
          <h1 class="text-3xl font-black text-white tracking-tight">Đội ngũ & <span class="text-brand-400">Nhân sự</span></h1>
          <p class="text-white/40 text-sm font-medium">Phân quyền chi tiết cho nhân viên tham gia chuỗi cung ứng.</p>
        </div>
        
        <button 
          @click="openCreate"
          class="flex items-center gap-2 bg-brand-500 hover:bg-brand-400 text-cosmic-950 px-6 py-3 rounded-2xl transition-all font-black text-xs uppercase tracking-widest shadow-xl shadow-brand-900/20 active:scale-95"
        >
          <UserPlusIcon class="w-5 h-5 stroke-[3px]" />
          Thêm nhân viên
        </button>
      </div>

      <!-- Users Grid -->
      <div v-if="staffList.length === 0" 
        class="bg-white/5 border border-dashed border-white/10 rounded-[2rem] p-20 text-center" data-aos="zoom-in">
        <div class="w-16 h-16 rounded-full bg-white/5 flex items-center justify-center mx-auto mb-4 text-white/20">
          <UserPlusIcon class="w-8 h-8" />
        </div>
        <div class="text-white/40 font-bold uppercase tracking-widest">Chưa có tài khoản nhân viên</div>
      </div>

      <div v-else class="grid grid-cols-1 md:grid-cols-2 gap-4" v-auto-animate>
        <div 
          v-for="(u, i) in staffList" 
          :key="u.id"
          class="bg-white/5 border border-glass rounded-3xl p-5 hover:bg-white/8 hover:-translate-y-1 transition-all duration-300 group relative overflow-hidden shadow-lg"
          data-aos="fade-up" :data-aos-delay="i * 50"
        >
          <div class="flex items-start gap-4 relative z-10">
            <div class="w-12 h-12 rounded-2xl bg-brand-500/10 border border-brand-500/20 flex items-center justify-center text-brand-400 shrink-0 shadow-inner">
              <span class="text-lg font-black uppercase">{{ u.name[0] }}</span>
            </div>
            <div class="flex-1 min-w-0">
              <h3 class="text-sm font-black text-white truncate">{{ u.name }}</h3>
              <div class="flex items-center gap-1.5 text-xs text-white/30 mt-0.5 mb-3">
                <EnvelopeIcon class="w-3.5 h-3.5" />
                <span class="truncate">{{ u.email }}</span>
              </div>
              
              <!-- Mini Perms List -->
              <div class="flex flex-wrap gap-1">
                <span v-for="p in (u.permissions || [])" :key="p" 
                  class="text-[8px] px-1.5 py-0.5 rounded bg-white/5 text-white/40 border border-white/5 uppercase font-bold tracking-tighter">
                  {{ p.split('.').pop() }}
                </span>
                <span v-if="!u.permissions?.length" class="text-[8px] text-white/20 italic">Chưa phân quyền</span>
              </div>
            </div>
            
            <div class="flex flex-col gap-1">
              <button @click="openEdit(u)" class="p-2 rounded-xl bg-white/5 border border-white/10 text-white/30 hover:text-white hover:bg-white/10 transition-all" title="Chỉnh sửa">
                <PencilSquareIcon class="w-4 h-4" />
              </button>
              <button @click="remove(u)" class="p-2 rounded-xl bg-white/5 border border-white/10 text-white/20 hover:text-red-400 hover:bg-red-500/10 transition-all" title="Xóa">
                <TrashIcon class="w-4 h-4" />
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal Form -->
    <Modal :show="showingModal" @close="closeModal" max-width="2xl">
      <div class="p-8 bg-cosmic-950 relative overflow-hidden">
        <div class="absolute -right-20 -top-20 w-64 h-64 bg-brand-500/10 blur-[100px] rounded-full"></div>
        
        <div class="relative z-10">
          <h2 class="text-2xl font-black text-white tracking-tight">{{ isEditing ? 'Cập nhật tài khoản' : 'Thêm nhân viên mới' }}</h2>
          <p class="text-xs text-white/40 mb-8 font-medium">Thông tin tài khoản sẽ được dùng để đăng nhập và thực hiện nghiệp vụ.</p>

          <form @submit.prevent="submit" class="space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <!-- Name -->
              <div>
                <InputLabel value="Họ và tên" class="text-[10px] text-white/30 uppercase font-black tracking-widest mb-1.5 ml-1" />
                <div class="relative group">
                  <IdentificationIcon class="absolute left-4 top-1/2 -translate-y-1/2 w-5 h-5 text-white/20 group-focus-within:text-brand-400 transition-colors" />
                  <input v-model="form.name" :class="[inputCls, 'pl-12']" placeholder="Nguyễn Văn A" required />
                </div>
                <InputError :message="form.errors.name" class="mt-2" />
              </div>

              <!-- Email -->
              <div>
                <InputLabel value="Địa chỉ Email" class="text-[10px] text-white/30 uppercase font-black tracking-widest mb-1.5 ml-1" />
                <div class="relative group">
                  <EnvelopeIcon class="absolute left-4 top-1/2 -translate-y-1/2 w-5 h-5 text-white/20 group-focus-within:text-brand-400 transition-colors" />
                  <input v-model="form.email" type="email" :class="[inputCls, 'pl-12']" placeholder="email@congty.com" :disabled="isEditing" required />
                </div>
                <InputError :message="form.errors.email" class="mt-2" />
              </div>

              <!-- Password (Only for Create) -->
              <div v-if="!isEditing" class="md:col-span-2">
                <InputLabel value="Mật khẩu ban đầu" class="text-[10px] text-white/30 uppercase font-black tracking-widest mb-1.5 ml-1" />
                <div class="relative group">
                  <KeyIcon class="absolute left-4 top-1/2 -translate-y-1/2 w-5 h-5 text-white/20 group-focus-within:text-brand-400 transition-colors" />
                  <input v-model="form.password" type="password" :class="[inputCls, 'pl-12']" placeholder="••••••••" required />
                </div>
                <InputError :message="form.errors.password" class="mt-2" />
              </div>

              <!-- Permissions Grid -->
              <div class="md:col-span-2">
                <label class="text-[10px] text-white/30 uppercase font-black tracking-widest mb-3 block ml-1">Phân quyền chức năng</label>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 bg-black/20 p-4 rounded-2xl border border-glass">
                  <button 
                    v-for="(label, key) in availablePermissions" 
                    :key="key"
                    type="button"
                    @click="togglePerm(key)"
                    class="flex items-center gap-3 p-3 rounded-xl border transition-all text-left group"
                    :class="form.permissions.includes(key) ? 'bg-brand-500/10 border-brand-500/40' : 'bg-white/5 border-white/5 hover:border-white/20'"
                  >
                    <div class="w-5 h-5 rounded-md border flex items-center justify-center transition-colors"
                      :class="form.permissions.includes(key) ? 'bg-brand-500 border-brand-500 text-cosmic-950' : 'border-white/20'">
                      <CheckCircleIcon v-if="form.permissions.includes(key)" class="w-4 h-4" />
                    </div>
                    <span class="text-xs font-bold transition-colors" :class="form.permissions.includes(key) ? 'text-white' : 'text-white/40 group-hover:text-white/60'">
                      {{ label }}
                    </span>
                  </button>
                </div>
                <InputError :message="form.errors.permissions" class="mt-2" />
              </div>
            </div>

            <div class="mt-8 pt-6 border-t border-white/5 flex justify-end gap-4">
              <SecondaryButton @click="closeModal" class="rounded-2xl px-8 py-3">Hủy bỏ</SecondaryButton>
              <button 
                :disabled="form.processing"
                class="bg-brand-500 text-cosmic-950 px-10 py-3 rounded-2xl font-black text-xs uppercase tracking-widest shadow-xl shadow-brand-900/30 hover:bg-brand-400 active:scale-95 transition-all disabled:opacity-50"
              >
                {{ form.processing ? 'Đang thực hiện...' : (isEditing ? 'LƯU THAY ĐỔI' : 'TẠO TÀI KHOẢN') }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </Modal>
  </AuthenticatedLayout>
</template>
